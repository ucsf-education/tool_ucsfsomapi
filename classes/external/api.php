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
 * Web service functions for the tool_ucsfsomapi plugin.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ucsfsomapi\external;

use coding_exception;
use context_course;
use context_module;
use dml_exception;
use Exception;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_external\util;
use invalid_parameter_exception;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use moodle_exception;
use question_engine;
use required_capability_exception;

/**
 * Web Service provider class.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    /**
     * Implements the tool_ucsfsomapi_get_courses web service endpoint.
     * Returns a list of course data for a given list of course IDs.
     * Each data point includes the course name, course ID, and course category ID.
     *
     * @param ?array $params Input parameters, contains the list of course IDs.
     * @return array A list of course data points.
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_courses(?array $params = []): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $rhett = [];

        $params = self::validate_parameters(self::get_courses_parameters(),
            ['categoryids' => $params]);

        if (empty($params['categoryids'])) {
            return $rhett;
        }

        $categoryids = clean_param_array($params['categoryids'], PARAM_INT);
        $courses = $DB->get_records_list('course', 'category', $categoryids);
        foreach ($courses as $course) {
            // Now security checks.
            $context = context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception) {
                continue;
            }
            if ($course->id != SITEID) {
                require_capability('moodle/course:view', $context);
            }

            $courseadmin = has_capability('moodle/course:update', $context);

            if ($courseadmin || $course->visible
                || has_capability('moodle/course:viewhiddencourses', $context)) {
                $rhett[] = [
                    'id' => $course->id,
                    'categoryid' => $course->category,
                    'name' => util::format_string($course->fullname, $context),
                ];
            }
        }

        return $rhett;
    }

    /**
     * Defines the input parameters for the tool_ucsfsomapi_get_courses web service endpoint.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function get_courses_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['categoryids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Category ID')
                , 'List of category IDs.',
                VALUE_REQUIRED
            )]);
    }

    /**
     * Defines the output structure for the tool_ucsfsomapi_get_courses web service endpoint.
     *
     * @return external_description The output structure definition.
     */
    public static function get_courses_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Course Name', VALUE_REQUIRED),
                'categoryid' => new external_value(PARAM_INT, 'Course Category ID', VALUE_REQUIRED),
            ])
        );
    }

    /**
     * Implements the tool_ucsfsomapi_get_quizzes web service endpoint.
     * Returns a list of quiz data for a given list of course IDs.
     * Each data point includes the quiz ID, course name, course ID, course module ID,
     * and a list of question IDs and max marks for all questions belonging to each quiz.
     *
     * @param ?array $params Input parameters, contains the list of course IDs.
     * @return array A list of quiz data points.
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    public static function get_quizzes(?array $params = []): array {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $rhett = [];

        $params = self::validate_parameters(self::get_quizzes_parameters(),
            ['courseids' => $params]);

        // Get the quizzes in this course, this function checks users visibility permissions.
        // We can avoid then additional validate_context calls.
        // Todo: figure out what to do with warnings. (probably eat them) [ST 2021/04/14].
        $courseids = clean_param_array($params['courseids'], PARAM_INT);
        list($courses, $warnings) = util::validate_courses($courseids);
        $quizzes = get_all_instances_in_courses("quiz", $courses);

        foreach ($quizzes as $quiz) {
            $context = context_module::instance($quiz->coursemodule);
            if (has_capability('mod/quiz:viewreports', $context)) {
                $ret = [
                    'id' => $quiz->id,
                    'name' => util::format_string($quiz->name, $context),
                    'courseid' => $quiz->course,
                    'coursemoduleid' => $quiz->coursemodule,
                    'questions' => [],
                ];
                $quizobj = quiz_settings::create($quiz->id, $USER->id);
                $quizobj->preload_questions();
                $quizobj->load_questions();
                $questions = $quizobj->get_questions();
                foreach ($questions as $question) {
                    $ret['questions'][] = [
                        'id' => $question->id,
                        'maxmarks' => $question->maxmark,
                    ];
                }

                $rhett[] = $ret;
            }
        }
        return $rhett;
    }

    /**
     * Defines the input parameters for the tool_ucsfsomapi_get_quizzes web service endpoint.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function get_quizzes_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID')
                , 'List of course IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * Defines the output structure for the tool_ucsfsomapi_get_quizzes web service endpoint.
     *
     * @return external_description The output structure definition.
     */
    public static function get_quizzes_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Quiz ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Quiz Name', VALUE_REQUIRED),
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'coursemoduleid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'questions' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                        'maxmarks' => new external_value(PARAM_FLOAT, 'Maximum marks for this question.', VALUE_REQUIRED),
                    ]),
                ),
            ]),
        );
    }

    /**
     * Implements the tool_ucsfsomapi_get_questions web service endpoint.
     * Returns a list of question data for a given list of quiz IDs.
     * Each data point includes the question ID, question name, question text, question default marks, question type,
     * question bank entry ID, a list of associated quiz IDs, and a list of question revisions.
     *
     * @param ?array $params Input parameters, contains the list of quiz IDs.
     * @return array A list of question data points.
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_questions(?array $params = []): array {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/lib/modinfolib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $rhett = [];

        $params = self::validate_parameters(self::get_questions_parameters(),
            ['quizids' => $params]);

        $quizzes = self::get_quizzes_by_ids($params['quizids']);

        foreach ($quizzes as $quiz) {
            // See mod_quiz_external::validate_quiz.
            list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
            $context = context_module::instance($cm->id);
            try {
                self::validate_context($context);
            } catch (Exception) {
                continue;
            }

            // Load quiz and questions.
            $quizobj = quiz_settings::create($quiz->id, $USER->id);
            $quizobj->preload_questions();
            $quizobj->load_questions();
            $questions = $quizobj->get_questions();

            foreach ($questions as $question) {
                if (! array_key_exists($question->id, $rhett)) {
                    $rhett[$question->id] = [
                        'id' => $question->id,
                        'name' => util::format_string($question->name, $context),
                        'text' => util::format_text($question->questiontext, $question->questiontextformat, $context)[0],
                        'defaultmarks' => $question->defaultmark,
                        'type' => $question->qtype,
                        'questionbankentryid' => $question->questionbankentryid,
                        'quizzes' => [ $quiz->id ],
                    ];
                    // Bolt on the question ids of all revisions of this question.
                    $versions = self::get_question_versions_by_questionbankentry($question->questionbankentryid);
                    $ids = array_map(function ($version) {
                        return $version->questionid;
                    }, $versions);
                    $rhett[$question->id]['revisions'] = $ids;
                } else {
                    $rhett[$question->id]['quizzes'][] = $quiz->id;
                }
            }
        }
        return array_values($rhett);
    }

    /**
     * Defines the input parameters for the tool_ucsfsomapi_get_questions web service endpoint.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function get_questions_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['quizids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Quiz ID')
                , 'List of quiz IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * Defines the output structure for the tool_ucsfsomapi_get_questions web service endpoint.
     *
     * @return external_description The output structure definition.
     */
    public static function get_questions_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Question name', VALUE_REQUIRED),
                'text' => new external_value(PARAM_RAW, 'Question text', VALUE_REQUIRED),
                'type' => new external_value(PARAM_TEXT, 'Question type', VALUE_REQUIRED),
                'defaultmarks' => new external_value(PARAM_FLOAT, 'Default marks for this question.', VALUE_REQUIRED),
                'quizzes' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Quiz ID', VALUE_REQUIRED),
                ),
                'revisions' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                ),
                'questionbankentryid' => new external_value(
                    PARAM_INT,
                    'The question bank entry id for this question',
                    VALUE_REQUIRED
                ),
            ]),
        );
    }

    /**
     * Implements the tool_ucsfsomapi_get_attempts web service endpoint.
     * Returns a list of quiz-attempt data for a given list of quiz IDs.
     * Each data point includes the attempt ID, attempt start time, attempt finish time, the attempt's quiz ID,
     * the attempt taker's user ID, and a list of given answers per attempt.

     * @param ?array $params Input parameters, contains the list of quiz IDs.
     * @return array A list of quiz-attempt data points.
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws coding_exception
     * @throws dml_exception
     * @see \mod_quiz_external::get_user_attempts()
     */
    public static function get_attempts(?array $params = []): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/modinfolib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $rhett = [];

        $params = self::validate_parameters(self::get_attempts_parameters(),
            ['quizids' => $params]);

        $quizzes = self::get_quizzes_by_ids($params['quizids']);

        foreach ($quizzes as $quiz) {
            // Validate against the quiz-owning course context.
            // See \mod_quiz_external::validate_quiz.
            list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
            $context = context_module::instance($cm->id);
            try {
                self::validate_context($context);
            } catch (Exception) {
                continue;
            }

            // Load finalized attempts.
            // Todo: figure out if only finalized attempts should be included [ST 2021/04/16].
            list($sql, $sqlparams) = $DB->get_in_or_equal($quiz->id, SQL_PARAMS_NAMED);
            $sqlparams['state1'] = quiz_attempt::FINISHED;
            $sqlparams['state2'] = quiz_attempt::ABANDONED;
            $quizattempts = $DB->get_records_select(
                'quiz_attempts',
                "quiz $sql AND state IN (:state1, :state2)",
                $sqlparams,
                'quiz'
            );

            foreach ($quizattempts as $quizattempt) {
                $ret = [
                    'id' => $quizattempt->id,
                    'timestart' => $quizattempt->timestart,
                    'timefinish' => $quizattempt->timefinish,
                    'quizid' => $quizattempt->quiz,
                    'userid' => $quizattempt->userid,
                    'questions' => [],
                ];

                $quba = question_engine::load_questions_usage_by_activity($quizattempt->uniqueid);
                $slots = $quba->get_slots();
                foreach ($slots as $slot) {
                    $questionattempt = $quba->get_question_attempt($slot);
                    $ret['questions'][] = [
                        'id' => $questionattempt->get_question_id(),
                        'mark' => $questionattempt->get_mark(),
                        'answer' => util::format_string($questionattempt->get_response_summary(), $context),
                    ];
                }

                $rhett[] = $ret;
            }
        }

        return $rhett;
    }

    /**
     * Defines the input parameters for the tool_ucsfsomapi_get_attempts web service endpoint.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function get_attempts_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['quizids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Quiz ID')
                , 'List of quiz IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * Defines the output structure for the tool_ucsfsomapi_get_attempts web service endpoint.
     *
     * @return external_description The output structure definition.
     */
    public static function get_attempts_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Attempt ID', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'Quiz ID', VALUE_REQUIRED),
                'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
                'timestart' => new external_value(PARAM_INT, 'Timestamp of when this attempt was started.', VALUE_REQUIRED),
                'timefinish' => new external_value(PARAM_INT, 'Timestamp of when this attempt was finished.', VALUE_REQUIRED),
                'questions' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                        'mark' => new external_value(PARAM_FLOAT, 'Mark received', VALUE_REQUIRED),
                        'answer' => new external_value(PARAM_RAW, 'Answer given', VALUE_REQUIRED),
                    ]),
                ),
            ]),
        );
    }

    /**
     * Implements the tool_ucsfsomapi_get_users web service endpoint.
     * Returns a list of user data for a given list of user IDs.
     * Each data point includes the user ID and the user's UC ID.
     *
     * @param ?array $params Input parameters, contains the list of user IDs.
     * @return array A list of user data points.
     * @throws invalid_parameter_exception
     * @throws coding_exception
     * @throws dml_exception
     * @see \core_user_external::get_users()
     */
    public static function get_users(?array $params = []): array {
        global $DB;

        $rhett = [];

        $params = self::validate_parameters(self::get_users_parameters(),
            ['userids' => $params]);

        if (empty($params['userids'])) {
            return [];
        }

        $userids = clean_param_array($params['userids'], PARAM_INT);

        list($sql, $sqlparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $users = $DB->get_records_select(
            'user',
            "id $sql AND deleted = 0",
            $sqlparams,
            'id'
        );

        foreach ($users as $user) {
            $rhett[] = [
                'id' => $user->id,
                'ucid' => $user->idnumber,
            ];
        }

        return $rhett;
    }

    /**
     * Defines the input parameters for the tool_ucsfsomapi_get_users web service endpoint.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function get_users_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID')
                , 'List of user IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * Defines the output structure for the tool_ucsfsomapi_get_users web service endpoint.
     *
     * @return external_description The output structure definition.
     */
    public static function get_users_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
                'ucid' => new external_value(PARAM_TEXT, 'UC ID', VALUE_REQUIRED),
            ]),
        );
    }

    /**
     * Retrieves a list of quiz records by their IDs.
     *
     * @param ?array $quizids The quiz IDs.
     * @return array A list of quiz records.
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function get_quizzes_by_ids(?array $quizids = []): array {
        global $DB;
        if (empty($quizids)) {
            return [];
        }
        $quizids = clean_param_array($quizids, PARAM_INT);
        list($sql, $sqlparams) = $DB->get_in_or_equal($quizids, SQL_PARAMS_NAMED);
        return $DB->get_records_select(
            'quiz',
            "id $sql",
            $sqlparams,
            'id'
        );
    }

    /**
     * Get a list of question versions by the question's question bank entry.
     *
     * @param ?string $entryid The question bank entry ID.
     * @return array A list of question records.
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function get_question_versions_by_questionbankentry(?string $entryid = ''): array {
        global $DB;
        if (!$entryid) {
            return [];
        }
        $entryid = clean_param($entryid, PARAM_INT);
        list($sql, $sqlparams) = $DB->get_in_or_equal($entryid, SQL_PARAMS_NAMED);
        return $DB->get_records_select(
            'question_versions',
            "questionbankentryid $sql",
            $sqlparams,
            'id'
        );
    }
}
