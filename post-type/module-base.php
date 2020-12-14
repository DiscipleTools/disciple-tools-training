<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Training_Base extends DT_Module_Base {
    private static $_instance = null;
    public $post_type = "trainings";
    public $module = "trainings_base";
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

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 );

        //setup tiles and fields
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        add_action( 'dt_render_field_for_display_template', [ $this, 'render_datetime_field' ], 10, 5 );

        // hooks
        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
        add_filter( "dt_post_update_fields", [ $this, "dt_post_update_fields" ], 10, 3 );
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
        add_action( "dt_post_created", [ $this, "dt_post_created" ], 10, 3 );
        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );

        //list
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );
    }


    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( "trainings", 'Training', 'Trainings' );
        }
    }
    public function dt_set_roles_and_permissions( $expected_roles ){
        if ( !isset( $expected_roles["multiplier"] ) ){
            $expected_roles["multiplier"] = [
                "label" => __( 'Multiplier', 'disciple_tools' ),
                "permissions" => []
            ];
        }
        if ( !isset( $expected_roles["dispatcher"] ) ){
            $expected_roles["dispatcher"] = [
                "label" => __( 'Dispatcher', 'disciple_tools' ),
                "description" => "All D.T permissions",
                "permissions" => []
            ];
        }
        if ( !isset( $expected_roles["dt_admin"] ) ){
            $expected_roles["dt_admin"] = [
                "label" => __( 'Disciple.Tools Admin', 'disciple_tools' ),
                "description" => "All D.T permissions",
                "permissions" => []
            ];
        }
        if ( !isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"] = [
                "label" => __( 'Administrator', 'disciple_tools' ),
                "description" => "All D.T permissions plus the ability to manage plugins.",
                "permissions" => []
            ];
        }

        foreach ( $expected_roles as $role => $role_value ){

            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
                $expected_roles[$role]["permissions"]['access_' . $this->post_type] = true;
                $expected_roles[$role]["permissions"]['create_' . $this->post_type] = true;
            }
            if ( in_array( $role, [ 'administrator', 'dispatcher', 'dt_admin' ] ) ) {
                $expected_roles[$role]["permissions"]['view_any_' . $this->post_type] = true;
            }
        }

        return $expected_roles;
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'trainings' ){
            // framework fields
            $fields['tags'] = [
                'name'        => __( 'Tags', 'disciple_tools' ),
                'description' => _x( 'A useful way to training related items and can help training contacts associated with noteworthy characteristics. e.g. business owner, sports lover. The contacts can also be filtered using these tags.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'tile'        => 'other',
                'custom_display' => true,
            ];
            $fields["follow"] = [
                'name'        => __( 'Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'section'     => 'misc',
                'hidden'      => true
            ];
            $fields["unfollow"] = [
                'name'        => __( 'Un-Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'hidden'      => true
            ];
            $fields['tasks'] = [
                'name' => __( 'Tasks', 'disciple_tools' ),
                'type' => 'post_user_meta',
            ];
            $fields["duplicate_data"] = [
                "name" => 'Duplicates', //system string does not need translation
                'type' => 'array',
                'default' => [],
            ];
            $fields["status"] = [
                'name' => "Status",
                'type' => 'key_select',
                "tile" => "",
                'default' => [
                    'new'   => [
                        "label" => _x( 'New', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "New training added to the system", "Training Status field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'proposed'   => [
                        "label" => _x( 'Proposed', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training has been proposed and is in initial conversations", "Training Status field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'scheduled' => [
                        "label" => _x( 'Scheduled', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is confirmed, on the calendar.", "Training Status field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'in_progress' => [
                        "label" => _x( 'In Progress', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is confirmed, on the calendar, or currently active.", "Training Status field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'complete'     => [
                        "label" => _x( "Complete", 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training has successfully completed", "Training Status field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'paused'       => [
                        "label" => _x( 'Paused', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This contact is currently on hold. It has potential of getting scheduled in the future.", "Training Status field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'closed'       => [
                        "label" => _x( 'Closed', 'Training Status label', 'disciple_tools' ),
                        "description" => _x( "This training is no longer going to happen.", "Training Status field description", 'disciple_tools' ),
                        "color" => "#366184",
                    ],
                ],
                "default_color" => "#366184",
            ];
            $fields['assigned_to'] = [
                'name'        => __( 'Assigned To', 'disciple_tools' ),
                'description' => __( "Select the main person who is responsible for reporting on this training.", 'disciple_tools' ),
                'type'        => 'user_select',
                'default'     => '',
                'tile' => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg',
                'custom_display' => true,
            ];
            $fields["coaches"] = [
                "name" => __( 'Training Coach / Church Planter', 'disciple_tools' ),
                'description' => _x( 'The person who planted and/or is coaching this training. Only one person can be assigned to a training while multiple people can be coaches / church planters of this training.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_coaches",
                'tile' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/coach.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-contact.svg',
            ];
            $fields["requires_update"] = [
                'name'        => __( 'Requires Update', 'disciple_tools' ),
                'description' => '',
                'type'        => 'boolean',
                'default'     => false,
            ];
            $fields['video_link'] = [
                'name'        => __( 'Video Link', 'disciple_tools' ),
                'description' => _x( 'Link to video chat service', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'text',
                'default'     => time(),
                'tile' => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/edit.svg',
            ];

            // @todo Add schedule section
            $fields['start_date'] = [
                'name'        => __( 'Start Date', 'disciple_tools' ),
                'description' => _x( 'The date this training began meeting.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'meeting_times',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
            ];
            $fields['end_date'] = [
                'name'        => __( 'End Date', 'disciple_tools' ),
                'description' => _x( 'The date this training stopped meeting (if applicable).', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'date',
                'default'     => '',
                'tile' => 'meeting_times',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-end.svg',
            ];
            $fields['recurring_time'] = [
                'name'        => __( 'Recurring Time', 'disciple_tools' ),
                'description' => _x( 'The date this training stopped meeting (if applicable).', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'datetime',
                'default'     => '',
                'tile' => 'meeting_times',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-end.svg',
            ];

            $fields['time_notes'] = [
                'name'        => __( 'Notes', 'disciple_tools' ),
                'description' => _x( 'Notes on when the trainings will happen', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'text',
                'default'     => time(),
                'tile' => 'meeting_times',
                'icon' => get_template_directory_uri() . '/dt-assets/images/edit.svg',
            ];
            // @todo end


            // location
            $fields['location_grid'] = [
                'name'        => __( 'Locations', 'disciple_tools' ),
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location',
                'mapbox'    => false,
                "in_create_form" => true,
                "tile" => "details",
                "icon" => get_template_directory_uri() . "/dt-assets/images/location.svg",
            ];
            $fields['location_grid_meta'] = [
                'name'        => __( 'Locations', 'disciple_tools' ), //system string does not need translation
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location_meta',
                "tile"      => "details",
                'mapbox'    => false,
                'hidden' => true
            ];
            $fields["contact_address"] = [
                "name" => __( 'Address', 'disciple_tools' ),
                "icon" => get_template_directory_uri() . "/dt-assets/images/house.svg",
                "type" => "communication_channel",
                "tile" => "details",
                'mapbox'    => false,
                "customizable" => false
            ];
            if ( DT_Mapbox_API::get_key() ){
                $fields["contact_address"]["hidden"] = true;
                $fields["contact_address"]["mapbox"] = true;
                $fields["location_grid"]["mapbox"] = true;
                $fields["location_grid_meta"]["mapbox"] = true;
            }


            // connection fields
            $fields["parent_trainings"] = [
                "name" => __( 'Parent Training', 'disciple_tools' ),
                'description' => _x( 'A training that launched this training.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "trainings",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_trainings",
                'tile' => 'other',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-parent.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields["peer_trainings"] = [
                "name" => __( 'Peer Training', 'disciple_tools' ),
                'description' => _x( "A related training that isn't a parent/child in relationship. It might indicate trainings that collaborate, are about to merge, recently split, etc.", 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "trainings",
                "p2p_direction" => "any",
                "p2p_key" => "trainings_to_peers",
                'tile' => 'other',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-peer.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields["child_trainings"] = [
                "name" => __( 'Child Training', 'disciple_tools' ),
                'description' => _x( 'A training that has been birthed out of this training.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "trainings",
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_trainings",
                'tile' => 'other',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-child.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields["members"] = [
                "name" => __( 'Member List', 'disciple_tools' ),
                'description' => _x( 'The contacts who are members of this training.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_contacts"
            ];
            $fields["leaders"] = [
                "name" => __( 'Leaders', 'disciple_tools' ),
                'description' => '',
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_leaders",
            ];

            $fields["peoplegroups"] = [
                "name" => __( 'People Groups', 'disciple_tools' ),
                'description' => _x( 'The people trainings represented by this training.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                'tile' => 'details',
                "post_type" => "peoplegroups",
                "p2p_direction" => "from",
                "p2p_key" => "trainings_to_peoplegroups"
            ];
            $fields['groups'] = [
                'name' => __( "Groups", 'disciple_tools' ),
                'type' => 'connection',
                "post_type" => 'groups',
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_groups",
                "tile" => "other",
                'icon' => get_template_directory_uri() . "/dt-assets/images/group-child.svg",
                'create-icon' => get_template_directory_uri() . "/dt-assets/images/add-group.svg",
            ];

            // count fields
            $fields["member_count"] = [
                'name' => __( 'Member Count', 'disciple_tools' ),
                'description' => _x( 'The number of members in this training. It will automatically be updated when new members are added or removed in the member list. Change this number manually to included people who may not be in the system but are also members of the training.', 'Optional Documentation', 'disciple_tools' ),
                'type' => 'number',
                'default' => '',
                'tile' => 'relationships',
            ];
            $fields["leader_count"] = [
                'name' => __( 'Leader Count', 'disciple_tools' ),
                'description' => _x( 'The number of members in this training. It will automatically be updated when new members are added or removed in the member list. Change this number manually to included people who may not be in the system but are also members of the training.', 'Optional Documentation', 'disciple_tools' ),
                'type' => 'number',
                'default' => '',
                'tile' => 'relationships',
            ];


        }

        if ( $post_type === 'contacts' ){
            $fields['training_leader'] = [
                'name' => __( "Training as Leader", 'disciple_tools' ),
                'description' => _x( 'Leader of a training', 'Optional Documentation', 'disciple_tools' ),
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_leaders",
                "tile" => "other",
                'icon' => get_template_directory_uri() . "/dt-assets/images/socialmedia.svg",
                'create-icon' => get_template_directory_uri() . "/dt-assets/images/add-group.svg",
            ];
            $fields['training_participant'] = [
                'name' => __( "Training as Participant", 'disciple_tools' ),
                'description' => _x( 'Participant in a training.', 'Optional Documentation', 'disciple_tools' ),
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_contacts",
                "tile" => "other",
                'icon' => get_template_directory_uri() . "/dt-assets/images/socialmedia.svg",
                'create-icon' => get_template_directory_uri() . "/dt-assets/images/add-group.svg",
            ];
        }
        if ( $post_type === 'groups' ){
            $fields[$this->post_type] = [
                'name' => __( "Trainings", 'disciple_tools' ),
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => "trainings_to_groups",
                "tile" => "other",
                'icon' => get_template_directory_uri() . "/dt-assets/images/socialmedia.svg",
                'create-icon' => get_template_directory_uri() . "/dt-assets/images/add-group.svg",
            ];
        }
        return $fields;
    }

    public function p2p_init(){
        /**
         * Training members field
         */
        p2p_register_connection_type(
            [
                'name'           => 'trainings_to_contacts',
                'from'           => 'trainings',
                'to'             => 'contacts',
                'admin_box' => [
                    'show' => false,
                ],
                'title'          => [
                    'from' => __( 'Members', 'disciple_tools' ),
                    'to'   => __( 'Contacts', 'disciple_tools' ),
                ]
            ]
        );
        /**
         * Training to groups
         */
        p2p_register_connection_type(
            [
                'name'           => 'trainings_to_groups',
                'from'           => 'trainings',
                'to'             => 'groups',
                'admin_box' => [
                    'show' => false,
                ],
                'title'          => [
                    'from' => __( 'Trainings', 'disciple_tools' ),
                    'to'   => __( 'Groups', 'disciple_tools' ),
                ]
            ]
        );
        /**
         * Training leaders field
         */
        p2p_register_connection_type(
            [
                'name'           => 'trainings_to_leaders',
                'from'           => 'trainings',
                'to'             => 'contacts',
                'admin_box' => [
                    'show' => false,
                ],
                'title'          => [
                    'from' => __( 'Trainings', 'disciple_tools' ),
                    'to'   => __( 'Leaders', 'disciple_tools' ),
                ]
            ]
        );
        /**
         * Training coaches field
         */
        p2p_register_connection_type(
            [
                'name'           => 'trainings_to_coaches',
                'from'           => 'trainings',
                'to'             => 'contacts',
                'admin_box' => [
                    'show' => false,
                ],
                'title'          => [
                    'from' => __( 'Trainings', 'disciple_tools' ),
                    'to'   => __( 'Coaches', 'disciple_tools' ),
                ]
            ]
        );
        /**
         * Parent and child trainings
         */
        p2p_register_connection_type(
            [
                'name'         => 'trainings_to_trainings',
                'from'         => 'trainings',
                'to'           => 'trainings',
                'title'        => [
                    'from' => __( 'Planted by', 'disciple_tools' ),
                    'to'   => __( 'Planting', 'disciple_tools' ),
                ],
            ]
        );
        /**
         * Peer trainings
         */
        p2p_register_connection_type( [
            'name'         => 'trainings_to_peers',
            'from'         => 'trainings',
            'to'           => 'trainings',
        ] );
        /**
         * Training People Groups field
         */
        p2p_register_connection_type(
            [
                'name'        => 'trainings_to_peoplegroups',
                'from'        => 'trainings',
                'to'          => 'peoplegroups',
                'title'       => [
                    'from' => __( 'People Groups', 'disciple_tools' ),
                    'to'   => __( 'Trainings', 'disciple_tools' ),
                ]
            ]
        );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === "trainings" ){
            $tiles["relationships"] = [ "label" => __( "Member List", 'disciple_tools' ) ];
            $tiles["meeting_times"] = [ "label" => __( "Meeting Times", 'disciple_tools' ) ];
            $tiles["other"] = [ "label" => __( "Other", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === "trainings" && $section === "status" ){
            $training = DT_Posts::get_post( $post_type, get_the_ID() );
            $training_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <?php render_field_for_display( "status", $training_fields, $training, true ); ?>
            </div>
            <div class="cell small-12 medium-4">
                <div class="section-subheader">
                    <img src="<?php echo esc_url( get_template_directory_uri() ) . '/dt-assets/images/assigned-to.svg' ?>">
                    <?php echo esc_html( $training_fields["assigned_to"]["name"] )?>
                    <button class="help-button" data-section="assigned-to-help-text">
                        <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                    </button>
                </div>

                <div class="assigned_to details">
                    <var id="assigned_to-result-container" class="result-container assigned_to-result-container"></var>
                    <div id="assigned_to_t" name="form-assigned_to" class="scrollable-typeahead">
                        <div class="typeahead__container">
                            <div class="typeahead__field">
                                    <span class="typeahead__query">
                                        <input class="js-typeahead-assigned_to input-height"
                                               name="assigned_to[query]" placeholder="<?php echo esc_html_x( "Search Users", 'input field placeholder', 'disciple_tools' ) ?>"
                                               autocomplete="off">
                                    </span>
                                <span class="typeahead__button">
                                        <button type="button" class="search_assigned_to typeahead__image_button input-height" data-id="assigned_to_t">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                        </button>
                                    </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cell small-12 medium-4">
                <?php render_field_for_display( "coaches", $training_fields, $training, true ); ?>
            </div>
        <?php }


        if ( $post_type === "trainings" && $section === "other" ) :
            $fields = DT_Posts::get_post_field_settings( $post_type );
            ?>
            <div class="section-subheader">
                <?php echo esc_html( $fields["tags"]["name"] ) ?>
            </div>
            <div class="tags">
                <var id="tags-result-container" class="result-container"></var>
                <div id="tags_t" name="form-tags" class="scrollable-typeahead typeahead-margin-when-active">
                    <div class="typeahead__container">
                        <div class="typeahead__field">
                            <span class="typeahead__query">
                                <input class="js-typeahead-tags input-height"
                                       name="tags[query]"
                                       placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), $fields["tags"]['name'] ) )?>"
                                       autocomplete="off">
                            </span>
                            <span class="typeahead__button">
                                <button type="button" data-open="create-tag-modal" class="create-new-tag typeahead__image_button input-height">
                                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/tag-add.svg' ) ?>"/>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;


        if ( $post_type === "trainings" && $section === "relationships" ) {
            $fields = DT_Posts::get_post_field_settings( $post_type );
            $post = DT_Posts::get_post( "trainings", get_the_ID() );
            ?>
            <div class="section-subheader members-header" style="padding-top: 10px;">
                <div style="padding-bottom: 5px; margin-right:10px; display: inline-block">
                    <?php esc_html_e( "Member List", 'disciple_tools' ) ?>
                </div>
                <button type="button" class="create-new-record" data-connection-key="members" style="height: 36px;">
                    <?php echo esc_html__( 'Create', 'disciple_tools' )?>
                    <img style="height: 14px; width: 14px" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/small-add.svg' ) ?>"/>
                </button>
                <button type="button"
                        class="add-new-member">
                    <?php echo esc_html__( 'Select', 'disciple_tools' )?>
                    <img style="height: 16px; width: 16px" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/add-group.svg' ) ?>"/>
                </button>
            </div>
            <div class="members-section" style="margin-bottom:10px">
                <div id="empty-members-list-message"><?php esc_html_e( "To add new members, click on 'Create' or 'Select'.", 'disciple_tools' ) ?></div>
                <div class="member-list">

                </div>
            </div>
            <div class="reveal" id="add-new-group-member-modal" data-reveal style="min-height:500px">
                <h3><?php echo esc_html_x( "Add members from existing contacts", 'Add members modal', 'disciple_tools' )?></h3>
                <p><?php echo esc_html_x( "In the 'Member List' field, type the name of an existing contact to add them to this training.", 'Add members modal', 'disciple_tools' )?></p>

                <?php render_field_for_display( "members", $fields, $post, false ); ?>

                <div class="grid-x pin-to-bottom">
                    <div class="cell">
                        <hr>
                        <span style="float:right; bottom: 0;">
                    <button class="button" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Close', 'disciple_tools' )?>
                    </button>
                </span>
                    </div>
                </div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php }
    }

    //action when a post connection is added during create or update
    public function post_connection_added( $post_type, $post_id, $field_key, $value ){
        if ( $post_type === "trainings" ){
            if ( $field_key === "members" ){
                // share the training with the owner of the contact when a member is added to a training
                $assigned_to = get_post_meta( $value, "assigned_to", true );
                if ( $assigned_to && strpos( $assigned_to, "-" ) !== false ){
                    $user_id = explode( "-", $assigned_to )[1];
                    if ( $user_id ){
                        DT_Posts::add_shared( $post_type, $post_id, $user_id, null, false, false );
                    }
                }
                self::update_training_member_count( $post_id );
            }
            if ( $field_key === "leaders" ){
                self::update_training_leader_count( $post_id );
            }
            if ( $field_key === "coaches" ){
                // share the training with the coach when a coach is added.
                $user_id = get_post_meta( $value, "corresponds_to_user", true );
                if ( $user_id ){
                    DT_Posts::add_shared( "trainings", $post_id, $user_id, null, false, false, false );
                }
            }
        }
        if ( $post_type === "contacts" && $field_key === "trainings" ){
            self::update_training_member_count( $value );
            // share the training with the owner of the contact.
            $assigned_to = get_post_meta( $post_id, "assigned_to", true );
            if ( $assigned_to && strpos( $assigned_to, "-" ) !== false ){
                $user_id = explode( "-", $assigned_to )[1];
                if ( $user_id ){
                    DT_Posts::add_shared( "trainings", $value, $user_id, null, false, false );
                }
            }
        }
    }

    //action when a post connection is removed during create or update
    public function post_connection_removed( $post_type, $post_id, $field_key, $value ){
        if ( $post_type === "trainings" ){
            if ( $field_key === "members" ){
                self::update_training_member_count( $post_id, "removed" );
            }
            if ( $field_key === "leaders" ){
                self::update_training_leader_count( $post_id, "removed" );
            }
        }
        if ( $post_type === "contacts" && $field_key === "trainings" ){
            self::update_training_member_count( $value, "removed" );
        }
    }

    //filter at the start of post update
    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        if ( $post_type === "trainings" ){
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
                    DT_Posts::add_shared( "trainings", $post_id, $user_id, null, false, true, false );
                }
            }
            $existing_training = DT_Posts::get_post( 'trainings', $post_id, true, false );
            if ( isset( $fields["status"] ) && empty( $fields["end_date"] ) && empty( $existing_training["end_date"] ) && $fields["status"] === 'closed' ){
                $fields["end_date"] = time();
            }
        }
        return $fields;
    }

    public function render_datetime_field( $post, $field_type, $field_key, $required_tag ){
        if ( $field_type === "datetime" ) :
            ?>
            <div class="<?php echo esc_html( $field_key ) ?> input-group">
                <input id="<?php echo esc_html( $field_key ) ?>" class="input-group-field dt_datetime_picker" type="text" autocomplete="off" <?php echo esc_html( $required_tag ) ?>
                       value="<?php echo esc_html( $post[$field_key]["timestamp"] ?? '' ) ?>" >
                <div class="input-group-button">
                    <button id="<?php echo esc_html( $field_key ) ?>-clear-button" class="button alert clear-date-button" data-inputid="<?php echo esc_html( $field_key ) ?>" title="Delete Date" type="button">x</button>
                </div>
            </div>
            <?php
        endif;
    }

    //update the training member count when members are added or removed.
    private static function update_training_member_count( $training_id, $action = "added" ){
        $training = get_post( $training_id );
        $args = [
            'connected_type'   => "trainings_to_contacts",
            'connected_direction' => 'from',
            'connected_items'  => $training,
            'nopaging'         => true,
            'suppress_filters' => false,
        ];
        $members = get_posts( $args );
        $member_count = get_post_meta( $training_id, 'member_count', true );
        if ( sizeof( $members ) > intval( $member_count ) ){
            update_post_meta( $training_id, 'member_count', sizeof( $members ) );
        } elseif ( $action === "removed" ){
            update_post_meta( $training_id, 'member_count', intval( $member_count ) - 1 );
        }
    }

    private static function update_training_leader_count( $training_id, $action = "added" ){
        $training = get_post( $training_id );
        $args = [
            'connected_type'   => "trainings_to_leaders",
            'connected_direction' => 'from',
            'connected_items'  => $training,
            'nopaging'         => true,
            'suppress_filters' => false,
        ];
        $leaders = get_posts( $args );
        $leader_count = get_post_meta( $training_id, 'leader_count', true );
        if ( sizeof( $leaders ) > intval( $leader_count ) ){
            update_post_meta( $training_id, 'leader_count', sizeof( $leaders ) );
        } elseif ( $action === "removed" ){
            update_post_meta( $training_id, 'leader_count', intval( $leader_count - 1 ) );
        }
    }


    //check to see if the training is marked as needing an update
    //if yes: mark as updated
    private static function check_requires_update( $training_id ){
        if ( get_current_user_id() ){
            $requires_update = get_post_meta( $training_id, "requires_update", true );
            if ( $requires_update == "yes" || $requires_update == true || $requires_update == "1"){
                //don't remove update needed if the user is a dispatcher (and not assigned to the trainings.)
                if ( DT_Posts::can_view_all( 'trainings' ) ){
                    if ( dt_get_user_id_from_assigned_to( get_post_meta( $training_id, "assigned_to", true ) ) === get_current_user_id() ){
                        update_post_meta( $training_id, "requires_update", false );
                    }
                } else {
                    update_post_meta( $training_id, "requires_update", false );
                }
            }
        }
    }

    //filter when a comment is created
    public function dt_comment_created( $post_type, $post_id, $comment_id, $type ){
        if ( $post_type === "trainings" ){
            if ( $type === "comment" ){
                self::check_requires_update( $post_id );
            }
        }
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === "trainings" ) {
            if ( !isset( $fields["status"] ) ) {
                $fields["status"] = "new";
            }
            if ( !isset( $fields["assigned_to"] ) ) {
                $fields["assigned_to"] = sprintf( "user-%d", get_current_user_id() );
            }
            if ( !isset( $fields["start_date"] ) ) {
                $fields["start_date"] = time();
            }
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
            }
        }
        return $fields;
    }

    //action when a post has been created
    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        if ( $post_type === "trainings" ){
            do_action( "dt_training_created", $post_id, $initial_fields );
            $training = DT_Posts::get_post( 'trainings', $post_id, true, false );
            if ( isset( $training["assigned_to"] )) {
                if ( $training["assigned_to"]["id"] ) {
                    DT_Posts::add_shared( "trainings", $post_id, $training["assigned_to"]["id"], null, false, false, false );
                }
            }
        }
    }


    //list page filters function
    private static function get_my_trainings_status_type(){
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT status.meta_value as status, count(pm.post_id) as count, count(un.post_id) as update_needed
            FROM $wpdb->postmeta pm
            INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'status' )
            INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'trainings' and a.post_status = 'publish' )
            INNER JOIN $wpdb->postmeta as assigned_to ON a.ID=assigned_to.post_id
              AND assigned_to.meta_key = 'assigned_to'
              AND assigned_to.meta_value = CONCAT( 'user-', %s )
            LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
            WHERE pm.meta_key = 'status'
            GROUP BY status.meta_value, pm.meta_value
        ", get_current_user_id() ), ARRAY_A);

        return $results;
    }

    //list page filters function
    private static function get_all_trainings_status_type(){
        global $wpdb;
        if ( current_user_can( 'view_any_trainings' ) ){
            $results = $wpdb->get_results("
                SELECT status.meta_value as status, count(pm.post_id) as count, count(un.post_id) as update_needed
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'status' )
                INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'trainings' and a.post_status = 'publish' )
                LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
                WHERE pm.meta_key = 'status'
                GROUP BY status.meta_value, pm.meta_value
            ", ARRAY_A);
        } else {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT status.meta_value as status, count(pm.post_id) as count, count(un.post_id) as update_needed
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'status' )
                INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'trainings' and a.post_status = 'publish' )
                LEFT JOIN $wpdb->dt_share AS shares ON ( shares.post_id = a.ID AND shares.user_id = %s )
                LEFT JOIN $wpdb->postmeta assigned_to ON ( assigned_to.post_id = pm.post_id AND assigned_to.meta_key = 'assigned_to' && assigned_to.meta_value = %s )
                LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
                WHERE pm.meta_key = 'status' AND
                      ( shares.user_id IS NOT NULL OR assigned_to.meta_value IS NOT NULL )
                GROUP BY status.meta_value, pm.meta_value
            ", get_current_user_id(), 'user-' . get_current_user_id() ), ARRAY_A);
        }

        return $results;
    }

    //build list page filters
    public static function dt_user_list_filters( $filters, $post_type ){
        if ( $post_type === 'trainings' ){
            $counts = self::get_my_trainings_status_type();
            $fields = DT_Posts::get_post_field_settings( $post_type );
            /**
             * Setup my training filters
             */
            $active_counts = [];
            $update_needed = 0;
            $status_counts = [];
            $total_my = 0;
            foreach ( $counts as $count ){
                $total_my += $count["count"];
                dt_increment( $status_counts[$count["status"]], $count["count"] );
                if ( $count["status"] === "new" ){
                    if ( isset( $count["update_needed"] ) ) {
                        $update_needed += (int) $count["update_needed"];
                    }
                    dt_increment( $active_counts[$count["status"]], $count["count"] );
                }
            }


            $filters["tabs"][] = [
                "key" => "assigned_to_me",
                "label" => _x( "Assigned to me", 'List Filters', 'disciple_tools' ),
                "count" => $total_my,
                "order" => 20
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'my_all',
                'tab' => 'assigned_to_me',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [
                    'assigned_to' => [ 'me' ],
                    'sort' => '-post_date'
                ],
                "count" => $total_my,
            ];

            foreach ( $fields["status"]["default"] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ){
                    $filters["filters"][] = [
                        "ID" => 'my_' . $status_key,
                        "tab" => 'assigned_to_me',
                        "name" => $status_value["label"],
                        "query" => [
                            'assigned_to' => [ 'me' ],
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        "count" => $status_counts[$status_key]
                    ];
                    if ( $status_key === "new" ){
                        if ( $update_needed > 0 ){
                            $filters["filters"][] = [
                                "ID" => 'my_update_needed',
                                "tab" => 'assigned_to_me',
                                "name" => $fields["requires_update"]["name"],
                                "query" => [
                                    'assigned_to' => [ 'me' ],
                                    'status' => [ 'new' ],
                                    'requires_update' => [ true ],
                                ],
                                "count" => $update_needed,
                                'subfilter' => true
                            ];
                        }
                    }
                }
            }

            $counts = self::get_all_trainings_status_type();
            $active_counts = [];
            $update_needed = 0;
            $status_counts = [];
            $total_all = 0;
            foreach ( $counts as $count ){
                $total_all += $count["count"];
                dt_increment( $status_counts[$count["status"]], $count["count"] );
                if ( $count["status"] === "new" ){
                    if ( isset( $count["update_needed"] ) ) {
                        $update_needed += (int) $count["update_needed"];
                    }
                    dt_increment( $active_counts[$count["status"]], $count["count"] );
                }
            }
            $filters["tabs"][] = [
                "key" => "all",
                "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                "count" => $total_all,
                "order" => 10
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'all',
                'tab' => 'all',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [
                    'sort' => '-post_date'
                ],
                "count" => $total_all
            ];

            foreach ( $fields["status"]["default"] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ){
                    $filters["filters"][] = [
                        "ID" => 'all_' . $status_key,
                        "tab" => 'all',
                        "name" => $status_value["label"],
                        "query" => [
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        "count" => $status_counts[$status_key]
                    ];
                    if ( $status_key === "new" ){
                        if ( $update_needed > 0 ){
                            $filters["filters"][] = [
                                "ID" => 'all_update_needed',
                                "tab" => 'all',
                                "name" => $fields["requires_update"]["name"],
                                "query" => [
                                    'status' => [ 'new' ],
                                    'requires_update' => [ true ],
                                ],
                                "count" => $update_needed,
                                'subfilter' => true
                            ];
                        }
                    }
                }
            }
        }
        return $filters;
    }

    public function scripts(){
        if ( is_singular( "trainings" ) ){

            wp_enqueue_script( 'dt_trainings', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'trainings.js', [
                'jquery',
                'details',
            ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'trainings.js' ), true );
        }
    }

    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === "trainings" ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = [];
            }
        }
        return $permissions;
    }


}
