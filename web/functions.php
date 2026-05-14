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
require_once(__DIR__ . '/mrbs_auth.php');

// Probably a bad place to put this, but for error reporting purposes
// $pview must be defined. If it's not then there are errors generated all
// over the place, so we test to see if it is set, and if not then set it.
$pview = optional_param('pview', 0, PARAM_INT);

function print_user_header_mrbs($day = null, $month = null, $year = null, $area = null) {
    print_header_mrbs($day, $month, $year, $area, true);
}

function print_header_mrbs($day = null, $month = null, $year = null, $area = null, $userview = false) {
    global $search_str, $locale_warning, $pview;
    global $OUTPUT, $PAGE, $USER;
    global $javascript_cursor;

    $strmrbs = get_string('blockname', 'block_mrbs');

    $context = context_system::instance();
    require_capability('block/mrbs:viewmrbs', $context);

    // If we dont know the right date then make it up.
    if (!$day) {
        $day = (int)date("d");
    }
    if (!$month) {
        $month = (int)date("m");
    }
    if (!$year) {
        $year = (int)date("Y");
    }
    if (empty($search_str)) {
        $search_str = "";
    }

    // Print the header.
    $PAGE->set_context($context);
    $PAGE->navbar->add($strmrbs);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_title($strmrbs);
    $PAGE->set_heading(format_string($strmrbs));

    // Load extra javascript.
    $PAGE->requires->js('/blocks/mrbs/web/roomsearch.js', true);
    if (!empty($javascript_cursor)) {
        $PAGE->requires->js('/blocks/mrbs/web/xbLib.js', true);
    }

    echo $OUTPUT->header();

    // Set the weekday names for the 'ChangeOptionDays' function.
    echo '<script type="text/javascript">SetWeekDayNames(';
    echo '"' . get_string('mon', 'calendar') . '", ';
    echo '"' . get_string('tue', 'calendar') . '", ';
    echo '"' . get_string('wed', 'calendar') . '", ';
    echo '"' . get_string('thu', 'calendar') . '", ';
    echo '"' . get_string('fri', 'calendar') . '", ';
    echo '"' . get_string('sat', 'calendar') . '", ';
    echo '"' . get_string('sun', 'calendar') . '"';
    echo ');</script>';

    echo '<div id="mrbscontainer">';

    if ((int)$pview !== 1) {
        if (!empty($locale_warning)) {
            echo '<div class="alert alert-warning">[Warning: ' . s($locale_warning) . ']</div>';
        }

        $titlestr = get_string('mrbs', 'block_mrbs');
        $homeurl = new moodle_url('/blocks/mrbs/web/index.php');

        $gotostr = get_string('goto', 'block_mrbs');
        $gotourl = new moodle_url('/blocks/mrbs/web/day.php');
        if ($userview) {
            $gotourl = new moodle_url('/blocks/mrbs/web/userweek.php');
        }

        $roomsearchstr = get_string('roomsearch', 'block_mrbs');
        $roomsearchurl = new moodle_url('/blocks/mrbs/web/roomsearch.php');

        $helpstr = get_string('help');
        $helpurl = new moodle_url('/blocks/mrbs/web/help.php', array(
            'day' => $day,
            'month' => $month,
            'year' => $year
        ));

        $adminstr = get_string('admin');
        $adminurl = new moodle_url('/blocks/mrbs/web/admin.php', array(
            'day' => $day,
            'month' => $month,
            'year' => $year
        ));

        $reportstr = get_string('report');
        $reporturl = new moodle_url('/blocks/mrbs/web/report.php');

        $searchstr = get_string('search');
        $searchurl = new moodle_url('/blocks/mrbs/web/search.php');
        $searchadvurl = new moodle_url($searchurl, array('advanced' => 1));

        $level = authGetUserLevel($USER->id);
        $canadmin = ($level >= 2);

        echo '<table width="100%" class="banner">';
        echo '<tr>';
        echo '<td bgcolor="#5B69A6">';
        echo '<table width="100%" border="0">';
        echo '<tr>';

        echo '<td class="banner" bgcolor="#C0E0FF">';
        echo '<span style="font-size:1.25rem;">';
        echo '<a href="' . $homeurl . '">' . s($titlestr) . '</a>';
        echo '</span>';
        echo '</td>';

        echo '<td class="banner" bgcolor="#C0E0FF">';
        echo '<form action="' . $gotourl . '" method="get" name="Form1">';
        echo '<span style="font-size:0.875rem;">';

        genDateSelector("", $day, $month, $year);

        if (!empty($area)) {
            echo '<input type="hidden" name="area" value="' . (int)$area . '">' . "\n";
        }

        echo '<script type="text/javascript">';
        echo 'ChangeOptionDays(document.Form1, "");';
        echo '</script>';

        echo '<input type="submit" value="' . s($gotostr) . '">';
        echo '</span>';
        echo '</form>';
        echo '</td>';

        if (!$userview) {
            if (has_capability('block/mrbs:forcebook', $context)) {
                $forceurl = new moodle_url('/blocks/mrbs/web/edit_entry.php', array('force' => 'TRUE'));
                echo '<td class="banner" bgcolor="#C0E0FF" align="center">';
                echo '<a href="' . $forceurl . '">' . get_string('forciblybook', 'block_mrbs') . '</a>';
                echo '</td>';
            }

            echo '<td class="banner" bgcolor="#C0E0FF" align="center">';
            echo '<a target="popup" title="' . s($roomsearchstr) . '" href="' . $roomsearchurl . '" ';
            echo 'onclick="this.target=\'popup\'; return openpopup(\'' . $roomsearchurl . '\', \'popup\', \'toolbar=1,location=0,scrollbars,resizable,width=500,height=400\', 0);">';
            echo s($roomsearchstr) . '</a></td>';
        }

        echo '<td class="banner" bgcolor="#C0E0FF" align="center"><a href="' . $helpurl . '">' . s($helpstr) . '</a></td>';

        if (!$userview) {
            if ($canadmin) {
                echo '<td class="banner" bgcolor="#C0E0FF" align="center"><a href="' . $adminurl . '">' . s($adminstr) . '</a></td>';
            }

            echo '<td class="banner" bgcolor="#C0E0FF" align="center"><a href="' . $reporturl . '">' . s($reportstr) . '</a></td>';

            echo '<td class="banner" bgcolor="#C0E0FF" align="center">';
            echo '<form method="get" action="' . $searchurl . '">';
            echo '<span style="font-size:0.875rem;"><a href="' . $searchadvurl . '">' . s($searchstr) . '</a></span> ';
            echo '<input type="text" name="search_str" value="' . s($search_str) . '" size="10">';
            echo '<input type="hidden" name="day" value="' . (int)$day . '">';
            echo '<input type="hidden" name="month" value="' . (int)$month . '">';
            echo '<input type="hidden" name="year" value="' . (int)$year . '">';
            if (!empty($area)) {
                echo '<input type="hidden" name="area" value="' . (int)$area . '">' . "\n";
            }
            echo '</form>';
            echo '</td>';
        }

        echo '</tr></table></td></tr></table>';
    }
}

