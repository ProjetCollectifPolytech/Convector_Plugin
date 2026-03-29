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
 * Builder for question-oriented OfflineQuiz PDF documents.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\service;

use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Builds OfflineQuiz PDF documents with the expected cover page.
 */
class question_pdf_document_builder {
    /**
     * Create and initialize the PDF document for one group.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<string, mixed> $groupcontext Group context
     * @param string $headingstringidentifier Heading string identifier in mod_offlinequiz
     * @return \offlinequiz_question_pdf
     */
    public function create_document(
        stdClass $offlinequiz,
        array $groupcontext,
        string $headingstringidentifier
    ): \offlinequiz_question_pdf {
        $pdf = new \offlinequiz_question_pdf('P', 'mm', 'A4');
        $pdf->set_title($this->build_document_title($offlinequiz, $groupcontext));
        $pdf->SetMargins(15, 28, 15);
        $pdf->SetAutoPageBreak(false, 25);
        $pdf->AddPage();

        $this->render_cover_page($pdf, $offlinequiz, $groupcontext, $headingstringidentifier);

        return $pdf;
    }

    /**
     * Build the document title following OfflineQuiz conventions.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<string, mixed> $groupcontext Group context
     * @return string
     */
    private function build_document_title(stdClass $offlinequiz, array $groupcontext): string {
        $title = offlinequiz_str_html_pdf($offlinequiz->name);
        if (!empty($offlinequiz->time)) {
            $title .= ': ' . offlinequiz_str_html_pdf(userdate($offlinequiz->time));
        }

        return $title . ', ' . offlinequiz_str_html_pdf(
            get_string('group', 'offlinequiz') . ' ' . $groupcontext['groupletter']
        );
    }

    /**
     * Render the first cover page.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<string, mixed> $groupcontext Group context
     * @param string $headingstringidentifier Heading string identifier
     * @return void
     */
    private function render_cover_page(
        \offlinequiz_question_pdf $pdf,
        stdClass $offlinequiz,
        array $groupcontext,
        string $headingstringidentifier
    ): void {
        $font = $groupcontext['font'];

        $pdf->SetFont($font, 'B', 14);
        $pdf->Ln(4);
        $pdf->Cell(
            0,
            4,
            offlinequiz_str_html_pdf(get_string($headingstringidentifier, 'offlinequiz')),
            0,
            0,
            'C'
        );

        if ($offlinequiz->printstudycodefield) {
            $pdf->Rect(34, 42, 137, 50, 'D');
        } else {
            $pdf->Rect(34, 42, 137, 40, 'D');
        }

        $pdf->SetFont($font, '', 10);
        $pdf->Ln(14);
        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('name')) . ':', 0, 0, 'R');
        $pdf->Rect(76, 54, 80, 0.3, 'F');

        $pdf->Ln(10);
        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('idnumber', 'offlinequiz')) . ':', 0, 0, 'R');
        $pdf->Rect(76, 64, 80, 0.3, 'F');

        $pdf->Ln(10);
        if ($offlinequiz->printstudycodefield) {
            $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('studycode', 'offlinequiz')) . ':', 0, 0, 'R');
            $pdf->Rect(76, 74, 80, 0.3, 'F');
            $pdf->Ln(10);
        }

        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('signature', 'offlinequiz')) . ':', 0, 0, 'R');
        if ($offlinequiz->printstudycodefield) {
            $pdf->Rect(76, 84, 80, 0.3, 'F');
        } else {
            $pdf->Rect(76, 74, 80, 0.3, 'F');
        }

        $pdf->Ln(25);
        $pdf->SetFont($font, '', $offlinequiz->fontsize);
        $this->render_intro($pdf, $offlinequiz);
    }

    /**
     * Render the optional PDF introduction while preserving page overflow handling.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return void
     */
    private function render_intro(\offlinequiz_question_pdf $pdf, stdClass $offlinequiz): void {
        if (empty($offlinequiz->pdfintro)) {
            return;
        }

        $oldx = $pdf->GetX();
        $oldy = $pdf->GetY();

        $pdf->checkpoint();
        $pdf->writeHTMLCell(
            165,
            round($offlinequiz->fontsize / 2),
            $pdf->GetX(),
            $pdf->GetY(),
            $offlinequiz->pdfintro
        );
        $pdf->Ln();

        if (!$pdf->is_overflowing()) {
            return;
        }

        $pdf->backtrack();
        $pdf->SetX($oldx);
        $pdf->SetY($oldy);

        $paragraphs = preg_split('/<p>/', $offlinequiz->pdfintro) ?: [];
        foreach ($paragraphs as $paragraph) {
            if ($paragraph === '') {
                continue;
            }

            $sentences = preg_split('/<br\s*\/>/', $paragraph) ?: [];
            foreach ($sentences as $sentence) {
                $this->render_intro_sentence($pdf, $offlinequiz, $sentence);
            }
        }
    }

    /**
     * Render one intro sentence with overflow backtracking.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param string $sentence Sentence HTML
     * @return void
     */
    private function render_intro_sentence(
        \offlinequiz_question_pdf $pdf,
        stdClass $offlinequiz,
        string $sentence
    ): void {
        $pdf->checkpoint();
        $pdf->writeHTMLCell(
            165,
            round($offlinequiz->fontsize / 2),
            $pdf->GetX(),
            $pdf->GetY(),
            $sentence . '<br/>'
        );
        $pdf->Ln();

        if (!$pdf->is_overflowing()) {
            return;
        }

        $pdf->backtrack();
        $pdf->AddPage();
        $pdf->Ln(14);
        $pdf->writeHTMLCell(
            165,
            round($offlinequiz->fontsize / 2),
            $pdf->GetX(),
            $pdf->GetY(),
            $sentence
        );
        $pdf->Ln();
    }
}
