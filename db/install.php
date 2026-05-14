<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Post installation hook for block_mrbs.
 *
 * @return void
 */
function xmldb_block_mrbs_install() {
    global $DB;

    $context = context_system::instance();

    if (!$DB->record_exists('role', array('shortname' => 'mrbsviewer'))) {
        $mrbsviewerid = create_role(
            get_string('mrbsviewer', 'block_mrbs'),
            'mrbsviewer',
            get_string('mrbsviewer_desc', 'block_mrbs')
        );
        set_role_contextlevels($mrbsviewerid, array(CONTEXT_SYSTEM));
        assign_capability('block/mrbs:viewmrbs', CAP_ALLOW, $mrbsviewerid, $context->id, true);
    }

    if (!$DB->record_exists('role', array('shortname' => 'mrbseditor'))) {
        $mrbseditorid = create_role(
            get_string('mrbseditor', 'block_mrbs'),
            'mrbseditor',
            get_string('mrbseditor_desc', 'block_mrbs')
        );
        set_role_contextlevels($mrbseditorid, array(CONTEXT_SYSTEM));
        assign_capability('block/mrbs:viewmrbs', CAP_ALLOW, $mrbseditorid, $context->id, true);
        assign_capability('block/mrbs:editmrbs', CAP_ALLOW, $mrbseditorid, $context->id, true);
    }

    if (!$DB->record_exists('role', array('shortname' => 'mrbsadmin'))) {
        $mrbsadminid = create_role(
            get_string('mrbsadmin', 'block_mrbs'),
            'mrbsadmin',
            get_string('mrbsadmin_desc', 'block_mrbs')
        );
        set_role_contextlevels($mrbsadminid, array(CONTEXT_SYSTEM));
        assign_capability('block/mrbs:viewmrbs', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:editmrbs', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:administermrbs', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:viewalltt', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:forcebook', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:doublebook', CAP_ALLOW, $mrbsadminid, $context->id, true);
        assign_capability('block/mrbs:ignoremaxadvancedays', CAP_ALLOW, $mrbsadminid, $context->id, true);
    }

    $context->mark_dirty();
}