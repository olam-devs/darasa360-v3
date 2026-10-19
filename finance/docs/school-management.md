# School management and accountant targets

The normal `/login` sends accounts with explicit management grants to
`/accountant/school-management`. Other accountant logins keep their existing
destination. The management page has Overview, Quarter analytics, Expense approvals, and
Accountant targets tabs. The management area and all KPI results are private to
the explicitly authorized Ritha management account. Regular accountants have no
KPI page or menu entry. The old `/accountant/my-targets` address rejects regular
accountants and redirects authorized management sessions to the private scorecard.

Apply only the central migration
`database/migrations/central/2026_10_17_000001_create_school_management_and_kpi_tables.php`.
The feature does not migrate or modify tenant financial tables.

`school_management_access` explicitly maps a manager accountant to a school
and an existing authorized main accountant for that school. Source and target
accounts must remain active main accountants and the target must belong to the
specified school. Grants must be provisioned deliberately; matching names or
emails never grants access automatically. School entry uses the authorized
school account and preserves a validated management session. Account settings
restores the original management account before opening Profile. Logout clears
the session. No platform super-admin access is granted.

Financial overview collections use voucher payment dates; approved expenses
use submission transaction dates. Balances and the pending approval queue are
current positions. A receipt must be unvoided and linked to a book to count as
fresh collections; applications of existing advance credit are excluded.

The period selector defaults to the current calendar quarter (Q1 Jan–Mar,
Q2 Apr–Jun, Q3 Jul–Sep, Q4 Oct–Dec). Year/quarter selection also sets the cash-flow
and KPI date range. Custom dates remain available for monthly or other targets;
fee analytics keep the separately displayed year/quarter selection. Previous
cash-flow comparisons use the full previous quarter in quarter mode. An ongoing
quarter is explicitly labelled as incomplete.

Quarter fee analytics read `particular_student.academic_year_id` and `quarter`,
matching the academic-year name within each tenant (IDs are not shared across
schools). Required/paid amounts use assignment sales/credit at the time of
viewing, including payments at other dates and advance allocations. Unpaid is
the sum of positive assignment balances so overpayments cannot hide another
student's debt. These figures are not historical quarter-end snapshots or fresh
cash collections. The Q1–Q4 comparison, school comparison and selected-quarter
particular breakdown reconcile to these assignments. Missing quarters are
labelled No assignments; existing historical quarters are retained. Assignments
without a valid quarter are disclosed separately and never reassigned by reports.

KPIs use receipt entry dates in Africa/Dar_es_Salaam and attribute entries to
`vouchers.created_by`. Targets match exact start/end dates and are stored in
`accountant_kpi_targets`. Unset targets are not failures. Missed targets require
an ended period; timing percentages without receipts show No data. Timing
compares the local entry day with the payment date and the configured day limit.
Target changes and school entry are recorded in the central activity log.

Run `php tests/regression/school_management_kpis.php` for isolated SQLite tests.
Run `php tests/regression/management_quarters.php` for quarter reporting fixtures.
`DARASA_FINANCE_ROOT` may point to an installed app with vendor dependencies and
`DARASA_MANAGEMENT_SOURCE` to a staged implementation. The test replaces both
database connections with fixtures and never writes real financial records.
