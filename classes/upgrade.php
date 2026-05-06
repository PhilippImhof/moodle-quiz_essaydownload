<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace quiz_essaydownload;

/**
 * Helper class for upgrades.
 *
 * @package    quiz_essaydownload
 * @copyright  2026 Philipp Imhof
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade {
    /**
     * Convert legacy "nameordering" preference to new template based system and remove old user
     * preferences. Contributed by @NJahreis.
     *
     * @return void
     */
    public static function nameordering_to_template(): void {
        global $DB;

        $table = 'user_preferences';

        // Get current user preferences.
        $records = $DB->get_records($table, ['name' => 'quiz_essaydownload_nameordering'], '', 'userid, name, value');

        // Delete old 'quiz_essaydownload_nameordering' preference from the DB.
        $DB->delete_records($table, ['name' => 'quiz_essaydownload_nameordering']);

        // Create array of objects for all preferences to be converted.
        $newrecords = [];
        foreach ($records as $record) {
            $newrecords[] = [
                'name' => 'quiz_essaydownload_nametemplate',
                'value' => ($record->value === 'firstlast') ? '%firstname% %lastname%' : '%lastname% %firstname%',
                'userid' => $record->userid,
            ];
            $newrecords[] = [
                'name' => 'quiz_essaydownload_filenametemplate',
                'value' => ($record->value === 'firstlast') ? '%firstname%_%lastname%' : '%lastname%_%firstname%',
                'userid' => $record->userid,
            ];
        }
        $DB->insert_records($table, $newrecords);
    }
}
