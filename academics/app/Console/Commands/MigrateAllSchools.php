<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\School;

class MigrateAllSchools extends Command
{
  protected $signature = 'migrate:schools';
  protected $description = 'Run only pending migrations on all school databases';

  public function handle()
  {
    $schools = School::all();

    foreach ($schools as $school) {
      $this->info("Processing: {$school->name} ({$school->database_url})");

      // 1. Configure dynamic connection
      Config::set('database.connections.school_dynamic', [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => $school->database_url,
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
      ]);

      DB::purge('school_dynamic');

      // Migration files under database/migrations/school hardcode
      // Schema::connection('tenant') rather than the dynamic connection above
      // (same as the HTTP-request path, where InitializeTenantDatabase
      // middleware repoints 'tenant' per request). Outside a request there's
      // no middleware, so this command must repoint it itself or every
      // school's migrations silently run against whatever 'tenant.database'
      // was last statically configured to, while the tracking table (which
      // does correctly use school_dynamic) wrongly records them as applied.
      Config::set('database.connections.tenant.database', $school->database_url);
      DB::purge('tenant');
      DB::reconnect('tenant');

      // 2. Verify connection
      try {
        DB::connection('school_dynamic')->getPdo();
      } catch (\Exception $e) {
        $this->error("Connection failed: {$e->getMessage()}");
        continue;
      }

      // 3. Get pending migrations using proper public method
      $migrator = app('migrator');
      $migrator->setConnection('school_dynamic');

      $files = $migrator->getMigrationFiles(database_path('migrations/school'));
      $ran = $migrator->getRepository()->getRan();
      $pendingMigrations = array_diff(array_keys($files), $ran);
      $pendingCount = count($pendingMigrations);

      if ($pendingCount > 0) {
        $this->info("Found {$pendingCount} pending migrations:");
        $this->table(['Pending Migration'], array_map(fn($m) => [$m], $pendingMigrations));

        // 4. Run only pending migrations
        $this->call('migrate', [
          '--path' => 'database/migrations/school',
          '--database' => 'school_dynamic',
          '--force' => true,
        ]);
      } else {
        $this->line("✓ Database already up-to-date");
      }
    }

    $this->info("✅ Migration check completed for all schools");
  }
}
