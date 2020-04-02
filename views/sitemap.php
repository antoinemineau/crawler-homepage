<?php
/**
 * Sitemap view
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

defined( 'ABSPATH' ) || die( 'Blocked' );

/**
 * @CODEREVIEW:
 * 1. The view should be HTML with PHP embedded in it for dynamic content.
 * 2. Processing code, i.e. get_option(), should be in the business logic and not in the view.
 *    The code here is simplified when moving the data prep into the business logic.
 *    For example:
 *       - business logic that handles the view:
 *
                $option = get_option( CH_SLUG, [] );
 			    $links  = ! empty( $option['links'] ) ? $option['links'] : [];
 *       - in the view:
                <?php foreach ( $links as $link ) :
					$link = esc_url( $link ); ?>
					<a href="<?php echo $link; ?>"><?php echo $link; ?></a><br>
				<?php endforeach; ?>
 * 3. Security issue. Links are not being escaped.
 *        - Notice in the example above, the link is escaped with esc_url().
 * 4. Don't use unserialize(). It's a security issue and unnecessary.
 */

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
