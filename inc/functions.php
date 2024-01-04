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

function overrideDestination($args) {
    $zipFileUrl = 'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/zipball/'.$args['rep_branch'];
    $downloadHeaders = [
        'Authorization' => $args['rep_api_key'] ? 'Bearer '.$args['rep_api_key'] : null,
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    $zipFileContents = wp_remote_get($zipFileUrl, ['headers' => $downloadHeaders]);

    if ( is_wp_error($zipFileContents) || $zipFileContents['response']['code'] !== 200) return $zipFileContents;

    // download
    $zipFilePath = WP_CONTENT_DIR . '/upgrade/' . $args['rep_name'] . 'zip';
    if (file_put_contents($zipFilePath, $zipFileContents['body']) === false) {
        return new \WP_Error('zip_not_copied', 'Zip file couldn not be created', ['status' => 418]);
    }

    // unzip to temporary directory
    if (!class_exists('ZipArchive')) {
        return new \WP_Error('zip_cant_be_proceeded', 'ZipArchive library is not installed', ['status' => 418]);
    }
    $zip = new \ZipArchive();
    if ($zip->open($zipFilePath) !== true) {
        return new \WP_Error('zip_cant_be_opened', 'Zip archive file seems to be broken', ['status' => 418]);
    }
    $unZipDir = WP_CONTENT_DIR . '/upgrade/' . $args['rep_name'] . '_tmp';
    if (!file_exists($unZipDir)) {
        if (!mkdir($unZipDir, 0755, true)) {
            return new \WP_Error('couldnt_create_directory', 'Could not create the temporary unzip directory', ['status' => 418]);
        }
    }
    if ($zip->extractTo($unZipDir) !== true) {
        return new \WP_Error('extraction_failed', 'Failed to extract the zip archive', ['status' => 418]);
    }
    $zip->close();


    // manipulate the contents
    $destDir = WP_CONTENT_DIR . '/' . $args['rep_dest'] . '/' . $args['rep_name'];
    rrmdir($destDir);

    $contents = glob($unZipDir . '/*');
    if (count($contents) === 1 && is_dir($contents[0])) {
        rcopy($contents[0], $destDir); // If there's only one directory, move it to $destDir
    } else {
        rename($unZipDir, $destDir); // If there's more than one directory, move the entire $unZipDir to $destDir
    }

    // Delete unnecessary folders and files
    unlink($zipFilePath);
    rrmdir($unZipDir);

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
    $gitResponseCode = wp_remote_retrieve_response_code( $gitResponse );

    if ( $gitResponseCode !== 200 ) { return ['body' => $gitResponseBody, 'code' => $gitResponseCode]; }

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

// schedule functions

function schedule_auto_update($postID, $type) {
    if ( in_array( $type, ['1', '2'] ) ) {
        wp_schedule_event( get_schedule_start(), 'twicedaily', FCGBF_SLUG.'_auto_updates', [$postID] );
        //error_log('schedule_auto_update '.$postID.' '.$type);
        return;
    }
    wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_updates', [$postID] );
}

function get_schedule_start() {
    if ( !FCGBF_DEV ) { return time(); }
    return time() + 60*3;
}

function display_next_event_time($event_hook, $event_args = []) {
    if ( !( $next_event_timestamp = wp_next_scheduled($event_hook, $event_args) ) ) { return false; }
    $time_remaining = $next_event_timestamp - time();
    $hours = floor($time_remaining / 3600);
    $minutes = floor(($time_remaining % 3600) / 60);
    return $hours.'h '. $minutes.'m';
}

function rcopy($src, $dst) {
    if (file_exists($dst)) rrmdir($dst);
    if (is_dir($src)) {
      mkdir($dst);
      $files = scandir($src);
      foreach ($files as $file)
      if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file"); 
    }
    else if (file_exists($src)) copy($src, $dst);
  }

  function rrmdir($folderPath) {
    if (!is_dir($folderPath)) { return false; }
    $files = array_diff( scandir($folderPath), ['.', '..'] );
    foreach ($files as $file) {
        $filePath = $folderPath . '/' . $file;
        if (is_dir($filePath)) {
            rrmdir($filePath);
        } else {
            unlink($filePath);
        }
    }
    return rmdir($folderPath);
}