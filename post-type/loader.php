<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'dt_post_type_modules', function( $modules ){
    $modules["trainings_base"] = [
        "name" => "Trainings",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "trainings",
        "description" => "Default Trainings Module"
    ];
    $modules["trainings_app_module"] = [
        "name" => "Trainings - Apps Module",
        "enabled" => true,
        "locked" => false,
        "prerequisites" => [ "trainings_base",  "contacts_base" ],
        "post_type" => "trainings",
        "description" => "Add Micro App Tile to Trainings"
    ];
    return $modules;
}, 20, 1 );

require_once 'module-base.php';
DT_Training_Base::instance();

require_once 'module-app.php';
DT_Training_Apps::instance();
DT_Training_Magic_Registration::instance();
