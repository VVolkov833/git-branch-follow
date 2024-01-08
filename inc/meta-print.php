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

    add_meta_box(
        FCGBF_PREF.'auto-updates',
        'Auto Updates',
        'FC\GitBranchFollow\auto_updates',
        FCGBF_SLUG,
        'side',
        'low'
    );

});


function rep_infos() {
    global $post, $pagenow;

    $rep_current = get_post_meta( $post->ID, FCGBF_PREF.'rep-current' )[0] ?? [];
    $rep_checked = get_post_meta( $post->ID, FCGBF_PREF.'rep-new' )[0] ?? [];

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
            'disabled' => $pagenow === 'post-new.php' ? true : null,
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
            'className' => 'button' . (empty($rep_checked) ? '' : ' '.FCGBF_PREF.'update-available'),
            'disabled' => $pagenow === 'post-new.php' ? true : null,
        ]);

        ?>
    </div>

    <input type="hidden" name="<?php echo esc_attr( FCGBF_PREF ) ?>nonce" value="<?php echo esc_attr( wp_create_nonce( FCGBF_PREF.'nonce' ) ) ?>">
    <input type="hidden" id="<?php echo esc_attr( FCGBF_PREF ) ?>rest-nonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>">

    <div class="<?php echo FCGBF_PREF ?>infos">
        <div class="<?php echo FCGBF_PREF ?>current"><?php format_data( 'Current Version', $rep_current ) ?></div>
        <div class="<?php echo FCGBF_PREF ?>checked"><?php format_data( 'Available version', $rep_checked ) ?></div>
    </div>
    <div class="<?php echo FCGBF_PREF ?>response"></div>

    <?php
}

function auto_updates() {
    global $post;

    $has_updates = !empty( get_post_meta( $post->ID, FCGBF_PREF.'rep-new' )[0] ?? [] );
    $trigger_next_time = next_update_in($post->ID);
    $auto_updates_type = get_post_meta( $post->ID, FCGBF_PREF.'rep-auto-updates' )[0] ?? '0';

    ?>
    <div class="<?php echo FCGBF_PREF ?>auto-update">
    <?php
        select( (object) [
            'name' => 'rep-auto-updates',
            'options' => ['0' => 'Off', '1' => 'Enabled', '2' => 'Force'], // ++ move all options to one place
            'value' => $auto_updates_type,
        ]);
    ?>

    <p>The <em>Force</em> option will run the process every scheduled time even if there are no new commits</p>

    <?php
    if ( $auto_updates_type !== '0' ) {
        echo $trigger_next_time;
        echo next_check_in();
    }
    ?>

    </div>
    <?php
}


function format_data($heading, $data) {
    if ( empty($data) ) { return ''; }

    $commit = $data->commit->commit;
    $committer = $commit->committer;

    $done_label = isset($data->extended_locally->checked) ? 'Last Checked' : 'Last Updated';
    
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $operation_date = date( 'Y-m-d\TH:i:s\Z', $data->extended_locally->date );
    date_default_timezone_set($originalTimezone);

    $print = [
        "Commiter Date" => $committer->date,
        "Commiter Message" => $commit->message,
        "Commiter Name" => $committer->name,
        "Branch" => $data->name,
        $done_label => $operation_date,
    ];

    ?>
    <h3><?php echo esc_html($heading) ?></h3>
    <dl>
    <?php foreach ($print as $k => $v) { ?>
        <dt><?php echo esc_html($k) ?></dt>
        <dd><?php echo $v ?></dd>
    <?php } ?>
    </dl>
    <?php
}