<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages MySQL database provisioning via DirectAdmin API.
 * Used because shared hosting users cannot run CREATE DATABASE directly.
 *
 * DA's assignuser action is not supported in this version, so each DB
 * gets its own unique MySQL user created at provisioning time. The
 * credentials are stored on the school record and used for tenant connections.
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
     * Create a MySQL database via DirectAdmin API.
     * Returns ['db_user' => 'olamtecc_xxx', 'db_password' => '...'] on success, false on failure.
     * DA auto-prefixes both DB name and username with the account prefix.
     */
    public function createDatabase(string $nameWithoutPrefix): array|false
    {
        try {
            $dbPassword = config('database.connections.central.password', '');
            // Generate a unique short username (DA has ~16 char limit including prefix)
            $uniqueUser = substr(preg_replace('/[^a-z0-9]/', '', $nameWithoutPrefix), 0, 6)
                        . substr(md5($nameWithoutPrefix), 0, 5);

            $response = $this->post('/CMD_API_DATABASES', [
                'action'  => 'create',
                'name'    => $nameWithoutPrefix,
                'user'    => $uniqueUser,
                'passwd'  => $dbPassword,
                'passwd2' => $dbPassword,
            ]);

            // If the user already exists (stale from a prior failed/rolled-back attempt),
            // retry with a randomised 2-char suffix so a clean provisioning run gets through.
            if (!$this->isSuccess($response) && str_contains($response->body(), 'That+user+already+exists')) {
                Log::warning("DirectAdmin: user '{$uniqueUser}' already exists, retrying with randomised suffix");
                $uniqueUser = substr($uniqueUser, 0, 9) . substr(md5(uniqid()), 0, 2);
                $response = $this->post('/CMD_API_DATABASES', [
                    'action'  => 'create',
                    'name'    => $nameWithoutPrefix,
                    'user'    => $uniqueUser,
                    'passwd'  => $dbPassword,
                    'passwd2' => $dbPassword,
                ]);
            }

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
                ->withOptions(['verify' => false, 'allow_redirects' => false, 'force_ip_resolve' => 'v4'])
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
        // force_ip_resolve=v4 is the real fix (confirmed 2026-07-31): Guzzle/
        // curl on this server resolves vda6000.is.cc to a link-local IPv6
        // address and connects over that by default, which DirectAdmin's
        // login-key IP allowlist (174.138.190.170, IPv4 only) then rejects as
        // an unauthorized source - reported confusingly as "Not logged in"
        // rather than an IP-restriction error. Manual curl testing never
        // caught this because plain curl on this box happened to prefer IPv4.
        // allow_redirects is also disabled defensively (Guzzle drops the
        // Authorization header on redirect) though it wasn't the root cause.
        return Http::withBasicAuth($this->user, $this->password)
            ->withOptions(['verify' => false, 'allow_redirects' => false, 'force_ip_resolve' => 'v4'])
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
