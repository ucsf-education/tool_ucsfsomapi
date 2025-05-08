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
 * Test coverage for UCSF SOM API web services class.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ucsfsomapi;

use assign;
use context_module;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\util;
use DateTime;
use externallib_advanced_testcase;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use question_bank;
use question_engine;
use tool_ucsfsomapi\external\api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Test coverage for UCSF SOM API web services class.
 *
 * @covers \tool_ucsfsomapi\external\api
 */
final class api_test extends externallib_advanced_testcase {

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Tests input parameters definition for "get_courses" endpoint.
     */
    public function test_get_courses_parameters(): void {
        $structure = api::get_courses_parameters();
        $this->assertCount(1, $structure->keys);

        $innerstructure = $structure->keys['categoryids'];
        $this->assertTrue( $innerstructure instanceof external_multiple_structure);
        $this->assertEquals('List of category IDs.', $innerstructure->desc);
        $this->assertEquals(VALUE_REQUIRED, $innerstructure->required);

        $componentvalue = $innerstructure->content;
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Category ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests return value definition for "get_courses" endpoint.
     */
    public function test_get_courses_returns(): void {
        $structure = api::get_courses_returns();
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure = $structure->content;
        $this->assertTrue($innerstructure instanceof external_single_structure);
        $this->assertCount(3, $innerstructure->keys);

        $componentvalue = $innerstructure->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['name'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course Name', $componentvalue->desc);
        $this->assertEquals(PARAM_TEXT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['categoryid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course Category ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests "get_courses" endpoint.
     */
    public function test_get_courses(): void {
        // For convenience, let's use a root-level user here.
        // That way, we don't need to deal with user perms for this.
        $this->setAdminUser();

        // Create a course category.
        $coursecategory = $this->getDataGenerator()->create_category();

        // Retrieve courses for this course category.
        $rhett = external_api::clean_returnvalue(
            api::get_courses_returns(),
            api::get_courses([$coursecategory->id])
        );
        $this->assertEmpty($rhett);

        // Create two courses in this category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);

        // Retrieve course again. There should be two now.
        $rhett = external_api::clean_returnvalue(
            api::get_courses_returns(),
            api::get_courses([$coursecategory->id])
        );
        $this->assertCount(2, $rhett);
        $this->assertEquals($course1->id, $rhett[0]['id']);
        $this->assertEquals($course1->fullname, $rhett[0]['name']);
        $this->assertEquals($coursecategory->id, $rhett[0]['categoryid']);
        $this->assertEquals($course2->id, $rhett[1]['id']);
        $this->assertEquals($course2->fullname, $rhett[1]['name']);
        $this->assertEquals($coursecategory->id, $rhett[1]['categoryid']);
    }

    /**
     * Tests input parameters definition for "get_quizzes" endpoint.
     */
    public function test_get_quizzes_parameters(): void {
        $structure = api::get_quizzes_parameters();
        $this->assertCount(1, $structure->keys);

        $innerstructure = $structure->keys['courseids'];
        $this->assertTrue( $innerstructure instanceof external_multiple_structure);
        $this->assertEquals('List of course IDs.', $innerstructure->desc);
        $this->assertEquals(VALUE_REQUIRED, $innerstructure->required);

        $componentvalue = $innerstructure->content;
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests return value definition for "get_quizzes" endpoint.
     */
    public function test_get_quizzes_returns(): void {
        $structure = api::get_quizzes_returns();
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure = $structure->content;
        $this->assertTrue($innerstructure instanceof external_single_structure);
        $this->assertCount(5, $innerstructure->keys);

        $componentvalue = $innerstructure->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Quiz ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['name'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Quiz Name', $componentvalue->desc);
        $this->assertEquals(PARAM_TEXT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['courseid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['coursemoduleid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Course ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['questions'];
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure2 = $componentvalue->content;
        $this->assertTrue($innerstructure2 instanceof external_single_structure);
        $this->assertCount(2, $innerstructure2->keys);

        $componentvalue = $innerstructure2->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure2->keys['maxmarks'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Maximum marks for this question.', $componentvalue->desc);
        $this->assertEquals(PARAM_FLOAT, $componentvalue->type);
    }

    /**
     * Tests "get_quizzes" endpoint.
     */
    public function test_get_quizzes(): void {
        $this->setAdminUser();

        // Create a course category.
        $coursecategory = $this->getDataGenerator()->create_category();
        // Create two courses in this category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);

        // Retrieve quizzes for these courses, should come up empty-handed.
        $rhett = external_api::clean_returnvalue(
            api::get_quizzes_returns(),
            api::get_quizzes([$course1->id, $course2->id])
        );
        $this->assertEmpty($rhett);

        // Create three quizzes total in these courses.
        $quiz1 = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Foo']);
        $quiz2 = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Bar']);
        $quiz3 = $this->getDataGenerator()->create_module('quiz', ['course' => $course2->id, 'name' => 'Baz']);

        // Add questions to quizzes.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $maxmark1 = 2.0;
        $maxmark2 = 0.67;
        $question1 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        quiz_add_quiz_question($question1->id, $quiz1, maxmark: $maxmark1);
        $question2 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        quiz_add_quiz_question($question2->id, $quiz1, maxmark: $maxmark2);
        $question3 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        quiz_add_quiz_question($question3->id, $quiz2);

        // Retrieve quizzes again. There should be three now.
        $rhett = external_api::clean_returnvalue(
            api::get_quizzes_returns(),
            api::get_quizzes([$course1->id, $course2->id])
        );

        // Check the output.
        $this->assertCount(3, $rhett);
        $this->assertEquals($quiz1->id, $rhett[0]['id']);
        $this->assertEquals($quiz2->id, $rhett[1]['id']);
        $this->assertEquals($quiz3->id, $rhett[2]['id']);
        $this->assertEquals($course1->id, $rhett[0]['courseid']);
        $this->assertEquals($course1->id, $rhett[1]['courseid']);
        $this->assertEquals($course2->id, $rhett[2]['courseid']);
        $this->assertEquals($quiz1->cmid, $rhett[0]['coursemoduleid']);
        $this->assertEquals($quiz2->cmid, $rhett[1]['coursemoduleid']);
        $this->assertEquals($quiz3->cmid, $rhett[2]['coursemoduleid']);
        $this->assertCount(2, $rhett[0]['questions']);
        $this->assertEquals($question1->id, $rhett[0]['questions'][0]['id']);
        $this->assertEquals($maxmark1, $rhett[0]['questions'][0]['maxmarks']);
        $this->assertEquals($question2->id, $rhett[0]['questions'][1]['id']);
        $this->assertEquals($maxmark2, $rhett[0]['questions'][1]['maxmarks']);
        $this->assertCount(1, $rhett[1]['questions']);
        $this->assertEquals($question3->id, $rhett[1]['questions'][0]['id']);
        $this->assertEquals($question3->defaultmark, $rhett[1]['questions'][0]['maxmarks']);
        $this->assertEmpty($rhett[2]['questions']);
    }

    /**
     * Tests input parameters definition for "get_questions" endpoint.
     */
    public function test_get_questions_parameters(): void {
        $structure = api::get_questions_parameters();
        $this->assertCount(1, $structure->keys);

        $innerstructure = $structure->keys['quizids'];
        $this->assertTrue( $innerstructure instanceof external_multiple_structure);
        $this->assertEquals('List of quiz IDs.', $innerstructure->desc);
        $this->assertEquals(VALUE_REQUIRED, $innerstructure->required);

        $componentvalue = $innerstructure->content;
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Quiz ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests return value definition for "get_questions" endpoint.
     */
    public function test_get_questions_returns(): void {
        $structure = api::get_questions_returns();
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure = $structure->content;
        $this->assertTrue($innerstructure instanceof external_single_structure);
        $this->assertCount(10, $innerstructure->keys);

        $componentvalue = $innerstructure->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['name'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question name', $componentvalue->desc);
        $this->assertEquals(PARAM_TEXT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['text'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question text', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);

        $componentvalue = $innerstructure->keys['generalfeedback'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('General feedback for this question', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);

        $componentvalue = $innerstructure->keys['type'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question type', $componentvalue->desc);
        $this->assertEquals(PARAM_TEXT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['defaultmarks'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Default marks for this question.', $componentvalue->desc);
        $this->assertEquals(PARAM_FLOAT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['quizzes'];
        $this->assertTrue($componentvalue instanceof external_multiple_structure);

        $innercomponent = $componentvalue->content;
        $this->assertTrue($innercomponent instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $innercomponent->required);
        $this->assertEquals('Quiz ID', $innercomponent->desc);
        $this->assertEquals(PARAM_INT, $innercomponent->type);

        $componentvalue = $innerstructure->keys['revisions'];
        $this->assertTrue($componentvalue instanceof external_multiple_structure);

        $innercomponent = $componentvalue->content;
         $this->assertTrue( $innercomponent instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $innercomponent->required);
        $this->assertEquals('Question ID', $innercomponent->desc);
        $this->assertEquals(PARAM_INT, $innercomponent->type);

        $componentvalue = $innerstructure->keys['questionbankentryid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('The question bank entry id for this question', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $innerstructure2 = $innerstructure->keys['options'];
        $this->assertTrue($innerstructure2 instanceof external_single_structure);
        $this->assertCount(2, $innerstructure2->keys);
        $this->assertEquals(VALUE_OPTIONAL, $innerstructure2->required);
        $this->assertEquals('Additional data points that are question-type specific', $innerstructure2->desc);

        $componentvalue = $innerstructure2->keys['graderinfo'];
        $this->assertTrue($componentvalue instanceof external_value);
        $this->assertEquals(VALUE_OPTIONAL, $componentvalue->required);
        $this->assertEquals('Information for graders on Essay questions', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);

        $innerstructure3 = $innerstructure2->keys['answers'];
        $this->assertTrue($innerstructure3 instanceof external_multiple_structure);
        $this->assertEquals(VALUE_OPTIONAL, $innerstructure3->required);
        $this->assertEquals('Question answers', $innerstructure3->desc);

        $innerstructure4 = $innerstructure3->content;
        $this->assertTrue($innerstructure4 instanceof external_single_structure);
        $this->assertCount(3, $innerstructure4->keys);
        $this->assertEquals(VALUE_OPTIONAL, $innerstructure4->required);
        $this->assertEquals('An answer to the question', $innerstructure4->desc);

        $componentvalue = $innerstructure4->keys['text'];
        $this->assertTrue($componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('The text of the answer', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);

        $componentvalue = $innerstructure4->keys['grade'];
        $this->assertTrue($componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('The fractional grade of the answer', $componentvalue->desc);
        $this->assertEquals(PARAM_FLOAT, $componentvalue->type);

        $componentvalue = $innerstructure4->keys['feedback'];
        $this->assertTrue($componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('The feedback to the answer', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);
    }

    /**
     * Tests "get_questions" endpoint.
     */
    public function test_get_questions(): void {
        $this->setAdminUser();

        // Create a course category.
        $coursecategory = $this->getDataGenerator()->create_category();
        // Create two courses in this category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        // Create three quizzes in these courses.
        $quiz1 = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Foo']);
        $quiz2 = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Bar']);
        $quiz3 = $this->getDataGenerator()->create_module('quiz', ['course' => $course2->id, 'name' => 'Baz']);
        // Get a hold of the quiz module contexts.
        $cm1 = get_course_and_cm_from_instance($quiz1, 'quiz')[1];
        $cm2 = get_course_and_cm_from_instance($quiz2, 'quiz')[1];
        $cm3 = get_course_and_cm_from_instance($quiz3, 'quiz')[1];
        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);
        $context3 = context_module::instance($cm3->id);

        // Retrieve questions for these quizzes, should come up empty-handed.
        $rhett = external_api::clean_returnvalue(
            api::get_questions_returns(),
            api::get_questions([$quiz1->id, $quiz2->id, $quiz3->id])
        );
        $this->assertEmpty($rhett);

        // Add questions to quizzes.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $maxmark1 = 2.0;
        $maxmark2 = 0.67;
        $generalfeedback1 = '<p>Lorem Ipsum.</p>';
        $question1 = $questiongenerator->create_question(
            'truefalse',
            null,
            [
                'name' => 'Yes or no',
                'category' => $category->id,
                'generalfeedback' => ['text' => $generalfeedback1, 'format' => FORMAT_HTML],
            ]
        );
        // Add this question to quizzes 1 and 2.
        quiz_add_quiz_question($question1->id, $quiz1, maxmark: $maxmark1);
        quiz_add_quiz_question($question1->id, $quiz2, maxmark: $maxmark2);
        $question2 = $questiongenerator->create_question(
            'numerical',
            null,
            ['name' => 'Water boiling temperature', 'category' => $category->id]
        );
        // Add this question to quiz 2.
        quiz_add_quiz_question($question2->id, $quiz2, maxmark: $maxmark2);
        $question3 = $questiongenerator->create_question(
            'multichoice',
            null,
            ['name' => 'Yes, no, or maybe', 'category' => $category->id]
        );
        // Add this question to quizzes 2 and 3.
        quiz_add_quiz_question($question3->id, $quiz2);
        quiz_add_quiz_question($question3->id, $quiz3);

        // Update the third question.
        $question3 = $questiongenerator->update_question(
                $question3,
                null,
                [
                    'name' => 'A new name',
                    'generalfeedback' => ['text' => '   ', 'format' => FORMAT_MOODLE],
                ]
        );

        // Get a handle on all versions of these questions.
        $question1versions = question_bank::get_all_versions_of_question($question1->id);
        $question2versions = question_bank::get_all_versions_of_question($question2->id);
        $question3versions = question_bank::get_all_versions_of_question($question3->id);
        // Re-sort the versions for question 3 to put them into the same order as
        // the revisions are listed in the API output.
        ksort($question3versions);

        // Sanity check - count the number of versions per question.
        // The first two should have one, the third question should have to versions.
        $this->assertCount(1, $question1versions);
        $this->assertCount(1, $question2versions);
        $this->assertCount(2, $question3versions);

        // Retrieve questions again.
        $rhett = external_api::clean_returnvalue(
            api::get_questions_returns(),
            api::get_questions([$quiz1->id, $quiz2->id, $quiz3->id])
        );

        // Compare the output with our generated questions data.
        $this->assertCount(3, $rhett);
        $this->assertEquals($question1->id, $rhett[0]['id']);
        $this->assertEquals(util::format_string($question1->name, $context1), $rhett[0]['name']);
        $this->assertEquals(
            util::format_text($question1->questiontext, $question1->questiontextformat, $context1)[0],
            $rhett[0]['text']
        );
        $this->assertEquals($question1->defaultmark, $rhett[0]['defaultmarks']);
        $this->assertEquals($question1->qtype, $rhett[0]['type']);
        $this->assertEquals($generalfeedback1, $rhett[0]['generalfeedback']);

        $this->assertEquals($question1->questionbankentryid, $rhett[0]['questionbankentryid']);
        $this->assertEquals([$quiz1->id, $quiz2->id], $rhett[0]['quizzes']);
        $this->assertCount(1, $rhett[0]['revisions']);
        $this->assertEquals(reset($question1versions)->questionid, $rhett[0]['revisions'][0]);

        $this->assertEquals($question2->id, $rhett[1]['id']);
        $this->assertEquals(util::format_string($question2->name, $context2), $rhett[1]['name']);
        $this->assertEquals(
            util::format_text($question2->questiontext, $question2->questiontextformat, $context3)[0],
            $rhett[1]['text']
        );
        $this->assertEquals($question2->defaultmark, $rhett[1]['defaultmarks']);
        $this->assertEquals($question2->qtype, $rhett[1]['type']);
        $this->assertEquals('Generalfeedback: 3.14 is the right answer.', $rhett[1]['generalfeedback']);
        $this->assertEquals($question2->questionbankentryid, $rhett[1]['questionbankentryid']);
        $this->assertEquals([$quiz2->id], $rhett[1]['quizzes']);
        $this->assertCount(1, $rhett[1]['revisions']);
        $this->assertEquals(reset($question2versions)->questionid, $rhett[1]['revisions'][0]);

        $this->assertEquals($question3->id, $rhett[2]['id']);
        $this->assertEquals(util::format_string($question3->name, $context3), $rhett[2]['name']);
        $this->assertEquals(
            util::format_text($question3->questiontext, $question3->questiontextformat, $context3)[0],
            $rhett[2]['text']
        );
        $this->assertEquals($question3->defaultmark, $rhett[2]['defaultmarks']);
        $this->assertEquals($question3->qtype, $rhett[2]['type']);
        $this->assertEquals('', $rhett[2]['generalfeedback']);
        $this->assertEquals($question3->questionbankentryid, $rhett[2]['questionbankentryid']);
        $this->assertEquals([$quiz2->id, $quiz3->id], $rhett[2]['quizzes']);
        $this->assertCount(2, $rhett[2]['revisions']);
        $this->assertEquals(reset($question3versions)->questionid, $rhett[2]['revisions'][0]);
        $this->assertEquals(next($question3versions)->questionid, $rhett[2]['revisions'][1]);
    }

    /**
     * Tests "get_questions" endpoint and check for grader info in the payload.
     */
    public function test_get_questions_graderinfo(): void {
        $this->setAdminUser();

        // Create a course category, a course, and a quiz.
        $coursecategory = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Foo']);
        $cm = get_course_and_cm_from_instance($quiz, 'quiz')[1];
        $context = context_module::instance($cm->id);

        // Add questions to quizzes.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        // Create an Essay question without grader info.
        $graderinfo1 = '<p>Foobar</p>';
        $question1 = $questiongenerator->create_question(
            'essay',
            null,
            ['category' => $category->id, 'graderinfo' => ['text' => $graderinfo1, 'format' => FORMAT_HTML]]
        );
        // Create another Essay question, this one has some grader info.
        $question2 = $questiongenerator->create_question('essay', null, ['category' => $category->id]);
        // Create a non-Essay question.
        $question3 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        // Add all questions to the quiz.
        quiz_add_quiz_question($question1->id, $quiz);
        quiz_add_quiz_question($question2->id, $quiz);
        quiz_add_quiz_question($question3->id, $quiz);

        // Retrieve all questions.
        $rhett = external_api::clean_returnvalue(
            api::get_questions_returns(),
            api::get_questions([$quiz->id])
        );

        // Check output specifically for grader info.
        $this->assertCount(3, $rhett);
        $this->assertEquals($question1->id, $rhett[0]['id']);
        $this->assertEquals($graderinfo1, $rhett[0]['options']['graderinfo']);
        $this->assertEquals($question2->id, $rhett[1]['id']);
        $this->assertEquals('', $rhett[1]['options']['graderinfo']);
        $this->assertEquals($question3->id, $rhett[2]['id']);
        // True/false questions don't have grader info.
        $this->assertArrayNotHasKey('graderinfo', $rhett[2]['options']);
    }

    /**
     * Tests "get_questions" endpoint and check for answers in the payload.
     */
    public function test_get_questions_answers(): void {
        $this->setAdminUser();

        // Create a course category, a course, and a quiz.
        $coursecategory = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course1->id, 'name' => 'Foo']);
        $cm = get_course_and_cm_from_instance($quiz, 'quiz')[1];
        $context = context_module::instance($cm->id);

        // Add questions to quizzes.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        // Create an Essay question.
        $question1 = $questiongenerator->create_question('essay', null, ['category' => $category->id]);
        // Create multi-choice question.
        $question2 = $questiongenerator->create_question('multichoice', null, ['category' => $category->id]);
        // Create a true/false question.
        $question3 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);

        // Add all questions to the quiz.
        quiz_add_quiz_question($question1->id, $quiz);
        quiz_add_quiz_question($question2->id, $quiz);
        quiz_add_quiz_question($question3->id, $quiz);

        // Retrieve all questions.
        $rhett = external_api::clean_returnvalue(
            api::get_questions_returns(),
            api::get_questions([$quiz->id])
        );

        // Check output specifically for grader info.
        $this->assertCount(3, $rhett);
        $this->assertEquals($question1->id, $rhett[0]['id']);
        // Essay questions don't have answers.
        $this->assertArrayNotHasKey('answers', $rhett[0]['options']);
        // Check the answers to the multi-choice question.
        $this->assertEquals($question2->id, $rhett[1]['id']);
        $this->assertCount(4, $rhett[1]['options']['answers']);
        $this->assertEquals('One', $rhett[1]['options']['answers'][0]['text']);
        $this->assertEquals(0.5, $rhett[1]['options']['answers'][0]['grade']);
        $this->assertEquals('One is odd.', $rhett[1]['options']['answers'][0]['feedback']);
        $this->assertEquals('Two', $rhett[1]['options']['answers'][1]['text']);
        $this->assertEquals(0.0, $rhett[1]['options']['answers'][1]['grade']);
        $this->assertEquals('Two is even.', $rhett[1]['options']['answers'][1]['feedback']);
        $this->assertEquals('Three', $rhett[1]['options']['answers'][2]['text']);
        $this->assertEquals(0.5, $rhett[1]['options']['answers'][2]['grade']);
        $this->assertEquals('Three is odd.', $rhett[1]['options']['answers'][2]['feedback']);
        $this->assertEquals('Four', $rhett[1]['options']['answers'][3]['text']);
        $this->assertEquals(0.0, $rhett[1]['options']['answers'][3]['grade']);
        $this->assertEquals('Four is even.', $rhett[1]['options']['answers'][3]['feedback']);
        // Check the answers to the true/false question.
        $this->assertEquals($question3->id, $rhett[2]['id']);
        $this->assertCount(2, $rhett[2]['options']['answers']);
        $this->assertEquals('True', $rhett[2]['options']['answers'][0]['text']);
        $this->assertEquals(1.0, $rhett[2]['options']['answers'][0]['grade']);
        $this->assertEquals('This is the right answer.', $rhett[2]['options']['answers'][0]['feedback']);
        $this->assertEquals('False', $rhett[2]['options']['answers'][1]['text']);
        $this->assertEquals(0.0, $rhett[2]['options']['answers'][1]['grade']);
        $this->assertEquals('This is the wrong answer.', $rhett[2]['options']['answers'][1]['feedback']);
    }

    /**
     * Tests input parameters definition for "get_attempts" endpoint.
     */
    public function test_get_attempts_parameters(): void {
        $structure = api::get_attempts_parameters();
        $this->assertCount(1, $structure->keys);

        $innerstructure = $structure->keys['quizids'];
        $this->assertTrue( $innerstructure instanceof external_multiple_structure);
        $this->assertEquals('List of quiz IDs.', $innerstructure->desc);
        $this->assertEquals(VALUE_REQUIRED, $innerstructure->required);

        $componentvalue = $innerstructure->content;
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Quiz ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests return value definition for "get_attempts" endpoint.
     */
    public function test_get_attempts_returns(): void {
        $structure = api::get_attempts_returns();
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure = $structure->content;
        $this->assertTrue($innerstructure instanceof external_single_structure);
        $this->assertCount(6, $innerstructure->keys);

        $componentvalue = $innerstructure->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Attempt ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['quizid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Quiz ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['userid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('User ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['timestart'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Timestamp of when this attempt was started.', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['timefinish'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Timestamp of when this attempt was finished.', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['questions'];
        $this->assertTrue($componentvalue instanceof external_multiple_structure);

        $innerstructure2 = $componentvalue->content;
        $this->assertTrue($innerstructure2 instanceof external_single_structure);

        $componentvalue = $innerstructure2->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Question ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure2->keys['mark'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Mark received', $componentvalue->desc);
        $this->assertEquals(PARAM_FLOAT, $componentvalue->type);

        $componentvalue = $innerstructure2->keys['answer'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('Answer given', $componentvalue->desc);
        $this->assertEquals(PARAM_RAW, $componentvalue->type);
    }

    /**
     * Tests "get_attempts" endpoint.
     */
    public function test_get_attempts(): void {
        global $DB;
        $this->setAdminUser();

        // Create a course category.
        $coursecategory = $this->getDataGenerator()->create_category();
        // Create a course in this category.
        $course = $this->getDataGenerator()->create_course(['category' => $coursecategory->id]);
        // Create a quiz in this course.
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'name' => 'Foo', 'sumgrades' => 2]);
        // Get a hold of the quiz module contexts.
        list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
        // Create three questions and add them to the quiz.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $question1 = $questiongenerator->create_question(
            'truefalse',
            null,
            ['category' => $category->id]
        );
        $question2 = $questiongenerator->create_question(
            'numerical',
            null,
            ['category' => $category->id]
        );

        quiz_add_quiz_question($question1->id, $quiz);
        quiz_add_quiz_question($question2->id, $quiz);

        // Retrieve attempts for the quiz, should come up empty-handed.
        $rhett = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );
        $this->assertEmpty($rhett);

        // Create users and enroll them as students in the course.
        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        // Simulate a quiz attempt with the given student and answers.
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptquiz = function(object $student, array $responses, int $timestart, int $timefinish)
            use ($quiz, $studentrole, $questiongenerator): quiz_attempt {
            // Create a new quiz attempt.
            $quizsettings = quiz_settings::create($quiz->id, $student->id);
            $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizsettings->get_context());
            $quba->set_preferred_behaviour($quizsettings->get_quiz()->preferredbehaviour);
            $attemptnumber = count(quiz_get_user_attempts($quizsettings->get_quizid(), $student->id)) + 1;
            $attempt = quiz_create_attempt($quizsettings, $attemptnumber, false, $timestart, false, $student->id);
            quiz_start_new_attempt($quizsettings, $quba, $attempt, $attemptnumber, $timestart);
            quiz_attempt_save_started($quizsettings, $quba, $attempt);
            $attemptobj = quiz_attempt::create($attempt->id);
            // Answer all questions.
            $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
                $attemptobj->get_question_usage(),
                $responses,
                true,
            );
            $attemptobj->process_submitted_actions($timestart, false, $postdata);
            // Finish the attempt.
            $attemptobj->process_attempt($timefinish, true, false, 1);
            return $attemptobj;
        };

        $timestart1 = (new DateTime('2024-04-02 14:00:00'))->getTimestamp();
        $timefinish1 = (new DateTime('2024-04-02 15:00:00'))->getTimestamp();
        $timestart2 = (new DateTime('2024-04-03 12:00:00'))->getTimestamp();
        $timefinish2 = (new DateTime('2024-04-03 13:00:00'))->getTimestamp();

        $answers1 = [1 => 'True', 2 => '100'];
        $answers2 = [1 => 'False', 2 => '3.14'];
        $attemptobj1 = $attemptquiz($student1, $answers1, $timestart1, $timefinish1);
        $attemptobj2 = $attemptquiz($student2, $answers2, $timestart2, $timefinish2);

        // Retrieve attempts for the quiz again.
        $rhett = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );

        // Check the response.
        $this->assertCount(2, $rhett);
        $this->assertEquals($attemptobj1->get_attempt()->id, $rhett[0]['id']);
        $this->assertEquals($quiz->id, $rhett[0]['quizid']);
        $this->assertEquals($student1->id, $rhett[0]['userid']);
        $this->assertEquals($timestart1, $rhett[0]['timestart']);
        $this->assertEquals($timefinish1, $rhett[0]['timefinish']);
        $this->assertCount(2, $rhett[0]['questions']);
        $this->assertEquals($question1->id, $rhett[0]['questions'][0]['id']);
        $this->assertEquals($attemptobj1->get_question_usage()->get_question_attempt(1)->get_database_id(), $rhett[0]['questions'][0]['attemptid']);
        $this->assertEquals(1.0, $rhett[0]['questions'][0]['mark']); // Correct answer.
        $this->assertEquals($answers1[1], $rhett[0]['questions'][0]['answer']);
        $this->assertEquals($question2->id, $rhett[0]['questions'][1]['id']);
        $this->assertEquals($attemptobj1->get_question_usage()->get_question_attempt(2)->get_database_id(), $rhett[0]['questions'][1]['attemptid']);
        $this->assertEquals(0.0, $rhett[0]['questions'][1]['mark']); // Wrong answer.
        $this->assertEquals($answers1[2], $rhett[0]['questions'][1]['answer']);

        $this->assertEquals($attemptobj2->get_attempt()->id, $rhett[1]['id']);
        $this->assertEquals($quiz->id, $rhett[1]['quizid']);
        $this->assertEquals($student2->id, $rhett[1]['userid']);
        $this->assertEquals($timestart2, $rhett[1]['timestart']);
        $this->assertEquals($timefinish2, $rhett[1]['timefinish']);
        $this->assertCount(2, $rhett[1]['questions']);
        $this->assertEquals($question1->id, $rhett[1]['questions'][0]['id']);
        $this->assertEquals($attemptobj2->get_question_usage()->get_question_attempt(1)->get_database_id(), $rhett[1]['questions'][0]['attemptid']);
        $this->assertEquals(0.0, $rhett[1]['questions'][0]['mark']); // Wrong answer.
        $this->assertEquals($answers2[1], $rhett[1]['questions'][0]['answer']);
        $this->assertEquals($question2->id, $rhett[1]['questions'][1]['id']);
        $this->assertEquals($attemptobj2->get_question_usage()->get_question_attempt(2)->get_database_id(), $rhett[1]['questions'][1]['attemptid']);
        $this->assertEquals(1.0, $rhett[1]['questions'][1]['mark']); // Correct answer.
        $this->assertEquals($answers2[2], $rhett[1]['questions'][1]['answer']);
    }

    /**
     * Tests input parameters definition for "get_users" endpoint.
     */
    public function test_get_users_parameters(): void {
        $structure = api::get_users_parameters();
        $this->assertCount(1, $structure->keys);

        $innerstructure = $structure->keys['userids'];
        $this->assertTrue( $innerstructure instanceof external_multiple_structure);
        $this->assertEquals('List of user IDs.', $innerstructure->desc);
        $this->assertEquals(VALUE_REQUIRED, $innerstructure->required);

        $componentvalue = $innerstructure->content;
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('User ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);
    }

    /**
     * Tests return value definition for "get_users" endpoint.
     */
    public function test_get_users_returns(): void {
        $structure = api::get_users_returns();
        $this->assertTrue($structure instanceof external_multiple_structure);

        $innerstructure = $structure->content;
        $this->assertTrue($innerstructure instanceof external_single_structure);
        $this->assertCount(2, $innerstructure->keys);

        $componentvalue = $innerstructure->keys['id'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('User ID', $componentvalue->desc);
        $this->assertEquals(PARAM_INT, $componentvalue->type);

        $componentvalue = $innerstructure->keys['ucid'];
        $this->assertTrue( $componentvalue instanceof external_value);
        $this->assertEquals(VALUE_REQUIRED, $componentvalue->required);
        $this->assertEquals('UC ID', $componentvalue->desc);
        $this->assertEquals(PARAM_TEXT, $componentvalue->type);
    }

    /**
     * Tests "get_users" endpoint.
     */
    public function test_get_users(): void {
        $this->setAdminUser();
        // Generate some users.
        // The third one is soft-deleted, and should not be retrievable via the API.
        $user1 = $this->getDataGenerator()->create_user(['idnumber' => 'xx0000001']);
        $user2 = $this->getDataGenerator()->create_user(['idnumber' => 'xx0000002']);
        $user3 = $this->getDataGenerator()->create_user(['idnumber' => 'xx0000003', 'deleted' => true]);

        // Check that the generated users have the given campus IDs.
        $this->assertEquals('xx0000001', $user1->idnumber);
        $this->assertEquals('xx0000002', $user2->idnumber);

        // Retrieve users.
        $rhett = external_api::clean_returnvalue(
            api::get_users_returns(),
            api::get_users([$user1->id, $user2->id, $user3->id])
        );

        // Check the retrieved users.
        $this->assertCount(2, $rhett);
        $this->assertEquals($user1->id, $rhett[0]['id']);
        $this->assertEquals($user1->idnumber, $rhett[0]['ucid']);
        $this->assertEquals($user2->id, $rhett[1]['id']);
        $this->assertEquals($user2->idnumber, $rhett[1]['ucid']);
    }

    /**
     * Tests the input parameters definition for the "set_question_attempt_mark" endpoint.
     */
    public function test_set_question_attempt_mark_parameters(): void {
        // Retrieve the parameter structure defined by the API.
        $structure = api::set_question_attempt_mark_parameters();

        // We expect three keys: attemptid, mark, and comment.
        $this->assertCount(3, $structure->keys, 'Expected 3 keys in the parameters structure.');

        // Validate "attemptid" key.
        $attemptid = $structure->keys['attemptid'];
        $this->assertInstanceOf(external_value::class, $attemptid);
        $this->assertEquals(PARAM_INT, $attemptid->type, 'attemptid should be of type PARAM_INT.');
        $this->assertEquals('The question attempt id to set mark.', $attemptid->desc);
        // Value is required by default.
        $this->assertEquals(VALUE_REQUIRED, $attemptid->required);

        // Validate "mark" key.
        $mark = $structure->keys['mark'];
        $this->assertInstanceOf(external_value::class, $mark);
        $this->assertEquals(PARAM_TEXT, $mark->type, 'mark should be of type PARAM_TEXT.');
        $this->assertEquals('Mark for this question attempt.', $mark->desc);
        $this->assertEquals(VALUE_REQUIRED, $mark->required);

        // Validate "comment" key.
        $comment = $structure->keys['comment'];
        $this->assertInstanceOf(external_value::class, $comment);
        $this->assertEquals(PARAM_RAW, $comment->type, 'comment should be of type PARAM_RAW.');
        $this->assertEquals("Grader's comment for this question attempt (optional)", $comment->desc);
        // Check that the "comment" is optional.
        $this->assertEquals(VALUE_DEFAULT, $comment->required, 'comment should be an optional parameter.');
    }

    /**
     * Tests the return value definition for the "set_question_attempt_mark" endpoint.
     */
    public function test_set_question_attempt_mark_returns(): void {
        // Retrieve the return value structure defined by the API.
        $result = api::set_question_attempt_mark_returns();

        $this->assertEquals(PARAM_INT, $result->type, 'result should be of type PARAM_INT.');
        $this->assertEquals('A value like 0 => OK, 1 => FAILED as defined in lib/grade/constants.php', $result->desc);
        // Check that the "result" is required.
        $this->assertEquals(VALUE_REQUIRED, $result->required, 'result should be a required parameter.');
    }

    /**
     * Tests the "set_question_attempt_mark" endpoint.
     *
     * This test creates a course, a quiz module, and a dummy quiz attempt record.
     * It then calls the API function with valid parameters and verifies that
     * the expected result is returned.
     */
    public function test_set_question_attempt_mark(): void {
        global $DB;

        // Set up the test environment.
        $this->setAdminUser();
        [$course, $quiz] = $this->create_course_and_quiz();
        $question = $this->create_question($quiz);
        // $attemptid = $this->create_quiz_attempt($quiz, $student);

        // Retrieve attempts for the quiz, should come up empty-handed.
        $result = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );
        $this->assertEmpty($result);

        // Create a user and enroll them as student in the course.
        $student = $this->create_student_and_enroll($course);

        // Create a dummy quiz attempt record.
        $attempt = $this->create_and_start_quiz_attempt($quiz, $student);

        // Answer the question
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptobj = quiz_attempt::create($attempt->id);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
            $attemptobj->get_question_usage(),
            [1 => 'True'],
            true,
        );
        $attemptobj->process_submitted_actions(time(), false, $postdata);
        // Finish the attempt.
        $attemptobj->process_attempt(time(), true, false, 1);

        // Get the question attempt ID.
        $qa = $attemptobj->get_question_usage()->get_question_attempt(1);
        $attemptid = $qa->get_database_id();

        // Call the API function with valid parameters.
        $result = external_api::clean_returnvalue(
            api::set_question_attempt_mark_returns(),
            api::set_question_attempt_mark($attemptid, 1.0, 'Good job!')
        );
        // Assert the result.
        $this->assertEquals(GRADE_UPDATE_OK, $result);

        // Check the database to ensure the mark was set correctly.
        $sql = 'SELECT qasd.*
                FROM {question_attempt_steps} qas
                JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                WHERE qas.questionattemptid = :qaid AND qasd.name = :markingfield
                ORDER BY qas.sequencenumber DESC LIMIT 1';
        $params = ['qaid' => $attemptid, 'markingfield' => '-mark'];
        $qasd = $DB->get_record_sql($sql, $params);

        // Assert mark in the database.
        $this->assertEquals(1.0, $qasd->value);

        // Assert comment in the database.
        $params = ['qaid' => $attemptid, 'markingfield' => '-comment'];
        $qasd = $DB->get_record_sql($sql, $params);

        $this->assertEquals('Good job!', $qasd->value);
    }

    /**
     * Tests an invalid execution of the "set_question_attempt_mark" endpoint.
     *
     * This test creates a course, a quiz module, and a dummy quiz attempt record.
     * It then calls the API function with an invalid attempt ID and verifies that
     * the expected exception is thrown.
     */
    public function test_set_question_attempt_mark_invalid_attemptid() {
        global $DB;

        // Expect an exception when calling the API with an invalid attempt ID.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid question attempt ID.');

        // Call the method with an invalid attempt ID.
        api::set_question_attempt_mark(0, '1.0', 'Invalid attempt');
        api::set_question_attempt_mark(99999, 1.0, 'Invalid attempt');
    }

    /**
     * Test set_question_attempt_mark with invalid mark.
     */
    public function test_set_question_attempt_mark_invalid_mark() {
        global $DB;

        // Set up the test environment.
        $this->setAdminUser();
        [$course, $quiz] = $this->create_course_and_quiz();
        $question = $this->create_question($quiz);

        // Retrieve attempts for the quiz, should come up empty-handed.
        $result = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );
        $this->assertEmpty($result);

        // Create a user and enroll them as student in the course.
        $student = $this->create_student_and_enroll($course);

        // Create a dummy quiz attempt record.
        $attempt = $this->create_and_start_quiz_attempt($quiz, $student);

        // Answer the question
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptobj = quiz_attempt::create($attempt->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
            $attemptobj->get_question_usage(),
            [1 => 'True'],
            true,
        );
        $attemptobj->process_submitted_actions(time(), false, $postdata);
        // Finish the attempt.
        $attemptobj->process_attempt(time(), true, false, 1);

        // Get the question attempt ID.
        $qa = $attemptobj->get_question_usage()->get_question_attempt(1);
        $attemptid = $qa->get_database_id();

        // Call the API function with valid parameters.
        $result = external_api::clean_returnvalue(
            api::set_question_attempt_mark_returns(),
            api::set_question_attempt_mark($attemptid, 'invalid_mark', 'Invalid mark')
        );

        // Assert the result.
        $this->assertEquals(GRADE_UPDATE_FAILED, $result);
    }

    /**
     * Test set_question_attempt_mark with invalid comment.
     */
    public function test_set_question_attempt_mark_invalid_comment() {
        global $DB;

        // Set up the test environment.
        $this->setAdminUser();
        [$course, $quiz] = $this->create_course_and_quiz();
        $question = $this->create_question($quiz);


        // Retrieve attempts for the quiz, should come up empty-handed.
        $result = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );
        $this->assertEmpty($result);

        // Create a user and enroll them as student in the course.
        $student = $this->create_student_and_enroll($course);

        // Create a dummy quiz attempt record.
        $attempt = $this->create_and_start_quiz_attempt($quiz, $student);

        // Answer the question
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptobj = quiz_attempt::create($attempt->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
            $attemptobj->get_question_usage(),
            [1 => 'True'],
            true,
        );
        $attemptobj->process_submitted_actions(time(), false, $postdata);
        // Finish the attempt.
        $attemptobj->process_attempt(time(), true, false, 1);

        // Get the question attempt ID.
        $qa = $attemptobj->get_question_usage()->get_question_attempt(1);
        $attemptid = $qa->get_database_id();

        // Call the method with an invalid comment.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid parameter value detected (Invalid external api parameter: the value is " Invalid Comment", the server was expecting "raw" type): Invalid external api parameter: the value is " Invalid Comment", the server was expecting "raw" type');

        // Call the API function with valid parameters.
        $result = external_api::clean_returnvalue(
            api::set_question_attempt_mark_returns(),
            api::set_question_attempt_mark($attemptid, '1.0', "\0Invalid\0Comment")
            // api::set_question_attempt_mark($attemptid, '1.0', null)
            // api::set_question_attempt_mark($attemptid, '1.0', str_repeat('a', 10001))
        );
    }

    /**
     * Test set_question_attempt_mark with unfinished attempt.
     */
    public function test_set_question_attempt_mark_unfinished_attempt() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a mock quiz attempt and question attempt.
        [$course, $quiz] = $this->create_course_and_quiz();
        $question = $this->create_question($quiz);

        // Create a user and enroll them as student in the course.
        $student = $this->create_student_and_enroll($course);

        // Create a dummy quiz attempt record.
        $attempt = $this->create_and_start_quiz_attempt($quiz, $student);

        // Answer the question
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptobj = quiz_attempt::create($attempt->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
            $attemptobj->get_question_usage(),
            [1 => 'True'],
            true,
        );
        $attemptobj->process_submitted_actions(time(), false, $postdata);

        // Get the question attempt ID.
        $qa = $attemptobj->get_question_usage()->get_question_attempt(1);
        $attemptid = $qa->get_database_id();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Attempt has not closed yet');

        // Call the method with an unfinished attempt.
        $result = external_api::clean_returnvalue(
            api::set_question_attempt_mark_returns(),
            api::set_question_attempt_mark($attemptid, 1.0, 'Good job!')
        );
    }

    /**
     * Test set_question_attempt_mark with missing 'mod/quiz:grade' capability.
     */
    public function test_set_question_attempt_mark_missing_quiz_grade_capability() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up the test environment.
        $this->setAdminUser();

        // Create a mock quiz attempt and question attempt.
        [$course, $quiz] = $this->create_course_and_quiz();
        $question = $this->create_question($quiz);


        // Retrieve attempts for the quiz, should come up empty-handed.
        $result = external_api::clean_returnvalue(
            api::get_attempts_returns(),
            api::get_attempts([$quiz->id])
        );
        $this->assertEmpty($result);

        // Create a user and enroll them as student in the course.
        $student = $this->create_student_and_enroll($course);

        // Create a dummy quiz attempt record.
        $attempt = $this->create_and_start_quiz_attempt($quiz, $student);

        // Answer the question
        // @see /mod/quiz/tests/external/external_test.php for reference.
        $attemptobj = quiz_attempt::create($attempt->id);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $postdata = $questiongenerator->get_simulated_post_data_for_questions_in_usage(
            $attemptobj->get_question_usage(),
            [1 => 'True'],
            true,
        );
        $attemptobj->process_submitted_actions(time(), false, $postdata);

        // Finish the attempt.
        $attemptobj->process_attempt(time(), true, false, 1);

        // Get the question attempt ID.
        $qa = $attemptobj->get_question_usage()->get_question_attempt(1);
        $attemptid = $qa->get_database_id();

        // Create a user and attempt to set a mark without proper permissions.
        $grader = $this->getDataGenerator()->create_user();
        $graderrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($grader->id, $course->id, $graderrole->id);

        $this->setUser($grader);

        // Obtain the course module ID.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $cmid = $cm->id;
        // Set the context for the quiz module.
        $context = context_module::instance($cmid);

        // Check that the user has the "mod/quiz:grade" capability.
        $this->assertTrue(has_capability('mod/quiz:grade', $context, $grader->id));

        // Remove grading capability.
        assign_capability('mod/quiz:grade', CAP_PROHIBIT, $graderrole->id, $context->id);

        // Check that the user no longer has the "mod/quiz:grade" capability.
        $this->assertFalse(has_capability('mod/quiz:grade', $context, $grader->id));

        // Expect an exception when calling the API with missing permissions.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Grade quizzes manually).');

        // Call the method without proper permissions.
        $result = external_api::clean_returnvalue(
            api::set_question_attempt_mark_returns(),
            api::set_question_attempt_mark($attemptid, '1.0', 'No permissions')
        );
    }

    /**
     * Helper methods for common setup tasks.
     */

    /**
     * Create a course and a quiz module.
     *
     * @return array The created course and quiz objects.
     */
    private function create_course_and_quiz(): array {

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        // Get a hold of the quiz module contexts.
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'name' => 'Sample Quiz', 'sumgrades' => 1]);

        return [$course, $quiz];
    }

    /**
     * Create a quiz question and add it to the quiz.
     *
     * @param object $quiz The quiz object.
     * @return object The created question object.
     */
    private function create_question($quiz): object {
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $quiz);
        return $question;
    }

    /**
     * Create a student and enroll them in the course.
     *
     * @param object $course The course object.
     * @return object The created student object.
     */
    private function create_student_and_enroll($course): object {
        $student = $this->getDataGenerator()->create_user();
        //$studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        return $student;
    }

    /**
     * Create and start a quiz attempt for a given student.
     *
     * @param object $quiz The quiz object.
     * @param object $student The student object.
     * @return object The created quiz attempt.
     */
    private function create_and_start_quiz_attempt($quiz, $student): object {
        $quizsettings = quiz_settings::create($quiz->id, $student->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizsettings->get_context());
        $quba->set_preferred_behaviour($quizsettings->get_quiz()->preferredbehaviour);
        $attemptnumber = count(quiz_get_user_attempts($quizsettings->get_quizid(), $student->id)) + 1;
        $attempt = quiz_create_attempt($quizsettings, $attemptnumber, false, time(), false, $student->id);
        quiz_start_new_attempt($quizsettings, $quba, $attempt, 1, time());
        quiz_attempt_save_started($quizsettings, $quba, $attempt);

        return $attempt;
    }
}