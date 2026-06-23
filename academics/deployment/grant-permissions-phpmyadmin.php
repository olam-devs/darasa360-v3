<?php
/**
 * Grant School Database Permissions via PHP
 * This script grants permissions to all school databases
 * Run this from browser or command line
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

// Get all schools
try {
    $schools = DB::table('schools')
        ->select('id', 'school_name', 'school_code', 'database_name')
        ->get();

    if ($schools->isEmpty()) {
        echo "No schools found in database.\n";
        echo "Create a school from the admin dashboard first.\n";
        exit(0);
    }

    echo "Schools found:\n";
    foreach ($schools as $school) {
        echo "  {$school->id}. {$school->school_name} (Database: {$school->database_name})\n";
    }
    echo "\n";

    echo "=================================================================\n";
    echo "   SQL Commands to Run in phpMyAdmin\n";
    echo "=================================================================\n\n";

    echo "Copy and paste these commands into phpMyAdmin SQL tab:\n\n";
    echo "-- Click on any database in phpMyAdmin\n";
    echo "-- Go to the 'SQL' tab\n";
    echo "-- Copy and paste ALL the commands below\n";
    echo "-- Click 'Go'\n\n";

    echo "-- ============================================================\n";
    echo "-- Grant permissions for DARASA 360 school databases\n";
    echo "-- ============================================================\n\n";

    foreach ($schools as $school) {
        $dbName = $school->database_name;

        echo "-- School: {$school->school_name}\n";
        echo "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        echo "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}';\n";
        echo "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost';\n";
        echo "\n";
    }

    echo "-- Wildcard permissions for future schools\n";
    echo "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    echo "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'localhost';\n";

    // Get current user prefix for wildcard
    $userPrefix = explode('_', $dbUser)[0];
    echo "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    echo "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'localhost';\n";
    echo "\n";

    echo "FLUSH PRIVILEGES;\n\n";

    echo "=================================================================\n\n";

    // Save to file
    $sqlFile = __DIR__ . '/grant-permissions.sql';
    $sqlContent = "-- Grant permissions for DARASA 360 school databases\n";
    $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($schools as $school) {
        $dbName = $school->database_name;
        $sqlContent .= "-- School: {$school->school_name}\n";
        $sqlContent .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $sqlContent .= "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}';\n";
        $sqlContent .= "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost';\n\n";
    }

    $sqlContent .= "-- Wildcard permissions for future schools\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'localhost';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'localhost';\n\n";
    $sqlContent .= "FLUSH PRIVILEGES;\n";

    file_put_contents($sqlFile, $sqlContent);

    echo "✓ SQL commands saved to: grant-permissions.sql\n";
    echo "✓ You can download this file and import it in phpMyAdmin\n\n";

    echo "=================================================================\n";
    echo "   Alternative: Run via PHP (if user has GRANT privileges)\n";
    echo "=================================================================\n\n";

    echo "Attempting to grant permissions via PHP...\n\n";

    $success = true;
    $errors = [];

    foreach ($schools as $school) {
        $dbName = $school->database_name;

        echo "Processing: {$school->school_name} ({$dbName})\n";

        // Try to create database
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✓ Database created/verified\n";
        } catch (Exception $e) {
            echo "  ! Database creation: " . $e->getMessage() . "\n";
        }

        // Try to grant permissions
        try {
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
            echo "  ✓ Permissions granted via PHP\n";
        } catch (Exception $e) {
            echo "  ✗ Permission grant failed: " . $e->getMessage() . "\n";
            echo "  → You need to run the SQL commands in phpMyAdmin instead\n";
            $success = false;
            $errors[] = $dbName;
        }

        echo "\n";
    }

    // Try wildcard permissions
    try {
        DB::statement("GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'{$dbHost}'");
        DB::statement("GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'localhost'");
        DB::statement("GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'{$dbHost}'");
        DB::statement("GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'localhost'");
        DB::statement("FLUSH PRIVILEGES");
        echo "✓ Wildcard permissions granted via PHP\n\n";
    } catch (Exception $e) {
        echo "✗ Wildcard permissions failed: " . $e->getMessage() . "\n";
        echo "→ You need to run the SQL commands in phpMyAdmin instead\n\n";
        $success = false;
    }

    if ($success) {
        echo "=================================================================\n";
        echo "   ✓ SUCCESS! All permissions granted automatically!\n";
        echo "=================================================================\n\n";
        echo "Next steps:\n";
        echo "  1. Clear caches: php artisan config:clear && php artisan cache:clear\n";
        echo "  2. Test school access: bash test-permissions.sh\n";
        echo "  3. Try viewing school details and logging in as owner\n\n";
    } else {
        echo "=================================================================\n";
        echo "   Manual Action Required\n";
        echo "=================================================================\n\n";
        echo "Your database user doesn't have GRANT privileges.\n";
        echo "Please run the SQL commands in phpMyAdmin:\n\n";
        echo "1. Login to phpMyAdmin\n";
        echo "2. Click on database '{$dbMain}' (or any database)\n";
        echo "3. Click the 'SQL' tab at the top\n";
        echo "4. Copy the SQL commands above (or from grant-permissions.sql)\n";
        echo "5. Paste and click 'Go'\n\n";
        echo "Failed databases: " . implode(', ', $errors) . "\n\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=================================================================\n";
echo "   Script Complete\n";
echo "=================================================================\n";
