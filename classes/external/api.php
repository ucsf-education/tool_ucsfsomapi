<?php

/**
 * Web Service functions for tool_ucsfsomapi.
 *
 * @package tool_ucsfsomapi
 */

namespace tool_ucsfsomapi\external;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/externallib.php';

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
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use question_engine;


/**
 * Web Service provider.
 */
class api extends external_api {

    /**
     * @param ?array $params
     * @return array
     */
    public static function get_courses(?array $params = []) : array {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        $rhett = [];

        $params = self::validate_parameters(self::get_courses_parameters(),
            ['categoryids' => $params]);

        if (empty($params['categoryids'])) {
            return $rhett;
        }

        $categoryids = clean_param_array($params['categoryids'], PARAM_INT);
        $courses = $DB->get_records_list('course', 'category', $categoryids);
        foreach ($courses as $course) {
            // now security checks
            $context = context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                continue;
            }
            if ($course->id != SITEID) {
                require_capability('moodle/course:view', $context);
            }

            $courseadmin = has_capability('moodle/course:update', $context);

            if ($courseadmin or $course->visible
                or has_capability('moodle/course:viewhiddencourses', $context)) {
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
     * @return external_function_parameters
     */
    public static function get_courses_parameters() : external_function_parameters {
        return new external_function_parameters(
            ['categoryids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Category ID')
                , 'List of category IDs.',
                VALUE_REQUIRED
            )]);
    }

    /**
     * @return external_description
     */
    public static function get_courses_returns() : external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Course Name', VALUE_REQUIRED),
                'categoryid' => new external_value(PARAM_INT, 'Course Category ID', VALUE_REQUIRED),
            ])
        );
    }

    /**
     * @param ?array $params
     * @return array
     */
    public static function get_quizzes(?array $params = []) : array {
        global $USER, $CFG;
        require_once $CFG->dirroot . '/mod/quiz/locallib.php';

        $rhett = [];

        $params = self::validate_parameters(self::get_quizzes_parameters(),
            ['courseids' => $params]);

        // Get the quizzes in this course, this function checks users visibility permissions.
        // We can avoid then additional validate_context calls.
        // @todo figure out what to do with warnings. (probably eat them) [ST 2021/04/14]
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
     * @return external_function_parameters
     */
    public static function get_quizzes_parameters() : external_function_parameters {
        return new external_function_parameters(
            ['courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID')
                , 'List of course IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * @return external_description
     */
    public static function get_quizzes_returns() : external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Quiz ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Quiz Name', VALUE_REQUIRED),
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'coursemoduleid' =>  new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
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
     * @param ?array $params
     * @return array
     */
    public static function get_questions(?array $params = []) : array{
        global $DB, $USER, $CFG;
        require_once $CFG->dirroot . '/lib/modinfolib.php';
        require_once $CFG->dirroot . '/mod/quiz/locallib.php';

        $rhett = [];

        $params = self::validate_parameters(self::get_questions_parameters(),
            ['quizids' => $params]);

        $quizzes = self::get_quizzes_by_ids($params['quizids']);

        foreach($quizzes as $quiz) {
            /* @see \mod_quiz_external::validate_quiz() */
            list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
            $context = context_module::instance($cm->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                continue;
            }

            // load quiz and questions
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
                    // bolt on the question ids of all revisions of this question
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
     * @return external_function_parameters
     */
    public static function get_questions_parameters() : external_function_parameters {
        return new external_function_parameters(
            ['quizids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Quiz ID')
                , 'List of quiz IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * @return external_description
     */
    public static function get_questions_returns() : external_description {
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
                'questionbankentryid' =>  new external_value(PARAM_INT, 'The question bank entry id for this question', VALUE_REQUIRED),
            ]),
        );
    }

    /**
     * @param ?array $params
     * @return array
     * @see \mod_quiz_external::get_user_attempts()
     */
    public static function get_attempts(?array $params = []) : array {
        global $DB, $CFG;
        require_once $CFG->dirroot . '/lib/modinfolib.php';
        require_once $CFG->dirroot . '/mod/quiz/locallib.php';

        $rhett = [];

        $params = self::validate_parameters(self::get_attempts_parameters(),
            ['quizids' => $params]);

        $quizzes = self::get_quizzes_by_ids($params['quizids']);

        foreach($quizzes as $quiz) {
            // validate against the quiz-owning course context.
            /* @see \mod_quiz_external::validate_quiz() */
            list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
            $context = context_module::instance($cm->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                continue;
            }

            // load finalized attempts
            // @todo figure out if only finalized attempts should be included. [ST 2021/04/16]
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
                    'timestart' => $quizattempt->timestart, // @todo convert to datetime string? [ST 2021/04/16]
                    'timefinish' => $quizattempt->timefinish, // @todo convert to datetime string? [ST 2021/04/16]
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
     * @return external_function_parameters
     */
    public static function get_attempts_parameters() : external_function_parameters {
        return new external_function_parameters(
            ['quizids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Quiz ID')
                , 'List of quiz IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * @return external_description
     */
    public static function get_attempts_returns() : external_description {
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
     * @param ?array $params
     * @return array
     * @see \core_user_external::get_users()
     */
    public static function get_users(?array $params = []) : array {
        global $DB;

        $rhett = [];

        $params = self::validate_parameters(self::get_users_parameters(),
            ['userids' => $params]);

        if (empty($params['userids'])) {
            return [];
        }

        $userids = clean_param_array($params['userids'], PARAM_INT);

        list($sql, $sqlparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        // @todo Should we include deleted user accounts here? [ST 2021/04/26]
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
     * @return external_function_parameters
     */
    public static function get_users_parameters() : external_function_parameters {
        return new external_function_parameters(
            ['userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID')
                , 'List of user IDs.',
                VALUE_REQUIRED
            )]
        );
    }

    /**
     * @return external_description
     */
    public static function get_users_returns() : external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
                'ucid' => new external_value(PARAM_TEXT, 'UC ID', VALUE_REQUIRED),
            ]),
        );
    }

    /**
     * @param ?array $quizids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function get_quizzes_by_ids(?array $quizids = []) : array {
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
