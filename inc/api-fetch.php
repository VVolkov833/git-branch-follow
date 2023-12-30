<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {

    $branch_infos = function($args) {
        $headers = [
            'Authorization' => 'Bearer '.$args['rep_api_key'],
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        $response = wp_remote_get(
            'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/branches/'.$args['rep_branch'],
            ['headers' => $headers]
        );
        return $response;
    };

    $deleteFolderContents = function($folder) use (&$deleteFolderContents) {
        if (@is_dir($folder) && $dh = @opendir($folder)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    $path = $folder . '/' . $file;
                    is_dir($path) ? $deleteFolderContents($path) && rmdir($path) : unlink($path);
                }
            }
            closedir($dh);
        }
    };

    $override_dest = function($args) use ($deleteFolderContents){
        $zipFileUrl = 'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/zipball/'.$args['rep_branch'];
        $downloadHeaders = array(
            'Authorization' => 'Bearer '.$args['rep_api_key'],
            'X-GitHub-Api-Version' => '2022-11-28',
        );
    
        $zipFileContents = wp_remote_get($zipFileUrl, array('headers' => $downloadHeaders));
    
        if ( is_wp_error($zipFileContents) || $zipFileContents['response']['code'] !== 200) return $zipFileContents;

        // download
        $zipFilePath = WP_CONTENT_DIR.'/upgrade/'.$args['rep_name'].'zip';
        file_put_contents($zipFilePath, $zipFileContents['body']);

        // delete the existing contents
        $destDir = WP_CONTENT_DIR.'/'.$args['rep_dest'];
        // ++check if zip library exists else error
        $deleteFolderContents($destDir);

        // unzip to dest
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) === true) {

            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true); // ++return error
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileInfo = pathinfo($filename);
                $targetPath = $destDir.'/'.$fileInfo['basename'];
                copy("zip://$zipFilePath#$filename", $targetPath);
            }

            $zip->close();
        }

        // delete zip
        unlink( $zipFilePath );

        // update the meta
 
    };

    $route_args = [
        'methods'  => 'GET',
        'callback' => function(\WP_REST_Request $request) use ($branch_infos, $override_dest) {

            if ( FCGBF_DEV ) { // simulate server responce delay
                nocache_headers();
                usleep( rand(0, 1000000) );
            }

            $wp_query_args = [
                'post_type' => FCGBF_SLUG,
                'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
                'p' => $request['id'],
            ];

            $query = new \WP_Query( $wp_query_args );

            if ( !$query->have_posts() ) { return new \WP_Error( 'nothing_found', 'No results found', ['status' => 404] ); }

            $details = [];
            while ( $query->have_posts() ) {
                $p = $query->next_post();
                $details = [
                    'rep_url' => get_post_meta( $p->ID, FCGBF_PREF.'rep-url' )[0] ?? '',
                    'rep_api_key' => get_post_meta( $p->ID, FCGBF_PREF.'rep-api-key' )[0] ?? '',
                    'rep_branch' => get_post_meta( $p->ID, FCGBF_PREF.'rep-branch' )[0] ?? FCGBF_BRANCH,
                    'rep_dest' => get_post_meta( $p->ID, FCGBF_PREF.'rep-dest' )[0] ?? FCGBF_BRANCH,
                ];
                break;
            }

            if ( preg_match( '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)$/', $details['rep_url'], $matches) ) {
                list( , $details['rep_author'], $details['rep_name'] ) = $matches;
            } else {
                return new \WP_Error( 'wrong_format', 'Wrong Repository Link format', ['status' => 422] );
            }

            //return new \WP_REST_Response( $details, 200 );
            //return new \WP_REST_Response( $branch_infos($details), 200 );

            $response = $branch_infos($details);
            
            if ( is_wp_error( $response ) ) { return $response; }

            if ( $request['action'] === 'install' ) {
                $override_dest($details);       
            }

            return new \WP_REST_Response( json_decode(stripslashes(wp_remote_retrieve_body( $response ))), wp_remote_retrieve_response_code( $response ) );

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