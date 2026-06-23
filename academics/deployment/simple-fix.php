<?php
/**
 * SIMPLE FIX - Use ONE database for everything
 * No separate school databases needed!
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=================================================================\n";
echo "   SIMPLE FIX - Use Single Database\n";
echo "=================================================================\n\n";

$dbHost = config('database.connections.mysql.host');
$dbUser = config('database.connections.mysql.username');
$dbMain = config('database.connections.mysql.database');

echo "Main database: {$dbMain}\n";
echo "This database already works and you have access to it!\n\n";

echo "Updating all schools to use main database...\n\n";

try {
    // Get all schools
    $schools = DB::table('schools')->get();

    if ($schools->isEmpty()) {
        echo "No schools found.\n";
        exit(0);
    }

    foreach ($schools as $school) {
        $newUrl = "mysql://{$dbUser}:password@{$dbHost}/{$dbMain}";

        DB::table('schools')
            ->where('id', $school->id)
            ->update(['database_url' => $newUrl]);

        echo "✓ Updated school: {$school->name}\n";
        echo "  Old URL: {$school->database_url}\n";
        echo "  New URL: {$newUrl}\n\n";
    }

    echo "=================================================================\n";
    echo "   SUCCESS! All schools now use main database\n";
    echo "=================================================================\n\n";

    echo "All schools will share the main database: {$dbMain}\n";
    echo "No separate databases needed!\n\n";

    echo "Next steps:\n";
    echo "1. Clear caches:\n";
    echo "   php artisan config:clear\n";
    echo "   php artisan cache:clear\n\n";

    echo "2. Test:\n";
    echo "   - View school details ✓\n";
    echo "   - Login as school owner ✓\n";
    echo "   - Everything should work now!\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=================================================================\n";
