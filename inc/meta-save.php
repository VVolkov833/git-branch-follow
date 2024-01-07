<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


add_action( 'save_post', function( $postID ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( empty( $_POST[ FCGBF_PREF.'nonce' ] ) || !wp_verify_nonce( $_POST[ FCGBF_PREF.'nonce' ], FCGBF_PREF.'nonce' ) ) { return; }
    if ( !current_user_can( 'administrator' ) ) { return; }

    $post = get_post( $postID );
    if ( $post->post_type !== FCGBF_SLUG ) { return; }
    if ( $post->post_type === 'revision' ) { return; }

    $fields = [ 'rep-url', 'rep-api-key', 'rep-branch', 'rep-dest', 'rep-auto-updates' ];

    foreach ( $fields as $f ) {
        $f = FCGBF_PREF.$f;
        if ( empty( $_POST[ $f ] ) || empty( $new_value = sanitize_meta( $_POST[ $f ], $f, $postID ) ) ) {
            delete_post_meta( $postID, $f );
            continue;
        }
        update_post_meta( $postID, $f, $new_value );
    }

    // schedule the update event
    schedule_auto_update( $postID, $_POST[FCGBF_PREF.'rep-auto-updates'] );

});

// update the title
add_filter('wp_insert_post_data', function ($data, $postarr) {
    if ( $data['post_type'] !== FCGBF_SLUG || !current_user_can('administrator') ) { return $data; }

    $rep_url = $postarr[FCGBF_PREF.'rep-url'] ?? get_post_meta( $postarr['ID'], FCGBF_PREF.'rep-url' )[0] ?? '';
    $rep_url = gitUrlSplit( $rep_url );

    $data['post_title'] = $rep_url[1] ?: 'Repository not set'; // ++on untrash it loses the title
    $data['post_status'] = in_array($data['post_status'], ['auto-draft', 'trash']) ? $data['post_status'] : FCGBF_PREF.'active';

    return $data;
}, 1, 2);


function sanitize_meta( $value, $field, $postID ) {

    $field = ( strpos( $field, FCGBF_PREF ) === 0 ) ? substr( $field, strlen( FCGBF_PREF ) ) : $field;

    switch( $field ) {
        case ( 'rep-url' ):
            return $value;
        break;
        case ( 'rep-api-key' ):
            return $value;
        break;
        case ( 'rep-branch' ):
            return $value;
        break;
        case ( 'rep-dest' ):
            return in_array($value, ['plugins', 'themes']) ? $value : 'plugins';
        break;
        case ( 'rep-auto-updates' ):
            return in_array($value, ['0', '1', '2']) ? $value : '0';
        break;
    }

    return '';
}