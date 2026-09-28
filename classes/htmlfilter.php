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

use DOMNode;

/**
 * Class to remove possibly dangerous HTML content in student responses.
 *
 * @package    quiz_essaydownload
 * @copyright  2026 Philipp Imhof
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class htmlfilter {
    /** @var array HTML tags that we do not support and want to remove */
    const TAGS_TO_REMOVE = [
        'audio',
        'video',
        'track',
        'source',
        'iframe',
        'frameset',
        'frame',
        'script',
        'svg',
        'input',
        'link',
        'embed',
        'style',
    ];

    /** @var array HTML tags that can contain other accepted HTML as a drop-in replacement */
    const TAGS_TO_UNWRAP = [
        'object',
        'picture',
    ];

    /** @var array HTML attributes that could lead to the inclusion of external resources  */
    const RESOURCE_ATTRIBUTES = [
        'src',
        'srcset',
        'srcdoc',
        'data',
        'poster',
        'href',
    ];

    /**
     * FIXME
     *
     * @param DOMNode $element
     * @return bool
     */
    protected static function img_src_refers_to_pluginfile(DOMNode $element): bool {
        // If there is no src attribute, the image does not refer to a @@PLUGINFILE@@.
        if (!$element->hasAttribute('src')) {
            return false;
        }

        $src = $element->getAttribute('src');
        return str_starts_with($src, '@@PLUGINFILE@@');
    }

    /**
     * Take the given HTML and remove all "passive" references to (possibly dangerous) resources.
     * Using e. g. <a href="..."> shall be allowed, because the resource would only be accessed
     * when the user clicks the given link. Passive references, on the other hand, could lead to
     * a server-initiated access to some URL during the generation of the PDF.
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
        $input = '<?xml encoding="UTF-8">' . $html;

        // We suppress errors or warnings from the parser, because it might output them for
        // valid HTML 5 stuff. Storing the previous state here.
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            $input,
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

        // Fetch all HTML elements and create a snapshot to iterate over. The snapshot will contain
        // references to the real elements, so all changes will reflect to the real DOM.
        $elements = $dom->getElementsByTagName('*');
        $snapshot = iterator_to_array($elements);

        foreach ($snapshot as $element) {
            // Check whether we have removed the parent of the current node in an earlier step.
            // In that case, the current node will be gone from the DOM, but it is still in the snapshot.
            if ($element->parentNode === null) {
                continue;
            }

            $tagname = $element->tagName;

            // Certain elements like <audio>, <svg> or <source> should be removed. Removing them
            // will also remove the entire subtree.
            if (in_array($tagname, self::TAGS_TO_REMOVE)) {
                $replacement = $dom->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                );
                $element->parentNode->replaceChild($replacement, $element);
            }

            // Elements like <picture> or <object> can include embedded content to be displayed
            // if the element itself is not supported. Although it is unlikely to have those in
            // the question text or the student's response, we try to "unwrap" them, i. e. move
            // their children one level up and then remove the element itself.
            if (in_array($tagname, self::TAGS_TO_UNWRAP)) {
                while ($element->firstChild !== null) {
                    $element->parentNode->insertBefore($element->firstChild, $element);
                }

                $notice = $element->ownerDocument->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                );
                $element->parentNode->insertBefore($notice, $element);

                $element->parentNode->removeChild($element);
            }
        }

        // Reload the elements to get a clean, updated DOM. Now we check the attributes of
        // the remaining tags.
        $elements = $dom->getElementsByTagName('*');
        $snapshot = iterator_to_array($elements);

        foreach ($snapshot as $element) {
            $tagname = $element->tagName;

            // First, go through our list of potentially resource-bearing attributes.
            foreach (self::RESOURCE_ATTRIBUTES as $attribute) {
                if ($element->hasAttribute($attribute)) {
                    // For the <img> tag, allow a src attribute, but only if it refers to a
                    // @@PLUGINFILE@@. Otherwise, remove the entire tag.
                    if ($tagname === 'img') {
                        if (!self::img_src_refers_to_pluginfile($element)) {
                            $replacement = $dom->createTextNode(
                                get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                            );
                            $element->parentNode->replaceChild($replacement, $element);
                        }
                        continue;
                    }

                    // Allow the href attribute for <a> tags with no filtering.
                    if ($attribute === 'href' && $tagname === 'a') {
                        continue;
                    }

                    // For all other cases, remove the attribute and add a note to the HTML.
                    $a = (object)['tag' => $tagname, 'attribute' => $attribute];
                    $notice = $element->ownerDocument->createTextNode(
                        get_string('filter_tagattributeremoved', 'quiz_essaydownload', $a)
                    );
                    $element->parentNode->insertBefore($notice, $element);
                    $element->removeAttribute($attribute);
                }
            }

            // Finally, check whether the element has a style attribute containing an url(...)
            // reference. If it does, we remove the URL.
            if ($element->hasAttribute('style')) {
                $style = $element->getAttribute('style');
                if (preg_match('/url\s*\(/i', $style)) {
                    $style = preg_replace('/url\s*\([^)]*\)/i', 'none', $style);
                    $notice = $element->ownerDocument->createTextNode(
                        get_string('filter_styleurlremoved', 'quiz_essaydownload', $element->tagName)
                    );
                    $element->parentNode->insertBefore($notice, $element);
                    $element->setAttribute('style', $style);
                }
            }
        }

        $output = $dom->saveHTML();
        return preg_replace('/^<\?xml encoding="UTF-8">/', '', $output);
    }
}
