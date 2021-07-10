<?php
/**
 * The Third Party integration with Gravity Forms.
 *
 * @since       4.1.0
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class Gravity_Forms
{
    /**
     * Check if GF is enabled and disable LSCWP on gf-download and gf-signature URI
     *
     * @since 4.1.0 #900899 #827184
     */
    public static function preload()
    {
        if ( class_exists( 'GFCommon' ) ) {
            if (  isset( $_GET['gf-download'] ) || isset( $_GET['gf-signature'] ) ) {
               do_action( 'litespeed_disable_all', 'Stopped for Gravity Form' );
            }
        }
    }
}
