<?php

/**
 * Web Service functions for tool_ucsfsomapi.
 *
 * @package tool_ucsfsomapi
 */

namespace tool_ucsfsomapi\external;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/externallib.php';

use context_module;
use Exception;
use external_api;
use external_description;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_util;
use external_value;
use moodle_exception;
use quiz;
use stdClass;

/**
 * Web Service provider.
 * @todo Add user endpoint. [ST 2021/04/14]
 */
class api extends external_api {

    /**
     * @param array $params
     * @return array
     */
    public static function get_courses($params = array()) : array {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        $rhett = [];

        $params = self::validate_parameters(self::get_courses_parameters(),
            ['categoryids' => $params]);

        if (empty($params)) {
            return $rhett;
        }

        $courses = $DB->get_records_list('course', 'category', $params['categoryids']);
        foreach ($courses as $course) {
            // now security checks
            $context = \context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $course->id;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
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
                    'name' => external_format_string($course->fullname, $context->id),
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
     * @param array $params
     * @return array
     */
    public static function get_quizzes($params = array()) : array {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $rhett = [];

        $params = self::validate_parameters(self::get_quizzes_parameters(),
            ['courseids' => $params]);

        if (empty($params)) {
            return $rhett;
        }

        // Get the quizzes in this course, this function checks users visibility permissions.
        // We can avoid then additional validate_context calls.
        // @todo figure out what to do with warnings. (probably eat them) [ST 2021/04/14]
        list($courses, $warnings) = external_util::validate_courses($params['courseids']);
        $quizzes = get_all_instances_in_courses("quiz", $courses);

        foreach ($quizzes as $quiz) {
            $context = context_module::instance($quiz->coursemodule);
            if (has_capability('mod/quiz:view', $context)) {
                $ret = [
                    'id' => $quiz->id,
                    'name' => external_format_string($quiz->name, $context->id),
                    'courseid' => $quiz->course,
                    'questions' => [],
                ];
                $quizobj = quiz::create($quiz->id, $USER->id);
                $quizobj->preload_questions();
                $quizobj->load_questions();
                $questions = $quizobj->get_questions();
                foreach ($questions as $question) {
                    $ret['questions'][] = [
                        'id' => $question->id,
                        'name' => external_format_string($question->name, $context->id),
                        'text' => external_format_text($question->questiontext, $question->questiontextformat, $context->id)[0],
                        'maxmarks' => $question->maxmark,
                        'defaultmarks' => $question->defaultmark,
                        'type' => $question->qtype,
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
                'questions' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                        'name' => new external_value(PARAM_TEXT, 'Question name', VALUE_REQUIRED),
                        'text' => new external_value(PARAM_RAW, 'Question text', VALUE_REQUIRED),
                        'type' => new external_value(PARAM_TEXT, 'Question type', VALUE_REQUIRED),
                        'maxmarks' => new external_value(PARAM_FLOAT, 'Maximum marks for this question.', VALUE_REQUIRED),
                        'defaultmarks' => new external_value(PARAM_FLOAT, 'Default marks for this question.', VALUE_REQUIRED),
                    ]),
                ),
            ]),
        );
    }

    /**
     * @param array $params
     * @return array
     */
    public static function get_questions($params = array()) : array {
        $rhett = [];
        // @todo implement. [ST 2021/04/14]
        return $rhett;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_questions_parameters() : external_function_parameters {
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
    public static function get_questions_returns() : external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Question Name', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'Quiz ID', VALUE_REQUIRED),
                'text' => new external_value(PARAM_TEXT, 'Question Test', VALUE_REQUIRED),
                'type' => new external_value(PARAM_TEXT, 'Question Type', VALUE_REQUIRED),
                // @todo add marks to output [ST 2021/04/14]
            ])
        );
    }

    /**
     * @param array $params
     * @return array
     */
    public static function get_attempts($params = array()) : array {
        $rhett = [];
        // @todo implement. [ST 2021/04/14]
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
                // @todo add question ids, start date, end date, marks attained, final submitted responses. [ST 2021/04/14]
            ])
        );
    }
}
