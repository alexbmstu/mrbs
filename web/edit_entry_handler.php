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

global $PAGE, $DB, $USER, $OUTPUT;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";
require_once "mrbs_sql.php";

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$period = optional_param('period', 0, PARAM_INT);
$hour = optional_param('hour', 0, PARAM_INT);
$minute = optional_param('minute', 0, PARAM_INT);
$durationraw = optional_param('duration', 0, PARAM_RAW);
$dur_units = optional_param('dur_units', 'periods', PARAM_TEXT);
$create_by = optional_param('create_by', '', PARAM_TEXT);
$name = optional_param('name', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$rep_type = optional_param('rep_type', 0, PARAM_INT);
$rep_end_month = optional_param('rep_end_month', 0, PARAM_INT);
$rep_end_day = optional_param('rep_end_day', 0, PARAM_INT);
$rep_end_year = optional_param('rep_end_year', 0, PARAM_INT);
$rep_num_weeks = optional_param('rep_num_weeks', 0, PARAM_INT);
$rep_opt = optional_param('rep_opt', '', PARAM_SEQUENCE);
$rep_enddate = optional_param('rep_enddate', 0, PARAM_INT);
$forcebook = optional_param('forcebook', false, PARAM_BOOL);
$edit_type = optional_param('edit_type', '', PARAM_TEXT);
$type = optional_param('type', '', PARAM_TEXT);
$all_day = optional_param('all_day', false, PARAM_BOOL);
$ampm = optional_param('ampm', null, PARAM_TEXT);
$rep_day = optional_param_array('rep_day', array(), PARAM_RAW);
$rooms = optional_param_array('rooms', array(), PARAM_INT);
$doublebook = optional_param('doublebook', 0, PARAM_INT);
$roomchange = optional_param('roomchange', false, PARAM_BOOL);

define('MRBS_ERR_DOUBLEBOOK', 1);
define('MRBS_ERR_TOOMANY', 2);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

if (!$area) {
    $area = get_default_area();
}

$PAGE->set_url(new moodle_url('/blocks/mrbs/web/edit_entry_handler.php'));
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    print_error('invalidrequest');
}

if (!getAuthorised(1)) {
    showAccessDenied($day, $month, $year, $area);
    exit;
}

$context = context_system::instance();

$roomadmin = false;
$editunconfirmed = has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false);
if (!getWritable($create_by, getUserName())) {
    if ($editunconfirmed) {
        foreach ($rooms as $key => $room) {
            $adminemail = $DB->get_field('block_mrbs_room', 'room_admin_email', array('id' => $room));
            if ($adminemail == $USER->email) {
                $roomadmin = true;
            } else {
                unset($rooms[$key]);
            }
        }
        $rooms = array_values($rooms);
    }

    if (!$roomadmin) {
        showAccessDenied($day, $month, $year, $area);
        exit;
    }
}

// Make sure that confirmed bookings can't be made by non-room admins.
if (authGetUserLevel(getUserName()) < 2 && $editunconfirmed) {
    foreach ($rooms as $room) {
        $adminemail = $DB->get_field('block_mrbs_room', 'room_admin_email', array('id' => $room));
        if ($adminemail != $USER->email) {
            $type = 'U';
            break;
        }
    }
}

require_sesskey();

if (empty($rooms)) {
    print_header_mrbs($day, $month, $year, $area);
    echo '<h1>' . get_string('invalid_booking', 'block_mrbs') . '</h1>';
    echo get_string('valid_room', 'block_mrbs');
    echo $OUTPUT->footer();
    exit;
}

$name = trim($name);
if ($name === '') {
    print_header_mrbs($day, $month, $year, $area);
    echo '<h1>' . get_string('invalid_booking', 'block_mrbs') . '</h1>';
    echo get_string('must_set_name', 'block_mrbs');
    echo $OUTPUT->footer();
    exit;
}

$description = trim($description);
if ($description === '') {
    print_header_mrbs($day, $month, $year, $area);
    echo '<h1>' . get_string('invalid_booking', 'block_mrbs') . '</h1>';
    echo get_string('must_set_description', 'block_mrbs');
    echo $OUTPUT->footer();
    exit;
}

if (!check_max_advance_days($day, $month, $year)) {
    print_header_mrbs($day, $month, $year, $area);
    echo '<h1>' . get_string('invalid_booking', 'block_mrbs') . '</h1>';
    echo get_string('toofaradvance', 'block_mrbs', $max_advance_days);
    echo $OUTPUT->footer();
    exit;
}

