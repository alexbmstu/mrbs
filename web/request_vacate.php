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
require_once($CFG->libdir . '/editorlib.php');

global $USER, $PAGE;

$messagelang = new stdClass();
$messagelang->user = fullname($USER);

if (empty($description)) {
    $messagelang->description = $name;
} else {
    $messagelang->description = $description;
}

$messagelang->room = $room_name;
$messagelang->datetime = $start_date;

$url = new moodle_url('/blocks/mrbs/web/edit_entry.php', array('id' => $id));
$messagelang->href = $url->out(false);

$context = context_system::instance();

if (has_capability('block/mrbs:editmrbs', $context) || has_capability('block/mrbs:administermrbs', $context)) {
    $textareaid = 'id_message';
    $messagehtml = get_string('requestvacatemessage_html', 'block_mrbs', $messagelang);
    $sendurl = new moodle_url('/blocks/mrbs/web/request_vacate_send.php');

    echo '<br><br>';
    echo '<a href="#" onclick="document.getElementById(\'request_vacate\').style.visibility=\'visible\'; return false;">'
        . get_string('requestvacate', 'block_mrbs') . '</a>';

    echo '<form id="editing" method="post" action="' . $sendurl . '">';
    echo '<div id="request_vacate">';
    echo '<input type="hidden" name="id" value="' . (int)$id . '" />';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo '<textarea id="' . $textareaid . '" name="message" rows="15" cols="80">'
        . s($messagehtml) . '</textarea>';
    echo '<input type="hidden" name="format" value="' . FORMAT_HTML . '" />';
    echo '<br /><input type="submit" value="' . get_string('sendmessage', 'message') . '" />';
    echo '</div>';
    echo '</form>';

    $editor = editors_get_preferred_editor(FORMAT_HTML);
    $editor->use_editor($textareaid, array(
        'context' => $context,
        'autosave' => false,
        'enable_filemanagement' => false,
    ));

    echo '<script type="text/javascript">
        var requestVacate = document.getElementById("request_vacate");
        if (requestVacate) {
            requestVacate.style.visibility = "hidden";
        }
    </script>';
}