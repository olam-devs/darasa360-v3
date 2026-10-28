<?php

namespace App\Http\Controllers;

use App\Models\Central\School;
use App\Services\ManagementQuarterAnalytics;
use App\Services\ReceiptCheckService;
use Illuminate\Http\Request;

/**
 * Receipt check and bank ledger for a school's own accountants: check fee entries against the physical
 * receipts and bank slips, merge entries that were one payment, set the real bank date and reference.
 * Their bank ledger shows only their own school; the combined ledger of accounts shared by several
 * schools is in the management portal.
 */
class AccountantReceiptController extends SchoolManagementController
{
    private function service(): ReceiptCheckService { return app(ReceiptCheckService::class); }

    private function school(Request $request): School
    {
        try { $school = app('current_school'); } catch (\Throwable $e) { $school = null; }
        $school ??= $request->user()?->school_id ? School::find($request->user()->school_id) : null;
        return $school ?? abort(403, 'No school selected.');
    }

    private function periodViewData(array $period): array
    {
        return ['quarterLabels' => ManagementQuarterAnalytics::LABELS,
            'yearOptions' => collect([$period['year'], now('Africa/Dar_es_Salaam')->year])->unique()->sortDesc()->values()];
    }

    public function index(Request $request)
    {
        $school = $this->school($request);
        $period = $this->period($request);
        $request->validate(['multi' => 'nullable|in:1', 'q' => 'nullable|string|max:80', 'student' => 'nullable|integer']);
        $onlyMulti = $request->boolean('multi');
        $q = trim((string)$request->input('q'));
        $studentId = $request->integer('student') ?: null;
        $r = $this->service()->forSchool($school, $period['from'], $period['to'], $onlyMulti, $studentId);
        $proposals = $r['proposals']->sortBy([['date', 'asc'], ['student', 'asc']])->values();
        $confirmed = $r['confirmed'];
        $found = $q !== '' ? $this->service()->searchStudents($school, $q) : collect();
        $student = $studentId ? $this->service()->student($school, $studentId) : null;
        return view('admin.accountant.modules.receipt-check', $period + $this->periodViewData($period)
            + compact('school', 'proposals', 'confirmed', 'onlyMulti', 'q', 'found', 'student') + ['selectedSchool' => null]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['voucher_ids' => 'required|array|min:1|max:50', 'voucher_ids.*' => 'integer',
            'receipt_date' => 'required|date_format:Y-m-d', 'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500'],
            ['voucher_ids.required' => 'Tick at least one item to confirm.']);
        [$ok, $msg] = $this->service()->confirm($this->school($request), $data['voucher_ids'], $data['receipt_date'], $data['reference'] ?? null, $data['note'] ?? null, $request->user(), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function update(Request $request, int $groupId)
    {
        $data = $request->validate(['receipt_date' => 'required|date_format:Y-m-d', 'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500']);
        [$ok, $msg] = $this->service()->update($this->school($request), $groupId, $data['receipt_date'], $data['reference'] ?? null, $data['note'] ?? null, $request->user(), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function undo(Request $request, int $groupId)
    {
        [$ok, $msg] = $this->service()->undo($this->school($request), $groupId, $request->user(), $request->ip());
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function ledger(Request $request)
    {
        $school = $this->school($request);
        $period = $this->period($request);
        $request->validate(['account' => ['nullable', 'regex:/^(account:[0-9A-Za-z]+|book:\d+:\d+)$/'], 'download' => 'nullable|in:csv']);
        // Only this school's own movements; the combined ledger of shared accounts is for the manager (owner).
        $choices = $this->service()->accountChoices(collect([$school]));
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
        return view('admin.accountant.modules.bank-ledger', $period + $this->periodViewData($period)
            + compact('school', 'lines', 'opening', 'balance', 'choices', 'account', 'multiSchool') + ['selectedSchool' => null, 'selected' => null]);
    }
}
