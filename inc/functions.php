<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

function gitUrlSplit($url) {
    if ( !$url || !preg_match( '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)$/', $url, $matches) ) { return ['','']; }
    return [$matches[1] ?? '', $matches[2] ?? ''];
}

function gitBranchInfos($args) {
    $headers = array_filter([
        'Authorization' => $args['rep_api_key'] ? 'Bearer '.$args['rep_api_key'] : null,
        'Accept' => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
    ]);
    $gitResponse = wp_remote_get(
        'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/branches/'.$args['rep_branch'],
        ['headers' => $headers]
    );
    return $gitResponse;
}

function deleteFolderContents($folder) {
    if (@is_dir($folder) && $dh = @opendir($folder)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != '.' && $file != '..') {
                $path = $folder . '/' . $file;
                is_dir($path) ? deleteFolderContents($path) && @rmdir($path) : @unlink($path);
            }
        }
        closedir($dh);
    }
}

function overrideDestination($args) {
    $zipFileUrl = 'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/zipball/'.$args['rep_branch'];
    $downloadHeaders = array(
        'Authorization' => $args['rep_api_key'] ? 'Bearer '.$args['rep_api_key'] : null,
        'X-GitHub-Api-Version' => '2022-11-28',
    );

    $zipFileContents = wp_remote_get($zipFileUrl, array('headers' => $downloadHeaders));

    if ( is_wp_error($zipFileContents) || $zipFileContents['response']['code'] !== 200) return $zipFileContents;

    // download
    $zipFilePath = WP_CONTENT_DIR.'/upgrade/'.$args['rep_name'].'zip';
    if ( file_put_contents($zipFilePath, $zipFileContents['body']) === false ) {
        return new \WP_Error( 'zip_not_copied', 'Zip file couldn not be created', ['status' => 418] );
    }

    // delete the existing contents
    $destDir = WP_CONTENT_DIR.'/'.$args['rep_dest'].'/'.$args['rep_name'];
    deleteFolderContents($destDir); // ++error

    // unzip to dest
    if ( !class_exists('ZipArchive') ) {
        return new \WP_Error( 'zip_cant_be_proceeded', 'ZipArchive library is not installed', ['status' => 418] );
    }
    $zip = new \ZipArchive();
    if ( $zip->open($zipFilePath) !== true ) {
        return new \WP_Error( 'zip_cant_be_opened', 'Zip archive file seem to be broken', ['status' => 418] );
    }

    if (!file_exists($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return new \WP_Error( 'couldnt_create_directory', 'Could not create the destination directory', ['status' => 418] );
        }
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $fileInfo = pathinfo($filename);
        $targetPath = $destDir.'/'.$fileInfo['basename'];
        copy("zip://$zipFilePath#$filename", $targetPath);
    }

    $zip->close();

    // delete zip
    unlink( $zipFilePath );

    return [
        "installed" => true
    ];
};

function processGitRequest($request) { //[id, action]
    //error_log('processGitRequest '.$request['id'].' '.$request['action']);
    if ( empty($request['id']) || empty($request['action'] )) {
        return new \WP_Error( 'no_arguments', 'Not enough arguments for processGitRequest', ['status' => 422] );
    }

    if ( FCGBF_DEV ) { // simulate server responce delay
        nocache_headers();
        usleep( rand(0, 1000000) );
    }

    $wp_query_args = [
        'post_type' => FCGBF_SLUG,
        'post_status' => FCGBF_PREF.'active',//['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
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

    list( $details['rep_author'], $details['rep_name'] ) = gitUrlSplit( $details['rep_url'] );
    if ( !$details['rep_author'] || !$details['rep_name'] ) {
        return new \WP_Error( 'wrong_format', 'Wrong Repository Link format', ['status' => 422] );
    }

    // git data
    $gitResponse = gitBranchInfos($details);
    if ( is_wp_error( $gitResponse ) ) { return $gitResponse; }

    $gitResponseBody = json_decode(stripslashes(wp_remote_retrieve_body( $gitResponse )));
    $gitResponseBody->extended_locally = [];

    // git zip, override
    if ( $request['action'] === 'install' ) {
        $zipProcessed = overrideDestination($details);
        if ( is_wp_error( $zipProcessed ) ) { return $zipProcessed; }
        $gitResponseBody->extended_locally = (object) array_merge((array) $gitResponseBody->extended_locally, $zipProcessed, ["date" => time()]);

        // update the meta
        update_post_meta( $request['id'], FCGBF_PREF.'rep-current', $gitResponseBody );
        delete_post_meta( $request['id'], FCGBF_PREF.'rep-new' );
    }

    // update the meta after check with changes
    if ( $request['action'] === 'check' ) {
        $gitResponseBody->extended_locally = (object) array_merge((array)  $gitResponseBody->extended_locally, ["checked" => true], ["date" => time()]);
        //delete_post_meta( $request['id'], FCGBF_PREF.'rep-new' );
        $repCurrentBody = get_post_meta( $request['id'], FCGBF_PREF.'rep-current' )[0] ?? [];
        if (empty($repCurrentBody) || $repCurrentBody->commit->sha !== $gitResponseBody->commit->sha) {
            update_post_meta( $request['id'], FCGBF_PREF.'rep-new', $gitResponseBody );
            $gitResponseBody->extended_locally = (object) array_merge((array) $gitResponseBody->extended_locally, ["has_changes" => true]);
        }
    }

    $gitResponseBody->extended_locally = (object) $gitResponseBody->extended_locally;

    return ['body' => $gitResponseBody, 'code' => wp_remote_retrieve_response_code( $gitResponse )];

}