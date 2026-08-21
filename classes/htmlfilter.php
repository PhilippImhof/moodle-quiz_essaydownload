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
    /** @var array HTML tag names that could lead to the inclusion of external resources.
     *             Note that <source> and <img> are intentionally left out here.
    */
    const PASSIVE_ELEMENTS = [
        // The <audio> and <video> tags normally use one or more <source> tags for their
        // source, but they do also support the src attribute.
        'audio',
        'video',
        'svg',
        'iframe',
        'frame',
        'frameset',
        'object',
        'embed',
        'track',
        'input',
        'script',
        'link',
    ];

    /** @var array HTML attributes that could lead to the inclusion of external resources  */
    const RESOURCE_ATTRIBUTES = [
        'src',
        'srcset',
        'srcdoc',
        'data',
        'poster',
    ];

    /**
     * Check whether a given HTML element only refers to @@PLUGINFILE@@ files.
     *
     * @param DOMNode $element the element to check
     * @return bool
     */
    protected static function refers_to_pluginfile(DOMNode $element): bool {
        $tagname = $element->tagName;

        // The <video> and <audio> tags normally use a child <source> to refer to their
        // content, but they may also have a src attribute. We check both. If one refers
        // to something else than a @@PLUGINFILE@@, that shall be enough to trigger our filter.
        if (in_array($tagname, ['video', 'audio'])) {
            // Let's assume there is no src attribute.
            $src = null;

            // If there is a src and it does not go to a @@PLUGINFILE@@, we're out.
            if ($element->hasAttribute('src')) {
                // Store the src for later.
                $src = $element->getAttribute('src');
                if (!str_starts_with($src, '@@PLUGINFILE@@')) {
                    return false;
                }
            }

            // Otherwise, we check the <source> children. None of them must contain a reference
            // to a non-@@PLUGINFILE@@.
            $sources = $element->getElementsByTagName('source');
            foreach ($sources as $source) {
                if (self::refers_to_pluginfile($source) === false) {
                    return false;
                }
            }

            // Still here? If there was no src and there are no <source> children, there
            // is no @@PLUGINFILE@@ reference.
            if ($src === null && $sources->length === 0) {
                return false;
            }

            // So we either have a valid <source> child or a valid src attribute.
            return true;
        }

        // For all other tags, particularly <img> and <source>, we check the src and the
        // srcset attributes. Let's assume, neither one is set.
        $src = null;
        $srcset = null;

        // If we have a src attribute and it does not start with @@PLUGINFILE@@,
        // we can leave here.
        if ($element->hasAttribute('src')) {
            // Store the src for later.
            $src = $element->getAttribute('src');
            if (!str_starts_with($src, '@@PLUGINFILE@@')) {
                return false;
            }
        }

        // If we have a srcset attribute and there is at least one entry containing
        // something else than a @@PLUGINFILE@@, we can leave.
        if ($element->hasAttribute('srcset')) {
            $srcset = $element->getAttribute('srcset');
            $sources = explode(',', $srcset);
            foreach ($sources as $source) {
                $source = trim($source);
                if (!str_starts_with($source, '@@PLUGINFILE@@')) {
                    return false;
                }
            }
        }

        // If the element has neither src nor srcset, it cannot refer to a @@PLUGINFILE@@.
        if ($srcset === null & $src === null) {
            return false;
        }

        // Still here? So we have at least a src or a srcset and they do not refer to other
        // things than @@PLUGINFILE@@'s.
        return true;
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

            // For all <img> elements, we check whether the source is a @@PLUGINFILE@@ file. If not,
            // we remove the element and replace it by a note.
            if ($tagname === 'img' && !self::refers_to_pluginfile($element)) {
                $replacement = $dom->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                );
                $element->parentNode->replaceChild($replacement, $element);
            }

            // For the <source> of a <picture>, <video> or <audio>, we do the same as above, but we
            // have to remove the parent element with its entire subtree and not just the element itself.
            if ($tagname === 'source' && !self::refers_to_pluginfile($element)) {
                // We will add our note to the parent's parent, so we must make sure it sill exists.
                if ($element->parentNode->parentNode === null) {
                    continue;
                }
                $replacement = $dom->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $element->parentNode->tagName)
                );
                $element->parentNode->parentNode->replaceChild($replacement, $element->parentNode);
            }

            // For all other "risky" elements, we just remove the node with its subtree.
            if (in_array($tagname, self::PASSIVE_ELEMENTS) && !self::refers_to_pluginfile($element)) {
                $replacement = $dom->createTextNode(
                    get_string('filter_tagremoved', 'quiz_essaydownload', $tagname)
                );
                $element->parentNode->replaceChild($replacement, $element);
            }
        }

        // Reload the elements to get a clean, updated DOM.
        $elements = $dom->getElementsByTagName('*');
        $snapshot = iterator_to_array($elements);

        // Tags that should be excluded from a part of the following checks.
        $exclude = array_merge(self::PASSIVE_ELEMENTS, ['img', 'source']);

        foreach ($snapshot as $element) {
            $tagname = $element->tagName;

            // We check whether the element has a style attribute containing an url(...)
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

            // Finally, we check whether the element has a src, srcset, srcdoc, poster or data
            // attribute. In that case, we remove the attribute, just to be sure. Note that we
            // exclude the element types (e. g. img) that have been treated in the step before.
            foreach (self::RESOURCE_ATTRIBUTES as $attribute) {
                if (in_array($tagname, $exclude)) {
                    continue;
                }

                if ($element->hasAttribute($attribute)) {
                    $a = (object)['tag' => $tagname, 'attribute' => $attribute];
                    $notice = $element->ownerDocument->createTextNode(
                        get_string('filter_tagattributeremoved', 'quiz_essaydownload', $a)
                    );
                    $element->parentNode->insertBefore($notice, $element);
                    $element->removeAttribute($attribute);
                }
            }
        }

        $output = $dom->saveHTML();
        return preg_replace('/^<\?xml encoding="UTF-8">/', '', $output);
    }
}
