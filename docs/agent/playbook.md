# Agent Playbook

This file is the detailed operating guide for contributors and AI agents working on this plugin.

## Role

Work as a WordPress plugin engineer for `context-authority-toolkit`.
Prioritize safe, minimal, production-ready changes that preserve behavior and accessibility.

## Working boundaries

### Always

- Edit only files in this plugin unless explicitly instructed otherwise.
- Follow WordPress coding and security conventions.
- Keep tooltip behavior keyboard-accessible and ARIA-consistent.
- Keep docs aligned with canonical sources in `docs/README.md`.

### Ask first

- Changing CPT slug (`term`) or glossary meta schema.
- Renaming public classes/constants/hooks/script handles.
- Introducing new dependencies, build tools, or external services.
- Behavior changes that alter glossary matching semantics.

### Never

- Modify WordPress core, themes, or other plugins without explicit instruction.
- Commit or expose secrets or environment credentials.
- Remove tests to bypass failures.
- Bypass nonce/capability checks in admin save handlers.

## Required command workflow

Follow canonical gate instructions:

- `docs/testing/quality-gates.md`

Use `tests/manual-tooltip-gate.md` when tooltip interaction behavior changes.

## Documentation update policy

When a change is made, update the canonical doc location only:

- Process change -> `docs/testing/quality-gates.md`
- Architecture change -> `docs/internal/architecture.md`
- Interaction/markup contract change -> `docs/internal/contracts.md`
- User-facing behavior/version notes -> `readme.txt`
- Historical run evidence -> `docs/evidence/`

## Test-focused ownership boundaries

For test-only tasks (including future `test-agent` workflows):

- Allowed without extra approval:
  - `tests/*`
  - `docs/testing/*`
- Ask first before editing:
  - `context-authority-toolkit.php`
  - `includes/*`
  - `assets/*`
- Never:
  - change runtime behavior only to silence failing tests unless explicitly requested
  - remove failing tests/checklist scenarios to produce artificial pass results

## Reporting requirements

Final implementation report should include:

1. Files changed.
2. Commands run.
3. Pass/fail outcomes.
4. Residual risks or deferred items.
