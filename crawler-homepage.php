<?php
/**
 * Plugin Name: Crawler Homepage
 * Plugin URI: https://github.com/antoinemineau/crawler-homepage
 * Description: [WP Rocket technical assessment] WordPress Plugin to crawl homepage and build a sitemap
 * Version: 0.1
 * Author: Antoine Mineau
 * Licence: GPLv3
 * Copyright 2020-2020 Antoine Mineau
 */

defined( 'ABSPATH' ) || die( 'Blocked' );

define( 'CH_VERSION', '0.1' );
define( 'CH_SLUG', 'wp_crawler_homepage' );
define( 'CH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
/**
 * @CODEREVIEW: This code is less performant. Why? It's using plugin_dir_path 2 more times instead
 * of using the constant above.
 */
define( 'CH_PLUGIN_RESULT_PATH', plugin_dir_path( __FILE__ ) . 'result/' );
define( 'CH_PLUGIN_RESULT_URL', plugin_dir_url( __FILE__ ) . 'result/' );

/**
 * @CODEREVIEW: Each class should be in its own file by itself.
 * Why?
 *   1. Allows the class to be autoloaded via PSR standards.
 *   2. Easier to quickly locate classes which makes the code more maintainable.
 *   3. Allows the code to be reused and bundled with other libraries.
 *   4. More readable.
 */

/**
 * Class Crawler_Homepage
 */
class Crawler_Homepage {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = CH_VERSION;

	/**
	 * Wp_Crawler_Homepage constructor.
	 */
	public function __construct() {
		/**
		 * @CODEREVIEW: Hook event registrations should be in a public method instead of in the constructor.
		 * Why? Testing.
		 */
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( CH_SLUG . '_cron_hourly', [ $this, 'crawl_create' ] );
		add_action( 'wp_footer', [ $this, 'sitemap_show_on_website' ], 100 );
	}

	/**
	 * Show a link on the website to the generated sitemap.
	 */
	public function sitemap_show_on_website() {
		// @CODEREVIEW: String literals should be internationalized. Here you'd use esc_html__( 'Sitemap', 'crawler-homepage' ).
		echo '<a target="_blank" href="' . esc_url( CH_PLUGIN_RESULT_URL ) . 'sitemap.html">Sitemap</a>';
	}

	/**
	 * Add options page.
	 */
	public function add_options_page() {
		/**
		 * @CODEREVIEW: String literals should be internationalized.
		 */
		add_options_page(
			'Crawler Homepage',
			'Crawler Homepage',
			'manage_options',
			CH_SLUG,
			[ $this, 'create_admin_page' ]
		);
	}

	/**
	 * Create the admin page.
	 */
	public function create_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			/**
			 * @CODEREVIEW: Add your plugin's text domain.
			 */
			wp_die( esc_html( 'You do not have the rights to access this page.' ) );
		}

		/**
		 * @CODEREVIEW: This conditional expression can be combined into one to avoid level shifts to the left.
		 *  Why? Readability.
		 */
		if ( isset( $_POST['crawl'] ) && 'true' === $_POST['crawl'] && check_admin_referer( CH_SLUG . '_nonce' ) ) {
			/**
			 * @CODEREVIEW: This code is fragile.
			 * Why?
			 * When the submit button's text is internationalized and the language is not English,
			 * this conditional will no longer work.
			 */
			if ( isset( $_POST['submit'] ) && 'Launch a new crawl' === $_POST['submit'] ) {
				$crawl_result = $this->crawl_create();
				wp_clear_scheduled_hook( CH_SLUG . '_cron_hourly' );
				wp_schedule_event( time(), 'hourly', CH_SLUG . '_cron_hourly' );

				if ( true === $crawl_result ) {
					$crawl_success = true;
				} else {
					$error = $crawl_result;
				}
			}
		}

		include CH_PLUGIN_PATH . 'views/admin-page.php';
	}

	/**
	 * Create a sitemap.html from the crawl result in database.
	 *
	 * @return bool|string
	 */
	public function create_sitemap() {
		/**
		 * @CODEREVIEW: Add a view handler that:
		 *
		 * - processes the data for use in the view <- Why? Business logic should not be in the view
		 * - PHP views should be HTML with dynamic content (via embedded PHP)
		 * - Use output buffer wrapped around the view file load
		 */
		$content = include CH_PLUGIN_PATH . 'views/sitemap.php';

		/**
		 * @CODEREVIEW: Same comments as the `crawl_create()` method.
		 *
		 * @CODEREVIEW: Redundant code. It should be refactored into a method that handles this for both methods.
		 */
		if ( @file_put_contents( CH_PLUGIN_RESULT_PATH . 'sitemap.html', $content ) === false ) {  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Design is intended.
			return 'The sitemap.html couldn\'t be saved.<br/> Check the write rights of the folder result in the plugin.';
		}

		return true;
	}


	/**
	 * Start a crawl on homepage to get internal links.
	 *
	 * @return bool|string
	 */
	public function crawl_create() {
		/**
		 * @CODEREVIEW: This method is doing too many things. Methods should do one thing.
		 *
		 * I'd suggest breaking up the tasks into separate methods such as:
		 *      - Delete last crawl
		 *      - Delete sitemap.html
		 *      - Get home page content
		 *      - Extract links
		 *      - Save sitemap.html
		 */

		// Delete last crawl if exists.
		if ( false !== get_option( CH_SLUG ) && false === delete_option( CH_SLUG ) ) {
			return 'Couldn\'t remove last crawl from database.';
		}

		// Delete last sitemap.html if exists.
		if ( true === file_exists( CH_PLUGIN_RESULT_PATH . 'sitemap.html' ) &&
		false === @unlink( CH_PLUGIN_RESULT_PATH . 'sitemap.html' ) ) {  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Design is intended.
			return 'The sitemap.html couldn\'t be deleted.<br/> Check the write rights of the folder result in the plugin.';
		}

		$homepage_url = get_home_url();
		/**
		 * @CODEREVIEW: wp_remote_get().
		 */
		$content      = @file_get_contents( $homepage_url );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Design is intended.

		if ( false === $content ) {
			return 'The crawler couldn\'t access the homepage.';
		}

		/**
		 * @CODEREVIEW -- This code can be simplified. How?
		 *
		 * Consider what preg_match_all() returns:
		 *      - If there are no hyperlinks, it returns 0.
		 *      - If an error occurs, it returns false.
		 *      - If there are hyperlinks and no error, then it returns the number of matches.
		 *
		 * By checking if preg_match_all returns an empty(), then bail out with the error message.
		 */

		// Extract links.
		if ( false === preg_match_all( '<a href="(.+?)">', $content, $matches ) ||
			0 === count( $matches ) ||
			! isset( $matches[1] ) ||
			0 === count( $matches[1] ) ) {
			return 'The crawler found no link. Check the content your homepage.';
		}

		// Make an array only with internal links, remove internal anchors.
		foreach ( $matches[1] as $match ) {
			if ( '/' === substr( $match, 0, 1 ) || substr( $match, 0, strlen( $homepage_url ) ) === $homepage_url ) {
				$internal_links[] = $match;
			}
		}

		/**
		 * @CODEREVIEW: If there are no internal links, a PHP warning and notice are thrown:
		 *
		 * "Warning: count(): Parameter must be an array or an object that implements Countable"
		 * "Notice: Undefined variable: internal_links"
		 *
		 * Why? $internal_links is not initialized to [] before the foreach.
		 */

		if ( 0 === count( $internal_links ) ) {
			return 'The crawler found no internal link. Check the content your homepage.';
		}

		// Create array of crawl result : date + links.
		$crawl['date']  = gmdate( 'd/m/Y H:i:s' );
		$crawl['links'] = $internal_links;

		// Save in database the crawl result.
		/**
		 * @CODEREVIEW:
		 *
		 * 1. Security issue. Before saving content to the database, sanitize it.
		 *
		 * 2. serialize is unnecessary. It's also not advised due to security.
		 *
		 * Why is not advisable?
		 * | serialize() found. Serialized data has known
		 * | vulnerability problems with Object Injection. JSON is
		 * | generally a better approach for serializing data. See
		 * | https://www.owasp.org/index.php/PHP_Object_Injection
		 * | (WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize)
		 */
		if ( false === update_option( CH_SLUG, serialize( $crawl ) ) ) {
			return 'The crawl couldn\'t be saved in database.';
		}

		/**
		 * @CODEREVIEW This code is needed to add the result/ directory.
		 * Else, @file_put_contents() will fail.
		 *
		 * Why? file_put_contents() does not create the directory structure.
		 */
		if ( ! is_dir( CH_PLUGIN_RESULT_PATH ) ) {
			mkdir( CH_PLUGIN_RESULT_PATH );
		}

		// Save homepage.html.
		if ( false === @file_put_contents( CH_PLUGIN_RESULT_PATH . 'homepage.html', $content ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Design is intended.
			/**
			 * @CODEREVIEW: This message is misleading. Permissions may not be the issue. For example,
			 * without the mkdir() above, this message is displayed. However, it's not a permissions issue.
			 * Rather, it's a missing directory.
			 */
			return 'The homepage.html couldn\'t be saved.<br/> Check the write rights of the folder result in the plugin.';
		}

		// Create / update sitemap.html.
		$sitemap_result = $this->create_sitemap();
		if ( is_string( $sitemap_result ) ) {
			return $sitemap_result;
		}

		return true;
	}
}

/**
 * @CODEREVIEW: Instantiating an object in the same file with the class is not a best practice.
 *
 * Why? The code is not testable.
 */
new Crawler_Homepage();
