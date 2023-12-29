<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;


function input($a) {
    ?>
    <input type="text"
        name="<?php echo esc_attr( FCGBF_PREF.$a->name ) ?>"
        id="<?php echo esc_attr( FCGBF_PREF.$a->name ) ?>"
        placeholder="<?php echo isset( $a->placeholder ) ? esc_attr( $a->placeholder )  : '' ?>"
        title="<?php echo esc_attr( $a->title ) ?>"
        value="<?php echo isset( $a->value ) ? esc_attr( $a->value ) : '' ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"
    />
    <?php
}

function button($a) {
    ?>
    <button type="button"
        name="<?php echo esc_attr( FCGBF_PREF.$a->name ) ?>"
        id="<?php echo esc_attr( FCGBF_PREF.$a->name ) ?>"
        title="<?php echo esc_attr( $a->title ) ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"
    >
        <?php if (isset($a->imageSrc)) { ?>
            <img src="<?php echo esc_url($a->imageSrc); ?>" alt="<?php echo esc_attr($a->imageAlt); ?>">
        <?php } ?>
        <?php echo isset( $a->value ) ? esc_html( $a->value ) : ''; ?>
    </button>
    <?php
}