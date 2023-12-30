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

    $fields = [ 'rep-url', 'rep-api-key', 'rep-branch', 'rep-dest' ];

    foreach ( $fields as $f ) {
        $f = FCGBF_PREF.$f;
        if ( empty( $_POST[ $f ] ) || empty( $new_value = sanitize_meta( $_POST[ $f ], $f, $postID ) ) ) {
            delete_post_meta( $postID, $f );
            continue;
        }
        update_post_meta( $postID, $f, $new_value );
    }
});


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
            return $value;
            //return in_array($value, ['plugins', 'themes']) ? $value : 'plugins';
        break;
    }

    return '';
}