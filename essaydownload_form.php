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
 * This file defines the settings form for the quiz essaydownload report.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Quiz essaydownload report settings form.
 *
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

/**
 * Class defining the form for a {@see quiz_essaydownload_report}.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_essaydownload_form extends moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencespage', get_string('options', 'quiz_essaydownload'));
        $this->standard_preference_fields($mform);
        $mform->addElement('submit', 'download', get_string('download'));
    }

    /**
     * Add the preference fields that we offer.
     *
     * @param MoodleQuickForm $mform the form
     * @return void
     */
    protected function standard_preference_fields(MoodleQuickForm $mform) {
        $mform->addElement(
            'select',
            'groupby',
            get_string('groupby', 'quiz_essaydownload'),
            [
                'byattempt' => get_string('byattempt', 'quiz_essaydownload'),
                'byquestion' => get_string('byquestion', 'quiz_essaydownload'),
            ]
        );
        $mform->setType('groupby', PARAM_ALPHA);
        $mform->addHelpButton('groupby', 'groupby', 'quiz_essaydownload');

        $mform->addElement(
            'advcheckbox',
            'questiontext',
            get_string('whattoinclude', 'quiz_essaydownload'),
            get_string('includequestiontext', 'quiz_essaydownload')
        );
        $mform->addElement('advcheckbox', 'responsetext', get_string('includeresponsetext', 'quiz_essaydownload'));
        $mform->addElement('advcheckbox', 'attachments', get_string('includeattachments', 'quiz_essaydownload'));

        $mform->addElement(
            'select',
            'nameordering',
            get_string('nameordering', 'quiz_essaydownload'),
            [
                'lastfirst' => get_string('lastfirst', 'quiz_essaydownload'),
                'firstlast' => get_string('firstlast', 'quiz_essaydownload'),
            ]
        );
        $mform->setType('nameordering', PARAM_ALPHA);

        $mform->addElement(
            'advcheckbox',
            'shortennames',
            get_string('additionalsettings', 'quiz_essaydownload'),
            get_string('shortennames', 'quiz_essaydownload')
        );
        $mform->addHelpButton('shortennames', 'shortennames', 'quiz_essaydownload');
    }
}
