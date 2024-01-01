<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

function gitUrlSplit($url) {
    if ( !$url || !preg_match( '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)$/', $url, $matches) ) { return ['','']; }
    return [$matches[1] ?? '', $matches[2] ?? ''];
}