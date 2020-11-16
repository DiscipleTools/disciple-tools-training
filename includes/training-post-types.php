<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class DT_Training_Post_Type {

    public $post_type = "trainings";

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 );

        //setup tiles and fields
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        // hooks
        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
        add_filter( "dt_post_update_fields", [ $this, "dt_post_update_fields" ], 10, 3 );
        add_action( "dt_post_created", [ $this, "dt_post_created" ], 10, 3 );
        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );

//        add_filter( "dt_get_post_fields_filter", [ $this, "dt_get_post_fields_filter" ], 10, 2 );

        // list
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );

        add_action( 'dt_modal_help_text', [ $this, 'modal_help_text' ], 10 );
    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, 'Training', 'Trainings' );
        }
    }

    public function dt_set_roles_and_permissions( $expected_roles ){
        if ( !isset( $expected_roles["multiplier"] ) ){
            $expected_roles["multiplier"] = [
                "label" => __( 'Multiplier', 'disciple_tools' ),
                "permissions" => []
            ];
        }
        foreach ( $expected_roles as $role => $role_value ){
            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
                $expected_roles[$role]["permissions"]['access_' . $this->post_type] = true;
                $expected_roles[$role]["permissions"]['create_' . $this->post_type] = true;
            }
        }

        return $expected_roles;
    }

    public function p2p_init(){
        p2p_register_connection_type([
            'name' => 'trainings_to_contacts',
            'from' => 'trainings',
            'to' => 'contacts'
        ]);
        p2p_register_connection_type([
            'name' => 'trainings_to_groups',
            'from' => 'trainings',
            'to' => 'groups'
        ]);
        p2p_register_connection_type([
            'name' => 'trainings_to_leaders',
            'from' => 'trainings',
            'to' => 'contacts'
        ]);
        p2p_register_connection_type([
            'name' => 'trainings_to_trainings',
            'from' => 'trainings',
            'to' => 'trainings'
        ]);

    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $fields['leader_count'] = [
                'name' => "Leaders #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => false
            ];
            $fields['contact_count'] = [
                'name' => "Participants #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => false
            ];
            $fields['group_count'] = [
                'name' => "Groups #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => false
            ];

            $fields["requires_update"] = [
                'name'        => __( 'Requires Update', 'disciple_tools' ),
                'description' => '',
                'type'        => 'boolean',
                'default'     => false,
            ];
            $fields['location_grid'] = [
                'name'        => __( 'Locations', 'disciple_tools' ),
                'description' => _x( 'The general location where this group meets.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location',
                'default'     => [],
                'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg',
                'show_in_table' => 40
            ];
            $fields['location_grid_meta'] = [
                'name'        => 'Location Grid Meta', //system string does not need translation
                'type'        => 'location_meta',
                'default'     => [],
                'hidden' => true,
            ];
            $fields["status"] = [
                'name' => "Status",
                'type' => 'key_select',
                "tile" => "details",
                'default' => [
                    'new'   => [
                        "label" => _x( 'New', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "New training added to the system", "Training Status field description", 'disciple_tools' ),
                        "color" => "#F43636",
                    ],
                    'proposed'   => [
                        "label" => _x( 'Proposed', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training has been proposed and is in initial conversations", "Training Status field description", 'disciple_tools' ),
                        "color" => "#F43636",
                    ],
                    'scheduled' => [
                        "label" => _x( 'Scheduled', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is confirmed, on the calendar.", "Training Status field description", 'disciple_tools' ),
                        "color" => "#FF9800",
                    ],
                    'in_progress' => [
                        "label" => _x( 'In Progress', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is confirmed, on the calendar, or currently active.", "Training Status field description", 'disciple_tools' ),
                        "color" => "#FF9800",
                    ],
                    'complete'     => [
                        "label" => _x( "Complete", 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training has successfully completed", "Training Status field description", 'disciple_tools' ),
                        "color" => "#FF9800",
                    ],
                    'paused'       => [
                        "label" => _x( 'Paused', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This contact is currently on hold. It has potential of getting scheduled in the future.", "Training Status field description", 'disciple_tools' ),
                        "color" => "#FF9800",
                    ],
                    'closed'       => [
                        "label" => _x( 'Closed', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is no longer going to happen.", "Training Status field description", 'disciple_tools' ),
                        "color" => "#F43636",
                    ],
                ],
                'show_in_table' => false
            ];
            $fields['start_date'] = [
                'name'        => __( 'Start Date', 'disciple_tools' ),
                'description' => _x( 'The date this group began meeting.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'date',
                'default'     => time(),
                "tile" => "details",
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
            ];
            $fields["leaders"] = [
                "name" => __( 'Leaders', 'disciple_tools' ),
                'description' => '',
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_leaders",
                "show_in_table" => 30
            ];
            $fields['parents'] = [
                'name' => "Parent Training",
                'type' => 'connection',
                'tile' => 'connections',
                "post_type" => $this->post_type,
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_trainings",
            ];
            $fields['children'] = [
                'name' => "Child Training",
                'description' => _x( 'A group that has been birthed out of this group.', 'Optional Documentation', 'disciple_tools' ),
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_trainings",
                'tile' => 'connections',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-child.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields['contacts'] = [
                'name' => "Participants",
                'type' => 'connection',
                "post_type" => 'contacts',
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_contacts",
            ];
            $fields['groups'] = [
                'name' => "Groups",
                'type' => 'connection',
                "post_type" => 'groups',
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_groups",
            ];

        }
        if ( $post_type === 'groups' ){
            $fields[$this->post_type] = [
                'name' => "Trainings",
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_groups",
            ];
        }
        if ( $post_type === 'contacts' ){
            $fields['training_leader'] = [
                'name' => "Leader",
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_leaders",
            ];
            $fields['training_participant'] = [
                'name' => "Participant",
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_contacts",
            ];
        }
        return $fields;
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === $this->post_type ){
            $tiles["connections"] = [ "label" => __( "Connections", 'disciple_tools' ) ];
            $tiles["location"] = [ "label" => __( "Location", 'disciple_tools' ) ];
        }
        if ( $post_type === 'contacts' || $post_type === 'groups' ){
            $tiles[$this->post_type] = [ "label" => __( "Training", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ){

        if ($section === "location" && $post_type === $this->post_type ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>
            <style>#location-tile h3:first-child {display:none;} </style>
            <h3 class="section-header">
                Location <a class="button clear" id="new-mapbox-search"><?php esc_html_e( "add", 'disciple_tools' ) ?></a>
            </h3>

            <?php /* If Mapbox Upgrade */ if ( DT_Mapbox_API::get_key() ) : ?>

                <div id="mapbox-wrapper"></div>

                <?php if ( isset( $dt_post['location_grid_meta'] ) ) : ?>

                    <!-- reveal -->
                    <div class="reveal" id="map-reveal" data-reveal>
                        <div id="map-reveal-content"><!-- load content here --><div class="loader">Loading...</div></div>
                        <button class="close-button" data-close aria-label="Close modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

            <?php /* No Mapbox Upgrade */ else : ?>

                <?php render_field_for_display( 'location_grid', $post_settings["fields"], $dt_post ); ?>

            <?php endif; ?>


        <?php }

        // Connections tile on Trainings details page
        if ($section === "connections" && $post_type === $this->post_type ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <?php render_field_for_display( 'leaders', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'leader_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'contacts', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'contact_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'groups', $post_settings["fields"], $dt_post ) ?>

        <?php }

        // Trainings tile on contacts details page
        if ($section === $this->post_type && $post_type === "contacts"){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <?php render_field_for_display( 'training_leader', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'training_participant', $post_settings["fields"], $dt_post ) ?>

        <?php }

        // Trainings tile on groups details page
        if ($section === $this->post_type && $post_type === "groups"){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <?php render_field_for_display( $this->post_type, $post_settings["fields"], $dt_post ) ?>

        <?php }
    }

    public function post_connection_added( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === $this->post_type && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'added', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === $this->post_type ) {
            $this->update_event_counts( $value, 'added', $post_type );
        }
    }

    public function post_connection_removed( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === $this->post_type && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'removed', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === $this->post_type ) {
            $this->update_event_counts( $value, 'removed', $post_type );
        }
    }

    private function update_event_counts( $training_id, $action = "added", $type = 'contacts' ){
        $training = get_post( $training_id );
        if ( $type === 'contacts' ){
            $args = [
                'connected_type'   => "trainings_to_contacts",
                'connected_direction' => 'from',
                'connected_items'  => $training,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $contacts = get_posts( $args );
            $contact_count = get_post_meta( $training_id, 'contact_count', true );
            if ( sizeof( $contacts ) > intval( $contact_count ) ){
                update_post_meta( $training_id, 'contact_count', sizeof( $contacts ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $training_id, 'contact_count', intval( $contact_count ) - 1 );
            }
        }
        if ( $type === 'groups' ){
            $args = [
                'connected_type'   => "trainings_to_groups",
                'connected_direction' => 'from',
                'connected_items'  => $training,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $groups = get_posts( $args );
            $group_count = get_post_meta( $training_id, 'group_count', true );
            if ( sizeof( $groups ) > intval( $group_count ) ){
                update_post_meta( $training_id, 'group_count', sizeof( $groups ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $training_id, 'group_count', intval( $group_count ) - 1 );
            }
        }
        if ( $type === 'leaders' ){
            $args = [
                'connected_type'   => "trainings_to_leaders",
                'connected_direction' => 'from',
                'connected_items'  => $training,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $contacts = get_posts( $args );
            $contact_count = get_post_meta( $training_id, 'leader_count', true );
            if ( sizeof( $contacts ) > intval( $contact_count ) ){
                update_post_meta( $training_id, 'leader_count', sizeof( $contacts ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $training_id, 'leader_count', intval( $contact_count ) - 1 );
            }
        }
    }

    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            if ( !isset( $fields["status"] ) ){
                $fields["status"] = "new";
            }
        }
        return $fields;
    }

    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        if ( $post_type === $this->post_type ){
            if ( isset( $fields["assigned_to"] ) ) {
                if ( filter_var( $fields["assigned_to"], FILTER_VALIDATE_EMAIL ) ){
                    $user = get_user_by( "email", $fields["assigned_to"] );
                    if ( $user ) {
                        $fields["assigned_to"] = $user->ID;
                    } else {
                        return new WP_Error( __FUNCTION__, "Unrecognized user", $fields["assigned_to"] );
                    }
                }
                //make sure the assigned to is in the right format (user-1)
                if ( is_numeric( $fields["assigned_to"] ) ||
                    strpos( $fields["assigned_to"], "user" ) === false ){
                    $fields["assigned_to"] = "user-" . $fields["assigned_to"];
                }
                $user_id = explode( '-', $fields["assigned_to"] )[1];
                if ( $user_id ){
                    DT_Posts::add_shared( "groups", $post_id, $user_id, null, false, true, false );
                }
            }

            $existing_group = DT_Posts::get_post(  $this->post_type, $post_id, true, false );
            if ( isset( $fields["group_type"] ) && empty( $fields["church_start_date"] ) && empty( $existing_group["church_start_date"] ) && $fields["group_type"] === 'church' ){
                $fields["church_start_date"] = time();
            }

            if ( isset( $fields["group_status"] ) && empty( $fields["end_date"] ) && empty( $existing_group["end_date"] ) && $fields["group_status"] === 'inactive' ){
                $fields["end_date"] = time();
            }
        }
        return $fields;
    }

    public static function get_all_training_counts(){
        global $wpdb;
        if ( current_user_can( 'view_any_trainings' ) ){
            $results = $wpdb->get_results("
                SELECT pm.meta_value as status, count(pm.meta_value) as count
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'trainings' and a.post_status = 'publish' )
                WHERE pm.meta_key = 'status'
                GROUP BY pm.meta_value
            ", ARRAY_A);
        } else {
            $results = []; //@todo with assignment field.
        }

        return $results;
    }

    public function dt_user_list_filters( $filters, $post_type ) {
        if ( $post_type === $this->post_type ) {
            $counts = self::get_all_training_counts();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $this->post_type );
            $total = 0;
            $status_counts = [];
            foreach ( $counts as $count ) {
                $total += $count["count"];
                self::increment( $status_counts[$count["status"]], $count["count"] );
            }
            $filters["tabs"][] = [
                "key" => "all_trainings",
                "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                "order" => 10

            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'all_trainings',
                'tab' => 'all_trainings',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [],
                'count' => $total
            ];

            foreach ( $post_settings["fields"]['status']['default'] as $status_key => $status_value ){
                if ( isset( $status_counts[$status_key] ) ){
                    $filters["filters"][] = [
                        'ID' => "all_$status_key",
                        'tab' => 'all_trainings',
                        'name' => $status_value["label"],
                        'query' => [ "status" => [ $status_key ] ],
                        'count' => $status_counts[$status_key]
                    ];
                }
            }
        }
        return $filters;
    }

    private function increment( &$var, $val ){
        if ( !isset( $var ) ){
            $var = 0;
        }
        $var += (int) $val;
    }

    public function dt_comment_created( $post_type, $post_id, $comment_id, $type ){
        if ( $post_type === "trainings" ){
            if ( $type === "comment" ){
                self::check_requires_update( $post_id );
            }
        }
    }

    private static function check_requires_update( $group_id ){
        if ( get_current_user_id() ){
            $requires_update = get_post_meta( $group_id, "requires_update", true );
            if ( $requires_update == "yes" || $requires_update == true || $requires_update == "1"){
                //don't remove update needed if the user is a dispatcher (and not assigned to the groups.)
                if ( DT_Posts::can_view_all( 'trainings' ) ){
                    if ( dt_get_user_id_from_assigned_to( get_post_meta( $group_id, "assigned_to", true ) ) === get_current_user_id() ){
                        update_post_meta( $group_id, "requires_update", false );
                    }
                } else {
                    update_post_meta( $group_id, "requires_update", false );
                }
            }
        }
    }

    public function dt_get_post_fields_filter( $fields, $post_type ) {
        if ( $post_type === 'trainings' ){
            $fields = apply_filters( 'dt_trainings_fields_post_filter', $fields );
        }
        return $fields;
    }

    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        if ( $post_type === "trainings" ){
            do_action( "dt_training_created", $post_id, $initial_fields );
            $group = DT_Posts::get_post( 'trainings', $post_id, true, false );
            if ( isset( $group["assigned_to"] )) {
                if ( $group["assigned_to"]["id"] ) {
                    DT_Posts::add_shared( "trainings", $post_id, $group["assigned_to"]["id"], null, false, false, false );
                }
            }
        }
    }

    public function scripts(){
       // no unique scripts needed
    }

    public function modal_help_text() {
        if ( is_singular( $this->post_type ) ) {
            ?>
            <div class="help-section" id="connections-help-text" style="display: none">
                <h3><?php echo esc_html_x( "Connections", 'Optional Documentation', 'disciple_tools' ) ?></h3>
                <p><?php echo esc_html_x( "You can track leaders or number of leaders independently, depending on what information you have. This is also true of participants and number of participants.", 'Optional Documentation', 'disciple_tools' ) ?></p>
                <p><?php echo esc_html_x( "Connected leaders, participants, and groups create a link to the trainings tile on the corresponding contacts and groups sections.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
        if ( is_singular( "contacts" ) ) {
            ?>
            <div class="help-section" id="trainings-help-text" style="display: none">
                <h3><?php echo esc_html_x( "Trainings", 'Optional Documentation', 'disciple_tools' ) ?></h3>
                <p><?php echo esc_html_x( "You can connect this contact as a leader or a participant to a training.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
        if ( is_singular( "groups" ) ) {
            ?>
            <div class="help-section" id="trainings-help-text" style="display: none">
                <h3><?php echo esc_html_x( "Trainings", 'Optional Documentation', 'disciple_tools' ) ?></h3>
                <p><?php echo esc_html_x( "You can connect this group to a training.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
    }
}
DT_Training_Post_Type::instance();