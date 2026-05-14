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

// mrbs/month.php - Month-at-a-time view.
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php'); //for Moodle integration.

global $PAGE, $OUTPUT, $DB, $USER;

include "config.inc.php";
include "functions.php";
require_once('mrbs_auth.php');
include "mincals.php";

$month = optional_param('month', date("m"), PARAM_INT);
$year = optional_param('year', date("Y"), PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$debug_flag = optional_param('debug_flag', 0, PARAM_INT);
$pview = optional_param('pview', 0, PARAM_INT);

// 3-value compare: Returns result of compare as "< " "= " or "> ".
function cmp3($a, $b) {
    if ($a < $b) {
        return "< ";
    }
    if ($a == $b) {
        return "= ";
    }
    return "> ";
}

if (($month == 0) || ($year == 0) || !checkdate((int)$month, 1, (int)$year)) {
    $month = date("m");
    $year = date("Y");
}
$day = 1;

$baseurl = new moodle_url('/blocks/mrbs/web/month.php', array(
    'month' => $month,
    'year' => $year
)); // Used as basis for URLs throughout this file.
$thisurl = new moodle_url($baseurl);

if ($area > 0) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($room > 0) {
    $thisurl->param('room', $room);
} else {
    $room = get_default_room($area);
    // Note $room will be 0 if there are no rooms; this is checked for below.
}

$PAGE->set_url($thisurl);
require_login();

// Print the page header.
print_header_mrbs($day, $month, $year, $area);

// Month view start time, ignoring morningstarts/eveningends.
$month_start = mktime(0, 0, 0, $month, 1, $year);

// Column where the month starts: 0 means $weekstarts weekday.
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;

$days_in_month = (int)date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);

if ($enable_periods) {
    $resolution = 60;
    $morningstarts = 12;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
}

// Define start and end of each day of the month in a way not affected by DST.
for ($j = 1; $j <= $days_in_month; $j++) {
    $dst_change[$j] = is_dst($month, $j, $year);
    if (empty($enable_periods)) {
        $midnight[$j] = mktime(0, 0, 0, $month, $j, $year);
        $midnight_tonight[$j] = mktime(23, 59, 59, $month, $j, $year);
    } else {
        $midnight[$j] = mktime(12, 0, 0, $month, $j, $year);
        $midnight_tonight[$j] = mktime(12, count($periods), 59, $month, $j, $year);
    }
}

if ($pview != 1) {
    // Table with areas, rooms, minicals.
    echo '<table width="100%"><tr>';
    $this_area_name = "";
    $this_room_name = "";

    // Show all areas.
    echo '<td width="30%"><u>' . get_string('areas', 'block_mrbs') . '</u><br>';
}

// Show either a select box or standard HTML list.
if ($area_list_format == "select") {
    echo make_area_select_html('month.php', $area, $year, $month, $day); // From functions.php.
    $this_area_name = s($DB->get_field('block_mrbs_area', 'area_name', array('id' => $area)));
    $this_room_name = s($DB->get_field('block_mrbs_room', 'room_name', array('id' => $room)));
} else {
    $dbareas = $DB->get_records('block_mrbs_area', null, 'area_name');
    $areaurl = new moodle_url($baseurl);
    foreach ($dbareas as $dbarea) {
        if ($pview != 1) {
            $areaurl->param('area', $dbarea->id);
            echo '<a href="' . $areaurl . '">';
        }
        if ($dbarea->id == $area) {
            $this_area_name = s($dbarea->area_name);
            if ($pview != 1) {
                echo '<font color="red">' . $this_area_name . "</font></a><br>\n";
            }
        } else if ($pview != 1) {
            echo s($dbarea->area_name) . "</a><br>\n";
        }
    }
}

if ($pview != 1) {
    echo "</td>\n";

    // Show all rooms in the current area.
    echo '<td width="30%"><u>' . get_string('rooms', 'block_mrbs') . "</u><br>";
}

