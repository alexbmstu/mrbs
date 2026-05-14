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

global $PAGE, $DB, $USER;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";

global $twentyfourhour_format, $morningstarts;

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$edit_type = optional_param('edit_type', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$hour = optional_param('hour', 0, PARAM_INT);
$minute = optional_param('minute', 0, PARAM_INT);
$force = optional_param('force', false, PARAM_BOOL);
$period = optional_param('period', 0, PARAM_INT);
$all_day = optional_param('all_day', false, PARAM_BOOL);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
));

if ($area) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($id) {
    $thisurl->param('id', $id);
}
if ($force) {
    $thisurl->param('force', $force);
}
if ($room) {
    $thisurl->param('room', $room);
}
if (!empty($edit_type)) {
    $thisurl->param('edit_type', $edit_type);
}
if ($hour !== 0) {
    $thisurl->param('hour', $hour);
}
if ($minute !== 0) {
    $thisurl->param('minute', $minute);
}
if ($period) {
    $thisurl->param('period', $period);
}
if ($all_day) {
    $thisurl->param('all_day', $all_day);
}

$PAGE->set_url($thisurl);
require_login();

if (!getAuthorised(1)) {
    showAccessDenied($day, $month, $year, $area);
    exit;
}

// This page will either add or modify a booking.

// Firstly we need to know if this is a new booking or modifying an old one.
// If we had $id passed in then it's a modification.
if ($id > 0) {
    $entry = $DB->get_record('block_mrbs_entry', array('id' => $id), '*', MUST_EXIST);

    $name = $entry->name;
    $create_by = $entry->create_by;
    $description = $entry->description;
    $start_time = $entry->start_time;
    $start_day = (int)userdate($entry->start_time, '%d');
    $start_month = (int)userdate($entry->start_time, '%m');
    $start_year = (int)userdate($entry->start_time, '%Y');
    $start_hour = (int)userdate($entry->start_time, '%H');
    $start_min = (int)userdate($entry->start_time, '%M');
    $end_time = $entry->end_time;
    $duration = $entry->end_time - $entry->start_time - cross_dst($entry->start_time, $entry->end_time);
    $type = $entry->type;
    $room_id = $entry->room_id;

    if (!empty($room)) {
        $room_id = $room;
    }

    $entry_type = $entry->entry_type;
    $rep_id = $entry->repeat_id;

    if ($entry_type >= 1) {
        $repeat = $DB->get_record('block_mrbs_repeat', array('id' => $rep_id), '*', MUST_EXIST);
        $rep_type = $repeat->rep_type;

        if ($edit_type == "series") {
            $start_day = (int)userdate($repeat->start_time, '%d');
            $start_month = (int)userdate($repeat->start_time, '%m');
            $start_year = (int)userdate($repeat->start_time, '%Y');

            $rep_end_day = (int)userdate($repeat->end_date, '%d');
            $rep_end_month = (int)userdate($repeat->end_date, '%m');
            $rep_end_year = (int)userdate($repeat->end_date, '%Y');

            switch ($rep_type) {
                case 2:
                case 6:
                    $rep_day[0] = ($repeat->rep_opt[0] != "0");
                    $rep_day[1] = ($repeat->rep_opt[1] != "0");
                    $rep_day[2] = ($repeat->rep_opt[2] != "0");
                    $rep_day[3] = ($repeat->rep_opt[3] != "0");
                    $rep_day[4] = ($repeat->rep_opt[4] != "0");
                    $rep_day[5] = ($repeat->rep_opt[5] != "0");
                    $rep_day[6] = ($repeat->rep_opt[6] != "0");

                    if ($rep_type == 6) {
                        $rep_num_weeks = $repeat->rep_num_weeks;
                    }
                    break;

                default:
                    $rep_day = array(0, 0, 0, 0, 0, 0, 0);
            }
        } else {
            $rep_type = $repeat->rep_type;
            $rep_end_date = userdate($repeat->end_date, '%A %d %B %Y');
            $rep_opt = $repeat->rep_opt;
        }
    }
} else {
    $edit_type = "series";
    $name = getUserName();
    $create_by = getUserName();
    $description = '';
    $start_day = $day;
    $start_month = $month;
    $start_year = $year;
    $start_hour = $hour;
    $start_min = $minute;
    $duration = ($enable_periods ? 60 : 60 * 60);
    $type = "I";
    $room_id = $room;
    $start_time = mktime(12, $period, 0, $start_month, $start_day, $start_year);
    $end_time = $start_time;
    $rep_id = 0;
    $rep_type = 0;
    $rep_end_day = $day;
    $rep_end_month = $month;
    $rep_end_year = $year;
    $rep_day = array(0, 0, 0, 0, 0, 0, 0);
}

