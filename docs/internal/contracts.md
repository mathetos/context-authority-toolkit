# Behavior and Interaction Contracts

This document defines live behavior contracts that must remain true unless a change is explicitly approved.

## Matching and replacement contract

- Matches include glossary term titles and configured alternatives.
- Terms of length 3 or less are case-sensitive.
- Longer terms match case-insensitively.
- Longer terms must be prioritized to avoid shorter-term collisions.
- Terms are not wrapped inside excluded HTML contexts (for example `a`, `code`, `pre`, `option`, existing glossary container markup).
- Repeated appearances of the same matched term in one content string are wrapped once.
- When `cat_disable_autolinking` is enabled for a post, no glossary auto-linking is applied in that post context.
- Term single content never auto-links its own term title; other glossary terms may still be linked.

## Markup and accessibility contract

- Wrapped term renders as a button trigger inside `.cat-glossary-item-container`.
- Trigger has:
  - `type="button"`
  - `aria-expanded`
  - `aria-haspopup="dialog"`
  - `aria-controls` linked to panel id
- Hidden panel has:
  - unique `id`
  - `role="dialog"`
  - `aria-labelledby` linked to trigger id
  - `hidden` initial state
- Tooltip description is sourced from `cat_tooltip_content` meta (not term `post_content`).
- Tooltip description treats HTML as plain text and supports line breaks.
- Frontend content includes a user-facing `Learn more` link to the term permalink.
- Legacy frontend text `Edit Term` must not appear.

## Interaction contract

- Click/tap opens popover.
- Clicking a different glossary term closes previously open popover and opens the new one.
- Click outside closes open popover.
- `Esc` closes open popover and should return focus to trigger when applicable.
- Keyboard navigation must allow focus to move into popover content and close on leaving active region.
- Hover intent opens/closes with short delay on desktop behavior.

## Verification contract

- Automated behavior/security checks: `tests/run-behavior-tests.php`
- Manual tooltip checks: `tests/manual-tooltip-gate.md`
- Full gate process: `docs/testing/quality-gates.md`
