<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FVCP_Admin {

	const PER_PAGE = 20;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_fvcp_delete', array( $this, 'delete_lead' ) );
		add_action( 'admin_post_fvcp_export', array( $this, 'export_csv' ) );
	}

	public function menu() {
		add_menu_page( 'Popup Leads', 'Popup Leads', 'manage_options', 'fvcp', array( $this, 'leads_page' ), 'dashicons-email-alt', 58 );
		add_submenu_page( 'fvcp', 'Leads', 'Leads', 'manage_options', 'fvcp', array( $this, 'leads_page' ) );
		add_submenu_page( 'fvcp', 'Popup Settings', 'Settings', 'manage_options', 'fvcp-settings', array( $this, 'settings_page' ) );
	}

	/* ---------------- Settings ---------------- */

	public function register_settings() {
		register_setting(
			'fvcp_group',
			'fvcp_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => fvcp_default_options(),
			)
		);
	}

	public function sanitize_options( $in ) {
		$defaults = fvcp_default_options();
		$old      = fvcp_get_options();
		$in       = is_array( $in ) ? $in : array();

		$out                 = array();
		$out['enabled']      = empty( $in['enabled'] ) ? 0 : 1;
		$out['heading']      = isset( $in['heading'] ) ? sanitize_text_field( $in['heading'] ) : $defaults['heading'];
		$out['subtext']      = isset( $in['subtext'] ) ? sanitize_text_field( $in['subtext'] ) : $defaults['subtext'];
		$out['button_text']  = isset( $in['button_text'] ) ? sanitize_text_field( $in['button_text'] ) : $defaults['button_text'];
		$out['success_text'] = isset( $in['success_text'] ) ? sanitize_text_field( $in['success_text'] ) : $defaults['success_text'];
		$out['delay']        = isset( $in['delay'] ) ? max( 0, min( 60, (int) $in['delay'] ) ) : $defaults['delay'];
		$out['cookie_days']  = isset( $in['cookie_days'] ) ? max( 1, min( 365, (int) $in['cookie_days'] ) ) : $defaults['cookie_days'];

		$email               = isset( $in['notify_email'] ) ? sanitize_email( $in['notify_email'] ) : '';
		$out['notify_email'] = is_email( $email ) ? $email : $defaults['notify_email'];

				$color               = isset( $in['accent_color'] ) ? sanitize_hex_color( $in['accent_color'] ) : '';
		$out['accent_color'] = $color ? $color : $old['accent_color'];

		return $out;
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o       = fvcp_get_options();
		$preview = add_query_arg( 'fvcp_preview', '1', home_url( '/' ) );
		?>
		<div class="wrap">
			<h1>Popup Settings</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'fvcp_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Popup enabled</th>
						<td><label><input type="checkbox" name="fvcp_options[enabled]" value="1" <?php checked( $o['enabled'], 1 ); ?>> Show the popup to first-time visitors</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-heading">Heading</label></th>
						<td><input id="fvcp-heading" type="text" class="regular-text" name="fvcp_options[heading]" value="<?php echo esc_attr( $o['heading'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-subtext">Sub-text</label></th>
						<td><input id="fvcp-subtext" type="text" class="large-text" name="fvcp_options[subtext]" value="<?php echo esc_attr( $o['subtext'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-button">Button text</label></th>
						<td><input id="fvcp-button" type="text" class="regular-text" name="fvcp_options[button_text]" value="<?php echo esc_attr( $o['button_text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-success">Thank-you message</label></th>
						<td><input id="fvcp-success" type="text" class="large-text" name="fvcp_options[success_text]" value="<?php echo esc_attr( $o['success_text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-delay">Show after (seconds)</label></th>
						<td><input id="fvcp-delay" type="number" min="0" max="60" name="fvcp_options[delay]" value="<?php echo esc_attr( $o['delay'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-days">Don't show again for (days)</label></th>
						<td><input id="fvcp-days" type="number" min="1" max="365" name="fvcp_options[cookie_days]" value="<?php echo esc_attr( $o['cookie_days'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-email">Send leads to</label></th>
						<td><input id="fvcp-email" type="email" class="regular-text" name="fvcp_options[notify_email]" value="<?php echo esc_attr( $o['notify_email'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="fvcp-color">Accent colour</label></th>
						<td><input id="fvcp-color" type="text" name="fvcp_options[accent_color]" value="<?php echo esc_attr( $o['accent_color'] ); ?>" placeholder="#2563eb"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p><a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener">Preview popup on your site</a>
			<span class="description">Opens your homepage and shows the popup immediately (only for logged-in admins).</span></p>
		</div>
		<?php
	}

	/* ---------------- Leads ---------------- */

	public function leads_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$table = fvcp_table();

		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$offset = ( $paged - 1 ) * self::PER_PAGE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", self::PER_PAGE, $offset ) );
		// phpcs:enable

		$pages = (int) ceil( $total / self::PER_PAGE );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Popup Leads</h1>
			<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fvcp_export' ), 'fvcp_export' ) ); ?>">Export CSV</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>Lead deleted.</p></div>
			<?php endif; ?>

			<table class="widefat striped">
				<thead>
					<tr><th>Date (UTC)</th><th>Name</th><th>Email</th><th>Message</th><th>Page</th><th></th></tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="6">No leads yet. Use "Preview popup" in Settings to test the form.</td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->created_at ); ?></td>
							<td><?php echo esc_html( $r->name ); ?></td>
							<td><a href="mailto:<?php echo esc_attr( $r->email ); ?>"><?php echo esc_html( $r->email ); ?></a></td>
							<td><?php echo esc_html( $r->message ); ?></td>
							<td><?php echo $r->page_url ? '<a href="' . esc_url( $r->page_url ) . '" target="_blank" rel="noopener">view</a>' : ''; ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fvcp_delete&id=' . (int) $r->id ), 'fvcp_delete_' . (int) $r->id ) ); ?>" onclick="return confirm('Delete this lead?');">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $paged,
								'total'   => $pages,
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function delete_lead() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( ! current_user_can( 'manage_options' ) || ! $id ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'fvcp_delete_' . $id );

		global $wpdb;
		$wpdb->delete( fvcp_table(), array( 'id' => $id ), array( '%d' ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'fvcp', 'deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'fvcp_export' );

		global $wpdb;
		$table = fvcp_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT created_at, name, email, message, page_url FROM {$table} ORDER BY id DESC", ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="popup-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Date (UTC)', 'Name', 'Email', 'Message', 'Page' ), ',', '"', '' );
		foreach ( $rows as $row ) {
			// Neutralise spreadsheet formula injection.
			foreach ( $row as $k => $v ) {
				if ( isset( $v[0] ) && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
					$row[ $k ] = "'" . $v;
				}
			}
			fputcsv( $out, $row, ',', '"', '' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
