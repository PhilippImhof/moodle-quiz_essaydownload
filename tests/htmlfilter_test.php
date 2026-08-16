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

/**
 * Tests for Essay responses downloader plugin (quiz_essaydownload)
 *
 * @package   quiz_essaydownload
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \quiz_essaydownload\htmlfilter
 */
final class htmlfilter_test extends \advanced_testcase {
    /**
     * Data provider.
     *
     * @return Generator
     */
    public static function provide_html_input(): Generator {
        yield ['', ''];
        yield ['<p>foo<strong>bar</strong></p>', '<p>foo<strong>bar</strong></p>'];
        yield ['<p><a href="https://www.moodle.org">Moodle</a></p>', '<p><a href="https://www.moodle.org">Moodle</a></p>'];
        yield ['[&lt;img&gt; tag removed]', '<img src="foo.jpg">'];
        yield [
            '<p>[url(...) removed from style attribute of &lt;span&gt; tag]<span style="background-image: none;">foo</span></p>',
            '<p><span style="background-image: url(\'foo.jpg\');">foo</span></p>',
        ];
    }

    /**
     * Test filtering of possibly dangerous elements.
     *
     * @param string $expected the expected HTML output
     * @param string $input the HTML input to be filtered
     * @return void
     * @dataProvider provide_html_input
     */
    public function test_filtering(string $expected, string $input): void {
        $expected = format_text($expected, FORMAT_HTML);
        $input = format_text($input, FORMAT_HTML);
        $output = trim(htmlfilter::remove_embedded_stuff($input));
        self::assertEquals($expected, $output);
    }
}
