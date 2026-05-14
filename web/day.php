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

require_once(__DIR__ . "/config.inc.php");
require_once(__DIR__ . "/functions.php");
require_once(__DIR__ . "/mrbs_auth.php");
require_once(__DIR__ . "/mincals.php");

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$morningstarts_minutes = optional_param('morningstarts_minutes', 0, PARAM_INT);
$debug_flag = optional_param('debug_flag', 0, PARAM_INT);
$timetohighlight = optional_param('timetohighlight', -1, PARAM_INT);
$roomnotfound = optional_param('roomnotfound', null, PARAM_TEXT);
$pview = optional_param('pview', 0, PARAM_INT);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
} else {
    while (!checkdate((int)$month, (int)$day, (int)$year)) {
        $day--;
    }
}

$format = "Gi";
if (!empty($enable_periods)) {
    $format = "i";
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
}

$baseurl = new moodle_url('/blocks/mrbs/web/day.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
));
$thisurl = clone $baseurl;

if ($area > 0) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($morningstarts_minutes > 0) {
    $thisurl->param('morningstarts_minutes', $morningstarts_minutes);
}
if ($timetohighlight >= 0) {
    $thisurl->param('timetohighlight', $timetohighlight);
}
if ($pview == 1) {
    $thisurl->param('pview', $pview);
}

$PAGE->set_url($thisurl);
require_login();

// Print the page header.
print_header_mrbs($day, $month, $year, $area);

// Define the start and end of each day in a way which is not affected by daylight saving.
$dst_change = is_dst($month, $day, $year);
$am7 = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day, $year);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);

if ($pview != 1) {
    echo '<table width="100%"><tr><td width="40%">';

    echo '<u>' . get_string('areas', 'block_mrbs') . '</u><br>';

    if ($area_list_format == "select") {
        echo make_area_select_html(new moodle_url('/blocks/mrbs/web/day.php'), $area, $year, $month, $day);
    } else {
        $areas = $DB->get_records('block_mrbs_area', null, 'area_name');
        foreach ($areas as $dbarea) {
            $arealink = $baseurl->out(true, array('area' => $dbarea->id));
            echo '<a href="' . $arealink . '">';
            if ($dbarea->id == $area) {
                echo '<span style="color:red;">' . s($dbarea->area_name) . "</span></a><br>\n";
            } else {
                echo s($dbarea->area_name) . "</a><br>\n";
            }
        }
    }
    echo "</td>\n";

    $gotoroom = new moodle_url('/blocks/mrbs/web/gotoroom.php');
    $gostr = get_string('goroom', 'block_mrbs');
    $gotoval = '';
    $gotomsg = '';
    if (!empty($roomnotfound)) {
        $gotoval = s($roomnotfound);
        $gotomsg = ' ' . get_string('noroomsfound', 'block_mrbs');
    }

    echo '<td width="20%"><h3>' . get_string('findroom', 'block_mrbs') . "</h3>
        <form action=\"" . $gotoroom . "\" method=\"get\">
            <input type=\"text\" name=\"room\" value=\"" . $gotoval . "\">
            <input type=\"hidden\" name=\"day\" value=\"" . $day . "\">
            <input type=\"hidden\" name=\"month\" value=\"" . $month . "\">
            <input type=\"hidden\" name=\"year\" value=\"" . $year . "\">
            <input type=\"submit\" value=\"" . s($gostr) . "\">" . $gotomsg . '
        </form></td>';

    minicals($year, $month, $day, $area, '', 'day');
    echo "</tr></table>";
}

// y? are year, month and day of yesterday.
// t? are year, month and day of tomorrow.
$i = mktime(12, 0, 0, $month, $day - 1, $year);
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);

$i = mktime(12, 0, 0, $month, $day + 1, $year);
$ty = date("Y", $i);
$tm = date("m", $i);
$td = date("d", $i);

// Don't continue if there are no areas.
if ($area <= 0) {
    echo "<h1>" . get_string('noareas', 'block_mrbs') . "</h1>";
    echo "</table>\n";
    if (isset($output)) {
        echo $output;
    }
    show_colour_key();
    require_once(__DIR__ . "/trailer.php");
    exit;
}

