<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * Configures the site link system for the network reporting
 */

// Adds the type of network connection to the site link system
add_filter( 'site_link_type', 'dt_training_site_link_type', 10, 1 );
function dt_training_site_link_type( $type ) {
    $type['training'] = __( 'Training' );
    return $type;
}

// Add the specific capabilities needed for the site to site linking.
add_filter( 'site_link_type_capabilities', 'dt_training_site_link_capabilities', 10, 1 );
function dt_training_site_link_capabilities( $args ) {
    if ( 'training' === $args['connection_type'] ) {
        $args['capabilities'][] = 'create_trainings';
        $args['capabilities'][] = 'update_any_trainings';
    }
    return $args;
}

