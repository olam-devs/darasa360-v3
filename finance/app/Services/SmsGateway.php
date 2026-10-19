<?php

namespace App\Services;

use App\Models\Central\School;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS for a school. A school with its own active gateway (school_sms_gateways, e.g. Onfon) sends
 * through its own account and sender ID and pays from its own wallet; every other school uses the shared
 * Darasa gateway (NextSMS, SMS_API_TOKEN) and Darasa SMS credits.
 *
 * SMS_FAKE_SEND=true (test copies only) records messages as sent without contacting any gateway.
 */
class SmsGateway
{
    public const ONFON_URL = 'https://api.onfonmedia.co.ke/v1/sms';

    /** Active own gateway of a school, or null when it uses the shared Darasa gateway. */
    public function gatewayFor(?School $school): ?object
    {
        if (!$school) return null;
        $g = DB::connection('central')->table('school_sms_gateways')->where('school_id', $school->id)->where('is_active', true)->first();
        if (!$g) return null;
        try { $g->api_key = Crypt::decryptString($g->api_key); } catch (\Throwable $e) { Log::warning("SMS gateway key for school {$school->id} cannot be decrypted"); return null; }
        return $g;
    }

    public function usesOwnGateway(?School $school): bool
    {
        return $this->gatewayFor($school) !== null;
    }

    /** One message to one number. */
    public function send(?School $school, string $to, string $text): SmsSendResult
    {
        $gateway = $this->gatewayFor($school);
        if ($gateway) {
            return $this->sendOnfon($gateway, [['to' => $to, 'text' => $text]])[0];
        }
        return $this->sendShared($to, $text);
    }

    /**
     * Many messages through an own gateway in batches; returns one result per message, in order.
     * $messages = [['to' => ..., 'text' => ...], ...]
     */
    public function sendMany(object $gateway, array $messages, int $batchSize = 200): array
    {
        $results = [];
        foreach (array_chunk($messages, $batchSize) as $chunk) {
            array_push($results, ...$this->sendOnfon($gateway, $chunk));
        }
        return $results;
    }

    /** Onfon wallet balance: ['ok' => bool, 'credits' => string|null, 'error' => string|null]. */
    public function onfonBalance(string $clientId, string $apiKey): array
    {
        if (config('services.sms.fake_send')) return ['ok' => true, 'credits' => '99999', 'error' => null];
        try {
            $r = Http::timeout(20)->withHeaders(['Content-Type' => 'application/json', 'AccessKey' => $apiKey])
                ->get(self::ONFON_URL.'/Balance', ['ApiKey' => $apiKey, 'ClientId' => $clientId]);
            $j = $r->json() ?? [];
            if ((int)($j['ErrorCode'] ?? -1) === 0) return ['ok' => true, 'credits' => $j['Data'][0]['Credits'] ?? null, 'error' => null];
            return ['ok' => false, 'credits' => null, 'error' => self::onfonError($j['ErrorCode'] ?? null, $j['ErrorDescription'] ?? null)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'credits' => null, 'error' => 'Could not reach Onfon: '.$e->getMessage()];
        }
    }

    /** Tanzanian numbers in international format without "+": 0752434818 / +255 752 434 818 / 752434818 -> 255752434818. */
    public static function normalizePhone(?string $phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return null;
        if (str_starts_with($d, '00')) $d = substr($d, 2);
        if (strlen($d) === 10 && $d[0] === '0') $d = '255'.substr($d, 1);
        if (strlen($d) === 9 && in_array($d[0], ['6', '7'], true)) $d = '255'.$d;
        return (strlen($d) >= 11 && strlen($d) <= 15) ? $d : null;
    }

    /** Number of SMS parts a message uses (160 / 153 characters, or 70 / 67 with non-GSM characters). */
    public static function parts(string $message): int
    {
        $unicode = preg_match('/[^\x00-\x7F]/', $message);
        $len = mb_strlen($message);
        $single = $unicode ? 70 : 160; $multi = $unicode ? 67 : 153;
        return $len <= $single ? 1 : (int)ceil($len / $multi);
    }

