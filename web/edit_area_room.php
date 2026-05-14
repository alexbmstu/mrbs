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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $DB, $OUTPUT;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";

// Passed in when starting to edit.
$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);

// Editing general.
$change_done = optional_param('change_done', 0, PARAM_RAW);

// Editing room.
$room_name = optional_param('room_name', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$capacity = optional_param('capacity', 0, PARAM_INT);
$room_admin_email = optional_param('room_admin_email', '', PARAM_TEXT);
$booking_users = optional_param('booking_users', '', PARAM_TEXT);
$change_room = optional_param('change_room', '', PARAM_RAW);

// Editing area.
$area_name = optional_param('area_name', '', PARAM_TEXT);
$area_admin_email = optional_param('area_admin_email', '', PARAM_TEXT);
$change_area = optional_param('change_area', '', PARAM_RAW);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/edit_area_room.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year,
    'sesskey' => sesskey()
));
if ($room) {
    $thisurl->param('room', $room);
}
if ($area) {
    $thisurl->param('area', $area);
}

$PAGE->set_url($thisurl);
require_login();

if (!getAuthorised(2)) {
    showAccessDenied($day, $month, $year, $area);
    exit();
}
require_sesskey();

// Done changing area or room information?
if (!empty($change_done)) {
    if (!empty($room)) {
        $area = $DB->get_field('block_mrbs_room', 'area_id', array('id' => $room));
    }
    $adminurl = new moodle_url('/blocks/mrbs/web/admin.php', array(
        'day' => $day,
        'month' => $month,
        'year' => $year,
        'area' => $area
    ));
    redirect($adminurl);
    exit();
}

print_header_mrbs($day, $month, $year, $area);
echo $OUTPUT->heading(get_string('editroomarea', 'block_mrbs'), 2);

echo '<table>';

if ($room > 0) {
    $valid_email = true;
    if (!empty($room_admin_email)) {
        $emails = explode(',', $room_admin_email);
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email !== '' && !get_user_by_email($email)) {
                $valid_email = false;
                echo $OUTPUT->box(get_string('no_user_with_email', 'block_mrbs', $email));
            }
        }
    }

    $valid_email2 = true;
    if (!empty($booking_users)) {
        $booking_emails = explode(',', $booking_users);
        foreach ($booking_emails as $email) {
            $email = trim($email);
            if ($email !== '' && !get_user_by_email($email)) {
                $valid_email2 = false;
                echo $OUTPUT->box(get_string('no_user_with_email', 'block_mrbs', $email));
            }
        }
    }

    if (!empty($change_room) && $valid_email && $valid_email2) {
        $updroom = new stdClass();
        $updroom->id = $room;
        $updroom->room_name = substr(trim($room_name), 0, 25);
        $updroom->description = $description;
        $updroom->capacity = $capacity;
        $updroom->room_admin_email = trim($room_admin_email);
        $updroom->booking_users = trim($booking_users);

        $DB->update_record('block_mrbs_room', $updroom);
    }

    $dbroom = $DB->get_record('block_mrbs_room', array('id' => $room), '*', MUST_EXIST);

    echo '<tr><td>';
    echo '<h3 align="center">' . get_string('editroom', 'block_mrbs') . '</h3>';
    echo '<form action="' . $thisurl->out_omit_querystring() . '" method="post">';
    echo '<input type="hidden" name="room" value="' . (int)$dbroom->id . '" />';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo '<center><table>';
    echo '<tr><td>' . get_string('name') . ':</td><td><input type="text" name="room_name" value="' . s($dbroom->room_name) . '" maxlength="25" /></td></tr>';
    echo '<tr><td>' . get_string('description') . '</td><td><input type="text" name="description" value="' . s($dbroom->description) . '" /></td></tr>';
    echo '<tr><td>' . get_string('capacity', 'block_mrbs') . ':</td><td><input type="text" name="capacity" value="' . (int)$dbroom->capacity . '" /></td></tr>';
    echo '<tr><td>' . get_string('room_admin_email', 'block_mrbs') . ':</td><td><input type="text" name="room_admin_email" maxlength="255" value="' . s($dbroom->room_admin_email) . '" /></td>';
    if (!$valid_email) {
        echo '<td>&nbsp;</td><td><strong>' . get_string('emailmustbereal') . '</strong></td>';
    }
    echo '</tr>';
    echo '<tr><td>' . get_string('booking_users', 'block_mrbs') . ': ' . $OUTPUT->help_icon('booking_users', 'block_mrbs') . '</td><td><textarea name="booking_users" cols="25" rows="3">' . s($dbroom->booking_users) . '</textarea></td>';
    if (!$valid_email2) {
        echo '<td>&nbsp;</td><td><strong>' . get_string('emailmustbereal') . '</strong></td>';
    }
    echo '</tr>';
    echo '</table>';
    echo '<input type="submit" name="change_room" value="' . get_string('savechanges') . '" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo '<input type="submit" name="change_done" value="' . get_string('backadmin', 'block_mrbs') . '" />';
    echo '</center></form>';
    echo '</td></tr>';
}

if ($area) {
    $valid_email = true;
    if ($area_admin_email !== '') {
        $emails = explode(',', $area_admin_email);
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email !== '' && !get_user_by_email($email)) {
                $valid_email = false;
                echo $OUTPUT->box(get_string('no_user_with_email', 'block_mrbs', $email));
            }
        }
    }

    if (!empty($change_area) && $valid_email) {
        $updarea = new stdClass();
        $updarea->id = $area;
        $updarea->area_name = trim($area_name);
        $updarea->area_admin_email = trim($area_admin_email);
        $DB->update_record('block_mrbs_area', $updarea);
    }

    $dbarea = $DB->get_record('block_mrbs_area', array('id' => $area), '*', MUST_EXIST);

    echo '<tr><td>';
    echo '<h3 align="center">' . get_string('editarea', 'block_mrbs') . '</h3>';
    echo '<form action="' . $thisurl->out_omit_querystring() . '" method="post">';
    echo '<input type="hidden" name="area" value="' . (int)$dbarea->id . '" />';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo '<center><table>';
    echo '<tr><td>' . get_string('name') . ':</td><td><input type="text" name="area_name" value="' . s($dbarea->area_name) . '" /></td></tr>';
    echo '<tr><td>' . get_string('area_admin_email', 'block_mrbs') . ':</td><td><input type="text" name="area_admin_email" maxlength="255" value="' . s($dbarea->area_admin_email) . '" /></td>';
    if (!$valid_email) {
        echo '<td>&nbsp;</td><td><strong>' . get_string('emailmustbereal') . '</strong></td>';
    }
    echo '</tr>';
    echo '</table>';
    echo '<input type="submit" name="change_area" value="' . get_string('savechanges') . '" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo '<input type="submit" name="change_done" value="' . get_string('backadmin', 'block_mrbs') . '" />';
    echo '</center></form>';
    echo '</td></tr>';
}

echo '</table>';

include "trailer.php";