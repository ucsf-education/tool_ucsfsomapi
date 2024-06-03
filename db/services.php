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
 * Web service declarations for the tool_ucsfsomapi plugin.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_ucsfsomapi_get_courses' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_courses',
        'description' => 'Gets all courses in given categories.',
        'type' => 'read',
        'capabilities' => 'moodle/course:view',
        'ajax' => false,
        'services' => ['ucsf_som_api'],
    ],
    'tool_ucsfsomapi_get_quizzes' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_quizzes',
        'description' => 'Gets all quizzes in given courses.',
        'type' => 'read',
        'capabilities' => 'mod/quiz:viewreports',
        'ajax' => false,
        'services' => ['ucsf_som_api'],
    ],
    'tool_ucsfsomapi_get_questions' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_questions',
        'description' => 'Gets all questions in given quizzes.',
        'type' => 'read',
        'capabilities' => 'moodle/question:viewall',
        'ajax' => false,
        'services' => ['ucsf_som_api'],
    ],
    'tool_ucsfsomapi_get_attempts' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_attempts',
        'description' => 'Gets all attempts for given quizzes.',
        'type' => 'read',
        'capabilities' => 'mod/quiz:viewreports',
        'ajax' => false,
        'services' => ['ucsf_som_api'],
    ],
    'tool_ucsfsomapi_get_users' => [
        'classname' => 'tool_ucsfsomapi\external\api',
        'methodname' => 'get_users',
        'description' => 'Gets UC ids for given users.',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewdetails',
        'ajax' => false,
        'services' => ['ucsf_som_api'],
    ],
];

$services = [
    'UCSF School Of Medicine API'  => [
        'functions' => [],
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'ucsf_som_api',
    ],
];
