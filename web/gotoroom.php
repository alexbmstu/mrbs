<?php
// This file is part of the MRBS block for Moodle.
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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $DB;

include "config.inc.php";
include "functions.php";
require_once('mrbs_auth.php');
include "mrbs_sql.php";

$room = required_param('room', PARAM_TEXT);
$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);

// If we don't know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
} else {
    while (!checkdate((int)$month, (int)$day, (int)$year)) {
        $day--;
    }
}

$thisurl = new moodle_url('/blocks/mrbs/web/gotoroom.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year,
    'room' => $room,
));
$PAGE->set_url($thisurl);

require_login();

if (!getAuthorised(1)) {
    showAccessDenied($day, $month, $year, null);
    exit;
}

$sql = "SELECT a.id AS area_id, a.area_name
          FROM {block_mrbs_room} r
          JOIN {block_mrbs_area} a ON a.id = r.area_id
         WHERE r.room_name = ? OR r.room_name = ?";

$area = $DB->get_record_sql($sql, array($room, '0' . $room), IGNORE_MULTIPLE);

if ($area) {
    $areaurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'day' => $day,
        'month' => $month,
        'year' => $year,
        'area' => $area->area_id,
    ));
    redirect($areaurl);
} else {
    $notfoundurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'day' => $day,
        'month' => $month,
        'year' => $year,
        'roomnotfound' => $room,
    ));
    redirect($notfoundurl);
}