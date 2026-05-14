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
require_once($CFG->libdir . '/outputcomponents.php');

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";

global $PAGE, $OUTPUT, $twentyfourhour_format;

require_login();

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', get_default_area(), PARAM_INT);
$edit_type = optional_param('edit_type', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$room_id = optional_param('room_id', 0, PARAM_INT);
$start_hour = optional_param('start_hour', 0, PARAM_INT);
$start_min = optional_param('start_min', 0, PARAM_INT);
$period = optional_param('period', 0, PARAM_INT);
$rep_num_weeks = optional_param('rep_num_weeks', 0, PARAM_INT);
$force = optional_param('force', false, PARAM_BOOL);
$duration = optional_param('duration', 1, PARAM_INT);
$all_day = optional_param('all_day', false, PARAM_BOOL);

if ($enable_periods) {
    $default_dur_units = 'periods';
} else {
    $default_dur_units = 'hours';
}
$dur_units = optional_param('dur_units', $default_dur_units, PARAM_TEXT);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/roomsearch.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year,
    'area' => $area
));
if (!empty($edit_type)) {
    $thisurl->param('edit_type', $edit_type);
}
if (!empty($id)) {
    $thisurl->param('id', $id);
}
if (!empty($room_id)) {
    $thisurl->param('room_id', $room_id);
}
if (!empty($start_hour)) {
    $thisurl->param('start_hour', $start_hour);
}
if (!empty($start_min)) {
    $thisurl->param('start_min', $start_min);
}
if (!empty($period)) {
    $thisurl->param('period', $period);
}
if (!empty($rep_num_weeks)) {
    $thisurl->param('rep_num_weeks', $rep_num_weeks);
}
if (!empty($force)) {
    $thisurl->param('force', $force);
}
if (!empty($duration)) {
    $thisurl->param('duration', $duration);
}
if (!empty($all_day)) {
    $thisurl->param('all_day', $all_day);
}
if (!empty($dur_units)) {
    $thisurl->param('dur_units', $dur_units);
}

$PAGE->set_url($thisurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('search'));
$PAGE->set_heading(get_string('search'));
$PAGE->requires->js(new moodle_url('/blocks/mrbs/web/roomsearch.js'));

echo $OUTPUT->header();
?>

<script type="text/javascript">
    function openURL(sURL) {
        opener.document.location = sURL;
        window.close();
    }

    function OnAllDayClick() {
        var allday = document.getElementById('all_day');
        var form = document.forms["main"];
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
        RoomSearch();
    }
</script>

<?php
echo '<script type="text/javascript">SetWeekDayNames(';
echo '"' . get_string('mon', 'calendar') . '", ';
echo '"' . get_string('tue', 'calendar') . '", ';
echo '"' . get_string('wed', 'calendar') . '", ';
echo '"' . get_string('thu', 'calendar') . '", ';
echo '"' . get_string('fri', 'calendar') . '", ';
echo '"' . get_string('sat', 'calendar') . '", ';
echo '"' . get_string('sun', 'calendar') . '"';
echo ');</script>';
?>

<h2><?php echo get_string('search'); ?></h2>

<div id="searchform">
    <form name="main" action="#" method="get">
        <table border="0">
            <tr>
                <td class="CR"><b><?php echo get_string('date') ?></b></td>
                <td class="CL">
                    <?php genDateSelector("", $day, $month, $year, false, true) ?>
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
                        ?>" maxlength="2" onChange="RoomSearch()">:
                        <input name="minute" size="2" value="<?php echo $start_min; ?>" maxlength="2" onChange="RoomSearch()">
                        <?php
                        if (!$twentyfourhour_format) {
                            $checked = ($start_hour < 12) ? "checked" : "";
                            echo '<input name="ampm" type="radio" value="am" ' . $checked . '>' . userdate(mktime(1, 0, 0, 1, 1, 2000), "%p");
                            $checked = ($start_hour >= 12) ? "checked" : "";
                            echo '<input name="ampm" type="radio" value="pm" ' . $checked . '>' . userdate(mktime(13, 0, 0, 1, 1, 2000), "%p");
                        }
                        ?>
                    </td>
                </tr>
            <?php } else { ?>
                <tr>
                    <td class="CR"><b><?php echo get_string('period', 'block_mrbs') ?></b></td>
                    <td class="CL">
                        <select name="period" onChange="RoomSearch()">
                            <?php
                            foreach ($periods as $p_num => $p_val) {
                                echo '<option value="' . $p_num . '"';
                                if ((isset($period) && $period == $p_num) || $p_num == $start_min) {
                                    echo ' selected';
                                }
                                echo '>' . s($p_val) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            <?php } ?>

            <tr>
                <td class="CR"><b><?php echo get_string('duration', 'block_mrbs'); ?></b></td>
                <td class="CL">
                    <input name="duration" size="7" value="<?php echo (int)$duration; ?>" onChange="RoomSearch()" onKeyUp="RoomSearch()">
                    <select name="dur_units" onChange="RoomSearch()">
                        <?php
                        if ($enable_periods) {
                            $units = array("periods", "days");
                        } else {
                            $units = array("minutes", "hours", "days", "weeks");
                        }

                        foreach ($units as $unit) {
                            echo '<option value="' . s($unit) . '"';
                            if ($dur_units == $unit || $dur_units == get_string($unit, 'block_mrbs')) {
                                echo ' selected';
                            }
                            echo '>' . get_string($unit, 'block_mrbs') . '</option>';
                        }
                        ?>
                    </select>
                    <input name="all_day" type="checkbox" value="yes" id="all_day" <?php if ($all_day) { echo 'checked '; } ?>onClick="OnAllDayClick()">
                    <?php echo get_string('all_day', 'block_mrbs'); ?>
                </td>
            </tr>

            <tr>
                <td class="CR"><b><?php echo get_string('mincapacity', 'block_mrbs') ?></b></td>
                <td class="CL"><input name="mincap" size="3" onChange="RoomSearch()" onKeyUp="RoomSearch()"></td>
            </tr>

            <tr>
                <td class="CR"><b><?php echo get_string('teachingroom', 'block_mrbs') ?></b></td>
                <td class="CL"><input type="checkbox" name="teaching" onClick="RoomSearch()" checked="checked"></td>
            </tr>

            <tr>
                <td class="CR"><b><?php echo get_string('specialroom', 'block_mrbs') ?></b></td>
                <td class="CL"><input type="checkbox" name="special" onClick="RoomSearch()" checked="checked"></td>
            </tr>

            <tr>
                <td class="CR"><b><?php echo get_string('computerroom', 'block_mrbs') ?></b></td>
                <td class="CL"><input type="checkbox" name="computer" onClick="RoomSearch()"></td>
            </tr>
        </table>
    </form>
</div>

<h2 id="results"></h2>
<?php
echo '<table border="1"><thead><tr><th>' . get_string('area', 'block_mrbs') . '</th><th>Room</th><th>' .
    get_string('description') . '</th><th>' . get_string('capacity', 'block_mrbs') .
    '</th></tr></thead><tbody id="rooms"></tbody></table>';
?>

<script language="JavaScript">
    var langRoomsFree = '<?php print_string('roomsfree', 'block_mrbs');?>';
    var langNoRooms = '<?php print_string('noroomsfound', 'block_mrbs');?>';
    window.onload = function() {
        RoomSearch();
        <?php if ($all_day) { ?>OnAllDayClick();<?php } ?>
    };
</script>

<?php
echo $OUTPUT->footer();