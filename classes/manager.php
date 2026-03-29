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
 * Facade for the Convector workflow.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

use local_convector\service\pdf_generation_service;
use local_convector\service\temporal_analysis_service;
use stdClass;

/**
 * Coordinates analysis and generation workflows.
 */
class manager {
    /** @var temporal_analysis_service */
    private temporal_analysis_service $analysisservice;

    /** @var pdf_generation_service */
    private pdf_generation_service $pdfgenerationservice;

    /**
     * Constructor.
     *
     * @param temporal_analysis_service $analysisservice
     * @param pdf_generation_service $pdfgenerationservice
     */
    public function __construct(
        temporal_analysis_service $analysisservice,
        pdf_generation_service $pdfgenerationservice
    ) {
        $this->analysisservice = $analysisservice;
        $this->pdfgenerationservice = $pdfgenerationservice;
    }

    /**
     * Build the data needed by the Convector page.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return array<string, mixed>
     */
    public function build_page_data(stdClass $offlinequiz): array {
        $errors = $this->analysisservice->validate($offlinequiz);
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'analysis' => null,
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'analysis' => $this->analysisservice->build_analysis($offlinequiz),
        ];
    }

    /**
     * Generate the normalized ZIP archive for one OfflineQuiz.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @return array<string, mixed>
     */
    public function generate_normalized_archive(stdClass $offlinequiz): array {
        $pagedata = $this->build_page_data($offlinequiz);
        if (!$pagedata['valid']) {
            return [
                'success' => false,
                'errors' => $pagedata['errors'],
            ];
        }

        $result = $this->pdfgenerationservice->generate_normalized_archive(
            $offlinequiz,
            $pagedata['analysis']['blankpages']
        );
        if ($result === null) {
            return [
                'success' => false,
                'errors' => [get_string('error_pdf_generation', 'local_convector')],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'filepath' => $result['filepath'],
            'downloadname' => $result['downloadname'],
            'analysis' => $pagedata['analysis'],
        ];
    }
}
