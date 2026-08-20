---
name: update-guidebooks
description: Update the Darasa Finance accountant guidebooks when new features are added. Use when the user says "update the guidebook", "add X to the guidebook", or after shipping a new feature that accountants interact with.
---

# Guidebook update procedure (Darasa Finance)

Two guidebooks live as Blade views + PDF templates in the Finance app. Both have an online view (in-browser) and a downloadable PDF version. Keep all four files in sync when updating.

## File locations

| File | Purpose |
|---|---|
| `finance/resources/views/admin/accountant/guidebooks/normal.blade.php` | Normal accountant — online view (Google Fonts, full CSS) |
| `finance/resources/views/admin/accountant/guidebooks/normal-pdf.blade.php` | Normal accountant — dompdf template (DejaVu Sans, no external fonts) |
| `finance/resources/views/admin/accountant/guidebooks/main.blade.php` | Main accountant — online view |
| `finance/resources/views/admin/accountant/guidebooks/main-pdf.blade.php` | Main accountant — dompdf template |

Routes:
- `GET /accountant/guidebook` → online view (all logged-in accountants)
- `GET /accountant/guidebook/pdf` → PDF download (all)
- `GET /accountant/guidebook-main` → main accountant online view (is.main.accountant middleware)
- `GET /accountant/guidebook-main/pdf` → main accountant PDF download

## Who gets which guidebook

| Feature | Normal guidebook | Main guidebook |
|---|---|---|
| Login, dashboard, fee entry, receipts | ✔ | ✔ (brief, "same as normal") |
| Overdue, advance payments, assign advance | ✔ | ✔ |
| Submit expenses, inventory, SMS | ✔ | ✔ |
| Download reports, Excel export | ✔ | ✔ |
| **Approve/deny expenses, reverse vouchers** | ✘ | ✔ |
| **Manage students, classes, particulars** | ✘ | ✔ |
| **Payroll, reconciliation, settings, team permissions** | ✘ | ✔ |
| **Activity logs** | ✘ | ✔ |

Add a new feature to the normal guidebook if normal accountants interact with it. Add to main guidebook if it's gated by `is_main_accountant`. Add to both if both roles use it.

## Section structure

Each section follows this pattern in the Blade files:

```html
{{-- Section Title --}}
<div class="gb-sec" id="sN">
  <div class="gb-sh"><div class="gb-sn">NN</div><div>
    <h2>Section Title</h2><div class="sd">One-line description.</div>
  </div></div>
  <div class="gb-body">
    <!-- optional sub-section -->
    <div class="gb-sub">Sub-section label</div>
    <div class="gb-steps">
      <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c">
        <p>Step text. Use <span class="btn-lbl">Button Name</span> for UI buttons.</p>
      </div></div>
      <!-- more steps -->
    </div>
    <!-- optional callout -->
    <div class="callout tip"><span class="callout-icon">💡</span><div>Tip text.</div></div>
    <div class="callout warn"><span class="callout-icon">⚠</span><div>Warning text.</div></div>
  </div>
</div>
```

For the PDF templates (`-pdf.blade.php`), the same content uses `<div class="sec">`, `<div class="sec-hdr">`, `<div class="step">`, etc. (shorter class names, dompdf-compatible). Mirror every change across both the `.blade.php` and its `-pdf.blade.php` counterpart.

## Update checklist

When a new feature ships:

1. **Identify the section** — does this fit in an existing section (e.g. a new report type goes in the Reports section) or does it need its own numbered section?

2. **Update the correct guidebook(s)** — see "who gets which" table above.

3. **Update the TOC** — if you added a new section, add it to the `<div class="gb-toc-grid">` block.

4. **Update the PDF template** — mirror every change in the `-pdf.blade.php` file. The PDF template uses simple HTML; no Google Fonts, no Tailwind, no grid/flexbox — use `display: table`/`display: table-cell` for multi-column layouts (dompdf doesn't support flex/grid).

5. **Contact section** — always ensure Section 11 (normal) / Section 13 (main) mentions **devs@olamtec.co.tz** as the help contact, and that "things only the main accountant can do" table in the normal guidebook is up to date if the feature adds any new restricted action.

6. **Commit and deploy** — use the `deploy-to-live` skill after updating the views. Only `view:clear` is needed on the server after a Blade-only change (no migration, no composer update):
   ```bash
   ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/finance.darasa360.co.tz/public_html/finance && php artisan view:clear"
   ```

7. **Update the Artifact** — the guidebook is also published as a Claude Artifact at:
   `https://claude.ai/code/artifact/ee612c5e-9efc-44bc-a91d-869d74f7890b`
   After updating the Blade view, re-publish the scratchpad HTML file to keep the shared artifact current.

## Writing style rules

- **Use "Click [Button]" not "Press [Button]"** — web buttons are clicked.
- **Action-first sentences** — "Click Fee Entry" not "Fee Entry can be clicked".
- **No jargon** — write "the school's finance system" not "the application". Avoid database terms (no "records", "entities", "foreign key").
- **Mention the main accountant limitation** for every restricted action in the normal guide — "only the main accountant can reverse this".
- **Contact line** — every section that could result in an irreversible action must end with a callout directing them to devs@olamtec.co.tz if it goes wrong.
- **Keep steps short** — one action per step. Long multi-part steps get split.
- **UI button labels** use `<span class="btn-lbl">Label</span>` in the online view and `<span class="btn">Label</span>` in the PDF template.
