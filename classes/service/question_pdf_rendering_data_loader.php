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
 * Loader for question data used by questionnaire-style PDFs.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\service;

use filter_tex\text_filter;
use stdClass;

/**
 * Loads prepared questions and slot metadata for one group.
 */
class question_pdf_rendering_data_loader {
    /**
     * Load the question records and slot metadata needed for rendering.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<string, mixed> $groupcontext Group context
     * @return array<string, mixed>|null
     */
    public function load(stdClass $offlinequiz, array $groupcontext): ?array {
        global $DB;

        $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
                  FROM {offlinequiz_group_questions} ogq
                  JOIN {question} q ON q.id = ogq.questionid
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} c ON c.id = qbe.questioncategoryid
                 WHERE ogq.offlinequizid = :offlinequizid
                   AND ogq.offlinegroupid = :offlinegroupid
              ORDER BY ogq.slot ASC";

        $questions = $DB->get_records_sql($sql, [
            'offlinequizid' => $offlinequiz->id,
            'offlinegroupid' => $groupcontext['group']->id,
        ]);
        if (!$questions || !get_question_options($questions)) {
            return null;
        }

        $questionslots = [];
        foreach ($groupcontext['templateusage']->get_slots() as $slot) {
            $questionslots[$groupcontext['templateusage']->get_question($slot)->id] = $slot;
        }

        return [
            'questions' => $questions,
            'questionslots' => $questionslots,
            'texfilter' => new text_filter($groupcontext['context'], []),
        ];
    }
}
