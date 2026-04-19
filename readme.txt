=== Context & Authority Toolkit ===
Contributors: webdevmattcrom
Tags: glossary, tooltips, schema, seo, aeo
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a glossary post type and inline term tooltips for post and comment content.

== Description ==

Context & Authority Toolkit provides:

* A custom post type for defining context terms.
* Optional alternate term names and abbreviations.
* Frontend tooltip rendering for matched terms in content.

== Credits and Attribution ==

This project is a clean fork of the "WordPress.org Glossary" plugin:
https://wordpress.org/plugins/wporg-glossary/

Original work is credited to Automattic and listed contributors in that
upstream project. This fork keeps GPL-2.0-or-later licensing and documents
notable modifications in this repository.

For full provenance and attribution details, see:
* ATTRIBUTION.txt

== Changelog ==

= 0.1.0 =
* Initial clean fork from wporg-glossary.
* Added nonce and capability checks for metabox saving.
* Switched to prefixed classes and plugin-specific CPT/meta keys.
* Replaced jQuery tooltip behavior with a vanilla JavaScript implementation.
* Added strict behavior/security test runner and documented quality gates.
