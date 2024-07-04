<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_action( 'init', function() {
    $shorter = [
        'name' => 'Git Repository',
        'plural' => 'Git Repositories',
        'public' => false,
        'description' => 'Add github repositories of your plugins or themes to follow the updates',
    ];
    $labels = [
        'name'                => $shorter['plural'],
        'singular_name'       => $shorter['name'],
        'menu_name'           => $shorter['plural'],
        'all_items'           => 'View All ' . $shorter['plural'],
        'archives'            => 'All ' . $shorter['plural'],
        'view_item'           => 'View ' . $shorter['name'],
        'add_new'             => 'Add New',
        'add_new_item'        => 'Add New ' . $shorter['name'],
        'edit_item'           => 'Edit ' . $shorter['name'],
        'update_item'         => 'Update ' . $shorter['name'],
        'search_items'        => 'Search ' . $shorter['name'],
        'not_found'           => $shorter['name'] . ' Not Found',
        'not_found_in_trash'  => $shorter['name'] . ' Not found in Trash',
    ];
    $args = [
        'label'               => $shorter['name'],
        'description'         => $shorter['description'],
        'labels'              => $labels,
        'supports'            => [],
        'hierarchical'        => false,
        'public'              => $shorter['public'],
        'show_in_rest'        => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'menu_position'       => 29,
        'menu_icon'           => 'dashicons-open-folder',
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => !$shorter['public'],
        'publicly_queryable'  => $shorter['public'],
        'capabilities'        => [ // only admins
            'edit_post'          => 'switch_themes',
            'read_post'          => 'switch_themes',
            'delete_post'        => 'switch_themes',
            'edit_posts'         => 'switch_themes',
            'edit_others_posts'  => 'switch_themes',
            'delete_posts'       => 'switch_themes',
            'publish_posts'      => 'switch_themes',
            'read_private_posts' => 'switch_themes'
        ]
    ];
    register_post_type( FCGBF_SLUG, $args );
});

add_action('admin_init', function() {
    remove_post_type_support(FCGBF_SLUG, 'title');
    remove_post_type_support(FCGBF_SLUG, 'editor');
});

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


function list_rep_posts() {
    global $wpdb;

    static $plugins_to_mark = null;
    if ( $plugins_to_mark !== null ) { return $plugins_to_mark; }

    $plugins_to_mark = $wpdb->get_results( $wpdb->prepare( "
        SELECT ID, post_title FROM $wpdb->posts WHERE post_type = %s AND post_status != 'trash'
        ",
        FCGBF_SLUG
    ));
    return $plugins_to_mark;
}