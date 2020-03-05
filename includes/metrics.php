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

//        parent::__construct();
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
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#overview" onclick="write_training_overview()">'. esc_html__( 'Overview', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#basic_map" onclick="write_training_basicmap()">'. esc_html__( 'Basic Map', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_1" onclick="write_training_heatmap1()">'. esc_html__( 'Heat Map 1', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_2" onclick="write_training_heatmap2()">'. esc_html__( 'Heat Map 2', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_3" onclick="write_training_heatmap3()">'. esc_html__( 'Heat Map 3', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_4" onclick="write_training_heatmap4()">'. esc_html__( 'Heat Map 4', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_5" onclick="write_training_heatmap5()">'. esc_html__( 'Heat Map 5', 'disciple_tools' ) .'</a></li>
                    <li><a href="'. site_url( '/metrics/trainings/' ) .'#heat_map_6" onclick="write_training_heatmap6()">'. esc_html__( 'Heat Map 6', 'disciple_tools' ) .'</a></li>
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
                'data' => [],
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

    }

    public function heatmap1( WP_REST_Request $request ) {
        if ( ! $this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }
        $params = $request->get_params();


        global $wpdb;

        $results = $wpdb->get_results("
            SELECT ps.post_type, lg.longitude as lng, lg.latitude as lat, p.post_id 
            FROM $wpdb->postmeta as p 
                INNER JOIN $wpdb->dt_location_grid as lg ON p.meta_value=lg.grid_id 
                JOIN $wpdb->posts as ps ON ps.ID=p.post_id
            WHERE p.meta_key = 'location_grid'
            ", ARRAY_A );
//        dt_write_log($results);
        $features = [];
        foreach( $results as $result ) {
            $features[] = array(
                'type' => 'Feature',
                'properties' => array("post_type" => $result['post_type'], "post_id" => $result['post_id']),
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


/* pulling 30k from location_grid_meta table */
//        $results = $wpdb->get_results("SELECT * FROM wp_3_dt_location_grid_meta", ARRAY_A );
//        $features = [];
//        foreach( $results as $result ) {
//            $features[] = array(
//                'type' => 'Feature',
//                'properties' => array("name" => $result['label']),
//                'geometry' => array(
//                    'type' => 'Point',
//                    'coordinates' => array(
//                        $result['lng'],
//                        $result['lat'],
//                        1
//                    ),
//                ),
//            );
//        }


        $new_data = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $new_data;
    }


}

