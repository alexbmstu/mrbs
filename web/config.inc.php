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

###########################################################################
#   MRBS Configuration File
#   You shouldn't have to modify this file as all options can be set via Moodle
###########################################################################

// For integration with Moodle.
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $CFG, $SITE, $USER;

$cfg_mrbs = get_config('block/mrbs'); // Get Moodle config settings for the MRBS block.

###################
# Database settings
###################
// All database calls now use Moodle DB directly - no settings needed here.

################################
# Site identification information
#################################
$mrbs_admin = $cfg_mrbs->admin ?? '';
$mrbs_admin_email = $cfg_mrbs->admin_email ?? '';

$mrbs_company = $SITE->fullname ?? '';
$mrbs_company_url = $CFG->wwwroot ?? '';

$url_base = $cfg_mrbs->serverpath ?? '';

###################
# Calendar settings
###################

$enable_periods = $cfg_mrbs->enable_periods ?? 0;

if ((int)$enable_periods === 0) {
    $resolution = $cfg_mrbs->resolution ?? 1800;
    $morningstarts = $cfg_mrbs->morningstarts ?? 8;
    $eveningends = $cfg_mrbs->eveningends ?? 18;
    $morningstarts_minutes = $cfg_mrbs->morningstarts_min ?? 0;
    $eveningends_minutes = $cfg_mrbs->eveningends_min ?? 0;
}

$periods = array();

if (empty($cfg_mrbs->periods)) {
    $periods[] = "Period&nbsp;1";
    $periods[] = "Period&nbsp;2";
    $periods[] = "Period&nbsp;3";
    $periods[] = "Period&nbsp;4";
    $periods[] = "Period&nbsp;5";
    $periods[] = "Period&nbsp;6";
    $periods[] = "Period&nbsp;7";
    $periods[] = "Period&nbsp;8";
    $periods[] = "Period&nbsp;9";
    $periods[] = "Period&nbsp;10";
    $periods[] = "Period&nbsp;11";
    $periods[] = "Period&nbsp;12";
} else {
    $pds = explode("\n", $cfg_mrbs->periods);
    foreach ($pds as $pd) {
        $pd = trim($pd);
        if ($pd !== '') {
            $periods[] = $pd;
        }
    }
    if (empty($periods)) {
        $periods[] = "Period&nbsp;1";
        $periods[] = "Period&nbsp;2";
        $periods[] = "Period&nbsp;3";
        $periods[] = "Period&nbsp;4";
        $periods[] = "Period&nbsp;5";
        $periods[] = "Period&nbsp;6";
        $periods[] = "Period&nbsp;7";
        $periods[] = "Period&nbsp;8";
        $periods[] = "Period&nbsp;9";
        $periods[] = "Period&nbsp;10";
        $periods[] = "Period&nbsp;11";
        $periods[] = "Period&nbsp;12";
    }
}

$weekstarts = $cfg_mrbs->weekstarts ?? 0;
$dateformat = $cfg_mrbs->dateformat ?? 0;
$twentyfourhour_format = $cfg_mrbs->timeformat ?? 1;

########################
# Miscellaneous settings
########################

$max_rep_entrys = ($cfg_mrbs->max_rep_entrys ?? 365) + 1;
$max_advance_days = $cfg_mrbs->max_advance_days ?? -1;
$default_report_days = $cfg_mrbs->default_report_days ?? 7;

$search = array();
$search["count"] = $cfg_mrbs->search_count ?? 25;

$area_list_format = $cfg_mrbs->area_list_format ?? 'list';
$monthly_view_entries_details = $cfg_mrbs->monthly_view_entries_details ?? 'both';
$view_week_number = $cfg_mrbs->view_week_number ?? 0;
$times_right_side = $cfg_mrbs->times_right_side ?? 0;

$javascript_cursor = $cfg_mrbs->javascript_cursor ?? 0;
$show_plus_link = $cfg_mrbs->show_plus_link ?? 0;
$highlight_method = $cfg_mrbs->highlight_method ?? 'hybrid';

