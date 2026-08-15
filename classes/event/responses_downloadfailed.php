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
 * The quiz_essaydownload "download of responses failed" event.
 *
 * @package    quiz_essaydownload
 * @copyright  2026 Philipp E. Imhof
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_essaydownload\event;

/**
 * The quiz_essaydownload "download of responses failed" event class.
 *
 * @package   quiz_essaydownload
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class responses_downloadfailed extends \mod_quiz\event\report_viewed {
    #[\Override]
    public static function get_name() {
        return get_string('eventresponsesdownloadfailed', 'quiz_essaydownload');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' tried to download essay responses for the quiz with " .
            "course module id '$this->contextinstanceid' using the quiz_essaydownload plugin, but the " .
            "archive was empty.";
    }

    #[\Override]
    public function get_url() {
        return null;
    }

    #[\Override]
    protected function validate_data() {
        // We only check the quiz ID, unlike the parent method that would also check the reportname.
        if (!isset($this->other['quizid'])) {
            throw new \coding_exception('The \'quizid\' value must be set in other.');
        }
    }
}
