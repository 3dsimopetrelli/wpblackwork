# Product Grid Glass Token

## File modified

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-product-grid.css`

## Variables created

These custom properties were added on:

- `.bw-product-grid-wrapper`

Exact declarations:

```css
.bw-product-grid-wrapper {
  position: relative;
  --bw-fpw-panel-glass-bg: var(--bw-surface-glass-bg, #171717d9);
  --bw-fpw-panel-glass-blur: var(--bw-surface-glass-blur, blur(24px));
  --bw-fpw-panel-glass-border: var(--bw-surface-glass-border, 1px solid rgba(255, 255, 255, 0.1));
  --bw-fpw-panel-glass-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
}
```

## Panels wired to the token

### 1. Desktop visible filter dropdown

Selector:

```css
.bw-product-grid-wrapper[data-responsive-filter-mode="yes"] .bw-fpw-visible-filter__panel
```

Applied properties:

```css
border: var(--bw-fpw-panel-glass-border);
background: var(--bw-fpw-panel-glass-bg);
background-color: var(--bw-fpw-panel-glass-bg);
-webkit-backdrop-filter: var(--bw-fpw-panel-glass-blur);
backdrop-filter: var(--bw-fpw-panel-glass-blur);
box-shadow: var(--bw-fpw-panel-glass-shadow);
```

Additional inner fill applied to:

```css
.bw-product-grid-wrapper[data-responsive-filter-mode="yes"] .bw-fpw-visible-filter__panel-inner
```

```css
background-color: var(--bw-fpw-panel-glass-bg);
```

### 2. Desktop sort dropdown

Selector:

```css
.bw-product-grid-wrapper[data-responsive-filter-mode="yes"] .bw-fpw-sort-menu
```

Applied properties:

```css
border: var(--bw-fpw-panel-glass-border);
background: var(--bw-fpw-panel-glass-bg);
background-color: var(--bw-fpw-panel-glass-bg);
-webkit-backdrop-filter: var(--bw-fpw-panel-glass-blur);
backdrop-filter: var(--bw-fpw-panel-glass-blur);
box-shadow: var(--bw-fpw-panel-glass-shadow);
```

### 3. Mobile filter drawer

Selector:

```css
.bw-product-grid-wrapper[data-responsive-filter-mode="yes"] .bw-fpw-mobile-filter-drawer
```

Applied properties:

```css
border: var(--bw-fpw-panel-glass-border);
background: var(--bw-fpw-panel-glass-bg);
background-color: var(--bw-fpw-panel-glass-bg);
backdrop-filter: var(--bw-fpw-panel-glass-blur);
-webkit-backdrop-filter: var(--bw-fpw-panel-glass-blur);
box-shadow: var(--bw-fpw-panel-glass-shadow);
```

## Important rendering note

The blur issue on the desktop filter dropdown was not only a token/value issue.

The desktop dropdown lives inside:

```css
.bw-product-grid-wrapper[data-responsive-filter-mode="yes"] .bw-fpw-discovery-toolbar
```

That toolbar previously had:

```css
isolation: isolate;
```

This isolated compositing context could prevent `backdrop-filter` from reading the expected backdrop behind the dropdown.

That line was removed from:

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-product-grid.css`

## Current implementation summary

- Token is defined locally on `.bw-product-grid-wrapper`
- Desktop filter panel uses the token
- Desktop sort panel uses the token
- Mobile filter drawer uses the token
- Desktop filter panel inner wrapper also receives the explicit background color
- `isolation: isolate` was removed from the product-grid discovery toolbar to avoid blur/compositing interference

## Check run

```bash
git diff --check -- assets/css/bw-product-grid.css
```

Result:

- OK