$default_view = $cfg_mrbs->default_view ?? 'day';
$default_room = $cfg_mrbs->default_room ?? 0;

###############################################
# Authentication settings
###############################################
$auth = array();
$auth["session"] = "php";
$auth["type"] = "moodle";

if (($CFG->sessioncookiepath ?? '/') == '/') {
    $cookie_path_override = '';
} else {
    $cookie_path_override = $CFG->sessioncookiepath;
}

###############################################
# Email settings
###############################################

define("MAIL_ADMIN_ON_BOOKINGS", $cfg_mrbs->mail_admin_on_bookings ?? 0);
define("MAIL_AREA_ADMIN_ON_BOOKINGS", $cfg_mrbs->mail_area_admin_on_bookings ?? 0);
define("MAIL_ROOM_ADMIN_ON_BOOKINGS", $cfg_mrbs->mail_room_admin_on_bookings ?? 0);
define("MAIL_ADMIN_ON_DELETE", $cfg_mrbs->mail_admin_on_delete ?? 0);
define("MAIL_ADMIN_ALL", $cfg_mrbs->mail_admin_all ?? 0);
define("MAIL_DETAILS", $cfg_mrbs->mail_details ?? 0);
define("MAIL_BOOKER", $cfg_mrbs->mail_booker ?? 0);

# Miscellaneous settings

if (!empty($USER->lang)) {
    define("MAIL_ADMIN_LANG", substr($USER->lang, 0, 2));
} else {
    define("MAIL_ADMIN_LANG", 'en');
}

define("MAIL_FROM", $cfg_mrbs->mail_from ?? $mrbs_admin_email);
define("MAIL_RECIPIENTS", $cfg_mrbs->mail_recipients ?? $mrbs_admin_email);
define("MAIL_CC", $cfg_mrbs->mail_cc ?? '');

$mail = array();
$mail["subject"] = get_string('mail_subject', 'block_mrbs', $mrbs_company);
$mail["subject_delete"] = get_string('mail_subject_delete', 'block_mrbs', $mrbs_company);
$mail["new_entry"] = get_string('mail_new_entry', 'block_mrbs');
$mail["changed_entry"] = get_string('mail_changed_entry', 'block_mrbs');
$mail["deleted_entry"] = get_string('mail_deleted_entry', 'block_mrbs');

##########
# Language
##########

if (empty($locale)) {
    if (!empty($USER->lang)) {
        $locale = substr($USER->lang, 0, 2);
    } else {
        $locale = 'en';
    }
}

$windows_locale = "eng";
$unicode_encoding = 1;

if (!empty($USER->lang)) {
    $default_language_tokens = substr($USER->lang, 0, 2);
} else {
    $default_language_tokens = 'en';
}

$disable_automatic_language_changing = 1;
$override_locale = "";

#############
# Entry Types
#############

$typel = array();
$typel["A"] = $cfg_mrbs->entry_type_a ?? '';
$typel["B"] = $cfg_mrbs->entry_type_b ?? '';
$typel["C"] = $cfg_mrbs->entry_type_c ?? '';
$typel["D"] = $cfg_mrbs->entry_type_d ?? '';
$typel["E"] = $cfg_mrbs->entry_type_e ?? '';
$typel["F"] = $cfg_mrbs->entry_type_f ?? '';
$typel["G"] = $cfg_mrbs->entry_type_g ?? '';
$typel["H"] = $cfg_mrbs->entry_type_h ?? '';
$typel["I"] = $cfg_mrbs->entry_type_i ?? '';
$typel["J"] = $cfg_mrbs->entry_type_j ?? '';

if (!empty($cfg_mrbs->cronfile)) {
    $typel["K"] = get_string('importedbooking', 'block_mrbs');
    $typel["L"] = get_string('importedbookingmoved', 'block_mrbs');
}

$typel["U"] = get_string('unconfirmedbooking', 'block_mrbs');

// WARNING: DO NOT USE TYPE M, type M is used by import script and will delete
// other type M bookings.