if (!empty($area)) {
    $sql = "SELECT e.id AS eid, r.id AS rid, e.start_time, e.end_time, e.name, e.type,
                   e.description
              FROM {block_mrbs_entry} e
              JOIN {block_mrbs_room} r ON e.room_id = r.id
             WHERE r.area_id = ?
               AND e.start_time <= ?
               AND e.end_time > ?";

    $entries = $DB->get_records_sql($sql, array($area, $pm7, $am7));

    $today = array();

    foreach ($entries as $entry) {
        $start_t = max(round_t_down($entry->start_time, $resolution, $am7), $am7);
        $end_t = min(round_t_up($entry->end_time, $resolution, $am7) - $resolution, $pm7);

        for ($t = $start_t; $t <= $end_t; $t += $resolution) {
            $timeslot = date($format, $t);
            if (empty($today[$entry->rid][$timeslot])) {
                $today[$entry->rid][$timeslot] = array(
                    "id" => $entry->eid,
                    "color" => $entry->type,
                    "data" => "",
                    "long_descr" => "",
                    "double_booked" => false
                );
            } else {
                $today[$entry->rid][$timeslot]["id"] .= ',' . $entry->eid;
                $today[$entry->rid][$timeslot]["data"] .= "\n";
                $today[$entry->rid][$timeslot]["long_descr"] .= ",";
                $today[$entry->rid][$timeslot]["double_booked"] = true;
            }
        }

        if ($entry->start_time < $am7) {
            $slot = date($format, $am7);
            $today[$entry->rid][$slot]["data"] .= $entry->name;
            $today[$entry->rid][$slot]["long_descr"] .= $entry->description;
        } else {
            $slot = date($format, $start_t);
            $today[$entry->rid][$slot]["data"] .= $entry->name;
            $today[$entry->rid][$slot]["long_descr"] .= $entry->description;
        }
    }

    if ($debug_flag) {
        echo "<p>DEBUG:<pre>\n";
        echo "\$dst_change = $dst_change\n";
        echo "\$am7 = $am7 or " . date($format, $am7) . "\n";
        echo "\$pm7 = $pm7 or " . date($format, $pm7) . "\n";
        if (is_array($today)) {
            foreach ($today as $w_k => $w_v) {
                foreach ($w_v as $t_k => $t_v) {
                    foreach ($t_v as $k_k => $k_v) {
                        echo "d[$w_k][$t_k][$k_k] = '" . $k_v . "'\n";
                    }
                }
            }
        } else {
            echo "today is not an array!\n";
        }
        echo "</pre><p>\n";
    }

    $rooms = $DB->get_records('block_mrbs_room', array('area_id' => $area), 'room_name');
    foreach ($rooms as $room) {
        $room->allowedtobook = allowed_to_book($USER, $room);
    }

    if (empty($rooms)) {
        echo "<h1>" . get_string('no_rooms_for_area', 'block_mrbs') . "</h1>";
    } else {
        echo '<h2 align="center">' . userdate($am7, "%A %d %B %Y") . "</h2>\n";

        if ($pview != 1) {
            $todayurl = new moodle_url($baseurl, array('area' => $area));
            $todayurl->remove_params('day', 'month', 'year');
            $daybefore = new moodle_url($todayurl, array('year' => $yy, 'month' => $ym, 'day' => $yd));
            $dayafter = new moodle_url($todayurl, array('year' => $ty, 'month' => $tm, 'day' => $td));

            $output = '<table width="100%"><tr><td><a href="' . $daybefore . '">&lt;&lt;'
                . get_string('daybefore', 'block_mrbs') . '</a></td>
            <td align="center"><a href="' . $todayurl . '">'
                . get_string('gototoday', 'block_mrbs') . '</a></td>
            <td align="right"><a href="' . $dayafter . '">'
                . get_string('dayafter', 'block_mrbs') . "&gt;&gt;</a></td></tr></table>\n";
            echo $output;
        }

        if (!empty($javascript_cursor)) {
            echo "<script>\n";
            echo "InitActiveCell("
                . ($show_plus_link ? "true" : "false") . ", "
                . "true, "
                . (!empty($times_right_side) ? "true" : "false") . ", "
                . "\"" . $highlight_method . "\", "
                . "\"" . get_string('click_to_reserve', 'block_mrbs') . "\""
                . ");\n";
            echo "</script>\n";
        }

        echo '<table cellspacing="0" border="1" width="100%">';
        echo '<tr><th width="1%">' . (!empty($enable_periods) ?
                get_string('period', 'block_mrbs') : get_string('time')) . '</th>';

        $room_column_width = (int)(95 / count($rooms));
        $weekurl = new moodle_url('/blocks/mrbs/web/week.php', array(
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'area' => $area
        ));

        foreach ($rooms as $room) {
            $roomweekurl = $weekurl->out(true, array('room' => $room->id));
            $title = get_string('viewweek', 'block_mrbs') . " \n\n" . s($room->description);
            echo '<th width="' . $room_column_width . '%">
            <a href="' . $roomweekurl . '" title="' . $title . '">'
                . s($room->room_name)
                . ($room->capacity > 0 ? '(' . (int)$room->capacity . ')' : '')
                . '<br />' . s($room->description) . "</a></th>";
        }

        if (!empty($times_right_side)) {
            echo '<th width="1%">' . (!empty($enable_periods) ?
                    get_string('period', 'block_mrbs') : get_string('time')) . "</th>";
        }
        echo "</tr>\n";

        $hiliteurl = new moodle_url($baseurl, array('area' => $area));

        $j = ($dst_change != -1) ? 1 : 0;

        $advanceok = check_max_advance_days($day, $month, $year);

        $row_class = "even_row";
        $starttime = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day + $j, $year);
        $endtime = mktime($eveningends, $eveningends_minutes, 0, $month, $day + $j, $year);

        for ($t = $starttime; $t <= $endtime; $t += $resolution) {
            $row_class = ($row_class == 'even_row') ? 'odd_row' : 'even_row';

            $time_t = date($format, $t);
            $hiliteurl->param('timetohighlight', $time_t);

            echo "<tr>";
            tdcell("red");
            if (!empty($enable_periods)) {
                $time_t_stripped = preg_replace("/^0/", "", $time_t);
                echo '<a href="' . $hiliteurl . '" title="'
                    . get_string('highlight_line', 'block_mrbs') . '">'
                    . $periods[$time_t_stripped] . "</a></td>\n";
            } else {
                echo '<a href="' . $hiliteurl . '" title="'
                    . get_string('highlight_line', 'block_mrbs') . '">'
                    . userdate($t, hour_min_format()) . "</a></td>\n";
            }

            foreach ($rooms as $room) {
                if (isset($today[$room->id][$time_t]["id"])) {
                    $id = $today[$room->id][$time_t]["id"];
                    $color = $today[$room->id][$time_t]["color"];
                    $descr = s($today[$room->id][$time_t]["data"]);
                    $long_descr = s($today[$room->id][$time_t]["long_descr"]);
                    $double_booked = $today[$room->id][$time_t]["double_booked"];
                    if ($double_booked) {
                        $color = 'DoubleBooked';
                    }
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

                if (!isset($id)) {
                    $hour = date("H", $t);
                    $minute = date("i", $t);

                    if ($pview != 1) {
                        if (!$room->allowedtobook) {
                            echo '<center>';
                            $title = get_string('notallowedbook', 'block_mrbs');
                            echo '<img src="' . $OUTPUT->image_url('toofaradvance', 'block_mrbs')
                                . '" width="10" height="10" border="0" alt="' . s($title)
                                . '" title="' . s($title) . '" />';
                            echo '</center>';
                        } else if (!$advanceok) {
                            echo '<center>';
                            $title = get_string('toofaradvance', 'block_mrbs', $max_advance_days);
                            echo '<img src="' . $OUTPUT->image_url('toofaradvance', 'block_mrbs')
                                . '" width="10" height="10" border="0" alt="' . s($title)
                                . '" title="' . s($title) . '" />';
                            echo '</center>';
                        } else {
                            if (!empty($javascript_cursor)) {
                                echo "<script>\n";
                                echo "BeginActiveCell();\n";
                                echo "</script>";
                            }
                            echo "<center>";
                            $editurl = new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
                                'room' => $room->id,
                                'area' => $area,
                                'year' => $year,
                                'month' => $month,
                                'day' => $day
                            ));
                            if (!empty($enable_periods)) {
                                echo '<a href="' . $editurl->out(true, array('period' => $time_t_stripped)) . '">';
                            } else {
                                echo '<a href="' . $editurl->out(true, array('hour' => $hour, 'minute' => $minute)) . '">';
                            }
                            echo '<img src="' . $OUTPUT->image_url('new', 'block_mrbs')
                                . '" width="10" height="10" border="0" alt="'
                                . s(get_string('newentry', 'block_mrbs')) . '" title="'
                                . s(get_string('newentry', 'block_mrbs')) . '" /></a>';
                            echo "</center>";
                            if (!empty($javascript_cursor)) {
                                echo "<script>\n";
                                echo "EndActiveCell();\n";
                                echo "</script>";
                            }
                        }
                    } else {
                        echo '&nbsp;';
                    }
                    $descrs = array();
                } else if (!empty($double_booked)) {
                    $descrs = explode("\n", $descr);
                    $long_descrs = explode(",", $long_descr);
                    $ids = explode(",", $id);
                } else {
                    $descrs = array($descr);
                    $long_descrs = array($long_descr);
                    $ids = array($id);
                }

                for ($i2 = 0; $i2 < count($descrs); $i2++) {
                    $viewentry = new moodle_url('/blocks/mrbs/web/view_entry.php', array(
                        'id' => $ids[$i2],
                        'area' => $area,
                        'day' => $day,
                        'month' => $month,
                        'year' => $year
                    ));
                    if ($descrs[$i2] != "") {
                        echo ' <a href="' . $viewentry . '" title="' . $long_descrs[$i2] . '">' . $descrs[$i2] . "</a><br>";
                    } else {
                        echo '<a href="' . $viewentry . '" title="' . $long_descrs[$i2] . '">&nbsp;</a><br>';
                    }
                }

                unset($descrs, $long_descrs, $ids);
                echo "</td>\n";
            }

            if (!empty($times_right_side)) {
                tdcell("red");
                if (!empty($enable_periods)) {
                    $time_t_stripped = preg_replace("/^0/", "", $time_t);
                    echo '<a href="' . $hiliteurl . '" title="'
                        . get_string('highlight_line', 'block_mrbs') . '">'
                        . $periods[$time_t_stripped] . "</a></td>\n";
                } else {
                    echo '<a href="' . $hiliteurl . '" title="'
                        . get_string('highlight_line', 'block_mrbs') . '">'
                        . userdate($t, hour_min_format()) . "</a></td>\n";
                }
            }

            echo "</tr>\n";
        }
    }

    echo "</table>\n";
    if (isset($output)) {
        echo $output;
    }
    show_colour_key();
}

unset($room);
require_once(__DIR__ . "/trailer.php");