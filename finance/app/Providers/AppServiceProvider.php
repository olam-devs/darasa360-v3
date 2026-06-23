<?php

namespace App\Providers;

use App\Models\Central\School;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind current school as a simple binding (not singleton to avoid caching issues)
        $this->app->bind('current_school', function () {
            return $this->resolveCurrentSchool();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for MySQL key length limit on older versions
        Schema::defaultStringLength(191);

        View::composer('layouts.accountant', function ($view) {
            /** @var \Illuminate\View\View $view */
            $data = $view->getData();
            if (empty($data['settings'])) {
                try {
                    $view->with('settings', SchoolSetting::getSettings());
                } catch (\Throwable $e) {
                    $view->with('settings', null);
                }
            }
        });
    }

    /**
     * Resolve the current school from database name.
     */
    protected function resolveCurrentSchool(): ?School
    {
        try {
            // Get the database name from environment
            $dbName = env('TENANT_DB_DATABASE') ?: env('DB_DATABASE');

            if (! $dbName) {
                return null;
            }

            // Try to find the school
            $school = School::on('central')->where('database_name', $dbName)->first();

            // Fallback: if only one school exists, use it
            if (! $school) {
                $count = School::on('central')->count();
                if ($count === 1) {
                    $school = School::on('central')->first();
                }
            }

            return $school;
        } catch (\Exception $e) {
            // Log error but don't crash - central database might not be available
            Log::debug('Could not resolve current school: '.$e->getMessage());

            return null;
        }
    }
}