function toTimeString(&$dur, &$units) {
    if ($dur >= 60) {
        $dur /= 60;

        if ($dur >= 60) {
            $dur /= 60;

            if (($dur >= 24) && ($dur % 24 == 0)) {
                $dur /= 24;

                if (($dur >= 7) && ($dur % 7 == 0)) {
                    $dur /= 7;

                    if (($dur >= 52) && ($dur % 52 == 0)) {
                        $dur /= 52;
                        $units = get_string('years');
                    } else {
                        $units = get_string('weeks', 'block_mrbs');
                    }
                } else {
                    $units = get_string('days');
                }
            } else {
                $units = get_string('hours', 'block_mrbs');
            }
        } else {
            $units = get_string('minutes');
        }
    } else {
        $units = get_string('secs');
    }
}

function toPeriodString($start_period, &$dur, &$units) {
    global $periods;

    $max_periods = count($periods);
    $dur /= 60;

    if ($dur >= $max_periods || $start_period == 0) {
        if ($start_period == 0 && $dur == $max_periods) {
            $units = get_string('days');
            $dur = 1;
            return;
        }

        $dur /= 60;
        if (($dur >= 24) && is_int($dur)) {
            $dur /= 24;
            $units = get_string('days');
            return;
        } else {
            $dur *= 60;
            $dur = ($dur % $max_periods) + floor($dur / (24 * 60)) * $max_periods;
            $units = get_string('periods', 'block_mrbs');
            return;
        }
    } else {
        $units = get_string('periods', 'block_mrbs');
    }
}

function genDateSelector($prefix, $day, $month, $year, $updatefreerooms = false, $roomsearch = false) {
    if ($day == 0) {
        $day = (int)date("d");
    }
    if ($month == 0) {
        $month = (int)date("m");
    }
    if ($year == 0) {
        $year = (int)date("Y");
    }

    echo '<select name="' . $prefix . 'day" ';
    if ($updatefreerooms) {
        echo 'onchange="updateFreeRooms()"';
    }
    if ($roomsearch) {
        echo 'onchange="RoomSearch()"';
    }
    echo '>';

    for ($i = 1; $i <= 31; $i++) {
        echo '<option' . ($i == $day ? ' selected' : '') . '>' . $i . '</option>';
    }

    echo '</select>';

    echo '<select name="' . $prefix . 'month" onchange="ChangeOptionDays(this.form,\'' . $prefix . '\'';
    if ($updatefreerooms) {
        echo ',true';
    }
    if ($roomsearch) {
        echo ',false,true';
    }
    echo ')">';

    for ($i = 1; $i <= 12; $i++) {
        $m = userdate(mktime(0, 0, 0, $i, 1, $year) + date('Z', mktime(0, 0, 0, $i, 1, $year)), '%b', '0');
        echo '<option value="' . $i . '"' . ($i == $month ? ' selected' : '') . '>' . s($m) . '</option>';
    }

    echo '</select>';

    echo '<select name="' . $prefix . 'year" onchange="ChangeOptionDays(this.form,\'' . $prefix . '\'';
    if ($updatefreerooms) {
        echo ',true';
    }
    if ($roomsearch) {
        echo ',false,true';
    }
    echo ')">';

    $min = min($year, (int)date("Y")) - 5;
    $max = max($year, (int)date("Y")) + 5;

    for ($i = $min; $i <= $max; $i++) {
        echo '<option value="' . $i . '"' . ($i == $year ? ' selected' : '') . '>' . $i . '</option>';
    }

    echo '</select>';
}

// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// If $need_header!=0 output the top of the page too, else assume the
// caller did that. Always outputs the bottom of the page and exits.
function fatal_error($need_header, $message) {
    if ($need_header) {
        print_header_mrbs(0, 0, 0, 0);
    }
    echo $message;
    include "trailer.php";
    exit;
}

// Return a default area; used if no area is already known. This returns the
// lowest area ID in the database (no guarantee there is an area 1).
function get_default_area() {
    global $DB;

    $area = $DB->get_records('block_mrbs_area', null, 'area_name', 'id', 0, 1);
    if (empty($area)) {
        return 0;
    }
    $area = reset($area);
    return (int)$area->id;
}

