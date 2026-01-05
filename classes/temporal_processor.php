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
 * Temporal Processor class for normalizing exam page counts.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Temporal Processor for the Convecteur Temporel functionality.
 *
 * This class handles the analysis and normalization of exam copies
 * to ensure all copies have the same number of pages.
 */
class temporal_processor {

    /** @var object The offlinequiz instance */
    private $offlinequiz;

    /** @var array The questions in the quiz */
    private $questions;

    /** @var int The target number of pages (maximum) */
    private $targetpages;

    /** @var array Errors encountered during processing */
    private $errors = [];

    /**
     * Constructor.
     *
     * @param object $offlinequiz The offlinequiz instance
     */
    public function __construct($offlinequiz) {
        $this->offlinequiz = $offlinequiz;
        $this->questions = [];
        $this->targetpages = 0;
    }

    /**
     * Validate the offlinequiz before processing.
     *
     * @return bool True if valid, false otherwise
     */
    public function validate() {
        global $DB;

        $this->errors = [];

        // Check if offlinequiz exists
        if (!$this->offlinequiz) {
            $this->errors[] = get_string('error_no_offlinequiz', 'local_offlinequizaddons');
            return false;
        }

        // Get questions for this offlinequiz
        $this->questions = $this->get_offlinequiz_questions();

        // Check if there are questions
        if (empty($this->questions)) {
            $this->errors[] = get_string('error_no_questions', 'local_offlinequizaddons');
            return false;
        }

        // Validate question types (should be multichoice or essay)
        foreach ($this->questions as $question) {
            if (!in_array($question->qtype, ['multichoice', 'essay', 'shortanswer', 'truefalse'])) {
                $this->errors[] = get_string('error_invalid_question_type', 'local_offlinequizaddons', $question->qtype);
            }
        }

        return empty($this->errors);
    }

    /**
     * Get all questions for the offlinequiz.
     *
     * @return array Array of question objects
     */
    private function get_offlinequiz_questions() {
        global $DB;

        $questions = [];

        // Get all groups for this offlinequiz
        $groups = $DB->get_records('offlinequiz_groups', ['offlinequizid' => $this->offlinequiz->id]);

        foreach ($groups as $group) {
            // Determine group display name - try different possible fields
            $groupname = get_string('group', 'local_offlinequizaddons') . ' ' . $group->id;
            if (isset($group->name) && !empty($group->name)) {
                $groupname = $group->name;
            } else if (isset($group->groupnumber)) {
                $groupname = get_string('group', 'local_offlinequizaddons') . ' ' . $group->groupnumber;
            } else if (isset($group->number)) {
                $groupname = get_string('group', 'local_offlinequizaddons') . ' ' . $group->number;
            }
            
            // Get questions for this group
            $sql = "SELECT q.*, oqg.page, oqg.slot
                    FROM {question} q
                    JOIN {offlinequiz_group_questions} oqg ON oqg.questionid = q.id
                    WHERE oqg.offlinequizid = :offlinequizid
                      AND oqg.offlinegroupid = :groupid
                    ORDER BY oqg.page, oqg.slot";

            $groupquestions = $DB->get_records_sql($sql, [
                'offlinequizid' => $this->offlinequiz->id,
                'groupid' => $group->id
            ]);

            foreach ($groupquestions as $question) {
                $question->groupid = $group->id;
                $question->groupname = $groupname;
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * Calculate the number of pages for each question.
     *
     * @param object $question The question object
     * @return int Number of pages needed
     */
    private function calculate_question_pages($question) {
        // Base calculation: each question gets 1 page minimum
        $pages = 1;

        // Add pages based on question type
        switch ($question->qtype) {
            case 'multichoice':
                // Count number of answers
                global $DB;
                $answers = $DB->count_records('question_answers', ['question' => $question->id]);
                // Add 1 page for every 8 answers
                $pages += floor($answers / 8);
                break;

            case 'essay':
                // Essay questions typically need more space
                // Check defaultmark to estimate space needed
                if ($question->defaultmark > 10) {
                    $pages += 2;
                } else if ($question->defaultmark > 5) {
                    $pages += 1;
                }
                break;

            case 'shortanswer':
                $pages = 1;
                break;

            case 'truefalse':
                $pages = 1;
                break;
        }

        return $pages;
    }

    /**
     * Analyze all exam copies and calculate the maximum number of pages.
     *
     * @return array Analysis results with page counts per group
     */
    public function analyze_copies() {
        $analysis = [];
        $maxpages = 0;

        // Group questions by group
        $groupedquestions = [];
        foreach ($this->questions as $question) {
            if (!isset($groupedquestions[$question->groupid])) {
                $groupedquestions[$question->groupid] = [
                    'groupname' => $question->groupname,
                    'questions' => [],
                    'totalpages' => 0
                ];
            }
            $groupedquestions[$question->groupid]['questions'][] = $question;
        }

        // Calculate pages for each group
        foreach ($groupedquestions as $groupid => $data) {
            // Count actual pages used based on the page numbers in questions
            $maxpage = 0;
            $questiondetails = [];

            foreach ($data['questions'] as $question) {
                // The page field tells us which page this question is on (0-indexed or 1-indexed)
                if ($question->page > $maxpage) {
                    $maxpage = $question->page;
                }

                $questiondetails[] = [
                    'name' => $question->name,
                    'type' => $question->qtype,
                    'page' => $question->page
                ];
            }

            // Total pages is the highest page number + 1 (if 0-indexed) or just max (if 1-indexed)
            // In offlinequiz, page numbers are usually 1-indexed, so we use max page as total
            $totalpages = $maxpage > 0 ? $maxpage : 1;

            $analysis[$groupid] = [
                'groupname' => $data['groupname'],
                'totalpages' => $totalpages,
                'questioncount' => count($data['questions']),
                'questions' => $questiondetails
            ];

            if ($totalpages > $maxpages) {
                $maxpages = $totalpages;
            }
        }

        $this->targetpages = $maxpages;

        return [
            'groups' => $analysis,
            'maxpages' => $maxpages,
            'needsnormalization' => $this->needs_normalization($analysis)
        ];
    }

    /**
     * Check if normalization is needed.
     *
     * @param array $analysis Analysis results
     * @return bool True if different groups have different page counts
     */
    private function needs_normalization($analysis) {
        $pagecounts = [];
        foreach ($analysis as $group) {
            $pagecounts[] = $group['totalpages'];
        }

        return count(array_unique($pagecounts)) > 1;
    }

    /**
     * Calculate how many blank pages to add to each group.
     *
     * @return array Array of group IDs with blank pages to add
     */
    public function calculate_blank_pages() {
        $analysis = $this->analyze_copies();
        $blankpages = [];

        foreach ($analysis['groups'] as $groupid => $data) {
            $needed = $analysis['maxpages'] - $data['totalpages'];
            $blankpages[$groupid] = [
                'groupname' => $data['groupname'],
                'currentpages' => $data['totalpages'],
                'blankpages' => $needed,
                'targetpages' => $analysis['maxpages'] + 1  // +1 for cover page
            ];
        }

        return $blankpages;
    }

    /**
     * Get any errors from validation.
     *
     * @return array Array of error messages
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get the target page count.
     *
     * @return int Number of pages
     */
    public function get_target_pages() {
        return $this->targetpages;
    }

    /**
     * Get the questions.
     *
     * @return array Array of questions
     */
    public function get_questions() {
        return $this->questions;
    }
}
