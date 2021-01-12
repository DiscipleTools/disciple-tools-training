<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Training_App_Registration_Module extends DT_Module_Base {
    public $post_type = "trainings";
    public $module = "trainings_app_module";
    public $root = 'training_app';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }

        // setup tile
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 20, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'custom_fields' ], 10, 2 );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === 'trainings' ){
            $tiles["apps"] = [ "label" => __( "Apps", 'disciple-tools-training' ) ];
        }
        return $tiles;
    }
    public function custom_fields( $fields, $post_type ){

        return $fields;
    }
    public function dt_details_additional_section( $section, $post_type ) {
        if ( $post_type === 'trainings' && $section === "apps" ){
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            $magic = new DT_Magic_URL( $this->root );
            $types = $magic->list_types();
            ?>
            <div class="section-subheader">
                <img class="dt-icon" src="<?php echo esc_url( get_stylesheet_directory_uri() ) ?>/dt-assets/images/date-end.svg">
                Registration
                <span id="register-spinner" class="loading-spinner"></span>
            </div>
            <div class="cell">
                <?php
                if ( ! empty( $types ) ){
                    foreach ( $types as $key => $type ){
                        ?>
                        <div class="cell small-12 medium-4">
                            <?php
                            if ( isset( $record[$type['meta_key']] ) ) {
                                /* copy link */
                                ?><a class="button hollow small" href="<?php echo esc_url( site_url() ) . '/' . esc_attr( $type['root'] ) . '/' . esc_attr( $type['type'] ) . '/'. esc_attr( $record[$type['meta_key']] ) ?>"><?php echo esc_html__( 'copy link', 'disciple-tools-training' ) ?></a> <?php
                                /* edit form */
?><a class="button hollow small" data-open="modal-large"><?php echo esc_html__( 'edit form', 'disciple-tools-training' ) ?></a> <?php
                                /* show report */
?><a class="button hollow small" data-open="modal-small" ><?php echo esc_html__( 'report', 'disciple-tools-training' ) ?></a><?php
                            }
                            /* create link*/
                            else {
                                ?><a class="create-magic-link button hollow small" data-meta_key_name="<?php echo esc_attr( $type['meta_key'] ) ?>" data-meta_key_value="<?php echo esc_attr( $magic->create_unique_key() ) ?>" ><?php echo esc_html__( 'create link', 'disciple-tools-training' ) ?></a><?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <script>
                jQuery(document).ready(function($){
                    $('.create-magic-link').on('click', function(e){
                        let link = $(this)
                        let meta_key_name = $(this).data('meta_key_name')
                        let meta_key_value = $(this).data('meta_key_value')
                        let url = '<?php echo esc_url( site_url() ) . '/' . esc_attr( $type['root'] ) . '/' . esc_attr( $type['type'] ) . '/' ?>'

                        let data = {}
                        data[meta_key_name] = meta_key_value

                        makeRequestOnPosts('POST', detailsSettings.post_type+'/'+detailsSettings.post_id, data)
                            .done((updatedPost)=>{
                                console.log(updatedPost)
                                link.parent().empty().append(`
                                <a class="button hollow small" href="${url + updatedPost[meta_key_name]}">copy link</a>
                                <a class="button hollow small" >edit form</a>
                                <a class="button hollow small" >reports</a>
                                `)
                            })
                    })
                })
            </script>


            <div class="section-subheader">
                <img class="dt-icon" src="<?php echo esc_url( get_stylesheet_directory_uri() ) ?>/dt-assets/images/date-end.svg">
                Public Calendar
                <span id="register-spinner" class="loading-spinner"></span>
            </div>
            <div class="cell">
                <?php
                if ( ! empty( $types ) ){
                    foreach ( $types as $key => $type ){
                        ?>
                        <div class="cell small-12 medium-4">
                            <?php
                            /* copy link */
                            ?><a class="button hollow small" href="<?php echo esc_url( site_url() ) . '/' . esc_attr( $type['root'] ) . '/' . esc_attr( $type['type'] ) . '/' ?>"><?php echo esc_html__( 'link', 'disciple-tools-training' ) ?></a> <?php
                            /* edit form */
?><a class="button hollow small" data-open="modal-large"><?php echo esc_html__( 'show on calendar', 'disciple-tools-training' ) ?></a> <?php
                            /* show report */
?><a class="button hollow small" data-open="modal-small" ><?php echo esc_html__( 'open registration', 'disciple-tools-training' ) ?></a>                        </div>
                        <?php
                    }
                }
                ?>
            </div>

        <?php }
    }

}


class DT_Training_Magic_Registration
{
    public $url_magic;
    public $parts = false;
    public $root = 'training_app'; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'register'; // define the type

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        // register type
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );
        $this->url_magic = new DT_Magic_URL( $this->root );

        // register REST and REST access
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );

        // fail if not valid url
        $this->parts = $this->url_magic->parse_url_parts();
        if ( ! $this->parts ){
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        if ( $this->url_magic->is_valid_key_url( $this->type ) && 'stats' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'stats_body' ] );
        }
        else if ( $this->url_magic->is_valid_key_url( $this->type ) && 'maps' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'maps_body' ] );
        }
        else if ( $this->url_magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'home_body' ] );
        } else {
            // fail if no valid action url found
            return;
        }

        // load page elements
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ], 999 );
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => 'Registration',
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => $this->root . '_' . $this->type . '_public_key', // coaching-magic_c_key
            'actions' => [],
            'post_type' => 'trainings'
        ];
        return $types;
    }

    public function register_url( $template_for_url ){
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( ! $parts ){ // parts returns false
            return $template_for_url;
        }

        // test 2 : only base url requested
        if ( empty( $parts['public_key'] ) ){ // no public key present
            $template_for_url[ $parts['root'] . '/'. $parts['type'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 3 : no specific action requested
        if ( empty( $parts['action'] ) ){ // only root public key requested
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 4 : valid action requested
        $actions = $this->url_magic->list_actions( $parts['type'] );
        if ( isset( $actions[ $parts['action'] ] ) ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
        }

        return $template_for_url;
    }

    public function _has_access() : bool {
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( $parts ){ // parts returns false
            return true;
        }

        return false;
    }

    public function load_scripts(){
        wp_enqueue_script( 'lodash' );
        wp_enqueue_script( 'moment' );
        wp_enqueue_script( 'datepicker' );

        wp_enqueue_script( 'mapbox-search-widget', trailingslashit( esc_url( get_stylesheet_directory_uri() ) ) . 'dt-mapping/geocode-api/mapbox-search-widget.js', [ 'jquery', 'mapbox-gl' ], filemtime( get_template_directory() . '/dt-mapping/geocode-api/mapbox-search-widget.js' ), false );
        wp_localize_script(
            "mapbox-search-widget", "dtMapbox", array(
                'post_type' => get_post_type(),
                "post_id" => $post->ID ?? 0,
                "post" => $post_record ?? false,
                "map_key" => DT_Mapbox_API::get_key(),
                "mirror_source" => dt_get_location_grid_mirror( true ),
                "google_map_key" => ( Disciple_Tools_Google_Geocode_API::get_key() ) ? Disciple_Tools_Google_Geocode_API::get_key() : false,
                "spinner_url" => get_stylesheet_directory_uri() . '/spinner.svg',
                "theme_uri" => get_stylesheet_directory_uri(),
                "translations" => array(
                    'add' => __( 'add', 'disciple-tools-training' ),
                    'use' => __( 'Use', 'disciple-tools-training' ),
                    'search_location' => __( 'Search Location', 'disciple-tools-training' ),
                    'delete_location' => __( 'Delete Location', 'disciple-tools-training' ),
                    'open_mapping' => __( 'Open Mapping', 'disciple-tools-training' ),
                    'clear' => __( 'clear', 'disciple-tools-training' )
                )
            )
        );

        if ( Disciple_Tools_Google_Geocode_API::get_key() ){
            wp_enqueue_script( 'google-search-widget', 'https://maps.googleapis.com/maps/api/js?libraries=places&key='.Disciple_Tools_Google_Geocode_API::get_key(), [ 'jquery', 'mapbox-gl' ], '1', false );
        }

    }

    public function print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'lodash',
            'moment',
            'datepicker',
            'site-js',
            'shared-functions',
            'mapbox-gl',
            'mapbox-cookie',
            'mapbox-search-widget',
            'google-search-widget',
            'jquery-cookie',
            'coaching-contact-report'
        ];

        global $wp_scripts;
        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
    }

    public function print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'jquery-ui-site-css',
            'site-css',
            'datepicker-css',
            'mapbox-gl-css'
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        DT_Mapbox_API::mapbox_search_widget_css();
        ?>
        <style>
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #email {
                display:none;
            }
            #wrapper {
                max-width:500px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
            }
            #value {
                width:50px;
                display:inline;
            }
            #type {
                width:75px;
                padding:5px 10px;
                display:inline;
            }
            .title-year {
                font-size:3em;
                font-weight: 100;
                color: #0a0a0a;
            }

            /* size specific style section */
            @media screen and (max-width: 991px) {
                /* start of large tablet styles */

            }
            @media screen and (max-width: 767px) {
                /* start of medium tablet styles */

            }
            @media screen and (max-width: 479px) {
                /* start of phone styles */
                body {
                    background-color: white;
                }
            }
        </style>
        <script>
            var trainingApp = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [
                    'title' => __( 'Register for Training', 'disciple-tools-training' ),
                    'add' => __( 'Add', 'disciple-tools-training' ),
                    'submit' => __( 'Join Training', 'disciple-tools-training' ),
                    'submit_in' => __( 'Join in', 'disciple-tools-training' )
                ],
            ]) ?>][0]

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)

                /* 7 second submit delay to discourage robots */
                let button = jQuery('#submit-button')
                button.html( trainingApp.translations.submit ).prop('disabled', true)
                let counter = 5;
                let myInterval = setInterval(function () {
                    let button = jQuery('#submit-button')
                    button.html( trainingApp.translations.submit_in + ' ' + counter)
                    --counter;
                    if ( counter === 0 ) {
                        clearInterval(myInterval);
                        button.html( trainingApp.translations.submit ).prop('disabled', false)
                    }
                }, 1000);

                /* LOAD */
                let spinner = $('.loading-spinner')
                let title = $('#title')
                let content = $('#content')

                spinner.removeClass('active')

                /* set title */
                title.html( _.escape( trainingApp.translations.title ) )

                write_input_widget()

                button.on('click', function(e){
                    console.log('click')
                    if ( $('#email').val() ){
                        console.log('Buzz buzz')
                        return;
                    }

                    let name = $('#name').val()
                    let phone = $('#phone').val()
                    let email = $('#e').val()

                    /* @todo add submit logic */

                })















                /* FUNCTIONS */
                window.load_reports = ( data ) => {
                    content.empty()
                    $.each(data, function(i,v){
                        content.prepend(`
                                 <div class="cell">
                                     <div class="center"><span class="title-year">${_.escape( i )}</span> </div>
                                     <table class="hover"><tbody id="report-list-${_.escape( i )}"></tbody></table>
                                 </div>
                             `)
                        let list = $('#report-list-'+_.escape( i ))
                        $.each(v, function(ii,vv){
                            list.append(`
                                <tr><td>${_.escape( vv.value )} total ${_.escape( vv.payload.type )} in ${_.escape( vv.label )}</td><td style="vertical-align: middle;"><button type="button" class="button small alert delete-report" data-id="${_.escape( vv.id )}" style="margin: 0;float:right;">&times;</button></td></tr>
                            `)
                        })
                    })

                    $('.delete-report').on('click', function(e){
                        let id = $(this).data('id')
                        $(this).attr('disabled', 'disabled')
                        window.delete_report( id )
                    })

                    spinner.removeClass('active')

                }

                window.get_reports = () => {
                    $.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'get', parts: trainingApp.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: trainingApp.root + trainingApp.parts.root + '/v1/' + trainingApp.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', trainingApp.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_reports( data )
                        })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                        })
                }

                window.get_geojson = () => {
                    return $.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'geojson', parts: trainingApp.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: trainingApp.root + trainingApp.parts.root + '/v1/' + trainingApp.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', trainingApp.nonce )
                        }
                    })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                        })
                }

                window.get_statistics = () => {
                    return $.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'statistics', parts: trainingApp.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: trainingApp.root + trainingApp.parts.root + '/v1/' + trainingApp.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', trainingApp.nonce )
                        }
                    })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                        })
                }

                window.add_new_listener = () => {
                    let d = new Date()
                    let n = d.getFullYear()
                    let e = n - 11
                    let ten_years = ''
                    for(var i = n; i>=e; i--){
                        ten_years += `<option value="${_.escape( i )}-12-31 23:59:59">${_.escape( i )}</option>`.toString()
                    }

                    $('#add-report-button').on('click', function(e){
                        $('#add-form-wrapper').empty().append(`
                            <div class="grid-x grid-x-padding" id="new-report-form">
                                <div class="cell center">
                                    There were <input type="number" id="value" class="number-input" placeholder="#" value="1" />&nbsp;
                                    total&nbsp;
                                    <select id="type" class="select-input">
                                        <option value="groups">groups</option>
                                        <option value="baptisms">baptisms</option>
                                    </select>
                                    in
                                </div>
                                <div class="cell">
                                    <div id="mapbox-wrapper">
                                        <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="false">
                                            <input id="mapbox-search" type="text" name="mapbox_search" class="input-group-field" autocomplete="off" placeholder="${ _.escape( dtMapbox.translations.search_location ) /*Search Location*/ }" />
                                            <div class="input-group-button">
                                                <button id="mapbox-spinner-button" class="button hollow" style="display:none;"><span class="loading-spinner active"></span></button>
                                                <button id="mapbox-clear-autocomplete" class="button alert input-height delete-button-style mapbox-delete-button" type="button" title="${ _.escape( dtMapbox.translations.clear ) /*Delete Location*/}" style="display:none;">&times;</button>
                                            </div>
                                            <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="cell center">at the end of&nbsp;
                                    <select id="year" class="select-input">
                                        ${ten_years}
                                    </select>
                                </div>
                                <div class="cell center" style="padding-left: 5px;" ><button class="button  expanded" type="button" id="save_new_report" disabled="disabled">Save</button></div>
                            </div>
                        `)

                        write_input_widget()

                        $('.number-input').focus(function(e){
                            window.currentEvent = e
                            if ( e.currentTarget.value === '1' ){
                                e.currentTarget.value = ''
                            }
                        })

                        $('#save_new_report').on('click', function(){
                            window.insert_report()
                            $('#add-form-wrapper').empty()
                        })

                        $('#mapbox-search').on('change', function(e){
                            if ( typeof window.selected_location_grid_meta !== 'undefined' || window.selected_location_grid_meta !== '' ) {
                                $('#save_new_report').removeAttr('disabled')
                            }
                        })
                    })
                }

                window.insert_report = () => {
                    spinner.addClass('active')

                    let year = $('#year').val()
                    let value = $('#value').val()
                    let type = $('#type').val()

                    let report = {
                        action: 'insert',
                        parts: trainingApp.parts,
                        type: type,
                        subtype: type,
                        value: value,
                        time_end: year
                    }

                    if ( typeof window.selected_location_grid_meta.location_grid_meta !== 'undefined' || window.selected_location_grid_meta.location_grid_meta !== '' ) {
                        report.location_grid_meta = window.selected_location_grid_meta.location_grid_meta
                    }

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(report),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: trainingApp.root + trainingApp.parts.root + '/v1/' + trainingApp.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', trainingApp.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_reports( data )
                        })
                        .fail(function(e) {
                            console.log(e)
                            jQuery('#error').html(e)
                        })
                }

                window.delete_report = ( id ) => {
                    spinner.addClass('active')

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'delete', parts: trainingApp.parts, report_id: id }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: trainingApp.root + trainingApp.parts.root + '/v1/' + trainingApp.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', trainingApp.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_reports( data )
                        })
                        .fail(function(e) {
                            console.log(e)
                            jQuery('#error').html(e)
                        })
                }
            })
        </script>
        <?php
    }

    public function home_body(){
        // FORM BODY
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x" data-sticky-container>
                <div class="cell center" data-sticky>
                    <span id="title"></span>
                </div>
            </div>
            <div class="grid-x grid-padding-x" id="main-section" style="height: inherit !important;">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content">
                    <form data-abide novalidate>
                        <div  class="grid-x">
                            <div class="cell">
                                Description
                            </div>
                            <div class="cell">
                                Name <br>
                                <input name="name" type="text" id="name" placeholder="Name" required />
                            </div>
                            <div class="cell">
                                Email <br>
                                <input name="email" type="email" id="email" value="something" placeholder="Email" />
                                <input name="e" type="email" id="e" placeholder="Email" required />
                            </div>
                            <div class="cell">
                                Phone <br>
                                <input name="name" type="text" id="phone" placeholder="Phone" required />
                            </div>
                            <div class="cell">
                                Location <br>
                                <div id="mapbox-wrapper">
                                    <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="false" data-add-address="false">
                                        <input id="mapbox-search" type="text" name="mapbox_search" class="input-group-field" autocomplete="off" placeholder="<?php echo esc_html__( 'Search Location', 'disciple-tools-training' ) ?>" />
                                        <div class="input-group-button">
                                            <button id="mapbox-spinner-button" class="button hollow" style="display:none;border-color:lightgrey;">
                                                <span class="" style="border-radius: 50%;width: 24px;height: 24px;border: 0.25rem solid lightgrey;border-top-color: black;animation: spin 1s infinite linear;display: inline-block;"></span>
                                            </button>
                                            <button id="mapbox-clear-autocomplete" class="button alert input-height delete-button-style mapbox-delete-button" type="button" title="<?php echo esc_html__( 'Clear', 'disciple-tools-training' ) ?>" style="display:none;">&times;</button>
                                        </div>
                                        <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="cell">
                                Comments <br>
                                <input name="name" type="text" />
                            </div>
    <!--                        <div class="cell">-->
    <!--                            Footer-->
    <!--                        </div>-->
                            <div class="cell center">
                                <button type="button" id="submit-button" class="button" ></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <?php
    }

    /**
     * Open default restrictions for access to registered endpoints
     * @param $authorized
     * @return bool
     */
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->root . '/v1/'.$this->type ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'endpoint' ],
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );

        // validate
        $magic = new DT_Magic_URL( $this->root );
        $post_id = $magic->get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );

        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        switch ( $action ) {
            case 'insert':
                return $this->insert_report( $params, $post_id );
            case 'get':
                return $this->retrieve_reports( $post_id );
            case 'delete':
                return $this->delete_report( $params, $post_id );
            case 'geojson':
                return $this->geojson_reports( $params, $post_id );
            case 'statistics':
                return $this->statistics_reports( $params, $post_id );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function insert_report( $params, $post_id ) {

        // @todo test if values set

        // @todo load location

        // run your function here
        $args = [
            'parent_id' => null,
            'post_id' => $post_id,
            'post_type' => 'contacts',
            'type' => $params['parts']['root'],
            'subtype' => $params['parts']['type'],
            'payload' => [
                'type' => $params['type'] // groups or baptisms
            ],
            'value' => $params['value'] ?? 1,
            'time_begin' => empty( $params['time_begin'] ) ? null : strtotime( $params['time_begin'] ),
            'time_end' => empty( $params['time_end'] ) ? time() : strtotime( $params['time_end'] ),
            'timestamp' => time(),
        ];

        if ( isset( $params['location_grid_meta'] ) ){
            $args['lng'] = $params['location_grid_meta']['values'][0]['lng'];
            $args['lat'] = $params['location_grid_meta']['values'][0]['lat'];
            $args['level'] = $params['location_grid_meta']['values'][0]['level'];
            $args['label'] = $params['location_grid_meta']['values'][0]['label'];

            $geocoder = new Location_Grid_Geocoder();
            $grid_row = $geocoder->get_grid_id_by_lnglat( $args['lng'], $args['lat'] );
            if ( ! empty( $grid_row ) ){
                $args['grid_id'] = $grid_row['grid_id'];
            }
        }

        $report_id = dt_report_insert( $args );

        if ( is_wp_error( $report_id ) || empty( $report_id ) ){
            return new WP_Error( __METHOD__, "Failed to create report.", [ 'status' => 400 ] );
        }

        return $this->retrieve_reports( $post_id );

    }

    public function retrieve_reports( $post_id ) {
        global $wpdb;
        $data = [];

        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->dt_reports WHERE post_id = %s ORDER BY time_end DESC", $post_id ), ARRAY_A );
        if ( ! empty( $results ) ) {
            foreach ( $results as $index => $result ){
                $time = $result['time_end'];
                if ( empty( $time ) ) {
                    $time = $result['time_begin'];
                }
                if ( empty( $time ) ) {
                    continue;
                }
                $year = gmdate( 'Y', $time );
                if ( ! isset( $data[$year] ) ) {
                    $data[$year] = [];
                }
                $result['payload'] = maybe_unserialize( $result['payload'] );
                $data[$year][] = $result;
            }
        }
        return $data;
    }

    public function statistics_reports( $params, $post_id ) {
        global $wpdb;
        $data = [];

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT r.*,  lg.level_name, lg.name, lg.admin0_grid_id, lg0.name as country, lg.admin1_grid_id, lg1.name as state, lg.admin2_grid_id, lg2.name as county
            FROM $wpdb->dt_reports as r
            LEFT JOIN $wpdb->dt_location_grid as lg
            ON r.grid_id= lg.grid_id
            LEFT JOIN $wpdb->dt_location_grid as lg0
            ON lg.admin0_grid_id=lg0.grid_id
            LEFT JOIN $wpdb->dt_location_grid as lg1
            ON lg.admin1_grid_id=lg1.grid_id
            LEFT JOIN $wpdb->dt_location_grid as lg2
            ON lg.admin2_grid_id=lg2.grid_id
            WHERE post_id = %s
            ORDER BY time_end DESC
            ", $post_id ), ARRAY_A );

        if ( empty( $results ) ){
            return [];
        }

        $countries = [];
        $states = [];
        $counties = [];

        foreach ( $results as $index => $result ){
            /*time*/
            $time = $result['time_end'];
            if ( empty( $time ) ) {
                $time = $result['time_begin'];
            }
            if ( empty( $time ) ) {
                continue;
            }
            $year = gmdate( 'Y', $time );
            if ( ! isset( $data[$year] ) ) {
                $data[$year] = [
                    'total_groups' => 0,
                    'total_baptisms' => 0,
                    'total_countries' => 0,
                    'total_states' => 0,
                    'total_counties' => 0,
                    'countries' => [],
                    'states' => [],
                    'counties' => []
                ];
            }
            $result['payload'] = maybe_unserialize( $result['payload'] );

            if ( ! isset( $countries[$result['admin0_grid_id'] ] ) ) {
                $countries[$result['admin0_grid_id'] ] = [
                    'groups' => 0,
                    'baptisms' => 0,
                    'name' => $result['country']
                ];
            }
            if ( ! isset( $states[$result['admin1_grid_id'] ] ) ) {
                $states[$result['admin1_grid_id'] ] = [
                    'groups' => 0,
                    'baptisms' => 0,
                    'name' => $result['state'] . ', ' . $result['country']
                ];
            }
            if ( ! isset( $counties[$result['admin2_grid_id'] ] ) ) {
                $counties[$result['admin2_grid_id'] ] = [
                    'groups' => 0,
                    'baptisms' => 0,
                    'name' => $result['county'] . ', ' . $result['state'] . ', ' . $result['country']
                ];
            }

            // add groups and baptisms
            if ( $result['payload']['type'] === 'groups' ) {
                $data[$year]['total_groups'] = $data[$year]['total_groups'] + $result['value']; // total
                $countries[$result['admin0_grid_id']] = $countries[$result['admin0_grid_id']]['groups'] + $result['value']; // country
                $states[$result['admin1_grid_id']] = $states[$result['admin1_grid_id']]['groups'] + $result['value']; // state
                $counties[$result['admin2_grid_id']] = $counties[$result['admin2_grid_id']]['groups'] + $result['value']; // counties
            }
            else if ( $result['payload']['type'] === 'baptisms' ) {
                $data[$year]['total_baptisms'] = $data[$year]['total_baptisms'] + $result['value'];
                $countries[$result['admin0_grid_id']] = $countries[$result['admin0_grid_id']]['baptisms'] + $result['value'];
                $states[$result['admin1_grid_id']] = $states[$result['admin1_grid_id']]['baptisms'] + $result['value'];
                $counties[$result['admin2_grid_id']] = $counties[$result['admin2_grid_id']]['baptisms'] + $result['value'];
            }

            $data[$year]['total_countries'] = count( $countries );
            $data[$year]['total_states'] = count( $states );
            $data[$year]['total_counties'] = count( $counties );

            $data[$year]['countries'] = $countries;
            $data[$year]['states'] = $states;
            $data[$year]['counties'] = $counties;

        }

        return $data;
    }

    public function delete_report( $params, $post_id ) {
        $result = Disciple_Tools_Reports::delete( $params['report_id'] );
        if ( ! $result ) {
            return new WP_Error( __METHOD__, "Failed to delete report", [ 'status' => 400 ] );
        }
        return $this->retrieve_reports( $post_id );
    }

    public function geojson_reports( $params, $post_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->dt_reports WHERE post_id = %s ORDER BY time_end DESC", $post_id ), ARRAY_A );

        if ( empty( $results ) ) {
            return $this->_empty_geojson();
        }

        foreach ($results as $index => $result) {
            $results[$index]['payload'] = maybe_unserialize( $result['payload'] );
        }

        // @todo sum multiple reports for same area

        $features = [];
        foreach ($results as $result) {
            // get year
            $time = $result['time_end'];
            if ( empty( $time ) ) {
                $time = $result['time_begin'];
            }
            if ( empty( $time ) ) {
                continue;
            }
            $year = gmdate( 'Y', $time );

            // build feature
            $features[] = array(
                'type' => 'Feature',
                'properties' => array(
                    'value' => $result['value'],
                    'type' => $result['payload']['type'] ?? '',
                    'year' => $year,
                    'label' => $result['label']
                ),
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

        $geojson = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $geojson;
    }

    private function _empty_geojson() {
        return array(
            'type' => 'FeatureCollection',
            'features' => array()
        );
    }

}

