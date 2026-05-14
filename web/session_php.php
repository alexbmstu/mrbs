<?php
/*****************************************************************************\
 *                                                                             *
 *   File name       session_php.php                                           *
 *                                                                             *
 *   Description     Use Moodle session/auth handling                          *
 *                                                                             *
\*****************************************************************************/

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $CFG, $USER, $SESSION;

/*
 * Legacy entry point: Action=SetName.
 * In Moodle 4.x we should delegate authentication to require_login().
 */
$action = optional_param('Action', '', PARAM_ALPHA);
$targeturl = optional_param('TargetURL', '', PARAM_LOCALURL);

if ($action === 'SetName') {
    if (!empty($targeturl)) {
        $SESSION->wantsurl = new moodle_url($targeturl)->out(false);
    }
    require_login();
    redirect(!empty($targeturl) ? $targeturl : new moodle_url('/'));
}

/*
 * Display the login form.
 * Will eventually return to $TargetURL.
 */
function printLoginForm($TargetURL) {
    global $SESSION;

    if (!empty($TargetURL)) {
        $SESSION->wantsurl = $TargetURL;
    }
    require_login();
}

/**
 * Request the user name/password.
 *
 * @return void
 */
function authGet() {
    global $SESSION;

    print_header_mrbs(0, 0, 0, 0);
    echo '<p>' . get_string('norights', 'block_mrbs') . "</p>\n";

    $scriptname = $_SERVER['SCRIPT_NAME'] ?? '';
    $querystring = $_SERVER['QUERY_STRING'] ?? '';

    $targeturl = basename($scriptname);
    if (!empty($querystring)) {
        $targeturl .= '?' . $querystring;
    }

    $SESSION->wantsurl = $targeturl;
    require_login();
    exit();
}

function getUserName() {
    global $USER;

    if (isloggedin() && !isguestuser()) {
        return $USER->username;
    }
    return null;
}

// Print the logon entry on the top banner.
function PrintLogonBox() {
    global $CFG, $USER, $user_list_link, $day, $month, $year;

    $scriptname = $_SERVER['SCRIPT_NAME'] ?? '';
    $querystring = $_SERVER['QUERY_STRING'] ?? '';

    $targeturl = basename($scriptname);
    if (!empty($querystring)) {
        $targeturl .= '?' . $querystring;
    }

    $user = getUserName();

    if (!empty($user)) {
        $search_string = "report.php?From_day=$day&From_month=$month&"
            . "From_year=$year&To_day=1&To_month=12&To_year=2030&areamatch=&"
            . "roommatch=&namematch=&descrmatch=&summarize=1&sortby=r&display=d&"
            . "sumby=d&creatormatch=" . urlencode($user);

        echo '<td class="banner" bgcolor="#C0E0FF" align="center">';
        echo '<a name="logonBox" href="' . s($search_string) . '" title="'
            . s(get_string('show_my_entries', 'block_mrbs')) . '">'
            . get_string('you_are', 'block_mrbs') . ' ' . s($user) . '</a><br>';

        $logouturl = new moodle_url('/login/logout.php', array(
            'sesskey' => sesskey()
        ));
        echo '<form method="post" action="' . $logouturl->out(false) . '">';
        echo '<input type="hidden" name="loginpage" value="1" />';
        echo '<input type="submit" value=" ' . s(get_string('logout')) . ' " />';
        echo '</form>';

        if (isset($user_list_link)) {
            echo '<br><a href="' . s($user_list_link) . '">' . get_string('user_list') . "</a><br>\n";
        }

        echo '</td>';
    } else {
        echo '<td class="banner" bgcolor="#C0E0FF" align="center">';
        echo '<a name="logonBox" href="' . s(get_login_url()) . '">'
            . s(get_string('usernamenotfound')) . '</a><br>';
        echo '<form method="get" action="' . s(get_login_url()) . '">';
        echo '<input type="hidden" name="wantsurl" value="' . s($targeturl) . '" />';
        echo '<input type="submit" value=" ' . s(get_string('login')) . ' " />';
        echo '</form>';

        if (isset($user_list_link)) {
            echo '<br><a href="' . s($user_list_link) . '">' . get_string('user_list') . "</a><br>\n";
        }

        echo '</td>';
    }
}