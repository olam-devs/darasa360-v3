<?php

namespace App\Services;

use App\Models\Central\ActivityLog;
use App\Models\Central\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Receipt check and bank ledger, shared by the management portal and the school accountants.
 *
 * Fee entries were often recorded one receipt voucher per particular even when the parent paid once.
 * Accountants confirm which vouchers were one payment into the bank (a "bank receipt": same student, same
 * account, any recorded dates) with the real bank date and reference. Groups live in the central database
 * (receipt_groups / receipt_group_vouchers); tenant vouchers are never changed. The bank ledger shows a
 * confirmed group as one line on its bank date, and an account shared by several schools (same account
 * number) as one ledger with every school's movements.
 */
class ReceiptCheckService
{
    public function __construct(private TenantDatabaseManager $tenants) {}

    public static function shortName(string $name): string
    {
        return \App\Http\Controllers\SchoolManagementController::shortName($name);
    }

    private function central() { return DB::connection('central'); }

    /** Receipt vouchers that hold money in a book (advance applications have no book). */
    private function receiptVouchers($db)
    {
        return $db->table('vouchers as v')->where('v.voucher_type', 'Receipt')->whereNull('v.voided_at')->whereNotNull('v.book_id');
    }

    /** Confirmed groups of a school, keyed by voucher id. */
    public function groupedVoucherIds(int $schoolId): array
    {
        return $this->central()->table('receipt_group_vouchers')->where('school_id', $schoolId)->pluck('receipt_group_id', 'voucher_id')->all();
    }

    public static function batchLabel(?string $notes): string
    {
        return str_contains((string)$notes, '__BATCH:') ? 'Combined receipt' : (trim(explode('__BATCH:', (string)$notes)[0]) ?: 'Receipt');
    }

    private function itemQuery($db)
    {
        return $this->receiptVouchers($db)
            ->leftJoin('students as s', 's.id', '=', 'v.student_id')->leftJoin('books as b', 'b.id', '=', 'v.book_id')
            ->leftJoin('particulars as p', 'p.id', '=', 'v.particular_id')->leftJoin('particular_student as ps', 'ps.id', '=', 'v.particular_student_id')
            ->orderBy('v.date')->orderBy('s.name')->orderBy('v.created_at')
            ->select(['v.id', 'v.voucher_number', 'v.date', 'v.debit', 'v.book_id', 'v.student_id', 'v.notes', 'v.created_at', 's.name as student', 's.class',
                'b.name as book', 'p.name as particular', 'ps.quarter']);
    }

    private static function item(object $v): object
    {
        return (object)['id' => $v->id, 'number' => $v->voucher_number, 'particular' => $v->particular ?: self::batchLabel($v->notes),
            'quarter' => $v->quarter, 'amount' => (float)$v->debit, 'entered' => $v->created_at, 'date' => $v->date];
    }

