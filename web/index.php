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
include("config.inc.php");

global $DB;

require_login();

$day = (int)date("d");
$month = (int)date("m");
$year = (int)date("Y");

switch ($default_view) {
    case 'month':
        $redirect = new moodle_url('/blocks/mrbs/web/month.php', array(
            'year' => $year,
            'month' => $month,
        ));
        break;

    case 'week':
        $redirect = new moodle_url('/blocks/mrbs/web/week.php', array(
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ));
        break;

    default:
        $redirect = new moodle_url('/blocks/mrbs/web/day.php', array(
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ));
        break;
}

if (!empty($default_room)) {
    $room = $DB->get_record('block_mrbs_room', array('id' => $default_room));

    if (!empty($room)) {
        $redirect->params(array(
            'area' => $room->area_id,
            'room' => $default_room,
        ));
    }
}

redirect($redirect);