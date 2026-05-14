<?php
/**
 * The block_mrbs booking created event.
 *
 * @package    block_mrbs
 * @copyright  2014 Davo Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mrbs\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The block_mrbs booking created event.
 *
 * @package    block_mrbs
 * @since      Moodle 2.7
 * @copyright  2014 Davo Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_created extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'block_mrbs_entry';
        $this->context = \context_system::instance();
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbookingcreated', 'block_mrbs');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' has created a booking in '{$this->other['room']}' for '{$this->other['name']}'";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/mrbs/web/view_entry.php', array('id' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        global $CFG;

        return array(
            SITEID,
            'mrbs',
            'add booking',
            $CFG->wwwroot . '/blocks/mrbs/web/view_entry.php?id=' . $this->objectid,
            $this->other['name']
        );
    }

    /**
     * Validate event data.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['name'])) {
            throw new \coding_exception('Must specify the name of the booking as \'other[\'name\']\'.');
        }

        if (!isset($this->other['room'])) {
            throw new \coding_exception('Must specify the room for the booking as \'other[\'room\']\'.');
        }
    }
}