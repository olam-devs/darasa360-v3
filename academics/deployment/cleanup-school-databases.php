<?php

/**
 * Cleanup Script for School Databases and Users
 *
 * This script removes all existing schools, their databases, and MySQL users
 * to prepare for the new multi-tenancy database credential system.
 *
 * WARNING: This is a destructive operation. All school data will be lost.
 * Run this only when starting fresh with the new system.
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "School Database Cleanup Script\n";
echo "========================================\n\n";

echo "WARNING: This will delete ALL schools and their databases!\n";
echo "Type 'YES' to continue: ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'YES') {
    echo "Cleanup cancelled.\n";
    exit(0);
}

echo "\nStarting cleanup process...\n\n";

try {
    // Get all schools
    $schools = DB::table('schools')->get();
    $schoolCount = $schools->count();

    echo "Found {$schoolCount} school(s) to clean up.\n\n";

    $droppedDatabases = 0;
    $droppedUsers = 0;
    $errors = [];

    foreach ($schools as $school) {
        echo "Processing school: {$school->name}\n";

        // Extract database name and username from database_url
        if (!empty($school->database_url)) {
            $parsed = parse_url($school->database_url);

            // Extract database name
            $dbName = isset($parsed['path']) ? ltrim($parsed['path'], '/') : null;
            $dbUser = isset($parsed['user']) ? $parsed['user'] : null;

            // Drop database
            if ($dbName) {
                try {
                    DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                    echo "  ✓ Dropped database: {$dbName}\n";
                    $droppedDatabases++;
                } catch (\Exception $e) {
                    $error = "  ✗ Failed to drop database {$dbName}: " . $e->getMessage();
                    echo $error . "\n";
                    $errors[] = $error;
                }
            }

            // Drop MySQL user
            if ($dbUser && $dbUser !== 'root' && $dbUser !== 'username') {
                try {
                    // Drop user for localhost
                    DB::statement("DROP USER IF EXISTS '{$dbUser}'@'localhost'");
                    echo "  ✓ Dropped user: {$dbUser}@localhost\n";
                    $droppedUsers++;
                } catch (\Exception $e) {
                    $error = "  ✗ Failed to drop user {$dbUser}: " . $e->getMessage();
                    echo $error . "\n";
                    $errors[] = $error;
                }
            }
        }

        echo "\n";
    }

    // Truncate schools table
    echo "Cleaning up schools table...\n";
    DB::table('schools')->truncate();
    echo "✓ Schools table truncated\n\n";

    // Flush privileges
    DB::statement('FLUSH PRIVILEGES');
    echo "✓ Privileges flushed\n\n";

    // Summary
    echo "========================================\n";
    echo "Cleanup Summary\n";
    echo "========================================\n";
    echo "Schools processed: {$schoolCount}\n";
    echo "Databases dropped: {$droppedDatabases}\n";
    echo "Users dropped: {$droppedUsers}\n";
    echo "Errors: " . count($errors) . "\n";

    if (count($errors) > 0) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo $error . "\n";
        }
    }

    echo "\n✓ Cleanup completed successfully!\n";
    echo "You can now create schools with proper database credentials.\n";

} catch (\Exception $e) {
    echo "\n✗ Fatal error during cleanup: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
