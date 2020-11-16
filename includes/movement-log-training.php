<?php

/**
 * Test if Network Dashboard Exists and is Active
 * if true, continue; if false, stop load.
 */
$active_plugins = get_option( 'active_plugins' );
if ( ! in_array( 'disciple-tools-network-dashboard/disciple-tools-network-dashboard.php', $active_plugins ) ){
    return false;
}



/**************************************************************************************************************
 * CREATE AND READ NETWORK DASHBOARD ACTIVITY LOG SECTION
 *
 * The following actions provide the support to register action key, create the training activity log, and
 * upgrade the activity log for missing items, and generate the proper message to be displayed from the log.
 *
 * dt_network_dashboard_register_actions
 * This action registers the action keys that are to be used throughout the system. This is required.
 *
 * dt_post_created & dt_post_updated
 * The post_created & post_updated actions fire after a post is created or updated and the action must be filtered
 * to match the intended event. Once the event is identified, the location details can be collected, the data array
 * constructed, and the log inserted.
 *
 * dt_network_dashboard_build_message
 * This action interprets the key into the message to be displayed. This can be expanded to construct the message
 *
 * dt_network_dashboard_rebuild_activity
 * This action support the administrative area when the process to rebuild/resync the local database is triggered.
 * Not all events might be able to be recreated, but nearly all that leave an activity log record in the standard
 * log system.
 *
 **************************************************************************************************************/
/**
 * REGISTER ACTIONS (AND CATEGORIES)
 */
add_action( 'dt_network_dashboard_register_actions', 'dt_network_dashboard_register_training_actions', 10, 1 );
function dt_network_dashboard_register_training_actions( $actions ){

    $actions['training_new'] = [
        'key' => 'training_new',
        'label' => 'New Training',
        'message_pattern' => []
    ];

    $actions['training_in_progress'] = [
        'key' => 'training_in_progress',
        'label' => 'Training Started',
        'message_pattern' => []
    ];

    $actions['training_completed'] = [
        'key' => 'training_completed',
        'label' => 'Training Completed',
        'message_pattern' => []
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

        DT_Network_Activity_Log::insert_log( $data );
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

            DT_Network_Activity_Log::insert_log( $data );
        }
    }
}

/**
 * READ LOG
 */
add_filter( 'dt_network_dashboard_build_message', 'dt_network_dashboard_read_log_trainings', 10, 1 );
function dt_network_dashboard_read_log_trainings( $activity_log ){

    foreach ( $activity_log as $index => $log ){

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
add_action( 'dt_network_dashboard_rebuild_activity', 'dt_network_dashboard_rebuild_training_activity', 10, 1 );
function dt_network_dashboard_rebuild_training_activity(){
    global $wpdb;
    $site_id = dt_network_site_id();

    /**
     * New
     */
    $training_new = $wpdb->get_results( $wpdb->prepare( "
                SELECT 
                   a.object_id as site_object_id, 
                   'training_new' as action, 
                   a.hist_time as timestamp
                FROM $wpdb->dt_activity_log as a
                LEFT JOIN $wpdb->dt_movement_log as m
                    ON a.object_id=m.site_object_id
                    AND m.site_id = %s
                    AND m.action = 'training_new'
                WHERE a.object_type = 'trainings' 
                    AND a.action = 'created'
                    AND m.id IS NULL
                ORDER BY a.object_id;", $site_id ),
    ARRAY_A );
    DT_Network_Activity_Log::local_bulk_insert( $training_new );

    /**
     * In Progress
     */
    $training_in_progress = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
               DISTINCT( a.object_id ) as site_object_id, 
               'training_in_progress' as action, 
               a.hist_time as timestamp
            FROM $wpdb->dt_activity_log as a
            LEFT JOIN $wpdb->dt_movement_log as m
                ON a.object_id=m.site_object_id
                AND m.site_id = %s
                AND m.action = 'training_in_progress'
            WHERE a.object_type = 'trainings' 
                AND a.action = 'field_update'
                AND a.meta_key = 'status'
                AND a.meta_value = 'in_progress'
                AND m.id IS NULL
            ORDER BY a.object_id;", $site_id ),
    ARRAY_A );
    DT_Network_Activity_Log::local_bulk_insert( $training_in_progress );

    /**
     * Completed
     */
    $training_completed = $wpdb->get_results( $wpdb->prepare( "
                 SELECT 
               DISTINCT( a.object_id ) as site_object_id, 
               'training_completed' as action, 
               a.hist_time as timestamp
            FROM $wpdb->dt_activity_log as a
            LEFT JOIN $wpdb->dt_movement_log as m
                ON a.object_id=m.site_object_id
                AND m.site_id = %s
                AND m.action = 'training_completed'
            WHERE a.object_type = 'trainings' 
                AND a.action = 'field_update'
                AND a.meta_key = 'status'
                AND a.meta_value = 'complete'
                AND m.id IS NULL
            ORDER BY a.object_id;", $site_id ),
    ARRAY_A );
    DT_Network_Activity_Log::local_bulk_insert( $training_completed );
}


/**************************************************************************************************************
 * Loading Custom Metrics Section
 *
 * This action fires after the network dashboard plugin is loaded and can be used to guarantee resources needed
 * are available.
 *
 **************************************************************************************************************/

add_action( 'dt_network_dashboard_loaded', function(){ // only load once the

} );