    private function sendOnfon(object $gateway, array $messages): array
    {
        $params = []; $index = [];
        foreach ($messages as $i => $m) {
            $num = self::normalizePhone($m['to']);
            if (!$num) { $index[$i] = SmsSendResult::failed('Invalid phone number', true); continue; }
            $params[] = ['Number' => $num, 'Text' => $m['text']];
            $index[$i] = $num;
        }
        if (!$params) return array_values($index);
        if (config('services.sms.fake_send')) {
            return array_values(array_map(fn ($v) => $v instanceof SmsSendResult ? $v : SmsSendResult::sent('fake-'.bin2hex(random_bytes(8)), 'Test copy: not really sent', [], true), $index));
        }
        $unicode = collect($params)->contains(fn ($p) => preg_match('/[^\x00-\x7F]/', $p['Text']));
        try {
            $r = Http::timeout(60)->withHeaders(['Content-Type' => 'application/json', 'AccessKey' => $gateway->api_key])
                ->post(self::ONFON_URL.'/SendBulkSMS', [
                    'SenderId' => $gateway->sender_id, 'IsUnicode' => (bool)$unicode, 'IsFlash' => false,
                    'MessageParameters' => $params, 'ApiKey' => $gateway->api_key, 'ClientId' => $gateway->client_id,
                ]);
            $j = $r->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('Onfon send failed: '.$e->getMessage());
            return array_values(array_map(fn ($v) => $v instanceof SmsSendResult ? $v : SmsSendResult::failed('Could not reach the SMS gateway', true, 0), $index));
        }
        if ((int)($j['ErrorCode'] ?? -1) !== 0) {
            $why = self::onfonError($j['ErrorCode'] ?? null, $j['ErrorDescription'] ?? null);
            return array_values(array_map(fn ($v) => $v instanceof SmsSendResult ? $v : SmsSendResult::failed($why, true, $r->status(), $j), $index));
        }
        // Match returned message IDs to numbers (a number may repeat; take them in order).
        $ids = [];
        foreach ($j['Data'] ?? [] as $row) $ids[(string)($row['MobileNumber'] ?? '')][] = $row['MessageId'] ?? null;
        return array_values(array_map(function ($v) use (&$ids, $j) {
            if ($v instanceof SmsSendResult) return $v;
            $id = isset($ids[$v]) ? array_shift($ids[$v]) : null;
            return SmsSendResult::sent($id, 'Submitted', $j, true);
        }, $index));
    }

    private function sendShared(string $to, string $text): SmsSendResult
    {
        if (config('services.sms.fake_send')) return SmsSendResult::sent('fake-'.bin2hex(random_bytes(8)), 'Test copy: not really sent', [], false);
        $r = Http::withHeaders(['Authorization' => 'Bearer '.env('SMS_API_TOKEN'), 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->post('https://messaging-service.co.tz/api/sms/v2/text/single', ['from' => env('SMS_SENDER_NAME', 'DARASA 360'), 'to' => $to, 'text' => $text]);
        $j = $r->json() ?? [];
        $id = $j['messages'][0]['messageId'] ?? null;
        $desc = $j['messages'][0]['status']['description'] ?? 'Unknown';
        return $r->successful() ? SmsSendResult::sent($id, $desc, $j, false, $r->status()) : SmsSendResult::failed($desc, false, $r->status(), $j);
    }

    public static function onfonError($code, $description = null): string
    {
        $map = ['003' => 'Sender ID cannot be blank', '004' => 'Message cannot be blank', '007' => 'Invalid API key or client ID', '008' => 'Onfon account inactive',
            '009' => 'Onfon account locked', '010' => 'API access not allowed for this account', '011' => 'This server\'s IP address is not allowed by Onfon',
            '013' => 'Invalid mobile number(s)', '015' => 'Invalid sender ID (not approved for this account)', '021' => 'Not enough SMS credit in the Onfon wallet',
            '030' => 'Onfon account expired', '035' => 'Not enough SMS credit in the Onfon wallet', '039' => 'Onfon flagged the message as spam', '042' => 'Too many numbers in one request'];
        $key = str_pad((string)$code, 3, '0', STR_PAD_LEFT);
        return $map[$key] ?? trim('Onfon error '.$key.($description && $description !== 'null' ? ': '.$description : ''));
    }
}
