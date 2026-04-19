# Plugin Architecture

## Purpose

Context & Authority Toolkit adds glossary term detection and tooltip/popover rendering for post and comment content.

## Main components

- `context-authority-toolkit.php`
  - Defines constants (`CAT_TOOLKIT_VERSION`, `CAT_TOOLKIT_FILE`, `CAT_TOOLKIT_DIR`, `CAT_TOOLKIT_URL`)
  - Loads component classes
  - Bootstraps plugin services on `plugins_loaded`
- `includes/class-cat-glossary-admin.php`
  - Registers glossary CPT (`term`)
  - Registers and saves alternatives metabox
  - Enforces capability + nonce checks on save
- `includes/class-cat-glossary.php`
  - Loads published glossary items
  - Handles matching data and regex generation
  - Maintains cache invalidation on glossary saves
- `includes/class-cat-glossary-handler.php`
  - Filters `the_content` and `comment_text`
  - Skips excluded HTML contexts
  - Wraps first in-content term match with trigger/panel markup
- `includes/class-cat-glossary-hovercards.php`
  - Enqueues frontend CSS/JS assets
- `assets/js/glossary-hovercards.js`
  - Manages interaction states (`is-visible`, `is-pinned`)
  - Handles click, hover, focus, and escape-close logic

## Data model

- CPT: `term`
- Post content: glossary definition/description
- Meta key: `cat_alternatives` (array of alternate names)

## Content flow

1. Glossary terms are loaded from published `term` posts.
2. Active names and alternatives are converted into a regex pattern.
3. Content/comment text is split and scanned outside excluded tags/contexts.
4. First match per term is replaced with trigger + hidden panel markup.
5. Frontend JS/CSS handles visibility and interactions.

## Related docs

- Behavior contracts: `docs/internal/contracts.md`
- Quality process: `docs/testing/quality-gates.md`
- Manual checklist: `tests/manual-tooltip-gate.md`
