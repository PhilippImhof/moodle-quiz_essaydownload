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

    /** @var bool whether to include attachments (if there are) in the archive */
    public $attachments = true;

    /** @var string file format TXT or PDF */
    public $fileformat = 'pdf';

    /** @var bool whether to try to work around Atto bug MDL-67360 */
    public $fixremfontsize = true;

    /** @var string base font family for PDF export */
    public $font = 'sansserif';

    /** @var int font size for PDF export */
    public $fontsize = 12;

    /** @var string how to organise the sub folders in the archive (by question or by attempt) */
    public $groupby = 'byattempt';

    /** @var bool whether a footer containing the page number should be added to PDFs */
    public $includefooter = false;

    /** @var float line spacing for PDF export */
    public $linespacing = 1;

    /** @var int bottom margin for PDF export */
    public $marginbottom = 20;

    /** @var int left margin for PDF export */
    public $marginleft = 20;

    /** @var int right margin for PDF export */
    public $marginright = 20;

    /** @var int top margin for PDF export */
    public $margintop = 20;

    /** @var string whether to have the last name or the first name first */
    public $nameordering = 'lastfirst';

    /** @var string page format for PDF export */
    public $pageformat = 'a4';

    /** @var bool whether to include the question text in the archive */
    public $questiontext = true;

    /** @var bool whether to shorten file and path names to workaround a Windows issue */
    public $shortennames = false;

    /** @var string which source to use: plain-text summary or original HTML text */
    public $source = 'html';

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

        $toform->attachments = $this->attachments;
        $toform->fileformat = $this->fileformat;
        $toform->fixremfontsize = $this->fixremfontsize;
        $toform->font = $this->font;
        $toform->fontsize = $this->fontsize;
        $toform->groupby = $this->groupby;
        $toform->includefooter = $this->includefooter;
        $toform->linespacing = $this->linespacing;
        $toform->marginbottom = $this->marginbottom;
        $toform->marginleft = $this->marginleft;
        $toform->marginright = $this->marginright;
        $toform->margintop = $this->margintop;
        $toform->nameordering = $this->nameordering;
        $toform->pageformat = $this->pageformat;
        $toform->questiontext = $this->questiontext;
        $toform->shortennames = $this->shortennames;
        $toform->source = $this->source;

        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     *
     * @param object $fromform data from the settings form
     */
    public function setup_from_form_data($fromform): void {
        $this->attachments = $fromform->attachments;
        $this->fileformat = $fromform->fileformat;
        $this->fixremfontsize = $fromform->fixremfontsize;
        $this->font = $fromform->font ?? '';
        $this->fontsize = $fromform->fontsize ?? '';
        $this->groupby = $fromform->groupby;
        $this->includefooter = $fromform->includefooter;
        $this->linespacing = $fromform->linespacing ?? '';
        $this->marginbottom = $fromform->marginbottom ?? '';
        $this->marginleft = $fromform->marginleft ?? '';
        $this->marginright = $fromform->marginright ?? '';
        $this->margintop = $fromform->margintop ?? '';
        $this->nameordering = $fromform->nameordering;
        $this->pageformat = $fromform->pageformat ?? '';
        $this->questiontext = $fromform->questiontext;
        $this->shortennames = $fromform->shortennames;
        $this->source = $fromform->source ?? '';
    }

    /**
     * Set the fields of this object from the URL parameters.
     */
    public function setup_from_params() {
        $this->attachments = optional_param('attachments', $this->attachments, PARAM_BOOL);
        $this->fileformat = optional_param('fileformat', $this->fileformat, PARAM_ALPHA);
        $this->fixremfontsize = optional_param('fixremfontsize', $this->fixremfontsize, PARAM_BOOL);
        $this->font = optional_param('font', $this->font, PARAM_ALPHA);
        $this->fontsize = optional_param('fontsize', $this->fontsize, PARAM_INT);
        $this->groupby = optional_param('groupby', $this->groupby, PARAM_ALPHA);
        $this->includefooter = optional_param('includefooter', $this->includefooter, PARAM_BOOL);
        $this->linespacing = optional_param('linespacing', $this->linespacing, PARAM_FLOAT);
        $this->marginbottom = optional_param('marginbottom', $this->marginbottom, PARAM_INT);
        $this->marginleft = optional_param('marginleft', $this->marginleft, PARAM_INT);
        $this->marginright = optional_param('marginright', $this->marginright, PARAM_INT);
        $this->margintop = optional_param('margintop', $this->margintop, PARAM_INT);
        $this->nameordering = optional_param('nameordering', $this->nameordering, PARAM_ALPHA);
        $this->pageformat = optional_param('pageformat', $this->pageformat, PARAM_ALPHA);
        $this->questiontext = optional_param('questiontext', $this->questiontext, PARAM_BOOL);
        $this->shortennames = optional_param('shortennames', $this->shortennames, PARAM_BOOL);
        $this->source = optional_param('source', $this->source, PARAM_ALPHA);
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
     * Deal with conflicting options, e.g. user requesting TXT output, but HTML source.
     */
    public function resolve_dependencies() {
        if ($this->fileformat === 'txt') {
            $this->source = 'plain';
        }
    }
}
