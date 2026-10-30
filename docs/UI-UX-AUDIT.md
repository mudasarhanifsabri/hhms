# HHMS UI/UX audit

## Scope

- 216 Blade screens reviewed through their shared layout and repeated UI patterns.
- 759 occurrences of cards, forms, tables, modals, and embedded style blocks identified.
- Admin desktop and admin PWA use the same screens, so consistency must be solved centrally.

## Main findings

1. Visual rules were split between the vendor theme, `admin-pwa.css`, and page-level styles.
2. Cards, headings, input heights, table density, buttons, and modal spacing varied by module.
3. Many fields had no placeholder or visible required-field indicator.
4. Icon-only actions depended on browser title text and were difficult to understand on keyboard/touch.
5. Forms did not consistently communicate submission progress, allowing accidental double submission.
6. UUID route segments appeared as page titles and breadcrumbs.
7. Empty tables, validation feedback, and success messages had inconsistent presentation.
8. Images loaded eagerly even when below the fold.

## Implemented foundation

- Shared premium design tokens for color, typography, radius, elevation, borders, and spacing.
- Consistent cards, form controls, buttons, tables, pagination, badges, tabs, alerts, dropdowns, and modals.
- Responsive mobile modal and content behavior.
- Label-derived smart placeholders where copy was missing.
- Automatic required-field indicators and accessibility attributes.
- Duplicate-submit protection with visible loading state.
- Keyboard focus on the first useful field when a modal opens.
- Tooltips for icon-only actions.
- Lazy loading and async decoding for non-critical images.
- Human-readable titles when the URL ends in a UUID.
- Standard validation, success, warning, error, and empty states.

## Follow-up page-level priorities

1. Booking creation and editing: split long forms into clear steps and contextual summaries.
2. Accounting: standardize metric headers and action menus across every ledger/report screen.
3. Owners and units: reuse one profile header and one tab/navigation component.
4. Tasks and inspections: reuse condition/status tokens and evidence viewers.
5. Replace remaining embedded `<style>` blocks with shared module styles to reduce repeated CSS.
