<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

// install / uninstall is in install.php

// trash / untrash
add_action( 'trashed_post', function($postID) {
    schedule_auto_update( $postID, '0' );
});
add_action( 'untrashed_post', function($postID) {
    if ( get_post_type( $postID ) !==  FCGBF_SLUG ) { return; }
    schedule_auto_update( $postID );
} );

// shedules hooks

// update
add_action( FCGBF_SLUG.'_auto_updates', 'FC\GitBranchFollow\auto_updates_hook', 10, 1 );
function auto_updates_hook($postID) {
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

    // ++ exclude those with auto-update type === '0'?
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        FCGBF_SLUG, 'publish'
    ));

    foreach ($post_ids as $post_id) {
        $result = processGitRequest(['id' => $post_id, 'action' => 'check']);
        if ( is_wp_error( $result ) ) {
            if ( FCGBF_DEV ) { error_log($post_id); error_log($result); }
        }
    }
}