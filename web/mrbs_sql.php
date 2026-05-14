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

/**
 * Check to see if the time period specified is free.
 *
 * @param int $room_id
 * @param int $starttime
 * @param int $endtime
 * @param int $ignore
 * @param int $repignore
 * @return string
 */
function mrbsCheckFree($room_id, $starttime, $endtime, $ignore, $repignore) {
    global $DB, $enable_periods, $periods;

    $sql = "start_time < ? AND end_time > ? AND room_id = ?";
    $params = array($endtime, $starttime, $room_id);

    if ($ignore > 0) {
        $sql .= " AND id <> ?";
        $params[] = $ignore;
    }
    if ($repignore > 0) {
        $sql .= " AND repeat_id <> ?";
        $params[] = $repignore;
    }

    $entries = $DB->get_records_select('block_mrbs_entry', $sql, $params, 'start_time');

    if (empty($entries)) {
        return "";
    }

    $area = mrbsGetRoomArea($room_id);

    $err = "";
    foreach ($entries as $entry) {
        $starts = getdate($entry->start_time);
        $param_ym = array('area' => $area, 'year' => $starts['year'], 'month' => $starts['mon']);
        $param_ymd = array_merge($param_ym, array('day' => $starts['mday']));

        if ($enable_periods) {
            $p_num = $starts['minutes'];
            $periodname = isset($periods[$p_num]) ? $periods[$p_num] : $p_num;
            $startstr = userdate($entry->start_time, '%A %d %B %Y, ') . $periodname;
        } else {
            $startstr = userdate($entry->start_time, '%A %d %B %Y %H:%M:%S');
        }

        $viewurl = new moodle_url('/blocks/mrbs/web/view_entry.php', array('id' => $entry->id));
        $dayurl = new moodle_url('/blocks/mrbs/web/day.php', $param_ymd);
        $weekurl = new moodle_url('/blocks/mrbs/web/week.php', array_merge($param_ymd, array('room' => $room_id)));
        $monthurl = new moodle_url('/blocks/mrbs/web/month.php', array_merge($param_ym, array('room' => $room_id)));

        $err .= '<li><a href="' . $viewurl . '">' . s($entry->name) . '</a>'
            . ' (' . s($startstr) . ') '
            . '(<a href="' . $dayurl . '">' . get_string('viewday', 'block_mrbs') . '</a>'
            . ' | <a href="' . $weekurl . '">' . get_string('viewweek', 'block_mrbs') . '</a>'
            . ' | <a href="' . $monthurl . '">' . get_string('viewmonth', 'block_mrbs') . '</a>)</li>';
    }

    return $err;
}

/**
 * Delete an entry, or optionally all entries.
 *
 * @param string $user
 * @param int $id
 * @param int $series
 * @param int $all
 * @param bool $roomadminoverride
 * @return bool
 */
function mrbsDelEntry($user, $id, $series, $all, $roomadminoverride = false) {
    global $DB;

    $repeat_id = $DB->get_field('block_mrbs_entry', 'repeat_id', array('id' => $id));
    if ($repeat_id === false || $repeat_id < 0) {
        return false;
    }

    if ($series) {
        $params = array('repeat_id' => $repeat_id);
    } else {
        $params = array('id' => $id);
    }

    $removed = 0;
    $entries = $DB->get_records('block_mrbs_entry', $params);
    foreach ($entries as $entry) {
        if (!$roomadminoverride && !getWritable($entry->create_by, $user)) {
            continue;
        }

        if ($series && $entry->entry_type == 2 && !$all) {
            continue;
        }

        $DB->delete_records('block_mrbs_entry', array('id' => $entry->id));
        $removed++;
    }

    if ($repeat_id > 0 && $DB->count_records('block_mrbs_entry', array('repeat_id' => $repeat_id)) == 0) {
        $DB->delete_records('block_mrbs_repeat', array('id' => $repeat_id));
    }

    return ($removed > 0);
}

/**
 * Create a single (non-repeating) entry in the database.
 *
 * @return int
 */
function mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
                               $owner, $name, $type, $description, $oldid = 0, $roomchange = false) {
    global $DB;

    $add = new stdClass();
    $add->start_time = $starttime;
    $add->end_time = $endtime;
    $add->entry_type = $entry_type;
    $add->repeat_id = $repeat_id;
    $add->room_id = $room_id;
    $add->create_by = $owner;
    $add->name = $name;
    $add->type = $type;
    $add->description = $description;
    $add->timestamp = time();
    $add->roomchange = $roomchange ? 1 : 0;

    if ($endtime > $starttime) {
        if ($oldid) {
            $add->id = $oldid;
            $DB->update_record('block_mrbs_entry', $add);
            return (int)$oldid;
        } else {
            return (int)$DB->insert_record('block_mrbs_entry', $add);
        }
    }

    return 0;
}

/**
 * Creates a repeat entry in the database.
 *
 * @return int
 */
function mrbsCreateRepeatEntry($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt,
                               $room_id, $owner, $name, $type, $description, $rep_num_weeks, $oldrepeatid = 0) {
    global $DB;

    $add = new stdClass();

    $add->start_time = $starttime;
    $add->end_time = $endtime;
    $add->rep_type = $rep_type;
    $add->end_date = $rep_enddate;
    $add->room_id = $room_id;
    $add->create_by = $owner;
    $add->type = $type;
    $add->name = $name;
    $add->timestamp = time();

    if (!empty($rep_opt)) {
        $add->rep_opt = $rep_opt;
    } else {
        $add->rep_opt = "0";
    }
    if (!empty($description)) {
        $add->description = $description;
    }
    if (!empty($rep_num_weeks)) {
        $add->rep_num_weeks = $rep_num_weeks;
    }

    if ($oldrepeatid) {
        $add->id = $oldrepeatid;
        $DB->update_record('block_mrbs_repeat', $add);
    } else {
        $add->id = $DB->insert_record('block_mrbs_repeat', $add);
    }

    return (int)$add->id;
}

/**
 * Find the same day of the week in next month, same week number.
 *
 * @param int $time
 * @return int
 */
function same_day_next_month($time) {
    global $_initial_weeknumber;

    $days_in_month = date("t", $time);
    $day = date("d", $time);
    $weeknumber = (int)(($day - 1) / 7) + 1;
    $temp1 = ($day + 7 * (5 - $weeknumber) <= $days_in_month);

    $next_month = date("n", mktime(11, 0, 0, date("n", $time), $day + 35, date("Y", $time)))
        + (date("n", mktime(11, 0, 0, date("n", $time), $day + 35, date("Y", $time))) < date("n", $time)) * 12;

    $days_jump = 28 + (($temp1 && !($next_month - date("n", $time) - 1)) * 7);

    $days_jump += 7 * (($_initial_weeknumber == 5)
        && (date("n", mktime(11, 0, 0, date("n", $time), $day + $days_jump, date("Y", $time)))
            == date("n", mktime(11, 0, 0, date("n", $time), $day + $days_jump + 7, date("Y", $time)))));

    return $days_jump;
}

/**
 * Returns a list of the repeating entries.
 *
 * @return array
 */
function mrbsGetRepeatEntryList($time, $enddate, $rep_type, $rep_opt, $max_ittr, $rep_num_weeks) {
    $sec = date("s", $time);
    $min = date("i", $time);
    $hour = date("G", $time);
    $day = date("d", $time);
    $month = date("m", $time);
    $year = date("Y", $time);

    global $_initial_weeknumber;
    $_initial_weeknumber = (int)(($day - 1) / 7) + 1;
    $week_num = 0;
    $start_day = date('w', mktime($hour, $min, $sec, $month, $day, $year));
    $cur_day = $start_day;

    $entries = array();

    for ($i = 0; $i < $max_ittr; $i++) {
        $time = mktime($hour, $min, $sec, $month, $day, $year);
        if ($time > $enddate) {
            break;
        }

        $entries[] = $time;

        switch ($rep_type) {
            case 1:
                $day += 1;
                break;

            case 2:
                $j = $cur_day = date("w", $entries[$i]);
                while ((($j = ($j + 1) % 7) != $cur_day) && empty($rep_opt[$j])) {
                    $day += 1;
                }
                $day += 1;
                break;

            case 3:
                $month += 1;
                break;

            case 4:
                $year += 1;
                break;

            case 5:
                $day += same_day_next_month($time);
                break;

            case 6:
                while (true) {
                    $day++;
                    $cur_day = ($cur_day + 1) % 7;

                    if (($cur_day % 7) == $start_day) {
                        $week_num++;
                    }

                    if (($rep_num_weeks > 0) &&
                        ($week_num % $rep_num_weeks == 0) &&
                        !empty($rep_opt[$cur_day])) {
                        break;
                    }
                }
                break;

            default:
                return array();
        }
    }

    return $entries;
}

