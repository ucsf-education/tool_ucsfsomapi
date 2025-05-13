<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * The tool_ucsfsomapi set_question_attempt_mark_called event.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_ucsfsomapi\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The tool_ucsfsomapi set_question_attempt_mark_called event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int quizid: the id of the quiz.
 *      - int attemptid: the id of the attempt.
 *      - int slot: the question number in the attempt.
 * }
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_question_attempt_mark_called extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->context = \context_system::instance();
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventset_question_attempt_mark_called', 'tool_ucsfsomapi');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' called set_question_attempt_mark for the question attempt id '$this->objectid' to" .
            "set mark to '{$this->other['mark']}' with grader's comment '{$this->other['comment']}'.";
    }

   /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['mark'])) {
            throw new \coding_exception('The \'mark\' value must be set in other.');
        }

        if (!isset($this->other['comment'])) {
            throw new \coding_exception('The \'comment\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return ['db' => 'question', 'restore' => 'question'];
    }

    public static function get_other_mapping() {
        return false;
    }
}
