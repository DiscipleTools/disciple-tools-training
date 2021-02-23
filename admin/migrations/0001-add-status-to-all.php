<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Training_Migration_0001
 */
class DT_Training_Migration_0001 extends DT_Training_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;
        $list = $wpdb->get_col("SELECT ID 
            FROM $wpdb->posts p 
            WHERE post_type = 'trainings' AND ID NOT IN (SELECT p.ID
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'status'
            WHERE post_type = 'trainings' );"
        );

        if ( ! empty( $list ) ) {
            foreach ( $list as $id ){
                add_post_meta( $id, 'status', 'new', true );
            }
        }
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
