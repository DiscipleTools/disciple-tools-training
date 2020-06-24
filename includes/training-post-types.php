<?php

class DT_Training_Post_Type {
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 10, 2 );
        add_action( 'dt_modal_help_text', [ $this, 'modal_help_text' ], 10 );

        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_section_ids', [ $this, 'dt_details_additional_section_ids' ], 10, 2 );
        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
        add_filter( "dt_get_post_fields_filter", [ $this, "dt_get_post_fields_filter" ], 10, 2 );
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( "trainings", 'Training', 'Trainings' );
        }
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'trainings' ){
            $fields['leader_count'] = [
                'name' => "Leaders #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => true
            ];
            $fields['contact_count'] = [
                'name' => "Participants #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => true
            ];
            $fields['group_count'] = [
                'name' => "Groups #",
                'type' => 'text',
                'default' => '0',
                'show_in_table' => false
            ];
            $fields["location_grid"] = [
                'name' => "Locations",
                'type' => 'location',
                'default' => [],
                'show_in_table' => true
            ];
            $fields["location_grid_meta"] = [
                'name' => "Locations",
                'type' => 'location_meta',
                'default' => [],
                'show_in_table' => false,
                'silent' => true,
            ];
            $fields["status"] = [
                'name' => "Status",
                'type' => 'key_select',
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
                'show_in_table' => true
            ];
            $fields["start_date"] = [
                'name' => "Start Date",
                'type' => 'date',
                'default' => '',
                'show_in_table' => true
            ];
            $fields['leaders'] = [
                'name' => "Leaders",
                'type' => 'connection',
                "post_type" => 'contacts',
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_leaders",
            ];
            $fields['parents'] = [
                'name' => "Parent Training",
                'type' => 'connection',
                "post_type" => 'trainings',
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_trainings",
            ];
            $fields['children'] = [
                'name' => "Child Training",
                'type' => 'connection',
                "post_type" => 'trainings',
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_trainings",
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
            $fields['trainings'] = [
                'name' => "Trainings",
                'type' => 'connection',
                "post_type" => 'trainings',
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_groups",
            ];
        }
        if ( $post_type === 'contacts' ){
            $fields['training_leader'] = [
                'name' => "Leader",
                'type' => 'connection',
                "post_type" => 'trainings',
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_leaders",
            ];
            $fields['training_participant'] = [
                'name' => "Participant",
                'type' => 'connection',
                "post_type" => 'trainings',
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_contacts",
            ];
        }
        return $fields;
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

    public function dt_details_additional_section_ids( $sections, $post_type = "" ){
        if ( $post_type === "trainings"){
            $sections[] = 'connections';
            $sections[] = 'location';
        }
        if ( $post_type === 'contacts' || $post_type === 'groups' ){
            $sections[] = 'trainings';
        }
        return $sections;
    }

    public function dt_details_additional_section( $section, $post_type ){
        // top tile on training details page // @todo remove unnecessary header or add editing capability
        if ( $section === "details" && $post_type === "trainings" ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>
            <div class="grid-x grid-padding-x">
                <div class="cell medium-6">
                    <?php render_field_for_display( 'status', $post_settings["fields"], $dt_post ); ?>
                </div>
                <div class="cell medium-6">
                    <?php render_field_for_display( 'start_date', $post_settings["fields"], $dt_post ); ?>
                </div>
            </div>
            <?php
        }

        if ($section === "location" && $post_type === "trainings"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );

            ?>

            <label class="section-header">
                <?php esc_html_e( 'Location', 'disciple_tools' )?> <a class="button clear" id="new-mapbox-search"><?php esc_html_e( "add", 'disciple_tools' ) ?></a>
            </label>

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
        if ($section === "connections" && $post_type === "trainings"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Connections', 'disciple_tools' )?>
                <button class="help-button float-right" data-section="connections-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </label>
            <div class="section-body">

                <?php render_field_for_display( 'leaders', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'leader_count', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'contacts', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'contact_count', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'groups', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'parents', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'children', $post_settings["fields"], $dt_post ) ?>

            </div>

        <?php }

        // Trainings tile on contacts details page
        if ($section == "trainings" && $post_type === "contacts"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Trainings', 'disciple_tools' )?>
                <button class="help-button float-right" data-section="trainings-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </label>
            <div class="section-body">

                <?php render_field_for_display( 'training_leader', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'training_participant', $post_settings["fields"], $dt_post ) ?>

            </div>

        <?php }

        // Trainings tile on groups details page
        if ($section == "trainings" && $post_type === "groups"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Trainings', 'disciple_tools' )?>
                <button class="help-button float-right" data-section="trainings-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </label>
            <div class="section-body">

                <?php render_field_for_display( 'trainings', $post_settings["fields"], $dt_post ) ?>

            </div>

        <?php }
    }

    public function modal_help_text() {
        if ( is_singular( "trainings" ) ) {
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
    public function post_connection_added( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === "trainings" && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'added', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === "trainings" ) {
            $this->update_event_counts( $value, 'added', $post_type );
        }
    }
    public function post_connection_removed( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === "trainings" && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'removed', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === "trainings" ) {
            $this->update_event_counts( $value, 'removed', $post_type );
        }
    }

    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === "trainings" ){
            if ( !isset( $fields["status"] ) ){
                $fields["status"] = "new";
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


    private static function increment( &$var, $val ){
        if ( !isset( $var ) ){
            $var = 0;
        }
        $var += (int) $val;
    }
    public static function dt_user_list_filters( $filters, $post_type ) {
        if ( $post_type === 'trainings' ) {
            $counts = self::get_all_training_counts();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], "trainings" );
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

    public function dt_get_post_fields_filter( $fields, $post_type ) {
        if ( $post_type === 'trainings' ){
            $fields = apply_filters( 'dt_trainings_fields_post_filter', $fields );
        }
        return $fields;
    }
}
DT_Training_Post_Type::instance();