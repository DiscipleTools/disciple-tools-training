<?php

add_action( 'after_setup_theme', 'dt_training_metrics', 100 );
function dt_training_metrics() {
    DT_Training_Metrics::instance();
}

class DT_Training_Metrics
{
    public $permissions = [ 'view_any_contacts', 'view_project_metrics' ];

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        $url_path = dt_get_url_path();

        if ( !$this->has_permission() ){
            return;
        }

        if ( 'metrics' === substr( $url_path, '0', 7 ) ) {

            add_filter( 'dt_templates_for_urls', [ $this, 'add_url' ] ); // add custom URL
            add_filter( 'dt_metrics_menu', [ $this, 'add_menu' ], 50 );

            if ( 'metrics/trainings' === $url_path ) {
                add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
            }
        }

    }

    public function has_permission(){
        $permissions = $this->permissions;
        $pass = count( $permissions ) === 0;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    public function add_url( $template_for_url ) {
        $template_for_url['metrics/trainings'] = 'template-metrics.php';
        return $template_for_url;
    }

    public function add_menu( $content ) {
        $content .= '
            <li><a href="">' .  esc_html__( 'Training', 'disciple_tools' ) . '</a>
                <ul class="menu vertical nested" id="training-menu" aria-expanded="true">
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#cluster_map" onclick="write_training_cluster_map()">'. esc_html__( 'Cluster Map', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#choropleth_map" onclick="write_training_choropleth_map()">'. esc_html__( 'Choropleth Map', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#contacts_map" onclick="contacts_map()">'. esc_html__( 'Contacts Map', 'disciple_tools' ) .'</a></li>
                </ul>
            </li>
            ';
        return $content;
    }

    public function scripts() {

        wp_enqueue_script( 'dt_training_metrics',  trailingslashit( plugin_dir_url(__FILE__) ) . 'metrics.js', [
            'jquery',
        ], filemtime( trailingslashit(plugin_dir_path(__FILE__) ) . 'metrics.js' ), true );
        wp_localize_script(
            'dt_training_metrics', 'dtTrainingMetrics', [
                'root' => esc_url_raw( rest_url() ),
                'theme_uri' => trailingslashit( get_template_directory_uri() ),
                'plugin_uri' => plugin_dir_url(__DIR__),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id(),
                'map_key' => DT_Mapbox_API::get_key(),
                "spinner_url" => get_stylesheet_directory_uri() . '/spinner.svg',
                'data' => $this->get_totals(),
            ]
        );

    }

    public function add_api_routes() {
        $namespace = 'dt/v1';

        register_rest_route(
            $namespace, '/trainings/heatmap1', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'heatmap1' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/trainings/list', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'get_list' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/trainings/contacts_map', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'contacts_map' ],
                ],
            ]
        );

    }

    public function heatmap1( WP_REST_Request $request ) {
        if ( ! $this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }
        $params = $request->get_params();
        global $wpdb;

        /* pulling 30k from location_grid_meta table */
        $results = $wpdb->get_results("SELECT lg.label as address, p.post_title as name, post_id, lng, lat FROM $wpdb->dt_location_grid_meta as lg JOIN $wpdb->posts as p ON p.ID=lg.post_id WHERE lg.post_type = 'trainings' LIMIT 40000", ARRAY_A );
        $features = [];
        foreach( $results as $result ) {
            $features[] = array(
                'type' => 'Feature',
                'properties' => array("address" => $result['address'], "post_id" => $result['post_id'], "name" => $result['name'] ),
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        $result['lng'],
                        $result['lat'],
                        1
                    ),
                ),
            );
        }


        $new_data = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $new_data;
    }

    public function get_list( WP_REST_Request $request ) {
        if ( !$this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }

        return $this->get_totals();

    }

    public function get_totals() {
        global $wpdb;

//        if ( get_transient( __METHOD__) ) {
//            return get_transient( __METHOD__ );
//        }

        $results = $wpdb->get_results("
        SELECT
          t1.admin0_grid_id as grid_id,
          t1.type,
          count(t1.admin0_grid_id) as count
        FROM (
            SELECT
                g.admin0_grid_id,
                CASE
                    WHEN gt.meta_value = 'church' THEN 'churches'
                    WHEN cu.meta_value IS NOT NULL THEN 'users'
                    ELSE pp.post_type
                END as type
            FROM $wpdb->postmeta as p
                JOIN $wpdb->posts as pp ON p.post_id=pp.ID
                LEFT JOIN $wpdb->dt_location_grid as g ON g.grid_id=p.meta_value             
                LEFT JOIN $wpdb->postmeta as cu ON cu.post_id=p.post_id AND cu.meta_key = 'corresponds_to_user'
                LEFT JOIN $wpdb->postmeta as gt ON gt.post_id=p.post_id AND gt.meta_key = 'group_type'
            WHERE p.meta_key = 'location_grid'
        ) as t1
        WHERE t1.admin0_grid_id != ''
        GROUP BY t1.admin0_grid_id, t1.type
        UNION
        SELECT
          t2.admin1_grid_id as grid_id,
          t2.type,
          count(t2.admin1_grid_id) as count
        FROM (
                SELECT
                g.admin1_grid_id,
                CASE
                    WHEN gt.meta_value = 'church' THEN 'churches'
                    WHEN cu.meta_value IS NOT NULL THEN 'users'
                    ELSE pp.post_type
                END as type
            FROM $wpdb->postmeta as p
                JOIN $wpdb->posts as pp ON p.post_id=pp.ID
                LEFT JOIN $wpdb->dt_location_grid as g ON g.grid_id=p.meta_value             
                LEFT JOIN $wpdb->postmeta as cu ON cu.post_id=p.post_id AND cu.meta_key = 'corresponds_to_user'
                LEFT JOIN $wpdb->postmeta as gt ON gt.post_id=p.post_id AND gt.meta_key = 'group_type'
            WHERE p.meta_key = 'location_grid'
        ) as t2
        WHERE t2.admin1_grid_id != ''
        GROUP BY t2.admin1_grid_id, t2.type
        UNION
        SELECT
          t3.admin2_grid_id as grid_id,
          t3.type,
          count(t3.admin2_grid_id) as count
        FROM (
                SELECT
                g.admin2_grid_id,
                CASE
                    WHEN gt.meta_value = 'church' THEN 'churches'
                    WHEN cu.meta_value IS NOT NULL THEN 'users'
                    ELSE pp.post_type
                END as type
            FROM $wpdb->postmeta as p
                JOIN $wpdb->posts as pp ON p.post_id=pp.ID
                LEFT JOIN $wpdb->dt_location_grid as g ON g.grid_id=p.meta_value             
                LEFT JOIN $wpdb->postmeta as cu ON cu.post_id=p.post_id AND cu.meta_key = 'corresponds_to_user'
                LEFT JOIN $wpdb->postmeta as gt ON gt.post_id=p.post_id AND gt.meta_key = 'group_type'
            WHERE p.meta_key = 'location_grid'
        ) as t3
        WHERE t3.admin2_grid_id != ''
        GROUP BY t3.admin2_grid_id, t3.type
        UNION
        SELECT
          t4.admin3_grid_id as grid_id,
          t4.type,
          count(t4.admin3_grid_id) as count
        FROM (
                SELECT
                g.admin3_grid_id,
                CASE
                    WHEN gt.meta_value = 'church' THEN 'churches'
                    WHEN cu.meta_value IS NOT NULL THEN 'users'
                    ELSE pp.post_type
                END as type
            FROM $wpdb->postmeta as p
                JOIN $wpdb->posts as pp ON p.post_id=pp.ID
                LEFT JOIN $wpdb->dt_location_grid as g ON g.grid_id=p.meta_value             
                LEFT JOIN $wpdb->postmeta as cu ON cu.post_id=p.post_id AND cu.meta_key = 'corresponds_to_user'
                LEFT JOIN $wpdb->postmeta as gt ON gt.post_id=p.post_id AND gt.meta_key = 'group_type'
            WHERE p.meta_key = 'location_grid'
        ) as t4
        WHERE t4.admin3_grid_id != ''
        GROUP BY t4.admin3_grid_id, t4.type;
    ", ARRAY_A );

        $data = [];
        foreach ( $results as $result ) {
            $data[$result['grid_id'] ] = $result;
        }

        set_transient( __METHOD__, $data, 60 * 60 * 24 );


        if ( empty( $data ) ) {
            $data = [];
        }

        return $data;
    }

    public function contacts_map( WP_REST_Request $request ) {
        if ( !$this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }

        global $wpdb;
        $results = $wpdb->get_results("
            SELECT lg.longitude as lng, lg.latitude as lat, p.meta_value as location_grid
            FROM $wpdb->postmeta as p 
                INNER JOIN $wpdb->dt_location_grid as lg ON p.meta_value=lg.grid_id 
                JOIN $wpdb->posts as ps ON ps.ID=p.post_id
            WHERE p.meta_key = 'location_grid' AND ps.post_type = 'contacts'
            ", ARRAY_A );

        $features = [];
        foreach ( $results as $result ) {
            $features[] = array(
                'type' => 'Feature',
                'properties' => array( "location_grid" => $result['location_grid'] ),
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        $result['lng'],
                        $result['lat'],
                        1
                    ),
                ),
            );
        }

        $new_data = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $new_data;
    }


}

