"""Move trailing inline <script> blocks to @push('scripts') for accountant modules."""
from pathlib import Path
import re

VIEWS = Path(__file__).resolve().parents[1] / "resources" / "views"

    FILES = [
    "admin/accountant/modules/ledgers.blade.php",
    "admin/accountant/modules/students.blade.php",
    "admin/accountant/modules/particulars.blade.php",
    "admin/accountant/modules/student-profile.blade.php",
]

HEADERS = {
    "admin/accountant/modules/ledgers.blade.php": (
        "@extends($portalLayout ?? 'layouts.accountant')\n\n"
        "@section('title', 'Ledgers — Darasa Finance')\n"
        "@section('page_title', 'Ledgers')\n"
    ),
    "admin/accountant/modules/students.blade.php": (
        "@extends($portalLayout ?? 'layouts.accountant')\n\n"
        "@section('title', 'Student Management — Darasa Finance')\n"
        "@section('page_title', 'Students')\n"
    ),
    "admin/accountant/modules/particulars.blade.php": (
        "@extends($portalLayout ?? 'layouts.accountant')\n\n"
        "@section('title', 'Particulars — Darasa Finance')\n"
        "@section('page_title', 'Particulars')\n"
    ),
    "admin/accountant/modules/student-profile.blade.php": (
        "@extends($portalLayout ?? 'layouts.accountant')\n\n"
        "@section('title', 'Student profile — Darasa Finance')\n"
        "@section('page_title', 'Student profile')\n"
    ),
}


def migrate(rel: str) -> None:
    path = VIEWS / rel
    text = path.read_text(encoding="utf-8")
    if "@push('scripts')" in text:
        print(f"skip (already migrated): {rel}")
        return

    m = re.search(r"(?ms)(@section\('content'\).*?)(\n\s*<script>.*?</script>)(\s*@endsection\s*)$", text)
    if not m:
        print(f"no trailing script: {rel}")
        return

    content_block, script_block, end = m.group(1), m.group(2), m.group(3)
    inner = re.sub(r"^\s*<script>\s*", "", script_block.strip(), flags=re.I)
    inner = re.sub(r"\s*</script>\s*$", "", inner, flags=re.I)
    inner = inner.strip()

    new_text = content_block.rstrip() + "\n@endsection\n\n@push('scripts')\n    <script>\n" + inner + "\n    </script>\n@endpush\n"
    path.write_text(new_text, encoding="utf-8")
    print(f"migrated: {rel}")


for f in FILES:
    migrate(f)
