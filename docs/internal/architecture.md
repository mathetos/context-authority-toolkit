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
  - Registers REST-backed meta for block editor sidebar fields
  - Sanitizes `sameAs` and source repeater inputs to valid public `http/https` URLs and strict `YYYY-MM-DD` dates
  - Enqueues custom block editor sidebar controls
  - Runs one-time tooltip content migration from legacy post content
- `includes/class-cat-glossary.php`
  - Loads published glossary items
  - Handles matching data and regex generation
  - Maintains cache invalidation on glossary saves
- `includes/class-cat-glossary-handler.php`
  - Filters `the_content` and `comment_text`
  - Skips excluded HTML contexts
  - Wraps first in-content term match with trigger/panel markup
- `includes/class-cat-seo-peacekeeper.php`
  - Builds canonical `DefinedTerm` data from CAT-owned CPT/meta fields
  - Selects schema transport mode (standalone/Yoast/Rank Math/SEOPress/off)
  - Normalizes schema URL/date fields before output or adapter handoff
  - Injects schema into SEO plugin hooks or prints standalone JSON-LD
  - Adds semantic microdata wrappers (`aria-labelledby` + `dfn` id linkage) and read-aloud sanitization pipeline
- `includes/class-cat-glossary-hovercards.php`
  - Enqueues frontend CSS/JS assets
- `assets/js/glossary-hovercards.js`
  - Manages interaction states (`is-visible`, `is-pinned`)
  - Handles click, hover, focus, and escape-close logic

## Data model

- CPT: `term`
- Post content: block-editor single term page content
- Meta key: `cat_alternatives` (array of alternate names)
- Meta key: `cat_tooltip_content` (plain-text tooltip body with line breaks)
- Meta key: `cat_disable_autolinking` (boolean toggle for public content)
- Meta key: `cat_same_as` (array of external authority URLs)
- Meta key: `cat_sources` (array of citation rows with url/title/publisher/date)
- Option: `cat_schema_output_mode` (`auto|standalone|off`)
- Option: `cat_breadcrumb_integration` (boolean)

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
