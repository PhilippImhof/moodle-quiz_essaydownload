<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for the quiz_essaydownload plugin
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Essay responses downloader plugin (quiz_essaydownload)';
$string['plugindescription'] = 'Download text answers and attachment files submitted in response to essay questions in a quiz.';

$string['essaydownload'] = 'Download essay responses';
$string['noessayquestion'] = 'This quiz does not contain any essay questions.';
$string['nothingtodownload'] = 'Nothing to download';

$string['options'] = 'Options';
$string['groupby'] = 'Group by';
$string['groupby_help'] = 'The archive can be structured by question or by attempt:<ul><li>If you group by question, the archive will have a folder for every question. Inside each folder, you will have a folder for every attempt.</li><li>If you group by attempt, the archive will have a folder for every attempt. Inside each folder, you will have a folder for every question.</li></ul>';
$string['byquestion'] = 'Question';
$string['byattempt'] = 'Attempt';
$string['whattoinclude'] = 'What to include';
$string['includeresponsetext'] = 'Include response text';
$string['includequestiontext'] = 'Include question text';
$string['includeattachments'] = 'Include attachments, if there are any';

$string['privacy:metadata'] = 'The quiz essay download plugin does not store any personal data about any user.';
