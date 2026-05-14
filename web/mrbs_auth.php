<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// Include the authentication wrappers.
include "auth_{$auth['type']}.php";
if (isset($auth['session'])) {
    include "session_{$auth['session']}.php";
}

/**
 * Check whether the current user has the required MRBS access level.
 *
 * @param int $level
 * @return bool
 */
function getAuthorised($level) {
    $user = getUserName();

    if (!isset($user) || $user === '') {
        authGet();
        return false;
    }

    return (authGetUserLevel($user) >= $level);
}

/**
 * Determines if a user is able to modify an entry.
 *
 * @param string $creator Username of entry creator.
 * @param string $user Username of current user.
 * @return bool
 */
function getWritable($creator, $user) {
    if (strcasecmp($creator, $user) === 0) {
        return true;
    }

    if (authGetUserLevel($user) >= 2) {
        return true;
    }

    return false;
}

/**
 * Displays an appropriate message when access has been denied.
 *
 * @param int|null $day
 * @param int|null $month
 * @param int|null $year
 * @param int|null $area
 * @return void
 */
function showAccessDenied($day, $month, $year, $area) {
    global $OUTPUT;

    print_header_mrbs($day, $month, $year, $area);
    echo $OUTPUT->box(
        get_string('accessdenied', 'block_mrbs') . ' ' . get_string('norights', 'block_mrbs'),
        'generalbox boxaligncenter'
    );
    echo $OUTPUT->footer();
}