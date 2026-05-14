<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $DB, $USER, $PAGE;

include "config.inc.php";
include "functions.php";
require_once "mrbs_auth.php";

$id = required_param('id', PARAM_INT);
$day = optional_param('day', 0, PARAM_INT);
$month = optional_param('month', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$area = optional_param('area', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$series = optional_param('series', 0, PARAM_INT);
$pview = optional_param('pview', 0, PARAM_INT);

$context = context_system::instance();

// If the booking belongs to the current user, try to redirect to edit page.
if ($record = $DB->get_record('block_mrbs_entry', array('id' => $id))) {
    if (strtolower($record->create_by) === strtolower($USER->username)) {
        $redirect = true;

        if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
            $adminemail = $DB->get_field('block_mrbs_room', 'room_admin_email', array('id' => $record->room_id));
            if ($USER->email != $adminemail && $record->type != 'U') {
                $redirect = false;
            }
        }

        if ($redirect) {
            redirect(new moodle_url('/blocks/mrbs/web/edit_entry.php', array('id' => $id)));
        }
    }
}

// If we don't know the date then make it up.
if (($day == 0) || ($month == 0) || ($year == 0)) {
    $day = (int)date("d");
    $month = (int)date("m");
    $year = (int)date("Y");
}

$thisurl = new moodle_url('/blocks/mrbs/web/view_entry.php', array(
    'day' => $day,
    'month' => $month,
    'year' => $year,
    'id' => $id
));

if ($area) {
    $thisurl->param('area', $area);
} else {
    $area = get_default_area();
}
if ($room) {
    $thisurl->param('room', $room);
}
if ($series) {
    $thisurl->param('series', $series);
}
if ($pview) {
    $thisurl->param('pview', $pview);
}

$PAGE->set_url($thisurl);
require_login();

// Moodle 4.x replacement for deprecated old user-name helper.
$namefieldlist = \core_user\fields::get_name_fields();
$namefields = array();
foreach ($namefieldlist as $field) {
    $namefields[] = 'u.' . $field;
}
$namefields = implode(', ', $namefields);

if ($series) {
    $sql = "SELECT re.name,
                   re.description,
                   re.create_by,
                   r.room_name,
                   a.area_name,
                   re.type,
                   re.room_id,
                   re.timestamp,
                   (re.end_time - re.start_time) AS duration,
                   re.start_time,
                   re.end_time,
                   re.rep_type,
                   re.end_date,
                   re.rep_opt,
                   re.rep_num_weeks,
                   u.id AS userid,
                   $namefields
              FROM {block_mrbs_repeat} re
         LEFT JOIN {user} u ON u.username = re.create_by
              JOIN {block_mrbs_room} r ON re.room_id = r.id
              JOIN {block_mrbs_area} a ON r.area_id = a.id
             WHERE re.id = ?";
} else {
    $sql = "SELECT e.name,
                   e.description,
                   e.create_by,
                   r.room_name,
                   a.area_name,
                   e.type,
                   e.room_id,
                   e.timestamp,
                   (e.end_time - e.start_time) AS duration,
                   e.start_time,
                   e.end_time,
                   e.repeat_id,
                   u.id AS userid,
                   $namefields
              FROM {block_mrbs_entry} e
         LEFT JOIN {user} u ON u.username = e.create_by
              JOIN {block_mrbs_room} r ON e.room_id = r.id
              JOIN {block_mrbs_area} a ON r.area_id = a.id
             WHERE e.id = ?";
}

$booking = $DB->get_record_sql($sql, array($id), MUST_EXIST);
$booking->fullname = fullname($booking);

$name = s($booking->name);
$description = format_text($booking->description, FORMAT_PLAIN);
$userurl = null;
$createby = s($booking->create_by);

if (!empty($booking->userid)) {
    $userurl = new moodle_url('/user/view.php', array('id' => $booking->userid));
    $createby = html_writer::link($userurl, s($booking->fullname));
}

$roomname = s($booking->room_name);
$areaname = s($booking->area_name);
$type = $booking->type;
$roomid = $booking->room_id;
$updated = time_date_string($booking->timestamp);

$duration = $booking->duration - cross_dst($booking->start_time, $booking->end_time);

if ($enable_periods) {
    list($startperiod, $startdate) = period_date_string($booking->start_time);
    list(, $enddate) = period_date_string($booking->end_time, -1);
    toPeriodString($startperiod, $duration, $dur_units);
} else {
    $startdate = time_date_string($booking->start_time);
    $enddate = time_date_string($booking->end_time);
    toTimeString($duration, $dur_units);
}

$rep_type = 0;
$repeatid = 0;
$rep_end_date = '';
$rep_num_weeks = '';
$rep_opt = '';

if ($series == 1) {
    $rep_type = $booking->rep_type;
    $rep_end_date = userdate($booking->end_date, '%A %d %B %Y');
    $rep_opt = $booking->rep_opt;
    $rep_num_weeks = $booking->rep_num_weeks;
    $repeatid = false;

    $entry = $DB->get_records('block_mrbs_entry', array('repeat_id' => $id, 'entry_type' => 1), 'start_time', 'id', 0, 1);
    if (empty($entry)) {
        $entry = $DB->get_records('block_mrbs_entry', array('repeat_id' => $id), 'start_time', 'id', 0, 1);
    }
    if (!empty($entry)) {
        $entry = reset($entry);
        $id = $entry->id;
    }
} else {
    $repeatid = $booking->repeat_id;
    if ($repeatid != 0) {
        $repeat = $DB->get_record('block_mrbs_repeat', array('id' => $repeatid));
        if ($repeat) {
            $rep_type = $repeat->rep_type;
            $rep_end_date = userdate($repeat->end_date, '%A %d %B %Y');
            $rep_opt = $repeat->rep_opt;
            $rep_num_weeks = $repeat->rep_num_weeks;
        }
    }
}

$roomadmin = false;
if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
    $adminemail = $DB->get_field('block_mrbs_room', 'room_admin_email', array('id' => $booking->room_id));
    if ($adminemail == $USER->email) {
        $roomadmin = true;
    }
}

