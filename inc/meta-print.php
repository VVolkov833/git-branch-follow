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

function format_data($heading, $data) {
    if ( empty($data) ) { return ''; }

    $commit = $data->commit->commit;
    $committer = $commit->committer;

    $done_label = isset($data->extended_locally->checked) ? 'Last Checked' : 'Last Installed';
    $date_format = get_option('date_format');
    $date_format = strpos($date_format, 'H') || strpos($date_format, 'h') ? $date_format : $date_format.' H:i:s';

    $print = [
        $done_label => date( $date_format, $data->extended_locally->date ),
        "Commiter Date" => date( $date_format, strtotime($committer->date) ),
        "Commiter Name" => $committer->name,
        "Commiter Message" => $commit->message,
        "Branch" => $data->name,
    ];

    ?>
    <h3><?php echo esc_html($heading) ?></h3>
    <dl>
    <?php foreach ($print as $k => $v) { ?>
        <dt><?php echo esc_html($k) ?></dt>
        <dd><?php echo esc_html($v) ?></dd>
    <?php } ?>
    </dl>
    <?php
}

function rep_infos() {
    global $post;

    ?>
    <div class="<?php echo FCGBF_PREF ?>fields">
        <?php

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
            'placeholder' => FCGBF_BRANCH,
            'value' => get_post_meta( $post->ID, FCGBF_PREF.'rep-branch' )[0] ?? '',
        ]);

        button( (object) [
            'name' => 'rep-check',
            'title' => 'Check for existence / updates',
            'value' => 'Check',
            'className' => 'button',
        ]);

        ?>
    </div>
    <div class="<?php echo FCGBF_PREF ?>fields">
        Destination: <?php echo WP_CONTENT_DIR ?>/
        <?php

        select( (object) [
            'name' => 'rep-dest',
            'options' => ['plugins' => 'plugins', 'themes' => 'themes'],
            'value' => get_post_meta( $post->ID, FCGBF_PREF.'rep-dest' )[0] ?? '',
        ]);

        ?>/{github_repoisitory_name}/<?php

        button( (object) [
            'name' => 'rep-install',
            'title' => 'Override the content of Destination',
            'value' => 'Install / Update',
            'className' => 'button',
        ]);
        ?>
    </div>

    <input type="hidden" name="<?php echo esc_attr( FCGBF_PREF ) ?>nonce" value="<?php echo esc_attr( wp_create_nonce( FCGBF_PREF.'nonce' ) ) ?>">
    <input type="hidden" id="<?php echo esc_attr( FCGBF_PREF ) ?>rest-nonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>">

    <div class="<?php echo FCGBF_PREF ?>infos">
        <div class="<?php echo FCGBF_PREF ?>current"><?php format_data( 'Current Version', get_post_meta( $post->ID, FCGBF_PREF.'rep-current' )[0] ?? [] ) ?></div>
        <div class="<?php echo FCGBF_PREF ?>checked"><?php format_data( 'Available version', get_post_meta( $post->ID, FCGBF_PREF.'rep-new' )[0] ?? [] ) ?></div>
    </div>
    <div class="<?php echo FCGBF_PREF ?>response"></div>

    <?php
}