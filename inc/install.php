<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


register_activation_hook(FCGBF_REGISTER, function() {
    global $wpdb;


    // add the self entry to update the plugin automatically

    // check if the record about self already exists
    $existing_meta_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = %s",
        FCGBF_PREF.'rep-self'
    ));
    if ( !$existing_meta_id ) {

        // add the record
        $post_args = array(
            'post_title'   => 'git-branch-follow',
            'post_type'    => FCGBF_SLUG,
            'post_status'  => 'publish',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        );
        $wpdb->insert($wpdb->posts, $post_args);
        $post_id = $wpdb->insert_id;

        // add meta values
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            VALUES
                (%d, %s, %s),
                (%d, %s, %s),
                (%d, %s, %s),
                (%d, %s, %s),
                (%d, %s, %s)",
            $post_id, FCGBF_PREF.'rep-url', 'https://github.com/VVolkov833/git-branch-follow',
            $post_id, FCGBF_PREF.'rep-branch', 'main',
            $post_id, FCGBF_PREF.'rep-dest', 'plugins',
            $post_id, FCGBF_PREF.'rep-self', '1',
            $post_id, FCGBF_PREF.'rep-auto-updates', '1'
        ));

    }

    // add schedules

    // schedule the auto-updates for existing entries (self, re-activate)
    $results = $wpdb->get_results( $wpdb->prepare("
        SELECT p.ID, m1.meta_value AS auto_updates_type, COALESCE(m2.meta_value, '') AS has_updates
        FROM $wpdb->posts p
        INNER JOIN $wpdb->postmeta m1 ON p.ID = m1.post_id
        LEFT JOIN $wpdb->postmeta m2 ON p.ID = m2.post_id AND m2.meta_key = %s
        WHERE p.post_type = %s
        AND p.post_status = %s
        AND m1.meta_key = %s
        AND m1.meta_value <> %s
        ",
        FCGBF_PREF.'rep-new', FCGBF_SLUG, 'publish', FCGBF_PREF.'rep-auto-updates', '0'
    ));

    $time = get_schedule_start();
    foreach ($results as $row) {
        if ( schedule_auto_update( $row->ID, (string) $row->auto_updates_type, !empty($row->has_updates), $time ) !== 'updateEventAdded' ) { continue; }
        $time += 60*3;
    }

    // schedule auto-checks
    wp_schedule_event( $time, 'twicedaily', FCGBF_SLUG.'_auto_checks' );
});


register_deactivation_hook( FCGBF_REGISTER, function() {
    wp_unschedule_hook( FCGBF_SLUG.'_auto_checks' );
    wp_unschedule_hook( FCGBF_SLUG.'_auto_updates' );
});