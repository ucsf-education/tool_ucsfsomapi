<?php

/**
 * List of Web Services for the tool_ucsfsomapi plugin.
 *
 * @package tool_ucsfsomapi
 */
defined('MOODLE_INTERNAL') || die();

const UCSF_SOM_API_SERVICE = 'ucsf_som_api';

$functions = [
    'tool_ucsfsomapi_get_courses' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_courses',
        'description' => 'Gets all courses in given categories.',
        'type' => 'read',
        'capabilities' => 'moodle/course:view',
        'ajax' => false,
        'services' => [UCSF_SOM_API_SERVICE],
    ],
    'tool_ucsfsomapi_get_quizzes' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_quizzes',
        'description' => 'Gets all quizzes in given courses.',
        'type' => 'read',
        'capabilities' => 'mod/quiz:viewreports',
        'ajax' => false,
        'services' => [UCSF_SOM_API_SERVICE],
    ],
    'tool_ucsfsomapi_get_questions' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_questions',
        'description' => 'Gets all questions in given quizzes.',
        'type' => 'read',
        'capabilities' => 'moodle/question:viewall',
        'ajax' => false,
        'services' => [UCSF_SOM_API_SERVICE],
    ],
    'tool_ucsfsomapi_get_attempts' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_attempts',
        'description' => 'Gets all attempts for given quizzes.',
        'type' => 'read',
        'capabilities' => 'mod/quiz:viewreports',
        'ajax' => false,
        'services' => [UCSF_SOM_API_SERVICE],
    ],
    'tool_ucsfsomapi_get_users' => array(
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_users',
        'description' => 'Gets UC ids for given users.',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewdetails',
        'ajax' => false,
        'services' => [UCSF_SOM_API_SERVICE],
    ),
];

$services = [
    'UCSF School Of Medicine API'  => [
        'functions' => [],
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => UCSF_SOM_API_SERVICE,
    ],
];
