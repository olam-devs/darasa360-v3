<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages MySQL database provisioning via DirectAdmin API.
 * Used because shared hosting users cannot run CREATE DATABASE directly.
 */
class DirectAdminService
{
    protected string $host;
    protected int    $port;
    protected string $user;
    protected string $password;
    protected string $dbPrefix;
    protected string $dbUser;

    public function __construct()
    {
        $this->host     = config('directadmin.host', 'localhost');
        $this->port     = (int) config('directadmin.port', 2222);
        $this->user     = config('directadmin.user', '');
        $this->password = config('directadmin.password', '');
        $this->dbPrefix = config('directadmin.db_prefix', '');
        $this->dbUser   = config('directadmin.db_user', '');
    }

    /**
     * Create a MySQL database via DirectAdmin API and grant access to the app DB user.
     * DirectAdmin automatically prefixes the DB name with the account username.
     * Pass the name WITHOUT the prefix (e.g. "olam_secondary") — returns full name.
     */
    public function createDatabase(string $nameWithoutPrefix): bool
    {
        try {
            $response = $this->post('/CMD_API_DATABASES', [
                'action'  => 'create',
                'name'    => $nameWithoutPrefix,
            ]);

            if (!$this->isSuccess($response)) {
                Log::error("DirectAdmin createDatabase failed: " . $response->body());
                return false;
            }

            // Grant the app DB user access to the new database
            $fullName = $this->dbPrefix . $nameWithoutPrefix;
            return $this->grantUserAccess($fullName);

        } catch (\Exception $e) {
            Log::error("DirectAdmin createDatabase exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop a MySQL database via DirectAdmin API.
     * Pass the full name including prefix (e.g. "olamtecc_olam_secondary").
     */
    public function dropDatabase(string $fullName): bool
    {
        $nameWithoutPrefix = str_starts_with($fullName, $this->dbPrefix)
            ? substr($fullName, strlen($this->dbPrefix))
            : $fullName;

        try {
            $response = $this->post('/CMD_API_DATABASES', [
                'action'  => 'delete',
                'name'    => $nameWithoutPrefix,
            ]);

            if (!$this->isSuccess($response)) {
                Log::warning("DirectAdmin dropDatabase failed (may already be gone): " . $response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error("DirectAdmin dropDatabase exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Grant the configured app DB user ALL PRIVILEGES on a database.
     */
    protected function grantUserAccess(string $fullDbName): bool
    {
        try {
            $response = $this->post('/CMD_API_DATABASES', [
                'action'   => 'assignuser',
                'name'     => $fullDbName,
                'dbuser'   => $this->dbUser,
                'passwd'   => config('database.connections.central.password', ''),
            ]);

            if (!$this->isSuccess($response)) {
                Log::warning("DirectAdmin grantUserAccess failed: " . $response->body());
                // Not fatal — the DB was created; admin can grant manually
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("DirectAdmin grantUserAccess exception: " . $e->getMessage());
            return true; // DB created, grant can be done manually
        }
    }

    protected function post(string $endpoint, array $data): \Illuminate\Http\Client\Response
    {
        return Http::withBasicAuth($this->user, $this->password)
            ->withOptions(['verify' => false]) // self-signed certs on shared hosting
            ->timeout(30)
            ->asForm()
            ->post("https://{$this->host}:{$this->port}{$endpoint}", $data);
    }

    protected function isSuccess(\Illuminate\Http\Client\Response $response): bool
    {
        $body = $response->body();
        // DirectAdmin returns "error=0" on success
        return str_contains($body, 'error=0') || str_contains($body, 'result=success');
    }
}
