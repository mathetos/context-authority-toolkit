<?php
/**
 * Plugin Name:       Context & Authority Toolkit
 * Description:       Adds glossary-powered context tooltips for terms in post and comment content.
 * Version:           0.1.0
 * Author:            Crucible CRM
 * Author URI:        https://cruciblecrm.com/
 * Forked from:       WordPress.org Glossary (Automattic and contributors)
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       context-authority-toolkit
 *
 * @package ContextAuthorityToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * This plugin is a clean fork of the "WordPress.org Glossary" plugin
 * and remains licensed under GPL-2.0-or-later. See ATTRIBUTION.md.
 */

define( 'CAT_TOOLKIT_VERSION', '0.1.0' );
define( 'CAT_TOOLKIT_FILE', __FILE__ );
define( 'CAT_TOOLKIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAT_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

require_once CAT_TOOLKIT_DIR . 'includes/class-cat-glossary.php';
require_once CAT_TOOLKIT_DIR . 'includes/class-cat-glossary-handler.php';
require_once CAT_TOOLKIT_DIR . 'includes/class-cat-glossary-admin.php';
require_once CAT_TOOLKIT_DIR . 'includes/class-cat-glossary-hovercards.php';

/**
 * Bootstrap the plugin.
 *
 * @return void
 */
function cat_toolkit_bootstrap() {
	$glossary = new CAT_Glossary();

	new CAT_Glossary_Admin();
	new CAT_Glossary_Hovercards();
	new CAT_Glossary_Handler( $glossary );
}
add_action( 'plugins_loaded', 'cat_toolkit_bootstrap' );
