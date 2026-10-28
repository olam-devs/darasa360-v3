<?php

namespace App\Http\Controllers;

use App\Services\ManagementQuarterAnalytics;
use App\Services\ReceiptCheckService;
use Illuminate\Http\Request;

/**
 * Receipt check and bank ledger in the management portal (all managed schools). The logic is shared with
 * the school accountants' pages in ReceiptCheckService.
 */
class ReceiptCheckController extends SchoolManagementController
{
    private function service(): ReceiptCheckService { return app(ReceiptCheckService::class); }

    private function periodViewData(array $period): array
    {
        return ['quarterLabels' => ManagementQuarterAnalytics::LABELS,
            'yearOptions' => collect([$period['year'], now('Africa/Dar_es_Salaam')->year])->unique()->sortDesc()->values()];
    }

    public function index(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $period = $this->period($request);
        $request->validate(['multi' => 'nullable|in:1', 'q' => 'nullable|string|max:80', 'student' => 'nullable|integer']);
        $onlyMulti = $request->boolean('multi');
        $selected = $this->selectedSchool($request);
        $schools = $selected ? collect([$selected]) : $this->access->schools($request->user());
        // Student search and merge work inside one school.
        $q = $selected ? trim((string)$request->input('q')) : '';
        $studentId = $selected ? $request->integer('student') ?: null : null;
        $proposals = collect(); $confirmed = collect();
        foreach ($schools as $school) {
            $r = $this->service()->forSchool($school, $period['from'], $period['to'], $onlyMulti, $studentId);
            $proposals = $proposals->merge($r['proposals']); $confirmed = $confirmed->merge($r['confirmed']);
        }
        $proposals = $proposals->sortBy([['date', 'asc'], ['student', 'asc']])->values();
        $found = $q !== '' ? $this->service()->searchStudents($selected, $q) : collect();
        $student = $studentId ? $this->service()->student($selected, $studentId) : null;
        return view('admin.accountant.management.receipt-check', $period + $this->periodViewData($period) + compact('proposals', 'confirmed', 'selected', 'onlyMulti', 'q', 'found', 'student')
            + ['nav' => $this->nav($request, $selected?->id), 'selectedSchool' => $selected, 'activeSection' => 'receipts']);
    }

    private function actor(Request $request) { return $this->access->manager($request->user()) ?? abort(403); }

    public function confirm(Request $request)
    {
        $data = $request->validate(['school_id' => 'required|integer', 'voucher_ids' => 'required|array|min:1|max:50', 'voucher_ids.*' => 'integer',
            'receipt_date' => 'required|date_format:Y-m-d', 'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500'],
            ['voucher_ids.required' => 'Tick at least one item to confirm.']);
        $school = $this->access->school($request->user(), (int)$data['school_id']);
        [$ok, $msg] = $this->service()->confirm($school, $data['voucher_ids'], $data['receipt_date'], $data['reference'] ?? null, $data['note'] ?? null, $this->actor($request), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function update(Request $request, int $groupId)
    {
        $data = $request->validate(['school_id' => 'required|integer', 'receipt_date' => 'required|date_format:Y-m-d', 'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500']);
        $school = $this->access->school($request->user(), (int)$data['school_id']);
        [$ok, $msg] = $this->service()->update($school, $groupId, $data['receipt_date'], $data['reference'] ?? null, $data['note'] ?? null, $this->actor($request), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function undo(Request $request, int $groupId)
    {
        $schoolId = (int)$request->validate(['school_id' => 'required|integer'])['school_id'];
        $school = $this->access->school($request->user(), $schoolId);
        [$ok, $msg] = $this->service()->undo($school, $groupId, $this->actor($request), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    /**
     * Bank ledger for one account. An account number shared by several schools always covers all of them,
     * also when one school is selected (the selection only narrows which accounts are offered).
     */
    public function ledger(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $period = $this->period($request);
        $request->validate(['account' => ['nullable', 'regex:/^(account:[0-9A-Za-z]+|book:\d+:\d+)$/'], 'download' => 'nullable|in:csv']);
        $selected = $this->selectedSchool($request);
        $all = $this->service()->accountChoices($this->access->schools($request->user()));
        $choices = $selected ? array_filter($all, fn ($c) => in_array($selected->id, $c['schools'])) : $all;
        $account = $request->input('account');
        if (!$account || !isset($choices[$account])) {
            $account = array_key_first(array_filter($choices, fn ($c) => str_starts_with($c['value'], 'account:'))) ?? array_key_first($choices);
        }
        $chosen = $choices[$account] ?? null;
        [$lines, $opening, $balance] = $this->service()->ledger($chosen['books'] ?? [], $period['from'], $period['to']);
        $multiSchool = count($chosen['schools'] ?? []) > 1;
        if ($request->input('download') === 'csv') {
            return $this->service()->csv($chosen['label'] ?? 'account', $lines, $opening, $balance, $period['from'], $period['to'], $multiSchool);
        }
        return view('admin.accountant.management.bank-ledger', $period + $this->periodViewData($period) + compact('lines', 'opening', 'balance', 'choices', 'account', 'selected', 'multiSchool')
            + ['nav' => $this->nav($request, $selected?->id), 'selectedSchool' => $selected, 'activeSection' => 'ledger']);
    }
}
