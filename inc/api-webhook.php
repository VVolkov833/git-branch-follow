<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_action( 'rest_api_init', function () {

    $route_args = [
        'methods'  => 'POST',
        'callback' => function(\WP_REST_Request $request) {

            $data = $request->get_json_params();

            if ( empty($data['repository']) || empty($data['repository']['html_url']) ) {
                return new \WP_REST_Response('Repository URL is required', 400);
            }
            if ( !($post_id = get_post_id_by_meta_value(FCGBF_PREF.'rep-url', $data['repository']['html_url'])) ) {
                return new \WP_REST_Response('Post not found for the given repository URL', 404);
            }
            if ( ($auto_updates_type = get_post_meta( $post_id, FCGBF_PREF.'rep-auto-updates' )[0] ?? '0') !== '3' ) {
                return new \WP_REST_Response('Auto-updates are not enabled for post ID ' . $post_id, 400);
            }
            if ( is_wp_error( $result = processGitRequest(['id' => $post_id, 'action' => 'install']) ) ) {
                if ( FCGBF_DEV ) { error_log($post_id); error_log($result); }
                if ( $result = schedule_auto_update($post_id, '3', true, time()) !== 'updateEventAdded' ) { // try to schedule on fail
                    return new \WP_REST_Response('Scheduling error: ' . $result, 418);
                }
                return new \WP_REST_Response('Auto-update SCHEDULED for post ID ' . $post_id, 200);
            }
            return new \WP_REST_Response('Auto-update EXECUTED for post ID ' . $post_id, 200);

        },
        'permission_callback' => function() {
            //if ( empty( $_SERVER['HTTP_REFERER'] ) ) { return false; }
            return true;
        },
        'args' => [],
    ];

    register_rest_route( FCGBF_ENDPOINT, '/update/', $route_args );
});

function get_post_id_by_meta_value($meta_key, $meta_value) {
    global $wpdb;

    $query = $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta
        JOIN $wpdb->posts ON $wpdb->postmeta.post_id = $wpdb->posts.ID
        WHERE $wpdb->postmeta.meta_key = %s
        AND $wpdb->postmeta.meta_value = %s
        AND $wpdb->posts.post_type = %s
        AND $wpdb->posts.post_status = 'publish'",
        $meta_key,
        $meta_value,
        FCGBF_SLUG
    );

    $post_id = $wpdb->get_var($query);

    return $post_id;
}
