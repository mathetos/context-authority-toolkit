<?php
/**
 * Frontend glossary linking and markup handling.
 *
 * @package ContextAuthorityToolkit
 */

namespace ContextAuthorityToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles replacing matched glossary terms in content.
 */
class Cat_Glossary_Handler {
	/**
	 * Glossary storage.
	 *
	 * @var Cat_Glossary
	 */
	private $glossary;

	/**
	 * Tracks terms already replaced in current content.
	 *
	 * @var bool[]
	 */
	private $processed = array();

	/**
	 * Incrementing instance counter for unique trigger/panel IDs.
	 *
	 * @var int
	 */
	private $instance_counter = 0;

	/**
	 * Current context post ID.
	 *
	 * @var int
	 */
	private $context_post_id = 0;

	/**
	 * Constructor.
	 *
	 * @param Cat_Glossary $glossary Glossary object.
	 */
	public function __construct( Cat_Glossary $glossary ) {
		$this->glossary = $glossary;

		add_filter( 'the_content', array( $this, 'glossary_links' ), 20 );
		add_filter( 'comment_text', array( $this, 'glossary_links' ), 20 );
	}

	/**
	 * Build hovercard markup for a matched term.
	 *
	 * @param array $matches Regex match payload.
	 * @return string
	 */
	public function glossary_item_hovercard( $matches ) {
		$found_text    = $matches[1];
		$glossary_item = $this->glossary->get_active_item( $found_text );

		if ( ! $glossary_item ) {
			return $matches[0];
		}

		if ( $this->context_post_id > 0 && absint( $glossary_item->id ) === $this->context_post_id ) {
			return $matches[0];
		}

		$processed_key = strtolower( $found_text );
		if ( ! empty( $this->processed[ $processed_key ] ) ) {
			return $matches[0];
		}
		$this->processed[ $processed_key ] = true;
		$this->instance_counter++;

		$description_text = is_string( $glossary_item->description ) ? trim( $glossary_item->description ) : '';
		$description_html = nl2br( esc_html( $description_text ) );
		$learn_more_url = get_permalink( $glossary_item->id );
		if ( ! empty( $learn_more_url ) ) {
			$description_html .= sprintf(
				'<br /><a class="cat-glossary-item-link" href="%1$s">%2$s</a>',
				esc_url( $learn_more_url ),
				esc_html__( 'Learn more', 'context-authority-toolkit' )
			);
		}

		$trigger_id = 'cat-glossary-item-trigger-' . absint( $glossary_item->id ) . '-' . $this->instance_counter;
		$panel_id   = 'cat-glossary-item-panel-' . absint( $glossary_item->id ) . '-' . $this->instance_counter;

		$tooltip_html = sprintf(
			'<span id="%1$s" class="cat-glossary-item-hidden-content" role="dialog" aria-labelledby="%2$s" hidden><span class="cat-glossary-item-header">%3$s</span><span class="cat-glossary-item-description">%4$s</span></span>',
			esc_attr( $panel_id ),
			esc_attr( $trigger_id ),
			esc_html( $glossary_item->name ),
			$description_html
		);

		return sprintf(
			'<span class="cat-glossary-item-container"><button type="button" id="%1$s" class="cat-glossary-item-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="%2$s">%3$s</button>%4$s</span>',
			esc_attr( $trigger_id ),
			esc_attr( $panel_id ),
			esc_html( $found_text ),
			$tooltip_html
		);
	}

	/**
	 * Parse and wrap glossary terms in content.
	 *
	 * @param string $content Source content.
	 * @return string
	 */
	public function glossary_links( $content ) {
		if ( is_feed() || is_embed() || is_admin() ) {
			return $content;
		}

		$this->context_post_id = $this->get_context_post_id();

		if ( $this->context_post_id > 0 && $this->is_autolinking_disabled_for_context() ) {
			return $content;
		}

		$regex = $this->glossary->get_item_names_regex();
		if ( ! $regex ) {
			return $content;
		}

		$this->processed = array();
		$this->instance_counter = 0;
		$textarr         = wp_html_split( $content );
		$ignore_elements = array( 'code', '/code', 'a', '/a', 'pre', '/pre', 'dt', '/dt', 'option', '/option' );
		$inside_block    = array();

		foreach ( $textarr as &$element ) {
			if ( 0 === strpos( $element, '<' ) ) {
				$offset     = 1;
				$is_end_tag = false;

				if ( 1 === strpos( $element, '/' ) ) {
					$offset     = 2;
					$is_end_tag = true;
				}

				preg_match( '/^.+(\b|\n|$)/U', substr( $element, $offset ), $matches );
				if ( $matches && in_array( $matches[0], $ignore_elements, true ) ) {
					if ( ! $is_end_tag ) {
						array_unshift( $inside_block, $matches[0] );
					} elseif ( $inside_block && $matches[0] === $inside_block[0] ) {
						array_shift( $inside_block );
					}
					continue;
				}

				if ( $matches && 'span' === $matches[0] && false !== strpos( $element, 'cat-glossary-item-container' ) ) {
					if ( ! $is_end_tag ) {
						array_unshift( $inside_block, $matches[0] );
					} elseif ( $inside_block && $matches[0] === $inside_block[0] ) {
						array_shift( $inside_block );
					}
					continue;
				}
			}

			if ( strpos( $element, 'http://' ) !== false || strpos( $element, 'https://' ) !== false || strpos( $element, 'www.' ) !== false ) {
				continue;
			}

			if ( empty( $inside_block ) ) {
				$element = preg_replace_callback( $regex, array( $this, 'glossary_item_hovercard' ), $element );
			}
		}

		return implode( '', $textarr );
	}

	/**
	 * Resolve the current post context ID.
	 *
	 * @return int
	 */
	private function get_context_post_id() {
		$post_id = get_the_ID();

		if ( $post_id > 0 ) {
			return absint( $post_id );
		}

		global $post;
		if ( $post instanceof \WP_Post ) {
			return absint( $post->ID );
		}

		return 0;
	}

	/**
	 * Determine if auto-linking is disabled for the current post context.
	 *
	 * @return bool
	 */
	private function is_autolinking_disabled_for_context() {
		$is_disabled = get_post_meta( $this->context_post_id, Cat_Glossary_Admin::DISABLE_AUTOLINKING_META_KEY, true );

		return (bool) rest_sanitize_boolean( $is_disabled );
	}
}
