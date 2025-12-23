<?php
/**
 * License Management for LemonSqueezy
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_License class
 *
 * Handles license validation with LemonSqueezy API.
 * Plans: free, pro, business
 */
class VPB_License {

	/**
	 * LemonSqueezy API base URL
	 */
	const API_URL = 'https://api.lemonsqueezy.com/v1/licenses/';

	/**
	 * Option name for license data
	 */
	const OPTION_NAME = 'vpb_license';

	/**
	 * Transient name for license cache
	 */
	const CACHE_KEY = 'vpb_license_valid';

	/**
	 * Cache duration (24 hours)
	 */
	const CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Singleton instance
	 *
	 * @var VPB_License
	 */
	private static $instance = null;

	/**
	 * Current license data
	 *
	 * @var array
	 */
	private $license_data = null;

	/**
	 * Get singleton instance
	 *
	 * @return VPB_License
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->license_data = get_option( self::OPTION_NAME, array() );

		// Admin AJAX handlers.
		add_action( 'wp_ajax_vpb_activate_license', array( $this, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_vpb_deactivate_license', array( $this, 'ajax_deactivate_license' ) );
		add_action( 'wp_ajax_vpb_check_license', array( $this, 'ajax_check_license' ) );

		// Daily license check.
		add_action( 'vpb_daily_license_check', array( $this, 'validate_license' ) );
		if ( ! wp_next_scheduled( 'vpb_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'vpb_daily_license_check' );
		}
	}

	/**
	 * Get current plan name
	 *
	 * @return string free|pro|business
	 */
	public function get_plan() {
		if ( empty( $this->license_data['key'] ) ) {
			return 'free';
		}

		// Check cache first.
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		// Validate with API.
		$plan = $this->validate_license();

		return $plan ? $plan : 'free';
	}

	/**
	 * Check if license is active
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->license_data['key'] ) && 'free' !== $this->get_plan();
	}

	/**
	 * Check if PRO or higher
	 *
	 * @return bool
	 */
	public function is_pro() {
		$plan = $this->get_plan();
		return in_array( $plan, array( 'pro', 'business' ), true );
	}

	/**
	 * Check if BUSINESS
	 *
	 * @return bool
	 */
	public function is_business() {
		return 'business' === $this->get_plan();
	}

	/**
	 * Check if FREE
	 *
	 * @return bool
	 */
	public function is_free() {
		return 'free' === $this->get_plan();
	}

	/**
	 * Get license key (masked for display)
	 *
	 * @return string
	 */
	public function get_license_key_masked() {
		if ( empty( $this->license_data['key'] ) ) {
			return '';
		}
		$key = $this->license_data['key'];
		return substr( $key, 0, 8 ) . '...' . substr( $key, -4 );
	}

	/**
	 * Get license expiry date
	 *
	 * @return string|null
	 */
	public function get_expiry_date() {
		return isset( $this->license_data['expires_at'] ) ? $this->license_data['expires_at'] : null;
	}

