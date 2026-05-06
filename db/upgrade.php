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
 * Upgrade functions for quiz_essaydownload.
 *
 * @package   quiz_essaydownload
 * @copyright 2026 University of Bayreuth
 * @author    Nikolai Jahreis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define upgrade steps to be performed to upgrade the plugin from the old version to the current one.
 *
 * @param int $oldversion Version number the plugin is being upgraded from.
 */
function xmldb_quiz_essaydownload_upgrade($oldversion) {
    if ($oldversion < 2026042000) {
        quiz_essaydownload\upgrade::nameordering_to_template();
        upgrade_plugin_savepoint(true, 2026042000, 'quiz', 'essaydownload');
    }

    return true;
}
