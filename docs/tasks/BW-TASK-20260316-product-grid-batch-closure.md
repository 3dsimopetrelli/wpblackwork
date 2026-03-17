# BW-TASK-20260316 — Product Grid Batch Closure

## Task Identification
- Task ID: `BW-TASK-20260316-PRODUCT-GRID-BATCH`
- Title: Product grid — Elementor hook fallback + reveal animation speed
- Domain: Product Grid / Frontend / Elementor Lifecycle
- Tier classification: Tier 2 (frontend widget, non-payment, non-auth)
- Closure date: `2026-03-16`
- Final task status: `CLOSED`

## Protocol Reference
- Closure executed following: `docs/governance/task-close.md`

## Commit Traceability

| Commit | Message | Files |
|--------|---------|-------|
| `41afb3d1` | Add fallback init for product grid when Elementor frontend hooks fail | `assets/js/bw-product-grid.js` |
| `86c1db13` | Speed up product grid reveal animation on page load | `assets/js/bw-product-grid.js`, `assets/css/bw-product-grid.css` |

## Implementation Summary

### Elementor frontend hook fallback (`41afb3d1`)
`registerElementorHooks()` registra il handler su `elementorFrontend.hooks.addAction(...)`.
In alcune configurazioni il metodo `hooks` non è ancora disponibile al momento dell'esecuzione.

Doppio fallback aggiunto:
1. `elementorFrontend.on('init', ...)` — se `hooks` non è disponibile ma `on` lo è.
2. `initAllGrids()` diretto su `document.ready` — rete di sicurezza finale.

### Reveal animation speed (`86c1db13`)
Riduzione coordinata di CSS e JS per minimizzare il tempo percepito al caricamento:

| Parametro | Prima | Dopo |
|-----------|-------|------|
| CSS `transition: opacity` | `1.8s ease` | `0.45s ease` |
| `baseDelay` (initial) | `80ms` | `40ms` |
| `STAGGER` (scroll/append) | `80ms` | `40ms` |
| `cleanupDelay` (initial) | `1200ms` | `600ms` |
| `cleanupDelay` (scroll) | `2200ms` | `600ms` |

Lo stagger sequenziale (card che appaiono top→left) è mantenuto.
Invariante rispettato: `cleanupDelay (600ms) > CSS duration (450ms)`.

## Runtime Surfaces Touched
- `assets/js/bw-product-grid.js` — `registerElementorHooks()`, `animatePostsStaggered()`, `revealItemsPerViewport()`
- `assets/css/bw-product-grid.css` — `transition: opacity`

Nessun nuovo hook, endpoint AJAX, filtro o rotta admin.

## Acceptance Criteria Verification
- ✅ Product grid si inizializza correttamente anche quando `elementorFrontend.hooks` non è disponibile al timing di esecuzione
- ✅ Griglia da 20 prodotti completamente visibile in ~1.3s (vs ~3.4s precedente)
- ✅ `cleanupDelay` > durata CSS transition (invariante mantenuto)
- ✅ Stagger sequenziale visivamente coerente
- ✅ `prefers-reduced-motion` disabilita la transition (invariante CSS esistente non modificato)

## Regression Surface Verification
- Filtri categoria/sottocategoria/tag: non impattati
- Infinite scroll: stagger append invariato (stesso `STAGGER` applicato in `revealItemsPerViewport`)
- Editor Elementor: `isElementorEditor()` path invariato
- Masonry layout: `initGrid()` e `imagesLoaded` non toccati
- CSS grid layout: non impattato
- Supabase/auth/payment/cart surfaces: non toccati

## Determinism Verification
- Animazione deterministica: ogni item riceve delay = `index * baseDelay` o `i * STAGGER`
- Fallback init: path sequenziale, nessun side effect su doppia inizializzazione (guard `data-bw-initialized` già presente)

## Documentation Alignment Verification
- `docs/30-features/product-grid/product-grid-architecture.md` — sezione 9 aggiunta (9.1 fallback init, 9.2 animation constants)

## Governance Artifact Updates
- Feature documentation updated: ✅

## Rollback Safety
- Entrambi i commit revertabili individualmente
- Nessuna migration DB o option admin coinvolta
- Rollback animazione: revert `86c1db13` ripristina valori precedenti

## Post-Closure Monitoring
- Monitorare product grid su pagina pubblica dopo deploy per verificare inizializzazione corretta
- Durata suggerita: 1 settimana

## Closure Declaration
- Task closure status: `CLOSED`
- Date: `2026-03-16`
