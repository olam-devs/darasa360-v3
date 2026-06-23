"""Convert standalone superadmin school views to layouts.superadmin."""
from pathlib import Path
import re

VIEWS = Path(__file__).resolve().parents[1] / "resources" / "views" / "superadmin" / "schools"

CONFIG = {
    "create.blade.php": ("Create school — Super Admin", "Create school", 33),
    "edit.blade.php": ("Edit school — Super Admin", "Edit school", 33),
    "show.blade.php": ("School details — Super Admin", "School details", 34),
}


def convert(name: str, title: str, nav: str, content_start: int) -> None:
    path = VIEWS / name
    lines = path.read_text(encoding="utf-8").splitlines(keepends=True)
    if lines[0].strip().startswith("@extends"):
        print(f"skip: {name}")
        return

    body = "".join(lines[content_start - 1 :])
    # strip trailing html/body and scripts for show - keep scripts in @push
    script = ""
    if name == "show.blade.php":
        sm = re.search(r"(?ms)(<script>.*?</script>)\s*</body>\s*</html>\s*$", body)
        if sm:
            script = sm.group(1)
            body = body[: sm.start()].rstrip()
        else:
            body = re.sub(r"\s*</body>\s*</html>\s*$", "", body).rstrip()
    else:
        body = re.sub(r"\s*</body>\s*</html>\s*$", "", body).rstrip()

    # remove duplicate flash blocks (layout handles session flash)
    body = re.sub(
        r"@if\(session\('success'\)\).*?@endif\s*",
        "",
        body,
        count=1,
        flags=re.S,
    )
    body = re.sub(
        r"@if\(session\('error'\)\).*?@endif\s*",
        "",
        body,
        count=1,
        flags=re.S,
    )
    body = re.sub(
        r"@if\(\$errors->any\(\)\).*?@endif\s*",
        "",
        body,
        count=1,
        flags=re.S,
    )

    out = f"""@extends('layouts.superadmin')

@section('title', '{title}')
@section('nav_title', '{nav}')

@section('content')
{body.strip()}
@endsection
"""
    if script:
        inner = re.sub(r"^\s*<script>\s*", "", script.strip(), flags=re.I)
        inner = re.sub(r"\s*</script>\s*$", "", inner, flags=re.I)
        out += f"""
@push('scripts')
    <script>
{inner}
    </script>
@endpush
"""

    path.write_text(out, encoding="utf-8")
    print(f"converted: {name}")


for fname, (title, nav, start) in CONFIG.items():
    convert(fname, title, nav, start)
