# Context & Authority Toolkit Documentation Map

This file is the canonical routing guide for contributor and agent documentation.
Use it to determine which document is authoritative before making updates.

## Canonical sources by audience

- **Users / distributors:** `readme.txt`
- **Legal / provenance:** `ATTRIBUTION.txt`
- **Agent entrypoint:** `agents.md` (router only)
- **Detailed agent guidance:** `docs/agent/playbook.md`
- **Testing process and gate definitions:** `docs/testing/quality-gates.md`
- **Test coverage matrix and ownership:** `docs/testing/test-matrix.md`
- **Manual tooltip QA policy:** `docs/testing/manual-gates.md`
- **Manual tooltip execution checklist:** `tests/manual-tooltip-gate.md`
- **Architecture overview:** `docs/internal/architecture.md`
- **Behavior and interaction contracts:** `docs/internal/contracts.md`
- **Historical run evidence:** `docs/evidence/*.md`

## Update rules

1. Update only the canonical file for the concern you are changing.
2. If behavior changes, update both:
   - `docs/internal/contracts.md`
   - relevant testing docs (`docs/testing/quality-gates.md` and/or `tests/manual-tooltip-gate.md`).
3. If release-visible behavior or messaging changes, update `readme.txt`.
4. If process commands or gate order changes, update `docs/testing/quality-gates.md` first.
5. Preserve historical evidence as append-only snapshots in `docs/evidence/`.
