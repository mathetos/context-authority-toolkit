<?php
/**
 * Behavior and security gate tests for Context & Authority Toolkit.
 *
 * Execute using: wp eval-file tests/run-behavior-tests.php
 *
 * @package ContextAuthorityToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/user.php';

if ( ! class_exists( '\\ContextAuthorityToolkit\\Cat_Glossary' ) || ! class_exists( '\\ContextAuthorityToolkit\\Cat_Glossary_Handler' ) || ! class_exists( '\\ContextAuthorityToolkit\\Cat_Glossary_Admin' ) ) {
	echo "Plugin classes are unavailable. Ensure plugin is active before running tests.\n";
	exit( 1 );
}

$failures = array();

/**
 * Record assertion failures.
 *
 * @param bool   $condition Condition result.
 * @param string $message Failure message.
 * @return void
 */
function cat_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
	}
}

/**
 * Count substring occurrences.
 *
 * @param string $haystack Full string.
 * @param string $needle   Search token.
 * @return int
 */
function cat_count_occurrences( $haystack, $needle ) {
	return substr_count( $haystack, $needle );
}

/**
 * Create a glossary term post.
 *
 * @param string   $name Term name.
 * @param string   $description Description.
 * @param string[] $alternatives Alternatives list.
 * @return int
 */
function cat_create_term( $name, $description, array $alternatives = array() ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => \ContextAuthorityToolkit\Cat_Glossary_Admin::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => $description,
		)
	);

	if ( ! is_wp_error( $post_id ) && ! empty( $alternatives ) ) {
		update_post_meta( $post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_META_KEY, $alternatives );
	}

	return (int) $post_id;
}

$admin_users = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);

if ( empty( $admin_users ) ) {
	echo "No administrator user available for tests.\n";
	exit( 1 );
}

$admin_user_id = (int) $admin_users[0];
wp_set_current_user( $admin_user_id );

$test_post_ids = array();

// Test 1: Longer term precedence should still allow shorter standalone matches.
$test_post_ids[] = cat_create_term( 'WordPress', 'Short description.' );
$test_post_ids[] = cat_create_term( 'WordPress.org', 'Long description.' );
$content         = 'WordPress.org powers WordPress.';
$filtered        = apply_filters( 'the_content', $content );

cat_assert(
	cat_count_occurrences( $filtered, 'cat-glossary-item-container' ) === 2,
	'Longer term precedence test failed: expected two wrapped terms.'
);
cat_assert(
	strpos( $filtered, '>WordPress.org<' ) !== false,
	'Longer term precedence test failed: WordPress.org was not wrapped.'
);

// Test 2: Short term case sensitivity (<=3 chars) should require exact case.
$test_post_ids[] = cat_create_term( 'API', 'Application Programming Interface.' );
$filtered_case   = apply_filters( 'the_content', 'api and API are different here.' );
cat_assert(
	cat_count_occurrences( $filtered_case, 'cat-glossary-item-container' ) === 1,
	'Case sensitivity test failed: expected exactly one wrapped short term.'
);
cat_assert(
	strpos( $filtered_case, 'api and' ) !== false,
	'Case sensitivity test failed: lowercase short term should remain plain text.'
);

// Test 3: Terms inside excluded HTML tags should not be wrapped.
$excluded_content  = 'Outside WordPress <a href="#">WordPress</a> <code>API</code> <pre>WordPress.org</pre>';
$filtered_excluded = apply_filters( 'the_content', $excluded_content );
cat_assert(
	cat_count_occurrences( $filtered_excluded, 'cat-glossary-item-container' ) === 1,
	'Excluded tags test failed: only non-excluded text should be wrapped.'
);
cat_assert(
	strpos( $filtered_excluded, '<a href="#">WordPress</a>' ) !== false,
	'Excluded tags test failed: anchor content should be untouched.'
);
cat_assert(
	strpos( $filtered_excluded, '<code>API</code>' ) !== false,
	'Excluded tags test failed: code content should be untouched.'
);

// Test 4: A repeated term in same content should only be wrapped once.
$filtered_repeat = apply_filters( 'the_content', 'WordPress WordPress WordPress' );
cat_assert(
	cat_count_occurrences( $filtered_repeat, 'cat-glossary-item-container' ) === 1,
	'Single replacement test failed: repeated term should wrap only once per content.'
);

// Test 5: Interactive popover markup contract and Learn more permalink link.
$learn_more_post_id = cat_create_term( 'Permalink Term', 'Permalink test description.' );
$test_post_ids[]    = $learn_more_post_id;
$filtered_link      = apply_filters( 'the_content', 'Permalink Term appears in content.' );
$expected_href      = esc_url( get_permalink( $learn_more_post_id ) );

