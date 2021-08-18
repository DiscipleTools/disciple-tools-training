<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


class DT_Metrics_Trainings_Tree extends DT_Metrics_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'trainings'; // lowercase
    public $slug = 'tree'; // lowercase
    public $base_title;
    public $title;
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'tree.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];
    public $namespace = null;

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Trainings', 'disciple_tools' );
        $this->title = __( 'Trainings Tree', 'disciple_tools' );

        $url_path = dt_get_url_path();
        if ( "metrics/$this->base_slug/$this->slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        }

        $this->namespace = "dt-metrics/$this->base_slug/$this->slug";
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function add_api_routes() {

        $version = '1';
        $namespace = 'dt/v' . $version;
        register_rest_route(
            $namespace, '/metrics/trainings/tree', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'tree' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

    }

    public function tree( WP_REST_Request $request ) {
        if ( !$this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }

        $params = $request->get_params();

        if ( ! isset( $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        switch ( $action ) {
            case 'only_multiplying':
                $query = $this->query_multiplying_only();
                return $this->get_training_generations_tree( $query );
            case 'show_all':
                $query = $this->query_show_all();
                return $this->get_training_generations_tree( $query );
            default:
                return [];
        }

    }

    public function scripts() {
        wp_enqueue_script( 'dt_metrics_project_script', trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
            'lodash'
        ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . $this->js_file_name ), true );

        wp_localize_script(
            'dt_metrics_project_script', 'dtMetricsProject', [
                'root' => esc_url_raw( rest_url() ),
                'theme_uri' => get_template_directory_uri(),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id(),
                'map_key' => empty( DT_Mapbox_API::get_key() ) ? '' : DT_Mapbox_API::get_key(),
                'data' => $this->data(),
            ]
        );
    }

    public function data() {
        return [
            'translations' => [
                'title_training_tree' => __( 'Training Generation Tree', 'disciple_tools' ),
                'highlight_active' => __( 'Highlight Active', 'disciple_tools' ),
                'highlight_churches' => __( 'Highlight Churches', 'disciple_tools' ),
                'show_all' => __( 'Show All', 'disciple_tools' ),
                'show_active' => __( 'Show Active', 'disciple_tools' ),
                'show_multiplying' => __( 'Show Multiplying Only', 'disciple_tools' ),

                'members' => __( 'Members', 'disciple_tools' ),
                'view_record' => __( "View Record", "disciple_tools" ),
                'assigned_to' => __( "Assigned To", "disciple_tools" ),
                'status' => __( "Status", "disciple_tools" ),
                'total_members' => __( "Total Members", "disciple_tools" ),
                'view_training' => __( "View Training", "disciple_tools" ),

            ],
            'training_generation_tree' => $this->get_training_generations_tree(),
        ];
    }

    public function get_training_generations_tree( $query = [] ){
        if ( empty( $query ) ) {
            $query = $this->query_multiplying_only();
        }
        if ( is_wp_error( $query )){
            return $this->_circular_structure_error( $query );
        }
        if ( empty( $query ) ) {
            return $this->_no_results();
        }
        $menu_data = $this->prepare_menu_array( $query );
        return $this->build_training_tree( 0, $menu_data, 0 );
    }

    public function prepare_menu_array( $query) {
        // prepare special array with parent-child relations
        $menu_data = array(
            'items' => array(),
            'parents' => array()
        );

        foreach ( $query as $menu_item )
        {
            $menu_data['items'][$menu_item['id']] = $menu_item;
            $menu_data['parents'][$menu_item['parent_id']][] = $menu_item['id'];
        }
        return $menu_data;
    }

    public function build_training_tree( $parent_id, $menu_data, $gen, $unique_check = []) {
        $html = '';

        if (isset( $menu_data['parents'][$parent_id] ))
        {
            $gen++;

            $first_section = '';
            if ( $gen === 0 ) {
                $first_section = 'first-section';
            }

            $html = '<ul class="ul-gen-'.esc_html( $gen ).'">';
            foreach ($menu_data['parents'][$parent_id] as $item_id)
            {
                $html .= '<li class="gen-node li-gen-' . esc_html( $gen ) . ' ' . esc_attr( $first_section ) . '">';
                $html .= '(' . esc_html( $gen ) . ') ';
                $html .= '<strong><a href="' . esc_url( site_url( "/trainings/" ) ) . esc_html( $item_id ) . '">' . esc_html( $menu_data['items'][ $item_id ]['name'] ) . '</a></strong><br>';

                // find child items recursively
                if ( !in_array( $item_id, $unique_check ) ){
                    $unique_check[] = $item_id;
                    $html .= $this->build_training_tree( $item_id, $menu_data, $gen, $unique_check );
                }

                $html .= '</li>';
            }
            $html .= '</ul>';

        }
        return $html;
    }

    public function query_show_all() {
        global $wpdb;
        $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'trainings'
                    AND a.ID NOT IN (
                    SELECT DISTINCT (p2p_from)
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'trainings_to_trainings'
                    GROUP BY p2p_from)
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'trainings_to_trainings'
                ", ARRAY_A );

        return $query;
    }

    public function query_multiplying_only() {
        global $wpdb;
        $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'trainings'
                    AND a.ID NOT IN (
                    SELECT DISTINCT (p2p_from)
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'trainings_to_trainings'
                    GROUP BY p2p_from)
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'trainings_to_trainings'
                ", ARRAY_A );

        $list = [];
        foreach ( $query as $item ) {
            $list[$item['id']] = $item;
        }
        $multiplying_only = [];
        foreach ( $query as $item ) {
            if ( ! empty( $item['parent_id'] ) ) {
                $multiplying_only[] = $item;
                $multiplying_only[] = $list[$item['parent_id']];
            }
        }

        return $multiplying_only;
    }


}
new DT_Metrics_Trainings_Tree();