// Return a default room given a valid area; used if no room is already known.
// This returns the first room in alphabetic order in the database.
function get_default_room($area) {
    global $DB;

    $room = $DB->get_records('block_mrbs_room', array('area_id' => $area), 'room_name', 'id', 0, 1);
    if (empty($room)) {
        return 0;
    }
    $room = reset($room);
    return (int)$room->id;
}

// Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber) {
    return userdate(mktime(0, 0, 0, 1, 2 + $daynumber, 2000), "%A");
}

function hour_min_format() {
    global $twentyfourhour_format;
    if ($twentyfourhour_format) {
        return "%H:%M";
    } else {
        return "%I:%M%p";
    }
}

function period_date_string($t, $mod_time = 0) {
    global $periods;

    $time = getdate($t);
    $p_num = $time["minutes"] + $mod_time;
    if ($p_num < 0) {
        $p_num = 0;
    }
    if ($p_num >= count($periods) - 1) {
        $p_num = count($periods) - 1;
    }

    return array($p_num, $periods[$p_num] . userdate($t, ", %A %d %B %Y"));
}

function period_time_string($t, $mod_time = 0) {
    global $periods;

    $time = getdate($t);
    $p_num = $time["minutes"] + $mod_time;
    if ($p_num < 0) {
        $p_num = 0;
    }
    if ($p_num >= count($periods) - 1) {
        $p_num = count($periods) - 1;
    }
    return $periods[$p_num];
}

function time_date_string($t) {
    global $twentyfourhour_format;

    if ($twentyfourhour_format) {
        return userdate($t, "%H:%M:%S - %A %d %B %Y");
    } else {
        return userdate($t, "%I:%M:%S%p - %A %d %B %Y");
    }
}

// Output a start table cell tag <td> with color class and fallback color.
function tdcell($colclass) {
    static $ecolors;
    if (!isset($ecolors)) {
        $ecolors = array(
            "A" => "#FFCCFF", "B" => "#99CCCC",
            "C" => "#FF9999", "D" => "#FFFF99", "E" => "#C0E0FF", "F" => "#FFCC99",
            "G" => "#FF6666", "H" => "#66FFFF", "I" => "#DDFFDD", "J" => "#CCCCCC",
            "red" => "#FFF0F0", "white" => "#FFFFFF"
        );
    }

    if (isset($ecolors[$colclass])) {
        echo '<td class="' . s($colclass) . '" bgcolor="' . $ecolors[$colclass] . '">';
    } else {
        echo '<td class="' . s($colclass) . '">';
    }
}

// Display the entry-type color key. This has up to 2 rows, up to 5 columns.
function show_colour_key() {
    global $typel;

    echo "<table border=\"0\"><tr>\n";
    $nct = 0;
    for ($ct = "A"; $ct <= "Z"; $ct++) {
        if (!empty($typel[$ct])) {
            if (++$nct > 5) {
                $nct = 1;
                echo "</tr><tr>";
            }
            tdcell($ct);
            echo s($typel[$ct]) . "</td>\n";
        }
    }
    echo "</tr></table>\n";
}

// Round time down to the nearest resolution.
function round_t_down($t, $resolution, $am7) {
    return (int)$t - (int)abs(((int)$t - (int)$am7) % $resolution);
}

// Round time up to the nearest resolution.
function round_t_up($t, $resolution, $am7) {
    if ((($t - $am7) % $resolution) != 0) {
        return $t + $resolution - abs(((int)$t - (int)$am7) % $resolution);
    } else {
        return $t;
    }
}

// Generates some html that can be used to select which area should be displayed.
function make_area_select_html($link, $current, $year, $month, $day) {
    global $DB;

    $out_html = '
<form name="areaChangeForm" method="get" action="' . s($link) . '">
  <select name="area" onchange="document.areaChangeForm.submit()">';

    $areas = $DB->get_records('block_mrbs_area', null, 'area_name');
    foreach ($areas as $area) {
        $selected = ($area->id == $current) ? ' selected' : '';
        $out_html .= '
    <option value="' . (int)$area->id . '"' . $selected . '>' . s($area->area_name) . '</option>';
    }

    $out_html .= '
  </select>

  <input type="hidden" name="day" value="' . (int)$day . '">
  <input type="hidden" name="month" value="' . (int)$month . '">
  <input type="hidden" name="year" value="' . (int)$year . '">
  <noscript><input type="submit" value="' . s(get_string('savechanges')) . '"></noscript>
</form>' . "\n";

    return $out_html;
}

function make_room_select_html($link, $area, $current, $year, $month, $day) {
    global $DB;

    $out_html = '
<form name="roomChangeForm" method="get" action="' . s($link) . '">
  <select name="room" onchange="document.roomChangeForm.submit()">';

    $rooms = $DB->get_records('block_mrbs_room', array('area_id' => $area), 'room_name');
    foreach ($rooms as $room) {
        $selected = ($room->id == $current) ? ' selected' : '';
        $out_html .= '
    <option value="' . (int)$room->id . '"' . $selected . '>' . s($room->room_name) . '</option>';
    }

    $out_html .= '
  </select>
  <input type="hidden" name="day" value="' . (int)$day . '">
  <input type="hidden" name="month" value="' . (int)$month . '">
  <input type="hidden" name="year" value="' . (int)$year . '">
  <input type="hidden" name="area" value="' . (int)$area . '">
  <noscript><input type="submit" value="' . s(get_string('savechanges')) . '"></noscript>
</form>' . "\n";

    return $out_html;
}

