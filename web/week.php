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


// mrbs/week.php - Week-at-a-time view

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $DB, $PAGE, $OUTPUT, $USER;

require_once "config.inc.php";
require_once "functions.php";
require_once 'mrbs_auth.php';
require_once "mincals.php";

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$debug_flag = optional_param('debug_flag', 0, PARAM_INT);
$morningstarts_minutes = optional_param('morningstarts_minutes', 0, PARAM_INT);
$pview = optional_param('pview', 0, PARAM_INT);
$timetohighlight = optional_param('timetohighlight', -1, PARAM_INT);

$num_of_days = $cfg_mrbs->weeklength;
if ($num_of_days == 0) {
    $num_of_days = 7; // if user has not configured this, default to 7
}

// If we don't know the right date then use today:
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
} else {
    // Make the date valid if day is more then number of days in month:
    while (!checkdate((int)$month, (int)$day, (int)$year)) {
        $day--;
    }
}

$format = "Gi";
if ($enable_periods) {
    $format = "i";
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
}

// Set the date back to the previous $weekstarts day (Sunday, if 0):
$time = mktime(12, 0, 0, $month, $day, $year);
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0) {
    $time -= $weekday * 86400;
    $day = (int)date("d", $time);
    $month = (int)date("m", $time);
    $year = (int)date("Y", $time);
}

$baseurl = new moodle_url('/blocks/mrbs/web/week.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
)); // Used as the basis for URLs throughout this file.

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
if ($morningstarts_minutes > 0) {
    $thisurl->param('morningstarts_minutes', $morningstarts_minutes);
}

$PAGE->set_url($thisurl);
require_login();

// print the page header
print_header_mrbs($day, $month, $year, $area);

// Define the start and end of each day of the week in a way which is not
// affected by daylight saving...
$dst_change = array();
$am7 = array();
$pm7 = array();
$d = array();

for ($j = 0; $j <= ($num_of_days - 1); $j++) {
    // are we entering or leaving daylight saving
    // dst_change:
    // -1 => no change
    //  0 => entering DST
    //  1 => leaving DST
    $dst_change[$j] = is_dst($month, $day + $j, $year);
    $am7[$j] = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day + $j, $year);
    $pm7[$j] = mktime($eveningends, $eveningends_minutes, 0, $month, $day + $j, $year);
}

$this_area_name = "";
$this_room_name = "";
$this_room_description = "";

if ($pview != 1) {
    // Table with areas, rooms, minicals.
    echo '<table width="100%"><tr>';

    // Show all areas.
    echo '<td width="30%"><u>' . get_string('areas', 'block_mrbs') . '</u><br>';
}

// show either a select box or the normal html list
if ($area_list_format == "select") {
    echo make_area_select_html('week.php', $area, $year, $month, $day); // from functions.php
    $this_area_name = (string)$DB->get_field('block_mrbs_area', 'area_name', array('id' => $area));
    $this_room_name = (string)$DB->get_field('block_mrbs_room', 'room_name', array('id' => $room));
    $this_room_description = (string)$DB->get_field('block_mrbs_room', 'description', array('id' => $room));
} else {
    $areas = $DB->get_records('block_mrbs_area', null, 'area_name');
    foreach ($areas as $dbarea) {
        $areaurl = new moodle_url($baseurl, array('area' => $dbarea->id));
        if ($pview != 1) {
            echo '<a href="' . $areaurl . '">';
        }
        if ($dbarea->id == $area) {
            $this_area_name = s($dbarea->area_name);
            if ($pview != 1) {
                echo '<span style="color:red">' . $this_area_name . "</span></a><br>\n";
            }
        } else if ($pview != 1) {
            echo s($dbarea->area_name) . "</a><br>\n";
        }
    }
}

if ($pview != 1) {
    echo "</td>\n";

    // Show all rooms in the current area.
    echo '<td width="30%"><u>' . get_string('rooms', 'block_mrbs') . '</u><br>';
}

