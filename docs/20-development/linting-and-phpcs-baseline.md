# Linting and PHPCS Baseline Strategy

## Purpose
`composer run lint:main` must stay green for active development while legacy PHPCS debt is reduced incrementally.

## Current Strategy
Blackwork uses a temporary narrow baseline by excluding only:
- `blackwork-core-plugin.php`

This is configured in:
- `phpcs.xml.dist`

Why this shape:
- `lint:main` stays stable/green for mandatory developer checks.
- legacy debt remains visible via dedicated scripts (`lint:legacy`, `lint:strict`).
- incremental enforcement is done with `lint:changed` on touched PHP files.

## Commands
- Main lint (CI/local default):
  - `composer run lint:main`
- Main alias:
  - `composer run lint`
- Legacy debt visibility (tracked file only):
  - `composer run lint:legacy`
- Changed PHP files gate (incremental workflow):
  - `composer run lint:changed`
- Full strict audit (known to fail on legacy debt until cleanup is done):
  - `composer run lint:strict`

## Governance Rules
1. No new PHPCS violations are allowed in changed PHP files (`lint:changed`).
2. Legacy debt in `blackwork-core-plugin.php` is tracked by `lint:legacy`.
3. Repository-wide debt trend is tracked by `lint:strict`.
4. Baseline scope must not expand without explicit governance approval.

## How to Reduce the Baseline
1. Create a dedicated cleanup task for `blackwork-core-plugin.php`.
2. Fix a bounded subset of violations.
3. Validate with:
   - `composer run lint:legacy`
   - `composer run lint:changed`
   - `composer run lint:strict` (expected to improve over time)
4. Repeat until `lint:legacy` passes.
5. Remove the exclude from `phpcs.xml.dist` once the legacy file is compliant.

## How to Regenerate/Update Baseline
This repository currently uses a narrow file exclusion baseline (not a generated violations snapshot).

To update baseline scope:
1. Edit `phpcs.xml.dist` exclude rules.
2. Document rationale in governance docs.
3. Verify:
   - `lint:main` remains green/stable
   - `lint:changed` still enforces changed files
   - `lint:legacy` still reports legacy debt
