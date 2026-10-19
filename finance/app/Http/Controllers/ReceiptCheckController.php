<?php

namespace App\Http\Controllers;

use App\Models\Central\ActivityLog;
use App\Services\ManagementQuarterAnalytics;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Receipt check and bank ledger for the management portal.
 *
 * Older fee entries recorded one receipt voucher per particular even when the parent paid once. The check
 * proposes the original payments (same student, same date, same book) so an accountant can confirm them as
 * one bank receipt with the real bank date and reference. Confirmed groups live in the central database
 * (receipt_groups / receipt_group_vouchers); tenant vouchers are never changed. The bank ledger shows a
 * confirmed group as one line on its bank date, so it can be matched against the bank statement.
 */
class ReceiptCheckController extends SchoolManagementController
{
    /** Receipt vouchers that hold money in a book (advance applications have no book). */
    private function receiptVouchers($db)
    {
        return $db->table('vouchers as v')->where('v.voucher_type', 'Receipt')->whereNull('v.voided_at')->whereNotNull('v.book_id');
    }

    /** Confirmed groups of a school, keyed by voucher id. */
    private function groupedVoucherIds(int $schoolId): array
    {
        return DB::connection('central')->table('receipt_group_vouchers')->where('school_id', $schoolId)->pluck('receipt_group_id', 'voucher_id')->all();
    }

