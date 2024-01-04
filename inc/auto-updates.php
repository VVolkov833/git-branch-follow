<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


// plugin activate / deactevate adding schedules
register_activation_hook( FCGBF_REGISTER, function() {
    // check
    wp_schedule_event( get_schedule_start(), 'twicedaily', FCGBF_SLUG.'_auto_checks' );
    // ++ install
});
register_deactivation_hook( FCGBF_REGISTER, function() {
    // check
    wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_checks' );
    // ++ install
});

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


// functions

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
// ++ !! remove the event on post delete and restore on restore!!
// ++ clear the events on disable the plugin (wp_clear_scheduled_hook(FCGBF_SLUG.'_auto_updates');) and re-enable on install