# BW-TASK-20260306-14 — Supabase JS SDK Version Pinning

## Scope
- `woocommerce/woocommerce-init.php`
- `docs/tasks/BW-TASK-20260306-14-closure.md`

## Why version pinning is used
- Pinning removes uncontrolled CDN minor/patch drift for a security-sensitive auth/runtime dependency.
- This keeps frontend behavior deterministic across deployments and prevents unexpected breakage from upstream changes.

## Previous behavior
- Supabase SDK loaded from jsDelivr with floating major tag:
  - `https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2`
- This allowed automatic minor/patch updates outside repository change control.

## New pinned version
- Supabase SDK is now pinned to an exact version:
  - `https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.43.4/dist/umd/supabase.min.js`
- Script handle, dependencies, enqueue/register flow, and loading position are unchanged.

## Verification steps
1. Confirm both Supabase enqueue/register paths now use the pinned URL.
2. Run:
   - `php -l woocommerce/woocommerce-init.php`
   - `composer run lint:main`
