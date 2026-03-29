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
 * PDF generator for temporal normalization.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

use local_offlinequizaddons\service\pdf_archive_builder;
use local_offlinequizaddons\service\questionnaire_merge_service;
use stdClass;

/**
 * Generates normalized questionnaire and correction archives.
 */
class pdf_generator {
    /** @var stdClass */
    private stdClass $offlinequiz;

    /** @var temporal_processor */
    private temporal_processor $processor;

    /** @var string */
    private string $tempdir;

    /** @var quiz_generator */
    private quiz_generator $quizgenerator;

    /** @var answer_sheet_generator */
    private answer_sheet_generator $answersheetgenerator;

    /** @var correction_generator */
    private correction_generator $correctiongenerator;

    /** @var questionnaire_merge_service */
    private questionnaire_merge_service $questionnairemergeservice;

    /** @var pdf_archive_builder */
    private pdf_archive_builder $pdfarchivebuilder;

    /**
     * Constructor.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param temporal_processor $processor Temporal processor
     * @param questionnaire_merge_service|null $questionnairemergeservice Merge service
     * @param pdf_archive_builder|null $pdfarchivebuilder ZIP builder
     */
    public function __construct(
        stdClass $offlinequiz,
        temporal_processor $processor,
        ?questionnaire_merge_service $questionnairemergeservice = null,
        ?pdf_archive_builder $pdfarchivebuilder = null
    ) {
        $this->offlinequiz = $offlinequiz;
        $this->processor = $processor;
        $this->tempdir = make_temp_directory('offlinequizaddons/temporal/' . $this->build_request_id());
        $this->quizgenerator = new quiz_generator($offlinequiz, $this->tempdir);
        $this->answersheetgenerator = new answer_sheet_generator($offlinequiz, $this->tempdir);
        $this->correctiongenerator = new correction_generator($offlinequiz, $this->tempdir);
        $this->questionnairemergeservice = $questionnairemergeservice ?? new questionnaire_merge_service();
        $this->pdfarchivebuilder = $pdfarchivebuilder ?? new pdf_archive_builder();
    }

    /**
     * Generate normalized PDFs for all groups.
     *
     * @param array<int, array<string, mixed>>|null $blankpages Optional blank-page plan
     * @return string|false
     */
    public function generate_normalized_pdfs(?array $blankpages = null) {
        $blankpages = $blankpages ?? $this->processor->calculate_blank_pages();
        $pdffiles = [];

        foreach ($blankpages as $groupid => $data) {
            $this->append_questionnaire_archive_files($pdffiles, (int) $groupid, $data);
            $this->append_correction_archive_file($pdffiles, (int) $groupid, $data);
        }

        if (empty($pdffiles)) {
            debugging('Temporal Convector did not generate any PDF artifact.', DEBUG_DEVELOPER);
            return false;
        }

        $archivepath = $this->pdfarchivebuilder->build($this->offlinequiz, $this->tempdir, $pdffiles);
        if ($archivepath === false) {
            return false;
        }

        $this->cleanup_files(array_column($pdffiles, 'path'));

        return $archivepath;
    }

    /**
     * Append questionnaire-related files for one group.
     *
     * @param array<int, array<string, string>> $pdffiles Archive file list
     * @param int $groupid OfflineQuiz group id
     * @param array<string, mixed> $data Group normalization data
     * @return void
     */
    private function append_questionnaire_archive_files(array &$pdffiles, int $groupid, array $data): void {
        $questionnaire = $this->quizgenerator->generate($groupid, $data);
        $answersheet = $this->answersheetgenerator->generate($groupid, $data);

        if ($questionnaire === false || $answersheet === false) {
            $this->cleanup_files(array_filter([$questionnaire, $answersheet], 'is_string'));
            return;
        }

        $merged = $this->questionnairemergeservice->merge(
            $groupid,
            $questionnaire,
            $answersheet,
            (int) ($data['blankpages'] ?? 0),
            $this->tempdir
        );
        if ($merged !== false) {
            $pdffiles[] = ['path' => $merged, 'type' => 'questionnaire'];
        }

        $this->cleanup_files([$questionnaire, $answersheet]);
    }

    /**
     * Append the correction PDF for one group when generation succeeds.
     *
     * @param array<int, array<string, string>> $pdffiles Archive file list
     * @param int $groupid OfflineQuiz group id
     * @param array<string, mixed> $data Group normalization data
     * @return void
     */
    private function append_correction_archive_file(array &$pdffiles, int $groupid, array $data): void {
        $correctionform = $this->correctiongenerator->generate($groupid, $data);
        if ($correctionform !== false) {
            $pdffiles[] = ['path' => $correctionform, 'type' => 'correction'];
        }
    }

    /**
     * Remove generated intermediate files.
     *
     * @param string[] $paths Files to remove
     * @return void
     */
    private function cleanup_files(array $paths): void {
        foreach ($paths as $path) {
            if (is_string($path) && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Build a unique request identifier for temporary storage.
     *
     * @return string
     */
    private function build_request_id(): string {
        return str_replace('.', '', uniqid('run_', true));
    }
}
