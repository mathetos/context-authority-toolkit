<?php
/**
 * Behavior and security gate tests for Context & Authority Toolkit.
 *
 * Execute using: wp eval-file tests/run-behavior-tests.php
 *
 * PHP version 7.2+
 *
 * @category ContextAuthorityToolkit
 * @package  ContextAuthorityToolkit
 * @author   Crucible CRM <support@cruciblecrm.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://cruciblecrm.com/
 */

// phpcs:disable Generic.Files.LineLength.TooLong

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
 * @param string $message   Failure message.
 *
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
 *
 * @return int
 */
function cat_count_occurrences( $haystack, $needle ) {
	return substr_count( $haystack, $needle );
}

/**
 * Create a glossary term post.
 *
 * @param string   $name           Term name.
 * @param string   $single_content Single term page content.
 * @param string[] $alternatives   Alternatives list.
 * @param string   $tooltip        Tooltip text.
 *
 * @return int
 */
function cat_create_term( $name, $single_content, array $alternatives = array(), $tooltip = '' ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => \ContextAuthorityToolkit\Cat_Glossary_Admin::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => $single_content,
		)
	);

	if ( ! is_wp_error( $post_id ) && ! empty( $alternatives ) ) {
		update_post_meta( $post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::ALTERNATIVES_META_KEY, $alternatives );
	}

	if ( ! is_wp_error( $post_id ) && '' !== $tooltip ) {
		update_post_meta( $post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, $tooltip );
	}

	return (int) $post_id;
}

/**
 * Create a public post for content-linking tests.
 *
 * @param string $title   Post title.
 * @param string $content Post content.
 *
 * @return int
 */
function cat_create_public_post( $title, $content ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
		)
	);

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

$admin_handler = new \ContextAuthorityToolkit\Cat_Glossary_Admin();
$admin_handler->register_post_type();
$admin_handler->register_post_meta();

$test_post_ids = array();

// Test 1: Longer term precedence should still allow shorter standalone matches.
$test_post_ids[] = cat_create_term( 'WordPress', 'Single content A', array(), 'Short description.' );
$test_post_ids[] = cat_create_term( 'WordPress.org', 'Single content B', array(), 'Long description.' );
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
$test_post_ids[] = cat_create_term( 'API', 'Single content C', array(), 'Application Programming Interface.' );
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
$learn_more_post_id = cat_create_term(
	'Permalink Term',
	'Single term block content should not be used as tooltip.',
	array(),
	"Tooltip line one.\n<strong>Tooltip line two</strong>"
);
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
	strpos( $expected_href, '?post_type=' ) === false && strpos( $expected_href, '&p=' ) === false,
	'Learn more link test failed: expected permalink URL instead of query-style URL.'
);
cat_assert(
	strpos( $filtered_link, 'Edit Term' ) === false,
	'Learn more link test failed: legacy Edit Term link text must not appear in frontend output.'
);
cat_assert(
	strpos( $filtered_link, 'Tooltip line one.<br />' ) !== false,
	'Tooltip source test failed: expected newline conversion into <br />.'
);
cat_assert(
	strpos( $filtered_link, '&lt;strong&gt;Tooltip line two&lt;/strong&gt;' ) !== false,
	'Tooltip source test failed: expected tooltip HTML to be escaped and rendered as text.'
);
cat_assert(
	strpos( $filtered_link, 'Single term block content should not be used as tooltip.' ) === false,
	'Tooltip source test failed: tooltip output still appears to source from post_content.'
);

// Test 6: Meta authorization callback should reject subscribers and allow admins.
$secured_post_id = cat_create_term( 'Security Term', 'Security test term.', array(), 'Security tooltip.' );
$test_post_ids[] = $secured_post_id;

$subscriber_login = 'cat_subscriber_' . wp_generate_password( 8, false, false );
$subscriber_user  = wp_insert_user(
	array(
		'user_login' => $subscriber_login,
		'user_pass'  => wp_generate_password( 24, true, true ),
		'user_email' => $subscriber_login . '@example.com',
		'role'       => 'subscriber',
	)
);

// Subscriber should not be authorized to edit term meta.
if ( ! is_wp_error( $subscriber_user ) ) {
	wp_set_current_user( (int) $subscriber_user );
	$allowed = $admin_handler->can_edit_term_meta( false, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, $secured_post_id, (int) $subscriber_user );
	cat_assert(
		false === $allowed,
		'Security test failed: subscriber should not be authorized to edit term meta.'
	);
}

// Admin should be authorized to edit term meta.
wp_set_current_user( $admin_user_id );
$allowed = $admin_handler->can_edit_term_meta( false, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, $secured_post_id, $admin_user_id );
cat_assert(
	true === $allowed,
	'Security test failed: administrator should be authorized to edit term meta.'
);

