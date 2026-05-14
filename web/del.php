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

global $PAGE, $DB;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$type = required_param('type', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/del.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year,
    'type' => $type
));

if ($area) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($room) {
    $thisurl->param('room', $room);
}
if ($confirm) {
    $thisurl->param('confirm', $confirm);
}

$PAGE->set_url($thisurl);
require_login();

if (!getAuthorised(2)) {
    showAccessDenied($day, $month, $year, $area);
    exit();
}

require_sesskey();

$adminurl = new moodle_url('/blocks/mrbs/web/admin.php');

// This is gonna blast away something. We want them to be really sure
// that this is what they want to do.
if ($type === "room") {
    $adminurl->param('area', $area);

    if ($confirm) {
        $DB->delete_records('block_mrbs_entry', array('room_id' => $room));
        $DB->delete_records('block_mrbs_room', array('id' => $room));

        redirect($adminurl);
    } else {
        print_header_mrbs($day, $month, $year, $area);

        $bookings = $DB->get_records('block_mrbs_entry', array('room_id' => $room));
        if (!empty($bookings)) {
            echo get_string('deletefollowing', 'block_mrbs') . ":<ul>";

            foreach ($bookings as $booking) {
                echo '<li>' . s($booking->name) . ' (';
                echo time_date_string($booking->start_time) . " -> ";
                echo time_date_string($booking->end_time) . ")</li>";
            }

            echo "</ul>";
        }

        echo "<center>";
        echo "<h1>" . get_string('sure', 'block_mrbs') . "</h1>";
        echo '<h1><a href="' . $thisurl->out(true, array(
            'confirm' => 1,
            'sesskey' => sesskey()
        )) . '">' . get_string('yes') . "</a>";
        echo '&nbsp;&nbsp;&nbsp; <a href="' . $adminurl . '">' . get_string('no') . "</a></h1>";
        echo "</center>";

        include "trailer.php";
    }
} else if ($type === "area") {
    $n = $DB->count_records('block_mrbs_room', array('area_id' => $area));

    if ($n == 0) {
        $DB->delete_records('block_mrbs_area', array('id' => $area));
        redirect($adminurl);
    } else {
        print_header_mrbs($day, $month, $year, $area);

        echo '<br /><p>' . get_string('delarea', 'block_mrbs') . '</p>';
        echo '<a href="' . $adminurl . '">' . get_string('backadmin', 'block_mrbs') . "</a>";

        include "trailer.php";
    }
} else {
    throw new moodle_exception('invalidrequest');
}