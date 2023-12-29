<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function($hook) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) { return; }

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCGBF_SLUG ) { return; }

    wp_enqueue_style( FCGBF_PREF.'style', FCGBF_URL.'assets/style.css', [], FCGBF_VER );
    wp_enqueue_script( FCGBF_PREF.'scripts', FCGBF_URL.'assets/scripts.js', [], FCGBF_VER );

});