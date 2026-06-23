<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Particular;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalAssistantService
{
    public function resolveAudience(Request $request): ?string
    {
        if ($request->session()->has('parent_student_id')) {
            return 'parent';
        }

        if ($request->session()->has('headmaster_id')) {
            return 'headmaster';
        }

        $user = $request->user();
        if ($user && in_array($user->role, ['accountant', 'superadmin'], true)) {
            return 'accountant';
        }

        return null;
    }

    protected function currency(): string
    {
        $settings = SchoolSetting::getSettings();
        return $settings->currency ?? 'TSH';
    }

    protected function fmt(float $amount): string
    {
        return $this->currency() . ' ' . number_format($amount, 0);
    }

    /**
     * @return list<array{id: string, label: string, hint?: string}>
     */
    public function intentsFor(string $audience): array
    {
        return match ($audience) {
            'parent' => [
                ['id' => 'balance',          'label' => 'What is my child\'s fee balance?',     'hint' => 'Outstanding after scholarships'],
                ['id' => 'unpaid_fees',       'label' => 'Which fee items are still unpaid?',    'hint' => 'Per-item breakdown'],
                ['id' => 'recent_payments',   'label' => 'Show recent fee payments',             'hint' => 'Last 5 receipts'],
                ['id' => 'collected',         'label' => 'How much has been paid so far?',       'hint' => 'Total collected'],
                ['id' => 'fee_structure',     'label' => 'Show all assigned fees',               'hint' => 'Full fee list with amounts'],
                ['id' => 'deadlines',         'label' => 'What are the fee payment deadlines?',  'hint' => 'Due dates per item'],
                ['id' => 'completion',        'label' => 'Are all fee items fully paid?',        'hint' => 'Completion status'],
                ['id' => 'invoice_download',  'label' => 'Where do I get my invoice?',           'hint' => 'Download & print'],
                ['id' => 'contact_school',    'label' => 'How do I contact the school office?',  'hint' => 'Phone / WhatsApp'],
                ['id' => 'portal_help',       'label' => 'How do I use the parent portal?',      'hint' => 'Navigation guide'],
            ],
            'headmaster' => [
                ['id' => 'today_collections',  'label' => 'How much was collected today?',           'hint' => 'Fee receipts only'],
                ['id' => 'week_collections',   'label' => 'Collections this week',                   'hint' => 'Monday–Sunday'],
                ['id' => 'outstanding',        'label' => 'Total outstanding fees',                  'hint' => 'Net of scholarships'],
                ['id' => 'collection_rate',    'label' => 'Overall collection rate',                 'hint' => 'All active students'],
                ['id' => 'active_students',    'label' => 'How many active students?',               'hint' => 'Enrolled count'],
                ['id' => 'top_overdue_class',  'label' => 'Which class has the lowest collection?',  'hint' => 'By % collected'],
            ],
            default => [
                ['id' => 'today_collections',  'label' => 'How much was collected today?',       'hint' => 'Fee receipts only'],
                ['id' => 'week_collections',   'label' => 'Collections this week',               'hint' => 'Monday–Sunday'],
                ['id' => 'month_collections',  'label' => 'Collections this month',              'hint' => 'Calendar month'],
                ['id' => 'outstanding',        'label' => 'Total outstanding fees',              'hint' => 'Net of scholarships'],
                ['id' => 'record_fee',         'label' => 'How do I record a fee payment?',      'hint' => 'Quick steps'],
                ['id' => 'overdue_students',   'label' => 'How many students have balances?',    'hint' => 'Students owing fees'],
            ],
        };
    }

    public function answer(string $audience, string $intentId, Request $request): array
    {
        return match ($audience) {
            'parent'    => $this->answerParent($intentId, (int) $request->session()->get('parent_student_id')),
            'headmaster' => $this->answerStaff($intentId, true),
            default     => $this->answerStaff($intentId, false),
        };
    }

    /**
     * Resolve a free-text message to an intent and return the answer.
     *
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    public function freeText(string $message, string $audience, Request $request): array
    {
        $intentId = $this->resolveIntent($message, $audience);

        if ($intentId === null) {
            return [
                'reply' => $this->noMatchReply($audience),
            ];
        }

        return $this->answer($audience, $intentId, $request);
    }

    /**
     * Map a natural-language message (Swahili or English) to an intent ID.
     */
    protected function resolveIntent(string $message, string $audience): ?string
    {
        $t = mb_strtolower(trim($message));

        // ── Parent intents ──────────────────────────────────────────────────
        if ($audience === 'parent') {
            // balance / outstanding
            if ($this->matches($t, ['balance', 'baki', 'deni', 'outstanding', 'how much do i owe', 'kiasi', 'nilipe', 'ninajohitaji', 'inabaki', 'inakosekana', 'remaining'])) {
                return 'balance';
            }
            // unpaid fees
            if ($this->matches($t, ['unpaid', 'hakulipa', 'hajalipwa', 'not paid', 'which fee', 'ada gani', 'gani hajalipwa', 'what fee', 'what is unpaid', 'pending fee'])) {
                return 'unpaid_fees';
            }
            // recent payments / receipts
            if ($this->matches($t, ['recent', 'malipo ya hivi karibuni', 'receipt', 'risiti', 'last payment', 'payment history', 'historia ya malipo', 'recent payment', 'payments made', 'alilipa'])) {
                return 'recent_payments';
            }
            // how much collected / total paid
            if ($this->matches($t, ['collected', 'paid so far', 'total paid', 'kimelipwa', 'jumla ya malipo', 'how much paid', 'imelipwa', 'total collected'])) {
                return 'collected';
            }
            // fee structure / all fees
            if ($this->matches($t, ['fee structure', 'all fees', 'muundo wa ada', 'ada zote', 'list of fees', 'orodha ya ada', 'fees assigned', 'fee list'])) {
                return 'fee_structure';
            }
            // deadlines
            if ($this->matches($t, ['deadline', 'due date', 'tarehe ya mwisho', 'tarehe', 'when to pay', 'lini kulipa', 'muda', 'expiry', 'due'])) {
                return 'deadlines';
            }
            // completion / fully paid
            if ($this->matches($t, ['fully paid', 'complete', 'imekamilika', 'all paid', 'completed', 'paid everything', 'ameshalipa', 'finished paying'])) {
                return 'completion';
            }
            // invoice / download
            if ($this->matches($t, ['invoice', 'ankara', 'download', 'print', 'chapisha', 'cheti', 'statement', 'taarifa', 'get invoice', 'fee invoice'])) {
                return 'invoice_download';
            }
            // contact school
            if ($this->matches($t, ['contact', 'wasiliana', 'phone', 'simu', 'whatsapp', 'call', 'pigia', 'email', 'office', 'reach', 'where is'])) {
                return 'contact_school';
            }
            // portal help
            if ($this->matches($t, ['how to use', 'jinsi ya kutumia', 'help', 'msaada', 'guide', 'portal', 'navigate', 'where', 'find', 'wapi'])) {
                return 'portal_help';
            }
        }

        // ── Headmaster intents ──────────────────────────────────────────────
        if ($audience === 'headmaster') {
            if ($this->matches($t, ['today', 'leo', 'today collection', 'makusanyo ya leo', 'collected today'])) {
                return 'today_collections';
            }
            if ($this->matches($t, ['this week', 'wiki hii', 'week collection', 'weekly'])) {
                return 'week_collections';
            }
            if ($this->matches($t, ['outstanding', 'baki', 'deni', 'total outstanding', 'not yet paid', 'hajalipwa'])) {
                return 'outstanding';
            }
            if ($this->matches($t, ['collection rate', 'kiwango', 'percentage', 'asilimia', 'rate', '%'])) {
                return 'collection_rate';
            }
            if ($this->matches($t, ['students', 'wanafunzi', 'how many', 'wangapi', 'active students', 'enrolled'])) {
                return 'active_students';
            }
            if ($this->matches($t, ['lowest', 'worst', 'overdue class', 'darasa', 'which class', 'class with', 'behind'])) {
                return 'top_overdue_class';
            }
        }

        // ── Accountant intents ──────────────────────────────────────────────
        if ($audience === 'accountant') {
            if ($this->matches($t, ['today', 'leo', 'today collection', 'makusanyo ya leo'])) {
                return 'today_collections';
            }
            if ($this->matches($t, ['this week', 'wiki hii', 'week', 'weekly'])) {
                return 'week_collections';
            }
            if ($this->matches($t, ['this month', 'mwezi huu', 'month', 'monthly'])) {
                return 'month_collections';
            }
            if ($this->matches($t, ['outstanding', 'baki', 'deni', 'not paid'])) {
                return 'outstanding';
            }
            if ($this->matches($t, ['record', 'andika', 'how to record', 'jinsi ya kuandika', 'enter payment', 'add payment', 'lipa'])) {
                return 'record_fee';
            }
            if ($this->matches($t, ['overdue', 'students owing', 'how many students', 'wangapi', 'balance students'])) {
                return 'overdue_students';
            }
        }

        return null;
    }

    /**
     * Check whether the text contains any of the given keywords.
     *
     * @param list<string> $keywords
     */
    protected function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }
        return false;
    }

    protected function noMatchReply(string $audience): string
    {
        $tip = match ($audience) {
            'parent'     => "Try asking things like:\n• \"What is my child's balance?\"\n• \"Which fees are unpaid?\"\n• \"Show recent payments\"\n• \"Niambie baki ya ada\" (Swahili)",
            'headmaster' => "Try: \"How much was collected today?\", \"Total outstanding fees\", or \"Which class has the lowest collection?\"",
            default      => "Try: \"How much was collected today?\", \"Total outstanding fees\", or \"How many students have balances?\"",
        };

        return "I didn't quite understand that. " . $tip . "\n\nOr choose one of the quick questions below.";
    }

    /**
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    protected function answerParent(string $intentId, int $studentId): array
    {
        $student  = Student::with('schoolClass')->findOrFail($studentId);
        $settings = SchoolSetting::getSettings();

        $row = (object) DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $studentId)
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
            ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) as outstanding')
            ->first();

        $expected    = (float) ($row->expected_net ?? 0);
        $collected   = (float) ($row->collected ?? 0);
        $outstanding = (float) ($row->outstanding ?? 0);
        $className   = $student->schoolClass->name ?? $student->class ?? '—';

        return match ($intentId) {

            'balance' => [
                'reply' => "**{$student->name}** ({$student->student_reg_no}, {$className})\n\n"
                    . 'Expected (net): **' . $this->fmt($expected) . "**\n"
                    . 'Collected: **' . $this->fmt($collected) . "**\n"
                    . 'Outstanding: **' . $this->fmt($outstanding) . '**',
                'links' => [['label' => 'View fees', 'url' => route('parent.fees')]],
            ],

            'unpaid_fees' => $this->parentUnpaidFees($studentId, $student),

            'collected' => [
                'reply' => 'Total collected for **' . $student->name . '**: **' . $this->fmt($collected) . '** '
                    . 'of **' . $this->fmt($expected) . '** expected.',
            ],

            'recent_payments' => $this->parentRecentPayments($student),

            'fee_structure' => $this->parentFeeStructure($studentId, $student),

            'deadlines' => $this->parentDeadlines($studentId, $student),

            'completion' => $this->parentCompletion($studentId, $student),

            'invoice_download' => [
                'reply' => "To get your invoice for **{$student->name}**:\n\n"
                    . "1. Click **Invoices** in the menu above\n"
                    . "2. Select the fee item or academic year\n"
                    . "3. Click **Download PDF** to save or print\n\n"
                    . "_Invoices show what has been charged and paid._",
                'links' => [['label' => 'Invoices', 'url' => route('parent.invoices')]],
            ],

            'contact_school' => [
                'reply' => '**' . $settings->school_name . "**\n\n"
                    . 'Phone: ' . ($settings->phone ?: '—') . "\n"
                    . 'Email: ' . ($settings->email ?: '—') . "\n"
                    . 'WhatsApp: ' . ($settings->office_whatsapp_number ?: 'Not set — ask the school') . "\n\n"
                    . ($settings->parent_messenger_pin
                        ? 'You can also WhatsApp/SMS the school number with your registration number for instant balance checks.'
                        : 'The school can enable a parent messenger PIN for WhatsApp/SMS self-service queries.'),
            ],

            'portal_help' => [
                'reply' => "The parent portal gives you access to:\n"
                    . "• **Fees & Statements** — current balance and payment history\n"
                    . "• **Invoices** — printable PDF invoices per fee item\n"
                    . "• **Messages** — notices from the school\n\n"
                    . 'You signed in with your child\'s registration number. All data shown is for **' . $student->name . '**.',
                'links' => [
                    ['label' => 'Fees', 'url' => route('parent.fees')],
                    ['label' => 'Invoices', 'url' => route('parent.invoices')],
                ],
            ],

            default => [
                'reply' => 'Choose a question from the list or type what you need — I\'ll do my best to help.',
            ],
        };
    }

    /**
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    protected function parentUnpaidFees(int $studentId, Student $student): array
    {
        $items = DB::table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $studentId)
            ->selectRaw('p.name, ps.sales, ps.credit, ps.deadline, COALESCE(sch.forgiven_amount, 0) as forgiven')
            ->get()
            ->filter(fn ($r) => ($r->sales - $r->forgiven - $r->credit) > 0);

        if ($items->isEmpty()) {
            return ['reply' => '**' . $student->name . '** has no unpaid fee items. All fees are settled!'];
        }

        $lines = $items->map(function ($r) {
            $owed     = $r->sales - $r->forgiven - $r->credit;
            $deadline = $r->deadline ? Carbon::parse($r->deadline)->format('d M Y') : 'No deadline set';
            return '• **' . $r->name . '**: ' . $this->fmt($owed) . ' (due: ' . $deadline . ')';
        })->implode("\n");

        return [
            'reply'  => "Unpaid fee items for **{$student->name}**:\n\n" . $lines,
            'links'  => [['label' => 'View fees', 'url' => route('parent.fees')]],
        ];
    }

    /**
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    protected function parentFeeStructure(int $studentId, Student $student): array
    {
        $items = DB::table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $studentId)
            ->selectRaw('p.name, ps.sales, ps.credit, COALESCE(sch.forgiven_amount, 0) as forgiven')
            ->get();

        if ($items->isEmpty()) {
            return ['reply' => 'No fees have been assigned to **' . $student->name . '** yet.'];
        }

        $lines = $items->map(function ($r) {
            $net    = max(0.0, $r->sales - $r->forgiven);
            $status = $r->credit >= $net && $net > 0 ? '✅' : ($r->credit > 0 ? '⚡' : '⬜');
            return $status . ' **' . $r->name . '**: ' . $this->fmt($net)
                . ' (paid: ' . $this->fmt((float) $r->credit) . ')';
        })->implode("\n");

        return [
            'reply'  => "All fees assigned to **{$student->name}**:\n\n" . $lines
                . "\n\n✅ = fully paid  ⚡ = partially paid  ⬜ = not started",
            'links'  => [['label' => 'View fees', 'url' => route('parent.fees')]],
        ];
    }

    /**
     * @return array{reply: string}
     */
    protected function parentDeadlines(int $studentId, Student $student): array
    {
        $items = DB::table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->where('ps.student_id', $studentId)
            ->whereNotNull('ps.deadline')
            ->selectRaw('p.name, ps.sales, ps.credit, ps.deadline')
            ->orderBy('ps.deadline')
            ->get();

        if ($items->isEmpty()) {
            return ['reply' => 'No deadlines have been set for **' . $student->name . '**\'s fees yet. Contact the school for more information.'];
        }

        $now   = now();
        $lines = $items->map(function ($r) use ($now) {
            $date      = Carbon::parse($r->deadline);
            $isPaid    = $r->credit >= $r->sales && $r->sales > 0;
            $isOverdue = !$isPaid && $date->lt($now);
            $daysLeft  = (int) abs($now->diffInDays($date));

            $tag = $isPaid ? '✅ Paid' : ($isOverdue ? '🔴 Overdue ' . $daysLeft . 'd ago' : '🟡 ' . $daysLeft . ' days left');
            return '• **' . $r->name . '** — ' . $date->format('d M Y') . ' · ' . $tag;
        })->implode("\n");

        return ['reply' => "Fee deadlines for **{$student->name}**:\n\n" . $lines];
    }

    /**
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    protected function answerStaff(string $intentId, bool $readOnly): array
    {
        $now        = now();
        $todayFrom  = $now->copy()->startOfDay();
        $todayTo    = $now->copy()->endOfDay();
        $weekFrom   = $now->copy()->startOfWeek();
        $weekTo     = $now->copy()->endOfWeek();
        $monthFrom  = $now->copy()->startOfMonth();
        $monthTo    = $now->copy()->endOfMonth();

        $outstanding = (float) DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) as outstanding')
            ->value('outstanding');

        $expectedNet = (float) DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected')
            ->value('expected');

        $collectedTotal = (float) DB::table('particular_student')->sum('credit');
        $rate = $expectedNet > 0 ? round(($collectedTotal / $expectedNet) * 100, 1) : 0;

        return match ($intentId) {

            'today_collections' => [
                'reply' => 'Fee collections **today**: **' . $this->fmt($this->feeCollectionsBetween($todayFrom, $todayTo)) . '**.',
            ],

            'week_collections' => [
                'reply' => 'Fee collections **this week**: **' . $this->fmt($this->feeCollectionsBetween($weekFrom, $weekTo)) . '**.',
            ],

            'month_collections' => [
                'reply' => 'Fee collections **this month**: **' . $this->fmt($this->feeCollectionsBetween($monthFrom, $monthTo)) . '**.',
            ],

            'outstanding' => [
                'reply' => 'Total **outstanding** fees (net of scholarships): **' . $this->fmt($outstanding) . '**.',
                'links' => $readOnly
                    ? [['label' => 'Overdue report', 'url' => route('headmaster.overdue')]]
                    : [['label' => 'Overdue module', 'url' => route('accountant.overdue')]],
            ],

            'collection_rate' => [
                'reply' => 'Overall collection rate: **' . $rate . '%** '
                    . '(' . $this->fmt($collectedTotal) . ' collected of ' . $this->fmt($expectedNet) . ' expected).',
            ],

            'active_students' => [
                'reply' => '**' . Student::where('status', 'active')->count() . '** active students enrolled.',
            ],

            'top_overdue_class' => $this->lowestCollectionClassReply(),

            'record_fee' => [
                'reply' => "To record a fee payment:\n"
                    . "1. Open **Record fee** (Fee entry)\n"
                    . "2. Search the student by name or registration number\n"
                    . "3. Select the fee particular and book\n"
                    . "4. Enter the amount and save the receipt",
                'links' => [['label' => 'Record fee', 'url' => route('accountant.fee-entry')]],
            ],

            'overdue_students' => [
                'reply' => '**' . $this->studentsWithBalanceCount() . '** students have an outstanding balance on their fee assignments.',
                'links' => $readOnly
                    ? [['label' => 'Overdue', 'url' => route('headmaster.overdue')]]
                    : [['label' => 'Overdue', 'url' => route('accountant.overdue')]],
            ],

            default => [
                'reply' => 'Pick a guided question below or type your question to get an instant answer from your school data.',
            ],
        };
    }

    protected function feeCollectionsBetween(Carbon $from, Carbon $to): float
    {
        return (float) Voucher::whereBetween('date', [$from, $to])
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('student_id')
            ->whereNotNull('particular_id')
            ->sum('debit');
    }

    protected function studentsWithBalanceCount(): int
    {
        return (int) DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->select('ps.student_id')
            ->groupBy('ps.student_id')
            ->havingRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) > 0')
            ->get()
            ->count();
    }

    /**
     * @return array{reply: string}
     */
    protected function lowestCollectionClassReply(): array
    {
        $worstName      = null;
        $worstRate      = 101.0;
        $worstCollected = 0.0;
        $worstExpected  = 0.0;

        foreach (SchoolClass::where('is_active', true)->orderBy('display_order')->get() as $class) {
            $studentIds = $class->students()->where('status', 'active')->pluck('id')->all();
            if ($studentIds === []) {
                continue;
            }

            $row = DB::table('particular_student as ps')
                ->leftJoin('scholarships as sch', function ($join) {
                    $join->on('sch.student_id', '=', 'ps.student_id')
                        ->on('sch.particular_id', '=', 'ps.particular_id')
                        ->where('sch.is_active', '=', 1)
                        ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
                })
                ->whereIn('ps.student_id', $studentIds)
                ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
                ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected')
                ->first();

            $expected = (float) ($row->expected ?? 0);
            if ($expected <= 0) {
                continue;
            }

            $collected = (float) ($row->collected ?? 0);
            $rate      = ($collected / $expected) * 100;

            if ($rate < $worstRate) {
                $worstRate      = $rate;
                $worstName      = $class->name;
                $worstCollected = $collected;
                $worstExpected  = $expected;
            }
        }

        if ($worstName === null) {
            return ['reply' => 'No class data available yet. Assign students to classes first.'];
        }

        return [
            'reply' => 'Lowest collection by class: **' . $worstName . '** at **' . round($worstRate, 1) . '%** '
                . '(' . $this->fmt($worstCollected) . ' / ' . $this->fmt($worstExpected) . ').',
        ];
    }

    /**
     * @return array{reply: string, links?: list<array{label: string, url: string}>}
     */
    protected function parentRecentPayments(Student $student): array
    {
        $rows = Voucher::where('student_id', $student->id)
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('particular_id')
            ->with('particular')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return ['reply' => 'No fee receipts found yet for **' . $student->name . '**.'];
        }

        $lines = $rows->map(function ($v) {
            $amount    = max((float) $v->debit, (float) $v->credit);
            $dateLabel = $v->date instanceof \Carbon\CarbonInterface
                ? $v->date->format('d M Y')
                : Carbon::parse($v->date)->format('d M Y');
            return '• ' . $dateLabel . ' — ' . ($v->particular->name ?? 'Fee')
                . ': **' . $this->fmt($amount) . '**';
        })->implode("\n");

        return [
            'reply' => "Recent payments for **{$student->name}**:\n\n" . $lines,
            'links' => [['label' => 'Full statement', 'url' => route('parent.fees')]],
        ];
    }

    /**
     * @return array{reply: string}
     */
    protected function parentCompletion(int $studentId, Student $student): array
    {
        $assignments = DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $studentId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0) > 0 AND ps.credit >= GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0) THEN 1 ELSE 0 END) as done')
            ->first();

        $total = (int) ($assignments->total ?? 0);
        $done  = (int) ($assignments->done ?? 0);

        if ($total === 0) {
            return ['reply' => '**' . $student->name . '** has no fee items assigned yet.'];
        }

        $allPaid = $done >= $total;

        return [
            'reply' => ($allPaid
                ? '✅ **All fee items are fully paid** for ' . $student->name . " ({$done}/{$total} assignments)."
                : '**' . $student->name . '** has completed **' . $done . '/' . $total . '** fee assignments. Some items still have a balance.'),
        ];
    }

    /**
     * Parent SMS/WhatsApp messenger (school office number + PIN).
     *
     * @return array{reply: string}
     */
    public function messengerReply(string $phone, string $pin, string $message): array
    {
        $settings      = SchoolSetting::getSettings();
        $normalizedPin = trim($pin);
        $schoolPin     = (string) ($settings->parent_messenger_pin ?? '');

        if ($schoolPin === '' || ! hash_equals($schoolPin, $normalizedPin)) {
            return ['reply' => 'Invalid school access code. Contact the school office for the correct PIN.'];
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($digits) < 9) {
            return ['reply' => 'Invalid phone number.'];
        }

        $student = Student::where('status', 'active')
            ->where(function ($q) use ($phone, $digits) {
                $q->where('parent_phone_1', $phone)
                    ->orWhere('parent_phone_2', $phone)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(parent_phone_1, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $digits])
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(parent_phone_2, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $digits]);
            })
            ->first();

        if (! $student) {
            return ['reply' => 'No student is linked to this phone number. Ask the school to update your contact number.'];
        }

        $text   = strtolower(trim($message));
        $intent = match (true) {
            $this->matches($text, ['balance', 'baki', 'deni', 'outstanding', 'inabaki'])           => 'balance',
            $this->matches($text, ['unpaid', 'hakulipa', 'hajalipwa', 'not paid', 'ada gani'])     => 'unpaid_fees',
            $this->matches($text, ['paid', 'lipa', 'collected', 'kimelipwa', 'jumla'])             => 'collected',
            $this->matches($text, ['receipt', 'risiti', 'recent', 'malipo ya hivi', 'alilipa'])    => 'recent_payments',
            $this->matches($text, ['deadline', 'tarehe', 'due', 'mwisho', 'lini'])                => 'deadlines',
            default                                                                                 => 'balance',
        };

        $answer = $this->answerParent($intent, $student->id);

        return [
            'reply' => preg_replace('/\*\*(.*?)\*\*/', '$1', $answer['reply']),
        ];
    }
}