if ($room_id == 0) {
    $dbrooms = $DB->get_records('block_mrbs_room', null, 'room_name', 'id', 0, 1);
    if ($dbrooms) {
        $dbroom = reset($dbrooms);
        $room_id = $dbroom->id;
    }
}

if ($start_hour === 0 && $morningstarts < 10) {
    $start_hour = "0$morningstarts";
}
if (empty($start_hour)) {
    $start_hour = "$morningstarts";
}
if (empty($start_min)) {
    $start_min = "00";
}
if (empty($rep_num_weeks)) {
    $rep_num_weeks = "";
}

$enable_periods ? toPeriodString($start_min, $duration, $dur_units) : toTimeString($duration, $dur_units);

$context = context_system::instance();

$roomadmin = false;
if (!getWritable($create_by, getUserName())) {
    if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
        if ($room_id) {
            $dbroom = $DB->get_record('block_mrbs_room', array('id' => $room_id));
            if ($dbroom && $dbroom->room_admin_email == $USER->email) {
                $roomadmin = true;
            }
        }
    }

    if (!$roomadmin) {
        showAccessDenied($day, $month, $year, $area);
        exit;
    }
}

$PAGE->requires->js('/blocks/mrbs/web/updatefreerooms.js', true);

print_header_mrbs($day, $month, $year, $area);

?>
<script language="JavaScript">
<?php
echo 'var currentroom=' . (int)$room_id . ';';
if (has_capability("block/mrbs:forcebook", $context)) {
    echo 'var canforcebook=true;';
} else {
    echo 'var canforcebook=false;';
}
?>
function validate_and_submit() {
    if (/(^$)|(^\s+$)/.test(document.forms["main"].name.value)) {
        alert("<?php echo get_string('you_have_not_entered', 'block_mrbs') . '\n' . get_string('name') ?>");
        return false;
    }
    if (/(^$)|(^\s+$)/.test(document.forms["main"].description.value)) {
        alert("<?php echo get_string('you_have_not_entered', 'block_mrbs') . '\n' . get_string('description') ?>");
        return false;
    }
    <?php if (!$enable_periods) { ?>
    h = parseInt(document.forms["main"].hour.value);
    m = parseInt(document.forms["main"].minute.value);

    if (h > 23 || m > 59) {
        alert("<?php echo get_string('you_have_not_entered', 'block_mrbs') . '\n' . get_string('valid_time_of_day', 'block_mrbs') ?>");
        return false;
    }
    <?php } ?>

    if (document.forms["main"].id) {
        i1 = parseInt(document.forms["main"].id.value);
    } else {
        i1 = 0;
    }

    i2 = parseInt(document.forms["main"].rep_id.value);
    if (document.forms["main"].rep_num_weeks) {
        n = parseInt(document.forms["main"].rep_num_weeks.value);
    }
    if ((!i1 || (i1 && i2)) && document.forms["main"].rep_type &&
            document.forms["main"].rep_type[6].checked && (!n || n < 2)) {
        alert("<?php echo get_string('you_have_not_entered', 'block_mrbs') . '\n' . get_string('useful_n-weekly_value', 'block_mrbs') ?>");
        return false;
    }

    if (document.forms["main"].elements['rooms[]'].selectedIndex == -1) {
        alert("<?php echo get_string('you_have_not_selected', 'block_mrbs') . '\n' . get_string('valid_room', 'block_mrbs') ?>");
        return false;
    }

    document.forms["main"].save_button.disabled = true;
    document.forms["main"].submit();

    return true;
}

function OnAllDayClick() {
    allday = document.getElementById('all_day');
    form = document.forms["main"];
    if (allday.checked) {
        <?php if (!$enable_periods) { ?>
        form.hour.value = "00";
        form.minute.value = "00";
        <?php } ?>
        if (form.dur_units.value != "days") {
            form.duration.value = "1";
            form.dur_units.value = "days";
        }
    }
    updateFreeRooms()
}
</script>

<h2><?php echo $id ? ($edit_type == "series" ? get_string('editseries', 'block_mrbs') : get_string('editentry', 'block_mrbs')) : get_string('addentry', 'block_mrbs'); ?></h2>

