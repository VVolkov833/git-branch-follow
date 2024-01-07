<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

// install / uninstall is in install.php

// trash / untrash
add_action( 'trashed_post', function($postID) {
    //wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_updates', [$postID] );
});
add_action( 'untrashed_post', function($postID) {
    if ( ($post_type = get_post_type( $postID )) !==  FCGBF_SLUG ) { return; }
    schedule_auto_update($postID);
} );

// shedules hooks

// update
add_action( FCGBF_SLUG.'_auto_updates', 'FC\GitBranchFollow\auto_updates_hook', 10, 1 );
function auto_updates_hook($postID) {
    //error_log('auto_updates_hook '.$postID.' install');
    $result = processGitRequest(['id' => $postID, 'action' => 'install']);
    if ( is_wp_error( $result ) ) {
        schedule_auto_update( $postID, null, null, get_schedule_start() + 60*60 );
        if ( FCGBF_DEV ) { error_log($postID); error_log($result); }
    }
}

// check
add_action( FCGBF_SLUG.'_auto_checks', 'FC\GitBranchFollow\auto_checks_hook', 10, 0 );
function auto_checks_hook() {    
    global $wpdb;

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        FCGBF_SLUG, FCGBF_PREF.'active'
    ));
    foreach ($post_ids as $post_id) {
        processGitRequest(['id' => $post_id, 'action' => 'check']);
    }
}