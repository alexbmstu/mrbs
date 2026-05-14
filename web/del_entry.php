<?php

// This file is part of the MRBS block for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
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

global $PAGE, $DB, $USER;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";
require_once "mrbs_sql.php";

$id = required_param('id', PARAM_INT);
$series = optional_param('series', 0, PARAM_INT);

$thisurl = new moodle_url('/blocks/mrbs/web/del_entry.php', array(
    'id' => $id,
    'series' => $series
));
$PAGE->set_url($thisurl);

require_login();
require_sesskey();

$day = 0;
$month = 0;
$year = 0;
$area = 0;

if (getAuthorised(1) && ($info = mrbsGetEntryInfo($id))) {
    $day = (int)userdate($info->start_time, "%d");
    $month = (int)userdate($info->start_time, "%m");
    $year = (int)userdate($info->start_time, "%Y");
    $area = mrbsGetRoomArea($info->room_id);

    if (MAIL_ADMIN_ON_DELETE) {
        $mail_previous = getPreviousEntryData($id, $series);
    }

    $roomadmin = false;
    $context = context_system::instance();
    if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
        $adminemail = $DB->get_field('block_mrbs_room', 'room_admin_email', array('id' => $info->room_id));
        if ($adminemail == $USER->email) {
            $roomadmin = true;
        }
    }

    $result = mrbsDelEntry(getUserName(), $id, $series, 1, $roomadmin);

    if ($result) {
        if (MAIL_ADMIN_ON_DELETE) {
            $result = notifyAdminOnDelete($mail_previous);
        }

        $desturl = new moodle_url('/blocks/mrbs/web/day.php', array(
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'area' => $area
        ));
        redirect($desturl);
        exit();
    }
}

showAccessDenied($day, $month, $year, $area);