if ($roomadmin && $type == 'U') {
    redirect(new moodle_url('/blocks/mrbs/web/edit_entry.php', array('id' => $id)));
}

print_header_mrbs($day, $month, $year, $area);

echo $OUTPUT->heading(get_string('viewentry', 'block_mrbs'), 2);

echo html_writer::start_tag('table', array('class' => 'generaltable'));
echo html_writer::start_tag('tbody');

$row = function($label, $value) {
    return html_writer::tag('tr',
        html_writer::tag('th', $label, array('class' => 'header c0')) .
        html_writer::tag('td', $value, array('class' => 'cell c1'))
    );
};

echo $row(get_string('namebooker', 'block_mrbs'), $name);
echo $row(get_string('description', 'block_mrbs'), $description);
echo $row(get_string('room', 'block_mrbs'), $roomname);
echo $row(get_string('area', 'block_mrbs'), $areaname);
echo $row(get_string('createdby', 'block_mrbs'), $createby);
echo $row(get_string('start_date', 'block_mrbs'), $startdate);
echo $row(get_string('end_date', 'block_mrbs'), $enddate);
echo $row(get_string('duration', 'block_mrbs'), s($dur_units));
echo $row(get_string('lastupdate', 'block_mrbs'), $updated);
echo $row(get_string('type', 'block_mrbs'), s($type));

if ($rep_type) {
    echo $row(get_string('rep_type', 'block_mrbs'), get_string('rep_type_' . $rep_type, 'block_mrbs'));
    if ($rep_num_weeks !== '') {
        echo $row(
            get_string('rep_num_weeks', 'block_mrbs') . get_string('rep_for_nweekly', 'block_mrbs'),
            s($rep_num_weeks)
        );
    }
    if (!empty($rep_end_date)) {
        echo $row(get_string('rep_end_date', 'block_mrbs'), s($rep_end_date));
    }
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

$canedit = getWritable($booking->create_by, getUserName());

$links = array();
if ($canedit || $roomadmin) {
    if (!$series) {
        $links[] = html_writer::link(
            new moodle_url('/blocks/mrbs/web/edit_entry.php', array('id' => $id)),
            get_string('editentry', 'block_mrbs')
        );
    }

    if ($repeatid || $series) {
        $links[] = html_writer::link(
            new moodle_url('/blocks/mrbs/web/edit_entry.php', array(
                'id' => $id,
                'edit_type' => 'series',
                'day' => $day,
                'month' => $month,
                'year' => $year
            )),
            get_string('editseries', 'block_mrbs')
        );
    }

    if (!$series) {
        $links[] = html_writer::link(
            new moodle_url('/blocks/mrbs/web/del_entry.php', array(
                'id' => $id,
                'series' => 0,
                'sesskey' => sesskey()
            )),
            get_string('deleteentry', 'block_mrbs')
        );
    }

    if ($repeatid || $series) {
        $links[] = html_writer::link(
            new moodle_url('/blocks/mrbs/web/del_entry.php', array(
                'id' => $id,
                'series' => 1,
                'sesskey' => sesskey(),
                'day' => $day,
                'month' => $month,
                'year' => $year
            )),
            get_string('deleteseries', 'block_mrbs')
        );
    }
}

if (!empty($links)) {
    echo html_writer::div(implode(' | ', $links), 'mrbs-actions');
}

if (strtolower($USER->username) != strtolower($booking->create_by) && file_exists(__DIR__ . '/request_vacate.php')) {
    include "request_vacate.php";
}

include "trailer.php";