<?php
/**
 * Grant School Database Permissions - FINAL VERSION
 * Extracts database names from database_url column
 */

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get database credentials from config
$dbHost = config('database.connections.mysql.host');
$dbUser = config('database.connections.mysql.username');
$dbPassword = config('database.connections.mysql.password');
$dbMain = config('database.connections.mysql.database');

echo "=================================================================\n";
echo "   DARASA 360 - Grant School Database Permissions\n";
echo "=================================================================\n\n";

echo "Database Configuration:\n";
echo "  Host: {$dbHost}\n";
echo "  User: {$dbUser}\n";
echo "  Main Database: {$dbMain}\n\n";

try {
    // Get all schools
    $schools = DB::table('schools')
        ->select('id', 'name', 'school_code', 'database_url')
        ->where('is_deleted', 0)
        ->where('status', 'active')
        ->get();

    if ($schools->isEmpty()) {
        echo "No active schools found in database.\n";
        echo "Create a school from the admin dashboard first.\n";
        exit(0);
    }

    echo "Schools found:\n";
    $schoolDatabases = [];

    foreach ($schools as $school) {
        // Extract database name from database_url
        // Format: mysql://username:password@localhost/database_name
        if (preg_match('/\/([^\/]+)$/', $school->database_url, $matches)) {
            $dbName = $matches[1];
            $schoolDatabases[] = [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->school_code,
                'database' => $dbName,
                'url' => $school->database_url
            ];

            echo "  {$school->id}. {$school->name}\n";
            echo "     Code: {$school->school_code}\n";
            echo "     Database: {$dbName}\n\n";
        } else {
            echo "  {$school->id}. {$school->name} - WARNING: Could not parse database URL\n";
            echo "     URL: {$school->database_url}\n\n";
        }
    }

    if (empty($schoolDatabases)) {
        echo "ERROR: No valid school databases found!\n";
        exit(1);
    }

    echo "=================================================================\n";
    echo "   SQL Commands for phpMyAdmin\n";
    echo "=================================================================\n\n";

    echo "INSTRUCTIONS:\n";
    echo "1. Login to phpMyAdmin (via cPanel or direct URL)\n";
    echo "2. Click on database '{$dbMain}' in the left sidebar\n";
    echo "3. Click the 'SQL' tab at the top\n";
    echo "4. Copy ALL the commands below\n";
    echo "5. Paste into the SQL text area\n";
    echo "6. Click 'Go' button at the bottom\n";
    echo "7. You should see a green success message\n\n";

    echo str_repeat("=", 65) . "\n";
    echo "COPY EVERYTHING BELOW THIS LINE\n";
    echo str_repeat("=", 65) . "\n\n";

    $sqlCommands = "-- Grant permissions for DARASA 360 school databases\n";
    $sqlCommands .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlCommands .= "-- User: {$dbUser}\n\n";

    foreach ($schoolDatabases as $school) {
        $dbName = $school['database'];
        $schoolName = $school['name'];

        $sqlCommands .= "-- School: {$schoolName}\n";
        $sqlCommands .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $sqlCommands .= "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost';\n";
        $sqlCommands .= "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}';\n";
        $sqlCommands .= "\n";
    }

    // Wildcard permissions for future schools
    $sqlCommands .= "-- Wildcard permissions for all future schools\n";
    $sqlCommands .= "GRANT ALL PRIVILEGES ON `school_%`.* TO '{$dbUser}'@'localhost';\n";
    $sqlCommands .= "GRANT ALL PRIVILEGES ON `school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";

    // Also grant for user-prefixed databases if needed
    $userPrefix = explode('_', $dbUser)[0];
    $sqlCommands .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_school_%`.* TO '{$dbUser}'@'localhost';\n";
    $sqlCommands .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    $sqlCommands .= "\n";

    $sqlCommands .= "FLUSH PRIVILEGES;\n";

    echo $sqlCommands;

    echo "\n" . str_repeat("=", 65) . "\n";
    echo "COPY EVERYTHING ABOVE THIS LINE\n";
    echo str_repeat("=", 65) . "\n\n";

    // Save to file
    $sqlFile = __DIR__ . '/grant-permissions.sql';
    file_put_contents($sqlFile, $sqlCommands);

    echo "✓ SQL commands also saved to: grant-permissions.sql\n";
    echo "  You can download this file if needed\n\n";

    echo "=================================================================\n";
    echo "   Try Automatic Grant (may not work without GRANT privilege)\n";
    echo "=================================================================\n\n";

    $success = true;
    $createdDbs = [];
    $grantedDbs = [];
    $failedDbs = [];

    foreach ($schoolDatabases as $school) {
        $dbName = $school['database'];
        $schoolName = $school['name'];

        echo "Processing: {$schoolName}\n";
        echo "  Database: {$dbName}\n";

        // Try to create database
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✓ Database created/verified\n";
            $createdDbs[] = $dbName;
        } catch (Exception $e) {
            echo "  ⚠ Create database: " . $e->getMessage() . "\n";
        }

        // Try to grant permissions
        try {
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}'");
            echo "  ✓ Permissions granted automatically!\n";
            $grantedDbs[] = $dbName;
        } catch (Exception $e) {
            echo "  ✗ Grant failed: " . $e->getMessage() . "\n";
            echo "  → You need to use phpMyAdmin method\n";
            $success = false;
            $failedDbs[] = $dbName;
        }

        echo "\n";
    }

    // Try wildcard permissions
    try {
        DB::statement("GRANT ALL PRIVILEGES ON `school_%`.* TO '{$dbUser}'@'localhost'");
        DB::statement("GRANT ALL PRIVILEGES ON `school_%`.* TO '{$dbUser}'@'{$dbHost}'");
        DB::statement("GRANT ALL PRIVILEGES ON `{$userPrefix}_school_%`.* TO '{$dbUser}'@'localhost'");
        DB::statement("GRANT ALL PRIVILEGES ON `{$userPrefix}_school_%`.* TO '{$dbUser}'@'{$dbHost}'");
        DB::statement("FLUSH PRIVILEGES");
        echo "✓ Wildcard permissions granted!\n\n";
    } catch (Exception $e) {
        echo "✗ Wildcard permissions failed: " . $e->getMessage() . "\n";
        echo "→ Use phpMyAdmin method\n\n";
        $success = false;
    }

    echo "=================================================================\n";

    if ($success && !empty($grantedDbs)) {
        echo "   ✓✓✓ SUCCESS! Permissions Granted Automatically! ✓✓✓\n";
        echo "=================================================================\n\n";

        echo "Databases with permissions:\n";
        foreach ($grantedDbs as $db) {
            echo "  ✓ {$db}\n";
        }
        echo "\n";

        echo "Next steps:\n";
        echo "  1. Clear Laravel caches:\n";
        echo "     cd /home/olamtecc/domains/darasa360.olamtec.co.tz\n";
        echo "     php artisan config:clear\n";
        echo "     php artisan cache:clear\n\n";

        echo "  2. Test school access:\n";
        echo "     - View school details in admin dashboard\n";
        echo "     - Login as school owner\n";
        echo "     - All features should work now!\n\n";

    } else {
        echo "   Manual Action Required - Use phpMyAdmin\n";
        echo "=================================================================\n\n";

        echo "Your database user doesn't have GRANT privileges.\n";
        echo "This is NORMAL for shared hosting.\n\n";

        echo "FOLLOW THESE STEPS:\n\n";

        echo "1. Copy the SQL commands shown above (between the === lines)\n\n";

        echo "2. Login to phpMyAdmin:\n";
        echo "   - Via cPanel → phpMyAdmin\n";
        echo "   - Or direct URL if you have it\n\n";

        echo "3. In phpMyAdmin:\n";
        echo "   - Click on '{$dbMain}' database (or any database)\n";
        echo "   - Click 'SQL' tab at the top\n";
        echo "   - Paste the SQL commands\n";
        echo "   - Click 'Go'\n\n";

        echo "4. You should see a green success message\n\n";

        if (!empty($failedDbs)) {
            echo "Databases that need permissions:\n";
            foreach ($failedDbs as $db) {
                echo "  - {$db}\n";
            }
            echo "\n";
        }
    }

    echo "=================================================================\n";
    echo "   Database Summary\n";
    echo "=================================================================\n\n";

    echo "Total schools: " . count($schoolDatabases) . "\n";
    echo "School databases:\n";
    foreach ($schoolDatabases as $school) {
        echo "  - {$school['database']}\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=================================================================\n";
echo "   Script Complete\n";
echo "=================================================================\n";
