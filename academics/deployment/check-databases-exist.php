<?php
/**
 * Check if school databases already exist
 * This helps us know if we need to create them or just grant permissions
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=================================================================\n";
echo "   Check School Databases Status\n";
echo "=================================================================\n\n";

// Get credentials
$dbHost = config('database.connections.mysql.host');
$dbUser = config('database.connections.mysql.username');
$dbPassword = config('database.connections.mysql.password');

try {
    // Get all databases accessible to this user
    $databases = DB::select("SHOW DATABASES");
    $existingDbs = array_map(fn($db) => $db->Database, $databases);

    echo "Databases you have access to:\n";
    foreach ($existingDbs as $db) {
        echo "  - {$db}\n";
    }
    echo "\n";

    // Get school databases needed
    $schools = DB::table('schools')
        ->select('id', 'name', 'school_code', 'database_url')
        ->where('is_deleted', 0)
        ->where('status', 'active')
        ->get();

    if ($schools->isEmpty()) {
        echo "No schools found.\n";
        exit(0);
    }

    echo "=================================================================\n";
    echo "   School Database Status\n";
    echo "=================================================================\n\n";

    $needToCreate = [];
    $alreadyExist = [];

    foreach ($schools as $school) {
        // Extract database name from URL
        if (preg_match('/\/([^\/]+)$/', $school->database_url, $matches)) {
            $dbName = $matches[1];

            echo "School: {$school->name}\n";
            echo "  Database needed: {$dbName}\n";

            // Check if database exists
            if (in_array($dbName, $existingDbs)) {
                echo "  Status: ✓ Database EXISTS and you have access!\n";
                $alreadyExist[] = $dbName;

                // Try to test connection
                try {
                    config(['database.connections.test.database' => $dbName]);
                    DB::connection('test')->getPdo();
                    echo "  Connection: ✓ Can connect successfully!\n";
                } catch (Exception $e) {
                    echo "  Connection: ✗ Cannot connect: " . $e->getMessage() . "\n";
                }
            } else {
                echo "  Status: ✗ Database does NOT exist or no access\n";
                $needToCreate[] = $dbName;
            }
            echo "\n";
        }
    }

    echo "=================================================================\n";
    echo "   Summary\n";
    echo "=================================================================\n\n";

    if (!empty($alreadyExist)) {
        echo "✓ Databases that already exist:\n";
        foreach ($alreadyExist as $db) {
            echo "  - {$db}\n";
        }
        echo "\n";
    }

    if (!empty($needToCreate)) {
        echo "✗ Databases that need to be created:\n";
        foreach ($needToCreate as $db) {
            echo "  - {$db}\n";
        }
        echo "\n";

        echo "=================================================================\n";
        echo "   How to Create These Databases\n";
        echo "=================================================================\n\n";

        echo "Since Evolution hosting doesn't have cPanel, you have these options:\n\n";

        echo "OPTION 1: Contact Evolution Support (RECOMMENDED)\n";
        echo "  - Email or ticket to Evolution support\n";
        echo "  - Ask them to create these databases:\n\n";
        foreach ($needToCreate as $db) {
            echo "    Database: {$db}\n";
            echo "    User: {$dbUser}\n";
            echo "    Privileges: ALL\n\n";
        }

        echo "OPTION 2: Use Evolution's Web Control Panel\n";
        echo "  - Login to Evolution's hosting control panel\n";
        echo "  - Look for 'MySQL' or 'Databases' section\n";
        echo "  - Create the databases listed above\n";
        echo "  - Grant user '{$dbUser}' ALL privileges\n\n";

        echo "OPTION 3: Support Ticket Template\n";
        echo "  Copy and send this to Evolution support:\n\n";
        echo str_repeat("-", 65) . "\n";
        echo "Subject: Request to Create MySQL Databases\n\n";
        echo "Hello Evolution Support,\n\n";
        echo "Please create the following MySQL databases and grant permissions:\n\n";
        echo "Domain: darasa360.olamtec.co.tz\n";
        echo "MySQL User: {$dbUser}\n\n";
        echo "Databases to create:\n";
        foreach ($needToCreate as $db) {
            echo "- {$db}\n";
        }
        echo "\nPlease grant user '{$dbUser}' ALL PRIVILEGES on these databases.\n\n";
        echo "Thank you!\n";
        echo str_repeat("-", 65) . "\n\n";
    } else {
        echo "✓✓✓ GREAT NEWS! ✓✓✓\n\n";
        echo "All school databases already exist and you have access!\n\n";

        echo "Next steps:\n";
        echo "1. Clear caches:\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan cache:clear\n\n";
        echo "2. Try accessing school features:\n";
        echo "   - View school details\n";
        echo "   - Login as school owner\n";
        echo "   - Should work now!\n\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=================================================================\n";
