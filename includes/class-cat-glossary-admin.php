<?php
/**
 * Admin glossary registration and metabox handling.
 *
 * @package ContextAuthorityToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers glossary admin UI.
 */
class CAT_Glossary_Admin {
	/**
	 * Glossary post type slug.
	 */
	const POST_TYPE = 'cat_glossary';

	/**
	 * Alternatives meta key.
	 */
	const ALTERNATIVES_META_KEY = 'cat_alternatives';

	/**
	 * Nonce action.
	 */
	const ALTERNATIVES_NONCE_ACTION = 'cat_save_alternatives';

	/**
	 * Nonce name.
	 */
	const ALTERNATIVES_NONCE_NAME = 'cat_alternatives_nonce';

	/**
	 * Wire admin hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_alternatives_metabox' ) );
		add_action( 'edit_form_after_title', array( $this, 'form_after_title' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_alternatives_metabox' ) );
	}

	/**
	 * Register plugin glossary post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Context Terms', 'context-authority-toolkit' ),
					'singular_name'      => __( 'Context Term', 'context-authority-toolkit' ),
					'add_new'            => __( 'Add New', 'context-authority-toolkit' ),
					'add_new_item'       => __( 'Add New Context Term', 'context-authority-toolkit' ),
					'edit_item'          => __( 'Edit Context Term', 'context-authority-toolkit' ),
					'new_item'           => __( 'New Context Term', 'context-authority-toolkit' ),
					'view_item'          => __( 'View Context Term', 'context-authority-toolkit' ),
					'search_items'       => __( 'Search Context Terms', 'context-authority-toolkit' ),
					'not_found'          => __( 'No context terms found.', 'context-authority-toolkit' ),
					'not_found_in_trash' => __( 'No context terms found in Trash.', 'context-authority-toolkit' ),
					'menu_name'          => __( 'Context Toolkit', 'context-authority-toolkit' ),
				),
				'public'       => true,
				'show_ui'      => true,
				'hierarchical' => false,
				'rewrite'      => false,
				'supports'     => array( 'title', 'editor', 'revisions' ),
			)
		);
	}

	/**
	 * Register alternatives metabox.
	 *
	 * @return void
	 */
	public function register_alternatives_metabox() {
		add_meta_box(
			'cat-alternate-names',
			__( 'Alternate Names', 'context-authority-toolkit' ),
			array( $this, 'alternative_names_metabox' ),
			self::POST_TYPE,
			'advanced',
			'high'
		);
	}

	/**
	 * Render advanced metaboxes after title.
	 *
	 * @return void
	 */
	public function form_after_title() {
		global $post, $wp_meta_boxes;

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		do_meta_boxes( get_current_screen(), 'advanced', $post );
		unset( $wp_meta_boxes[ self::POST_TYPE ]['advanced'] );
	}

	/**
	 * Output alternative names metabox.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function alternative_names_metabox( $post ) {
		$alternatives = get_post_meta( $post->ID, self::ALTERNATIVES_META_KEY, true );
		$alternatives = is_array( $alternatives ) ? $alternatives : array();

		wp_nonce_field( self::ALTERNATIVES_NONCE_ACTION, self::ALTERNATIVES_NONCE_NAME );

		echo '<p><label for="cat_alternative_names">' . esc_html__( 'Comma-separated alternative names or abbreviations for this term.', 'context-authority-toolkit' ) . '</label></p>';
		echo '<input type="text" id="cat_alternative_names" name="cat_alternative_names" class="large-text" value="' . esc_attr( implode( ', ', $alternatives ) ) . '" />';
	}

	/**
	 * Save alternative names metabox data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_alternatives_metabox( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::ALTERNATIVES_NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::ALTERNATIVES_NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::ALTERNATIVES_NONCE_ACTION ) ) {
			return;
		}

		if ( ! isset( $_POST['cat_alternative_names'] ) ) {
			delete_post_meta( $post_id, self::ALTERNATIVES_META_KEY );
			return;
		}

		$names = sanitize_text_field( wp_unslash( $_POST['cat_alternative_names'] ) );
		$names = preg_split( '!,\s*!', $names );
		$names = array_map( 'trim', $names );
		$names = array_map( 'sanitize_text_field', $names );
		$names = array_unique( $names );

		$names = array_filter(
			$names,
			function ( $name ) {
				return strlen( $name ) >= 2;
			}
		);

		update_post_meta( $post_id, self::ALTERNATIVES_META_KEY, array_values( $names ) );
	}
}
