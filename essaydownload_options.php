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
 * This file defines the setting form for the essay download report.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\attempts_report_options;

defined('MOODLE_INTERNAL') || die();

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\reports\attempts_report_options')) {
    class_alias('\mod_quiz\local\reports\attempts_report_options', '\quiz_essaydownload_options_parent_class_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_options.php');
    class_alias('\mod_quiz_attempts_report_options', '\quiz_essaydownload_options_parent_class_alias');
}

/**
 * Class to store the options for a {@see quiz_essaydownload_report}.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_essaydownload_options extends quiz_essaydownload_options_parent_class_alias {

    /** @var bool whether to include the text response files in the archive */
    public $responsetext = true;

    /** @var bool whether to include the question text in the archive */
    public $questiontext = true;

    /** @var bool whether to include attachments (if there are) in the archive */
    public $attachments = true;

    /** @var string whether to shorten file and path names to workaround a Windows issue */
    public $shortennames = false;

    /** @var string how to organise the sub folders in the archive (by question or by attempt) */
    public $groupby = 'byattempt';

    /** @var string whether to have the last name or the first name first */
    public $nameordering = 'lastfirst';

    /**
     * Constructor
     *
     * @param string $mode which report these options are for
     * @param object $quiz the settings for the quiz being reported on
     * @param object $cm the course module objects for the quiz being reported on
     * @param object $course the course settings for the coures this quiz is in
     */
    public function __construct($mode, $quiz, $cm, $course) {
        $this->mode   = $mode;
        $this->quiz   = $quiz;
        $this->cm     = $cm;
        $this->course = $course;
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data() {
        $toform = new stdClass();

        $toform->responsetext = $this->responsetext;
        $toform->questiontext = $this->questiontext;
        $toform->attachments = $this->attachments;
        $toform->shortennames = $this->shortennames;
        $toform->groupby = $this->groupby;
        $toform->nameordering = $this->nameordering;

        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     *
     * @param object $fromform data from the settings form
     */
    public function setup_from_form_data($fromform): void {
        $this->responsetext = $fromform->responsetext;
        $this->questiontext = $fromform->questiontext;
        $this->attachments = $fromform->attachments;
        $this->shortennames = $fromform->shortennames;
        $this->groupby = $fromform->groupby;
        $this->nameordering = $fromform->nameordering;
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params() {
        $this->responsetext = optional_param('responsetext', $this->responsetext, PARAM_BOOL);
        $this->questiontext = optional_param('questiontext', $this->questiontext, PARAM_BOOL);
        $this->attachments = optional_param('attachments', $this->attachments, PARAM_BOOL);
        $this->shortennames = optional_param('shortennames', $this->shortennames, PARAM_BOOL);
        $this->groupby = optional_param('groupby', $this->groupby, PARAM_ALPHA);
        $this->nameordering = optional_param('nameordering', $this->nameordering, PARAM_ALPHA);
    }

    /**
     * Override parent method, because we do not have settings that are backed by
     * user-preferences.
     */
    public function setup_from_user_preferences() {
    }

    /**
     * Override parent method, because we do not have settings that are backed by
     * user-preferences.
     */
    public function update_user_preferences() {
    }

    /**
     * Override parent method, because our settings cannot be incompatible.
     */
    public function resolve_dependencies() {
    }
}