// This will return the appropriate value for isdst for mktime().
function is_dst($month, $day, $year, $hour = -1) {
    if ($hour != -1 && $hour > 3) {
        return -1;
    }

    if (!date("I", mktime(12, 0, 0, $month, $day - 1, $year)) &&
        date("I", mktime(12, 0, 0, $month, $day, $year))) {
        return 0;
    } elseif (date("I", mktime(12, 0, 0, $month, $day - 1, $year)) &&
        !date("I", mktime(12, 0, 0, $month, $day, $year))) {
        return 1;
    } else {
        return -1;
    }
}

// If crossing dst determine if you need to make a modification
// of 3600 seconds (1 hour) in either direction.
function cross_dst($start, $end) {
    if (!date("I", $start) && date("I", $end)) {
        $modification = -3600;
    } elseif (date("I", $start) && !date("I", $end)) {
        $modification = 3600;
    } else {
        $modification = 0;
    }

    return $modification;
}

/**
 * Convert already utf-8 encoded strings to charset defined for mails in c.i.php.
 *
 * @param string $string string to convert
 * @return string
 */
function removeMailUnicode($string) {
    global $unicode_encoding;

    if ($unicode_encoding) {
        return iconv("utf-8", get_string('charset', 'block_mrbs'), $string);
    } else {
        return $string;
    }
}

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param int $t
 * @param int $mod_time
 * @return array
 */
function getMailPeriodDateString($t, $mod_time = 0) {
    global $periods;

    $time = getdate($t);
    $p_num = $time['minutes'] + $mod_time;
    if ($p_num < 0) {
        $p_num = 0;
    }
    if ($p_num >= count($periods) - 1) {
        $p_num = count($periods) - 1;
    }

    return array($p_num, $periods[$p_num] . userdate($t, ", %A %d %B %Y"));
}

// }}}
// {{{ getMailTimeDateString()

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param int $t timestamp to format
 * @param bool $inc_time include time in return string
 * @return string formatted string
 */
function getMailTimeDateString($t, $inc_time = true) {
    global $twentyfourhour_format;

    $ampm = date("a", $t);
    if ($inc_time) {
        if ($twentyfourhour_format) {
            return userdate($t, "%H:%M:%S - %A %d %B %Y");
        } else {
            return userdate($t, "%I:%M:%S$ampm - %A %d %B %Y");
        }
    } else {
        return userdate($t, "%A %d %B %Y");
    }
}

// }}}
// {{{ notifyAdminOnBooking()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param bool $new_entry to know if this is a new entry or not
 * @param int $new_id used for create a link to the new entry
 * @param int|null $modified_enddate if set, represents the actual end date of the repeat booking
 * @return bool TRUE if success, false otherwise
 */