// Show rooms list.
if ($area_list_format == "select") {
    echo make_room_select_html('month.php', $area, $room, $year, $month, $day); // From functions.php.
} else {
    $rooms = $DB->get_records('block_mrbs_room', array('area_id' => $area), 'room_name');
    $roomurl = new moodle_url($baseurl, array('area' => $area));
    foreach ($rooms as $dbroom) {
        $roomurl->param('room', $dbroom->id);
        echo '<a href="' . $roomurl . '">';
        if ($dbroom->id == $room) {
            $this_room_name = s($dbroom->room_name);
            if ($pview != 1) {
                echo '<font color="red">' . $this_room_name . "</font></a><br>\n";
            }
        } else if ($pview != 1) {
            echo s($dbroom->room_name) . "</a><br>\n";
        }
    }
}

if ($pview != 1) {
    echo "</td>\n";

    // Draw the three month calendars.
    minicals($year, $month, $day, $area, $room, 'month');
    echo "</tr></table>\n";
}

// Don't continue if this area has no rooms.
if ($room <= 0) {
    echo $OUTPUT->heading(get_string('no_rooms_for_area', 'block_mrbs'));
    include "trailer.php";
    exit;
}

// Show Month, Year, Area, Room header.
echo '<h2 align="center">' . userdate($month_start, "%B %Y")
    . " - $this_area_name - $this_room_name</h2>\n";

// Previous and next month navigation.
$i = mktime(12, 0, 0, $month - 1, 1, $year);
$yy = date("Y", $i);
$ym = date("n", $i);

$i = mktime(12, 0, 0, $month + 1, 1, $year);
$ty = date("Y", $i);
$tm = date("n", $i);

if ($pview != 1) {
    $thismonthurl = new moodle_url($baseurl, array('area' => $area, 'room' => $room));
    $thismonthurl->remove_params('month', 'year');
    $monthbefore = new moodle_url($thismonthurl, array('month' => $ym, 'year' => $yy));
    $monthafter = new moodle_url($thismonthurl, array('month' => $tm, 'year' => $ty));

    echo '<table width="100%"><tr>';
    echo '<td><a href="' . $monthbefore . '">&lt;&lt;' . get_string('monthbefore', 'block_mrbs') . '</a></td>';
    echo '<td align="center"><a href="' . $thismonthurl . '">' . get_string('gotothismonth', 'block_mrbs') . '</a></td>';
    echo '<td align="right"><a href="' . $monthafter . '">' . get_string('monthafter', 'block_mrbs') . '&gt;&gt;</a></td>';
    echo '</tr></table>';
}

if ($debug_flag) {
    echo "<p>DEBUG: month=$month year=$year start=$weekday_start range=$month_start:$month_end\n";
}

// Localized "all day" text with non-breaking spaces.
$all_day = str_replace(" ", "&nbsp;", get_string('all_day', 'block_mrbs'));

