# Manual Gate Policy

## Tooltip/popover manual QA

Automated PHP behavior tests are required, but interactive tooltip/popover UX checks are still a manual gate when interaction behavior changes.

Use this checklist as the executable test artifact:

- `tests/manual-tooltip-gate.md`

## When manual QA is required

Run the checklist when changes affect:

- tooltip open/close behavior
- hover/focus/click interactions
- keyboard behavior (`Esc`, tab order, focus handling)
- popover markup, ARIA attributes, or linked content
- tooltip JavaScript and CSS state handling

## Evidence requirements

For each run, capture:

- tester name
- date/time
- environment (browser + OS)
- PASS/FAIL per scenario
- failure notes for any failed scenario