function notifyAdminOnBooking($new_entry, $new_id, $modified_enddate = null) {
    global $DB;
    global $url_base, $returl, $name, $description, $area_name;
    global $room_name, $starttime, $duration, $dur_units, $end_date, $endtime;
    global $rep_enddate, $typel, $type, $create_by, $rep_type, $enable_periods;
    global $rep_opt, $rep_num_weeks;
    global $mail_previous, $auth, $weekstarts;

    $recipientlist = array();

    $id_table = ($rep_type > 0) ? "rep" : "e";

    if (MAIL_ADMIN_ON_BOOKINGS && !empty(MAIL_RECIPIENTS)) {
        $recipientlist[] = MAIL_RECIPIENTS;
    }

    if (MAIL_AREA_ADMIN_ON_BOOKINGS) {
        if ($new_entry) {
            $sql = "SELECT a.area_admin_email
                      FROM {block_mrbs_room} r,
                           {block_mrbs_area} a,
                           {block_mrbs_entry} e";
            if ($id_table == 'rep') {
                $sql .= ", {block_mrbs_repeat} rep";
            }
            $sql .= " WHERE {$id_table}.id = ?
                        AND r.id = {$id_table}.room_id
                        AND a.id = r.area_id";
            $emails = $DB->get_records_sql($sql, array($new_id), 0, 1);

            if (!empty($emails)) {
                $email = reset($emails);
                if (!empty($email->area_admin_email)) {
                    $recipientlist[] = $email->area_admin_email;
                }
            }
        } else {
            if (!empty($mail_previous['area_admin_email'])) {
                $recipientlist[] = $mail_previous['area_admin_email'];
            }
        }
    }

    if (MAIL_ROOM_ADMIN_ON_BOOKINGS) {
        if ($new_entry) {
            $sql = "SELECT r.room_admin_email
                      FROM {block_mrbs_room} r,
                           {block_mrbs_entry} e";
            if ($id_table == 'rep') {
                $sql .= ", {block_mrbs_repeat} rep";
            }
            $sql .= " WHERE {$id_table}.id = ?
                        AND r.id = {$id_table}.room_id";
            $emails = $DB->get_records_sql($sql, array($new_id), 0, 1);

            if (!empty($emails)) {
                $email = reset($emails);
                if (!empty($email->room_admin_email)) {
                    $recipientlist[] = $email->room_admin_email;
                }
            }
        } else {
            if (!empty($mail_previous['room_admin_email'])) {
                $recipientlist[] = $mail_previous['room_admin_email'];
            }
        }
    }

    if (MAIL_BOOKER) {
        if ('moodle' == $auth['type']) {
            $uname = ($new_entry) ? $create_by : ($mail_previous['createdby'] ?? '');
            if ($uname !== '') {
                $email = $DB->get_field('user', 'email', array('username' => $uname));
                if (!empty($email)) {
                    $recipientlist[] = $email;
                }
            }
        } else {
            if ($new_entry) {
                if (!empty($create_by)) {
                    $recipientlist[] = str_replace(MAIL_USERNAME_SUFFIX, '', $create_by) . MAIL_DOMAIN;
                }
            } else {
                if (!empty($mail_previous['createdby'])) {
                    $recipientlist[] = str_replace(MAIL_USERNAME_SUFFIX, '', $mail_previous['createdby']) . MAIL_DOMAIN;
                }
            }
        }
    }

    if (empty($recipientlist)) {
        return false;
    }

    $subjdetails = new stdClass();
    if ($enable_periods) {
        list($startperiodstr, $startdatestr) = getMailPeriodDateString($starttime);
        $subjdetails->date = $startdatestr;
    } else {
        $subjdetails->date = getMailTimeDateString($starttime);
    }
    $subjdetails->user = $create_by;
    $subjdetails->room = $room_name;
    $subjdetails->entry_type = $typel[$type] ?? $type;

    if ($new_entry) {
        $subject = get_string('mail_subject_newentry', 'block_mrbs', $subjdetails);
    } else {
        $subject = get_string('mail_subject_entry', 'block_mrbs', $subjdetails);
    }
    $subject = str_replace('&nbsp;', ' ', $subject);

    if ($new_entry) {
        $body = get_string('mail_body_new_entry', 'block_mrbs') . ": \n\n";
    } else {
        $body = get_string('mail_body_changed_entry', 'block_mrbs') . ": \n\n";
    }

    if (!empty($url_base)) {
        $body .= $url_base . "/view_entry.php?id=" . $new_id;
    } else {
        $url = '';
        if (!empty($returl)) {
            $parts = explode(basename($returl), $returl);
            $url = $parts[0] ?? '';
        }
        $body .= $url . "view_entry.php?id=" . $new_id;
    }

    if ($rep_type > 0) {
        $body .= "&series=1";
    }
    $body .= "\n";

    if (MAIL_DETAILS) {
        $body .= "\n" . get_string('namebooker', 'block_mrbs') . ": ";
        $body .= compareEntries(
            removeMailUnicode($name),
            removeMailUnicode($mail_previous['namebooker'] ?? ''),
            $new_entry
        ) . "\n";

        $body .= get_string('description') . ": ";
        $body .= compareEntries(
            removeMailUnicode($description),
            removeMailUnicode($mail_previous['description'] ?? ''),
            $new_entry
        ) . "\n";

        $body .= get_string('room', 'block_mrbs') . ": ";
        $body .= compareEntries(
            removeMailUnicode($area_name),
            removeMailUnicode($mail_previous['area_name'] ?? ''),
            $new_entry
        );
        $body .= " - " . compareEntries(
            removeMailUnicode($room_name),
            removeMailUnicode($mail_previous['room_name'] ?? ''),
            $new_entry
        ) . "\n";

        if ($enable_periods) {
            list($start_period, $start_date) = getMailPeriodDateString($starttime);
            $body .= get_string('start_date', 'block_mrbs') . ": ";
            $body .= compareEntries(
                unHtmlEntities($start_date),
                unHtmlEntities($mail_previous['start_date'] ?? ''),
                $new_entry
            ) . "\n";
        } else {
            $start_date = getMailTimeDateString($starttime);
            $body .= get_string('start_date', 'block_mrbs') . ": " .
                compareEntries($start_date, $mail_previous['start_date'] ?? '', $new_entry) . "\n";
        }

        $body .= get_string('duration', 'block_mrbs') . ": " .
            compareEntries($duration, $mail_previous['duration'] ?? '', $new_entry);
        $body .= " " . compareEntries($dur_units, $mail_previous['dur_units'] ?? '', $new_entry) . "\n";

        if ($enable_periods) {
            $myendtime = $endtime;
            $mod_time = -1;
            list($end_period, $end_date) = getMailPeriodDateString($myendtime, $mod_time);
            $body .= get_string('end_date', 'block_mrbs') . ": ";
            $body .= compareEntries(
                unHtmlEntities($end_date),
                unHtmlEntities($mail_previous['end_date'] ?? ''),
                $new_entry
            ) . "\n";
        } else {
            $myendtime = $endtime;
            $end_date = getMailTimeDateString($myendtime);
            $body .= get_string('end_date', 'block_mrbs') . ": " .
                compareEntries($end_date, $mail_previous['end_date'] ?? '', $new_entry) . "\n";
        }

        $body .= get_string('type', 'block_mrbs') . ": ";
        if ($new_entry) {
            $body .= $typel[$type] ?? $type;
        } else {
            $temp = $mail_previous['type'] ?? '';
            $body .= compareEntries($typel[$type] ?? $type, $typel[$temp] ?? $temp, $new_entry);
        }

        $body .= "\n" . get_string('createdby', 'block_mrbs') . ": " .
            compareEntries($create_by, $mail_previous['createdby'] ?? '', $new_entry) . "\n";

        $body .= get_string('lastmodified') . ": " .
            compareEntries(getMailTimeDateString(time()), $mail_previous['updated'] ?? '', $new_entry);

        $body .= "\n" . get_string('rep_type', 'block_mrbs');
        if ($new_entry) {
            $body .= ": " . get_string('rep_type_' . $rep_type, 'block_mrbs');
        } else {
            $temp = $mail_previous['rep_type'] ?? 0;
            $body .= ": " . compareEntries(
                get_string('rep_type_' . $rep_type, 'block_mrbs'),
                get_string('rep_type_' . $temp, 'block_mrbs'),
                $new_entry
            );
        }

        if ($rep_type > 0) {
            $opt = "";
            if (($rep_type == 2) || ($rep_type == 6)) {
                for ($i = 0; $i < 7; $i++) {
                    $daynum = ($i + $weekstarts) % 7;
                    if (!empty($rep_opt[$daynum])) {
                        $opt .= day_name($daynum) . " ";
                    }
                }
            }

            if ($rep_type == 6) {
                $body .= "\n" . get_string('rep_num_weeks', 'block_mrbs');
                $body .= ": " . compareEntries($rep_num_weeks, $mail_previous['rep_num_weeks'] ?? '', $new_entry);
            }

            if (!empty($opt) || !empty($mail_previous['rep_opt'])) {
                $body .= "\n" . get_string('rep_rep_day', 'block_mrbs');
                $body .= ": " . compareEntries($opt, $mail_previous['rep_opt'] ?? '', $new_entry);
            }

            $body .= "\n" . get_string('rep_end_date', 'block_mrbs');
            if ($new_entry) {
                if ($modified_enddate != null) {
                    $body .= ": " . userdate($modified_enddate, '%A %d %B %Y');
                } else {
                    $body .= ": " . userdate($rep_enddate, '%A %d %B %Y');
                }
            } else {
                if ($modified_enddate != null) {
                    $temp = userdate($modified_enddate, '%A %d %B %Y');
                } else {
                    $temp = userdate($rep_enddate, '%A %d %B %Y');
                }
                $body .= ": " .
                    compareEntries($temp, $mail_previous['rep_end_date'] ?? '', $new_entry) . "\n";
            }
        }
        $body .= "\n";
    }

    $recipientlist = array_unique(array_filter($recipientlist));

    $result = true;
    $fromuser = get_user_by_email(MAIL_FROM);
    if (!$fromuser) {
        return false;
    }

    foreach ($recipientlist as $recip) {
        $recipuser = get_user_by_email($recip);
        if ($recipuser && $result) {
            $result = email_to_user($recipuser, $fromuser, $subject, $body);
            if (!$result) {
                notice(get_string('email_failed', 'block_mrbs'));
            }
        } else {
            if (!$recipuser) {
                $result = false;
                notice(get_string('no_user_with_email', 'block_mrbs', $recip));
            } else {
                notice(get_string('no_user_with_email', 'block_mrbs', MAIL_FROM));
            }
        }
    }

    return $result;
}

