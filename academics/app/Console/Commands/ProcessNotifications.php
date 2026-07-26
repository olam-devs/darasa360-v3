<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process {--limit=50 : Maximum notifications to process} {--school= : Process for specific school}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending notifications from the queue (SMS and Email)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting notification processing...');

        $limit = (int) $this->option('limit');
        $schoolId = $this->option('school');

        $totalProcessed = 0;
        $totalFailed = 0;

        // If specific school, process only that school
        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            // Process all schools
            $schools = School::where('is_deleted', false)->get();
        }

        if ($schools->isEmpty()) {
            $this->warn('No schools found to process.');
            return 0;
        }

        $this->info("Processing notifications for " . $schools->count() . " school(s)...");

        foreach ($schools as $school) {
            $this->line("Processing school: {$school->name}");

            // Set tenant database
            if (!$school->db_username || !$school->db_password) {
                $this->warn("  Skipping {$school->name}: no dedicated db credentials stored yet.");
                continue;
            }
            $school->useAsTenant();

            // Process notifications for this school
            $notificationService = new NotificationService();

            try {
                $result = $notificationService->processPendingNotifications($limit);

                $this->info("  Processed: {$result['processed']}");
                $this->info("  Failed: {$result['failed']}");

                $totalProcessed += $result['processed'];
                $totalFailed += $result['failed'];

            } catch (\Exception $e) {
                $this->error("  Error processing school {$school->name}: " . $e->getMessage());
            }
        }

        $this->line('');
        $this->info('=== SUMMARY ===');
        $this->info("Total Processed: {$totalProcessed}");
        $this->info("Total Failed: {$totalFailed}");

        if ($totalFailed > 0) {
            $this->warn("Some notifications failed. Check logs for details.");
        }

        $this->info('Notification processing completed!');

        return 0;
    }
}