// should we show a drop-down for the room list, or not?
if ($area_list_format == "select") {
    echo make_room_select_html('week.php', $area, $room, $year, $month, $day); // from functions.php
} else {
    $rooms = $DB->get_records('block_mrbs_room', array('area_id' => $area), 'room_name');
    foreach ($rooms as $dbroom) {
        $roomurl = new moodle_url($baseurl, array('area' => $area, 'room' => $dbroom->id));
        if ($pview != 1) {
            echo '<a href="' . $roomurl . '" title="' . s($dbroom->room_name) . '">';
        }
        if ($dbroom->id == $room) {
            $this_room_name = s($dbroom->room_name);
            $this_room_description = s($dbroom->description);
            if ($pview != 1) {
                echo '<span style="color:red">' . $this_room_name . "</span></a><br>\n";
            }
        } else if ($pview != 1) {
            echo s($dbroom->room_name) . "</a><br>\n";
        }
    }
} // end select if

if ($pview != 1) {
    echo "</td>\n";

    // Draw the three month calendars.
    minicals($year, $month, $day, $area, $room, 'week');
    echo "</tr></table>\n";
}

// Don't continue if this area has no rooms:
if ($room <= 0) {
    echo '<h1>' . get_string('no_rooms_for_area', 'block_mrbs') . '</h1>';
    include "trailer.php";
    exit;
}

// Show area and room:
echo '<h2 align="center">' . $this_area_name . ' - ' . $this_room_name . ' (' . $this_room_description . ")</h2>\n";

// y? are year, month and day of the previous week.
// t? are year, month and day of the next week.
$i = mktime(12, 0, 0, $month, $day - 7, $year);
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);

$i = mktime(12, 0, 0, $month, $day + 7, $year);
$ty = date("Y", $i);
$tm = date("m", $i);
$td = date("d", $i);

if ($pview != 1) {
    // Show Go to week before and after links.
    $thisweekurl = new moodle_url($baseurl, array('area' => $area, 'room' => $room));
    $thisweekurl->remove_params('day', 'month', 'year');
    $weekbefore = new moodle_url($thisweekurl, array('year' => $yy, 'month' => $ym, 'day' => $yd));
    $weekafter = new moodle_url($thisweekurl, array('year' => $ty, 'month' => $tm, 'day' => $td));
    echo '<table width="100%"><tr><td>
      <a href="' . $weekbefore . '">
      &lt;&lt; ' . get_string('weekbefore', 'block_mrbs') . '</a></td>
      <td align="center"><a href="' . $thisweekurl . '">' . get_string('gotothisweek', 'block_mrbs') . '</a></td>
      <td align="right"><a href="' . $weekafter . '">
      ' . get_string('weekafter', 'block_mrbs') . '&gt;&gt;</a></td></tr></table>';
}

$roomdata = $DB->get_record('block_mrbs_room', array('id' => $room), '*', MUST_EXIST);
$allowedtobook = allowed_to_book($USER, $roomdata);

// Get all appointments for this week in the room that we care about.
// This data will be retrieved day-by-day.
for ($j = 0; $j <= ($num_of_days - 1); $j++) {
    $sql = 'room_id = ? AND start_time <= ? AND end_time > ?';
    $entries = $DB->get_records_select('block_mrbs_entry', $sql, array($room, $pm7[$j], $am7[$j]));

    foreach ($entries as $entry) {
        if ($debug_flag) {
            echo "<br>DEBUG: result $i, id $entry->id, starts $entry->start_time, ends $entry->end_time\n";
        }

        $start_t = max(round_t_down($entry->start_time, $resolution, $am7[$j]), $am7[$j]);
        $end_t = min(round_t_up($entry->end_time, $resolution, $am7[$j]) - $resolution, $pm7[$j]);

        for ($t = $start_t; $t <= $end_t; $t += $resolution) {
            $slotkey = date($format, $t);
            $d[$j][$slotkey]["id"] = $entry->id;
            $d[$j][$slotkey]["color"] = $entry->type;
            $d[$j][$slotkey]["data"] = "";
            $d[$j][$slotkey]["long_descr"] = "";
        }

        // Show the name of the booker in the first segment that the booking
        // happens in, or at the start of the day if it started before today.
        if ($entry->end_time < $am7[$j]) {
            $d[$j][date($format, $am7[$j])]["data"] = $entry->name;
            $d[$j][date($format, $am7[$j])]["long_descr"] = $entry->description;
        } else {
            $d[$j][date($format, $start_t)]["data"] = $entry->name;
            $d[$j][date($format, $start_t)]["long_descr"] = $entry->description;
        }
    }
}