// }}}
// {{{ notifyAdminOnDelete()

/**
 * Send email to administrator to notify a deleted entry.
 *
 * @param array $mail_previous contains deleted entry data for email body
 * @return bool TRUE if success, false otherwise
 */
function notifyAdminOnDelete($mail_previous) {
    global $typel, $enable_periods, $DB;

    $recipientlist = array();

    if (MAIL_ADMIN_ON_BOOKINGS && !empty(MAIL_RECIPIENTS)) {
        $recipientlist[] = MAIL_RECIPIENTS;
    }
    if (MAIL_AREA_ADMIN_ON_BOOKINGS && !empty($mail_previous['area_admin_email'])) {
        $recipientlist[] = $mail_previous['area_admin_email'];
    }
    if (MAIL_ROOM_ADMIN_ON_BOOKINGS && !empty($mail_previous['room_admin_email'])) {
        $recipientlist[] = $mail_previous['room_admin_email'];
    }
    if (MAIL_BOOKER && !empty($mail_previous['createdby'])) {
        $uname = $mail_previous['createdby'];
        $email = $DB->get_field('user', 'email', array('username' => $uname));
        if ($email) {
            $recipientlist[] = $email;
        }
    }

    if (empty($recipientlist)) {
        return false;
    }

    $subjdetails = new stdClass();
    $subjdetails->date = unHtmlEntities($mail_previous['start_date'] ?? '');
    $subjdetails->user = $mail_previous['createdby'] ?? '';
    $subjdetails->room = $mail_previous['room_name'] ?? '';
    $subjdetails->entry_type = $typel[$mail_previous['type']] ?? ($mail_previous['type'] ?? '');

    $subject = get_string('mail_subject_delete', 'block_mrbs', $subjdetails);
    $subject = str_replace('&nbsp;', ' ', $subject);

    $body = get_string('mail_body_del_entry', 'block_mrbs') . ": \n\n";
    $body .= "\n" . get_string('namebooker', 'block_mrbs') . ': ';
    $body .= removeMailUnicode($mail_previous['namebooker'] ?? '') . "\n";
    $body .= get_string('description') . ": ";
    $body .= removeMailUnicode($mail_previous['description'] ?? '') . "\n";
    $body .= get_string('room', 'block_mrbs') . ": ";
    $body .= removeMailUnicode($mail_previous['area_name'] ?? '');
    $body .= " - " . removeMailUnicode($mail_previous['room_name'] ?? '') . "\n";
    $body .= get_string('start_date', 'block_mrbs') . ': ';

    if ($enable_periods) {
        $body .= unHtmlEntities($mail_previous['start_date'] ?? '') . "\n";
    } else {
        $body .= ($mail_previous['start_date'] ?? '') . "\n";
    }

    $body .= get_string('duration', 'block_mrbs') . ': ' . ($mail_previous['duration'] ?? '') . ' ';
    $body .= ($mail_previous['dur_units'] ?? '') . "\n";

    if ($enable_periods) {
        $body .= get_string('end_date', 'block_mrbs') . ": ";
        $body .= unHtmlEntities($mail_previous['end_date'] ?? '') . "\n";
    } else {
        $body .= get_string('end_date', 'block_mrbs') . ": " . ($mail_previous['end_date'] ?? '');
        $body .= "\n";
    }

    $body .= get_string('type', 'block_mrbs') . ": ";
    $body .= empty($typel[$mail_previous['type'] ?? '']) ?
        "?" . ($mail_previous['type'] ?? '') . "?" :
        $typel[$mail_previous['type']];

    $body .= "\n" . get_string('createdby', 'block_mrbs') . ": ";
    $body .= ($mail_previous['createdby'] ?? '') . "\n";
    $body .= get_string('lastmodified') . ": " . ($mail_previous['updated'] ?? '');
    $body .= "\n" . get_string('rep_type', 'block_mrbs');

    $temp = $mail_previous['rep_type'] ?? 0;
    $body .= ": " . get_string('rep_type_' . $temp, 'block_mrbs');

    if (($mail_previous['rep_type'] ?? 0) > 0) {
        if (($mail_previous['rep_type'] ?? 0) == 6) {
            $body .= "\n" . get_string('rep_num_weeks', 'block_mrbs');
            $body .= ": " . ($mail_previous["rep_num_weeks"] ?? '');
        }

        if (!empty($mail_previous["rep_opt"])) {
            $body .= "\n" . get_string('rep_rep_day', 'block_mrbs');
            $body .= " " . $mail_previous["rep_opt"];
        }

        $body .= "\n" . get_string('rep_end_date', 'block_mrbs');
        $body .= " " . ($mail_previous['rep_end_date'] ?? '') . "\n";
    }
    $body .= "\n";

    $recipientlist = array_unique(array_filter($recipientlist));

    $result = true;
    $fromuser = get_user_by_email(MAIL_FROM);
    if (!$fromuser) {
        return false;
    }

    foreach ($recipientlist as $recip) {
        $recipuser = get_user_by_email($recip);
        if ($recipuser && $result) {
            $result = email_to_user($recipuser, $fromuser, $subject, $body);
            if (!$result) {
                notice(get_string('error_send_email', 'block_mrbs', $recip));
            }
        } else {
            if (!$recipuser) {
                $result = false;
                notice(get_string('no_user_with_email', 'block_mrbs', $recip));
            } else {
                notice(get_string('no_user_with_email', 'block_mrbs', MAIL_FROM));
            }
        }
    }

    return $result;
}

