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
 * Generator for normalized correction PDFs.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

/**
 * Generates the correction-sheet part of the normalized archive.
 */
class correction_generator extends question_pdf_generator_base {
    /**
     * {@inheritDoc}
     */
    protected function get_cover_heading_string_identifier(): string {
        return 'correctionform';
    }

    /**
     * {@inheritDoc}
     */
    protected function get_output_prefix_string_identifier(): string {
        return 'fileprefixcorrection';
    }

    /**
     * {@inheritDoc}
     */
    protected function should_render_correction_answers(): bool {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function after_questions_rendered(\offlinequiz_question_pdf $pdf, array $data): void {
        $targetpages = max(0, (int) ($data['targetpages'] ?? 0) - 1);
        $blankpages = $targetpages - $pdf->getNumPages();

        for ($i = 0; $i < $blankpages; $i++) {
            $pdf->AddPage();
        }
    }
}
