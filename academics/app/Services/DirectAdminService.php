<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages MySQL database provisioning via DirectAdmin API.
 * Used because shared hosting users cannot run CREATE DATABASE / CREATE USER
 * / GRANT directly (see DatabaseHelper's now-removed raw-SQL attempts —
 * those silently failed, which is what this service replaces).
 *
 * Mirrors the Finance app's already-working App\Services\DirectAdminService.
 * DA's assignuser action is not available on this server, so each school's
 * database gets its own dedicated MySQL user created at provisioning time.
 * Credentials are stored on the School record (db_username/db_password)
 * and used for that school's tenant connection specifically.
 */
class DirectAdminService
{
    protected string $host;
    protected int    $port;
    protected string $user;
    protected string $password;
    protected string $dbPrefix;

    public function __construct()
    {
        $this->host     = config('directadmin.host', 'localhost');
        $this->port     = (int) config('directadmin.port', 2222);
        $this->user     = config('directadmin.user', '');
        $this->password = config('directadmin.password', '');
        $this->dbPrefix = config('directadmin.db_prefix', '');
    }

    /**
     * Create a MySQL database + dedicated user via DirectAdmin API.
     * Returns ['db_user' => 'olamtecc_xxx', 'db_password' => '...'] on success, false on failure.
     * DA auto-prefixes both DB name and username with the account prefix.
     */
    public function createDatabase(string $nameWithoutPrefix): array|false
    {
        try {
            $dbPassword = config('database.connections.mysql.password', '');
            // Generate a unique short username (DA has a combined length limit including prefix)
            $uniqueUser = substr(preg_replace('/[^a-z0-9]/', '', strtolower($nameWithoutPrefix)), 0, 6)
                        . substr(md5($nameWithoutPrefix . microtime()), 0, 5);

            $response = $this->post('/CMD_API_DATABASES', [
                'action'  => 'create',
                'name'    => $nameWithoutPrefix,
                'user'    => $uniqueUser,
                'passwd'  => $dbPassword,
                'passwd2' => $dbPassword,
            ]);

            if (!$this->isSuccess($response)) {
                Log::error("DirectAdmin createDatabase failed: " . $response->body());
                return false;
            }

            return [
                'db_user'     => $this->dbPrefix . $uniqueUser,
                'db_password' => $dbPassword,
            ];

        } catch (\Exception $e) {
            Log::error("DirectAdmin createDatabase exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop a MySQL database via DirectAdmin's REST API.
     *
     * The classic CMD_API_DATABASES 'delete' action returns error=0 (success)
     * without actually deleting anything (verified directly against this
     * server on 2026-07-26) - the newer /api/db-manage REST endpoint actually
     * works, so use that instead despite createDatabase() above still using
     * the classic API (create does work there, just not delete).
     */
    public function dropDatabase(string $fullName): bool
    {
        try {
            $response = Http::withBasicAuth($this->user, $this->password)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->delete("https://{$this->host}:{$this->port}/api/db-manage/databases/{$fullName}");

            if (!$response->successful()) {
                Log::warning("DirectAdmin dropDatabase failed (may already be gone): " . $response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error("DirectAdmin dropDatabase exception: " . $e->getMessage());
            return false;
        }
    }

    protected function post(string $endpoint, array $data): \Illuminate\Http\Client\Response
    {
        return Http::withBasicAuth($this->user, $this->password)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->asForm()
            ->post("https://{$this->host}:{$this->port}{$endpoint}", $data);
    }

    protected function isSuccess(\Illuminate\Http\Client\Response $response): bool
    {
        $body = $response->body();
        return str_contains($body, 'error=0') || str_contains($body, 'result=success');
    }
}
