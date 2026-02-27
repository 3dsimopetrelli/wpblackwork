# Repo Root Hygiene Audit (Current Implementation)

Date: 2026-02-27
Scope: root-level files audit only (no runtime refactor)

## A) Plugin Header Metadata Target

Confirmed main plugin header file:
- `bw-main-elementor-widgets.php`

Confirmed display metadata fields in header block:
- `Plugin Name`
- `Description`
- `Version`
- `Author`

## B) Root File Inventory and Actions

| Root file | Purpose (evidence-based) | Required for runtime? | Recommended action | Notes |
|---|---|---:|---|---|
| `.DS_Store` | macOS Finder metadata file (OS artifact). | No | DELETE | Already covered by `.gitignore` (`.DS_Store`). Remove tracked copy from git index. |
| `AGENTS.md` | Agent workflow rules; explicitly referenced by root `README.md` and `docs/00-overview/README.md`. | No (runtime), Yes (workflow governance) | KEEP | Governance/tooling contract file at repo root is intentional. |
| `CLAUDE.md` | Assistant/tooling guidance; referenced by root `README.md` and `docs/00-overview/README.md`. | No (runtime), Yes (tooling context) | KEEP | Keep until tooling policy is migrated and links updated. |
| `CHANGELOG.md` | Change history; referenced by templates and ops/governance docs. | No (runtime), Yes (release/documentation discipline) | KEEP | Required by maintenance/documentation workflows. |
| `README.md` | Root repository entrypoint; points to docs and root governance docs. | No (runtime), Yes (repo onboarding) | KEEP | Should remain concise and accurate. |
| `checkout-css-fix.patch` | Standalone patch artifact with CSS diff; no runtime loader/reference found. | No | MOVE | Move to `docs/99-archive/root/checkout-css-fix.patch` as historical artifact. |
| `composer.json` | Composer dev tooling config (PHPCS scripts and standards). | No (frontend runtime), Yes (developer lint pipeline) | KEEP | Required for mandatory checks (`composer run lint:main`). |
| `composer.lock` | Locked dependency tree for composer dev tools. | No (frontend runtime), Yes (reproducible dev tooling) | KEEP | Keep with `composer.json` for deterministic tooling. |
| `elementor-smart-header.html` | Legacy standalone instructional/prototype HTML for smart header; no runtime load reference found. | No | MOVE | Move to `docs/99-archive/root/elementor-smart-header.html`. |
| `smart-header.html` | Legacy standalone smart header reference/prototype HTML; no runtime load reference found. | No | MOVE | Move to `docs/99-archive/root/smart-header.html`. |

## C) Safe Git Actions

### DELETE actions

For tracked OS artifact:

```bash
git rm .DS_Store
```

### MOVE actions (conservative archival)

```bash
mkdir -p docs/99-archive/root
git mv checkout-css-fix.patch docs/99-archive/root/checkout-css-fix.patch
git mv elementor-smart-header.html docs/99-archive/root/elementor-smart-header.html
git mv smart-header.html docs/99-archive/root/smart-header.html
```

## D) .gitignore Additions

Current `.gitignore` already contains:
- `.DS_Store`

No mandatory new `.gitignore` entries are required for the audited files.

## E) Runtime Safety Statement

The recommended actions above are metadata/repository hygiene actions only.
They MUST NOT alter runtime behavior because:
- Plugin bootstrap file path/name remains unchanged.
- Plugin folder slug remains unchanged.
- Text domain and internal prefixes remain unchanged.
- No hooks, options, runtime modules, or templates are modified.