// }}}
// {{{ getPreviousEntryData()

/**
 * Gather all fields values for an entry. Used for emails to get previous entry state.
 *
 * @param int $id entry id to get data
 * @param int $series 1 if this is a series or 0
 * @return array
 */
function getPreviousEntryData($id, $series) {
    global $DB, $enable_periods, $weekstarts;

    $sql = "
    SELECT  e.name,
            e.description,
            e.create_by,
            r.room_name,
            a.area_name,
            e.type,
            e.room_id,
            e.repeat_id,
            e.timestamp,
            (e.end_time - e.start_time) AS tbl_e_duration,
            e.start_time AS tbl_e_start_time,
            e.end_time AS tbl_e_end_time,
            a.area_admin_email,
            r.room_admin_email";

    if (1 == $series) {
        $sql .= ", re.rep_type, re.rep_opt, re.rep_num_weeks,
            (re.end_time - re.start_time) AS tbl_r_duration,
            re.start_time AS tbl_r_start_time,
            re.end_time AS tbl_r_end_time,
            re.end_date AS tbl_r_end_date";
    }

    $sql .= "
    FROM {block_mrbs_entry} e, {block_mrbs_room} r, {block_mrbs_area} a ";
    if (1 == $series) {
        $sql .= ", {block_mrbs_repeat} re ";
    }
    $sql .= "
    WHERE e.room_id = r.id
      AND r.area_id = a.id
      AND e.id = ? ";
    if (1 == $series) {
        $sql .= " AND e.repeat_id = re.id";
    }

    $details = $DB->get_record_sql($sql, array($id), MUST_EXIST);

    $mail_previous = array();
    $mail_previous['namebooker'] = $details->name;
    $mail_previous['description'] = $details->description;
    $mail_previous['createdby'] = $details->create_by;
    $mail_previous['room_name'] = $details->room_name;
    $mail_previous['area_name'] = $details->area_name;
    $mail_previous['type'] = $details->type;
    $mail_previous['room_id'] = $details->room_id;
    $mail_previous['repeat_id'] = $details->repeat_id;
    $mail_previous['updated'] = getMailTimeDateString($details->timestamp);
    $mail_previous['area_admin_email'] = $details->area_admin_email;
    $mail_previous['room_admin_email'] = $details->room_admin_email;

    if ($enable_periods) {
        if (1 != $series) {
            list($mail_previous['start_period'], $mail_previous['start_date']) =
                getMailPeriodDateString($details->tbl_e_start_time);
            list($mail_previous['end_period'], $mail_previous['end_date']) =
                getMailPeriodDateString($details->tbl_e_end_time, -1);
            $mail_previous['duration'] = $details->tbl_e_duration -
                cross_dst($details->tbl_e_start_time, $details->tbl_e_end_time);
        } else {
            list($mail_previous['start_period'], $mail_previous['start_date']) =
                getMailPeriodDateString($details->tbl_r_start_time);
            list($mail_previous['end_period'], $mail_previous['end_date']) =
                getMailPeriodDateString($details->tbl_r_end_time, 0);

            $mail_previous['rep_end_date'] =
                getMailTimeDateString($details->tbl_r_end_date, false);

            $mail_previous['duration'] = $details->tbl_r_duration -
                cross_dst($details->tbl_r_start_time, $details->tbl_r_end_time);

            $mail_previous['rep_opt'] = "";
            switch ($details->rep_type) {
                case 2:
                case 6:
                    $rep_day = array(
                        $details->rep_opt[0] != "0",
                        $details->rep_opt[1] != "0",
                        $details->rep_opt[2] != "0",
                        $details->rep_opt[3] != "0",
                        $details->rep_opt[4] != "0",
                        $details->rep_opt[5] != "0",
                        $details->rep_opt[6] != "0"
                    );

                    if ($details->rep_type == 6) {
                        $mail_previous['rep_num_weeks'] = $details->rep_num_weeks;
                    } else {
                        $mail_previous['rep_num_weeks'] = "";
                    }
                    break;

                default:
                    $rep_day = array(0, 0, 0, 0, 0, 0, 0);
            }

            for ($i = 0; $i < 7; $i++) {
                $wday = ($i + $weekstarts) % 7;
                if (!empty($rep_day[$wday])) {
                    $mail_previous['rep_opt'] .= day_name($wday) . " ";
                }
            }

            $mail_previous['rep_num_weeks'] = $details->rep_num_weeks;
        }

        toPeriodString(
            $mail_previous['start_period'],
            $mail_previous['duration'],
            $mail_previous['dur_units']
        );
    } else {
        if (1 != $series) {
            $mail_previous['start_date'] = getMailTimeDateString($details->tbl_e_start_time);
            $mail_previous['end_date'] = getMailTimeDateString($details->tbl_e_end_time);
            $mail_previous['duration'] = $details->tbl_e_duration -
                cross_dst($details->tbl_e_start_time, $details->tbl_e_end_time);
        } else {
            $mail_previous['start_date'] = getMailTimeDateString($details->tbl_r_start_time);
            $mail_previous['end_date'] = getMailTimeDateString($details->tbl_r_end_time);
            $mail_previous['rep_end_date'] = getMailTimeDateString($details->tbl_r_end_date, false);
            $mail_previous['duration'] = $details->tbl_r_duration -
                cross_dst($details->tbl_r_start_time, $details->tbl_r_end_time);

            $mail_previous['rep_opt'] = "";
            switch ($details->rep_type) {
                case 2:
                case 6:
                    $rep_day = array(
                        $details->rep_opt[0] != "0",
                        $details->rep_opt[1] != "0",
                        $details->rep_opt[2] != "0",
                        $details->rep_opt[3] != "0",
                        $details->rep_opt[4] != "0",
                        $details->rep_opt[5] != "0",
                        $details->rep_opt[6] != "0"
                    );

                    if ($details->rep_type == 6) {
                        $mail_previous['rep_num_weeks'] = $details->rep_num_weeks;
                    } else {
                        $mail_previous['rep_num_weeks'] = "";
                    }
                    break;

                default:
                    $rep_day = array(0, 0, 0, 0, 0, 0, 0);
            }

            for ($i = 0; $i < 7; $i++) {
                $wday = ($i + $weekstarts) % 7;
                if (!empty($rep_day[$wday])) {
                    $mail_previous['rep_opt'] .= day_name($wday) . " ";
                }
            }

            $mail_previous['rep_num_weeks'] = $details->rep_num_weeks;
        }

        toTimeString($mail_previous['duration'], $mail_previous['dur_units']);
    }

    if (1 == $series) {
        $mail_previous['rep_type'] = $details->rep_type;
    } else {
        $mail_previous['rep_type'] = 0;
    }

    return $mail_previous;
}

