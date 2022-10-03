<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Training_Migration_0002
 * Find any notes field and upgrade them to training_notes
 */
class DT_Training_Migration_0002 extends DT_Training_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;
        $wpdb->query( "
            UPDATE $wpdb->postmeta pm
            JOIN $wpdb->posts p on (p.ID = pm.post_id AND p.post_type = 'trainings')
            SET meta_key = 'training_notes'
            WHERE meta_key = 'notes'
        " );
    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * Test function
     */
    public function test() {
    }

}
