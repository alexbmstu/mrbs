<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/ddllib.php');

/**
 * Rename an old unprefixed table to the Moodle-prefixed name, if needed.
 *
 * @param database_manager $dbman
 * @param string $tablename Table name without prefix.
 * @return void
 */
function renameifexists(database_manager $dbman, $tablename) {
    global $DB, $CFG;

    $oldname = $tablename;
    $newname = $CFG->prefix . $tablename;

    $tbl = $DB->get_records_sql(
        'SELECT table_name
           FROM information_schema.tables
          WHERE table_name = ? AND table_schema = ?',
        array($oldname, $CFG->dbname)
    );

    if (empty($tbl)) {
        return;
    }

    $newtbl = new xmldb_table($tablename);
    if ($dbman->table_exists($newtbl)) {
        $newhasdata = $DB->count_records($tablename);

        if (!$newhasdata) {
            $dbman->drop_table($newtbl);
        } else {
            $oldhasdata = $DB->count_records_sql('SELECT COUNT(*) FROM ' . $oldname);

            if (!$oldhasdata) {
                $DB->execute('DROP TABLE ' . $oldname);
                return;
            } else {
                throw new moodle_exception(
                    'Database tables "' . $oldname . '" and "' . $newname . '" both exist and both contain data. ' .
                    'Please remove one of them manually before continuing the upgrade.'
                );
            }
        }
    }

    $DB->execute('ALTER TABLE ' . $oldname . ' RENAME TO ' . $newname);
}

/**
 * Convert timestamp field to integer unix timestamp for old MySQL upgrades.
 *
 * @param string $tablename
 * @param string $fieldname
 * @return void
 */
function block_mrbs_convert_timestamp($tablename, $fieldname) {
    global $DB;

    $fielddef = $DB->get_record_sql(
        "SHOW COLUMNS FROM {" . $tablename . "} LIKE '" . $fieldname . "'"
    );

    if (!$fielddef) {
        throw new moodle_exception("$fieldname does not exist in table $tablename");
    }

    if ($fielddef->Type !== 'timestamp' && $fielddef->type !== 'timestamp') {
        return;
    }

    $dbman = $DB->get_manager();
    $tempfield = $fieldname . '_conv';
    $table = new xmldb_table($tablename);
    $field = new xmldb_field($tempfield, XMLDB_TYPE_INTEGER, '11', null, null, null, null, $fieldname);

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $DB->execute('UPDATE {' . $tablename . '} SET ' . $tempfield . ' = UNIX_TIMESTAMP(' . $fieldname . ')');

    $backupfield = $fieldname . '_backup';
    $DB->execute('ALTER TABLE {' . $tablename . '} CHANGE ' . $fieldname . ' ' . $backupfield . ' TIMESTAMP');
    $dbman->rename_field($table, $field, $fieldname);
}

/**
 * Upgrade script for block_mrbs.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_mrbs_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011050600) {
        renameifexists($dbman, 'mrbs_area');
        renameifexists($dbman, 'mrbs_entry');
        renameifexists($dbman, 'mrbs_repeat');
        renameifexists($dbman, 'mrbs_room');

        upgrade_block_savepoint(true, 2011050600, 'mrbs');
    }

    if ($oldversion < 2011111200) {
        $table = new xmldb_table('mrbs_room');
        $field = new xmldb_field('booking_users', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'room_admin_email');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2011111200, 'mrbs');
    }

    if ($oldversion < 2012021300) {
        $table = new xmldb_table('mrbs_area');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'block_mrbs_area');
        }

        $table = new xmldb_table('mrbs_entry');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'block_mrbs_entry');
        }

        $table = new xmldb_table('mrbs_repeat');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'block_mrbs_repeat');
        }

        $table = new xmldb_table('mrbs_room');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'block_mrbs_room');
        }

        upgrade_block_savepoint(true, 2012021300, 'mrbs');
    }

    if ($oldversion < 2012022700) {
        $table = new xmldb_table('block_mrbs_entry');
        $field = new xmldb_field('roomchange', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'description');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2012022700, 'mrbs');
    }

    if ($oldversion < 2012091200) {
        if ($DB->get_dbfamily() === 'mysql') {
            block_mrbs_convert_timestamp('block_mrbs_entry', 'start_time');
            block_mrbs_convert_timestamp('block_mrbs_entry', 'end_time');
            block_mrbs_convert_timestamp('block_mrbs_entry', 'timestamp');

            block_mrbs_convert_timestamp('block_mrbs_repeat', 'start_time');
            block_mrbs_convert_timestamp('block_mrbs_repeat', 'end_time');
            block_mrbs_convert_timestamp('block_mrbs_repeat', 'end_date');
            block_mrbs_convert_timestamp('block_mrbs_repeat', 'timestamp');
        }

        upgrade_block_savepoint(true, 2012091200, 'mrbs');
    }

    return true;
}