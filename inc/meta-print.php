<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function() {
    if ( !current_user_can( 'administrator' ) ) { return; }

    add_meta_box(
        FCGBF_PREF.'rep-infos',
        'Repository information',
        'FC\GitBranchFollow\rep_infos',
        FCGBF_SLUG,
        'normal',
        'low'
    );

});


function rep_infos() {
    global $post;

    ?><div class="<?php echo FCGBF_PREF ?>fields"><?php

    input( (object) [
        'name' => 'rep-url',
        'title' => 'GitHub Repository URL',
        'placeholder' => 'https://github.com/{author}/{repository name}',
        'value' => get_post_meta( $post->ID, FCGBF_PREF.'rep-url' )[0] ?? '',
    ]);

    input( (object) [
        'name' => 'rep-api-key',
        'title' => 'GitHub API Key',
        'placeholder' => 'ghp_aAAaAAAAaaAaaAAaaAaAAaAAaaAAaAAAAaaA',
        'value' => get_post_meta( $post->ID, FCGBF_PREF.'rep-api-key' )[0] ?? '',
    ]);

    input( (object) [
        'name' => 'rep-branch',
        'title' => 'GitHub Repository Branch',
        'placeholder' => 'main',
        'value' => get_post_meta( $post->ID, FCGBF_PREF.'rep-branch' )[0] ?? '',
    ]);

    button( (object) [
        'name' => 'rep-check',
        'title' => 'Check for existence / updates',
        'id' => 'rep-check',
        'value' => 'Check',
        'className' => 'button',
    ]);

    button( (object) [
        'name' => 'rep-install',
        'title' => 'Install or update the repository',
        'id' => 'rep-install',
        'value' => 'Install / Update',
        'className' => 'button',
    ]);

    ?><input type="hidden" name="<?php echo esc_attr( FCGBF_PREF ) ?>nonce" value="<?= esc_attr( wp_create_nonce( FCGBF_PREF.'nonce' ) ) ?>"><?php

    ?></div><?php
}