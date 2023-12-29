<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {

    $route_args = [
        'methods'  => 'GET',
        'callback' => function(\WP_REST_Request $request) {

            if ( FCGBF_DEV ) { usleep( rand(0, 1000000) ); } // simulate server responce delay

            $wp_query_args = [
                'post_type' => FCGBF_SLUG,
                'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
                'p' => $request['id'],
            ];

            $query = new \WP_Query( $wp_query_args );

            if ( !$query->have_posts() ) {
                return new \WP_Error( 'nothing_found', 'No results found', ['status' => 404] );
            }

            $result = [];
            while ( $query->have_posts() ) {
                $p = $query->next_post();
                $result = [
                    'rep_url' => get_post_meta( $p->ID, FCGBF_PREF.'rep-url' )[0] ?? '',
                    'rep_api_key' => get_post_meta( $p->ID, FCGBF_PREF.'rep-api-key' )[0] ?? '',
                    'rep_branch' => get_post_meta( $p->ID, FCGBF_PREF.'rep-branch' )[0] ?? FCGBF_BRANCH,
                ];
            }

            if ( FCGBF_DEV ) { nocache_headers(); }

            return new \WP_REST_Response( $result, 200 );
        },
        'permission_callback' => function() {
            if ( empty( $_SERVER['HTTP_REFERER'] ) ) { return false; }
            if ( strtolower( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) ) !== strtolower( $_SERVER['HTTP_HOST'] ) ) { return false; }
            if ( !current_user_can( 'administrator' ) ) { return false; } // works only with X-WP-Nonce header passed
            return true;
        },
        'args' => [
            'id' => [
                'description' => 'The repository post id',
                'type'        => 'number',
                'required'    => true,
                'validate_callback' => function($param) {
                    return is_numeric(trim($param)) ? true : false;
                },
                'sanitize_callback' => function($param, \WP_REST_Request $request, $key) {
                    return (int) trim($param);
                },
            ],
        ],
    ];

    register_rest_route( FCGBF_SLUG.'/v1', '/(?P<id>\d{1,16})', $route_args );
});