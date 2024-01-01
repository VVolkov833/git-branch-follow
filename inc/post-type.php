<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_action( 'init', function() {
    $shorter = [
        'name' => 'Repository',
        'plural' => 'Repositories',
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


// the main list view change
