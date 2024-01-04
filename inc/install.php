<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


register_activation_hook(FCGBF_REGISTER, function() {
    global $wpdb;

    // add the first entry to update itself

    // check if the record about self already exists
    $existing_meta_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = %s",
        FCGBF_PREF.'rep-self'
    ));
    if ( $existing_meta_id ) { return; }

    // add the record
    $post_args = array(
        'post_title'   => 'git-branch-follow',
        'post_type'    => FCGBF_SLUG,
        'post_status'  => FCGBF_PREF.'active',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    );
    $wpdb->insert($wpdb->posts, $post_args);
    $post_id = $wpdb->insert_id;

    // add meta values
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
        $post_id, FCGBF_PREF.'rep-url', 'https://github.com/VVolkov833/git-branch-follow'
    ));
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
        $post_id, FCGBF_PREF.'rep-branch', 'main'
    ));
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
        $post_id, FCGBF_PREF.'rep-dest', 'plugins'
    ));
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
        $post_id, FCGBF_PREF.'rep-self', '1'
    ));

    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
        $post_id, FCGBF_PREF.'rep-auto-updates', '1'
    ));

    // schedule the auto-updates for the entry
    schedule_auto_update( $post_id, '1' );
    // schedule auto-checks for all entries
    wp_schedule_event( get_schedule_start(), 'twicedaily', FCGBF_SLUG.'_auto_checks' );
    // ++ add event for existing posts in case of re-activating
});


register_deactivation_hook( FCGBF_REGISTER, function() {
    // check
    wp_clear_scheduled_hook( FCGBF_SLUG.'_auto_checks' );
    // update
    wp_unschedule_hook( FCGBF_SLUG.'_auto_updates' );
});

/*
register_deactivation_hook(FCGBF_REGISTER, function() {
    global $wpdb;

    // check if the record about self already exists
    $existing_meta_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = %s",
        FCGBF_PREF.'rep-self'
    ));
    if ( !$existing_meta_id ) { return; }

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $wpdb->posts WHERE ID = %d",
        $existing_meta_id
    ));
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $wpdb->postmeta WHERE post_id = %d",
        $existing_meta_id
    ));
});
//*/

// some parts are in ./auto-updates.php