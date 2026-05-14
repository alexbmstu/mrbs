<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

function minicals($year, $month, $day, $area, $room, $dmy, $usertt = false) {
    // PHP Calendar Class
    //
    // Copyright David Wilkinson 2000. All Rights reserved.
    //
    // This software may be used, modified and distributed freely
    // providing this copyright notice remains intact at the head
    // of the file.
    //
    // This software is freeware. The author accepts no liability for
    // any loss or damages whatsoever incurred directly or indirectly
    // from the use of this script.
    //
    // URL:   http://www.cascade.org.uk/software/php/calendar/
    // Email: davidw@cascade.org.uk

    class Calendar {
        public $month;
        public $year;
        public $day;
        public $h;
        public $area;
        public $room;
        public $dmy;
        public $usertt;

        public function __construct($day, $month, $year, $h, $area, $room, $dmy, $usertt) {
            $this->day = $day;
            $this->month = $month;
            $this->year = $year;
            $this->h = $h;
            $this->area = $area;
            $this->room = $room;
            $this->dmy = $dmy;
            $this->usertt = $usertt;
        }

        public function getCalendarLink($month, $year) {
            return "";
        }

        public function getDateLink($day, $month, $year) {
            $isuser = '';
            if (!empty($this->usertt)) {
                $isuser = 'user';
            }

            $returl = new moodle_url('/blocks/mrbs/web/' . $isuser . $this->dmy . '.php', array(
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'area' => $this->area
            ));

            if (!empty($this->usertt)) {
                $returl->param('user', $this->usertt);
            }

            if (!empty($this->room)) {
                $returl->param('room', $this->room);
            }

            return $returl;
        }

        public function getDaysInMonth($month, $year) {
            if ($month < 1 || $month > 12) {
                return 0;
            }

            $days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
            $d = $days[$month - 1];

            if ($month == 2) {
                if ($year % 4 == 0) {
                    if ($year % 100 == 0) {
                        if ($year % 400 == 0) {
                            $d = 29;
                        }
                    } else {
                        $d = 29;
                    }
                }
            }

            return $d;
        }

        public function getFirstDays() {
            global $weekstarts;

            $basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
            $s = '';

            for ($i = 0; $i < 7; $i++) {
                $show = $basetime + ($i * 24 * 60 * 60);
                $fl = strftime('%a', $show);
                $s .= '<td align="center" valign="top" class="calendarHeader">' . $fl . "</td>\n";
            }

            return $s;
        }

        public function getHTML() {
            global $weekstarts;
            global $day;
            global $month;

            if (!isset($weekstarts)) {
                $weekstarts = 0;
            }

            $s = '';
            $daysinmonth = $this->getDaysInMonth($this->month, $this->year);

            if ($this->month - 1 > 0) {
                $prevmonth = $this->month - 1;
                $prevyear = $this->year;
            } else {
                $prevmonth = 12;
                $prevyear = $this->year - 1;
            }

            $daysinprevmonth = $this->getDaysInMonth($prevmonth, $prevyear);
            $date = mktime(12, 0, 0, $this->month, 1, $this->year);

            $first = (strftime('%w', $date) + 7 - $weekstarts) % 7;
            $monthname = userdate($date, "%B");

            $s .= "<table class=\"calendar\">\n";
            $s .= "<tr>\n";
            $s .= '<td align="center" valign="top" class="calendarHeader" colspan="7">' .
                $monthname . '&nbsp;' . $this->year . "</td>\n";
            $s .= "</tr>\n";

            $s .= "<tr>\n";
            $s .= $this->getFirstDays();
            $s .= "</tr>\n";

            $d = 1 - $first;
            $days_to_highlight = $d + 7;

            while ($d <= $daysinmonth) {
                $s .= "<tr>\n";

                for ($i = 0; $i < 7; $i++) {
                    $s .= '<td class="calendar" align="center" valign="top">';

                    if ($d > 0 && $d <= $daysinmonth) {
                        $link = $this->getDateLink($d, $this->month, $this->year);
                        $d_week = $d - 7;

                        if ($this->dmy == 'day') {
                            if (($d == $this->day) && ($this->h)) {
                                $s .= '<a href="' . $link . '"><span class="calendarHighlight">' . $d . '</span></a>';
                            } else {
                                $s .= '<a href="' . $link . '">' . $d . '</a>';
                            }
                        } else if ($this->dmy == 'week') {
                            if (($this->day <= $d) && ($this->day > $d_week) && ($this->h)) {
                                $s .= '<a href="' . $link . '"><span class="calendarHighlight">' . $d . '</span></a>';
                            } elseif (($this->day < $days_to_highlight) &&
                                      ($d < $days_to_highlight) &&
                                      (($day - $daysinprevmonth) > (-6)) &&
                                      ($this->month == (($month + 1) % 12)) &&
                                      ($first != 0)) {
                                $s .= '<a href="' . $link . '"><span class="calendarHighlight">' . $d . '</span></a>';
                            } else {
                                $s .= '<a href="' . $link . '">' . $d . '</a>';
                            }
                        } elseif ($this->dmy == 'month') {
                            if ($this->h) {
                                $s .= '<a href="' . $link . '"><span class="calendarHighlight">' . $d . '</span></a>';
                            } else {
                                $s .= '<a href="' . $link . '">' . $d . '</a>';
                            }
                        } else {
                            $s .= $d;
                        }
                    } else {
                        $s .= '&nbsp;';
                    }

                    $s .= "</td>\n";
                    $d++;
                }

                $s .= "</tr>\n";
            }

            $s .= "</table>\n";

            return $s;
        }
    }

    $lastmonth = mktime(12, 0, 0, $month - 1, 1, $year);
    $thismonth = mktime(12, 0, 0, $month, $day, $year);
    $nextmonth = mktime(12, 0, 0, $month + 1, 1, $year);

    echo "<td>";
    $cal = new Calendar(date("d", $lastmonth), date("m", $lastmonth), date("Y", $lastmonth), 0, $area, $room, $dmy, $usertt);
    echo $cal->getHTML();
    echo "</td>";

    echo "<td>";
    $cal = new Calendar(date("d", $thismonth), date("m", $thismonth), date("Y", $thismonth), 1, $area, $room, $dmy, $usertt);
    echo $cal->getHTML();
    echo "</td>";

    echo "<td>";
    $cal = new Calendar(date("d", $nextmonth), date("m", $nextmonth), date("Y", $nextmonth), 0, $area, $room, $dmy, $usertt);
    echo $cal->getHTML();
    echo "</td>";
}