    public function index(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $period = $this->period($request);
        ['from' => $from, 'to' => $to] = $period;
        $request->validate(['multi' => 'nullable|in:1']);
        $onlyMulti = $request->boolean('multi');
        $selected = $this->selectedSchool($request);
        $schools = $selected ? collect([$selected]) : $this->access->schools($request->user());
        $nav = $this->nav($request, $selected?->id);
        $proposals = collect(); $confirmed = collect();
        foreach ($schools as $school) {
            $short = self::shortName($school->name);
            $grouped = $this->groupedVoucherIds($school->id);
            $groups = DB::connection('central')->table('receipt_groups')->where('school_id', $school->id)->whereBetween('receipt_date', [$from, $to])->orderBy('receipt_date')->get();
            $data = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($from, $to, $grouped, $groups) {
                $db = DB::connection('tenant');
                $items = $this->receiptVouchers($db)->whereBetween('v.date', [$from, $to])
                    ->leftJoin('students as s', 's.id', '=', 'v.student_id')->leftJoin('books as b', 'b.id', '=', 'v.book_id')
                    ->leftJoin('particulars as p', 'p.id', '=', 'v.particular_id')->leftJoin('particular_student as ps', 'ps.id', '=', 'v.particular_student_id')
                    ->orderBy('v.date')->orderBy('s.name')->orderBy('v.created_at')
                    ->get(['v.id', 'v.voucher_number', 'v.date', 'v.debit', 'v.book_id', 'v.student_id', 'v.notes', 'v.created_at', 's.name as student', 's.class',
                        'b.name as book', 'p.name as particular', 'ps.quarter']);
                $groupIds = $groups->pluck('id')->all();
                $groupVoucherIds = collect($grouped)->filter(fn ($g) => in_array($g, $groupIds))->keys()->all();
                $groupItems = $groupVoucherIds ? $db->table('vouchers as v')->whereIn('v.id', $groupVoucherIds)
                    ->leftJoin('particulars as p', 'p.id', '=', 'v.particular_id')->leftJoin('particular_student as ps', 'ps.id', '=', 'v.particular_student_id')
                    ->get(['v.id', 'v.date', 'v.debit', 'v.notes', 'p.name as particular', 'ps.quarter']) : collect();
                return [
                    'items' => $items, 'groupItems' => $groupItems,
                    'students' => $db->table('students')->whereIn('id', $groups->pluck('student_id'))->pluck('name', 'id'),
                    'books' => $db->table('books')->whereIn('id', $groups->pluck('book_id'))->pluck('name', 'id'),
                ];
            });
            $open = $data['items']->filter(fn ($v) => !isset($grouped[$v->id]));
            foreach ($open->groupBy(fn ($v) => $v->student_id.'|'.$v->date.'|'.$v->book_id) as $key => $vs) {
                if ($onlyMulti && $vs->count() < 2) continue;
                $first = $vs->first();
                $hints = [];
                $entered = $vs->map(fn ($v) => $v->created_at ? \Carbon\Carbon::parse($v->created_at)->timezone('Africa/Dar_es_Salaam')->toDateString() : null)->unique()->filter(fn ($d) => $d && $d !== $first->date);
                if ($entered->isNotEmpty()) $hints[] = 'Entered on '.$entered->map(fn ($d) => date('d M', strtotime($d)))->implode(', ').' — check the bank date';
                $otherBooks = $open->filter(fn ($v) => $v->student_id === $first->student_id && $v->date === $first->date && $v->book_id !== $first->book_id)->pluck('book')->unique();
                if ($otherBooks->isNotEmpty()) $hints[] = 'Same student also paid into '.$otherBooks->implode(', ').' on this date';
                if ($vs->contains(fn ($v) => str_contains((string)$v->notes, '__BATCH:'))) $hints[] = 'Already entered as one combined receipt';
                $proposals->push((object)['school_id' => $school->id, 'school' => $short, 'student' => $first->student, 'class' => $first->class,
                    'book' => $first->book, 'date' => $first->date, 'total' => (float)$vs->sum('debit'), 'hints' => $hints,
                    'items' => $vs->map(fn ($v) => (object)['id' => $v->id, 'number' => $v->voucher_number, 'particular' => $v->particular ?: $this->batchLabel($v->notes),
                        'quarter' => $v->quarter, 'amount' => (float)$v->debit, 'entered' => $v->created_at])->values()]);
            }
            foreach ($groups as $g) {
                $its = $data['groupItems']->filter(fn ($v) => ($grouped[$v->id] ?? null) === $g->id);
                $confirmed->push((object)((array)$g + ['school' => $short, 'student' => $data['students'][$g->student_id] ?? 'Student #'.$g->student_id,
                    'book' => $data['books'][$g->book_id] ?? 'Book #'.$g->book_id,
                    'items' => $its->map(fn ($v) => (object)['particular' => $v->particular ?: $this->batchLabel($v->notes), 'quarter' => $v->quarter, 'amount' => (float)$v->debit, 'date' => $v->date])->values()]));
            }
        }
        $proposals = $proposals->sortBy([['date', 'asc'], ['student', 'asc']])->values();
        $quarterLabels = ManagementQuarterAnalytics::LABELS;
        $yearOptions = collect([$period['year'], now('Africa/Dar_es_Salaam')->year])->unique()->sortDesc()->values();
        return view('admin.accountant.management.receipt-check', $period + compact('proposals', 'confirmed', 'selected', 'nav', 'onlyMulti', 'quarterLabels', 'yearOptions')
            + ['selectedSchool' => $selected, 'activeSection' => 'receipts']);
    }

    private function ledgerDetails(object $v): string
    {
        if ($v->voucher_type === 'Receipt') return trim(($v->student ?? '').' — '.($v->particular ?: $this->batchLabel($v->notes)));
        if ($v->staff) return 'Salary · '.$v->staff.($v->salary_period ? ' ('.$v->salary_period.')' : '');
        if ($v->fee_for) return 'Bank fee for '.$v->fee_for.($v->category ? ' · '.$v->category : '');
        $note = trim(explode('__BATCH:', (string)$v->notes)[0]);
        return trim(implode(' · ', array_filter([$v->category ? 'Expense · '.$v->category : null, $v->expense_title, $v->payment_by_receipt_to, $note ?: null]))) ?: $v->voucher_type;
    }

    private function batchLabel(?string $notes): string
    {
        return str_contains((string)$notes, '__BATCH:') ? 'Combined receipt' : (trim(explode('__BATCH:', (string)$notes)[0]) ?: 'Receipt');
    }

    /** Confirm ticked receipt vouchers (same student and book) as one bank receipt with a bank date and reference. */
    public function confirm(Request $request)
    {
        $data = $request->validate(['school_id' => 'required|integer', 'voucher_ids' => 'required|array|min:1|max:50', 'voucher_ids.*' => 'integer',
            'receipt_date' => 'required|date_format:Y-m-d', 'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500'],
            ['voucher_ids.required' => 'Tick at least one item to confirm.']);
        $school = $this->access->school($request->user(), (int)$data['school_id']);
        $manager = $this->access->manager($request->user());
        $ids = array_values(array_unique(array_map('intval', $data['voucher_ids'])));
        $vouchers = app(TenantDatabaseManager::class)->executeForSchool($school, fn () => $this->receiptVouchers(DB::connection('tenant'))->whereIn('v.id', $ids)
            ->get(['v.id', 'v.debit', 'v.book_id', 'v.student_id']));
        if ($vouchers->count() !== count($ids)) return back()->with('error', 'Some items are no longer valid receipts (voided or changed). Refresh and try again.');
        if ($vouchers->pluck('book_id')->unique()->count() > 1 || $vouchers->pluck('student_id')->unique()->count() > 1) {
            return back()->with('error', 'One bank receipt must be for one student and one account.');
        }
        try {
            $groupId = DB::connection('central')->transaction(function () use ($data, $school, $vouchers, $manager, $ids) {
                $groupId = DB::connection('central')->table('receipt_groups')->insertGetId([
                    'school_id' => $school->id, 'book_id' => $vouchers->first()->book_id, 'student_id' => $vouchers->first()->student_id,
                    'receipt_date' => $data['receipt_date'], 'reference' => $data['reference'] ?? null, 'total' => $vouchers->sum('debit'),
                    'note' => $data['note'] ?? null, 'confirmed_by' => $manager->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::connection('central')->table('receipt_group_vouchers')->insert(array_map(fn ($id) => ['receipt_group_id' => $groupId, 'school_id' => $school->id, 'voucher_id' => $id], $ids));
                return $groupId;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Some of these items were already confirmed in another receipt. Refresh the page.');
        }
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
            'action' => 'management_receipt_confirmed', 'description' => "Confirmed bank receipt #$groupId: ".count($ids).' item(s), TSh '.number_format($vouchers->sum('debit'), 2)
                .', bank date '.$data['receipt_date'].(!empty($data['reference']) ? ', ref '.$data['reference'] : '').'.', 'ip_address' => $request->ip()]);
        return back()->with('success', 'Confirmed as one bank receipt: TSh '.number_format($vouchers->sum('debit'), 0).' on '.date('d M Y', strtotime($data['receipt_date'])).'.');
    }

    /** Undo a confirmed receipt: its items become separate proposals again. */
    public function undo(Request $request, int $groupId)
    {
        $group = DB::connection('central')->table('receipt_groups')->find($groupId) ?? abort(404);
        $school = $this->access->school($request->user(), (int)$group->school_id);
        $manager = $this->access->manager($request->user());
        DB::connection('central')->transaction(function () use ($groupId) {
            DB::connection('central')->table('receipt_group_vouchers')->where('receipt_group_id', $groupId)->delete();
            DB::connection('central')->table('receipt_groups')->where('id', $groupId)->delete();
        });
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
            'action' => 'management_receipt_undone', 'description' => "Undid bank receipt #$groupId (TSh ".number_format($group->total, 2).', '.$group->receipt_date.').', 'ip_address' => $request->ip()]);
        return back()->with('success', 'Receipt undone; its items are back in the proposals.');
    }

    /**
     * Bank ledger for one account: opening balance, every movement in the period (confirmed receipts as one
     * line on their bank date), running balance and closing balance. account = "account:<number>" (that
     * account in every selected school) or "book:<schoolId>:<bookId>" (one school's book, e.g. a cash book).
     */
    public function ledger(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $period = $this->period($request);
        ['from' => $from, 'to' => $to] = $period;
        $request->validate(['account' => ['nullable', 'regex:/^(account:[0-9A-Za-z]+|book:\d+:\d+)$/'], 'download' => 'nullable|in:csv']);
        $selected = $this->selectedSchool($request);
        $schools = $selected ? collect([$selected]) : $this->access->schools($request->user());
        $nav = $this->nav($request, $selected?->id);
        // Account choices: bank accounts by number across the selected schools, plus each school's books without a number.
        $choices = [];
        foreach ($schools as $school) {
            $books = app(TenantDatabaseManager::class)->executeForSchool($school, fn () => DB::connection('tenant')->table('books')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'bank_account_number']));
            foreach ($books as $b) {
                $acct = $b->bank_account_number ? preg_replace('/\s+/', '', $b->bank_account_number) : null;
                $value = $acct ? 'account:'.$acct : 'book:'.$school->id.':'.$b->id;
                $choices[$value] ??= ['value' => $value, 'label' => $acct ? $b->name.' · '.$acct : $b->name.' · '.self::shortName($school->name), 'books' => []];
                $choices[$value]['books'][] = [$school, $b->id];
            }
        }
        $account = $request->input('account') ?: (array_key_first(array_filter($choices, fn ($c) => str_starts_with($c['value'], 'account:'))) ?? array_key_first($choices));
        $lines = collect(); $opening = 0.0;
        foreach ($choices[$account]['books'] ?? [] as [$school, $bookId]) {
            $short = self::shortName($school->name);
            $grouped = $this->groupedVoucherIds($school->id);
            $groups = DB::connection('central')->table('receipt_groups')->where('school_id', $school->id)->where('book_id', $bookId)->get()->keyBy('id');
            $r = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($bookId) {
                $db = DB::connection('tenant');
                return [
                    'opening' => (float)$db->table('books')->where('id', $bookId)->value('opening_balance'),
                    'vouchers' => $db->table('vouchers as v')->where('v.book_id', $bookId)->whereNull('v.voided_at')
                        ->leftJoin('students as s', 's.id', '=', 'v.student_id')->leftJoin('particulars as p', 'p.id', '=', 'v.particular_id')
                        // What a payment was for: an approved expense (or its bank fee), or a salary.
                        ->leftJoin('expense_submissions as es', 'es.voucher_id', '=', 'v.id')->leftJoin('expense_submissions as ef', 'ef.bank_fee_voucher_id', '=', 'v.id')
                        ->leftJoin('expense_categories as ec', 'ec.id', '=', DB::raw('coalesce(es.expense_category_id, ef.expense_category_id)'))
                        ->leftJoin('payroll_entries as pe', 'pe.voucher_id', '=', 'v.id')->leftJoin('staff as st', 'st.id', '=', 'pe.staff_id')
                        ->orderBy('v.date')->orderBy('v.id')
                        ->get(['v.id', 'v.voucher_number', 'v.voucher_type', 'v.date', 'v.debit', 'v.credit', 'v.payment_by_receipt_to', 'v.notes', 'v.reference_number', 's.name as student', 'p.name as particular',
                            'ec.name as category', 'es.title as expense_title', 'ef.submission_number as fee_for', 'st.name as staff', 'pe.period as salary_period']),
                ];
            });
            $opening += $r['opening'];
            $seenGroups = [];
            foreach ($r['vouchers'] as $v) {
                $g = isset($grouped[$v->id]) ? ($groups[$grouped[$v->id]] ?? null) : null;
                $date = $g ? $g->receipt_date : $v->date;
                if ($g && isset($seenGroups[$g->id])) continue;
                if ($g) $seenGroups[$g->id] = true;
                $in = $g ? (float)$g->total : (float)$v->debit; $out = $g ? 0.0 : (float)$v->credit;
                if ($date < $from) { $opening += $in - $out; continue; }
                if ($date > $to) continue;
                $particulars = $g ? $r['vouchers']->filter(fn ($x) => ($grouped[$x->id] ?? null) === $g->id)->map(fn ($x) => $x->particular ?: 'receipt')->implode(', ') : null;
                $lines->push((object)['date' => $date, 'school' => $short, 'type' => $g ? 'Bank receipt' : $v->voucher_type,
                    'details' => $g ? trim($v->student.' — '.$particulars) : $this->ledgerDetails($v),
                    'reference' => $g ? $g->reference : $v->reference_number, 'number' => $g ? 'Group #'.$g->id : $v->voucher_number,
                    'confirmed' => (bool)$g, 'in' => $in, 'out' => $out, 'sort' => $date.sprintf('%012d', $v->id)]);
            }
        }
        $lines = $lines->sortBy('sort')->values();
        $balance = $opening;
        foreach ($lines as $l) { $balance += $l->in - $l->out; $l->balance = $balance; }
        if ($request->input('download') === 'csv') {
            $name = 'bank-ledger-'.preg_replace('/[^A-Za-z0-9]+/', '-', $choices[$account]['label'] ?? 'account').'-'.$from.'-to-'.$to.'.csv';
            return response()->streamDownload(function () use ($lines, $opening, $balance, $from, $to, $selected) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_merge(['Date'], $selected ? [] : ['School'], ['Type', 'Details', 'Reference', 'Number', 'Money in', 'Money out', 'Balance']));
                fputcsv($out, array_merge([$from], $selected ? [] : [''], ['Opening balance', '', '', '', '', '', number_format($opening, 2, '.', '')]));
                foreach ($lines as $l) fputcsv($out, array_merge([$l->date], $selected ? [] : [$l->school], [$l->type, $l->details, $l->reference, $l->number,
                    $l->in ? number_format($l->in, 2, '.', '') : '', $l->out ? number_format($l->out, 2, '.', '') : '', number_format($l->balance, 2, '.', '')]));
                fputcsv($out, array_merge([$to], $selected ? [] : [''], ['Closing balance', '', '', '', '', '', number_format($balance, 2, '.', '')]));
                fclose($out);
            }, $name, ['Content-Type' => 'text/csv']);
        }
        $quarterLabels = ManagementQuarterAnalytics::LABELS;
        $yearOptions = collect([$period['year'], now('Africa/Dar_es_Salaam')->year])->unique()->sortDesc()->values();
        return view('admin.accountant.management.bank-ledger', $period + compact('lines', 'opening', 'balance', 'choices', 'account', 'selected', 'nav', 'quarterLabels', 'yearOptions')
            + ['selectedSchool' => $selected, 'activeSection' => 'ledger']);
    }
}
