<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Training_App_Calendar_Module extends DT_Module_Base
{
    public $module = 'trainings_calendar_module';
    public $post_type = 'streams';

    public $magic = false;
    public $parts = false;
    public $root = 'streams_app'; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'calendar'; // define the type


    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }
        // register tiles if on details page
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 5, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'tile_scripts' ], 100 );


        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );


        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'custom_fields' ], 10, 2 );



        // fail if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        if ( $this->magic->is_valid_key_url( $this->type ) && 'stats' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'stats_body' ] );
        }
        else if ( $this->magic->is_valid_key_url( $this->type ) && 'maps' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'maps_body' ] );
        }
        else if ( $this->magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'home_body' ] );
        } else {
            // fail if no valid action url found
            return;
        }

        // load page elements
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === 'streams' ){
            $tiles['reports'] = [ 'label' => __( 'Reports', 'disciple-tools-training' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if streams post type and streams_app_module enabled
        if ( $post_type === 'streams' ) {

            // reports tile
            if ( $section === 'reports' ){
                $magic = new DT_Magic_URL( 'streams_app' );
                $types = $magic->list_types();

                // types
                if ( isset( $types['report'], $types['report']['root'], $types['report']['type'] ) ) {
                    $types['report']['new_key'] = $magic->create_unique_key();

                    $reports = self::instance()->statistics_reports( get_the_ID() );
                    /**
                     * Button Controls
                     */
                    ?>
                    <div class="cell" id="<?php echo esc_attr( $types['report']['root'] ) ?>-<?php echo esc_attr( $types['report']['type'] ) ?>-wrapper"></div>
                    <?php
                    /**
                     * List Reports
                     */
                    if ( ! empty( $reports ) ) {
                        foreach ( $reports as $year => $report ){
                            ?>
                            <div class="section-subheader">
                                Reports in <?php echo esc_html( $year ) ?>
                            </div>
                            <div class="reports-for-<?php echo esc_html( $year ) ?>">
                                <div class="grid-x">
                                    <div class="cell small-6"><?php echo esc_html__( 'Total Groups', 'disciple-tools-training' ) ?></div><div class="cell small-6"><?php echo esc_html( $report['total_groups'] ) ?></div>
                                    <div class="cell small-6"><?php echo esc_html__( 'Total Baptisms', 'disciple-tools-training' ) ?></div><div class="cell small-6"><?php echo esc_html( $report['total_baptisms'] ) ?></div>
                                    <div class="cell small-6"><?php echo esc_html__( 'Countries', 'disciple-tools-training' ) ?></div><div class="cell small-6"><?php echo esc_html( $report['total_countries'] ) ?></div>
                                    <div class="cell small-6"><?php echo esc_html__( 'States', 'disciple-tools-training' ) ?></div><div class="cell small-6"><?php echo esc_html( $report['total_states'] ) ?></div>
                                    <div class="cell small-6"><?php echo esc_html__( 'Counties', 'disciple-tools-training' ) ?></div><div class="cell small-6"><?php echo esc_html( $report['total_counties'] ) ?></div>
                                </div>
                            </div>
                            <div><hr>
                                <a class="button hollow" id="<?php echo esc_attr( $types['report']['root'] ) ?>-<?php echo esc_attr( $types['report']['type'] ) ?>-manage-reports">manage reports</a>
                            </div>
                            <?php
                            break; // loop only the most recent year
                        }
                    } else {
                        ?>
                        <div class="section-subheader">
                            No Reports
                        </div>
                        <?php
                    }
                    ?>
                    <?php
                }
            } /* end stream/app if*/

        } // end if streams and enabled
    }

    public function tile_scripts(){
        if ( is_singular( 'streams' ) ){
            $magic = new DT_Magic_URL( 'streams_app' );
            $types = $magic->list_types();
            $report = $types['report'] ?? [];
            $report['new_key'] = $magic->create_unique_key();

            wp_localize_script( // add object to streams-post-type.js
                'dt_streams', 'trainings_calendar_module', [
                    'report' => $report,
                ]
            );
        }
    }

    public function register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => 'Stream Report',
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => $this->root . '_' . $this->type . '_public_key', // coaching-magic_c_key
            'actions' => [
                '' => 'Home',
                'stats' => 'Stats',
                'maps' => 'Maps'
            ],
            'post_type' => $this->post_type,
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
        $actions = $this->magic->list_actions( $parts['type'] );
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

    public function custom_fields( $fields, $post_type ){
        if ( $post_type === 'streams' ){
            // do action
            $fields[$this->root . '_' . $this->type . '_public_key'] = [
                'name'   => 'Private Report Key',
                'description' => '',
                'type'   => 'hash',
                'hidden' => true,
            ];
            $fields['report_last_modified'] = [
                'name'   => 'Last Report',
                'description' => 'Stores the time of the last insert or delete performed on reports.',
                'type' => 'date',
                'default' => '',
                'show_in_table' => true
            ];
        }
        return $fields;
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
        ];

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
//        dt_write_log($wp_scripts->queue);
//        dt_write_log($wp_scripts);
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
            foreach ( $wp_styles->queue as $key => $item ) {
                if ( !in_array( $item, $allowed_css ) ) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->report_styles_header();
        $this->report_javascript_header();
    }

    public function report_styles_header(){
        ?>
        <style>
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #top-bar {
                position:relative;
                padding-bottom:1em;
            }
            #add-new {
                padding-top:1em;
            }
            #top-loader {
                position:absolute;
                right:5px;
                top: 5px;
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
            #mapbox-search {
                padding:5px 10px;
                border-bottom-color: rgb(138, 138, 138);
            }
            #year {
                width:75px;
                display:inline;
            }
            #new-report-form {
                padding: 1em .5em;
                background-color: #f4f4f4;;
                border: 1px solid #3f729b;
                font-weight: bold;
            }
            .number-input {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
                text-align:center;
            }
            .stat-heading {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 3.5rem;
            }
            .stat-year {
                font-size: 2rem;
                color: darkgrey;
            }
            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }
            .select-input {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
                text-align:center;
            }
            select::-ms-expand {
                display: none;
            }
            .input-group-field {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                padding:0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
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
        <?php
    }

    public function report_javascript_header(){
        ?>
        <script>
            var postReport = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id'] ),
                'translations' => [
                    'add' => __( 'Add', 'disciple-tools-training' ),
                    'search_location' => 'Search for Location'
                ],
            ]) ?>][0]

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)


                /* LOAD */
                let spinner = $('.loading-spinner')
                let title = $('#title')
                let content = $('#content')

                /* set title */
                title.html( window.lodash.escape( postReport.name ) )

                /* FUNCTIONS */
                window.load_reports = ( data ) => {
                    content.empty()
                    $.each(data, function(i,v){
                        content.prepend(`
                                 <div class="cell">
                                     <div class="center"><span class="title-year">${window.lodash.escape( i )}</span> </div>
                                     <table class="hover"><tbody id="report-list-${window.lodash.escape( i )}"></tbody></table>
                                 </div>
                             `)
                        let list = $('#report-list-'+window.lodash.escape( i ))
                        $.each(v, function(ii,vv){
                            list.append(`
                                <tr><td>${window.lodash.escape( vv.value )} total ${window.lodash.escape( vv.payload.type )} in ${window.lodash.escape( vv.label )}</td><td style="vertical-align: middle;"><button type="button" class="button small alert delete-report" data-id="${window.lodash.escape( vv.id )}" style="margin: 0;float:right;">&times;</button></td></tr>
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
                        data: JSON.stringify({ action: 'get', parts: postReport.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postReport.root + postReport.parts.root + '/v1/' + postReport.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postReport.nonce )
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
                        data: JSON.stringify({ action: 'geojson', parts: postReport.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postReport.root + postReport.parts.root + '/v1/' + postReport.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postReport.nonce )
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
                        data: JSON.stringify({ action: 'statistics', parts: postReport.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postReport.root + postReport.parts.root + '/v1/' + postReport.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postReport.nonce )
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
                        ten_years += `<option value="${window.lodash.escape( i )}-12-31 23:59:59">${window.lodash.escape( i )}</option>`.toString()
                    }

                    $('#add-report-button').on('click', function(e){
                        $('#add-report-button').hide()
                        $('#add-form-wrapper').empty().append(`
                            <div class="grid-x grid-x-padding" id="new-report-form">
                                <div class="cell center">
                                    There are <input type="number" id="value" class="number-input" placeholder="#" value="1" />&nbsp;
                                    total&nbsp;
                                    <select id="type" class="select-input">
                                        <option value="groups">groups</option>
                                        <option value="baptisms">baptisms</option>
                                    </select>
                                    in
                                </div>
                                <div class="cell">
                                    <div id="mapbox-wrapper">
                                        <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="false" data-add-address="true">
                                            <input id="mapbox-search" type="text" name="mapbox_search" class="input-group-field" autocomplete="off" placeholder="${ window.lodash.escape( postReport.translations.search_location ) /*Search Location*/ }" />
                                            <div class="input-group-button">
                                                <button id="mapbox-spinner-button" class="button hollow" style="display:none;border-color:lightgrey;">
                                                    <span class="" style="border-radius: 50%;width: 24px;height: 24px;border: 0.25rem solid lightgrey;border-top-color: black;animation: spin 1s infinite linear;display: inline-block;"></span>
                                                </button>
                                                <button id="mapbox-clear-autocomplete" class="button alert input-height delete-button-style mapbox-delete-button" type="button" title="${ window.lodash.escape( postReport.translations.clear ) /*Delete Location*/}" style="display:none;">&times;</button>
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
                                <div class="cell center" >
                                    <button class="button large save-report" type="button" id="save_new_report" disabled="disabled">Save</button>
                                    <button class="button large save-report" type="button" id="save_and_add_new_report" disabled="disabled">Save and Add</button>
                                    <button class="button large alert" type="button" id="cancel_new_report">&times;</button>
                                </div>
                            </div>
                        `)

                        window.write_input_widget()

                        $('.number-input').focus(function(e){
                            window.currentEvent = e
                            if ( e.currentTarget.value === '1' ){
                                e.currentTarget.value = ''
                            }
                        })

                        $('#save_new_report').on('click', function(){
                            window.insert_report()
                            $('#add-form-wrapper').empty()
                            $('#add-report-button').show()
                        })

                        $('#save_and_add_new_report').on('click', function(){
                            window.insert_report()
                            $('#add-form-wrapper').empty()
                            $('#add-report-button').click()
                            $('#value').focus()
                        })

                        $('#cancel_new_report').on('click', function(){
                            window.get_reports()
                            $('#add-form-wrapper').empty()
                            $('#add-report-button').show()
                        })

                        $('#mapbox-search').on('change', function(e){
                            if ( typeof window.selected_location_grid_meta !== 'undefined' || window.selected_location_grid_meta !== '' ) {
                                $('.save-report').removeAttr('disabled')
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
                        parts: postReport.parts,
                        type: type,
                        subtype: type,
                        value: value,
                        time_end: year
                    }

                    if ( typeof window.selected_location_grid_meta !== 'undefined' && ( typeof window.selected_location_grid_meta.location_grid_meta !== 'undefined' || window.selected_location_grid_meta.location_grid_meta !== '' ) ) {
                        report.location_grid_meta = window.selected_location_grid_meta.location_grid_meta
                    }
                    else if ( $('#new_contact_address').val() ) {
                        report.address = $('#new_contact_address').val()
                    }

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(report),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postReport.root + postReport.parts.root + '/v1/' + postReport.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postReport.nonce )
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
                        data: JSON.stringify({ action: 'delete', parts: postReport.parts, report_id: id }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postReport.root + postReport.parts.root + '/v1/' + postReport.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postReport.nonce )
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
        $actions = $this->magic->list_actions( $this->type );

        // FORM BODY
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x ">
                <div class="cell center">
                    <?php
                    foreach ( $actions as $action => $label ) {
                        ?>
                        <a href="<?php echo esc_url( trailingslashit( site_url() ) .  esc_attr( $this->parts['root'] ) . '/' . esc_attr( $this->parts['type'] ) . '/'. esc_attr( $this->parts['public_key'] ) . '/'. esc_html( $action ) ) ?>" class="button small hollow"><?php echo esc_html( $label ) ?></a>
                        <?php
                    }
                    ?>
                </div>
                <div class="cell center" id="title"></div>
            </div>
            <div class="grid-x" id="add-new">
                <div class="cell center"><button type="button" id="add-report-button" class="button large" style="min-width:200px;">Add</button></div>
                <div id="add-form-wrapper"></div>
            </div>
            <hr>
            <div class="grid-x grid-padding-x" id="main-section" style="height: inherit !important;">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content"><div class="center">... loading</div></div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){
                window.get_reports()
                window.add_new_listener()
            })
        </script>
        <?php
    }

    public function stats_body(){
        $actions = $this->magic->list_actions( $this->type );

        // FORM BODY
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x ">
                <div class="cell center">
                    <?php
                    foreach ( $actions as $action => $label ) {
                        ?>
                        <a href="<?php echo esc_url( trailingslashit( site_url() ) .  esc_attr( $this->parts['root'] ) . '/' . esc_attr( $this->parts['type'] ) . '/'. esc_attr( $this->parts['public_key'] ) . '/'. esc_html( $action ) ) ?>" class="button small hollow"><?php echo esc_html( $label ) ?></a>
                        <?php
                    }
                    ?>
                </div>
                <div class="cell center" id="title"></div>
            </div>
            <hr>
            <div class="grid-x grid-padding-x">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content"><div class="center">... loading</div></div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <script>

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)

                /* LOAD */
                let spinner = $('.loading-spinner')
                let title = $('#title')
                let content = $('#content')

                /* set title */
                title.html( postReport.translations.title )

                /* set vertical size the form column*/
                $('#custom-style').append(`<style>#wrapper { height: inherit !important; }</style>`)

                window.get_statistics().then(function(data){
                    console.log(data)
                    content.empty()
                    $.each(data, function(i,v){
                        content.prepend(`
                        <div class="grid-x">
                            <div class="cell center">
                                <span class="stat-year">${i}</span><br>
                            </div>
                            <div class="cell center">
                                <span class="stat-heading">Total Groups</span><br>
                                <span id="total_groups" class="stat-number">${v.total_groups}</span>
                            </div>
                            <div class="cell center">
                                <span class="stat-heading">Total Baptisms</span><br>
                                <span id="total_groups" class="stat-number">${v.total_baptisms}</span>
                            </div>
                            <div class="cell center">
                                <span class="stat-heading">Engaged Countries</span><br>
                                <span id="total_groups" class="stat-number">${v.total_countries}</span>
                            </div>
                            <div class="cell center">
                                <span class="stat-heading">Engaged States</span><br>
                                <span id="total_groups" class="stat-number">${v.total_states}</span>
                            </div>
                            <div class="cell center">
                                <span class="stat-heading">Engaged Counties</span><br>
                                <span id="total_groups" class="stat-number">${v.total_counties}</span>
                            </div>
                        </div>
                        <hr>
                    `)

                    })

                    spinner.removeClass('active')

                })/* end get_statistics */
            }) /* end .ready */
        </script>
        <?php
    }

    public function maps_body(){
        $actions = $this->magic->list_actions( $this->type );

        // FORM BODY
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x ">
                <div class="cell center">
                    <?php
                    foreach ( $actions as $action => $label ) {
                        ?>
                        <a href="<?php echo esc_url( trailingslashit( site_url() ) .  esc_attr( $this->parts['root'] ) . '/' . esc_attr( $this->parts['type'] ) . '/'. esc_attr( $this->parts['public_key'] ) . '/'. esc_html( $action ) ) ?>" class="button small hollow"><?php echo esc_html( $label ) ?></a>
                        <?php
                    }
                    ?>
                </div>
                <div class="cell center" id="title"></div>
            </div>
            <div class="grid-x grid-padding-x">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content">
                    <div id="map-wrapper">
                        <div id='map'></div>
                    </div>
                </div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)

                /* LOAD */
                let spinner = $('.loading-spinner')
                let title = $('#title')

                /* set title */
                title.html( postReport.translations.title )

                /* set vertical size the form column*/
                $('#custom-style').append(`
                    <style>
                        #wrapper {
                            height: ${window.innerHeight}px !important;
                        }
                        #map-wrapper {
                            height: ${window.innerHeight-100}px !important;
                        }
                        #map {
                            height: ${window.innerHeight-100}px !important;
                        }
                    </style>`)


                window.get_geojson().then(function(data){
                    mapboxgl.accessToken = postReport.map_key;
                    var map = new mapboxgl.Map({
                        container: 'map',
                        style: 'mapbox://styles/mapbox/light-v10',
                        center: [-98, 38.88],
                        minZoom: 0,
                        zoom: 0
                    });

                    // disable map rotation using right click + drag
                    map.dragRotate.disable();

                    // disable map rotation using touch rotation gesture
                    map.touchZoomRotate.disableRotation();

                    map.on('load', function() {
                        map.addSource('layer-source-reports', {
                            type: 'geojson',
                            data: data,
                        });

                        /* groups */
                        map.addLayer({
                            id: 'layer-groups-circle',
                            type: 'circle',
                            source: 'layer-source-reports',
                            paint: {
                                'circle-color': '#90C741',
                                'circle-radius': 22,
                                'circle-stroke-width': 0.5,
                                'circle-stroke-color': '#fff'
                            },
                            filter: ['==', 'groups', ['get', 'type'] ]
                        });
                        map.addLayer({
                            id: 'layer-groups-count',
                            type: 'symbol',
                            source: 'layer-source-reports',
                            layout: {
                                "text-field": ['get', 'value']
                            },
                            filter: ['==', 'groups', ['get', 'type'] ]
                        });

                        /* baptism */
                        map.addLayer({
                            id: 'layer-baptisms-circle',
                            type: 'circle',
                            source: 'layer-source-reports',
                            paint: {
                                'circle-color': '#51bbd6',
                                'circle-radius': 22,
                                'circle-stroke-width': 0.5,
                                'circle-stroke-color': '#fff'
                            },
                            filter: ['==', 'baptisms', ['get', 'type'] ]
                        });
                        map.addLayer({
                            id: 'layer-baptisms-count',
                            type: 'symbol',
                            source: 'layer-source-reports',
                            layout: {
                                "text-field": ['get', 'value']
                            },
                            filter: ['==', 'baptisms', ['get', 'type'] ]
                        });

                        spinner.removeClass('active')

                        // SET BOUNDS
                        window.map_bounds_token = 'report_activity_map'
                        window.map_start = get_map_start( window.map_bounds_token )
                        if ( window.map_start ) {
                            map.fitBounds( window.map_start, {duration: 0});
                        }
                        map.on('zoomend', function() {
                            set_map_start( window.map_bounds_token, map.getBounds() )
                        })
                        map.on('dragend', function() {
                            set_map_start( window.map_bounds_token, map.getBounds() )
                        })
                        // end set bounds
                    });

                })
            })
        </script>
        <?php
    }

    /**
     * Open default restrictions for access to registered endpoints
     * @param bool $authorized
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
                    'permission_callback' => '__return_true',
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type.'/all', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'endpoint_all' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );

        // validate
        $magic = $this->magic;
        $post_id = $magic->get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );

        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        $params = dt_recursive_sanitize_array( $params );

        switch ( $action ) {
            case 'insert':
                return $this->insert_report( $params, $post_id );
            case 'get':
                return $this->retrieve_reports( $post_id );
            case 'delete':
                return $this->delete_report( $params, $post_id );
            case 'geojson':
                return $this->geojson_reports( $post_id );
            case 'statistics':
                return $this->statistics_reports( $post_id );
            case 'get_all':
                $data = [];
                $data['reports'] = $this->retrieve_reports( $post_id );
                $data['geojson'] = $this->geojson_reports( $post_id );
                return $data;
            default:
                return new WP_Error( __METHOD__, 'Missing valid action', [ 'status' => 400 ] );
        }
    }

    public function endpoint_all( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['post_id'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        $post_id =sanitize_text_field( wp_unslash( $params['post_id'] ) );

        if ( ! Disciple_Tools_Posts::can_view( 'streams', $post_id ) ) {
            return new WP_Error( __METHOD__, 'Do not have permission', [ 'status' => 401 ] );
        }

        $data = [];
        $data['reports'] = $this->retrieve_reports( $post_id );
        $data['stats'] = $this->statistics_reports( $post_id );
        $data['geojson'] = $this->geojson_reports( $post_id );
        return $data;
    }

    public function insert_report( $params, $post_id ) {

        if ( ! isset( $params['parts']['root'], $params['parts']['type'], $params['type'], $post_id ) ){
            return new WP_Error( __METHOD__, 'Missing params', [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );

        // run your function here
        $args = [
            'parent_id' => null,
            'post_id' => $post_id,
            'post_type' => 'streams',
            'type' => $params['parts']['root'],
            'subtype' => $params['parts']['type'],
            'payload' => [
                'type' => $params['type'] // groups or baptisms
            ],
            'value' => 1,
            'time_begin' => empty( $params['time_begin'] ) ? null : strtotime( $params['time_begin'] ),
            'time_end' => empty( $params['time_end'] ) ? time() : strtotime( $params['time_end'] ),
            'timestamp' => time(),
        ];

        if ( ! empty( $params['value'] ) ){
            $args['value'] = $params['value'];
        }

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
        } else if ( isset( $params['address'] ) ) {
            $args['label'] = $params['address'];
        }

        $report_id = dt_report_insert( $args );

        if ( is_wp_error( $report_id ) || empty( $report_id ) ){
            return new WP_Error( __METHOD__, 'Failed to create report.', [ 'status' => 400 ] );
        }

        update_post_meta( $post_id, 'report_last_modified', time() );

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

    public function statistics_reports( $post_id ) : array {
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

            if ( empty( $result['grid_id'] ) ) {
                continue;
            }

            // set levels
            if ( ! isset( $countries[$result['admin0_grid_id'] ] ) ) {
                $countries[ $result['admin0_grid_id'] ] = [
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
            if ( isset( $result['payload']['type'] ) && $result['payload']['type'] === 'groups' ) {
                $data[$year]['total_groups'] = $data[$year]['total_groups'] + intval( $result['value'] ); // total
                $countries[$result['admin0_grid_id']]['groups'] = $countries[$result['admin0_grid_id']]['groups'] + intval( $result['value'] ); // country
                $states[$result['admin1_grid_id']]['groups'] = $states[$result['admin1_grid_id']]['groups'] + intval( $result['value'] ); // state
                $counties[$result['admin2_grid_id']]['groups'] = $counties[$result['admin2_grid_id']]['groups'] + intval( $result['value'] ); // counties
            }
            else if ( isset( $result['payload']['type'] ) && $result['payload']['type'] === 'baptisms' ) {
                $data[$year]['total_baptisms'] = $data[$year]['total_baptisms'] + intval( $result['value'] );
                $countries[$result['admin0_grid_id']]['baptisms'] = $countries[$result['admin0_grid_id']]['baptisms'] + intval( $result['value'] );
                $states[$result['admin1_grid_id']]['baptisms'] = $states[$result['admin1_grid_id']]['baptisms'] + intval( $result['value'] );
                $counties[$result['admin2_grid_id']]['baptisms'] = $counties[$result['admin2_grid_id']]['baptisms'] + intval( $result['value'] );
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
            return new WP_Error( __METHOD__, 'Failed to delete report', [ 'status' => 400 ] );
        }

        update_post_meta( $post_id, 'report_last_modified', time() );

        return $this->retrieve_reports( $post_id );
    }

    public function geojson_reports( $post_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->dt_reports WHERE post_id = %s ORDER BY time_end DESC", $post_id ), ARRAY_A );

        if ( empty( $results ) ) {
            return $this->_empty_geojson();
        }

        foreach ( $results as $index => $result ) {
            $results[$index]['payload'] = maybe_unserialize( $result['payload'] );
        }

        // @todo sum multiple reports for same area

        $features = [];
        foreach ( $results as $result ) {
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