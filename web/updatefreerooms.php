<?php

// This file is part of the MRBS block for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php'); // for Moodle integration

global $USER, $DB;

require_once "config.inc.php";
require_once "functions.php";
require_once 'mrbs_auth.php';

require_login();

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$period = optional_param('period', 0, PARAM_INT);
$durationraw = optional_param('duration', '0', PARAM_RAW_TRIMMED);
$dur_units = optional_param('dur_units', '', PARAM_TEXT);
$area = optional_param('area', 0, PARAM_RAW_TRIMMED);
$currentroom = optional_param('currentroom', 0, PARAM_INT);
$all_day = optional_param('all_day', false, PARAM_BOOL);
$hour = optional_param('hour', 0, PARAM_INT);
$minute = optional_param('minute', 0, PARAM_INT);
$ampm = optional_param('ampm', '', PARAM_ALPHA);

if (empty($area)) {
    $area = get_default_area();
}

// Support locales where ',' is used as the decimal point.
$durationraw = preg_replace('/,/', '.', $durationraw);
$duration = is_numeric($durationraw) ? (float)$durationraw : 0.0;

$resolution = isset($resolution) ? (int)$resolution : 3600;

if ($enable_periods) {
    $resolution = 60;
    $hour = 12;
    $minute = $period;
    $max_periods = count($periods);

    if ($dur_units === "periods" && ($minute + $duration) > $max_periods) {
        $duration = (24 * 60 * floor($duration / $max_periods)) + ($duration % $max_periods);
    }

    if ($dur_units === "days" && $minute == 0) {
        $dur_units = "periods";
        $duration = $max_periods + ($duration - 1) * 60 * 24;
    }
}

// Units start in seconds.
$units = 1.0;

switch ($dur_units) {
    case "years":
        $units *= 52;
    case "weeks":
        $units *= 7;
    case "days":
        $units *= 24;
    case "hours":
        $units *= 60;
    case "periods":
    case "minutes":
        $units *= 60;
    case "seconds":
        break;
}

if ($all_day) {
    if ($enable_periods) {
        $starttime = mktime(12, 0, 0, $month, $day, $year);
        $endtime = mktime(12, $max_periods, 0, $month, $day, $year);
    } else {
        $starttime = mktime($morningstarts, 0, 0, $month, $day, $year);
        $end_minutes = $eveningends_minutes + $morningstarts_minutes;
        if ($eveningends_minutes > 59) {
            $end_minutes += 60;
        }
        $endtime = mktime($eveningends, $end_minutes, 0, $month, $day, $year);
    }
} else {
    if (!$twentyfourhour_format) {
        if ($ampm === "pm" && $hour < 12) {
            $hour += 12;
        }
        if ($ampm === "am" && $hour > 11) {
            $hour -= 12;
        }
    }

    $starttime = mktime($hour, $minute, 0, $month, $day, $year);
    $endtime = $starttime + ($units * $duration);

    $diff = $endtime - $starttime;
    if (($tmp = $diff % $resolution) != 0 || $diff == 0) {
        $endtime += $resolution - $tmp;
    }

    $endtime += cross_dst($starttime, $endtime);
}

$sql = "SELECT r.id, r.room_name, r.description, r.capacity, a.area_name, r.area_id, r.booking_users
          FROM {block_mrbs_room} r
          JOIN {block_mrbs_area} a ON r.area_id = a.id
         WHERE ";

$params = array();

if (!empty($day)) {
    $sql .= "(
                (
                    SELECT COUNT(*)
                      FROM {block_mrbs_entry} e
                     WHERE (
                            (e.start_time >= :starttime1 AND e.end_time < :endtime1)
                         OR (e.start_time < :starttime2 AND e.end_time > :starttime3)
                         OR (e.start_time < :endtime2 AND e.end_time >= :endtime3)
                     )
                       AND e.room_id = r.id
                ) < 1
                OR r.id = :currentroom
             ) AND ";

    $params = array(
        'starttime1' => $starttime,
        'starttime2' => $starttime,
        'starttime3' => $starttime,
        'endtime1' => $endtime,
        'endtime2' => $endtime,
        'endtime3' => $endtime,
        'currentroom' => $currentroom,
    );
}

if ((string)$area === 'IT') {
    $sql .= $DB->sql_like('r.description', ':itdesc', false);
    $params['itdesc'] = 'Teaching IT%';
} else {
    $sql .= "r.area_id = :area";
    $params['area'] = (int)$area;
}

$sql .= " ORDER BY r.room_name";

$rooms = $DB->get_records_sql($sql, $params);

if (!empty($rooms)) {
    $list = '';
    foreach ($rooms as $room) {
        if (allowed_to_book($USER, $room)) {
            $info = array();
            $desc = trim(s($room->description));
            if ($desc !== '') {
                $info[] = $desc;
            }
            if (!empty($room->capacity)) {
                $info[] = (int)$room->capacity;
            }

            $infosuffix = '';
            if (!empty($info)) {
                $infosuffix = ' (' . implode(', ', $info) . ')';
            }

            $list .= (int)$room->id . ',' . s($room->room_name) . $infosuffix . "\n";
        }
    }

    echo rtrim($list, "\n");
}