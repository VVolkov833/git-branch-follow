<?php

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

function compareDates($date1, $date2) {
    $dateTime1 = new \DateTime($date1);
    $dateTime2 = new \DateTime($date2);

    if ($dateTime1 < $dateTime2) {
        return -1;
    } elseif ($dateTime1 > $dateTime2) {
        return 1;
    } else {
        return 0;
    }
}