/**
 * Creates a repeat entry in the database + all the repeating entries.
 *
 * @return stdClass
 */
function mrbsCreateRepeatingEntrys($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt,
                                   $room_id, $owner, $name, $type, $description, $rep_num_weeks, $roomchange = false, $oldid = 0) {
    global $max_rep_entrys, $DB;

    $ret = new stdClass();
    $ret->id = 0;
    $ret->repeating = 1;
    $ret->requested = 0;
    $ret->created = 0;
    $ret->lasttime = null;

    $reps = mrbsGetRepeatEntryList($starttime, $rep_enddate, $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks);
    $ret->requested = count($reps);
    if ($ret->requested > $max_rep_entrys) {
        return $ret;
    }

    $repeatid = 0;
    if ($oldid) {
        $repeatid = $DB->get_field('block_mrbs_entry', 'repeat_id', array('id' => $oldid));
    }

    if (empty($reps)) {
        if ($repeatid) {
            $DB->delete_records_select('block_mrbs_entry', 'repeat_id = :repeatid AND id <> :oldid', compact('repeatid', 'oldid'));
            $DB->delete_records('block_mrbs_repeat', array('id' => $repeatid));
        }

        $ret->id = mrbsCreateSingleEntry($starttime, $endtime, 0, 0, $room_id, $owner, $name, $type, $description, $oldid, $roomchange);
        $ret->repeating = 0;
        $ret->requested = 1;
        $ret->created = 1;
        $ret->lasttime = $starttime;
        return $ret;
    }

    $ret->id = mrbsCreateRepeatEntry(
        $starttime,
        $endtime,
        $rep_type,
        $rep_enddate,
        $rep_opt,
        $room_id,
        $owner,
        $name,
        $type,
        $description,
        $rep_num_weeks,
        $repeatid
    );

    if ($ret->id) {
        $oldids = array();
        if ($repeatid) {
            $oldids = $DB->get_fieldset_sql(
                'SELECT id FROM {block_mrbs_entry} WHERE repeat_id = ? ORDER BY start_time',
                array($repeatid)
            );
        }

        for ($i = 0; $i < count($reps); $i++) {
            $diff = $endtime - $starttime;
            $diff += cross_dst($reps[$i], $reps[$i] + $diff);

            if (!check_max_advance_days_timestamp($reps[$i])) {
                break;
            }

            if ($i < count($oldids)) {
                $updateid = $oldids[$i];
            } else {
                $updateid = 0;
            }

            mrbsCreateSingleEntry(
                $reps[$i],
                $reps[$i] + $diff,
                1,
                $ret->id,
                $room_id,
                $owner,
                $name,
                $type,
                $description,
                $updateid,
                $roomchange
            );
            $ret->lasttime = $reps[$i];
            $ret->created++;
        }

        for ($i = count($reps); $i < count($oldids); $i++) {
            $DB->delete_records('block_mrbs_entry', array('id' => $oldids[$i]));
        }
    }

    return $ret;
}

/**
 * Get the booking entry.
 *
 * @param int $id
 * @return stdClass|false
 */
function mrbsGetEntryInfo($id) {
    global $DB;
    return $DB->get_record('block_mrbs_entry', array('id' => $id));
}

/**
 * Get area id for room.
 *
 * @param int $id
 * @return mixed
 */
function mrbsGetRoomArea($id) {
    global $DB;
    return $DB->get_field('block_mrbs_room', 'area_id', array('id' => $id));
}