$roomdetails = $DB->get_records_list('block_mrbs_room', 'id', $rooms);
foreach ($roomdetails as $room) {
    if (!allowed_to_book($USER, $room)) {
        print_header_mrbs($day, $month, $year, $area);
        echo '<h1>' . get_string('invalid_booking', 'block_mrbs') . '</h1>';
        echo get_string('notallowedbook', 'block_mrbs', $max_advance_days);
        echo $OUTPUT->footer();
        exit;
    }
}

// Support locales where ',' is used as the decimal point.
$durationparts = explode(':', $durationraw, 2);
if ($dur_units == 'hours' && count($durationparts) == 2) {
    $duration = (float)intval($durationparts[0]) + ((float)intval($durationparts[1]) / 60.0);
} else {
    $duration = unformat_float($durationraw);
}

if ($enable_periods) {
    $resolution = 60;
    $hour = 12;
    $minute = $period;
    $max_periods = count($periods);
    if ($dur_units == "periods" && ($minute + $duration) > $max_periods) {
        $duration = (24 * 60 * floor($duration / $max_periods)) + ($duration % $max_periods);
    }
    if ($dur_units == "days" && $minute == 0) {
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
        if (!is_null($ampm) && ($ampm == "pm") && ($hour < 12)) {
            $hour += 12;
        }
        if (!is_null($ampm) && ($ampm == "am") && ($hour > 11)) {
            $hour -= 12;
        }
    }

    $starttime = mktime($hour, $minute, 0, $month, $day, $year);
    $endtime = mktime($hour, $minute, 0, $month, $day, $year) + ($units * $duration);

    $diff = $endtime - $starttime;
    if (($tmp = $diff % $resolution) != 0 || $diff == 0) {
        $endtime += $resolution - $tmp;
    }

    $endtime += cross_dst($starttime, $endtime);
}

if (isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year)) {
    $rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
} else {
    $rep_type = 0;
}

if (!is_array($rep_day)) {
    $rep_day = array();
}

// For weekly repeat(2), build string of weekdays to repeat on:
$rep_opt = "";
if (($rep_type == 2) || ($rep_type == 6)) {
    for ($i = 0; $i < 7; $i++) {
        $rep_opt .= empty($rep_day[$i]) ? "0" : "1";
    }
}

// Expand a series into a list of start times:
if ($rep_type != 0) {
    $reps = mrbsGetRepeatEntryList(
        $starttime,
        isset($rep_enddate) ? $rep_enddate : 0,
        $rep_type,
        $rep_opt,
        $max_rep_entrys,
        $rep_num_weeks
    );
}

// When checking for overlaps, for Edit (not New), ignore this entry and series:
$repeat_id = 0;
if ($id > 0) {
    $ignore_id = $id;
    $repeat_id = $DB->get_field('block_mrbs_entry', 'repeat_id', array('id' => $id));
    if ($repeat_id < 0) {
        $repeat_id = 0;
    }
} else {
    $ignore_id = 0;
}

$err = "";
$errtype = 0;
$forcemoveoutput = '';