<form name="main" action="edit_entry_handler.php" method="post">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

    <table border="0">
        <?php if ($edit_type != 'series' && $rep_id) { ?>
            <tr>
                <td colspan="2"><b><?php
                    $editseriesurl = new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
                        'id' => $id,
                        'edit_type' => 'series'
                    ));
                    echo get_string('editingserieswarning', 'block_mrbs');
                    echo html_writer::link($editseriesurl, get_string('editseries', 'block_mrbs'));
                ?></b></td>
            </tr>
        <?php } ?>

        <tr>
            <td class="CR"><b><?php echo get_string('namebooker', 'block_mrbs') ?></b></td>
            <td class="CL"><input name="name" size="40" value="<?php echo s($name); ?>"></td>
        </tr>

        <tr>
            <td class="TR"><b><?php echo get_string('fulldescription', 'block_mrbs') ?></b></td>
            <td class="TL"><textarea name="description" rows="8" cols="40"><?php echo s($description); ?></textarea></td>
        </tr>

        <tr>
            <td class="CR"><b><?php echo get_string('date') ?></b></td>
            <td class="CL">
                <?php genDateSelector("", $start_day, $start_month, $start_year, true) ?>
                <script language="JavaScript">ChangeOptionDays(document.main, '');</script>
            </td>
        </tr>

        <?php if (!$enable_periods) { ?>
            <tr>
                <td class="CR"><b><?php echo get_string('time') ?></b></td>
                <td class="CL">
                    <input name="hour" size="2" value="<?php
                        if (!$twentyfourhour_format && ($start_hour > 12)) {
                            echo ($start_hour - 12);
                        } else {
                            echo $start_hour;
                        }
                    ?>" maxlength="2" onChange="updateFreeRooms()">:
                    <input name="minute" size="2" value="<?php echo $start_min; ?>" maxlength="2" onChange="updateFreeRooms()">
                    <?php
                    if (!$twentyfourhour_format) {
                        $checked = ($start_hour < 12) ? "checked" : "";
                        echo "<input name=\"ampm\" type=\"radio\" value=\"am\" $checked>" . userdate(mktime(1, 0, 0, 1, 1, 2000), "%p");
                        $checked = ($start_hour >= 12) ? "checked" : "";
                        echo "<input name=\"ampm\" type=\"radio\" value=\"pm\" $checked>" . userdate(mktime(13, 0, 0, 1, 1, 2000), "%p");
                    }
                    ?>
                </td>
            </tr>
        <?php } else { ?>
            <tr>
                <td class="CR"><b><?php echo get_string('period', 'block_mrbs') ?></b></td>
                <td class="CL">
                    <select name="period" onChange="updateFreeRooms()">
                        <?php
                        foreach ($periods as $p_num => $p_val) {
                            echo "<option value=\"$p_num\"";
                            if ((isset($period) && $period == $p_num) || $p_num == $start_min) {
                                echo " selected";
                            }
                            echo ">$p_val</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
        <?php } ?>

        <tr>
            <td class="CR"><b><?php echo get_string('duration', 'block_mrbs'); ?></b></td>
            <td class="CL">
                <input name="duration" size="7" value="<?php echo s($duration); ?>" onChange="updateFreeRooms()">
                <select name="dur_units" onChange="updateFreeRooms()">
                    <?php
                    if ($enable_periods) {
                        $units = array("periods", "days");
                    } else {
                        $units = array("minutes", "hours", "days", "weeks");
                    }

                    foreach ($units as $unit) {
                        echo "<option value=\"$unit\"";
                        if ($dur_units == get_string($unit, 'block_mrbs')) {
                            echo " selected";
                        }
                        echo ">" . get_string($unit, 'block_mrbs') . "</option>";
                    }
                    ?>
                </select>
                <input name="all_day" type="checkbox" value="yes" id="all_day" <?php if ($all_day) { echo 'checked '; } ?>onClick="OnAllDayClick()">
                <?php echo get_string('all_day', 'block_mrbs'); ?>
            </td>
        </tr>

        <?php
        $area_id = $DB->get_field('block_mrbs_room', 'area_id', array('id' => $room_id), MUST_EXIST);
        $areas = $DB->get_records('block_mrbs_area', null, 'area_name');

        if (count($areas) > 1) {
        ?>
            <script language="JavaScript">
                this.document.writeln("<tr><td class=CR><b><?php echo get_string('areas', 'block_mrbs') ?>:</b></td><td class=CL valign=top>");
                this.document.writeln("<select name=\"areas\" onChange=\"updateFreeRooms()\">");
                <?php
                foreach ($areas as $dbarea) {
                    $selected = "";
                    if ($dbarea->id == $area_id) {
                        $selected = "SELECTED";
                    }
                    print "this.document.writeln(\"<option $selected value=\\\"" . $dbarea->id . "\\\">" . s($dbarea->area_name) . "</option>\")\n";
                }
                print "this.document.writeln(\"<option value=\\\"IT\\\">" . get_string('computerrooms', 'block_mrbs') . "</option>\")\n";
                ?>
                this.document.writeln("</select>");
                this.document.writeln("</td></tr>");
            </script>
        <?php } ?>

        <tr>
            <td class="CR"><b><?php echo get_string('rooms', 'block_mrbs') ?>:</b></td>
            <td class="CL" valign="top">
                <table>
                    <tr>
                        <td>
                            <select name="rooms[]" multiple="multiple">
                                <?php
                                $rooms = $DB->get_records('block_mrbs_room', array('area_id' => $area_id), 'room_name');
                                $roomsbyid = array();
                                $i = 0;

                                foreach ($rooms as $dbroom) {
                                    $roomsbyid[$dbroom->id] = $dbroom;
                                    if (!allowed_to_book($USER, $dbroom)) {
                                        continue;
                                    }
                                    $selected = "";
                                    if ($dbroom->id == $room_id) {
                                        $selected = "SELECTED";
                                    }
                                    echo '<option ' . $selected . ' value="' . $dbroom->id . '">'
                                        . s($dbroom->room_name) . ' (' . s($dbroom->description) . ' Capacity:' . (int)$dbroom->capacity . ')</option>';
                                    $room_names[$i] = $dbroom->room_name;
                                    $i++;
                                }
                                ?>
                            </select>
                        </td>
                        <td><?php echo get_string('ctrl_click', 'block_mrbs') ?></td>
                    </tr>
                    <tr>
                        <td><label for="nooccupied"><?php echo get_string('dontshowoccupied', 'block_mrbs') ?></label><input
                                name="nooccupied" id="nooccupied" type="checkbox" checked="checked" onclick="updateFreeRooms()"/></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="CR"><b><?php echo get_string('type', 'block_mrbs') ?></b></td>
            <td class="CL">
                <select name="type">
                    <?php
                    if (($type == 'K') || ($type == 'L')) {
                        echo '<option value="L" selected="selected">' . $typel['L'] . "</option>\n";
                    } else {
                        $unconfirmed = false;
                        $unconfirmedonly = false;
                        if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
                            $unconfirmed = true;
                        }
                        if (authGetUserLevel(getUserName()) < 2 && $unconfirmed) {
                            if (isset($roomsbyid[$room_id]) && $USER->email != $roomsbyid[$room_id]->room_admin_email) {
                                $type = 'U';
                                $unconfirmedonly = true;
                            }
                        }
                        if (!$unconfirmedonly) {
                            for ($c = "A"; $c <= "J"; $c++) {
                                if (!empty($typel[$c])) {
                                    echo "<option value=\"$c\"" . ($type == $c ? ' selected="selected"' : '') . ">$typel[$c]</option>\n";
                                }
                            }
                        }
                        if ($unconfirmed) {
                            echo '<option value="U" ' . ($type == 'U' ? 'selected="selected"' : '') . '>' . $typel['U'] . "</option>\n";
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <td>
                <?php if (has_capability("block/mrbs:forcebook", $context)) {
                    echo '<label for="mrbsforcebook"><b>' . get_string('forciblybook2', 'block_mrbs') . ':</b></label></td><td><input id="mrbsforcebook" type="checkbox" name="forcebook" value="TRUE"';
                    if ($force) {
                        echo ' checked="checked"';
                    }
                    echo ' onClick="document.getElementById(\'nooccupied\').checked=!this.checked; updateFreeRooms();">';
                } ?>
            </td>
        </tr>

        <?php if ($edit_type == "series") { ?>
            <tr>
                <td class="CR"><b><?php echo get_string('rep_type', 'block_mrbs') ?></b></td>
                <td class="CL">
                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        echo '<input id="radiorepeat' . $i . '" name="rep_type" type="radio" value="' . $i . '"';
                        if ($i == $rep_type) {
                            echo ' checked';
                        }
                        echo '><label for="radiorepeat' . $i . '">' . get_string('rep_type_' . $i, 'block_mrbs') . "</label>\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="CR"><b><?php echo get_string('rep_end_date', 'block_mrbs') ?></b></td>
                <td class="CL"><?php genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year) ?></td>
            </tr>

            <tr>
                <td class="CR">
                    <b><?php echo get_string('rep_rep_day', 'block_mrbs') ?></b> <?php echo get_string('rep_for_weekly', 'block_mrbs') ?>
                </td>
                <td class="CL">
                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        $wday = ($i + $weekstarts) % 7;
                        echo '<input id="chkrepeatday' . $i . '" name="rep_day[' . $wday . ']" type="checkbox"';
                        if ($rep_day[$wday]) {
                            echo ' checked';
                        }
                        echo '><label for="chkrepeatday' . $i . '">' . day_name($wday) . "</label>\n";
                    }
                    ?>
                </td>
            </tr>
        <?php } else {
            $key = "rep_type_" . (isset($rep_type) ? $rep_type : "0");

            echo '<tr><td class="CR"><b>' . get_string('rep_type', 'block_mrbs') . '</b></td><td class="CL">' . get_string($key, 'block_mrbs') . "</td></tr>\n";

            if (isset($rep_type) && ($rep_type != 0)) {
                $opt = "";
                if ($rep_type == 2) {
                    for ($i = 0; $i < 7; $i++) {
                        $wday = ($i + $weekstarts) % 7;
                        if ($rep_opt[$wday]) {
                            $opt .= day_name($wday) . " ";
                        }
                    }
                }
                if ($opt) {
                    echo '<tr><td class="CR"><b>' . get_string('rep_rep_day', 'block_mrbs') . '</b></td><td class="CL">' . $opt . "</td></tr>\n";
                }

                echo '<tr><td class="CR"><b>' . get_string('rep_end_date', 'block_mrbs') . '</b></td><td class="CL">' . $rep_end_date . "</td></tr>\n";
            }
        }

        if ((($id == 0)) xor (isset($rep_type) && ($rep_type != 0) && ("series" == $edit_type))) {
        ?>
            <tr>
                <td class="CR">
                    <b><?php echo get_string('rep_num_weeks', 'block_mrbs') ?></b> <?php echo get_string('rep_for_nweekly', 'block_mrbs') ?>
                </td>
                <td class="CL"><input type="text" name="rep_num_weeks" value="<?php echo s($rep_num_weeks) ?>"></td>
            </tr>
        <?php } ?>

        <?php if ($id != 0) { ?>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td class="CR"><label for="mrbsroomchange"><b><?php print_string('roomchange', 'block_mrbs'); ?></b></label></td>
                <td><input type="checkbox" checked="checked" name="roomchange" id="mrbsroomchange"/></td>
            </tr>
        <?php } ?>

        <tr>
            <td colspan="2" align="center">
                <script language="JavaScript">
                    document.writeln('<input type="button" name="save_button" value="<?php echo get_string('savechanges')?>" onclick="validate_and_submit()">');
                    window.onload = function() { updateFreeRooms(); <?php if ($all_day) { ?>OnAllDayClick();<?php } ?> };
                </script>
                <noscript>
                    <input type="submit" value="<?php echo get_string('savechanges') ?>">
                </noscript>

                <?php
                if ($id) {
                    $delurl = new moodle_url('/blocks/mrbs/web/del_entry.php', array(
                        'id' => $id,
                        'series' => 0,
                        'sesskey' => sesskey()
                    ));
                    echo '<noscript><a id="dellink" href="' . $delurl . '">' . get_string('deleteentry', 'block_mrbs') . '</a></noscript>'
                        . '<script type="text/javascript">
                    document.writeln(\'<a href="#" onClick="if(confirm(\\\'' . get_string('confirmdel', 'block_mrbs') . '\\\')){document.location=\\\'' . $delurl . '\\\';}">' . get_string('deleteentry', 'block_mrbs') . '</a>\');
                 </script>';
                    if ($rep_id) {
                        $delurl = new moodle_url('/blocks/mrbs/web/del_entry.php', array(
                            'id' => $id,
                            'series' => 1,
                            'sesskey' => sesskey(),
                            'day' => $day,
                            'month' => $month,
                            'year' => $year
                        ));
                        echo " - ";
                        echo '<noscript><a id="dellink" href="' . $delurl . '">' . get_string('deleteentry', 'block_mrbs') . '</a></noscript>'
                            . '<script type="text/javascript">
                    document.writeln(\'<a href="#" onClick="if(confirm(\\\'' . get_string('confirmdel', 'block_mrbs') . '\\\')){document.location=\\\'' . $delurl . '\\\';}">' . get_string('deleteseries', 'block_mrbs') . '</a>\');
                 </script>';
                    }
                }
                ?>
            </td>
        </tr>
    </table>

    <input type="hidden" name="create_by" value="<?php echo s($create_by) ?>">
    <input type="hidden" name="rep_id" value="<?php echo (int)$rep_id ?>">
    <input type="hidden" name="edit_type" value="<?php echo s($edit_type) ?>">
    <?php if (!empty($id)) {
        echo '<input type="hidden" name="id" value="' . (int)$id . '">' . "\n";
    } ?>
</form>

<?php include "trailer.php"; ?>