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
 * PDF generation service.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use local_convector\pdf_generator;
use local_convector\temporal_processor;
use stdClass;

/**
 * Orchestrates ZIP generation and download naming.
 */
class pdf_generation_service {
    /**
     * Generate the normalized ZIP archive.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param array<int, array<string, mixed>> $blankpages Blank page plan
     * @return array<string, string>|null
     */
    public function generate_normalized_archive(stdClass $offlinequiz, array $blankpages): ?array {
        $processor = new temporal_processor($offlinequiz, new offlinequiz_question_repository());
        $generator = new pdf_generator($offlinequiz, $processor);
        $filepath = $generator->generate_normalized_pdfs($blankpages);

        if ($filepath === false || !is_string($filepath) || !file_exists($filepath)) {
            return null;
        }

        return [
            'filepath' => $filepath,
            'downloadname' => clean_filename(
                'offlinequiz_' . $offlinequiz->id . '_normalized_' . date('Y-m-d_His') . '.zip'
            ),
        ];
    }
}
