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
 * Temporal analysis service.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\service;

use stdClass;

/**
 * Validates and analyses OfflineQuiz groups for page normalization.
 */
class temporal_analysis_service {
    /** @var offlinequiz_question_repository */
    private offlinequiz_question_repository $repository;

    /**
     * Constructor.
     *
     * @param offlinequiz_question_repository|null $repository
     */
    public function __construct(?offlinequiz_question_repository $repository = null) {
        $this->repository = $repository ?? new offlinequiz_question_repository();
    }

    /**
     * Validate the OfflineQuiz before running the Convector workflow.
     *
     * @param stdClass|null $offlinequiz OfflineQuiz activity
     * @return string[]
     */
    public function validate(?stdClass $offlinequiz): array {
        $errors = [];

        if ($offlinequiz === null) {
            $errors[] = get_string('error_no_offlinequiz', 'local_offlinequizaddons');
            return $errors;
        }

        $groups = $this->repository->get_grouped_questions($offlinequiz);
        if (empty($groups)) {
            $errors[] = get_string('error_no_questions', 'local_offlinequizaddons');
            return $errors;
        }

        foreach ($groups as $groupdata) {
            foreach ($groupdata['questions'] as $question) {
                if (!in_array($question->qtype, ['multichoice', 'essay', 'shortanswer', 'truefalse'], true)) {
                    $errors[] = get_string(
                        'error_invalid_question_type',
                        'local_offlinequizaddons',
                        $question->qtype
                    );
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Build the full analysis payload used by the page and generation flow.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return array<string, mixed>
     */
    public function build_analysis(stdClass $offlinequiz): array {
        $groups = $this->repository->get_grouped_questions($offlinequiz);
        $groupanalysis = [];
        $maxpages = 0;

        foreach ($groups as $groupid => $groupdata) {
            $questiondetails = [];
            $currentpages = 1;

            foreach ($groupdata['questions'] as $question) {
                $currentpages = max($currentpages, (int) $question->page ?: 1);
                $questiondetails[] = [
                    'id' => (int) $question->id,
                    'name' => $question->name,
                    'type' => $question->qtype,
                    'page' => (int) $question->page,
                ];
            }

            $groupanalysis[(int) $groupid] = [
                'groupname' => $groupdata['groupname'],
                'totalpages' => $currentpages,
                'questioncount' => count($questiondetails),
                'questions' => $questiondetails,
            ];
            $maxpages = max($maxpages, $currentpages);
        }

        $blankpages = [];
        foreach ($groupanalysis as $groupid => $groupdata) {
            $blankpages[$groupid] = [
                'groupname' => $groupdata['groupname'],
                'currentpages' => $groupdata['totalpages'],
                'blankpages' => $maxpages - $groupdata['totalpages'],
                'targetpages' => $maxpages + 2,
            ];
        }

        return [
            'groups' => $groupanalysis,
            'maxpages' => $maxpages,
            'needsnormalization' => count(array_unique(array_column($groupanalysis, 'totalpages'))) > 1,
            'blankpages' => $blankpages,
        ];
    }
}
