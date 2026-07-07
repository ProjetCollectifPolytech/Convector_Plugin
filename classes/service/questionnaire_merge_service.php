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
 * Merge service for questionnaire and answer-sheet PDFs.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use local_convector\generation_options;
use local_convector\merged_questionnaire_pdf;
use stdClass;

/**
 * Produces the merged questionnaire artifact for one group.
 */
class questionnaire_merge_service {
    /**
     * Merge questionnaire, optional answer sheet and custom pages, then append blank pages.
     *
     * Merge order:
     *  1. custom first page (when provided)
     *  2. questionnaire
     *  3. answer sheet (when provided)
     *  4. custom last page (when provided)
     *  5. blank normalization pages (always last)
     *
     * @param int $groupid OfflineQuiz group id
     * @param string $questionnairepath Path to questionnaire PDF
     * @param string|null $answersheetpath Path to answer-sheet PDF, null when excluded
     * @param int $blankpages Number of normalization blank pages
     * @param string $tempdir Request temp directory
     * @param generation_options|null $options Per-generation options
     * @return string|false
     */
    public function merge(
        int $groupid,
        string $questionnairepath,
        ?string $answersheetpath,
        int $blankpages,
        string $tempdir,
        ?generation_options $options = null
    ) {
        global $DB;

        try {
            $group = $DB->get_record('offlinequiz_groups', ['id' => $groupid], '*', MUST_EXIST);
            $mergedpath = rtrim($tempdir, '\\/') . DIRECTORY_SEPARATOR . $this->build_filename($group);

            $pdf = new merged_questionnaire_pdf('P', 'mm', 'A4');
            $pdf->setPrintHeader(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);

            if ($options !== null && $options->customfirstpagepath !== null) {
                $this->append_source_pdf($pdf, $options->customfirstpagepath, 'custom');
            }

            $this->append_source_pdf($pdf, $questionnairepath, 'questionnaire');

            if ($answersheetpath !== null) {
                $this->append_source_pdf($pdf, $answersheetpath, 'answer_sheet');
            }

            if ($options !== null && $options->customlastpagepath !== null) {
                $this->append_source_pdf($pdf, $options->customlastpagepath, 'custom');
            }

            for ($i = 0; $i < $blankpages; $i++) {
                $pdf->AddPage('P', 'A4');
                $pdf->register_page_type('blank');
            }

            $pdfcontent = $pdf->Output('', 'S');
            return file_put_contents($mergedpath, $pdfcontent) === false ? false : $mergedpath;
        } catch (\Throwable $e) {
            debugging(
                'Convector failed to merge questionnaire archive for group ' .
                $groupid . ': ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return false;
        }
    }

    /**
     * Append every page from one source PDF into the merged output.
     *
     * @param merged_questionnaire_pdf $pdf Destination PDF
     * @param string $sourcepath Source PDF path
     * @param string $type Source type
     * @return void
     */
    private function append_source_pdf(merged_questionnaire_pdf $pdf, string $sourcepath, string $type): void {
        $pagecount = $pdf->setSourceFile($sourcepath);
        for ($pagenumber = 1; $pagenumber <= $pagecount; $pagenumber++) {
            $templateid = $pdf->importPage($pagenumber);
            $templatesize = $pdf->getTemplateSize($templateid);
            $pdf->AddPage($templatesize['orientation'], [$templatesize['width'], $templatesize['height']]);
            $pdf->register_page_type($type);
            $pdf->useTemplate($templateid);
        }
    }

    /**
     * Build the merged questionnaire filename for one group.
     *
     * @param stdClass $group OfflineQuiz group
     * @return string
     */
    private function build_filename(stdClass $group): string {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $index = max(0, (int) ($group->groupnumber ?? 1) - 1);
        $groupletter = isset($alphabet[$index]) ? strtoupper($alphabet[$index]) : (string) $group->id;
        $timestamp = date('Ymd_His') . '_' . substr(uniqid('', true), -8);

        return clean_filename(
            get_string('fileprefixform', 'offlinequiz') . '_' . $groupletter . '_' . $timestamp . '.pdf'
        );
    }
}