cat_assert(
	strpos( $filtered_link, 'class="cat-glossary-item-trigger"' ) !== false,
	'Popover contract failed: trigger button class is missing.'
);
cat_assert(
	strpos( $filtered_link, 'aria-expanded="false"' ) !== false && strpos( $filtered_link, 'aria-haspopup="dialog"' ) !== false,
	'Popover contract failed: trigger ARIA state attributes are missing.'
);
cat_assert(
	preg_match( '/id="([^"]*cat-glossary-item-trigger[^"]*)"/', $filtered_link, $trigger_match ) === 1,
	'Popover contract failed: trigger ID was not rendered.'
);
cat_assert(
	preg_match( '/id="([^"]*cat-glossary-item-panel[^"]*)"/', $filtered_link, $panel_match ) === 1,
	'Popover contract failed: panel ID was not rendered.'
);

if ( ! empty( $trigger_match[1] ) && ! empty( $panel_match[1] ) ) {
	cat_assert(
		strpos( $filtered_link, 'aria-controls="' . esc_attr( $panel_match[1] ) . '"' ) !== false,
		'Popover contract failed: aria-controls does not match panel ID.'
	);
	cat_assert(
		strpos( $filtered_link, 'aria-labelledby="' . esc_attr( $trigger_match[1] ) . '"' ) !== false,
		'Popover contract failed: panel aria-labelledby does not match trigger ID.'
	);
}

cat_assert(
	strpos( $filtered_link, 'role="dialog"' ) !== false && strpos( $filtered_link, ' hidden' ) !== false,
	'Popover contract failed: panel dialog role/hidden state is missing.'
);
cat_assert(
	cat_count_occurrences( $filtered_link, 'class="cat-glossary-item-link"' ) === 1,
	'Learn more link test failed: expected exactly one Learn more link.'
);
cat_assert(
	strpos( $filtered_link, '>Learn more<' ) !== false,
	'Learn more link test failed: anchor text must be exactly Learn more.'
);
cat_assert(
	strpos( $filtered_link, 'href="' . $expected_href . '"' ) !== false,
	'Learn more link test failed: href does not match term permalink.'
);
cat_assert(
	strpos( $filtered_link, 'Edit Term' ) === false,
	'Learn more link test failed: legacy Edit Term link text must not appear in frontend output.'
);

// Test 6: Save handler security gates.
$admin_handler     = new \ContextAuthorityToolkit\Cat_Glossary_Admin();
$secured_post_id   = cat_create_term( 'Security Term', 'Security test term.' );
$test_post_ids[]   = $secured_post_id;
$subscriber_login  = 'cat_subscriber_' . wp_generate_password( 8, false, false );
$subscriber_user   = wp_insert_user(
	array(
		'user_login' => $subscriber_login,
		'user_pass'  => wp_generate_password( 24, true, true ),
		'user_email' => $subscriber_login . '@example.com',
		'role'       => 'subscriber',
	)
);

// Invalid capability should fail even with nonce.
if ( ! is_wp_error( $subscriber_user ) ) {
	wp_set_current_user( (int) $subscriber_user );
	$_POST = array(
		\ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_NONCE_NAME => wp_create_nonce( \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_NONCE_ACTION ),
		'cat_alternative_names'                     => 'NoAccess',
	);
	$admin_handler->save_alternatives_metabox( $secured_post_id );
	$saved = get_post_meta( $secured_post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_META_KEY, true );
	cat_assert(
		empty( $saved ),
		'Security test failed: subscriber should not be able to save alternatives.'
	);
}

// Invalid nonce should fail for admin.
wp_set_current_user( $admin_user_id );
$_POST = array(
	\ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_NONCE_NAME => 'invalid_nonce',
	'cat_alternative_names'                     => 'BadNonce',
);
$admin_handler->save_alternatives_metabox( $secured_post_id );
$saved = get_post_meta( $secured_post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_META_KEY, true );
cat_assert(
	empty( $saved ),
	'Security test failed: invalid nonce should block write.'
);

// Valid admin nonce should succeed.
$_POST = array(
	\ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_NONCE_NAME => wp_create_nonce( \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_NONCE_ACTION ),
	'cat_alternative_names'                     => 'Valid Name, VN',
);
$admin_handler->save_alternatives_metabox( $secured_post_id );
$saved = get_post_meta( $secured_post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_META_KEY, true );
cat_assert(
	is_array( $saved ) && in_array( 'Valid Name', $saved, true ) && in_array( 'VN', $saved, true ),
	'Security test failed: valid admin write did not persist expected alternatives.'
);

// Cleanup.
$_POST = array();
foreach ( $test_post_ids as $test_post_id ) {
	wp_delete_post( (int) $test_post_id, true );
}

if ( ! is_wp_error( $subscriber_user ) ) {
	wp_delete_user( (int) $subscriber_user );
}

if ( ! empty( $failures ) ) {
	echo "Behavior/Security tests FAILED:\n";
	foreach ( $failures as $failure ) {
		echo ' - ' . esc_html( $failure ) . "\n";
	}
	exit( 1 );
}

echo "Behavior/Security tests PASSED.\n";
exit( 0 );
