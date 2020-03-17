<?php
/**
 * Location Grid List API
 */

if ( defined( 'ABSPATH' ) ) { exit; }
/**
 * @link https://stackoverflow.com/questions/45421976/wordpress-rest-api-slow-response-time
 *       https://deliciousbrains.com/wordpress-rest-api-vs-custom-request-handlers/
 *
 * @version 1.0 Initialization
 */

define( 'DOING_AJAX', true );

//Tell WordPress to only load the basics
define( 'SHORTINIT', 1 );

// Setup
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
    exit( 'missing server info' );
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'; //@phpcs:ignore

if ( ! defined( 'WP_CONTENT_URL' ) ) {
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}

$dir = __DIR__;
$mapping_url = ABSPATH . 'wp-content/themes/' . get_option( 'template' ) . '/dt-mapping/';
require_once( $mapping_url . 'geocode-api/location-grid-geocoder.php' );


// register global database
global $wpdb;
$wpdb->dt_location_grid = $wpdb->prefix . 'dt_location_grid';
$wpdb->dt_location_grid_meta = $wpdb->prefix . 'dt_location_grid_meta';

$geocoder = new Location_Grid_Geocoder();

// geocodes longitude and latitude and returns json array of location_grid record
if ( isset( $_GET['type'] ) && isset( $_GET['longitude'] ) && isset( $_GET['latitude'] ) && isset( $_GET['nonce'] ) ) :

    // return json grid_id result from longitude/latitude
    if ( $_GET['type'] === 'geocode' ) {

        $level = null;
        if ( isset( $_GET['level'] ) ) {
            $level = sanitize_text_field( wp_unslash( $_GET['level'] ) );
        }
        $country_code = null;
        if ( isset( $_GET['country_code'] ) ) {
            $country_code = sanitize_text_field( wp_unslash( $_GET['country_code'] ) );
        }
        $longitude = sanitize_text_field( wp_unslash( $_GET['longitude'] ) );
        $latitude  = sanitize_text_field( wp_unslash( $_GET['latitude'] ) );

        require_once ( $mapping_url . 'mapping-queries.php' );

        if ( 'world' === $level ) {
            $response = Disciple_Tools_Mapping_Queries::get_children_by_grid_id( 1 );
        } else {
            $response = $geocoder->get_grid_id_by_lnglat( $longitude, $latitude, $country_code, $level );
        }

        header( 'Content-type: application/json' );
        echo json_encode( $response );
    }

endif; // html

exit();
