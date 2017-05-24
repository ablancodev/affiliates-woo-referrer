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
 * Version: 1.0.0
 */

if ( !defined('ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_WOO_REFERRER_PLUGIN_URL', WP_PLUGIN_URL . '/affiliates-woo-referrer' );
define( 'AFFILIATES_WOO_REFERRER_PLUGIN_DOMAIN', 'affiliates-woo-referrer' );


class Affiliates_Woo_Referrer {

	public static function init() {
		//add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action('woocommerce_checkout_process', array( __CLASS__, 'woocommerce_checkout_process' ));
		add_filter('woocommerce_checkout_fields', array( __CLASS__, 'woocommerce_checkout_fields'));
		
	}

	public static function wp_init() {
		// woocommerce actions
		add_action('woocommerce_checkout_process', array( __CLASS__, 'woocommerce_checkout_process' ));
		add_filter('woocommerce_checkout_fields', array( __CLASS__, 'woocommerce_checkout_fields'));
	}

	// Woocommerce

	public static function woocommerce_checkout_process () {
		if (isset($_POST['affiliates-referrer'])) {
			$affiliates = affiliates_get_affiliates( true );
			$affiliates_select = '';
			if ( !empty( $affiliates ) ) {
				$affiliate_id = null;
				foreach ( $affiliates as $affiliate ) {
					if ( ( isset( $affiliate['email'] ) ) && ( $affiliate['email'] == trim( $_POST['affiliates-referrer'] ) ) ) {
						$affiliate_id = $affiliate['affiliate_id'];
					}
				}
				if ( $affiliate_id !== null ) {
					$encoded_id = affiliates_encode_affiliate_id( $affiliate_id );
					$days = apply_filters( 'affiliates_cookie_timeout_days', get_option( 'aff_cookie_timeout_days', AFFILIATES_COOKIE_TIMEOUT_DAYS ), $affiliate_id );
					if ( $days > 0 ) {
						$expire = time() + AFFILIATES_COOKIE_TIMEOUT_BASE * $days;
					} else {
						$expire = 0;
					}
					if ( class_exists( 'Affiliates_Campaign' ) && method_exists( 'Affiliates_Campaign', 'evaluate' ) ) {
						if ( !empty( $_REQUEST['cmid'] ) ) {
							if ( $cmid = Affiliates_Campaign::evaluate( $_REQUEST['cmid'], $affiliate_id ) ) {
								$encoded_id .= '.' . $cmid;
							}
						}
					}
					$affiliates_request_encoded_id = $encoded_id;
					$hit = affiliates_record_hit( $affiliate_id );
					setcookie(
							AFFILIATES_COOKIE_NAME,
							$encoded_id,
							$expire,
							SITECOOKIEPATH,
							COOKIE_DOMAIN
							);
					
					// Update the current $_COOKIE variable
					if ( isset( $_COOKIE[AFFILIATES_COOKIE_NAME] ) ) {
						$_COOKIE[AFFILIATES_COOKIE_NAME] = $encoded_id;
					}
				}
			}
		}
	}


	public static function woocommerce_checkout_fields ($fields) {

		$fields['order']['affiliates-referrer'] = array(
			'type' => 'text',
			'label' => __('Referred by', AFFILIATES_WOO_REFERRER_PLUGIN_DOMAIN)
		);
		$fields['order']['affiliates-referrer']['placeholder'] = 'your_affiliate@example.com';
		
		require_once( AFFILIATES_CORE_LIB . '/class-affiliates-service.php' );
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
}
Affiliates_Woo_Referrer::init();
