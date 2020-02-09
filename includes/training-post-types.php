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

        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_section_ids', [ $this, 'dt_details_additional_section_ids' ], 10, 2 );
        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
    }


    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( "trainings", 'Training', 'Trainings' );
        }
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'trainings' ){
            $fields['contact_count'] = [
                'name' => "Number of contacts",
                'type' => 'text',
                'default' => '',
                'show_in_table' => true
            ];
            $fields['group_count'] = [
                'name' => "Number of groups",
                'type' => 'text',
                'default' => '',
                'show_in_table' => false
            ];
            $fields["location_grid"] = [
                'name' => "Locations",
                'type' => 'location',
                'default' => [],
                'show_in_table' => true
            ];
            $fields["start_date"] = [
                'name' => "Start Date",
                'type' => 'date',
                'default' => [],
                'show_in_table' => true
            ];
            $fields["end_date"] = [
                'name' => "End Date",
                'type' => 'date',
                'default' => [],
                'show_in_table' => true
            ];
            $fields['contacts'] = [
                'name' => "Contacts",
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
            $fields['trainings'] = [
                'name' => "Trainings",
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

    }

    public function dt_details_additional_section_ids( $sections, $post_type = "" ){
        if ( $post_type === "trainings"){
            $sections[] = 'contacts';
            $sections[] = 'groups';
            $sections[] = 'connections';

        }
        if ( $post_type === 'contacts' || $post_type === 'groups' ){
            $sections[] = 'trainings';
        }
        return $sections;
    }

    public function dt_details_additional_section( $section, $post_type ){
        // top tile on training details page
        if ( $section === "details" && $post_type === "trainings" ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );

            ?>
            <div class="grid-x grid-padding-x">
                <div class="cell">
                    <?php render_field_for_display( 'location_grid', $post_settings["fields"], $dt_post ); ?>
                </div>
                <div class="cell medium-6">
                    <?php
                    render_field_for_display( 'start_date', $post_settings["fields"], $dt_post );
                    ?>
                </div>
                <div class="cell medium-6">
                    <?php
                    render_field_for_display( 'end_date', $post_settings["fields"], $dt_post );
                    ?>
                </div>
            </div>
            <?php
        }
        // Connections tile on Trainings details page
        if ($section == "connections" && $post_type === "trainings"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Connections', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'contact_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'contacts', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'group_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'groups', $post_settings["fields"], $dt_post ) ?>

        <?php }

        // Trainings tile on contacts details page
        if ($section == "trainings" && $post_type === "contacts"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Trainings', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'trainings', $post_settings["fields"], $dt_post ) ?>

        <?php }

        // Trainings tile on groups details page
        if ($section == "trainings" && $post_type === "groups"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Trainings', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'trainings', $post_settings["fields"], $dt_post ) ?>

        <?php }


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

    public static function dt_user_list_filters( $filters, $post_type ) {
        if ( $post_type === 'trainings' ) {
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
            ];
        }
        return $filters;
    }
}
