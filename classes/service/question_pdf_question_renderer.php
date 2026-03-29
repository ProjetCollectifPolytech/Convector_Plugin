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
 * Renderer for question pages inside questionnaire-style PDFs.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\service;

use stdClass;

/**
 * Writes question content into the target PDF document.
 */
class question_pdf_question_renderer {
    /**
     * Render the full question sequence for one group.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<string, mixed> $groupcontext Group context
     * @param array<string, mixed> $renderingdata Rendering data
     * @param \offlinequiz_html_translator $translator HTML translator
     * @param bool $rendercorrectionanswers Whether answers should show correction hints
     * @return void
     */
    public function render(
        \offlinequiz_question_pdf $pdf,
        stdClass $offlinequiz,
        array $groupcontext,
        array $renderingdata,
        \offlinequiz_html_translator $translator,
        bool $rendercorrectionanswers
    ): void {
        $pdf->AddPage();
        $pdf->Ln(2);
        $pdf->SetMargins(15, 15, 15);

        $currentpage = 1;
        $number = 1;

        foreach ($renderingdata['questions'] as $question) {
            $currentpage = $this->advance_to_question_page($pdf, $question, $currentpage);

            $html = $this->build_question_html(
                $pdf,
                $offlinequiz,
                $question,
                $groupcontext,
                $renderingdata,
                $translator,
                $rendercorrectionanswers
            );
            if ($this->is_objective_question($question)) {
                $this->write_question_number($pdf, $groupcontext['font'], $offlinequiz, $number);
            }

            offlinequiz_write_question_to_pdf($pdf, $offlinequiz->fontsize, $question->qtype, $html, $number);
            $number += max(1, (int) ($question->length ?? 1));
        }
    }

    /**
     * Advance the PDF cursor to the logical page assigned to the question.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $question Question record
     * @param int $currentpage Current logical page
     * @return int
     */
    private function advance_to_question_page(
        \offlinequiz_question_pdf $pdf,
        stdClass $question,
        int $currentpage
    ): int {
        $targetpage = max(1, (int) $question->page);

        while ($targetpage > $currentpage) {
            $pdf->AddPage();
            $pdf->Ln(14);
            $currentpage++;
        }

        if ($pdf->GetY() > 230) {
            $pdf->AddPage();
            $pdf->Ln(14);
        }

        return $currentpage;
    }

    /**
     * Build the final HTML payload for one question.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param stdClass $question Question record
     * @param array<string, mixed> $groupcontext Group context
     * @param array<string, mixed> $renderingdata Rendering data
     * @param \offlinequiz_html_translator $translator HTML translator
     * @param bool $rendercorrectionanswers Whether answers should show correction hints
     * @return string
     */
    private function build_question_html(
        \offlinequiz_question_pdf $pdf,
        stdClass $offlinequiz,
        stdClass $question,
        array $groupcontext,
        array $renderingdata,
        \offlinequiz_html_translator $translator,
        bool $rendercorrectionanswers
    ): string {
        $html = offlinequiz_print_question_html(
            $pdf,
            $question,
            $renderingdata['texfilter'],
            $translator,
            $offlinequiz
        );

        if ($this->is_objective_question($question)) {
            $slot = $renderingdata['questionslots'][$question->id] ?? null;
            if ($slot !== null) {
                $html .= offlinequiz_get_answers_html(
                    $offlinequiz,
                    $groupcontext['templateusage'],
                    $slot,
                    $question,
                    $renderingdata['texfilter'],
                    $translator,
                    $rendercorrectionanswers
                );
            }
        }

        return $this->sanitize_question_html($html, $offlinequiz);
    }

    /**
     * Determine whether one question prints answer bubbles.
     *
     * @param stdClass $question Question record
     * @return bool
     */
    private function is_objective_question(stdClass $question): bool {
        return in_array($question->qtype, ['multichoice', 'multichoiceset'], true);
    }

    /**
     * Remove HTML fragments that break OfflineQuiz rendering when configured.
     *
     * @param string $html Raw question HTML
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return string
     */
    private function sanitize_question_html(string $html, stdClass $offlinequiz): string {
        if (empty($offlinequiz->disableimgnewlines)) {
            return $html;
        }

        $html = preg_replace('/(<span class="MathJax_Preview">.+?)+(title="TeX" >)/ms', '', $html) ?? $html;
        $html = preg_replace('/<\/a><\/span>/ms', '', $html) ?? $html;
        return preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/ms', '', $html) ?? $html;
    }

    /**
     * Print the question number label used by OfflineQuiz.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param string $font Font name
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param int $number Question number
     * @return void
     */
    private function write_question_number(
        \offlinequiz_question_pdf $pdf,
        string $font,
        stdClass $offlinequiz,
        int $number
    ): void {
        $pdf->SetFont($font, 'B', $offlinequiz->fontsize);
        $pdf->Cell(4, round($offlinequiz->fontsize / 2), $number . ')  ', 0, 0, 'R');
        $pdf->SetFont($font, '', $offlinequiz->fontsize);
    }
}
