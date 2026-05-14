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
require_once(__DIR__ . '/config.inc.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/version.php');

global $USER, $CFG, $PAGE;

$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);

// If we dont know the right date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/help.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year
));

if ($area > 0) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}

$PAGE->set_url($thisurl);

if ($CFG->forcelogin) {
    require_login();
}

print_header_mrbs($day, $month, $year, $area);

echo '<h3>' . get_string('about_mrbs', 'block_mrbs') . "</h3>\n";

$mrbsurl = new moodle_url('http://mrbs.sourceforge.net');
echo '<p><a href="' . $mrbsurl . '">' . get_string('mrbs', 'block_mrbs') . '</a> - ' . s(get_mrbs_version()) . "</p>\n";
echo '<p>' . get_string('system', 'block_mrbs') . s(php_uname()) . "</p>\n";
echo '<p>PHP: ' . s(phpversion()) . "</p>\n";

echo '<h3>' . get_string('help') . "</h3>\n";

$adminemail = !empty($mrbs_admin_email) ? $mrbs_admin_email : '';
$adminname = !empty($mrbs_admin) ? $mrbs_admin : $adminemail;

echo '<p>' . get_string('please_contact', 'block_mrbs')
    . '<a href="mailto:' . s($adminemail) . '">' . s($adminname) . '</a> '
    . get_string('for_any_questions', 'block_mrbs') . "</p>\n";

$lang = current_language();
$helppath = $CFG->dirroot . '/blocks/mrbs/lang/' . $lang . '/help/site_faq.html';
$defaulthelppath = $CFG->dirroot . '/blocks/mrbs/lang/en/help/site_faq.html';

if (file_exists($helppath)) {
    require $helppath;
} else if (file_exists($defaulthelppath)) {
    require $defaulthelppath;
}

require_once(__DIR__ . '/trailer.php');