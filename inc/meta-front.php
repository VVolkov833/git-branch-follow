<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function($hook) {

    if ( !in_array( $hook, ['post.php', 'post-new.php', 'edit.php'] ) ) { return; }

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCGBF_SLUG ) { return; }

    wp_enqueue_style( FCGBF_PREF.'style', FCGBF_URL.'assets/style.css', [], FCGBF_VER );
    wp_enqueue_script( FCGBF_PREF.'scripts', FCGBF_URL.'assets/scripts.js', [], FCGBF_VER );
    wp_localize_script(FCGBF_PREF.'scripts', FCGBF_SLUG.'_vars', [
        'SLUG' => FCGBF_SLUG,
        'PREF' => FCGBF_PREF,
        'URL' => get_rest_url( null, '/'.FCGBF_ENDPOINT.'/' ),
    ]);

});


// *****limit post statuses only to FCGBF_PREF.'active'

add_action( 'init', function() {
	register_post_status( FCGBF_PREF.'active', [
		'label'                     => __( 'Active' ),
        'label_count'               => false,
        'exclude_from_search'       => true,
        '_builtin'                  => false,
        'public'                    => false,
        'internal'                  => true,
        'protected'                 => true,
        'private'                   => true,
        'publicly_queryable'        => false,
        'show_in_admin_status_list' => false,
        'show_in_admin_all_list'    => false,
	]);
});

// hide the quick edit option
add_filter('post_row_actions', function ($actions) {
    global $post_type;
    if ( FCGBF_SLUG !== $post_type ) { return $actions; }
    unset( $actions['inline hide-if-no-js'] );
    return $actions;
}, 10, 2);

// change the save metabox block headline: Publish to Options
add_filter( 'do_meta_boxes', function() {
    global $wp_meta_boxes;
    $wp_meta_boxes[FCGBF_SLUG]['side']['core']['submitdiv']['title'] = __( 'Options' );
    return $wp_meta_boxes;
}, 0 );

// post status is glued in meta-save.php