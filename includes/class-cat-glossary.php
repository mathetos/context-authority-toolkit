<?php
/**
 * Glossary data access layer.
 *
 * @package ContextAuthorityToolkit
 */

namespace ContextAuthorityToolkit;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles glossary queries and caching.
 */
class Cat_Glossary {
	/**
	 * Cache group.
	 *
	 * @var string
	 */
	private $cache_group = 'context-authority-toolkit';

	/**
	 * Cache key version.
	 *
	 * @var int
	 */
	private $cache_version = 1;

	/**
	 * Construct the glossary object.
	 */
	public function __construct() {
		add_action( 'save_post_cat_glossary', array( $this, 'clear_cache' ) );
	}

	/**
	 * Load an item from the glossary by name.
	 *
	 * @param string $name Matched term.
	 * @return false|object
	 */
	public function get_active_item( $name ) {
		$case_sensitive = $this->name_is_case_sensitive( $name );

		$item = array_filter(
			$this->get_active_items(),
			function ( $entry ) use ( $name, $case_sensitive ) {
				if ( $case_sensitive && $entry->name === $name ) {
					return true;
				}

				if ( ! $case_sensitive && 0 === strcasecmp( $entry->name, $name ) ) {
					return true;
				}

				if ( empty( $entry->alternatives ) ) {
					return false;
				}

				if ( $case_sensitive && in_array( $name, $entry->alternatives, true ) ) {
					return true;
				}

				if ( ! $case_sensitive && in_array( strtolower( $name ), array_map( 'strtolower', $entry->alternatives ), true ) ) {
					return true;
				}

				return false;
			}
		);

		$match = array_shift( $item );
		return $match ? $match : false;
	}

	/**
	 * Get all glossary names and alternatives.
	 *
	 * @return string[]
	 */
	public function get_active_item_names() {
		$items = $this->get_active_items();
		$names = array_values( wp_list_pluck( $items, 'name' ) );

		$alternatives = array();
		foreach ( $items as $item ) {
			if ( ! empty( $item->alternatives ) && is_array( $item->alternatives ) ) {
				$alternatives = array_merge( $alternatives, $item->alternatives );
			}
		}

		return array_merge( $names, $alternatives );
	}

	/**
	 * Get all published glossary entries.
	 *
	 * @return object[]
	 */
	public function get_active_items() {
		$cache_key = "items-v{$this->cache_version}";
		$items     = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $items ) {
			return $items;
		}

		$items = array();
		$posts = get_posts(
			array(
				'post_type'   => Cat_Glossary_Admin::POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		foreach ( $posts as $post ) {
			$item                               = $this->post_to_glossary_item( $post );
			$items[ strtolower( $item->name ) ] = $item;
		}

		wp_cache_set( $cache_key, $items, $this->cache_group, HOUR_IN_SECONDS );
		return $items;
	}

	/**
	 * Map a post object into a glossary item object.
	 *
	 * @param WP_Post $post Source post.
	 * @return object
	 */
	protected function post_to_glossary_item( WP_Post $post ) {
		$alternatives = get_post_meta( $post->ID, Cat_Glossary_Admin::ALTERNATIVES_META_KEY, true );

		return (object) array(
			'id'           => $post->ID,
			'name'         => trim( $post->post_title ),
			'description'  => trim( $post->post_content ),
			'alternatives' => is_array( $alternatives ) ? $alternatives : array(),
		);
	}

	/**
	 * Convert all item names to a regex pattern.
	 *
	 * @return false|string
	 */
	public function get_item_names_regex() {
		$item_names = $this->get_active_item_names();
		if ( empty( $item_names ) ) {
			return false;
		}

		usort(
			$item_names,
			function ( $left, $right ) {
				return ( strlen( $left ) < strlen( $right ) ) ? 1 : -1;
			}
		);

		$regex = implode(
			'|',
			array_map(
				function ( $name ) {
					return preg_quote( $name, '/' );
				},
				$item_names
			)
		);

		return "/\b($regex)(?![^<]*>|[.]\w)\b/i";
	}

	/**
	 * Clear the glossary cache.
	 *
	 * @return void
	 */
	public function clear_cache() {
		wp_cache_delete( "items-v{$this->cache_version}", $this->cache_group );
	}

	/**
	 * Determine if a term should be case-sensitive.
	 *
	 * @param string $name Term.
	 * @return bool
	 */
	public function name_is_case_sensitive( $name ) {
		return strlen( $name ) <= 3;
	}
}
