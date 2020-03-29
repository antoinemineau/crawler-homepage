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
define( 'CH_PLUGIN_RESULT_PATH', plugin_dir_path( __FILE__ ) . 'result/' );
define( 'CH_PLUGIN_RESULT_URL', plugin_dir_url( __FILE__ ) . 'result/' );


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
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( CH_SLUG . '_cron_hourly', [ $this, 'crawl_create' ] );
		add_action( 'wp_footer', [ $this, 'sitemap_show_on_website' ], 100 );
	}

	/**
	 * Show a link on the website to the generated sitemap.
	 */
	public function sitemap_show_on_website() {

		echo '<a target="_blank" href="' . esc_url( CH_PLUGIN_RESULT_URL ) . 'sitemap.html">Sitemap</a>';
	}

	/**
	 * Add options page.
	 */
	public function add_options_page() {
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
			wp_die( esc_html( 'You do not have the rights to access this page.' ) );
		}

		if ( isset( $_POST['crawl'] ) && 'true' === $_POST['crawl'] && check_admin_referer( CH_SLUG . '_nonce' ) ) {
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
		$content = include CH_PLUGIN_PATH . 'views/sitemap.php';

		if ( @file_put_contents( CH_PLUGIN_RESULT_PATH . 'sitemap.html', $content ) === false ) {
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
		// Delete last crawl if exists.
		if ( false !== get_option( CH_SLUG ) && false === delete_option( CH_SLUG ) ) {
			return 'Couldn\'t remove last crawl from database.';
		}

		// Delete last sitemap.html if exists.
		if ( true === file_exists( CH_PLUGIN_RESULT_PATH . 'sitemap.html' ) &&
		false === @unlink( CH_PLUGIN_RESULT_PATH . 'sitemap.html' ) ) {
			return 'The sitemap.html couldn\'t be deleted.<br/> Check the write rights of the folder result in the plugin.';
		}

		$homepage_url = get_home_url();
		$content      = @file_get_contents( $homepage_url );

		if ( false === $content ) {
			return 'The crawler couldn\'t access the homepage.';
		}

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

		if ( 0 === count( $internal_links ) ) {
			return 'The crawler found no internal link. Check the content your homepage.';
		}

		// Create array of crawl result : date + links.
		$crawl['date']  = gmdate( 'd/m/Y H:i:s' );
		$crawl['links'] = $internal_links;

		// Save in database the crawl result.
		if ( false === update_option( CH_SLUG, serialize( $crawl ) ) ) {
			return 'The crawl couldn\'t be saved in database.';
		}

		// Save homepage.html.
		if ( false === @file_put_contents( CH_PLUGIN_RESULT_PATH . 'homepage.html', $content ) ) {
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

new Crawler_Homepage();
