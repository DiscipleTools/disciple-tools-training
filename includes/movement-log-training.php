<?php

/**
 * Test if Network Dashboard Exists and is Active
 */
$active_plugins = get_option( 'active_plugins' );
if ( ! in_array('disciple-tools-network-dashboard/disciple-tools-network-dashboard.php', $active_plugins ) ){
    return false;
}


/**
 * REGISTER ACTIONS (AND CATEGORIES)
 */
add_action( 'dt_network_dashboard_register_actions', 'dt_network_dashboard_register_training_actions', 10, 1 );
function dt_network_dashboard_register_training_actions( $actions ){

    $actions['training_new'] = [
        'key' => 'training_new',
        'label' => 'New Training',
        'message_pattern' => [

        ]
    ];

    $actions['training_in_progress'] = [
        'key' => 'training_in_progress',
        'label' => 'Training Started',
        'message_pattern' => [

        ]
    ];

    $actions['training_completed'] = [
        'key' => 'training_completed',
        'label' => 'Training Completed',
        'message_pattern' => [

        ]
    ];

    return $actions;
}

/**
 * CREATE LOG
 */
add_action( 'dt_post_created', 'dt_network_dashboard_write_log_new_trainings', 10, 3 );
function dt_network_dashboard_write_log_new_trainings( $post_type, $post_id, $initial_fields ){

    if ( $post_type === 'trainings' ) {

        $location = DT_Network_Activity_Log::get_location_details( $post_id );
        $data = [
            [
                'site_id' => dt_network_site_id(),
                'site_object_id' => $post_id,
                'action' => 'training_new',
                'category' => '',
                'location_type' => $location['location_type'], // id, grid, lnglat, no_location
                'location_value' => $location['location_value'],
                'payload' => [
                    'language' => get_locale(),
                ],
                'timestamp' => time()
            ]
        ];

        DT_Network_Activity_Log::insert_log($data);
    }

}

add_action( 'dt_post_updated', 'dt_network_dashboard_write_log_update_trainings', 10, 5 );
function dt_network_dashboard_write_log_update_trainings( $post_type, $post_id, $initial_fields, $existing_post, $post ){

    if ( $post_type === 'trainings' && isset( $initial_fields['status'] ) ) {

        if ( 'in_progress' === $initial_fields['status']
            || 'complete' === $initial_fields['status'] ){
            $location = DT_Network_Activity_Log::get_location_details( $post_id );
            $data = [
                [
                    'site_id' => dt_network_site_id(),
                    'site_object_id' => $post_id,
                    'action' => 'trainings_'. $initial_fields['status'],
                    'category' => '',
                    'location_type' => $location['location_type'], // id, grid, lnglat, no_location
                    'location_value' => $location['location_value'],
                    'payload' => [
                        'language' => get_locale(),
                    ],
                    'timestamp' => time()
                ]
            ];

            DT_Network_Activity_Log::insert_log($data);
        }

    }

}

/**
 * READ LOG
 */
add_filter( 'dt_network_dashboard_build_message', 'dt_network_dashboard_read_log_trainings', 10, 1 );
function dt_network_dashboard_read_log_trainings( $activity_log ){

    foreach( $activity_log as $index => $log ){

        /* training object created */
        if ( 'training_new' === $log['action'] ) {
            $activity_log[$index]['message'] = $log['site_name'] . ' is reporting a new training planned';
        }

        /* training status changed to start */
        if ( 'training_in_progress' === $log['action'] ) {
            $activity_log[$index]['message'] = $log['site_name'] . ' is reporting a training is starting';
        }

        /* training completed */
        if ( 'training_completed' === $log['action'] ) {
            $activity_log[$index]['message'] = $log['site_name'] . ' is reporting a training has completed';
        }


    }

    return $activity_log;
}