<?php
/**
 * Frontend assets for glossary hovercards.
 *
 * @package ContextAuthorityToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues hovercard assets.
 */
class CAT_Glossary_Hovercards {
	/**
	 * Register frontend hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue CSS/JS for glossary hovercards.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'cat-glossary-hovercards',
			CAT_TOOLKIT_URL . 'assets/css/glossary-hovercards.css',
			array(),
			CAT_TOOLKIT_VERSION
		);

		wp_enqueue_script(
			'cat-glossary-hovercards',
			CAT_TOOLKIT_URL . 'assets/js/glossary-hovercards.js',
			array( 'jquery' ),
			CAT_TOOLKIT_VERSION,
			true
		);
	}
}
