<?php
/**
 * Sitemap view
 */

defined( 'ABSPATH' ) || die( 'Blocked' );

$sitemap = '<html><head><title>Sitemap</title></head><body><h2>Sitemap</h2>';

$plugin_option = get_option( CH_SLUG );
if ( false !== $plugin_option ) {
	$plugin_option = unserialize( $plugin_option );
	foreach ( $plugin_option['links'] as $plugin_option_link ) {
		$sitemap .= '<a href="' . $plugin_option_link . '">' . $plugin_option_link . '</a><br/>';
	}
}

$sitemap .= '</body></html>';


return $sitemap;
