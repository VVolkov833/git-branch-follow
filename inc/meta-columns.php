<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_filter('manage_'.FCGBF_SLUG.'_posts_columns', function($columns) {
    unset($columns['date']);
    $columns[FCGBF_SLUG.'rep_dest'] = 'Directory';
    $columns[FCGBF_SLUG.'rep_new']  = 'Has updates';
    $columns[FCGBF_SLUG.'rep_auto_updates']  = 'Auto updates';
    return $columns;
});

add_action('manage_'.FCGBF_SLUG.'_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case FCGBF_SLUG.'rep_dest':
            echo get_post_meta( $post_id, FCGBF_PREF.'rep-dest' )[0] ?? '';
        break;
        case FCGBF_SLUG.'rep_new':
            $exists = !!(get_post_meta( $post_id, FCGBF_PREF.'rep-new' )[0] ?? '');
            echo $exists ? '<span style="color:var(--fcgbf-update-available-color);font-weight:bold">YES</span>' : '';
        break;
        case FCGBF_SLUG.'rep_auto_updates':
            $auto_updates_type = get_post_meta( $post_id, FCGBF_PREF.'rep-auto-updates' )[0] ?? '0';
            echo ['0' => 'Off', '1' => 'Enabled', '2' => 'Force', '3' => 'Webhook'][ $auto_updates_type ];
            if ( in_array($auto_updates_type, ['0', '3']) ) { break; }
            echo next_update_in($post_id);
            if ( !FCGBF_DEV ) { break; };
            echo next_check_in();
        break;
    }
}, 10, 2);