// Collect all meetings for this month in the selected room.
for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
    $entries = $DB->get_records_select(
        'block_mrbs_entry',
        'room_id = ? AND start_time <= ? AND end_time > ?',
        array($room, $midnight_tonight[$day_num], $midnight[$day_num]),
        'start_time'
    );

    foreach ($entries as $entry) {
        if ($debug_flag) {
            echo "<br>DEBUG: result id {$entry->id}, starts {$entry->start_time}, ends {$entry->end_time}\n";
            echo "<br>DEBUG: Entry {$entry->id} day $day_num\n";
        }

        $d[$day_num]["id"][] = $entry->id;
        $d[$day_num]["shortdescrip"][] = $entry->name;

        if (empty($enable_periods)) {
            switch (cmp3($entry->start_time, $midnight[$day_num]) . cmp3($entry->end_time, $midnight_tonight[$day_num] + 1)) {
                case "> < ":
                case "= < ":
                    $d[$day_num]["data"][] = userdate($entry->start_time, hour_min_format()) . "~" .
                                             userdate($entry->end_time, hour_min_format());
                    break;
                case "> = ":
                    $d[$day_num]["data"][] = userdate($entry->start_time, hour_min_format()) . "~24:00";
                    break;
                case "> > ":
                    $d[$day_num]["data"][] = userdate($entry->start_time, hour_min_format()) . "~====>";
                    break;
                case "= = ":
                    $d[$day_num]["data"][] = $all_day;
                    break;
                case "= > ":
                    $d[$day_num]["data"][] = $all_day . "====>";
                    break;
                case "< < ":
                    $d[$day_num]["data"][] = "<====~" . userdate($entry->end_time, hour_min_format());
                    break;
                case "< = ":
                    $d[$day_num]["data"][] = "<====" . $all_day;
                    break;
                case "< > ":
                    $d[$day_num]["data"][] = "<====" . $all_day . "====>";
                    break;
            }
        } else {
            $start_str = str_replace("&nbsp;", " ", period_time_string($entry->start_time));
            $end_str = str_replace("&nbsp;", " ", period_time_string($entry->end_time, -1));
            switch (cmp3($entry->start_time, $midnight[$day_num]) . cmp3($entry->end_time, $midnight_tonight[$day_num] + 1)) {
                case "> < ":
                case "= < ":
                    $d[$day_num]["data"][] = $start_str . "~" . $end_str;
                    break;
                case "> = ":
                    $d[$day_num]["data"][] = $start_str . "~24:00";
                    break;
                case "> > ":
                    $d[$day_num]["data"][] = $start_str . "~====>";
                    break;
                case "= = ":
                    $d[$day_num]["data"][] = $all_day;
                    break;
                case "= > ":
                    $d[$day_num]["data"][] = $all_day . "====>";
                    break;
                case "< < ":
                    $d[$day_num]["data"][] = "<====~" . $end_str;
                    break;
                case "< = ":
                    $d[$day_num]["data"][] = "<====" . $all_day;
                    break;
                case "< > ":
                    $d[$day_num]["data"][] = "<====" . $all_day . "====>";
                    break;
            }
        }
    }
}

if ($debug_flag) {
    echo "<p>DEBUG: Array of month day data:<p><pre>\n";
    for ($i = 1; $i <= $days_in_month; $i++) {
        if (isset($d[$i]["id"])) {
            $n = count($d[$i]["id"]);
            echo "Day $i has $n entries:\n";
            for ($j = 0; $j < $n; $j++) {
                echo "  ID: " . $d[$i]["id"][$j] .
                     " Data: " . $d[$i]["data"][$j] . "\n";
            }
        }
    }
    echo "</pre>\n";
}

// Include active cell JS if enabled.
if ($javascript_cursor) {
    echo "<script language=\"JavaScript\">InitActiveCell("
        . ($show_plus_link ? "true" : "false") . ", "
        . "false, "
        . "false, "
        . "\"$highlight_method\", "
        . "\"" . get_string('click_to_reserve', 'block_mrbs') . "\""
        . ");</script>\n";
}

echo "<table border=\"1\" cellspacing=\"0\" width=\"100%\">\n<tr>";
// Weekday name header row.
for ($weekcol = 0; $weekcol < 7; $weekcol++) {
    echo "<th width=\"14%\">" . day_name(($weekcol + $weekstarts) % 7) . "</th>";
}
echo "</tr><tr>\n";

// Skip days before start of month.
for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++) {
    echo "<td bgcolor=\"#cccccc\" height=\"100\">&nbsp;</td>\n";
}

$roomdata = $DB->get_record('block_mrbs_room', array('id' => $room));
$allowedtobook = allowed_to_book($USER, $roomdata);

