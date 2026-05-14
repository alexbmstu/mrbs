<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/mrbs:viewmrbs' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
    ),

    'block/mrbs:editmrbs' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
    ),

    'block/mrbs:administermrbs' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
    ),

    'block/mrbs:viewalltt' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
    ),

    'block/mrbs:forcebook' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'block/mrbs:administermrbs',
    ),

    'block/mrbs:doublebook' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'block/mrbs:administermrbs',
    ),

    // Limits users to only creating 'unconfirmed' bookings
    // (unless they are the room administrator).
    'block/mrbs:editmrbsunconfirmed' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
        'clonepermissionsfrom' => 'block/mrbs:editmrbs',
    ),

    'block/mrbs:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),

    'block/mrbs:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ),

    'block/mrbs:ignoremaxadvancedays' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'block/mrbs:administermrbs',
    ),
);