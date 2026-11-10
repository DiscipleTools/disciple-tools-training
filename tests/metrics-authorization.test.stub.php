<?php
/**
 * Unit test: the trainings metrics endpoint get_list_by_grid_id must enforce the same permission
 * check as its sibling endpoints (cluster_geojson, get_grid_totals, points_geojson).
 *
 * Asserts: a caller without permission is refused (400 Missing Permissions); an authorized caller is served.
 */

class Test_Training_Mapbox_Get_List_By_Grid_Id extends WP_UnitTestCase {

    private function instance() {
        return new DT_Metrics_Mapbox_Trainings_Maps();
    }

    private function request( array $body ) {
        $req = new WP_REST_Request( 'POST', '/dt-metrics/trainings/get_list_by_grid_id' );
        $req->set_body_params( $body );
        return $req;
    }

    /** refuses a caller without permission; serves an authorized one */
    public function test__caller_is_refused() {
        wp_set_current_user( 0 ); // anonymous
        $result = $this->instance()->get_list_by_grid_id(
            $this->request( [ 'grid_id' => 1, 'post_type' => 'trainings' ] )
        );
        $this->assertInstanceOf( WP_Error::class, $result, 'anon caller must get WP_Error, not a record list' );
        $this->assertSame( 'Missing Permissions', $result->get_error_message() );
    }

    /** Guards against over-correction: an authorized user must still receive results. */
    public function test_serves_when_permission_granted() {
        $uid = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $uid );
        $result = $this->instance()->get_list_by_grid_id(
            $this->request( [ 'grid_id' => 1, 'post_type' => 'trainings' ] )
        );
        $this->assertNotInstanceOf( WP_Error::class, $result, 'authorized caller must NOT be blocked by the new gate' );
    }
}
