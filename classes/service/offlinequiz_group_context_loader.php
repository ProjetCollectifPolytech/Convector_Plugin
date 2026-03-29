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

/**
 * Loader for the Moodle records needed to generate one group artifact.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Resolves the course, module and template context for one OfflineQuiz group.
 */
class offlinequiz_group_context_loader {
    /**
     * Load the group context used by the PDF generators.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param int $groupid OfflineQuiz group id
     * @return array<string, mixed>|null
     */
    public function load(stdClass $offlinequiz, int $groupid): ?array {
        global $DB;

        $group = $DB->get_record('offlinequiz_groups', [
            'id' => $groupid,
            'offlinequizid' => $offlinequiz->id,
        ]);
        if (!$group) {
            return null;
        }

        $course = $DB->get_record('course', ['id' => $offlinequiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context);
        if (!$templateusage) {
            return null;
        }

        return [
            'group' => $group,
            'course' => $course,
            'cm' => $cm,
            'context' => $context,
            'templateusage' => $templateusage,
            'groupletter' => $this->resolve_group_letter($group),
            'font' => offlinequiz_get_pdffont($offlinequiz),
        ];
    }

    /**
     * Resolve a printable letter for one group.
     *
     * @param stdClass $group OfflineQuiz group record
     * @return string
     */
    private function resolve_group_letter(stdClass $group): string {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $groupnumber = (int) ($group->groupnumber ?? 0);
        $index = max(0, $groupnumber - 1);

        if (isset($alphabet[$index])) {
            return strtoupper($alphabet[$index]);
        }

        if ($groupnumber > 0) {
            return (string) $groupnumber;
        }

        return (string) $group->id;
    }
}
