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

/*
 * Assigns MRBS access levels based on the user status
 * within the Moodle installation.
 *
 * MRBS levels:
 * 0 - View only
 * 1 - View and make bookings
 * 2 - Full administration - add rooms and bookings
 *
 * Moodle integration:
 * Defines one of the above levels using Moodle capabilities.
 *
 * Used in conjunction with session_moodle.inc
 *
 * To use this authentication scheme set the following
 * in config.inc.php:
 *
 * $auth["type"]    = "moodle";
 * $auth["session"] = "moodle";
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

/**
 * Validate a user.
 *
 * Authentication is delegated to Moodle session handling,
 * so if the user reached this code path, we accept them.
 *
 * @param string $user
 * @param string $pass
 * @return bool
 */
function authValidateUser($user, $pass) {
    return true;
}

/**
 * Get MRBS access level for the current Moodle user.
 *
 * @param string $user
 * @return int
 */
function authGetUserLevel($user) {
    $context = context_system::instance();

    if (has_capability('block/mrbs:administermrbs', $context)) {
        return 2;
    }

    if (has_capability('block/mrbs:editmrbs', $context)) {
        return 1;
    }

    if (has_capability('block/mrbs:editmrbsunconfirmed', $context, null, false)) {
        return 1;
    }

    if (has_capability('block/mrbs:viewmrbs', $context)) {
        return 0;
    }

    return 0;
}