if ($debug_flag) {
    echo "<p>DEBUG:<pre>\n";
    echo "\$dst_change = ";
    print_r($dst_change);
    print "\n";
    print "\$am7 =\n";
    foreach ($am7 as $am7_val) {
        print "$am7_val - " . date("r", $am7_val) . "\n";
    }
    print "\$pm7 =\n";
    foreach ($pm7 as $pm7_val) {
        print "$pm7_val - " . date("r", $pm7_val) . "\n";
    }

    echo "<p>\$d =\n";
    if (is_array($d)) {
        foreach ($d as $w_k => $w_v) {
            foreach ($w_v as $t_k => $t_v) {
                foreach ($t_v as $k_k => $k_v) {
                    echo "d[$w_k][$t_k][$k_k] = '$k_v'\n";
                }
            }
        }
    } else {
        echo "d is not an array!\n";
    }
    echo "</pre><p>\n";
}

// Include the active cell content management routines.
// Must be included before the beginnning of the main table.
if ($javascript_cursor) {
    echo "<script language=\"JavaScript\">InitActiveCell("
        . ($show_plus_link ? "true" : "false") . ", "
        . "true, "
        . ((false != $times_right_side) ? "true" : "false") . ", "
        . "\"$highlight_method\", "
        . "\"" . get_string('click_to_reserve', 'block_mrbs') . "\""
        . ");</script>\n";
}

// This is where we start displaying stuff.
echo '<table cellspacing="0" border="1" width="100%">';

// The header row contains the weekday names and short dates.
echo '<tr><th width="1%"><br>' . ($enable_periods ? get_string('period', 'block_mrbs') : get_string('time')) . '</th>';
if (empty($dateformat)) {
    $dformat = "%a<br>%b %d";
} else {
    $dformat = "%a<br>%d %b";
}
for ($j = 0; $j <= ($num_of_days - 1); $j++) {
    $t = mktime(12, 0, 0, $month, $day + $j, $year);
    $dayurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'year' => userdate($t, "%Y"),
        'month' => userdate($t, "%m"),
        'day' => userdate($t, "%d"),
        'area' => $area
    ));
    echo '<th width="14%"><a href="' . $dayurl . '" title="' . get_string('viewday', 'block_mrbs') . '">';
    echo userdate($t, $dformat) . "</a></th>\n";
}
// next line to display times on right side
if (false != $times_right_side) {
    echo '<th width="1%"><br>'
        . ($enable_periods ? get_string('period', 'block_mrbs') : get_string('time'))
        . "</th>";
}

echo "</tr>\n";

// URL for highlighting a time. Don't use REQUEST_URI or you will get
// the timetohighlight parameter duplicated each time you click.
$hiliteurl = new moodle_url($baseurl, array('area' => $area, 'room' => $room));

// if the first day of the week to be displayed contains a DST change then
// move to the next day to get the hours in the day.
$j = ($dst_change[0] != -1) ? 1 : 0;

$row_class = "even_row";
$starttime = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day + $j, $year);
$endtime = mktime($eveningends, $eveningends_minutes, 0, $month, $day + $j, $year);

