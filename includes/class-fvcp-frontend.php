<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FVCP_Frontend {

	const RATE_LIMIT_PER_HOUR = 5;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_footer', array( $this, 'render_markup' ) );
		add_action( 'wp_ajax_fvcp_submit', array( $this, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_fvcp_submit', array( $this, 'handle_submit' ) );
	}

	private function is_preview() {
		return isset( $_GET['fvcp_preview'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	private function should_show() {
		$opts = fvcp_get_options();
		if ( empty( $opts['enabled'] ) ) {
			return false;
		}
		return true;
	}

	public function enqueue() {
		if ( ! $this->should_show() ) {
			return;
		}

		$opts = fvcp_get_options();

		wp_enqueue_style( 'fvcp', FVCP_URL . 'assets/popup.css', array(), FVCP_VERSION );
		wp_enqueue_script( 'fvcp', FVCP_URL . 'assets/popup.js', array(), FVCP_VERSION, true );

		$accent = sanitize_hex_color( $opts['accent_color'] );
		if ( ! $accent ) {
			$accent = '#2563eb';
		}
		wp_add_inline_style( 'fvcp', ':root{--fvcp-accent:' . $accent . ';}' );

		wp_localize_script(
			'fvcp',
			'FVCP',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'delay'      => (int) $opts['delay'],
				'cookieDays' => (int) $opts['cookie_days'],
				'preview'    => $this->is_preview(),
				'error'      => __( 'Something went wrong. Please try again.', 'first-visit-contact-popup' ),
			)
		);
	}

	public function render_markup() {
		if ( ! $this->should_show() ) {
			return;
		}
		$opts = fvcp_get_options();
		?>
		<div id="fvcp-overlay" class="fvcp-overlay" hidden>
			<div class="fvcp-modal" role="dialog" aria-modal="true" aria-labelledby="fvcp-title">
				<button type="button" class="fvcp-close" aria-label="<?php esc_attr_e( 'Close', 'first-visit-contact-popup' ); ?>">&times;</button>
				<h2 id="fvcp-title" class="fvcp-title"><?php echo esc_html( $opts['heading'] ); ?></h2>
				<p class="fvcp-sub"><?php echo esc_html( $opts['subtext'] ); ?></p>
				<form id="fvcp-form" class="fvcp-form" novalidate>
					<label class="fvcp-label"><?php esc_html_e( 'Name', 'first-visit-contact-popup' ); ?>
						<input type="text" name="name" maxlength="100" required autocomplete="name">
					</label>
					<label class="fvcp-label"><?php esc_html_e( 'Email', 'first-visit-contact-popup' ); ?>
						<input type="email" name="email" maxlength="190" required autocomplete="email">
					</label>
					<label class="fvcp-label"><?php esc_html_e( 'Message (optional)', 'first-visit-contact-popup' ); ?>
						<textarea name="message" rows="3" maxlength="1000"></textarea>
					</label>
					<div class="fvcp-hp" aria-hidden="true">
						<label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					</div>
					<p class="fvcp-error" role="alert" hidden></p>
					<button type="submit" class="fvcp-submit"><?php echo esc_html( $opts['button_text'] ); ?></button>
				</form>
				<p class="fvcp-success" role="status" hidden><?php echo esc_html( $opts['success_text'] ); ?></p>
			</div>
		</div>
		<?php
	}

	public function handle_submit() {
		// Honeypot: real people never fill this in.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success(); // Pretend success so bots move on.
		}

		// Too-fast submissions are almost always bots.
		$elapsed = isset( $_POST['elapsed'] ) ? (int) $_POST['elapsed'] : 0;
		if ( $elapsed < 1500 ) {
			wp_send_json_error( array( 'message' => __( 'Please take a moment and try again.', 'first-visit-contact-popup' ) ) );
		}

		// Simple per-IP rate limit (IP is hashed, never stored).
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'fvcp_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT_PER_HOUR ) {
			wp_send_json_error( array( 'message' => __( 'Too many submissions. Please try again later.', 'first-visit-contact-popup' ) ) );
		}

		$name    = isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 100 ) : '';
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$message = isset( $_POST['message'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 0, 1000 ) : '';
		$page    = isset( $_POST['page'] ) ? esc_url_raw( wp_unslash( $_POST['page'] ) ) : '';
		$page    = mb_substr( $page, 0, 255 );

		if ( '' === $name || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your name and a valid email.', 'first-visit-contact-popup' ) ) );
		}

		global $wpdb;
		$inserted = $wpdb->insert(
			fvcp_table(),
			array(
				'name'       => $name,
				'email'      => $email,
				'message'    => $message,
				'page_url'   => $page,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Could not save your message. Please try again.', 'first-visit-contact-popup' ) ) );
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		$opts = fvcp_get_options();
		$to   = is_email( $opts['notify_email'] ) ? $opts['notify_email'] : get_option( 'admin_email' );

		$subject = sprintf(
			/* translators: 1: site name 2: visitor name */
			__( '[%1$s] New popup lead from %2$s', 'first-visit-contact-popup' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$name
		);
		$body = sprintf(
			"Name: %s\nEmail: %s\nPage: %s\n\nMessage:\n%s\n",
			$name,
			$email,
			$page,
			'' !== $message ? $message : '(none)'
		);
		wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

		wp_send_json_success();
	}
}
