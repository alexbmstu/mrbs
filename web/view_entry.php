<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $DB, $USER, $PAGE, $OUTPUT;

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

// If the booking belongs to the user looking at it, they probably want to edit it.
if ($record = $DB->get_record('blockmrbsentry', array('id' => $id))) {
    if (strtolower($record->createby) === strtolower($USER->username)) {
        $redirect = true;

        if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
            $adminemail = $DB->get_field('blockmrbsroom', 'roomadminemail', array('id' => $record->roomid));
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
    $area = getDefaultArea();
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

// Moodle 4.x safe replacement for deprecated get_all_user_name_fields().
$namefields = "u.firstname, u.lastname, u.middlename, u.alternatename,
               u.firstnamephonetic, u.lastnamephonetic";

if ($series) {
    $sql = "SELECT re.name,
                   re.description,
                   re.createby,
                   r.roomname,
                   a.areaname,
                   re.type,
                   re.roomid,
                   re.timestamp,
                   (re.endtime - re.starttime) AS duration,
                   re.starttime,
                   re.endtime,
                   re.reptype,
                   re.enddate,
                   re.repopt,
                   re.repnumweeks,
                   u.id AS userid,
                   $namefields
              FROM {blockmrbsrepeat} re
         LEFT JOIN {user} u ON u.username = re.createby
              JOIN {blockmrbsroom} r ON re.roomid = r.id
              JOIN {blockmrbsarea} a ON r.areaid = a.id
             WHERE re.id = ?";
} else {
    $sql = "SELECT e.name,
                   e.description,
                   e.createby,
                   r.roomname,
                   a.areaname,
                   e.type,
                   e.roomid,
                   e.timestamp,
                   (e.endtime - e.starttime) AS duration,
                   e.starttime,
                   e.endtime,
                   e.repeatid,
                   u.id AS userid,
                   $namefields
              FROM {blockmrbsentry} e
         LEFT JOIN {user} u ON u.username = e.createby
              JOIN {blockmrbsroom} r ON e.roomid = r.id
              JOIN {blockmrbsarea} a ON r.areaid = a.id
             WHERE e.id = ?";
}

$booking = $DB->get_record_sql($sql, array($id), MUST_EXIST);

$fullname = '';
if (!empty($booking->userid)) {
    $fullname = fullname($booking);
}

$name = s($booking->name);
$description = nl2br(s($booking->description));
$createby = s($booking->createby);

if (!empty($booking->userid) && !empty($fullname)) {
    $userurl = new moodle_url('/user/view.php', array('id' => $booking->userid));
    $createby = html_writer::link($userurl, s($fullname));
}

$roomname = s($booking->roomname);
$areaname = s($booking->areaname);
$type = $booking->type;
$roomid = $booking->roomid;
$updated = timeDateString($booking->timestamp);

$duration = $booking->duration - crossDST($booking->starttime, $booking->endtime);

if ($enableperiods) {
    list($startperiod, $startdate) = periodDateString($booking->starttime);
    list(, $enddate) = periodDateString($booking->endtime, -1);
    toPeriodString($startperiod, $duration, $durunits);
} else {
    $startdate = timeDateString($booking->starttime);
    $enddate = timeDateString($booking->endtime);
    toTimeString($duration, $durunits);
}

$reptype = 0;
$repeatid = 0;
$rependdate = '';
$repnumweeks = '';
$repopt = '';

if ($series == 1) {
    $reptype = $booking->reptype;
    $rependdate = userdate($booking->enddate, '%A %d %B %Y');
    $repopt = $booking->repopt;
    $repnumweeks = $booking->repnumweeks;
    $repeatid = false;

    $entry = $DB->get_records('blockmrbsentry', array('repeatid' => $id, 'entrytype' => 1), 'starttime', 'id', 0, 1);
    if (empty($entry)) {
        $entry = $DB->get_records('blockmrbsentry', array('repeatid' => $id), 'starttime', 'id', 0, 1);
    }
    if (!empty($entry)) {
        $entry = reset($entry);
        $id = $entry->id;
    }
} else {
    $repeatid = $booking->repeatid;
    if ($repeatid != 0) {
        $repeat = $DB->get_record('blockmrbsrepeat', array('id' => $repeatid));
        if ($repeat) {
            $reptype = $repeat->reptype;
            $rependdate = userdate($repeat->enddate, '%A %d %B %Y');
            $repopt = $repeat->repopt;
            $repnumweeks = $repeat->repnumweeks;
        }
    }
}

$roomadmin = false;
if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
    $adminemail = $DB->get_field('blockmrbsroom', 'roomadminemail', array('id' => $booking->roomid));
    if ($adminemail == $USER->email) {
        $roomadmin = true;
    }
}

if ($roomadmin && $type == 'U') {
    redirect(new moodle_url('/blocks/mrbs/web/edit_entry.php', array('id' => $id)));
}

printHeaderMRBS($day, $month, $year, $area);

echo $OUTPUT->heading(get_string('entry', 'block_mrbs'), 3);

echo html_writer::start_tag('table', array('class' => 'generaltable'));
echo html_writer::start_tag('tbody');

$row = function($label, $value) {
    return html_writer::tag('tr',
        html_writer::tag('th', $label, array('class' => 'header c0')) .
        html_writer::tag('td', $value, array('class' => 'cell c1'))
    );
};

echo $row(get_string('namebooker', 'block_mrbs'), $name);
echo $row(get_string('description'), $description);
echo $row(get_string('room', 'block_mrbs'), nl2br($areaname . ' - ' . $roomname));
echo $row(get_string('startdate', 'block_mrbs'), $startdate);
echo $row(get_string('duration', 'block_mrbs'), s($duration . ' ' . $durunits));
echo $row(get_string('enddate', 'block_mrbs'), $enddate);
echo $row(get_string('type', 'block_mrbs'), empty($typel[$type]) ? '?' . s($type) . '?' : s($typel[$type]));
echo $row(get_string('createdby', 'block_mrbs'), $createby);
echo $row(get_string('lastmodified'), $updated);

if ($reptype != 0) {
    $repeatkey = 'reptype' . $reptype;
    echo $row(get_string('reptype', 'block_mrbs'), get_string($repeatkey, 'block_mrbs'));

    if ($reptype == 6 && $repnumweeks !== '') {
        echo $row(
            get_string('repnumweeks', 'block_mrbs') . get_string('repfornweekly', 'block_mrbs'),
            s($repnumweeks)
        );
    }

    if (!empty($rependdate)) {
        echo $row(get_string('rependdate', 'block_mrbs'), s($rependdate));
    }
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

$canedit = getWritable($booking->createby, getUserName());

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
                'edittype' => 'series',
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
            get_string('deleteentry', 'block_mrbs'),
            array('onclick' => "return confirm('" . addslashes_js(get_string('confirmdel', 'block_mrbs')) . "')")
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
            get_string('deleteseries', 'block_mrbs'),
            array('onclick' => "return confirm('" . addslashes_js(get_string('confirmdel', 'block_mrbs')) . "')")
        );
    }
}

if (!empty($links)) {
    echo html_writer::div(implode(' | ', $links), 'mrbs-actions');
}

if (strtolower($USER->username) != strtolower($booking->createby) && file_exists(__DIR__ . '/request_vacate.php')) {
    include "request_vacate.php";
}

include "trailer.php";