// Draw the days of the month.
for ($cday = 1; $cday <= $days_in_month; $cday++) {
    if ($weekcol == 0) {
        echo "</tr><tr>\n";
    }
    $dayurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'year' => $year,
        'month' => $month,
        'day' => $cday,
        'area' => $area
    ));
    echo '<td valign="top" height="100" class="month"><div class="monthday"><a href="' .
         $dayurl . '">' . $cday . "</a>&nbsp;\n";
    echo "</div>";

    if (isset($d[$cday]["id"][0])) {
        echo '<font size="-2">';
        $n = count($d[$cday]["id"]);
        for ($i = 0; $i < $n; $i++) {
            if (($i == 11 && $n > 12 && $monthly_view_entries_details != "both") ||
                ($i == 6 && $n > 6 && $monthly_view_entries_details == "both")) {
                echo " ...\n";
                break;
            }
            if (($i > 0 && $i % 2 == 0) ||
                ($monthly_view_entries_details == "both" && $i > 0)) {
                echo "<br>";
            } else {
                echo " ";
            }

            $viewentry_url = new moodle_url('/blocks/mrbs/web/view_entry.php', array(
                'id' => $d[$cday]['id'][$i],
                'day' => $cday,
                'month' => $month,
                'year' => $year
            ));
            switch ($monthly_view_entries_details) {
                case "description":
                    echo '<a href="' . $viewentry_url . '" title="' .
                        s($d[$cday]["data"][$i]) . '">'
                        . s(substr($d[$cday]["shortdescrip"][$i], 0, 17)) . "</a>";
                    break;
                case "slot":
                    echo '<a href="' . $viewentry_url . '" title="' .
                        s(substr($d[$cday]["shortdescrip"][$i], 0, 17)) . '">'
                        . s($d[$cday]["data"][$i]) . "</a>";
                    break;
                case "both":
                    echo '<a href="' . $viewentry_url . '" title="">' .
                        s($d[$cday]["data"][$i]) . " " .
                        s(substr($d[$cday]["shortdescrip"][$i], 0, 6)) . "</a>";
                    break;
                default:
                    echo "error: unknown parameter";
            }
        }
        echo "</font>";
    }

    echo "<br>";
    if ($pview != 1) {
        if (!$allowedtobook) {
            $title = get_string('notallowedbook', 'block_mrbs');
            echo '<img src="' . $OUTPUT->image_url('toofaradvance', 'block_mrbs') .
                 '" width="10" height="10" border="0" alt="' . $title .
                 '" title="' . $title . '" />';
        } else if (!check_max_advance_days($cday, $month, $year)) {
            $title = get_string('toofaradvance', 'block_mrbs', $max_advance_days);
            echo '<img src="' . $OUTPUT->image_url('toofaradvance', 'block_mrbs') .
                 '" width="10" height="10" border="0" alt="' . $title .
                 '" title="' . $title . '" />';
        } else {
            if ($javascript_cursor) {
                echo "<script language=\"JavaScript\">\n<!--\n";
                echo "BeginActiveCell();\n";
                echo "// -->\n</script>";
            }
            $editurl = new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
                'room' => $room,
                'area' => $area,
                'year' => $year,
                'month' => $month,
                'day' => $cday
            ));
            if ($enable_periods) {
                echo '<a href="' . $editurl->out(true, array('period' => 0)) . '">';
            } else {
                echo '<a href="' . $editurl->out(true, array('hour' => $morningstarts, 'minute' => 0)) . '">';
            }
            echo '<img src="' . $OUTPUT->image_url('new', 'block_mrbs') .
                 '" width="10" height="10" border="0" alt="' .
                 get_string('newentry', 'block_mrbs') . '" /></a>';
            if ($javascript_cursor) {
                echo "<script language=\"JavaScript\">\n<!--\n";
                echo "EndActiveCell();\n";
                echo "// -->\n</script>";
            }
        }
    } else {
        echo '&nbsp;';
    }
    echo "</td>\n";
    if (++$weekcol == 7) {
        $weekcol = 0;
    }
}

// Skip remaining days to end of week.
if ($weekcol > 0) {
    for (; $weekcol < 7; $weekcol++) {
        echo '<td bgcolor="#cccccc" height="100">&nbsp;</td>' . "\n";
    }
}
echo "</tr></table>\n";

include "trailer.php";