<?php

function dt_training_scripts() {
    if ( function_exists( 'dt_get_url_path') ) {

        $url_path = dt_get_url_path();
        if ( strpos( $url_path, 'trainings' ) !== false ){

            wp_enqueue_script( 'trainings', plugin_dir_url(__FILE__) . '/trainings.js', array( 'jquery' ), filemtime( plugin_dir_path(__FILE__) . '/trainings.js' ), true );
            wp_localize_script(
                "trainings", "dtTrainings", array(
                    "translations" => array(
                        "cancel" => esc_html__( 'Cancel', 'zume' ),
                        "current:" => esc_html__( 'Current Step:', 'zume' ),
                        "pagination" => esc_html__( 'Cancel', 'zume' ),
                        "finish" => esc_html__( 'Finish', 'zume' ),
                        "next" => esc_html__( 'Next', 'zume' ),
                        "previous" => esc_html__( 'Previous', 'zume' ),
                        "loading" => esc_html__( 'Loading...', 'zume' ),
                    )
                )
            );
        }

    }
}
add_action( 'wp_enqueue_scripts', 'dt_training_scripts', 999 );