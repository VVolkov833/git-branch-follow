<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_action( 'rest_api_init', function () {

    $route_args = [
        'methods'  => 'GET',
        'callback' => function(\WP_REST_Request $request) {

            if ( is_wp_error( $response = processGitRequest($request) ) ) { return $response; }
return $response;
            ['body' => $gitResponseBody, 'code' => $gitResponseCode] = $response;
            return new \WP_REST_Response( $gitResponseBody, $gitResponseCode );

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
            'action' => [
                'description' => 'Check or install',
                'type'        => 'string',
                'required'    => true,
                'validate_callback' => function($param) {
                    return in_array(trim($param), ['check', 'install']) ? true : false;
                },
                'sanitize_callback' => function($param, \WP_REST_Request $request, $key) {
                    return trim($param);
                },
            ],
        ],
    ];

    register_rest_route( FCGBF_ENDPOINT, '/(?P<id>\d{1,16})/(?P<action>(check|install))', $route_args );
});