<!DOCTYPE html>
<html>
<head>
    <title>Menu Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .error { border-left-color: #f44336; }
        .warning { border-left-color: #ff9800; }
        .success { border-left-color: #4CAF50; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .button:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Report Cards Menu Diagnostics</h1>

        <?php
        session_start();

        echo '<div class="section success">';
        echo '<h2>✓ Session Information</h2>';
        echo '<table>';
        echo '<tr><td>Role:</td><td>' . ($_SESSION['role'] ?? 'NOT SET') . '</td></tr>';
        echo '<tr><td>Package:</td><td>' . ($_SESSION['package'] ?? 'NOT SET') . '</td></tr>';
        echo '<tr><td>User ID:</td><td>' . ($_SESSION['user_id'] ?? 'NOT SET') . '</td></tr>';
        echo '<tr><td>Name:</td><td>' . ($_SESSION['name'] ?? 'NOT SET') . '</td></tr>';
        echo '<tr><td>School DB:</td><td>' . ($_SESSION['school_db'] ?? 'NOT SET') . '</td></tr>';
        echo '<tr><td>School Code:</td><td>' . ($_SESSION['school_code'] ?? 'NOT SET') . '</td></tr>';
        echo '</table>';
        echo '</div>';

        // Check if role is Academic
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Academic') {
            echo '<div class="section success">';
            echo '<h2>✓ You are logged in as Academic</h2>';
            echo '<p>The Report Cards menu should be visible to you.</p>';
            echo '</div>';
        } else {
            echo '<div class="section error">';
            echo '<h2>✗ You are NOT logged in as Academic</h2>';
            echo '<p>Current role: ' . ($_SESSION['role'] ?? 'NONE') . '</p>';
            echo '<p>You need to login as Academic user to see the Report Cards menu.</p>';
            echo '</div>';
        }

        // Check menu file
        $menuFile = '../resources/menu/verticalMenuAcademic.json';
        if (file_exists($menuFile)) {
            $menuJson = file_get_contents($menuFile);
            $menuData = json_decode($menuJson, true);

            $found = false;
            foreach ($menuData['menu'] as $item) {
                if (isset($item['name']) && $item['name'] === 'Report Cards') {
                    $found = true;
                    echo '<div class="section success">';
                    echo '<h2>✓ Report Cards Menu Item Found</h2>';
                    echo '<table>';
                    echo '<tr><td>Name:</td><td>' . $item['name'] . '</td></tr>';
                    echo '<tr><td>URL:</td><td>' . $item['url'] . '</td></tr>';
                    echo '<tr><td>Icon:</td><td>' . $item['icon'] . '</td></tr>';
                    echo '<tr><td>Packages:</td><td>' . implode(', ', $item['package']) . '</td></tr>';
                    echo '</table>';
                    break;
                }
            }

            if (!$found) {
                echo '<div class="section error">';
                echo '<h2>✗ Report Cards Menu Item NOT FOUND in JSON</h2>';
                echo '</div>';
            }
        } else {
            echo '<div class="section error">';
            echo '<h2>✗ Menu file not found</h2>';
            echo '</div>';
        }

        // Check if package allows access
        if (isset($_SESSION['package'])) {
            $packageHierarchy = [
                'core' => ['core'],
                'standard' => ['core', 'standard'],
                'premium' => ['core', 'standard', 'premium'],
            ];

            $allowedPackages = $packageHierarchy[strtolower($_SESSION['package'])] ?? [];
            $requiredPackages = ['core', 'standard', 'premium'];

            $hasAccess = !empty(array_intersect($allowedPackages, $requiredPackages));

            if ($hasAccess) {
                echo '<div class="section success">';
                echo '<h2>✓ Your Package Has Access</h2>';
                echo '<p>Your "' . $_SESSION['package'] . '" package can access Report Cards menu.</p>';
                echo '</div>';
            } else {
                echo '<div class="section error">';
                echo '<h2>✗ Your Package Does NOT Have Access</h2>';
                echo '<p>Your "' . $_SESSION['package'] . '" package cannot access Report Cards menu.</p>';
                echo '</div>';
            }
        }
        ?>

        <div class="section warning">
            <h2>⚠️ Action Required</h2>
            <p><strong>If you're seeing this but still don't see Report Cards in your menu:</strong></p>
            <ol>
                <li><strong>Hard Refresh Your Browser:</strong>
                    <ul>
                        <li>Windows/Linux: Press <code>Ctrl + Shift + R</code> or <code>Ctrl + F5</code></li>
                        <li>Mac: Press <code>Cmd + Shift + R</code></li>
                    </ul>
                </li>
                <li><strong>Clear Browser Cache:</strong> Go to browser settings and clear cached data</li>
                <li><strong>Try Incognito Mode:</strong> Open an incognito/private window and login again</li>
                <li><strong>Logout and Login Again:</strong> This refreshes your session</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/academic/report-cards" class="button">Try Accessing Report Cards Directly</a>
            <a href="/" class="button" style="background: #2196F3;">Go to Dashboard</a>
            <a href="/login" class="button" style="background: #f44336;">Login Page</a>
        </div>

        <div class="section">
            <h2>🔗 Direct Links to Test</h2>
            <ul>
                <li><a href="/academic/report-cards">/academic/report-cards</a> - Main page</li>
                <li><a href="/academic/report-cards/create">/academic/report-cards/create</a> - Create new</li>
                <li><a href="/class-teacher/report-cards">/class-teacher/report-cards</a> - Class teacher view</li>
            </ul>
        </div>
    </div>
</body>
</html>
