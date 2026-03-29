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
 * Backward-compatible wrapper around the temporal analysis service.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

use local_convector\service\offlinequiz_question_repository;
use local_convector\service\temporal_analysis_service;
use stdClass;

/**
 * Legacy adapter kept while the PDF pipeline migrates to service-based inputs.
 */
class temporal_processor {
    /** @var stdClass */
    private stdClass $offlinequiz;

    /** @var temporal_analysis_service */
    private temporal_analysis_service $analysisservice;

    /** @var string[] */
    private array $errors = [];

    /** @var array<string, mixed>|null */
    private ?array $analysis = null;

    /**
     * Constructor.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param offlinequiz_question_repository|null $repository
     */
    public function __construct(stdClass $offlinequiz, ?offlinequiz_question_repository $repository = null) {
        $this->offlinequiz = $offlinequiz;
        $this->analysisservice = new temporal_analysis_service($repository);
    }

    /**
     * Validate the current OfflineQuiz.
     *
     * @return bool
     */
    public function validate(): bool {
        $this->errors = $this->analysisservice->validate($this->offlinequiz);
        return empty($this->errors);
    }

    /**
     * Return the current analysis payload.
     *
     * @return array<string, mixed>
     */
    public function analyze_copies(): array {
        $this->analysis = $this->analysisservice->build_analysis($this->offlinequiz);
        return $this->analysis;
    }

    /**
     * Return the current blank-page plan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function calculate_blank_pages(): array {
        if ($this->analysis === null) {
            $this->analysis = $this->analysisservice->build_analysis($this->offlinequiz);
        }

        return $this->analysis['blankpages'];
    }

    /**
     * Return validation errors.
     *
     * @return string[]
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Return the target page count.
     *
     * @return int
     */
    public function get_target_pages(): int {
        if ($this->analysis === null) {
            $this->analysis = $this->analysisservice->build_analysis($this->offlinequiz);
        }

        return (int) $this->analysis['maxpages'];
    }

    /**
     * Return all analysed questions as a flat list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_questions(): array {
        if ($this->analysis === null) {
            $this->analysis = $this->analysisservice->build_analysis($this->offlinequiz);
        }

        $questions = [];
        foreach ($this->analysis['groups'] as $groupdata) {
            foreach ($groupdata['questions'] as $question) {
                $questions[] = $question;
            }
        }

        return $questions;
    }
}
