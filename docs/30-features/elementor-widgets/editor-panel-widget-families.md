# Elementor Editor Panel Widget Families

## Scope
This document explains the current editor-only color and family system used for Blackwork widget cards inside the Elementor panel.

It documents the real runtime implemented by:
- `assets/js/bw-elementor-widget-panel.js`
- `assets/css/bw-elementor-widget-panel.css`
- `blackwork-core-plugin.php` via `elementor/editor/after_enqueue_scripts`

This is an editor-surface concern only.
It does not affect frontend widget rendering.

## Objective
The panel-family system gives Blackwork widgets a recognizable visual grouping inside the Elementor editor by applying:
- family-specific background colors
- white icon/text styling
- explicit hiding of removed/deprecated widget entries

The current system is now based on a deeper classification pass rather than a single title-prefix rule.

## Asset authority
- CSS authority:
  - `assets/css/bw-elementor-widget-panel.css`
- JS authority:
  - `assets/js/bw-elementor-widget-panel.js`
- Enqueue hook:
  - `elementor/editor/after_enqueue_scripts`
- Bootstrap location:
  - `blackwork-core-plugin.php`

This means the feature is loaded only in the Elementor editor panel, not on the storefront.

## Current runtime model
The system works in two layers:

1. JS classification layer
- scans Elementor panel cards
- reads visible title and widget runtime type
- determines whether the card is a Blackwork widget
- assigns a family class
- hides explicitly removed widgets

2. CSS styling layer
- styles cards once a family class has been attached
- applies family background colors
- forces icon and title text to white
- keeps the wrapper transparent so the family color lives on the outer card shell

## Current family classes
- `bw-family-ui`
  - Blackwork UI / neutral black family
- `bw-family-ui-ps`
  - dedicated presentation-slide family
  - currently green
- `bw-family-sp`
  - single-product / WooCommerce purple family
- `bw-family-deprecated`
  - deprecated/removed family

All styled cards also receive:
- `bw-editor-widget-card`

## Current classification rules
The classification system is now layered in this order:

1. Removed widget check
- slug-based:
  - `bw-add-to-cart`
  - `bw-add-to-cart-variation`
  - `bw-wallpost`
- title-based:
  - `DEPRECATED - BW Add to Cart`
  - `DEPRECATED - BW Add To Cart Variation`
  - `DEPRECATED - BW WallPost`

If matched, the card is hidden from the panel.

2. Slug-first family mapping
- `bw-title-product` -> `bw-family-sp`
- `bw-reviews` -> `bw-family-sp`
- `bw-go-to-app` -> `bw-family-ui`
- `bw-newsletter-subscription` -> `bw-family-ui`
- `bw-product-grid` -> `bw-family-ui`
- `bw-presentation-slide` -> `bw-family-ui-ps`

This slug map is the key refactor point.
It allows exact per-widget color assignment without depending only on visible editor titles.

3. Title-prefix fallback
- `DEPRECATED - ...` -> deprecated family
- `BW-UI ...` -> UI family
- `BW-SP ...` -> single-product family

4. Blackwork recognition gate
The system treats a card as Blackwork when at least one of these is true:
- widget type starts with `bw-`
- widget type starts with `bw_`
- title is exactly:
  - `BW Reviews`
  - `BW Title Product`
- title starts with:
  - `BW-UI `
  - `BW-SP `
  - `DEPRECATED -`

The explicit title exceptions for:
- `BW Reviews`
- `BW Title Product`

exist because those cards intentionally do not rely on a `BW-SP` title prefix, but still need the purple family treatment.

## Why the refactor matters
Older panel-color logic relied too heavily on visible naming conventions.
That becomes fragile when:
- a widget has a custom visible title without family prefix
- a widget should share a color family but keep a shorter title
- a widget needs a one-off family assignment

The current implementation fixes that by separating:
- widget recognition
- widget-to-family mapping
- visual skin application

This is the important architectural improvement:
- classification is now a real system
- colors are no longer inferred only from naming style

## Current family colors
As implemented in CSS:

- `bw-family-ui`
  - background: `#111111`
  - border: `#1f1f1f`

- `bw-family-ui-ps`
  - background: `#2d7d32`
  - border: `#388e3c`

- `bw-family-sp`
  - background: `#6b3fd6`
  - border: `#7c58db`

- `bw-family-deprecated`
  - background: `#2f3238`
  - border: `#4a4f57`

Text and icon color:
- white for all active family cards
- softer neutral text for deprecated family

## DOM behavior
The panel script is defensive:
- runs on `document.ready`
- runs again on Elementor init hooks
- reruns on panel keyup/click
- uses a `MutationObserver` on `.elementor-panel`
- schedules rescans with short delayed passes

This is intentional because Elementor rebuilds panel nodes dynamically.

The current behavior is not a one-shot enhancement.
It is a persistent editor observer model.

## Practical editor consequences
- `BW Product Grid` is treated as a UI-family card, not as a Woo purple card
- `BW Reviews` is forced into the purple SP family via slug/title exception
- `BW Title Product` is forced into the purple SP family via slug/title exception
- `BW-UI Presentation Slider` is isolated into its own green family
- removed widgets do not remain as visible stale choices in the panel

## Governance rule
When a new widget needs a non-default editor family color:
- prefer slug-first mapping in `SLUG_FAMILY_MAP`
- do not rely only on title naming conventions
- add explicit title exceptions only when visible naming intentionally breaks the family prefix model

This keeps the system predictable.

## Recommended future maintenance rule
Any future change to widget panel colors should be evaluated in three places together:
- Blackwork recognition gate
- slug/title family mapping
- CSS family palette

Changing only one of the three layers is likely to create apparent “color bugs” in the Elementor editor.
