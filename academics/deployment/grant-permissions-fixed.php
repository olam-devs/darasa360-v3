<?php
/**
 * Grant School Database Permissions via PHP - FIXED VERSION
 * This script grants permissions to all school databases
 * Works with any column names in schools table
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

// First, let's check what columns exist in the schools table
echo "Checking schools table structure...\n";

try {
    $columns = DB::select("SHOW COLUMNS FROM schools");
    $columnNames = array_map(fn($col) => $col->Field, $columns);

    echo "Columns in schools table: " . implode(', ', $columnNames) . "\n\n";

    // Determine the correct column names
    $nameColumn = in_array('school_name', $columnNames) ? 'school_name' :
                  (in_array('name', $columnNames) ? 'name' :
                  (in_array('school', $columnNames) ? 'school' : null));

    $codeColumn = in_array('school_code', $columnNames) ? 'school_code' :
                  (in_array('code', $columnNames) ? 'code' : null);

    $dbColumn = in_array('database_name', $columnNames) ? 'database_name' :
                (in_array('db_name', $columnNames) ? 'db_name' :
                (in_array('database', $columnNames) ? 'database' : null));

    if (!$dbColumn) {
        echo "ERROR: Could not find database name column in schools table!\n";
        echo "Available columns: " . implode(', ', $columnNames) . "\n\n";

        echo "Let's try to get all school records to see what data exists:\n\n";
        $allSchools = DB::table('schools')->get();
        foreach ($allSchools as $school) {
            echo "School record:\n";
            foreach ($school as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
            echo "\n";
        }
        exit(1);
    }

    echo "Using columns:\n";
    echo "  Name column: " . ($nameColumn ?: 'N/A') . "\n";
    echo "  Code column: " . ($codeColumn ?: 'N/A') . "\n";
    echo "  Database column: {$dbColumn}\n\n";

} catch (Exception $e) {
    echo "ERROR checking table structure: " . $e->getMessage() . "\n";
    exit(1);
}

// Get all schools
try {
    // Build query dynamically based on available columns
    $selectColumns = ['id', $dbColumn];
    if ($nameColumn) $selectColumns[] = $nameColumn;
    if ($codeColumn) $selectColumns[] = $codeColumn;

    $schools = DB::table('schools')->select($selectColumns)->get();

    if ($schools->isEmpty()) {
        echo "No schools found in database.\n";
        echo "Create a school from the admin dashboard first.\n";
        exit(0);
    }

    echo "Schools found:\n";
    foreach ($schools as $school) {
        $schoolName = $nameColumn ? $school->{$nameColumn} : 'Unknown';
        $schoolCode = $codeColumn ? $school->{$codeColumn} : 'N/A';
        $schoolDb = $school->{$dbColumn};

        echo "  {$school->id}. {$schoolName} (Code: {$schoolCode}, Database: {$schoolDb})\n";
    }
    echo "\n";

    echo "=================================================================\n";
    echo "   SQL Commands to Run in phpMyAdmin\n";
    echo "=================================================================\n\n";

    echo "INSTRUCTIONS:\n";
    echo "1. Login to phpMyAdmin\n";
    echo "2. Click on database '{$dbMain}' (or any database)\n";
    echo "3. Click the 'SQL' tab at the top\n";
    echo "4. Copy ALL the commands below\n";
    echo "5. Paste into the SQL text area\n";
    echo "6. Click 'Go' button\n\n";

    echo "-- ============================================================\n";
    echo "-- Grant permissions for DARASA 360 school databases\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- ============================================================\n\n";

    $sqlCommands = [];

    foreach ($schools as $school) {
        $schoolName = $nameColumn ? $school->{$nameColumn} : 'School';
        $schoolDb = $school->{$dbColumn};

        echo "-- {$schoolName}\n";
        echo "CREATE DATABASE IF NOT EXISTS `{$schoolDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        echo "GRANT ALL PRIVILEGES ON `{$schoolDb}`.* TO '{$dbUser}'@'{$dbHost}';\n";
        echo "GRANT ALL PRIVILEGES ON `{$schoolDb}`.* TO '{$dbUser}'@'localhost';\n";
        echo "\n";

        $sqlCommands[] = [
            'name' => $schoolName,
            'db' => $schoolDb
        ];
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

    foreach ($sqlCommands as $cmd) {
        $sqlContent .= "-- {$cmd['name']}\n";
        $sqlContent .= "CREATE DATABASE IF NOT EXISTS `{$cmd['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $sqlContent .= "GRANT ALL PRIVILEGES ON `{$cmd['db']}`.* TO '{$dbUser}'@'{$dbHost}';\n";
        $sqlContent .= "GRANT ALL PRIVILEGES ON `{$cmd['db']}`.* TO '{$dbUser}'@'localhost';\n\n";
    }

    $sqlContent .= "-- Wildcard permissions for future schools\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `olam_school_%`.* TO '{$dbUser}'@'localhost';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'{$dbHost}';\n";
    $sqlContent .= "GRANT ALL PRIVILEGES ON `{$userPrefix}_olam_school_%`.* TO '{$dbUser}'@'localhost';\n\n";
    $sqlContent .= "FLUSH PRIVILEGES;\n";

    file_put_contents($sqlFile, $sqlContent);

    echo "✓ SQL commands saved to: grant-permissions.sql\n\n";

    echo "=================================================================\n";
    echo "   Alternative: Try Automatic Grant via PHP\n";
    echo "=================================================================\n\n";

    echo "Attempting to grant permissions automatically...\n\n";

    $success = true;
    $errors = [];

    foreach ($schools as $school) {
        $schoolName = $nameColumn ? $school->{$nameColumn} : 'School';
        $schoolDb = $school->{$dbColumn};

        echo "Processing: {$schoolName} ({$schoolDb})\n";

        // Try to create database
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$schoolDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✓ Database verified/created\n";
        } catch (Exception $e) {
            echo "  ! Database: " . $e->getMessage() . "\n";
        }

        // Try to grant permissions
        try {
            DB::statement("GRANT ALL PRIVILEGES ON `{$schoolDb}`.* TO '{$dbUser}'@'{$dbHost}'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$schoolDb}`.* TO '{$dbUser}'@'localhost'");
            echo "  ✓ Permissions granted!\n";
        } catch (Exception $e) {
            echo "  ✗ Permission denied: " . $e->getMessage() . "\n";
            echo "  → Use phpMyAdmin method instead\n";
            $success = false;
            $errors[] = $schoolDb;
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
        echo "✓ Wildcard permissions granted!\n\n";
    } catch (Exception $e) {
        echo "✗ Wildcard failed: " . $e->getMessage() . "\n";
        echo "→ Use phpMyAdmin method instead\n\n";
        $success = false;
    }

    if ($success) {
        echo "=================================================================\n";
        echo "   ✓✓✓ SUCCESS! Permissions Granted Automatically! ✓✓✓\n";
        echo "=================================================================\n\n";
        echo "All school databases are now accessible!\n\n";
        echo "Next steps:\n";
        echo "  1. Clear caches:\n";
        echo "     php artisan config:clear\n";
        echo "     php artisan cache:clear\n\n";
        echo "  2. Test: bash test-permissions.sh\n\n";
        echo "  3. Try:\n";
        echo "     - View school details\n";
        echo "     - Login as school owner\n";
        echo "     - All school features should work!\n\n";
    } else {
        echo "=================================================================\n";
        echo "   Use phpMyAdmin Method\n";
        echo "=================================================================\n\n";
        echo "Your database user doesn't have GRANT privileges.\n\n";
        echo "COPY the SQL commands shown above and:\n\n";
        echo "1. Login to phpMyAdmin\n";
        echo "2. Click on database '{$dbMain}'\n";
        echo "3. Click 'SQL' tab\n";
        echo "4. Paste the SQL commands\n";
        echo "5. Click 'Go'\n\n";

        if (!empty($errors)) {
            echo "Databases that need permissions:\n";
            foreach ($errors as $db) {
                echo "  - {$db}\n";
            }
            echo "\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=================================================================\n";
echo "   Script Complete\n";
echo "=================================================================\n";
