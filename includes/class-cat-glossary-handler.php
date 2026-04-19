<?php
/**
 * Frontend glossary linking and markup handling.
 *
 * @package ContextAuthorityToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles replacing matched glossary terms in content.
 */
class CAT_Glossary_Handler {
	/**
	 * Glossary storage.
	 *
	 * @var CAT_Glossary
	 */
	private $glossary;

	/**
	 * Tracks terms already replaced in current content.
	 *
	 * @var bool[]
	 */
	private $processed = array();

	/**
	 * Constructor.
	 *
	 * @param CAT_Glossary $glossary Glossary object.
	 */
	public function __construct( CAT_Glossary $glossary ) {
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

		$processed_key = strtolower( $found_text );
		if ( ! empty( $this->processed[ $processed_key ] ) ) {
			return $matches[0];
		}
		$this->processed[ $processed_key ] = true;

		$description_html = wpautop( wp_kses_post( $glossary_item->description ) );
		if ( current_user_can( 'edit_post', $glossary_item->id ) ) {
			$description_html .= sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( get_edit_post_link( $glossary_item->id ) ),
				esc_html__( 'Edit Term', 'context-authority-toolkit' )
			);
		}

		$tooltip_html = sprintf(
			'<span class="cat-glossary-item-header">%1$s</span><span class="cat-glossary-item-description">%2$s</span>',
			esc_html( $glossary_item->name ),
			$description_html
		);

		return sprintf(
			'<span tabindex="0" class="cat-glossary-item-container">%1$s<span class="cat-glossary-item-hidden-content">%2$s</span></span>',
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

		$regex = $this->glossary->get_item_names_regex();
		if ( ! $regex ) {
			return $content;
		}

		$this->processed = array();
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
}
