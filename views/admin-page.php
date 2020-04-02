<?php
/**
 * Admin Page view
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

defined( 'ABSPATH' ) || die( 'Blocked' );

?>

<div class="wrap">
	<h2>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h2>
	<?php if ( isset( $error ) ) : ?>
		<div class="notice notice-error"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>
	<?php if ( isset( $crawl_success ) ) : ?>
		<div class="notice notice-success">
			<?php
			/**
			 * @CODEREVIEW: String literals should be internationalized through esc_html_e().
			 */
			?>
			The crawler homepage has run successfully.<br/>
		</div>
	<?php endif; ?>
	<form action="options-general.php?page=<?php echo esc_html( CH_SLUG ); ?>" method="post">
		<?php wp_nonce_field( CH_SLUG . '_nonce' ); ?>
		<input type="hidden" value="true" name="crawl" />
		<?php submit_button( 'Launch a new crawl' ); ?>
		<?php submit_button( 'Display crawl results' ); ?>
	</form>
	<?php
	/**
	 * @CODEREVIEW:
	 *    1. Processing code should be in the business logic and not in the view.
	 *    2. Code is fragile. How? When the submit button is internationalized and the language is
	 *       not English, this code will break.
	 */
	if ( ( isset( $_POST['submit'] ) && 'Display crawl results' === $_POST['submit'] &&
	check_admin_referer( CH_SLUG . '_nonce' ) ) ||
		isset( $crawl_success ) ) :
		?>
		<?php
		/**
		 * @CODEREVIEW: Don't use unserialize(). It's a security issue and it's unnecessary.
		 */
		?>
		<?php $plugin_option = get_option( CH_SLUG ); ?>
		<?php if ( false !== $plugin_option ) : ?>
		<?php
		/**
		 * @CODEREVIEW: Don't use unserialize(). It's a security issue and it's unnecessary.
		 */
		?>
			<?php $plugin_option = unserialize( $plugin_option ); ?>
			<?php if ( isset( $plugin_option['date'] ) && isset( $plugin_option['links'] ) ) : ?>
				<b>Crawl time:</b> <?php echo esc_html( $plugin_option['date'] ); ?><br/>
				<b>Results:</b> <br/>
				<?php foreach ( $plugin_option['links'] as $plugin_option_link ) : ?>
					<?php echo esc_html( $plugin_option_link ); ?><br/>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php else : ?>
		<?php
		/**
		 * @CODEREVIEW: String literals should be internationalized through esc_html_e().
		 */
		?>
			You didn't run a crawl yet :(.<br/>
			Press the button "Launch a new crawl" to start one.
		<?php endif; ?>
	<?php endif; ?>
</div>