foreach ($rooms as $room_id) {
    if ($rep_type != 0 && !empty($reps)) {
        if (count($reps) < $max_rep_entrys) {
            for ($i = 0; $i < count($reps); $i++) {
                $diff = $endtime - $starttime;
                $diff += cross_dst($reps[$i], $reps[$i] + $diff);
                $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
                if (!empty($tmp)) {
                    $err .= $tmp;
                    $errtype = MRBS_ERR_DOUBLEBOOK;
                }
            }
        } else {
            $err .= get_string('too_may_entrys', 'block_mrbs') . "<p>";
            $errtype = MRBS_ERR_TOOMANY;
            $hide_title = 1;
        }
    } else {
        if (has_capability("block/mrbs:forcebook", $context) && $forcebook) {
            require_once "force_book.php";
            $forcemoveoutput .= mrbsForceMove($room_id, $starttime, $endtime, $name, $id);
            $tmp = '';
        } else if ($doublebook && has_capability('block/mrbs:doublebook', $context)) {
            $sql = 'SELECT entry.id AS entryid,
                           entry.name as entryname,
                           entry.create_by,
                           room.room_name,
                           entry.start_time
                      FROM {block_mrbs_entry} as entry
                      JOIN {block_mrbs_room} as room ON entry.room_id = room.id
                     WHERE room.id = ?
                       AND ((entry.start_time >= ? AND entry.end_time < ?)
                         OR (entry.start_time < ? AND entry.end_time > ?)
                         OR (entry.start_time < ? AND entry.end_time >= ?))';

            $clashingbookings = $DB->get_records_sql($sql, array(
                $room_id, $starttime, $endtime, $starttime, $starttime, $endtime, $endtime
            ));

            foreach ($clashingbookings as $clashingbooking) {
                $oldbookinguser = $DB->get_record('user', array('username' => $clashingbooking->create_by));
                $langvars = new stdClass();
                $langvars->user = $USER->firstname . ' ' . $USER->lastname;
                $langvars->room = $clashingbooking->room_name;
                $langvars->time = to_hr_time($clashingbooking->start_time);
                $langvars->date = userdate($clashingbooking->start_time, '%A %d/%m/%Y');
                $langvars->oldbooking = $clashingbooking->entryname;
                $langvars->newbooking = $name;
                $langvars->admin = $mrbs_admin . ' (' . $mrbs_admin_email . ')';

                if ($oldbookinguser) {
                    if (!email_to_user(
                        $oldbookinguser,
                        $USER,
                        get_string('doublebookesubject', 'block_mrbs'),
                        get_string('doublebookebody', 'block_mrbs', $langvars)
                    )) {
                        $adminuser = $DB->get_record('user', array('email' => $mrbs_admin_email));
                        if ($adminuser) {
                            email_to_user(
                                $adminuser,
                                $USER,
                                get_string('doublebookefailsubject', 'block_mrbs'),
                                get_string('doublebookefailbody', 'block_mrbs', $oldbookinguser->username) .
                                get_string('doublebookebody', 'block_mrbs', $langvars)
                            );
                        }
                    }
                }
            }
        } else {
            $err .= mrbsCheckFree($room_id, $starttime, $endtime - 1, $ignore_id, 0);
        }
    }
}

if (empty($err)) {
    foreach ($rooms as $room_id) {
        if ($edit_type == "series") {
            $rep_details = mrbsCreateRepeatingEntrys(
                $starttime,
                $endtime,
                $rep_type,
                $rep_enddate,
                $rep_opt,
                $room_id,
                $create_by,
                $name,
                $type,
                $description,
                isset($rep_num_weeks) ? $rep_num_weeks : 0,
                $roomchange,
                $id
            );
            $new_id = $rep_details->id;

            $enddate = null;
            if ($rep_details->created && $rep_details->created < $rep_details->requested) {
                $forcemoveoutput .= get_string('notallcreated', 'block_mrbs', $rep_details);
                $enddate = $rep_details->lasttime;
            }

            $sql = "SELECT r.id, r.room_name, r.area_id, a.area_name
                      FROM {block_mrbs_room} r, {block_mrbs_area} a
                     WHERE r.id = ? AND r.area_id = a.id";
            $dbroom = $DB->get_record_sql($sql, array($room_id), MUST_EXIST);
            $room_name = $dbroom->room_name;
            $area_name = $dbroom->area_name;

            $params = array(
                'objectid' => $new_id,
                'other' => array('name' => $name, 'room' => $room_name),
            );
            $event = \block_mrbs\event\booking_created::create($params);
            $event->trigger();

            if (MAIL_ADMIN_ON_BOOKINGS || MAIL_AREA_ADMIN_ON_BOOKINGS || MAIL_ROOM_ADMIN_ON_BOOKINGS || MAIL_BOOKER) {
                if (((($id > 0) && MAIL_ADMIN_ALL) || ($id == 0)) && (0 != $new_id)) {
                    if ($id > 0) {
                        $mail_previous = getPreviousEntryData($id, $rep_details->repeating);
                    }
                    $result = notifyAdminOnBooking(($id == 0), $new_id, $enddate);
                }
            }
        } else {
            if ($repeat_id > 0) {
                $entry_type = 2;
            } else {
                $entry_type = 0;
            }

            $new_id = mrbsCreateSingleEntry(
                $starttime,
                $endtime,
                $entry_type,
                $repeat_id,
                $room_id,
                $create_by,
                $name,
                $type,
                $description,
                $id,
                $roomchange
            );

            $sql = "SELECT r.id, r.room_name, r.area_id, a.area_name
                      FROM {block_mrbs_room} r, {block_mrbs_area} a
                     WHERE r.id = ? AND r.area_id = a.id";
            $dbroom = $DB->get_record_sql($sql, array($room_id), MUST_EXIST);
            $room_name = $dbroom->room_name;
            $area_name = $dbroom->area_name;

            $params = array(
                'objectid' => $new_id,
                'other' => array('name' => $name, 'room' => $room_name),
            );
            $event = \block_mrbs\event\booking_updated::create($params);
            $event->trigger();

            if (MAIL_ADMIN_ON_BOOKINGS || MAIL_AREA_ADMIN_ON_BOOKINGS || MAIL_ROOM_ADMIN_ON_BOOKINGS || MAIL_BOOKER) {
                if (((($id > 0) && MAIL_ADMIN_ALL) || ($id == 0)) && (0 != $new_id)) {
                    if ($id > 0) {
                        $mail_previous = getPreviousEntryData($id, 0);
                    }
                    $result = notifyAdminOnBooking(($id == 0), $new_id);
                }
            }
        }
    }

    $area = mrbsGetRoomArea($room_id);

    $dayurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'year' => $year,
        'month' => $month,
        'day' => $day,
        'area' => $area
    ));
    redirect($dayurl, $forcemoveoutput, 20);
    exit;
}