    /**
     * Proposals and confirmed receipts of one school.
     * Without $studentId: open entries in the period grouped by student + recorded date + account, and groups
     * whose bank date is in the period. With $studentId: every open entry of that student (any date) in one
     * card per account so entries recorded on different days can be merged, and all of the student's groups.
     */
    public function forSchool(School $school, string $from, string $to, bool $onlyMulti = false, ?int $studentId = null): array
    {
        $short = self::shortName($school->name);
        $grouped = $this->groupedVoucherIds($school->id);
        $groups = $this->central()->table('receipt_groups')->where('school_id', $school->id)
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId), fn ($q) => $q->whereBetween('receipt_date', [$from, $to]))
            ->orderBy('receipt_date')->get();
        $data = $this->tenants->executeForSchool($school, function () use ($from, $to, $grouped, $groups, $studentId) {
            $db = DB::connection('tenant');
            $items = $this->itemQuery($db)->when($studentId, fn ($q) => $q->where('v.student_id', $studentId), fn ($q) => $q->whereBetween('v.date', [$from, $to]))->get();
            $groupIds = $groups->pluck('id')->all();
            $groupVoucherIds = collect($grouped)->filter(fn ($g) => in_array($g, $groupIds))->keys()->all();
            $groupItems = $groupVoucherIds ? $db->table('vouchers as v')->whereIn('v.id', $groupVoucherIds)
                ->leftJoin('particulars as p', 'p.id', '=', 'v.particular_id')->leftJoin('particular_student as ps', 'ps.id', '=', 'v.particular_student_id')
                ->get(['v.id', 'v.date', 'v.debit', 'v.notes', 'v.voucher_number', 'p.name as particular', 'ps.quarter']) : collect();
            return [
                'items' => $items, 'groupItems' => $groupItems,
                'students' => $db->table('students')->whereIn('id', $groups->pluck('student_id'))->get(['id', 'name', 'class'])->keyBy('id'),
                'books' => $db->table('books')->whereIn('id', $groups->pluck('book_id'))->pluck('name', 'id'),
            ];
        });
        $open = $data['items']->filter(fn ($v) => !isset($grouped[$v->id]));
        $proposals = collect();
        $key = $studentId ? fn ($v) => $v->book_id : fn ($v) => $v->student_id.'|'.$v->date.'|'.$v->book_id;
        foreach ($open->groupBy($key) as $vs) {
            if ($onlyMulti && $vs->count() < 2) continue;
            $first = $vs->first();
            $hints = [];
            if (!$studentId) {
                $entered = $vs->map(fn ($v) => $v->created_at ? \Carbon\Carbon::parse($v->created_at)->timezone('Africa/Dar_es_Salaam')->toDateString() : null)->unique()->filter(fn ($d) => $d && $d !== $first->date);
                if ($entered->isNotEmpty()) $hints[] = 'Entered on '.$entered->map(fn ($d) => date('d M', strtotime($d)))->implode(', ').' — check the bank date';
                $otherBooks = $open->filter(fn ($v) => $v->student_id === $first->student_id && $v->date === $first->date && $v->book_id !== $first->book_id)->pluck('book')->unique();
                if ($otherBooks->isNotEmpty()) $hints[] = 'Same student also paid into '.$otherBooks->implode(', ').' on this date';
            } elseif ($vs->pluck('date')->unique()->count() > 1) {
                $hints[] = 'Entries recorded on different days: tick the ones that were one bank payment';
            }
            if ($vs->contains(fn ($v) => str_contains((string)$v->notes, '__BATCH:'))) $hints[] = 'Entered as one combined receipt: confirm it with the real bank date';
            $proposals->push((object)['school_id' => $school->id, 'school' => $short, 'student_id' => $first->student_id, 'student' => $first->student, 'class' => $first->class,
                'book' => $first->book, 'date' => $vs->max('date'), 'dates' => $vs->pluck('date')->unique()->values(), 'total' => (float)$vs->sum('debit'), 'hints' => $hints,
                'items' => $vs->map(fn ($v) => self::item($v))->values()]);
        }
        $confirmed = $groups->map(function ($g) use ($data, $grouped, $short, $school) {
            $its = $data['groupItems']->filter(fn ($v) => ($grouped[$v->id] ?? null) === $g->id);
            $st = $data['students'][$g->student_id] ?? null;
            return (object)((array)$g + ['school' => $short, 'student' => $st->name ?? 'Student #'.$g->student_id, 'class' => $st->class ?? null,
                'book' => $data['books'][$g->book_id] ?? 'Book #'.$g->book_id,
                'items' => $its->map(fn ($v) => (object)['particular' => $v->particular ?: self::batchLabel($v->notes), 'quarter' => $v->quarter, 'amount' => (float)$v->debit,
                    'date' => $v->date, 'number' => $v->voucher_number])->values()]);
        });
        return ['proposals' => $proposals, 'confirmed' => $confirmed];
    }

    /** Students of a school matching a name or registration number, with their number of fee entries. */
    public function searchStudents(School $school, string $q): Collection
    {
        return $this->tenants->executeForSchool($school, fn () => DB::connection('tenant')->table('students as s')
            ->where(fn ($w) => $w->where('s.name', 'like', '%'.$q.'%')->orWhere('s.student_reg_no', 'like', '%'.$q.'%'))
            ->leftJoin('vouchers as v', fn ($j) => $j->on('v.student_id', '=', 's.id')->where('v.voucher_type', 'Receipt')->whereNull('v.voided_at')->whereNotNull('v.book_id'))
            ->groupBy('s.id', 's.name', 's.class', 's.student_reg_no', 's.status')->orderBy('s.name')->limit(30)
            ->get(['s.id', 's.name', 's.class', 's.student_reg_no', 's.status', DB::raw('count(v.id) as receipts')]));
    }

    public function student(School $school, int $studentId): ?object
    {
        return $this->tenants->executeForSchool($school, fn () => DB::connection('tenant')->table('students')->where('id', $studentId)->first(['id', 'name', 'class', 'student_reg_no']));
    }

    private function log(School $school, object $by, string $action, string $description, ?string $ip): void
    {
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $by->id, 'user_name' => $by->name,
            'action' => $action, 'description' => $description, 'ip_address' => $ip]);
    }

    /** Confirm vouchers (one student, one account) as one bank receipt. Returns [ok, message]. */
    public function confirm(School $school, array $voucherIds, string $date, ?string $reference, ?string $note, object $by, ?string $ip): array
    {
        $ids = array_values(array_unique(array_map('intval', $voucherIds)));
        $vouchers = $this->tenants->executeForSchool($school, fn () => $this->receiptVouchers(DB::connection('tenant'))->whereIn('v.id', $ids)->get(['v.id', 'v.debit', 'v.book_id', 'v.student_id']));
        if ($vouchers->count() !== count($ids)) return [false, 'Some items are no longer valid receipts (voided or changed). Refresh and try again.'];
        if ($vouchers->pluck('book_id')->unique()->count() > 1 || $vouchers->pluck('student_id')->unique()->count() > 1) {
            return [false, 'One bank receipt must be for one student and one account.'];
        }
        try {
            $groupId = $this->central()->transaction(function () use ($school, $vouchers, $ids, $date, $reference, $note, $by) {
                $groupId = $this->central()->table('receipt_groups')->insertGetId([
                    'school_id' => $school->id, 'book_id' => $vouchers->first()->book_id, 'student_id' => $vouchers->first()->student_id,
                    'receipt_date' => $date, 'reference' => $reference ?: null, 'total' => $vouchers->sum('debit'),
                    'note' => $note ?: null, 'confirmed_by' => $by->id, 'created_at' => now(), 'updated_at' => now()]);
                $this->central()->table('receipt_group_vouchers')->insert(array_map(fn ($id) => ['receipt_group_id' => $groupId, 'school_id' => $school->id, 'voucher_id' => $id], $ids));
                return $groupId;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return [false, 'Some of these items were already confirmed in another receipt. Refresh the page.'];
        }
        $this->log($school, $by, 'receipt_confirmed', "Confirmed bank receipt #$groupId: ".count($ids).' item(s), TSh '.number_format($vouchers->sum('debit'), 2)
            .", bank date $date".($reference ? ", ref $reference" : '').'.', $ip);
        return [true, 'Confirmed as one bank receipt: TSh '.number_format($vouchers->sum('debit'), 0).' on '.date('d M Y', strtotime($date)).'.'];
    }

    /** Change the bank date, reference or note of a confirmed receipt. */
    public function update(School $school, int $groupId, string $date, ?string $reference, ?string $note, object $by, ?string $ip): array
    {
        $g = $this->central()->table('receipt_groups')->where('school_id', $school->id)->find($groupId);
        if (!$g) return [false, 'Receipt not found.'];
        $this->central()->table('receipt_groups')->where('id', $g->id)->update(['receipt_date' => $date, 'reference' => $reference ?: null, 'note' => $note ?: null, 'updated_at' => now()]);
        $this->log($school, $by, 'receipt_updated', "Bank receipt #$groupId: bank date {$g->receipt_date} → $date, ref ".($g->reference ?: '—').' → '.($reference ?: '—').'.', $ip);
        return [true, 'Receipt updated: bank date '.date('d M Y', strtotime($date)).($reference ? ", ref $reference" : '').'.'];
    }

    /** Undo a confirmed receipt: its items become open entries again. */
    public function undo(School $school, int $groupId, object $by, ?string $ip): array
    {
        $g = $this->central()->table('receipt_groups')->where('school_id', $school->id)->find($groupId);
        if (!$g) return [false, 'Receipt not found.'];
        $this->central()->transaction(function () use ($groupId) {
            $this->central()->table('receipt_group_vouchers')->where('receipt_group_id', $groupId)->delete();
            $this->central()->table('receipt_groups')->where('id', $groupId)->delete();
        });
        $this->log($school, $by, 'receipt_undone', "Undid bank receipt #$groupId (TSh ".number_format($g->total, 2).", {$g->receipt_date}).", $ip);
        return [true, 'Receipt undone; its items are back in the list.'];
    }

    /**
     * Account choices over the given schools: one per bank account number (every school holding a book with
     * that number) plus one per book without a number (e.g. each school's cash book).
     * value => ['value', 'label', 'books' => [[School, bookId], ...], 'schools' => [schoolId, ...]]
     */
    public function accountChoices(Collection $schools): array
    {
        $choices = [];
        foreach ($schools as $school) {
            $books = $this->tenants->executeForSchool($school, fn () => DB::connection('tenant')->table('books')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'bank_account_number']));
            foreach ($books as $b) {
                $acct = $b->bank_account_number ? preg_replace('/\s+/', '', $b->bank_account_number) : null;
                $value = $acct ? 'account:'.$acct : 'book:'.$school->id.':'.$b->id;
                $choices[$value] ??= ['value' => $value, 'label' => $acct ? $b->name.' · '.$acct : $b->name.' · '.self::shortName($school->name), 'books' => [], 'schools' => []];
                $choices[$value]['books'][] = [$school, $b->id];
                $choices[$value]['schools'][] = $school->id;
            }
        }
        foreach ($choices as &$c) {
            if (count($c['schools']) > 1) $c['label'] .= ' · '.implode(' + ', array_map(fn ($sb) => self::shortName($sb[0]->name), $c['books']));
        }
        return $choices;
    }

    private static function ledgerDetails(object $v): string
    {
        if ($v->voucher_type === 'Receipt') return trim(($v->student ?? '').' — '.($v->particular ?: self::batchLabel($v->notes)));
        if ($v->staff) return 'Salary · '.$v->staff.($v->salary_period ? ' ('.$v->salary_period.')' : '');
        if ($v->fee_for) return 'Bank fee for '.$v->fee_for.($v->category ? ' · '.$v->category : '');
        $note = trim(explode('__BATCH:', (string)$v->notes)[0]);
        return trim(implode(' · ', array_filter([$v->category ? 'Expense · '.$v->category : null, $v->expense_title, $v->payment_by_receipt_to, $note ?: null]))) ?: $v->voucher_type;
    }

    /** Ledger of the given books together: [lines, opening, closing]. */
    public function ledger(array $books, string $from, string $to): array
    {
        $lines = collect(); $opening = 0.0;
        foreach ($books as [$school, $bookId]) {
            $short = self::shortName($school->name);
            $grouped = $this->groupedVoucherIds($school->id);
            $groups = $this->central()->table('receipt_groups')->where('school_id', $school->id)->where('book_id', $bookId)->get()->keyBy('id');
            $r = $this->tenants->executeForSchool($school, function () use ($bookId) {
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
                $particulars = $g ? $r['vouchers']->filter(fn ($x) => ($grouped[$x->id] ?? null) === $g->id)->map(fn ($x) => $x->particular ?: self::batchLabel($x->notes))->implode(', ') : null;
                $lines->push((object)['date' => $date, 'school' => $short, 'type' => $g ? 'Bank receipt' : $v->voucher_type,
                    'details' => $g ? trim($v->student.' — '.$particulars) : self::ledgerDetails($v),
                    'reference' => $g ? $g->reference : $v->reference_number, 'number' => $g ? 'Group #'.$g->id : $v->voucher_number,
                    'confirmed' => (bool)$g, 'in' => $in, 'out' => $out, 'sort' => $date.sprintf('%012d', $v->id)]);
            }
        }
        $lines = $lines->sortBy('sort')->values();
        $balance = $opening;
        foreach ($lines as $l) { $balance += $l->in - $l->out; $l->balance = $balance; }
        return [$lines, $opening, $balance];
    }

    public function csv(string $label, Collection $lines, float $opening, float $balance, string $from, string $to, bool $withSchool)
    {
        $name = 'bank-ledger-'.preg_replace('/[^A-Za-z0-9]+/', '-', $label).'-'.$from.'-to-'.$to.'.csv';
        return response()->streamDownload(function () use ($lines, $opening, $balance, $from, $to, $withSchool) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Date'], $withSchool ? ['School'] : [], ['Type', 'Details', 'Reference', 'Number', 'Money in', 'Money out', 'Balance']));
            fputcsv($out, array_merge([$from], $withSchool ? [''] : [], ['Opening balance', '', '', '', '', '', number_format($opening, 2, '.', '')]));
            foreach ($lines as $l) fputcsv($out, array_merge([$l->date], $withSchool ? [$l->school] : [], [$l->type, $l->details, $l->reference, $l->number,
                $l->in ? number_format($l->in, 2, '.', '') : '', $l->out ? number_format($l->out, 2, '.', '') : '', number_format($l->balance, 2, '.', '')]));
            fputcsv($out, array_merge([$to], $withSchool ? [''] : [], ['Closing balance', '', '', '', '', '', number_format($balance, 2, '.', '')]));
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
