<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


// link the new css from the plugins list
add_filter( 'plugin_action_links_'.FCGBF_BSN, function($links) {
    $settings_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type='.FCGBF_SLUG ) ) . '">Repositories</a>';
    array_unshift( $links, $settings_link );
    return $links;
});

// mark and link managed plugins in the plugins' list
add_filter( 'plugin_action_links', function($actions, $plugin_file, $plugin_data, $context) {

    $plugins_to_mark = list_rep_posts();

    foreach ( $plugins_to_mark as $plugin_post ) {
        if ( strpos( $plugin_file, $plugin_post->post_title.'/' ) !== 0 ) { continue; }
        add_filter( 'plugin_action_links_'.$plugin_file, function($links) use ($plugin_post) {
            $rep_link = '<a
                href="' . esc_url( admin_url( 'post.php?action=edit&post='.$plugin_post->ID ) ) . '"
                class="wp-menu-image dashicons-before dashicons-open-folder"
                title="The updates are followed this Git repository"
            ></a>';
            array_unshift( $links, $rep_link );
            return $links;
        });
    }

    return $actions;
}, 10, 4 );