// The room was not free.
if (strlen($err)) {
    print_header_mrbs($day, $month, $year, $area);

    echo "<h2>" . get_string('sched_conflict', 'block_mrbs') . "</h2>";
    if (!isset($hide_title)) {
        echo get_string('conflict', 'block_mrbs');
        echo "<ul>";
    }

    echo $err;
    if (has_capability('block/mrbs:doublebook', $context) && $errtype == MRBS_ERR_DOUBLEBOOK) {
        $thisurl = new moodle_url('/blocks/mrbs/web/edit_entry_handler.php');
        echo '<form method="post" action="' . $thisurl . '">';
        echo '<input type="hidden" name="name" value="' . s($name) . '" />';
        echo '<input type="hidden" name="description" value="' . s($description) . '" />';
        echo '<input type="hidden" name="day" value="' . (int)$day . '" />';
        echo '<input type="hidden" name="month" value="' . (int)$month . '" />';
        echo '<input type="hidden" name="year" value="' . (int)$year . '" />';
        echo '<input type="hidden" name="area" value="' . (int)$area . '" />';
        echo '<input type="hidden" name="create_by" value="' . s($create_by) . '" />';
        echo '<input type="hidden" name="id" value="' . (int)$id . '" />';
        echo '<input type="hidden" name="rep_type" value="' . (int)$rep_type . '" />';
        echo '<input type="hidden" name="rep_end_month" value="' . (int)$rep_end_month . '" />';
        echo '<input type="hidden" name="rep_end_day" value="' . (int)$rep_end_day . '" />';
        echo '<input type="hidden" name="rep_end_year" value="' . (int)$rep_end_year . '" />';
        echo '<input type="hidden" name="rep_num_weeks" value="' . (int)$rep_num_weeks . '" />';
        foreach ($rep_day as $repdayvalue) {
            echo '<input type="hidden" name="rep_day[]" value="' . s($repdayvalue) . '" />';
        }
        echo '<input type="hidden" name="rep_opt" value="' . s($rep_opt) . '" />';
        echo '<input type="hidden" name="rep_enddate" value="' . (int)$rep_enddate . '" />';
        echo '<input type="hidden" name="hour" value="' . (int)$hour . '" />';
        echo '<input type="hidden" name="minute" value="' . (int)$minute . '" />';
        echo '<input type="hidden" name="period" value="' . (int)$period . '" />';
        echo '<input type="hidden" name="duration" value="' . s($durationraw) . '" />';
        echo '<input type="hidden" name="dur_units" value="' . s($dur_units) . '" />';
        echo '<input type="hidden" name="type" value="' . s($type) . '" />';
        foreach ($rooms as $roomvalue) {
            echo '<input type="hidden" name="rooms[]" value="' . (int)$roomvalue . '" />';
        }
        echo '<input type="hidden" name="doublebook" value="1" />';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo '<input type="submit" name="submit" value="' . get_string('idontcare', 'block_mrbs') . '" />';
        echo '</form>';
    }

    if (!isset($hide_title)) {
        echo "</ul>";
    }
}

$returl = new moodle_url('/blocks/mrbs/web/index.php');
echo '<a href="' . $returl . '">' . get_string('returncal', 'block_mrbs') . '</a><p>';

include "trailer.php";