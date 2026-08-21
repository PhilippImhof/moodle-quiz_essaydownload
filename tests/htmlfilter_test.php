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
        yield [
            '<p><img class="img-fluid" src="@@PLUGINFILE@@/foo.png"></p>',
            '<p><img class="img-fluid" src="@@PLUGINFILE@@/foo.png"></p>'
        ];
        yield ['<p>one</p><p>two</p>', '<p>one</p><p>two</p>'];
        yield ['<p>el ni&ntilde;o th&eacute; apr&egrave;s mena&ccedil;ant</p>', '<p>el niño thé après menaçant</p>'];
        yield ['<p>foo<strong>bar</strong></p>', '<p>foo<strong>bar</strong></p>'];
        yield ['<p><a href="https://www.moodle.org">Moodle</a></p>', '<p><a href="https://www.moodle.org">Moodle</a></p>'];
        yield ['<p><a href="../../../../etc/passwd">Click me!</a></p>', '<p><a href="../../../../etc/passwd">Click me!</a></p>'];
        yield ['[&lt;img&gt; tag removed]', '<img src="foo.jpg">'];
        yield [
            '<p>[&lt;img&gt; tag removed]</p>',
            '<p><img srcset="@@PLUGINFILE@@/foo.png, https://www.evil.com/foobar.jpg"></p>',
        ];
        yield [
            '<p>[url(...) removed from style attribute of &lt;span&gt; tag]<span style="background-image: none;">foo</span></p>',
            '<p><span style="background-image: url(\'foo.jpg\');">foo</span></p>',
        ];
        yield [
            '<p>[url(...) removed from style attribute of &lt;span&gt; tag]<span style="background-image: none;">foo</span></p>',
            '<p><span style="background-image: url(https://example.com/foo.png);">foo</span></p>',
        ];
        yield [
            '<p><span style="font-weight: bold; text-align: center">Hello</span></p>',
            '<p><span style="font-weight: bold; text-align: center">Hello</span></p>',
        ];
        yield [
            '<p>[src attribute removed from &lt;span&gt; tag]<span>Hello</span></p>',
            '<p><span src="https://example.com/image.png">Hello</span></p>',
        ];
        yield [
            '<p>[srcset attribute removed from &lt;span&gt; tag]<span>Hello</span></p>',
            '<p><span srcset="https://example.com/image.png">Hello</span></p>',
        ];
        yield [
            '<p>[data attribute removed from &lt;span&gt; tag]<span>Hello</span></p>',
            '<p><span data="https://example.com/data">Hello</span></p>',
        ];
        yield [
            '<p>Hello [&lt;img&gt; tag removed] world.</p>',
            '<p>Hello <img src="https://example.com/image.png"> world.</p>',
        ];
        yield [
            '<p>Hello [&lt;img&gt; tag removed] world.</p>',
            '<p>Hello <img srcset="small.png 1x, large.png 2x"> world.</p>',
        ];
        yield [
            '<p>Hello [&lt;picture&gt; tag removed] world.</p>',
            '<p>Hello <picture><source srcset="large.webp"><img src="small.png"></picture> world.</p>',
        ];
        yield [
            '<p>Before [&lt;iframe&gt; tag removed] after.</p>',
            '<p>Before <iframe src="https://moodle.org"></iframe> after.</p>',
        ];
        yield [
            '<p>Before [&lt;object&gt; tag removed] after.</p>',
            '<p>Before <object data="https://example.com/file.pdf"></object> after.</p>',
        ];
        yield [
            '<p>Before [&lt;embed&gt; tag removed] after.</p>',
            '<p>Before <embed src="https://example.com/file.swf"></embed> after.</p>',
        ];
        yield [
            '<p>Before [&lt;video&gt; tag removed] after.</p>',
            '<p>Before <video src="movie.mp4" poster="poster.jpg"><source src="movie.webm"/></video> after.</p>',
        ];
        yield [
            '<p>A <video><source src="@@PLUGINFILE@@/foo.mp4"></source></video> B</p>',
            '<p>A <video><source src="@@PLUGINFILE@@/foo.mp4"/></video> B</p>',
        ];
        yield [
            '<p>Before [&lt;video&gt; tag removed] after.</p>',
            '<p>Before <video src="movie.mp4" poster="poster.jpg"></video> after.</p>',
        ];
        yield [
            '<p>Before [&lt;audio&gt; tag removed] after.</p>',
            '<p>Before <audio src="sound.mp3"></audio> after.</p>',
        ];
        yield [
            '<p>Before [&lt;audio&gt; tag removed] after.</p>',
            '<p>Before <audio><source src="sound.mp3"/></audio> after.</p>',
        ];
        yield [
            '<p>A <audio><source src="@@PLUGINFILE@@/foo.ogg"></source></audio> B</p>',
            '<p>A <audio><source src="@@PLUGINFILE@@/foo.ogg"/></audio> B</p>',
        ];
        yield [
            '<p>Before [&lt;track&gt; tag removed] after.</p>',
            '<p>Before <track src="captions.vtt"></track> after.</p>',
        ];
        yield [
            '<p>Before [&lt;svg&gt; tag removed] after.</p>',
            '<p>Before <svg><image href="https://example.com/image.png"></image></svg> after.</p>',
        ];
        yield [
            '<p>Before [&lt;svg&gt; tag removed] after.</p>',
            '<p>Before <svg><use href="https://example.com/icons.svg#foo"></use></svg> after.</p>',
        ];
        yield [
            '<p>One [&lt;img&gt; tag removed] two <a href="https://example.com">link</a>' .
                ' three [&lt;iframe&gt; tag removed] four.</p>',
            '<p>One <img src="a.png"> two <a href="https://example.com">link</a> three <iframe src="b"></iframe> four.</p>',
        ];
        // For the following test, we remove the outer <picture> element with its entire subtree,
        // so there should be only one notice in the output.
        yield [
            '<div>Before [&lt;picture&gt; tag removed] after.</div>',
            '<div>Before <picture><source src="a.png"><img src="b.png"></picture> after.</div>',
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
        $output = trim(htmlfilter::remove_embedded_stuff($input));
        self::assertEquals($expected, $output);
    }
}
