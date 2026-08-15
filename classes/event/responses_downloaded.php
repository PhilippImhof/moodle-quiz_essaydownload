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
 * The quiz_essaydownload "responses downloaded" event.
 *
 * @package    quiz_essaydownload
 * @copyright  2026 Philipp E. Imhof
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_essaydownload\event;

/**
 * The quiz_essaydownload "responses downloaded" event class.
 *
 * @package   quiz_essaydownload
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class responses_downloaded extends responses_downloadfailed {
    #[\Override]
    public static function get_name() {
        return get_string('eventresponsesdownloaded', 'quiz_essaydownload');
    }

    #[\Override]
    public function get_description() {
        $source = $this->other['source'];
        $outputformat = $this->other['outputformat'];
        $attachments = $this->other['attachments'];
        $questiontext = $this->other['questiontext'];
        $attempts = $this->other['attempts'];
        $group = $this->other['group'];

        return "The user with id '$this->userid' used the quiz_essaydownload plugin to download essay responses " .
            "($source) for the quiz with course module id '$this->contextinstanceid' in the format " .
            "$outputformat. Attachments $attachments, question text $questiontext. The export contains " .
            "the following attempts per user: $attempts. Group scope: $group.";
    }

    #[\Override]
    protected function validate_data() {
        parent::validate_data();

        $fieldstocheck = ['source', 'outputformat', 'attachments', 'questiontext', 'attempts', 'group'];
        foreach ($fieldstocheck as $field) {
            if (!isset($this->other[$field])) {
                throw new \coding_exception("The '$field' value must be set in other.");
            }
        }
    }
}