for ($t = $starttime; $t <= $endtime; $t += $resolution) {
    $row_class = ($row_class == "even_row") ? "odd_row" : "even_row";
    $time_t = date($format, $t);
    $hiliteurl->param('timetohighlight', $time_t);

    echo "<tr>";
    tdcell("red");
    if ($enable_periods) {
        $time_t_stripped = preg_replace("/^0/", "", $time_t);
        echo '<a href="' . $hiliteurl . '" title="' . get_string('highlight_line', 'block_mrbs') . '">';
        echo $periods[$time_t_stripped] . "</a></td>";
    } else {
        echo '<a href="' . $hiliteurl . '" title="' . get_string('highlight_line', 'block_mrbs') . '">';
        echo userdate($t, hour_min_format()) . "</a></td>";
    }

    if ($timetohighlight == $time_t) {
        $empty_color = "red";
    } else {
        $empty_color = "white";
    }

    // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
    for ($thisday = 0; $thisday <= ($num_of_days - 1); $thisday++) {
        $wt = mktime(12, 0, 0, $month, $day + $thisday, $year);
        $wday = date("d", $wt);
        $wmonth = date("m", $wt);
        $wyear = date("Y", $wt);

        if (isset($d[$thisday][$time_t]["id"])) {
            $id = $d[$thisday][$time_t]["id"];
            $color = $d[$thisday][$time_t]["color"];
            $descr = s($d[$thisday][$time_t]["data"]);
            $long_descr = s($d[$thisday][$time_t]["long_descr"]);
        } else {
            unset($id);
        }

        if (isset($id)) {
            $c = $color;
        } else if ($time_t == $timetohighlight) {
            $c = "red";
        } else {
            $c = $row_class;
        }

        tdcell($c);

        // If the room isnt booked then allow it to be booked.
        if (!isset($id)) {
            $hour = date("H", $t);
            $minute = date("i", $t);

            if ($pview != 1) {
                if (!$allowedtobook) {
                    echo '<div style="text-align:center;">';
                    $title = get_string('notallowedbook', 'block_mrbs', $max_advance_days);
                    echo '<img src="' . $OUTPUT->pix_url('toofaradvance', 'block_mrbs') . '" width="10" height="10" border="0" alt="' . s($title) . '" title="' . s($title) . '" />';
                    echo '</div>';
                } else if (!check_max_advance_days($wday, $wmonth, $wyear)) {
                    echo '<div style="text-align:center;">';
                    $title = get_string('toofaradvance', 'block_mrbs', $max_advance_days);
                    echo '<img src="' . $OUTPUT->pix_url('toofaradvance', 'block_mrbs') . '" width="10" height="10" border="0" alt="' . s($title) . '" title="' . s($title) . '" />';
                    echo '</div>';
                } else {
                    if ($javascript_cursor) {
                        echo "<script language=\"JavaScript\">\n<!--\n";
                        echo "BeginActiveCell();\n";
                        echo "// -->\n</script>";
                    }
                    echo '<div style="text-align:center;">';
                    $editentry = new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
                        'room' => $room,
                        'area' => $area,
                        'year' => $wyear,
                        'month' => $wmonth,
                        'day' => $wday
                    ));
                    if ($enable_periods) {
                        echo '<a href="' . ($editentry->out(true, array('period' => $time_t_stripped))) . '">';
                    } else {
                        echo '<a href="' . ($editentry->out(true, array('hour' => $hour, 'minute' => $minute))) . '">';
                    }
                    echo '<img src="' . $OUTPUT->pix_url('new', 'block_mrbs') . '" width="10" height="10" border="0" alt="" /></a>';
                    echo "</div>";
                    if ($javascript_cursor) {
                        echo "<script language=\"JavaScript\">\n<!--\n";
                        echo "EndActiveCell();\n";
                        echo "// -->\n</script>";
                    }
                }
            } else {
                echo '&nbsp;';
            }
        } elseif ($descr != "") {
            $viewentry = new moodle_url('/blocks/mrbs/web/view_entry.php', array(
                'id' => $id,
                'area' => $area,
                'day' => $wday,
                'month' => $wmonth,
                'year' => $wyear
            ));
            echo ' <a href="' . $viewentry . '" title="' . s($long_descr) . '">' . $descr . '</a>';
        } else {
            echo "&nbsp;&nbsp;";
        }

        echo "</td>\n";
    }

    // next lines to display times on right side
    if (false != $times_right_side) {
        tdcell("red");
        if ($enable_periods) {
            $time_t_stripped = preg_replace("/^0/", "", $time_t);
            echo '<a href="' . $hiliteurl . '" title="' . get_string('highlight_line', 'block_mrbs') . '">';
            echo $periods[$time_t_stripped] . "</a></td>";
        } else {
            echo '<a href="' . $hiliteurl . '" title="' . get_string('highlight_line', 'block_mrbs') . '">';
            echo userdate($t, hour_min_format()) . "</a></td>";
        }
    }

    echo "</tr>\n";
}
echo "</table>";

show_colour_key();

include "trailer.php";