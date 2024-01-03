<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


// schedule the autoupdates
add_action( FCGBF_SLUG.'_auto_updates', 'FC\GitBranchFollow\auto_updates_hook', 10, 1 );
function auto_updates_hook($postID) {
    processGitRequest($postID, 'install');
}
function schedule_auto_update($postID, $type) {
    if ( in_array( $type, ['1', '2'] ) ) {
        wp_schedule_event( current_time('timestamp'), 'twicedaily', FCGBF_SLUG.'_auto_updates', [$postID] );
    } else {
        wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_updates', [$postID] );
    }
}


// schedule the check
add_action( FCGBF_SLUG.'_auto_checks', 'FC\GitBranchFollow\auto_checks_hook', 10, 0 );
function auto_checks_hook() {    
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
        FCGBF_SLUG
    ));
    foreach ($post_ids as $post_id) {
        processGitRequest($post_id, 'check');
    }
}
register_activation_hook(FCGBF_REGISTER, function() {
    wp_schedule_event( current_time('timestamp'), 'twicedaily', FCGBF_SLUG.'_auto_checks' );
});
register_deactivation_hook(FCGBF_REGISTER, function() {
    wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_checks' );
});

// ++ clear the events on disable the plugin (wp_clear_scheduled_hook(FCGBF_SLUG.'_auto_updates');) and re-enable on install