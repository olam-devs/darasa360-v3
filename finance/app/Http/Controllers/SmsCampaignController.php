<?php

namespace App\Http\Controllers;

use App\Models\Central\ActivityLog;
use App\Services\SmsGateway;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SMS in the management portal: each school's own SMS account (setup), contact lists such as potential
 * customers, and campaigns to a contact list, to parents of the schools, or to pasted numbers.
 * Campaigns send through the chosen school's own account and sender ID (never Darasa SMS credits).
 */
class SmsCampaignController extends SchoolManagementController
{
    private function central() { return DB::connection('central'); }

    private function managerId(Request $request): int
    {
        return ($this->access->manager($request->user()) ?? abort(403))->id;
    }

    private function common(Request $request, string $active): array
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $selected = $this->selectedSchool($request);
        return ['selected' => $selected, 'selectedSchool' => $selected, 'nav' => $this->nav($request, $selected?->id), 'activeSection' => $active];
    }

    /** The manager's schools with their SMS account (key never sent to the page). */
    private function schoolGateways(Request $request)
    {
        $schools = $this->access->schools($request->user());
        $gateways = $this->central()->table('school_sms_gateways')->whereIn('school_id', $schools->pluck('id'))->get()->keyBy('school_id');
        return $schools->map(fn ($s) => (object)['id' => $s->id, 'name' => $s->name, 'short' => self::shortName($s->name), 'gateway' => $gateways[$s->id] ?? null]);
    }

    // ---------------------------------------------------------------- setup

    public function setup(Request $request)
    {
        $data = $this->common($request, 'sms-setup');
        $schools = $this->schoolGateways($request);
        return view('admin.accountant.management.sms-setup', $data + compact('schools'));
    }

    /** Save one SMS account for the ticked schools. An empty API key keeps each school's saved key. */
    public function saveSetup(Request $request)
    {
        $v = $request->validate([
            'schools' => 'required|array|min:1', 'schools.*' => 'integer',
            'client_id' => 'required|string|max:120', 'api_key' => 'nullable|string|max:255',
            'sender_id' => 'required|string|max:20|regex:/^[A-Za-z0-9 .\-]+$/', 'is_active' => 'nullable|boolean',
        ], ['schools.required' => 'Tick at least one school.', 'sender_id.max' => 'A sender ID is at most 20 characters.', 'sender_id.regex' => 'A sender ID uses letters, numbers, spaces, dots or dashes only.']);
        $managerId = $this->managerId($request);
        $names = [];
        foreach ($v['schools'] as $schoolId) {
            $school = $this->access->school($request->user(), (int)$schoolId);
            $existing = $this->central()->table('school_sms_gateways')->where('school_id', $school->id)->first();
            if (!$existing && empty($v['api_key'])) return back()->withInput()->with('error', self::shortName($school->name).' has no saved API key yet: enter the API key.');
            $row = ['provider' => 'onfon', 'client_id' => trim($v['client_id']), 'sender_id' => trim($v['sender_id']), 'is_active' => $request->boolean('is_active'),
                'updated_by' => $managerId, 'updated_at' => now()];
            if (!empty($v['api_key'])) $row['api_key'] = Crypt::encryptString(trim($v['api_key']));
            $existing ? $this->central()->table('school_sms_gateways')->where('id', $existing->id)->update($row)
                : $this->central()->table('school_sms_gateways')->insert($row + ['school_id' => $school->id, 'created_at' => now()]);
            ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $managerId, 'user_name' => $request->user()->name,
                'action' => 'sms_gateway_updated', 'description' => 'SMS account set to Onfon, client '.trim($v['client_id']).', sender '.trim($v['sender_id']).($request->boolean('is_active') ? ' (on)' : ' (off)')
                    .(!empty($v['api_key']) ? ', new API key' : ''), 'ip_address' => $request->ip()]);
            $names[] = self::shortName($school->name);
        }
        return back()->with('success', 'SMS account saved for '.implode(', ', $names).'.');
    }

    /** Check a school's saved SMS account by asking Onfon for the balance. */
    public function testSetup(Request $request, int $schoolId)
    {
        $school = $this->access->school($request->user(), $schoolId);
        $g = app(SmsGateway::class)->gatewayFor($school);
        if (!$g) return back()->with('error', self::shortName($school->name).' has no active SMS account.');
        $b = app(SmsGateway::class)->onfonBalance($g->client_id, $g->api_key);
        return back()->with($b['ok'] ? 'success' : 'error', self::shortName($school->name).': '.($b['ok'] ? 'connected · balance '.$b['credits'].' SMS' : $b['error']));
    }

    // ---------------------------------------------------------------- campaigns page

    public function index(Request $request)
    {
        $data = $this->common($request, 'sms');
        $managerId = $this->managerId($request);
        $schools = $this->schoolGateways($request);
        $lists = $this->central()->table('sms_contact_lists as l')->where('l.manager_accountant_id', $managerId)
            ->leftJoin('sms_contacts as c', 'c.sms_contact_list_id', '=', 'l.id')
            ->groupBy('l.id', 'l.name', 'l.notes', 'l.created_at')->orderBy('l.name')
            ->get(['l.id', 'l.name', 'l.notes', 'l.created_at', DB::raw('count(c.id) as contacts'), DB::raw('sum(c.opted_out_at is not null) as opted_out')]);
        $campaigns = $this->central()->table('sms_campaigns')->where('manager_accountant_id', $managerId)->orderByDesc('id')->limit(50)->get();
        $classes = [];
        foreach ($schools as $s) {
            $classes[$s->id] = app(TenantDatabaseManager::class)->executeForSchool($this->access->school($request->user(), $s->id),
                fn () => DB::connection('tenant')->table('students')->where('status', 'active')->whereNotNull('class')->distinct()->orderBy('class')->pluck('class')->all());
        }
        $openList = $request->integer('list') ? $this->central()->table('sms_contact_lists')->where('manager_accountant_id', $managerId)->find($request->integer('list')) : null;
        $openContacts = $openList ? $this->central()->table('sms_contacts')->where('sms_contact_list_id', $openList->id)->orderBy('name')->orderBy('phone')->get() : collect();
        return view('admin.accountant.management.sms', $data + compact('schools', 'lists', 'campaigns', 'classes', 'openList', 'openContacts'));
    }

    // ---------------------------------------------------------------- contact lists

    /** Parse pasted lines or a CSV upload into [phone => name]. Lines: "name, phone", "phone, name" or just "phone". */
    private function parseContacts(?string $text, $file): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string)$text);
        if ($file) $lines = array_merge($lines, preg_split('/\r\n|\r|\n/', (string)file_get_contents($file->getRealPath())));
        $out = []; $bad = 0;
        foreach ($lines as $line) {
            $line = trim($line, " \t\"'");
            if ($line === '' || preg_match('/^(name|jina)\b/i', $line)) continue;
            if (!preg_match('/\+?\d[\d \-]{7,}\d/', $line, $m)) { $bad++; continue; }
            $phone = SmsGateway::normalizePhone($m[0]);
            if (!$phone) { $bad++; continue; }
            $name = trim(preg_replace('/\s+/', ' ', str_replace($m[0], '', $line)), " ,;\t\"'");
            $out[$phone] = $name !== '' ? Str::limit($name, 150, '') : ($out[$phone] ?? null);
        }
        return [$out, $bad];
    }

    public function storeList(Request $request)
    {
        $v = $request->validate(['list_id' => 'nullable|integer', 'name' => 'required_without:list_id|nullable|string|max:120', 'notes' => 'nullable|string|max:500',
            'numbers' => 'nullable|string|max:500000', 'file' => 'nullable|file|mimes:csv,txt|max:5120']);
        $managerId = $this->managerId($request);
        [$contacts, $bad] = $this->parseContacts($v['numbers'] ?? null, $request->file('file'));
        if (!$contacts) return back()->withInput()->with('error', 'No valid phone numbers found. Put one number per line (optionally with a name).');
        if (!empty($v['list_id'])) {
            $list = $this->central()->table('sms_contact_lists')->where('manager_accountant_id', $managerId)->find($v['list_id']) ?? abort(404);
            $listId = $list->id;
        } else {
            $listId = $this->central()->table('sms_contact_lists')->insertGetId(['manager_accountant_id' => $managerId, 'name' => $v['name'], 'notes' => $v['notes'] ?? null,
                'created_at' => now(), 'updated_at' => now()]);
        }
        $existing = $this->central()->table('sms_contacts')->where('sms_contact_list_id', $listId)->pluck('phone')->flip();
        $new = 0;
        foreach (array_chunk($contacts, 500, true) as $chunk) {
            $rows = [];
            foreach ($chunk as $phone => $name) {
                if (isset($existing[$phone])) { if ($name) $this->central()->table('sms_contacts')->where('sms_contact_list_id', $listId)->where('phone', $phone)->whereNull('name')->update(['name' => $name]); continue; }
                $rows[] = ['sms_contact_list_id' => $listId, 'name' => $name, 'phone' => $phone, 'created_at' => now(), 'updated_at' => now()]; $new++;
            }
            if ($rows) $this->central()->table('sms_contacts')->insert($rows);
        }
        return redirect()->to(route('accountant.management.sms', array_filter(['school' => $request->input('school'), 'list' => $listId])).'#lists')
            ->with('success', "$new new number(s) added".(count($contacts) - $new ? ', '.(count($contacts) - $new).' already in the list' : '').($bad ? ", $bad line(s) skipped (not a valid number)" : '').'.');
    }

    /** Opt a contact out (no more campaigns) or back in. */
    public function toggleContact(Request $request, int $contactId)
    {
        $c = $this->central()->table('sms_contacts as c')->join('sms_contact_lists as l', 'l.id', '=', 'c.sms_contact_list_id')
            ->where('l.manager_accountant_id', $this->managerId($request))->where('c.id', $contactId)->first(['c.*']) ?? abort(404);
        $this->central()->table('sms_contacts')->where('id', $c->id)->update(['opted_out_at' => $c->opted_out_at ? null : now(), 'updated_at' => now()]);
        return back()->with('success', $c->phone.($c->opted_out_at ? ' will receive campaigns again.' : ' will not receive campaigns any more.'));
    }

    // ---------------------------------------------------------------- campaign: preview and send

    /** Recipients for the chosen audience: [phone => name]. */
    private function recipients(Request $request, array $v): array
    {
        $out = []; $desc = [];
        $managerId = $this->managerId($request);
        foreach ($v['lists'] ?? [] as $listId) {
            $list = $this->central()->table('sms_contact_lists')->where('manager_accountant_id', $managerId)->find($listId) ?? abort(404);
            $rows = $this->central()->table('sms_contacts')->where('sms_contact_list_id', $list->id)->whereNull('opted_out_at')->get(['phone', 'name']);
            foreach ($rows as $r) $out[$r->phone] ??= $r->name;
            $desc[] = 'List "'.$list->name.'" ('.$rows->count().')';
        }
        foreach ($v['parent_schools'] ?? [] as $schoolId) {
            $school = $this->access->school($request->user(), (int)$schoolId);
            $classes = array_filter($v['classes'][$schoolId] ?? []);
            $rows = app(TenantDatabaseManager::class)->executeForSchool($school, fn () => DB::connection('tenant')->table('students')->where('status', 'active')
                ->when($classes, fn ($q) => $q->whereIn('class', $classes))->get(['parent_name', 'parent_phone_1', 'parent_phone_2']));
            $n = 0;
            foreach ($rows as $r) {
                foreach (array_filter([$r->parent_phone_1, ($v['parent_phone'] ?? 'phone_1') === 'both' ? $r->parent_phone_2 : null]) as $p) {
                    if ($p = SmsGateway::normalizePhone($p)) { $out[$p] ??= $r->parent_name; $n++; }
                }
            }
            $desc[] = 'Parents · '.self::shortName($school->name).($classes ? ' ('.implode(', ', $classes).')' : '')." ($n)";
        }
        if (!empty($v['numbers'])) {
            [$pasted] = $this->parseContacts($v['numbers'], null);
            foreach ($pasted as $p => $name) $out[$p] ??= $name;
            $desc[] = 'Typed numbers ('.count($pasted).')';
        }
        // Never message a number that opted out of any of this manager's lists.
        $optedOut = $this->central()->table('sms_contacts as c')->join('sms_contact_lists as l', 'l.id', '=', 'c.sms_contact_list_id')
            ->where('l.manager_accountant_id', $managerId)->whereNotNull('c.opted_out_at')->pluck('c.phone')->flip();
        $out = array_diff_key($out, $optedOut->all());
        return [$out, implode(' · ', $desc)];
    }

    private function campaignRules(): array
    {
        return ['send_school_id' => 'required|integer', 'title' => 'required|string|max:150', 'message' => 'required|string|max:918',
            'lists' => 'nullable|array', 'lists.*' => 'integer', 'parent_schools' => 'nullable|array', 'parent_schools.*' => 'integer',
            'classes' => 'nullable|array', 'parent_phone' => 'nullable|in:phone_1,both', 'numbers' => 'nullable|string|max:200000'];
    }

    private static function personalize(string $message, ?string $name): string
    {
        $first = trim(explode(' ', trim((string)$name))[0] ?? '');
        return str_replace(['{name}', '{jina}'], [$first !== '' ? Str::title(Str::lower($first)) : 'Mzazi', $first !== '' ? Str::title(Str::lower($first)) : 'Mzazi'], $message);
    }

    public function preview(Request $request)
    {
        $v = $request->validate($this->campaignRules(), ['message.max' => 'Keep the message to at most 6 SMS (918 characters).']);
        $data = $this->common($request, 'sms');
        $school = $this->access->school($request->user(), (int)$v['send_school_id']);
        $gateway = app(SmsGateway::class)->gatewayFor($school);
        if (!$gateway) return back()->withInput()->with('error', self::shortName($school->name).' has no active SMS account. Set it up under SMS setup first.');
        [$recipients, $audience] = $this->recipients($request, $v);
        if (!$recipients) return back()->withInput()->with('error', 'Nobody to send to: choose a contact list, parents, or type numbers.');
        if (count($recipients) > 10000) return back()->withInput()->with('error', 'At most 10,000 numbers per campaign.');
        $parts = max(array_map(fn ($name) => SmsGateway::parts(self::personalize($v['message'], $name)), $recipients));
        $balance = app(SmsGateway::class)->onfonBalance($gateway->client_id, $gateway->api_key);
        $token = (string)Str::uuid();
        session(['sms_campaign_token' => $token]);
        $sample = array_slice($recipients, 0, 15, true);
        $firstName = array_values($recipients)[0] ?? null;
        return view('admin.accountant.management.sms-preview', $data + compact('v', 'school', 'gateway', 'recipients', 'audience', 'parts', 'balance', 'token', 'sample', 'firstName')
            + ['example' => self::personalize($v['message'], $firstName)]);
    }

    public function send(Request $request)
    {
        $v = $request->validate($this->campaignRules() + ['token' => 'required|string']);
        // One send per preview: a double click or a refresh cannot send the campaign twice.
        if (!hash_equals((string)session()->pull('sms_campaign_token'), $v['token'])) {
            return redirect()->route('accountant.management.sms', array_filter(['school' => $request->input('school')]))->with('error', 'This campaign was already sent (or the preview expired). Check the history below before sending again.');
        }
        $managerId = $this->managerId($request);
        $school = $this->access->school($request->user(), (int)$v['send_school_id']);
        $gateway = app(SmsGateway::class)->gatewayFor($school) ?? abort(422, 'No active SMS account.');
        [$recipients, $audience] = $this->recipients($request, $v);
        abort_if(!$recipients, 422);
        set_time_limit(600);
        $parts = max(array_map(fn ($name) => SmsGateway::parts(self::personalize($v['message'], $name)), $recipients));
        $campaignId = $this->central()->table('sms_campaigns')->insertGetId(['manager_accountant_id' => $managerId, 'school_id' => $school->id, 'title' => $v['title'],
            'message' => $v['message'], 'audience' => Str::limit($audience, 500, ''), 'recipients' => count($recipients), 'parts' => $parts, 'status' => 'sending',
            'created_at' => now(), 'updated_at' => now()]);
        $messages = []; $phones = array_keys($recipients);
        foreach ($recipients as $phone => $name) {
            $this->central()->table('sms_campaign_recipients')->insert(['sms_campaign_id' => $campaignId, 'name' => $name, 'phone' => $phone, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            $messages[] = ['to' => (string)$phone, 'text' => self::personalize($v['message'], $name)];
        }
        $results = app(SmsGateway::class)->sendMany($gateway, $messages);
        $sent = 0; $failed = 0;
        foreach ($results as $i => $r) {
            $r->ok ? $sent++ : $failed++;
            $this->central()->table('sms_campaign_recipients')->where('sms_campaign_id', $campaignId)->where('phone', (string)$phones[$i])
                ->update(['status' => $r->ok ? 'sent' : 'failed', 'message_id' => $r->messageId, 'description' => Str::limit($r->description, 250, ''), 'updated_at' => now()]);
        }
        $this->central()->table('sms_campaigns')->where('id', $campaignId)->update(['sent' => $sent, 'failed' => $failed, 'status' => 'done', 'updated_at' => now()]);
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $managerId, 'user_name' => $request->user()->name,
            'action' => 'sms_campaign_sent', 'description' => "SMS campaign #$campaignId \"{$v['title']}\": $sent sent, $failed failed ($audience).", 'ip_address' => $request->ip()]);
        $why = $failed ? collect($results)->where('ok', false)->pluck('description')->countBy()->sortDesc()->keys()->first() : null;
        return redirect()->route('accountant.management.sms.campaign', array_filter(['campaignId' => $campaignId, 'school' => $request->input('school')]))
            ->with($sent ? 'success' : 'error', "Campaign sent: $sent sent".($failed ? ", $failed failed ($why)" : '').'.');
    }

    public function campaign(Request $request, int $campaignId)
    {
        $data = $this->common($request, 'sms');
        $campaign = $this->central()->table('sms_campaigns')->where('manager_accountant_id', $this->managerId($request))->find($campaignId) ?? abort(404);
        $rows = $this->central()->table('sms_campaign_recipients')->where('sms_campaign_id', $campaign->id)->orderBy('id')->get();
        $sender = $this->schoolGateways($request)->firstWhere('id', $campaign->school_id);
        return view('admin.accountant.management.sms-campaign', $data + compact('campaign', 'rows', 'sender'));
    }

    // ---------------------------------------------------------------- Onfon delivery reports (public)

    /**
     * Onfon "Http Web push DLR": GET ?messageId=&status=&mobile=&errorCode=... to
     * /api/sms/onfon-dlr/{token}. Updates a campaign recipient or a school's SMS log.
     */
    public function onfonDelivery(Request $request, string $token)
    {
        $expected = (string)config('services.sms.onfon_dlr_token');
        if ($expected === '' || !hash_equals($expected, $token)) return response('forbidden', 403);
        $messageId = (string)$request->query('messageId');
        if ($messageId === '') return response('ok');
        $raw = strtolower((string)$request->query('status'));
        $state = str_contains($raw, 'undeliv') || str_contains($raw, 'fail') || str_contains($raw, 'reject') || str_contains($raw, 'expir') ? 'failed'
            : (str_contains($raw, 'deliv') ? 'delivered' : null);
        $desc = Str::limit(trim($request->query('status').' '.($request->query('errorCode') ? '(code '.$request->query('errorCode').')' : '')), 250, '');
        $updated = $this->central()->table('sms_campaign_recipients')->where('message_id', $messageId)
            ->update(array_filter(['status' => $state, 'description' => $desc, 'delivered_at' => $state === 'delivered' ? now() : null, 'updated_at' => now()]));
        if (!$updated) {
            $index = \App\Models\Central\SmsDeliveryIndex::where('message_id', $messageId)->first();
            $school = $index ? \App\Models\Central\School::find($index->school_id) : null;
            if ($school) {
                app(TenantDatabaseManager::class)->executeForSchool($school, fn () => DB::connection('tenant')->table('sms_logs')->where('message_id', $messageId)
                    ->update(array_filter(['status' => $state, 'status_description' => $desc, 'delivered_at' => $state === 'delivered' ? now() : null, 'updated_at' => now()])));
            }
        }
        return response('ok');
    }
}
