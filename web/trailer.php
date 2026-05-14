<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $OUTPUT;

$pview = $pview ?? 0;
$year = $year ?? (int)date('Y');
$month = $month ?? (int)date('m');
$day = $day ?? (int)date('d');
$area = $area ?? 0;
$room = $room ?? null;
$dateformat = $dateformat ?? '';
$weekstarts = $weekstarts ?? 0;
$view_week_number = $view_week_number ?? false;

if ((int)$pview !== 1) {
    echo '<p><hr><b>' . get_string('viewday', 'block_mrbs') . ":</b>\n";

    $params = empty($area) ? array() : array('area' => $area);

    for ($i = -6; $i <= 7; $i++) {
        $ctime = mktime(0, 0, 0, $month, $day + $i, $year);
        $str = userdate($ctime, empty($dateformat) ? "%b %d" : "%d %b");

        $cyear = (int)date("Y", $ctime);
        $cmonth = (int)date("m", $ctime);
        $cday = (int)date("d", $ctime);

        if ($i != -6) {
            echo ' | ';
        }
        if ($i == 0) {
            echo '<b>[ ';
        }

        $url = new moodle_url('/blocks/mrbs/web/day.php', array_merge(array(
            'year' => $cyear,
            'month' => $cmonth,
            'day' => $cday
        ), $params));

        echo '<a href="' . $url . '">' . s($str) . "</a>\n";

        if ($i == 0) {
            echo ']</b> ';
        }
    }

    echo '<br><b>' . get_string('viewweek', 'block_mrbs') . ":</b>\n";

    if (!empty($room)) {
        $params['room'] = is_object($room) ? $room->id : $room;
    }

    $ctime = mktime(0, 0, 0, $month, $day, $year);
    $skipback = (date("w", $ctime) - $weekstarts + 7) % 7;

    for ($i = -4; $i <= 4; $i++) {
        $ctime = mktime(0, 0, 0, $month, $day + 7 * $i - $skipback, $year);

        $cweek = date("W", $ctime);
        $cday = (int)date("d", $ctime);
        $cmonth = (int)date("m", $ctime);
        $cyear = (int)date("Y", $ctime);

        if ($i != -4) {
            echo ' | ';
        }

        if ($view_week_number) {
            $str = $cweek;
        } else {
            $str = userdate($ctime, empty($dateformat) ? "%b %d" : "%d %b");
        }

        if ($i == 0) {
            echo '<b>[ ';
        }

        $url = new moodle_url('/blocks/mrbs/web/week.php', array_merge(array(
            'year' => $cyear,
            'month' => $cmonth,
            'day' => $cday
        ), $params));

        echo '<a href="' . $url . '">' . s($str) . "</a>\n";

        if ($i == 0) {
            echo ']</b> ';
        }
    }

    echo '<br><b>' . get_string('viewmonth', 'block_mrbs') . ":</b>\n";

    for ($i = -2; $i <= 6; $i++) {
        $ctime = mktime(0, 0, 0, $month + $i, 1, $year);
        $str = userdate($ctime, "%b %Y");

        $cmonth = (int)date("m", $ctime);
        $cyear = (int)date("Y", $ctime);

        if ($i != -2) {
            echo ' | ';
        }
        if ($i == 0) {
            echo '<b>[ ';
        }

        $url = new moodle_url('/blocks/mrbs/web/month.php', array_merge(
            array('year' => $cyear, 'month' => $cmonth),
            $params
        ));

        echo '<a href="' . $url . '">' . s($str) . "</a>\n";

        if ($i == 0) {
            echo ']</b> ';
        }
    }

    echo '<hr>';

    $thisurl = new moodle_url($PAGE->url, array('pview' => 1));
    echo '<p><div style="text-align:center;"><a href="' . $thisurl . '">'
        . get_string('ppreview', 'block_mrbs') . '</a></div></p>';
}

echo '</div>';

echo $OUTPUT->footer();