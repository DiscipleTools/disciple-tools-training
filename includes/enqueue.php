<?php

function dt_training_scripts() {
    if ( function_exists( 'dt_get_url_path' ) ) {

        $url_path = dt_get_url_path();
        if ( strpos( $url_path, 'trainings' ) !== false ){

            if ( ! empty( DT_Mapbox_API::get_key() ) ) {

                if ( class_exists( 'DT_Mapbox_API' ) ) {
                    DT_Mapbox_API::load_mapbox_header_scripts();
                    DT_Mapbox_API::load_mapbox_search_widget();
                }
                else if ( ! class_exists( 'DT_Mapbox_API' ) && file_exists( get_stylesheet_directory() . 'dt-mapping/geocode-api/mapbox-api.php' ) ) {
                    require_once( get_stylesheet_directory() . 'dt-mapping/geocode-api/mapbox-api.php' );

                    DT_Mapbox_API::load_mapbox_header_scripts();
                    DT_Mapbox_API::load_mapbox_search_widget();

                }
            }
        }
    }
}
add_action( 'wp_enqueue_scripts', 'dt_training_scripts', 999 );