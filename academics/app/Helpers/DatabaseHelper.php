<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseHelper
{
    /**
     * Build database URL from username and database name
     * Uses environment variables for host, port, and password
     *
     * @param string $username Database username
     * @param string $database Database name
     * @return string Complete database URL
     */
    public static function buildDatabaseUrl(string $username, string $database): string
    {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $dbUser = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');

        return sprintf(
            'mysql://%s:%s@%s:%s/%s',
            $dbUser,
            $password,
            $host,
            $port,
            $database
        );
    }

    /**
     * Parse database URL into components
     *
     * @param string $url Database URL
     * @return array Parsed components
     */
    public static function parseDatabaseUrl(string $url): array
    {
        $parsed = parse_url($url);

        return [
            'driver' => $parsed['scheme'] ?? 'mysql',
            'host' => $parsed['host'] ?? '127.0.0.1',
            'port' => $parsed['port'] ?? 3306,
            'database' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : '',
            'username' => $parsed['user'] ?? '',
            'password' => $parsed['pass'] ?? '',
        ];
    }

    /**
     * Create MySQL user and grant permissions on specific database
     * Adapted for shared hosting - skips database creation if no privileges
     *
     * @param string $username Database username
     * @param string $database Database name
     * @return bool Success status
     * @throws Exception If critical operations fail
     */
    public static function createDatabaseUser(string $username, string $database): bool
    {
        try {
            $password = env('DB_PASSWORD', '');

            // Try to create database if it doesn't exist
            // In shared hosting, this may fail - we'll continue anyway
            try {
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (Exception $e) {
                // Shared hosting: Database creation not allowed
                // Check if database already exists
                if (!self::databaseExists($database)) {
                    throw new Exception("Database '{$database}' does not exist and you don't have permission to create it. Please create it via cPanel first.");
                }
                // Database exists, continue
            }

            // Try to create user and grant permissions
            // In shared hosting, this may also fail - we'll just verify connection
            try {
                // Drop user if exists (cleanup old)
                DB::statement("DROP USER IF EXISTS '{$username}'@'localhost'");

                // Create new user with password
                if (empty($password)) {
                    DB::statement("CREATE USER '{$username}'@'localhost'");
                } else {
                    DB::statement("CREATE USER '{$username}'@'localhost' IDENTIFIED BY '{$password}'");
                }

                // Grant all privileges on the specific database
                DB::statement("GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$username}'@'localhost'");

                // Flush privileges to apply changes
                DB::statement('FLUSH PRIVILEGES');
            } catch (Exception $e) {
                // Shared hosting: User creation/grant not allowed
                // This is OK - the main DB user has access to all databases in shared hosting
                // Just continue
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to create database user: " . $e->getMessage());
        }
    }

    /**
     * Test database connection with given URL
     *
     * @param string $url Database URL
     * @return bool Connection success status
     */
    public static function testConnection(string $url): bool
    {
        try {
            $config = self::parseDatabaseUrl($url);

            // Create temporary connection config
            config([
                'database.connections.test_tenant' => [
                    'driver' => $config['driver'],
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'database' => $config['database'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                ]
            ]);

            // Attempt connection
            DB::connection('test_tenant')->getPdo();

            // Purge test connection
            DB::purge('test_tenant');

            return true;
        } catch (Exception $e) {
            // Purge test connection
            DB::purge('test_tenant');
            return false;
        }
    }

    /**
     * Drop database user (for cleanup)
     *
     * @param string $username Database username
     * @return bool Success status
     */
    public static function dropDatabaseUser(string $username): bool
    {
        try {
            DB::statement("DROP USER IF EXISTS '{$username}'@'localhost'");
            DB::statement('FLUSH PRIVILEGES');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if database exists
     *
     * @param string $database Database name
     * @return bool True if database exists
     */
    public static function databaseExists(string $database): bool
    {
        try {
            $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);
            return count($result) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if MySQL user exists
     *
     * @param string $username Username to check
     * @return bool True if user exists
     */
    public static function userExists(string $username): bool
    {
        try {
            $result = DB::select("SELECT User FROM mysql.user WHERE User = ? AND Host = 'localhost'", [$username]);
            return count($result) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate database name format
     *
     * @param string $database Database name
     * @return bool True if valid
     */
    public static function isValidDatabaseName(string $database): bool
    {
        // MySQL database names can contain alphanumeric characters, underscores, and hyphens
        // Max length is 64 characters
        return preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $database) === 1;
    }

    /**
     * Validate MySQL username format
     *
     * @param string $username Username
     * @return bool True if valid
     */
    public static function isValidUsername(string $username): bool
    {
        // MySQL usernames max length is 32 characters (before MySQL 5.7.8 it was 16)
        // Can contain alphanumeric characters and underscores
        return preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username) === 1;
    }
}
