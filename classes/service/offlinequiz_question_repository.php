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
 * Repository helpers for OfflineQuiz question analysis.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use stdClass;

/**
 * Loads OfflineQuiz groups and questions for temporal analysis.
 */
class offlinequiz_question_repository {
    /**
     * Return all quiz questions grouped by OfflineQuiz group.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return array<int, array<string, mixed>>
     */
    public function get_grouped_questions(stdClass $offlinequiz): array {
        global $DB;

        $result = [];
        $groups = $DB->get_records('offlinequiz_groups', ['offlinequizid' => $offlinequiz->id], 'id ASC');
        foreach ($groups as $group) {
            $result[(int) $group->id] = [
                'groupid' => (int) $group->id,
                'groupname' => $this->resolve_group_name($group),
                'questions' => $this->get_group_questions($offlinequiz, $group),
            ];
        }

        return $result;
    }

    /**
     * Load the ordered questions for one group.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param stdClass $group OfflineQuiz group
     * @return stdClass[]
     */
    private function get_group_questions(stdClass $offlinequiz, stdClass $group): array {
        global $DB;

        $sql = "SELECT q.id, q.name, q.qtype, oqg.page, oqg.slot
                  FROM {question} q
                  JOIN {offlinequiz_group_questions} oqg
                    ON oqg.questionid = q.id
                 WHERE oqg.offlinequizid = :offlinequizid
                   AND oqg.offlinegroupid = :groupid
              ORDER BY oqg.page, oqg.slot";

        return array_values($DB->get_records_sql($sql, [
            'offlinequizid' => $offlinequiz->id,
            'groupid' => $group->id,
        ]));
    }

    /**
     * Resolve a display name for one OfflineQuiz group.
     *
     * @param stdClass $group OfflineQuiz group
     * @return string
     */
    private function resolve_group_name(stdClass $group): string {
        if (!empty($group->name)) {
            return $group->name;
        }

        if (isset($group->groupnumber)) {
            return get_string('group', 'local_convector') . ' ' . $group->groupnumber;
        }

        if (isset($group->number)) {
            return get_string('group', 'local_convector') . ' ' . $group->number;
        }

        return get_string('group', 'local_convector') . ' ' . $group->id;
    }
}
