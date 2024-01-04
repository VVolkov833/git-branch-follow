<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

// install / uninstall is in install.php

// trash / untrash
add_action( 'trashed_post', function($postID) {
    wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_updates', [$postID] );
});
add_action( 'untrashed_post', function($postID) {
    if ( ($post_type = get_post_type( $postID )) !==  FCGBF_SLUG ) { return; }
    $auto_updates_option = get_post_meta( $postID, FCGBF_PREF.'rep-auto-updates' )[0] ?? '0';
    schedule_auto_update($postID, $auto_updates_option);
} );

// shedules hooks

// update
add_action( FCGBF_SLUG.'_auto_updates', 'FC\GitBranchFollow\auto_updates_hook', 10, 1 );
function auto_updates_hook($postID) {
    //error_log('auto_updates_hook '.$postID.' install');
    processGitRequest(['id' => $postID, 'action' => 'install']);
}

// check
add_action( FCGBF_SLUG.'_auto_checks', 'FC\GitBranchFollow\auto_checks_hook', 10, 0 );
function auto_checks_hook() {    
    global $wpdb;

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
        FCGBF_SLUG
    ));
    foreach ($post_ids as $post_id) {
        processGitRequest(['id' => $post_id, 'action' => 'check']);
    }
}