<?php

defined( 'ABSPATH' ) || exit;
if ( !function_exists( 'wpseedd_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpseedd_fs() {
        global $wpseedd_fs;
        if ( !isset( $wpseedd_fs ) ) {
            if ( !defined( 'WP_FS__PRODUCT_3004_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_3004_MULTISITE', true );
            }
            $wpseedd_fs = fs_dynamic_init( array(
                'id'             => '3004',
                'slug'           => 'wp-sheet-editor-edd-downloads',
                'premium_slug'   => 'wp-sheet-editor-edd-downloads-premium',
                'type'           => 'plugin',
                'public_key'     => 'pk_93994267e6fb3a5ce9326b01930a3',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                    'slug'       => 'wpseedd_welcome_page',
                    'first-path' => 'admin.php?page=wpseedd_welcome_page',
                    'support'    => false,
                ),
                'is_live'        => true,
            ) );
        }
        return $wpseedd_fs;
    }

    // Init Freemius.
    wpseedd_fs();
    // Signal that SDK was initiated.
    do_action( 'wpseedd_fs_loaded' );
}