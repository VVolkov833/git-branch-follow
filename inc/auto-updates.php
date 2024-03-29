<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

// permanent hooks are in install.php

// trash / untrash
add_action( 'trashed_post', function($postID) {
    schedule_auto_update( $postID, '0' ); // clear
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

    $exclude = ['', '0', '3']; // auto-updates are off or webhook ++ replace with just in in future
    $placeholder = implode(', ', array_fill(0, count($exclude), '%s'));

    $post_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta
        JOIN $wpdb->posts ON $wpdb->postmeta.post_id = $wpdb->posts.ID
        WHERE $wpdb->postmeta.meta_key = %s
        AND $wpdb->postmeta.meta_value NOT IN ({$placeholder})
        AND $wpdb->posts.post_type = %s
        AND $wpdb->posts.post_status = 'publish'",
        array_merge(
            [FCGBF_PREF.'rep-auto-updates'],
            $exclude,
            [FCGBF_SLUG]
        )
    ));

    foreach ($post_ids as $post_id) {
        $result = processGitRequest(['id' => $post_id, 'action' => 'check']);
        if ( is_wp_error( $result ) ) {
            if ( FCGBF_DEV ) { error_log($post_id); error_log($result); }
        }
    }
}