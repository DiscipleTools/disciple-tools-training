<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'dt_post_type_modules', function( $modules ){
    $modules['trainings_base'] = [
        'name' => 'Trainings',
        'enabled' => true,
        'locked' => true,
        'prerequisites' => [ 'contacts_base' ],
        'post_type' => 'trainings',
        'description' => 'Default Trainings Module'
    ];
    //@todo Add Registration Module
    //    $modules["trainings_app_registration_module"] = [
    //        "name" => "Trainings - Registration Module",
    //        "enabled" => true,
    //        "locked" => false,
    //        "prerequisites" => [ "trainings_base",  "contacts_base" ],
    //        "post_type" => "trainings",
    //        "description" => "Add Micro App Tile to Trainings"
    //    ];
    // @todo Add Public Calendar Module
    //    $modules["trainings_app_calendar_module"] = [
    //        "name" => "Trainings - Calendar Module",
    //        "enabled" => true,
    //        "locked" => false,
    //        "prerequisites" => [ "trainings_base",  "contacts_base", "trainings_app_registration_module" ],
    //        "post_type" => "trainings",
    //        "description" => "Add Micro App Tile to Trainings"
    //    ];
    return $modules;
}, 20, 1 );

require_once 'module-base.php';
DT_Training_Base::instance();

//@todo Add Registration Module
//require_once 'module-registration.php';
//DT_Training_App_Registration_Module::instance();

// @todo Add Public Calendar Module
//require_once 'module-public-calendar.php';
// DT_Training_App_Calendar_Module::instance();
