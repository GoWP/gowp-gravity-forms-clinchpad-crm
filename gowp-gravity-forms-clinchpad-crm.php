<?php
/*
Plugin Name: Gravity Forms ClinchPad CRM Add-On
Description: Integrate Gravity Forms with ClinchPad CRM
Version: 1.0.0
Author: GoWP
Author URI: https://www.gowp.com
*/

define( 'GF_CLINCHPAD_CRM_VERSION', '1.0.0' );

add_action( 'gform_loaded', array( 'GF_ClinchPad_CRM_Bootstrap', 'load' ), 5 );

class GF_ClinchPad_CRM_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfclinchpad.php' );

        GFAddOn::register( 'GFClinchPad' );
    }

}

function gf_simple_addon() {
    return GFClinchPad::get_instance();
}