"""
Migrate legacy accountant module blades to layouts.accountant.
Strips <!DOCTYPE ... </nav>, wraps content in @section, moves trailing <script> to @push('scripts').
"""
from __future__ import annotations

import re
from pathlib import Path

VIEWS = Path(__file__).resolve().parents[1] / "resources" / "views"

# (relative path from views/, title, page_title, extra @push head lines or None)
MIGRATIONS: list[tuple[str, str, str, str | None]] = [
    ("admin/accountant/modules/books.blade.php", "Books — Darasa Finance", "Books", None),
    ("admin/accountant/modules/reconciliation.blade.php", "Reconciliation — Darasa Finance", "Reconciliation", None),
    ("admin/accountant/modules/advance-payments.blade.php", "Advance payments — Darasa Finance", "Advance payments", None),
    ("admin/accountant/modules/overdue.blade.php", "Overdue — Darasa Finance", "Overdue", None),
    ("admin/accountant/modules/particular-ledger.blade.php", "Particular ledger — Darasa Finance", "Particular ledger", None),
    ("admin/accountant/modules/payroll.blade.php", "Payroll — Darasa Finance", "Payroll", None),
    ("admin/accountant/modules/classes.blade.php", "Classes — Darasa Finance", "Classes", None),
    ("admin/accountant/modules/suspense.blade.php", "Suspense — Darasa Finance", "Suspense", None),
    ("admin/accountant/modules/bank-api.blade.php", "Bank integration — Darasa Finance", "Bank integration", None),
    (
        "admin/accountant/modules/expenses.blade.php",
        "Expenses — Darasa Finance",
        "Expenses",
        '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">\n    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>\n',
    ),
    ("admin/accountant/modules/student-promotion.blade.php", "Student promotion — Darasa Finance", "Student promotion", None),
    ("admin/accountant/modules/sms.blade.php", "SMS — Darasa Finance", "SMS", None),
    ("admin/accountant/modules/phone-numbers.blade.php", "Phone numbers — Darasa Finance", "Phone numbers", None),
    ("admin/accountant/modules/sms-logs.blade.php", "SMS logs — Darasa Finance", "SMS logs", None),
]


def extract_head_extras(full: str) -> str:
    """Pull optional flatpickr (or other) from old <head>, skip tailwind/axios/meta/title/charset/viewport."""
    m = re.search(r"<head>(.*?)</head>", full, re.DOTALL | re.IGNORECASE)
    if not m:
        return ""
    head = m.group(1)
    lines_out = []
    for line in head.splitlines():
        s = line.strip()
        if not s:
            continue
        low = s.lower()
        if any(
            x in low
            for x in (
                "tailwindcss",
                "axios",
                "csrf-token",
                "<title",
                "charset",
                "viewport",
                "<meta",
            )
        ):
            continue
        lines_out.append(line)
    return "\n".join(lines_out).strip()


def split_trailing_script(body_chunk: str) -> tuple[str, str | None]:
    """body_chunk is everything after </nav> up to (excluding) </body>. Returns (html_content, script_inner_or_none)."""
    body_chunk = body_chunk.rstrip()
    # Strip closing wrappers before script if file ends modals+script
    m = re.search(r"(?ms)(.*)(\n\s*<script>.*?</script>)\s*$", body_chunk)
    if not m:
        return body_chunk.strip(), None
    content, script_block = m.group(1).strip(), m.group(2).strip()
    # Remove optional module scripts comment
    content = re.sub(r"\n\s*<!--\s*Module Scripts\s*-->\s*\n?$", "\n", content, flags=re.IGNORECASE)
    inner = re.sub(r"^\s*<script>\s*", "", script_block, flags=re.IGNORECASE)
    inner = re.sub(r"\s*</script>\s*$", "", inner, flags=re.IGNORECASE)
    return content.strip(), inner.strip()


def migrate_one(rel: str, title: str, page_title: str, head_override: str | None) -> None:
    path = VIEWS / rel
    text = path.read_text(encoding="utf-8")
    if "@extends('layouts.accountant')" in text:
        print("skip (already migrated)", rel)
        return

    m = re.search(r"<!DOCTYPE html>.*?</nav>\s*", text, re.DOTALL | re.IGNORECASE)
    if not m:
        raise RuntimeError(f"No </nav> match: {rel}")

    tail = text[m.end() :]
    body_end = tail.lower().rfind("</body>")
    if body_end < 0:
        raise RuntimeError(f"No </body>: {rel}")
    body_chunk = tail[:body_end]

    content, script_inner = split_trailing_script(body_chunk)

    head_extra = head_override if head_override is not None else extract_head_extras(text)
    head_block = ""
    if head_extra:
        head_block = "\n@push('head')\n" + head_extra + "\n@endpush\n"

    content = content.replace('class="container mx-auto', 'class="w-full')
    content = content.replace("class='container mx-auto", "class='w-full")
    content = re.sub(
        r'class="container mx-auto px-4 py-8"', 'class="w-full space-y-6"', content
    )

    out: list[str] = [
        "@extends('layouts.accountant')",
        "",
        f"@section('title', {title!r})",
        f"@section('page_title', {page_title!r})",
        "",
    ]
    if head_block:
        out.append(head_block.rstrip() + "\n")

    out.extend(
        [
            "@section('content')",
            content,
            "@endsection",
            "",
        ]
    )

    if script_inner:
        out.extend(["@push('scripts')", "    <script>", script_inner, "    </script>", "@endpush", ""])

    path.write_text("\n".join(out).replace("\n\n\n\n", "\n\n\n"), encoding="utf-8")
    print("migrated", rel)


def main() -> None:
    for rel, title, page_title, head_ov in MIGRATIONS:
        migrate_one(rel, title, page_title, head_ov)


if __name__ == "__main__":
    main()
