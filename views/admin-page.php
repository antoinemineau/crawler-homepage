<?php
/**
 * Admin Page view
 */

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
	if ( ( isset( $_POST['submit'] ) && 'Display crawl results' === $_POST['submit'] &&
	check_admin_referer( CH_SLUG . '_nonce' ) ) ||
		isset( $crawl_success ) ) :
		?>
		<?php $plugin_option = get_option( CH_SLUG ); ?>
		<?php if ( false !== $plugin_option ) : ?>
			<?php $plugin_option = unserialize( $plugin_option ); ?>
			<?php if ( isset( $plugin_option['date'] ) && isset( $plugin_option['links'] ) ) : ?>
				<b>Crawl time:</b> <?php echo esc_html( $plugin_option['date'] ); ?><br/>
				<b>Results:</b> <br/>
				<?php foreach ( $plugin_option['links'] as $plugin_option_link ) : ?>
					<?php echo esc_html( $plugin_option_link ); ?><br/>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php else : ?>
			You didn't run a crawl yet :(.<br/>
			Press the button "Launch a new crawl" to start one.
		<?php endif; ?>
	<?php endif; ?>
</div>