	/**
	 * Activate license
	 *
	 * @param string $license_key License key from LemonSqueezy.
	 * @return array|WP_Error
	 */
	public function activate( $license_key ) {
		$license_key = sanitize_text_field( trim( $license_key ) );

		if ( empty( $license_key ) ) {
			return new WP_Error( 'empty_key', __( 'Please enter a license key.', 'visual-product-builder' ) );
		}

		// Call LemonSqueezy API to activate.
		$response = $this->api_request( 'activate', $license_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Determine plan from variant name or product.
		$plan = $this->determine_plan( $response );

		// Save license data.
		$this->license_data = array(
			'key'          => $license_key,
			'plan'         => $plan,
			'activated_at' => current_time( 'mysql' ),
			'expires_at'   => isset( $response['license_key']['expires_at'] ) ? $response['license_key']['expires_at'] : null,
			'instance_id'  => isset( $response['instance']['id'] ) ? $response['instance']['id'] : null,
			'customer'     => isset( $response['meta']['customer_name'] ) ? $response['meta']['customer_name'] : '',
		);

		update_option( self::OPTION_NAME, $this->license_data );
		set_transient( self::CACHE_KEY, $plan, self::CACHE_DURATION );
		delete_transient( 'vpb_license_error' );

		return array(
			'success' => true,
			'plan'    => $plan,
			'message' => sprintf(
				/* translators: %s: plan name */
				__( 'License activated! You now have access to %s features.', 'visual-product-builder' ),
				strtoupper( $plan )
			),
		);
	}

	/**
	 * Deactivate license
	 *
	 * @return array|WP_Error
	 */
	public function deactivate() {
		if ( empty( $this->license_data['key'] ) ) {
			return new WP_Error( 'no_license', __( 'No license to deactivate.', 'visual-product-builder' ) );
		}

		$instance_id = isset( $this->license_data['instance_id'] ) ? $this->license_data['instance_id'] : null;

		// Call LemonSqueezy API to deactivate.
		$response = $this->api_request( 'deactivate', $this->license_data['key'], $instance_id );

		// Clear license data regardless of API response.
		delete_option( self::OPTION_NAME );
		delete_transient( self::CACHE_KEY );
		$this->license_data = array();

		if ( is_wp_error( $response ) ) {
			// Still consider it a success since we cleared local data.
			return array(
				'success' => true,
				'message' => __( 'License deactivated locally.', 'visual-product-builder' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully.', 'visual-product-builder' ),
		);
	}

	/**
	 * Validate license with API
	 *
	 * @return string|false Plan name or false if invalid.
	 */
	public function validate_license() {
		if ( empty( $this->license_data['key'] ) ) {
			return false;
		}

		$response = $this->api_request( 'validate', $this->license_data['key'] );

		if ( is_wp_error( $response ) ) {
			set_transient( 'vpb_license_error', $response->get_error_message(), HOUR_IN_SECONDS );
			return false;
		}

		// Check if license is valid.
		if ( ! isset( $response['valid'] ) || ! $response['valid'] ) {
			set_transient( 'vpb_license_error', __( 'License is no longer valid.', 'visual-product-builder' ), HOUR_IN_SECONDS );
			return false;
		}

		$plan = $this->determine_plan( $response );
		set_transient( self::CACHE_KEY, $plan, self::CACHE_DURATION );
		delete_transient( 'vpb_license_error' );

		return $plan;
	}

	/**
	 * Make API request to LemonSqueezy
	 *
	 * @param string      $action      Action: activate, deactivate, validate.
	 * @param string      $license_key License key.
	 * @param string|null $instance_id Instance ID for deactivation.
	 * @return array|WP_Error
	 */
	private function api_request( $action, $license_key, $instance_id = null ) {
		$endpoint = self::API_URL . $action;

		$body = array(
			'license_key'   => $license_key,
			'instance_name' => $this->get_instance_name(),
		);

		if ( 'deactivate' === $action && $instance_id ) {
			$body['instance_id'] = $instance_id;
		}

		$response = wp_remote_post( $endpoint, array(
			'timeout' => 15,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				__( 'Could not connect to license server. Please try again.', 'visual-product-builder' )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code && 201 !== $code ) {
			$message = isset( $body['error'] ) ? $body['error'] : __( 'Invalid license key.', 'visual-product-builder' );
			return new WP_Error( 'invalid_license', $message );
		}

		return $body;
	}

	/**
	 * Determine plan from API response
	 *
	 * @param array $response API response.
	 * @return string Plan name.
	 */
	private function determine_plan( $response ) {
		// LemonSqueezy returns variant_name which should contain "pro" or "business".
		$variant_name = '';

		if ( isset( $response['meta']['variant_name'] ) ) {
			$variant_name = strtolower( $response['meta']['variant_name'] );
		} elseif ( isset( $response['license_key']['variant_name'] ) ) {
			$variant_name = strtolower( $response['license_key']['variant_name'] );
		}

		if ( strpos( $variant_name, 'business' ) !== false ) {
			return 'business';
		} elseif ( strpos( $variant_name, 'pro' ) !== false ) {
			return 'pro';
		}

		// Fallback: check product name.
		$product_name = '';
		if ( isset( $response['meta']['product_name'] ) ) {
			$product_name = strtolower( $response['meta']['product_name'] );
		}

		if ( strpos( $product_name, 'business' ) !== false ) {
			return 'business';
		} elseif ( strpos( $product_name, 'pro' ) !== false ) {
			return 'pro';
		}

		// Default to pro if we have a valid license but can't determine plan.
		return 'pro';
	}

	/**
	 * Get instance name for this site
	 *
	 * @return string
	 */
	private function get_instance_name() {
		return wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * AJAX: Activate license
	 */
	public function ajax_activate_license() {
		check_ajax_referer( 'vpb_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'visual-product-builder' ) ) );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		$result = $this->activate( $license_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Deactivate license
	 */
	public function ajax_deactivate_license() {
		check_ajax_referer( 'vpb_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'visual-product-builder' ) ) );
		}

		$result = $this->deactivate();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Check license status
	 */
	public function ajax_check_license() {
		check_ajax_referer( 'vpb_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'visual-product-builder' ) ) );
		}

		$plan = $this->validate_license();

		wp_send_json_success( array(
			'plan'       => $plan ? $plan : 'free',
			'is_active'  => $this->is_active(),
			'expires_at' => $this->get_expiry_date(),
		) );
	}

	/**
	 * Get plan limits
	 *
	 * @return array
	 */
	public function get_limits() {
		$plan = $this->get_plan();

		$limits = array(
			'free'     => array(
				'collections'             => 1,
				'elements_per_collection' => 50,
			),
			'pro'      => array(
				'collections'             => 3,
				'elements_per_collection' => 100,
			),
			'business' => array(
				'collections'             => -1, // Unlimited.
				'elements_per_collection' => -1, // Unlimited.
			),
		);

		return isset( $limits[ $plan ] ) ? $limits[ $plan ] : $limits['free'];
	}

	/**
	 * Check if a feature is available
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public function can_use( $feature ) {
		$features = array(
			// FREE features.
			'configurator'    => true,
			'preview'         => true,
			'add_to_cart'     => true,
			'undo_reset'      => true,
			// PRO features.
			'drag_drop'       => $this->is_pro(),
			'custom_css'      => $this->is_pro(),
			'bulk_operations' => $this->is_pro(),
			'png_hd_export'   => $this->is_pro(),
			'hide_branding'   => $this->is_pro(),
			// BUSINESS features.
			'support_image'   => $this->is_business(),
			'unlimited'       => $this->is_business(),
		);

		return isset( $features[ $feature ] ) ? $features[ $feature ] : false;
	}

	/**
	 * Cleanup on plugin uninstall
	 */
	public static function uninstall() {
		delete_option( self::OPTION_NAME );
		delete_transient( self::CACHE_KEY );
		delete_transient( 'vpb_license_error' );
		wp_clear_scheduled_hook( 'vpb_daily_license_check' );
	}
}
