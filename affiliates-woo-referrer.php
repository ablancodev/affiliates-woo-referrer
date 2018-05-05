<?php
/**
 * affiliates-woo-referrer.php
 *
 * Copyright (c) 2015 www.eggemplo.com
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author eggemplo
 * @package affiliates-woo-referrer
 * @since 1.0.0
 *
 * Plugin Name: Affiliates Woo Referrer
 * Plugin URI: http://www.eggemplo.com
 * Description: Add a referrer field on the Woocommerce checkout page
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com/
 * Version: 1.0.1
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_WOO_REFERRER_PLUGIN_URL', WP_PLUGIN_URL . '/affiliates-woo-referrer' );
define( 'AFFILIATES_WOO_REFERRER_PLUGIN_DOMAIN', 'affiliates-woo-referrer' );

/**
 * Affiliates Woo Referrer Class
 */
class Affiliates_Woo_Referrer {

	/**
	 * Initialize the plugin
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'woocommerce_checkout_order_processed' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'woocommerce_checkout_fields' ) );
	}

	/**
	 * Sets the affiliate cookie if affiliate is applicable
	 *
	 * @param string $order_id
	 */
	public static function woocommerce_checkout_order_processed( $order_id ) {
		$affiliate_id = null;
		if ( isset( $_POST['affiliates-referrer'] ) ) {
			if ( affiliates_check_affiliate_id( trim( $_POST['affiliates-referrer'] ) ) ) {
				$affiliate_id = trim( $_POST['affiliates-referrer'] );
			} else {
				$affiliates = affiliates_get_affiliates( true );
				$affiliates_select = '';
				if ( !empty( $affiliates ) ) {
					foreach ( $affiliates as $affiliate ) {
						if ( ( isset( $affiliate['email'] ) ) && ( $affiliate['email'] == trim( $_POST['affiliates-referrer'] ) ) ) {
							$affiliate_id = $affiliate['affiliate_id'];
						}
					}
				}
			}
			if ( $affiliate_id !== null ) {
				if ( class_exists( 'Affiliates_WooCommerce_Integration' ) ) {
					Affiliates_WooCommerce_Integration::process_order( $order_id, array( $affiliate_id ) );
				}
			}
		}
	}

	/**
	 * Adds the affiliate referrer field on checkout
	 *
	 * @param array $fields
	 * @return array $fields
	 */
	public static function woocommerce_checkout_fields( $fields ) {

		$fields['order']['affiliates-referrer'] = array(
			'type' => 'text',
			'label' => __( 'Referred by', 'affiliates-woo-referrer' )
		);
		$fields['order']['affiliates-referrer']['placeholder'] = 'your_affiliate@example.com';

		require_once AFFILIATES_CORE_LIB . '/class-affiliates-service.php';
		$affiliate_id = Affiliates_Service::get_referrer_id();
		if ( $affiliate_id ) {
			if ( $affiliate = affiliates_get_affiliate( $affiliate_id ) ) {
				if ( $affiliate['email'] ) {
					$fields['order']['affiliates-referrer']['default'] = $affiliate['email'];
				}
			}
		}

		return $fields;
	}
} Affiliates_Woo_Referrer::init();