// Test 7: Meta sanitizers should enforce field rules.
$sanitized_alternatives = $admin_handler->sanitize_alternatives_meta(
	array(
		' WP ',
		'WP',
		'a',
		'API',
	)
);
cat_assert(
	array( 'WP', 'API' ) === $sanitized_alternatives,
	'Sanitizer test failed: alternatives sanitizer did not normalize values as expected.'
);
$raw_tooltip       = "Tooltip first line\r\n<script>alert('x')</script>";
$sanitized_tooltip = $admin_handler->sanitize_tooltip_meta( $raw_tooltip );
cat_assert(
	strpos( $sanitized_tooltip, "\r" ) === false && strpos( $sanitized_tooltip, "<script>alert('x')</script>" ) !== false,
	'Sanitizer test failed: tooltip sanitizer should normalize line endings without stripping literal text.'
);

// Test 8: One-time migration copies legacy post_content only when tooltip meta is empty.
delete_option( \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_MIGRATION_OPTION_KEY );
$migration_source_id = cat_create_term( 'Migration Term', 'Legacy content for migration.', array(), '' );
$test_post_ids[]     = $migration_source_id;
$migration_target_id = cat_create_term( 'Migration Already Set', 'Should not overwrite.', array(), 'Existing tooltip text.' );
$test_post_ids[]     = $migration_target_id;
$admin_handler->maybe_run_tooltip_migration();

$migrated_tooltip = get_post_meta( $migration_source_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, true );
cat_assert(
	'Legacy content for migration.' === $migrated_tooltip,
	'Migration test failed: empty tooltip meta should be populated from legacy post_content.'
);
$preserved_tooltip = get_post_meta( $migration_target_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, true );
cat_assert(
	'Existing tooltip text.' === $preserved_tooltip,
	'Migration test failed: existing tooltip meta should not be overwritten.'
);

// Migration should not run again once option is set.
wp_update_post(
	array(
		'ID'           => $migration_source_id,
		'post_content' => 'Updated post content after migration.',
	)
);
$admin_handler->maybe_run_tooltip_migration();
$migrated_tooltip_after_second_run = get_post_meta( $migration_source_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_META_KEY, true );
cat_assert(
	'Legacy content for migration.' === $migrated_tooltip_after_second_run,
	'Migration test failed: migration should be idempotent and skip second run.'
);

// Test 9: Public post toggle disables/enables glossary auto-linking.
$toggle_post_id  = cat_create_public_post( 'Toggle Test Post', 'WordPress remains plain text when disabled.' );
$test_post_ids[] = $toggle_post_id;

update_post_meta( $toggle_post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::DISABLE_AUTOLINKING_META_KEY, true );
$toggle_post = get_post( $toggle_post_id );
setup_postdata( $toggle_post );
$filtered_toggle_disabled = apply_filters( 'the_content', 'WordPress should not be linked while disabled.' );
cat_assert(
	strpos( $filtered_toggle_disabled, 'cat-glossary-item-container' ) === false,
	'Auto-link toggle test failed: expected no glossary links when disabled.'
);

update_post_meta( $toggle_post_id, \ContextAuthorityToolkit\Cat_Glossary_Admin::DISABLE_AUTOLINKING_META_KEY, false );
$toggle_post = get_post( $toggle_post_id );
setup_postdata( $toggle_post );
$filtered_toggle_enabled = apply_filters( 'the_content', 'WordPress should be linked while enabled.' );
cat_assert(
	strpos( $filtered_toggle_enabled, 'cat-glossary-item-container' ) !== false,
	'Auto-link toggle test failed: expected glossary links when enabled.'
);

// Test 10: Term content does not self-link but still links other glossary terms.
$self_term_id    = cat_create_term( 'Objective', 'Objective mentions WordPress.', array(), 'Objective tooltip.' );
$test_post_ids[] = $self_term_id;
$self_term_post  = get_post( $self_term_id );
setup_postdata( $self_term_post );
$filtered_self_term = apply_filters( 'the_content', 'Objective mentions WordPress.' );

cat_assert(
	strpos( $filtered_self_term, '>Objective<' ) === false,
	'Self-link test failed: term title should not be converted into a self-link trigger.'
);
cat_assert(
	strpos( $filtered_self_term, 'Objective mentions' ) !== false,
	'Self-link test failed: original self-term text should remain plain text.'
);
cat_assert(
	strpos( $filtered_self_term, '>WordPress<' ) !== false && strpos( $filtered_self_term, 'cat-glossary-item-container' ) !== false,
	'Self-link test failed: other glossary terms should still be linked in term content.'
);

wp_reset_postdata();
foreach ( $test_post_ids as $test_post_id ) {
	wp_delete_post( (int) $test_post_id, true );
}

if ( ! is_wp_error( $subscriber_user ) ) {
	wp_delete_user( (int) $subscriber_user );
}
delete_option( \ContextAuthorityToolkit\Cat_Glossary_Admin::TOOLTIP_MIGRATION_OPTION_KEY );

if ( ! empty( $failures ) ) {
	echo "Behavior/Security tests FAILED:\n";
	foreach ( $failures as $failure ) {
		echo ' - ' . esc_html( $failure ) . "\n";
	}
	exit( 1 );
}

echo "Behavior/Security tests PASSED.\n";
exit( 0 );
