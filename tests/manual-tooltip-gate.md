# Manual Tooltip/Popover Gate

This gate is a strict human verification replacement for automated browser tests.

## Preconditions
- Plugin `context-authority-toolkit` is active.
- At least two glossary terms exist and are published.
- A post or page contains both terms in normal content (not inside `a`, `code`, `pre`).

## Pass/Fail Checklist

Run each scenario exactly as written. Any failed scenario is a gate FAIL.

1. Open on click/tap
   - Action: Click the first linked glossary term.
   - PASS when:
     - Popover appears.
     - Popover includes `Learn more` link text.
     - `Learn more` opens that term's public URL.

2. Single-open invariant
   - Action: Open first term popover, then click second term.
   - PASS when:
     - Second popover opens.
     - First popover closes.

3. Outside click close
   - Action: Open a popover, then click outside any glossary trigger/panel.
   - PASS when:
     - Open popover closes.

4. Escape close
   - Action: Open a popover, press `Esc`.
   - PASS when:
     - Open popover closes.

5. Keyboard focus behavior
   - Action: Tab to a glossary term trigger and press `Enter`.
   - PASS when:
     - Popover opens.
     - Tabbing can reach `Learn more`.
     - Moving focus outside the active popover closes it.

6. Hover intent behavior (desktop)
   - Action: Hover a glossary term for ~0.2 seconds.
   - PASS when:
     - Popover opens with a slight delay.
     - Moving pointer away closes it after a short delay.

7. Legacy link regression
   - Action: Inspect popover content in browser UI.
   - PASS when:
     - `Edit Term` does not appear in frontend popovers.

## Evidence Required
- Tester name.
- Date/time.
- Environment (browser + OS).
- PASS/FAIL per scenario.
- If FAIL: exact scenario number, observed behavior, and screenshot/video optional.
