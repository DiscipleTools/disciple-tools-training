<?php

class DT_Events{
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
            new Disciple_Tools_Post_Type_Template( "clusters", 'Cluster', 'Clusters' );
        }
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'clusters' ){
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
                'show_in_table' => true
            ];
            $fields['contacts'] = [
                'name' => "Contacts",
                'type' => 'connection',
                "post_type" => 'contacts',
                "p2p_direction" => "from",
                "p2p_key" => "clusters_to_contacts",
            ];
            $fields['groups'] = [
                'name' => "Groups",
                'type' => 'connection',
                "post_type" => 'groups',
                "p2p_direction" => "from",
                "p2p_key" => "clusters_to_groups",
            ];
            $fields["location_grid"] = [
                'name' => "Locations",
                'type' => 'location',
                'default' => [],
                'show_in_table' => true
            ];
            $fields["people_groups"] = [
                "name" => __( 'People Groups', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "peoplegroups",
                "p2p_direction" => "from",
                "p2p_key" => "clusters_to_peoplegroups"
            ];
            $fields["parent_clusters"] =[
                'name' => "Parent Cluster",
                'type' => "connection",
                "post_type" => "clusters",
                "p2p_key" => "clusters_to_clusters",
                "p2p_direction" => "from",
                "show_in_table" => true
            ];
            $fields["child_clusters"] =[
                'name' => "Child Clusters",
                'type' => "connection",
                "post_type" => "clusters",
                "p2p_key" => "clusters_to_clusters",
                "p2p_direction" => "to",
                "show_in_table" => true
            ];
        }
        if ( $post_type === 'groups' ){
            $fields['clusters'] = [
                'name' => "Clusters",
                'type' => 'connection',
                "post_type" => 'clusters',
                "p2p_direction" => "to",
                "p2p_key" => "clusters_to_groups",
            ];
        }
        if ( $post_type === 'contacts' ){
            $fields['clusters'] = [
                'name' => "Clusters",
                'type' => 'connection',
                "post_type" => 'clusters',
                "p2p_direction" => "to",
                "p2p_key" => "clusters_to_contacts",
            ];
        }
        return $fields;
    }

    public function p2p_init(){
        p2p_register_connection_type([
            'name' => 'clusters_to_contacts',
            'from' => 'clusters',
            'to' => 'contacts'
        ]);
        p2p_register_connection_type([
            'name' => 'clusters_to_groups',
            'from' => 'clusters',
            'to' => 'groups'
        ]);
        p2p_register_connection_type([
            'name' => 'clusters_to_clusters',
            'from' => 'clusters',
            'to' => 'clusters'
        ]);
        p2p_register_connection_type([
            'name' => 'clusters_to_peoplegroups',
            'from' => 'clusters',
            'to' => 'peoplegroups'
        ]);
    }

    public function dt_details_additional_section_ids( $sections, $post_type = "" ){
        if ( $post_type === "clusters"){
            $sections[] = 'contacts';
            $sections[] = 'groups';
        }
        if ( $post_type === 'contacts' || $post_type === 'groups' ){
            $sections[] = 'clusters';
        }
        return $sections;
    }

    public function dt_details_additional_section( $section, $post_type ){
        if ($section == "contacts" && $post_type === "clusters"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Contacts', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'contact_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'contacts', $post_settings["fields"], $dt_post ) ?>

        <?php }

        if ($section == "groups" && $post_type === "clusters"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Groups', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'group_count', $post_settings["fields"], $dt_post ) ?>

            <?php render_field_for_display( 'groups', $post_settings["fields"], $dt_post ) ?>

        <?php }

        if ($section == "clusters" && $post_type === "contacts"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Clusters', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'clusters', $post_settings["fields"], $dt_post ) ?>

        <?php }

        if ($section == "clusters" && $post_type === "groups"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php esc_html_e( 'Clusters', 'disciple_tools' )?>
            </label>

            <?php render_field_for_display( 'clusters', $post_settings["fields"], $dt_post ) ?>

        <?php }

        if ( $section === "details" && $post_type === "clusters" ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            render_field_for_display( 'location_grid', $post_settings["fields"], $dt_post );
            render_field_for_display( 'people_groups', $post_settings["fields"], $dt_post );
            render_field_for_display( 'parent_clusters', $post_settings["fields"], $dt_post );
            render_field_for_display( 'child_clusters', $post_settings["fields"], $dt_post );
        }
    }

    private function update_cluster_counts( $cluster_id, $action = "added", $type = 'contacts' ){
        $cluster = get_post( $cluster_id );
        if ( $type === 'contacts' ){
            $args = [
                'connected_type'   => "clusters_to_contacts",
                'connected_direction' => 'from',
                'connected_items'  => $cluster,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $contacts = get_posts( $args );
            $contact_count = get_post_meta( $cluster_id, 'contact_count', true );
            if ( sizeof( $contacts ) > intval( $contact_count ) ){
                update_post_meta( $cluster_id, 'contact_count', sizeof( $contacts ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $cluster_id, 'contact_count', intval( $contact_count ) - 1 );
            }
        }
        if ( $type === 'groups' ){
            $args = [
                'connected_type'   => "clusters_to_groups",
                'connected_direction' => 'from',
                'connected_items'  => $cluster,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $groups = get_posts( $args );
            $group_count = get_post_meta( $cluster_id, 'group_count', true );
            if ( sizeof( $groups ) > intval( $group_count ) ){
                update_post_meta( $cluster_id, 'group_count', sizeof( $groups ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $cluster_id, 'group_count', intval( $group_count ) - 1 );
            }
        }
    }
    public function post_connection_added( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === "clusters" && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_cluster_counts( $post_id, 'added', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === "clusters" ) {
            $this->update_cluster_counts( $value, 'added', $post_type );
        }
    }
    public function post_connection_removed( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === "clusters" && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_cluster_counts( $post_id, 'removed', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === "clusters" ) {
            $this->update_cluster_counts( $value, 'removed', $post_type );
        }
    }

    public static function dt_user_list_filters( $filters, $post_type ) {
        if ( $post_type === 'clusters' ) {
            $filters["tabs"][] = [
                "key" => "all_clusters",
                "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                "order" => 10
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'all_clusters',
                'tab' => 'all_clusters',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [],
            ];
        }
        return $filters;
    }
}
