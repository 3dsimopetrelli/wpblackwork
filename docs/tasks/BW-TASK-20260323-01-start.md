# BW-TASK-20260323-01 — Elementor Panel Family-Color Documentation Refresh

## Type
- documentation-only

## Objective
Document the current real implementation of the Elementor editor widget-panel color-family system after the recent refactor of family assignment and custom color handling.

## Scope
- inspect current JS classification logic
- inspect current CSS family styling
- inspect asset enqueue authority
- update architecture/feature docs only
- do not modify runtime code

## Current runtime authority
- `assets/js/bw-elementor-widget-panel.js`
- `assets/css/bw-elementor-widget-panel.css`
- `blackwork-core-plugin.php`

## Focus points
- slug-first family mapping
- explicit exceptions for widgets without visible family prefixes
- distinction between recognition and family assignment
- current active family palette
- panel observer and rescan model
- deprecated widget hiding

## Non-goals
- no runtime refactor
- no color changes
- no widget renaming

## Deliverables
- dedicated feature doc for panel widget families
- architecture/context doc refresh
- widget inventory/readme refresh if needed

## Risk note
This area is editor-only but easy to misunderstand because visual color changes can fail even when CSS is correct if classification gates do not match.
