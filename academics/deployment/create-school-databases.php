<?php
/**
 * Auto-Create School Databases
 * Creates database for each school and runs migrations
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=================================================================\n";
echo "   Auto-Create School Databases (Multi-Tenant)\n";
echo "=================================================================\n\n";

$dbHost = config('database.connections.mysql.host');
$dbUser = config('database.connections.mysql.username');
$dbPassword = config('database.connections.mysql.password');
$dbMain = config('database.connections.mysql.database');

echo "Main database: {$dbMain}\n";
echo "User: {$dbUser}\n\n";

try {
    // Get all schools
    $schools = DB::table('schools')
        ->where('is_deleted', 0)
        ->where('status', 'active')
        ->get();

    if ($schools->isEmpty()) {
        echo "No schools found.\n";
        exit(0);
    }

    echo "Found " . count($schools) . " school(s)\n\n";

    $success = 0;
    $failed = 0;

    foreach ($schools as $school) {
        // Parse database URL to extract credentials
        $parsed = parse_url($school->database_url);

        if (!isset($parsed['path']) || !isset($parsed['user'])) {
            echo "⚠ Skipping {$school->name} - invalid database URL format\n";
            echo "   Expected format: mysql://username:password@host:port/database\n\n";
            $failed++;
            continue;
        }

        $schoolDbName = ltrim($parsed['path'], '/');
        $schoolDbUser = $parsed['user'];
        $schoolDbPassword = $parsed['pass'] ?? env('DB_PASSWORD', '');
        $schoolDbHost = $parsed['host'] ?? '127.0.0.1';
        $schoolDbPort = $parsed['port'] ?? 3306;

        echo "=================================================================\n";
        echo "Processing: {$school->name}\n";
        echo "Database: {$schoolDbName}\n";
        echo "User: {$schoolDbUser}\n";
        echo "Host: {$schoolDbHost}:{$schoolDbPort}\n";
        echo "=================================================================\n\n";

        // Step 1: Try to create database
        echo "Step 1: Creating database...\n";
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$schoolDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Database created/verified\n\n";
        } catch (Exception $e) {
            echo "✗ Failed to create database: " . $e->getMessage() . "\n";
            echo "→ You may not have CREATE DATABASE privilege\n";
            echo "→ Contact Evolution support to create: {$schoolDbName}\n\n";
            $failed++;
            continue;
        }

        // Step 2: Create MySQL user and grant permissions
        echo "Step 2: Creating user and granting permissions...\n";
        try {
            // Drop user if exists (cleanup)
            try {
                DB::statement("DROP USER IF EXISTS '{$schoolDbUser}'@'localhost'");
            } catch (Exception $e) {
                // Ignore if user doesn't exist
            }

            // Create user with password
            if (empty($schoolDbPassword)) {
                DB::statement("CREATE USER '{$schoolDbUser}'@'localhost'");
            } else {
                DB::statement("CREATE USER '{$schoolDbUser}'@'localhost' IDENTIFIED BY '{$schoolDbPassword}'");
            }

            // Grant all privileges on the specific database
            DB::statement("GRANT ALL PRIVILEGES ON `{$schoolDbName}`.* TO '{$schoolDbUser}'@'localhost'");
            DB::statement("FLUSH PRIVILEGES");
            echo "✓ User created and permissions granted\n\n";
        } catch (Exception $e) {
            echo "⚠ Could not create user or grant permissions: " . $e->getMessage() . "\n";
            echo "→ Attempting to continue with existing user...\n\n";
        }

        // Step 3: Test connection with school-specific credentials
        echo "Step 3: Testing connection...\n";
        try {
            config([
                'database.connections.school.host' => $schoolDbHost,
                'database.connections.school.port' => $schoolDbPort,
                'database.connections.school.database' => $schoolDbName,
                'database.connections.school.username' => $schoolDbUser,
                'database.connections.school.password' => $schoolDbPassword,
            ]);
            DB::purge('school');
            DB::connection('school')->getPdo();
            echo "✓ Connection successful with credentials: {$schoolDbUser}@{$schoolDbHost}!\n\n";
        } catch (Exception $e) {
            echo "✗ Connection failed: " . $e->getMessage() . "\n";
            echo "→ Check if user '{$schoolDbUser}' has access to '{$schoolDbName}'\n\n";
            $failed++;
            continue;
        }

        // Step 4: Check if tables exist
        echo "Step 4: Checking tables...\n";
        try {
            $tables = DB::connection('school')->select("SHOW TABLES");

            if (empty($tables)) {
                echo "→ Database is empty, needs migrations\n\n";

                // Step 5: Run migrations
                echo "Step 5: Running migrations...\n";
                echo "→ This will create all tables in the school database\n\n";

                // Change to school database connection
                putenv("DB_CONNECTION=school");
                putenv("DB_DATABASE={$schoolDbName}");

                // Run migrations
                exec("cd " . __DIR__ . " && php artisan migrate --database=school --path=database/migrations/school --force 2>&1", $output, $returnCode);

                if ($returnCode === 0) {
                    echo "✓ Migrations completed!\n";
                    echo implode("\n", $output) . "\n\n";
                } else {
                    echo "⚠ Migrations had issues:\n";
                    echo implode("\n", $output) . "\n\n";
                }
            } else {
                echo "✓ Database has " . count($tables) . " tables already\n\n";
            }

            $success++;
            echo "✓✓✓ School database ready: {$schoolDbName}\n\n";

        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            $failed++;
        }
    }

    echo "=================================================================\n";
    echo "   Summary\n";
    echo "=================================================================\n\n";

    echo "Total schools: " . count($schools) . "\n";
    echo "Successfully created: {$success}\n";
    echo "Failed: {$failed}\n\n";

    if ($success > 0) {
        echo "✓✓✓ Success! School databases are ready!\n\n";

        echo "Next steps:\n";
        echo "1. Clear caches:\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan cache:clear\n\n";

        echo "2. Test:\n";
        echo "   - View school details\n";
        echo "   - Login as school owner\n";
        echo "   - Everything should work!\n\n";
    }

    if ($failed > 0) {
        echo "⚠ Some schools failed\n";
        echo "→ You may need Evolution support to create those databases\n";
        echo "→ Or check if you have CREATE DATABASE privilege\n\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=================================================================\n";
