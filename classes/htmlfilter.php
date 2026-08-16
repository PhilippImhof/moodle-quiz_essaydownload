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

/**
 * Class to remove possibly dangerous HTML content in student responses.
 *
 * @package    quiz_essaydownload
 * @copyright  2026 Philipp Imhof
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class htmlfilter {
    /** @var array HTML tag names that could lead to the inclusion of external resources */
    const PASSIVE_ELEMENTS = [
        'img',
        'picture',
        'svg',
        'iframe',
        'frame',
        'frameset',
        'object',
        'embed',
        'video',
        'audio',
        'source',
        'track',
        'input',
        'script',
        'link',
    ];

    /** @var array HTML attributes that could lead to the inclusion of external resources  */
    const RESOURCE_ATTRIBUTES = [
        'src',
        'srcset',
        'data',
    ];

    /**
     * Take the given HTML and remove all "passive" references to (possibly dangerous) resources.
     * Using <a href="..."> is allowed, because the resource would only be accessed when the user
     * clicks the given link. Passive references, on the other hand, could lead to a server-initiated
     * access to some URL during the generation of the PDF.
     *
     * @param string $html the HTML code to be filtered, should be sanitized before
     * @return string
     */
    public static function remove_embedded_stuff(string $html): string {
        // If the string is empty, we can leave early.
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // We use the DOMDocument parser class. Note that the student's response is not an
        // entire HTML document, so it makes sense to prepend an approriate XML declaration.
        // Note that LIBXML_NONET is particularly important, because we do not want the parser
        // to access any URLs.
        $dom = new \DOMDocument('1.0', 'UTF-8');
        // We suppress errors or warnings from the parser, because it might output them for
        // valid HTML 5 stuff. Storing the previous state here.
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        // Clear the errors and reset the error management to its previous state.
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // If the document could not be loaded (which should not happen), we return an error
        // message.
        if (!$loaded) {
            return get_string('filter_couldnotparse', 'quiz_essaydownload');
        }

        // Iterate over all "risky" tags and remove them from the HTML.
        foreach (self::PASSIVE_ELEMENTS as $tagname) {
            $nodes = $dom->getElementsByTagName($tagname);

            while ($nodes->length > 0) {
                $node = $nodes->item(0);
                $replacement = $dom->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                );
                $node->parentNode->replaceChild($replacement, $node);
            }
        }

        // Next, iterate over all remaining tags and check whether they contain "risky"
        // attributes.
        $elements = $dom->getElementsByTagName('*');
        for ($i = 0; $i < $elements->length; ++$i) {
            $element = $elements->item($i);

            foreach (self::RESOURCE_ATTRIBUTES as $attribute) {
                // If we encounter a "risky" attribute, prepare a short notice, add it
                // in front of the element and then remove the attribute.
                if ($element->hasAttribute($attribute)) {
                    $a = (object)['tag' => $element->tagName, 'attribute' => $attribute];
                    $notice = $element->ownerDocument->createTextNode(
                        get_string('filter_tagattributeremoved', 'quiz_essaydownload', $a)
                    );
                    $element->parentNode->insertBefore($notice, $element);
                    $element->removeAttribute($attribute);
                }
            }

            // Check for url(...) in inline CSS.
            if ($element->hasAttribute('style')) {
                $style = $element->getAttribute('style');

                // If there is an url(...) in the style attribute, we remove it and add a note.
                if (preg_match('/url\s*\(/i', $style)) {
                    $style = preg_replace(
                        '/url\s*\([^)]*\)/i',
                        'none',
                        $style
                    );
                    $notice = $element->ownerDocument->createTextNode(
                        get_string('filter_styleurlremoved', 'quiz_essaydownload', $element->tagName)
                    );
                    $element->parentNode->insertBefore($notice, $element);
                    $element->setAttribute('style', $style);
                }
            }
        }

        return $dom->saveHTML();
    }
}
