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
 * Library of functions for the UCSF SOM API plugin.
 *
 * @package    tool_ucsfsomapi
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve the requested question-related file for the tool_ucsfsomapi plugin.
 * This is a callback implementing the <code>question_preview_pluginfile</code> hook.
 *
 * @link https://docs.moodle.org/dev/Callbacks
 * @see quiz_statistics_question_preview_pluginfile()
 *
 * @package  tool_ucsfsomapi
 * @category files
 * @param context $previewcontext The quiz context.
 * @param int $questionid The question id.
 * @param context $filecontext The file context.
 * @param string $filecomponent The component the file belongs to.
 * @param string $filearea The file area (i.e. questiontext, generalfeedback, answer).
 * @param array $args Extra file args.
 * @param bool $forcedownload Force download, or not.
 * @param array $options Additional options affecting the file serving.
 */
function tool_ucsfsomapi_question_preview_pluginfile(
    $previewcontext,
    $questionid,
    $filecontext,
    $filecomponent,
    $filearea,
    $args,
    $forcedownload,
    $options = []
): void {
    global $CFG;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    list($context, $course, $cm) = get_context_info_array($previewcontext->id);
    require_login($course, false, $cm);

    // Check capabilities. Apply the same check as on the corresponding tool_ucsfsomapi_get_questions API endpoint.
    require_capability('moodle/question:viewall', $context);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$filecontext->id}/{$filecomponent}/{$filearea}/$questionid/{$relativepath}";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
