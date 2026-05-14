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

global $PAGE, $CFG, $DB;

require_once "config.inc.php";
require_once "functions.php";
require_once "mrbs_auth.php";

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$area_name = optional_param('area_name', '', PARAM_TEXT);

// If we don't know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/admin.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
));

if ($area) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}

$PAGE->set_url($thisurl);
require_login();

if (!getAuthorised(2)) {
    showAccessDenied($day, $month, $year, $area);
    exit();
}

print_header_mrbs($day, $month, $year, $area);

// If area is set but area name is not known, get the name.
if ($area && empty($area_name)) {
    $dbarea = $DB->get_record('block_mrbs_area', array('id' => $area), 'area_name', MUST_EXIST);
    $area_name = $dbarea->area_name;
}

echo '<h2>' . get_string('administration') . '</h2>';
echo '<table border="1">';
echo '<tr>';
echo '<th><center><b>' . get_string('areas', 'block_mrbs') . '</b></center></th>';
echo '<th><center><b>' . get_string('rooms', 'block_mrbs') . ' ';
if ($area_name !== '') {
    echo get_string('in', 'block_mrbs') . ' ' . s($area_name);
}
echo '</b></center></th>';
echo '</tr>';
echo '<tr>';
echo '<td class="border">';

// This cell has the areas.
$areas = $DB->get_records('block_mrbs_area', null, 'area_name');

if (empty($areas)) {
    echo get_string('noareas', 'block_mrbs');
} else {
    echo '<ul>';
    foreach ($areas as $dbarea) {
        $adminurl = new moodle_url('/blocks/mrbs/web/admin.php', array(
            'area' => $dbarea->id,
            'area_name' => $dbarea->area_name,
            'sesskey' => sesskey()
        ));
        $editroomurl = new moodle_url('/blocks/mrbs/web/edit_area_room.php', array(
            'area' => $dbarea->id,
            'sesskey' => sesskey()
        ));
        $delareaurl = new moodle_url('/blocks/mrbs/web/del.php', array(
            'area' => $dbarea->id,
            'type' => 'area',
            'sesskey' => sesskey()
        ));

        echo '<li><a href="' . $adminurl . '">' . s($dbarea->area_name) . '</a> ';
        echo '(<a href="' . $editroomurl . '">' . get_string('edit') . '</a>) ';
        echo '(<a href="' . $delareaurl . '">' . get_string('delete') . '</a>)</li>' . "\n";
    }
    echo 