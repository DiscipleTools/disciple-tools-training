<?php
/**
 * DT_Training_Menu class for the admin page
 *
 * @class       DT_Training_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

//@todo Replace all instances if DT_Training
if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Initialize menu class
 */
DT_Training_Menu::instance();

/**
 * Class DT_Training_Menu
 */
class DT_Training_Menu {

    public $token = 'dt_training';

    private static $_instance = null;

    /**
     * DT_Training_Menu Instance
     *
     * Ensures only one instance of DT_Training_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Training_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', __( 'Training', 'dt_training' ), __( 'Training', 'dt_training' ), 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Disciple Tools Training', 'dt_training' ) ?></h2>
            <hr style="border-top:1px solid darkgray">
            <span>&#x2705; Installed</span>

        </div><!-- End wrap -->
        <?php
    }
}