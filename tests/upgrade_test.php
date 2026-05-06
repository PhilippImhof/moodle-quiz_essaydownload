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

namespace quiz_essaydownload;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/essaydownload/classes/upgrade.php');

/**
 * Tests for Essay responses downloader plugin (quiz_essaydownload)
 *
 * @package   quiz_essaydownload
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \quiz_essaydownload_report
 */
final class upgrade_test extends \advanced_testcase {
    public function test_upgrade_legacy_nameorder(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $students = [];
        // Set the legacy preference for two users.
        for ($i = 0; $i < 2; $i++) {
            $preference = ($i % 2 === 0 ? 'lastfirst' : 'firstlast');
            $student = \phpunit_util::get_data_generator()->create_user();
            $students[] = $student;
            $this->setUser($student->id);
            set_user_preference('quiz_essaydownload_nameordering', $preference);

            // Check the preference is set.
            self::assertEquals($preference, get_user_preferences('quiz_essaydownload_nameordering'));
        }

        // Perform the conversion.
        upgrade::nameordering_to_template();

        // Fetch legacy preference. There should be no records.
        $records = $DB->get_records('user_preferences', ['name' => 'quiz_essaydownload_nameordering'], '', 'userid, name, value');
        self::assertEmpty($records);

        // Check the legacy preference was correctly converted to the template for the name and the filename.
        foreach ($students as $i => $student) {
            $expected = ($i % 2 === 0 ? '%lastname% %firstname%' : '%firstname% %lastname%');
            $this->setUser($student->id);
            $nametemplate = get_user_preferences('quiz_essaydownload_nametemplate');
            $filenametemplate = get_user_preferences('quiz_essaydownload_filenametemplate');
            self::assertEquals($expected, $nametemplate);
            self::assertEquals(str_replace(' ', '_', $expected), $filenametemplate);
        }
    }
}