// }}}
// {{{ compareEntries()

/**
 * Compare entries fields to show in emails.
 *
 * @param string $new_value new field value
 * @param string $previous_value previous field value
 * @param bool $new_entry is new entry or not
 * @return string
 */
function compareEntries($new_value, $previous_value, $new_entry) {
    if ($new_entry) {
        return $new_value;
    }
    if ($new_value != $previous_value) {
        return $new_value . " (" . $previous_value . ")";
    }
    return $new_value;
}

function unHtmlEntities($string) {
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

function get_user_by_email($email) {
    global $DB;

    if (empty($email)) {
        return false;
    }

    $user = $DB->get_record('user', array('email' => $email, 'deleted' => 0));
    if ($user) {
        return $user;
    }

    return false;
}

// }}}

/**
 * Convert a unix time to a human readable time. Gives period output if periods are enabled.
 *
 * @param int $time Unix timestamp
 * @return string Name of the period or time in Hours:Minutes format
 */
function to_hr_time($time) {
    $cfg_mrbs = get_config('block/mrbs');
    if (!empty($cfg_mrbs->enable_periods)) {
        $periods = preg_split("/\r\n|\n|\r/", (string)$cfg_mrbs->periods);
        $period = (int)date('i', $time);
        return trim($periods[$period] ?? '');
    } else {
        return date('G:i', $time);
    }
}

function check_max_advance_days_internal(DateTime $checkdate) {
    global $max_advance_days;

    if ($max_advance_days < 0) {
        return true;
    }

    $syscontext = context_system::instance();
    if (has_capability('block/mrbs:ignoremaxadvancedays', $syscontext)) {
        return true;
    }

    $now = new DateTime();
    if ($checkdate < $now) {
        return true;
    }

    $interval = (int)$checkdate->format('U') - (int)$now->format('U');
    $interval = (int)($interval / (24 * 60 * 60));
    if ($interval > $max_advance_days) {
        return false;
    }

    return true;
}

function check_max_advance_days_timestamp($ts) {
    $tsdate = new DateTime();
    $tsdate->setTimestamp($ts);
    $checkdate = new DateTime();
    $checkdate->setDate((int)$tsdate->format('Y'), (int)$tsdate->format('m'), (int)$tsdate->format('d'));
    return check_max_advance_days_internal($checkdate);
}

function check_max_advance_days($day, $month, $year) {
    $checkdate = new DateTime();
    $checkdate->setDate((int)$year, (int)$month, (int)$day);
    return check_max_advance_days_internal($checkdate);
}

function allowed_to_book($user, $room) {
    if (empty($room->booking_users)) {
        return true;
    }

    $booking_users = explode(',', $room->booking_users);
    foreach ($booking_users as $email) {
        if ($user->email == trim($email)) {
            return true;
        }
    }

    return false;
}