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

use Generator;
use quiz_essaydownload_form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/essaydownload/essaydownload_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/essaydownload/report.php');

/**
 * Tests for Essay responses downloader plugin (quiz_essaydownload)
 *
 * @package   quiz_essaydownload
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \quiz_essaydownload_form
 */
final class form_test extends \advanced_testcase {
    /**
     * Data provider.
     *
     * @return Generator
     */
    public static function provide_templates(): Generator {
        yield [false, ''];
        yield [false, ' '];
        yield [false, 'foobar'];
        yield [false, '%firstnme%'];
        yield [false, '%firstname'];
        yield [false, 'firstname%'];
        yield [true, '%firstname%'];
        yield [true, '%lastname%'];
        yield [true, '%userid%'];
        yield [true, '%username%'];
        yield [true, '%idnumber%'];
        yield [true, '%firstname%-%lastname%'];
        yield [true, '%lastname% %firstname%'];
        yield [true, '%lastname% %firstname% (%userid%)'];
        yield [true, '%lastname% %firstname% (%userid%, %username%)'];
        yield [true, '%lastname% %firstname% (%userid%, %username%, %idnumber%)'];
    }

    /**
     * Test validation of name and filename template strings.
     *
     * @dataProvider provide_templates
     *
     * @param bool $expected expected validation result
     * @param string $template template to be used
     * @return void
     */
    public function test_is_valid_template(bool $expected, string $template): void {
        $this->resetAfterTest();

        // Use reflection to access the validation method.
        $method = new \ReflectionMethod(quiz_essaydownload_form::class, 'is_valid_template');
        $method->setAccessible(true);

        // Validate and check the result.
        $result = $method->invoke(null, $template);
        self::assertEquals($expected, $result);
    }
}
