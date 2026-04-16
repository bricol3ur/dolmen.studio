# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Static marketing site for **Dolmen Studio** — a French freelance web dev studio. Single-page landing site with no build step, no framework, no bundler. Open `index.html` directly in a browser or serve locally.

## Local development

No install or build required. To preview:

```bash
# Any static server works, e.g.:
npx serve .
# or
python -m http.server 8080
```

There are no tests, no linters, and no CI configuration.

## File structure

| File | Role |
|---|---|
| `index.html` | Single-page site — all sections live here |
| `style.css` | All styles, including responsive breakpoints |
| `main.js` | Vanilla JS: footer year, burger nav, scroll animations, active nav highlighting |
| `assets/img/` | Image assets |
| `codebits/` | Standalone HTML prototypes / component explorations (not linked from the main site) |

## Architecture

The site is a single HTML document with seven sections in order: Hero → Crédibilité (stats bar) → Offres → CashPing (product feature) → Réalisations → Méthode → Contact.

**CSS design tokens** (`style.css:14–34`): all colours, fonts, radii and spacing are defined as CSS custom properties on `:root`. Always use these variables — never hardcode values.

- Palette: `--bg` (#F2EFE9) / `--accent` (#2A5C4E) / `--text` (#1A1A1A) / `--surface` (#FFF)
- Type: `--ff-title` (Fraunces, serif) for headings, `--ff-body` (DM Sans) for everything else

**Responsive strategy**: desktop-first with two breakpoints — `900px` (tablet, collapses grids) and `600px` (mobile, activates burger nav via `.nav-toggle` + `.is-open`).

**Scroll animations**: `main.js` adds `.fade-in` to cards/steps at runtime, then an `IntersectionObserver` adds `.visible` on entry. The CSS class is never present in the HTML source. Respects `prefers-reduced-motion`.

**Contact form**: posts to Formspree (`action="https://formspree.io/f/XXXXXXXX"`). The placeholder `XXXXXXXX` must be replaced with the real Formspree endpoint ID before going live.

## Accessibility (RGAA)

RGAA compliance is a core deliverable. Always maintain:
- Skip link (`.skip-link`) pointing to `#main`
- `aria-label` / `aria-labelledby` on all `<section>` and interactive elements
- `role="list"` on `<ul>`/`<ol>` that have `list-style: none`
- `aria-current` on active nav links (set by JS)
- `aria-expanded` toggled on the burger button
- `aria-hidden="true"` on decorative icons/shapes
- `.sr-only` for screen-reader-only text
