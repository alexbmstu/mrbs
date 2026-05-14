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

global $PAGE, $DB;

require_once "config.inc.php";
require_once "functions.php";

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$advanced = optional_param('advanced', 0, PARAM_BOOL);
$search_str = optional_param('search_str', '', PARAM_TEXT);
$total = optional_param('total', 0, PARAM_INT);
$search_pos = optional_param('search_pos', 0, PARAM_INT);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/search.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
));
if ($area) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($advanced) {
    $thisurl->param('advanced', $advanced);
}
if ($search_str !== '') {
    $thisurl->param('search_str', $search_str);
}
if ($search_pos) {
    $thisurl->param('search_pos', $search_pos);
}

$PAGE->set_url($thisurl);
require_login();

print_header_mrbs($day, $month, $year, $area);

if ($advanced) {
    echo '<h3>' . get_string('advanced_search', 'block_mrbs') . '</h3>';
    echo '<form method="get" action="search.php">';
    echo '<input type="hidden" name="day" value="' . (int)$day . '" />';
    echo '<input type="hidden" name="month" value="' . (int)$month . '" />';
    echo '<input type="hidden" name="year" value="' . (int)$year . '" />';
    echo '<input type="hidden" name="area" value="' . (int)$area . '" />';
    echo get_string('search_for', 'block_mrbs') . ' <input type="text" size="25" name="search_str" value="' . s($search_str) . '"><br>';
    echo get_string('from') . ' ';
    genDateSelector("", $day, $month, $year);
    echo '<br><input type="submit" value="' . get_string('search') . '">';
    include "trailer.php";
    exit;
}

if ($search_str === '') {
    echo '<h3>' . get_string('invalid_search', 'block_mrbs') . '</h3>';
    include "trailer.php";
    exit;
}

echo '<h3>' . get_string('search_results', 'block_mrbs') . ' "<span style="color:blue">' . s($search_str) . '</span>"</h3>' . "\n";

$now = mktime(0, 0, 0, $month, $day, $year);

// Search as "contains", not exact token.
$searchlike = '%' . $search_str . '%';

// This is the main part of the query predicate, used in both queries.
$sql_pred = '(' . $DB->sql_like('create_by', '?', false)
    . ' OR ' . $DB->sql_like('name', '?', false)
    . ' OR ' . $DB->sql_like('description', '?', false)
    . ') AND end_time > ?';
$params = array($searchlike, $searchlike, $searchlike, $now);

// The first time the search is called, we get the total number of matches.
if (!$total) {
    $total = $DB->count_records_select('block_mrbs_entry', $sql_pred, $params);
    $thisurl->param('total', $total);
}

if ($total <= 0) {
    echo '<b>' . get_string('nothingtodisplay') . '</b>' . "\n";
    include "trailer.php";
    exit;
}

if ($search_pos <= 0) {
    $search_pos = 0;
} else if ($search_pos >= $total) {
    $search_pos = $total - ($total % $search['count']);
    if ($search_pos < 0) {
        $search_pos = 0;
    }
}

$sql_pred = str_replace(
    array('create_by', 'name', 'description'),
    array('e.create_by', 'e.name', 'e.description'),
    $sql_pred
);

// Now we set up the real query using LIMIT.
$sql = "SELECT e.id, e.create_by, e.name, e.description, e.start_time, r.area_id, r.room_name
          FROM {block_mrbs_entry} e
          JOIN {block_mrbs_room} r ON e.room_id = r.id
         WHERE $sql_pred
         ORDER BY e.start_time ASC";

$result = $DB->get_records_sql($sql, $params, $search_pos, $search['count']);
$num_records = is_array($result) ? count($result) : 0;

$has_prev = $search_pos > 0;
$has_next = $search_pos < ($total - $search['count']);

if ($has_prev || $has_next) {
    echo '<b>' . get_string('records', 'block_mrbs') . ($search_pos + 1)
        . get_string('through', 'block_mrbs') . ($search_pos + $num_records)
        . get_string('of', 'block_mrbs') . $total . '</b><br>';

    if ($has_prev) {
        $prevurl = new moodle_url($thisurl, array('search_pos' => max(0, $search_pos - $search['count'])));
        echo '<a href="' . $prevurl . '">';
    }
    echo '<b>' . get_string('previous') . '</b>';
    if ($has_prev) {
        echo '</a>';
    }

    echo ' | ';

    if ($has_next) {
        $nexturl = new moodle_url($thisurl, array('search_pos' => max(0, $search_pos + $search['count'])));
        echo '<a href="' . $nexturl . '">';
    }
    echo '<b>' . get_string('next') . '</b>';
    if ($has_next) {
        echo '</a>';
    }
}
?>
<p>
<table border="2" cellspacing="0" cellpadding="3">
    <tr>
        <th><?php echo get_string('entry', 'block_mrbs') ?></th>
        <th><?php echo get_string('createdby', 'block_mrbs') ?></th>
        <th><?php echo get_string('namebooker', 'block_mrbs') ?></th>
        <th><?php echo get_string('room', 'block_mrbs') ?></th>
        <th><?php echo get_string('description') ?></th>
        <th><?php echo get_string('start_date', 'block_mrbs') ?></th>
    </tr>
<?php
foreach ($result as $entry) {
    $viewurl = new moodle_url('/blocks/mrbs/web/view_entry.php', array('id' => $entry->id));
    echo '<tr>';
    echo '<td><a href="' . $viewurl . '">' . get_string('view') . "</a></td>\n";
    echo '<td>' . s($entry->create_by) . "</td>\n";
    echo '<td>' . s($entry->name) . "</td>\n";
    echo '<td>' . s($entry->room_name) . "</td>\n";
    echo '<td>' . s($entry->description) . "</td>\n";

    $link = getdate($entry->start_time);
    $dayurl = new moodle_url('/blocks/mrbs/web/day.php', array(
        'day' => $link['mday'],
        'month' => $link['mon'],
        'year' => $link['year'],
        'area' => $entry->area_id
    ));
    echo '<td><a href="' . $dayurl . '">';
    if (empty($enable_periods)) {
        $link_str = time_date_string($entry->start_time);
    } else {
        list(, $link_str) = period_date_string($entry->start_time);
    }
    echo s($link_str) . "</a></td>";
    echo "</tr>\n";
}

echo "</table>\n";
include "trailer.php";