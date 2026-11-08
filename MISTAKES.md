# Mistakes

Things that broke or got corrected, why, and how to avoid a repeat. Newest last.

## 2026-11-07: Shortcut guard relied on `isContentEditable`

- **What happened:** The Vitest test for "don't fire shortcuts while typing in the editor" failed: `isTyping` returned false for a `contenteditable` element.
- **Root cause:** jsdom does not implement `HTMLElement.isContentEditable`, so it is always undefined in tests. The code would have passed in a browser and been untestable.
- **Prevention:** Detect editable regions with `element.closest('[contenteditable]:not([contenteditable="false"])')`, which works in jsdom and browsers and also covers nodes nested inside TipTap. When a DOM API behaves oddly in Vitest, check jsdom support before assuming the code is wrong.

## 2026-11-07: `autofocus` inside a Vue `v-if` dialog does nothing

- **What happened:** The keyboard-shortcuts dialog opened without focus, so Esc and Tab didn't reach it.
- **Root cause:** Browsers honour `autofocus` only on initial page load, not on elements inserted later by Vue.
- **Prevention:** For anything opened at runtime (dialogs, panels), focus a ref in a `watch` after `nextTick()`, and cover it with a Vitest test that checks `document.activeElement`.

## 2026-11-07: Overriding `--bs-primary` didn't recolour primary buttons

- **What happened:** The accent colour applied to links and focus rings, but `.btn-primary` stayed Bootstrap's default blue.
- **Root cause:** Bootstrap 5 buttons read their own `--bs-btn-*` variables, compiled from the Sass primary colour, not the runtime `--bs-primary` custom property.
- **Prevention:** When changing a theme colour in plain CSS, also set the component-level variables (`--bs-btn-bg`, `--bs-btn-hover-bg` and so on) and check a screenshot of the component, not just the token.
