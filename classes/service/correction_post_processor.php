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
 * Post-processor that wraps correction PDFs with custom pages.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use local_convector\generation_options;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/autoload.php');

/**
 * Prepends the custom first page and appends the custom last page to a correction PDF.
 */
class correction_post_processor {
    /**
     * Wrap the correction PDF with custom pages when configured.
     *
     * @param string $correctionpath Path to the generated correction PDF
     * @param generation_options|null $options Generation options
     * @param string $tempdir Request temp directory
     * @return string Path to the resulting PDF (original path when no custom page is needed)
     */
    public function process(string $correctionpath, ?generation_options $options, string $tempdir): string {
        if (!$options || !$options->has_custom_pages() || !file_exists($correctionpath)) {
            return $correctionpath;
        }

        try {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);

            if ($options->customfirstpagepath !== null) {
                $this->append_source_pages($pdf, $options->customfirstpagepath);
            }

            $this->append_source_pages($pdf, $correctionpath);

            if ($options->customlastpagepath !== null) {
                $this->append_source_pages($pdf, $options->customlastpagepath);
            }

            $destination = rtrim($tempdir, '\\/') . DIRECTORY_SEPARATOR . 'correction_custom_' . uniqid('', true) . '.pdf';
            $content = $pdf->Output('', 'S');

            if (file_put_contents($destination, $content) === false) {
                return $correctionpath;
            }

            @unlink($correctionpath);
            return $destination;
        } catch (\Throwable $e) {
            debugging(
                'Convector failed to post-process correction PDF: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return $correctionpath;
        }
    }

    /**
     * Append every page from one source PDF into the destination document.
     *
     * @param \setasign\Fpdi\Tcpdf\Fpdi $pdf Destination PDF
     * @param string $sourcepath Source PDF path
     * @return void
     */
    private function append_source_pages(\setasign\Fpdi\Tcpdf\Fpdi $pdf, string $sourcepath): void {
        $pagecount = $pdf->setSourceFile($sourcepath);
        for ($pagenumber = 1; $pagenumber <= $pagecount; $pagenumber++) {
            $templateid = $pdf->importPage($pagenumber);
            $templatesize = $pdf->getTemplateSize($templateid);
            $pdf->AddPage($templatesize['orientation'], [$templatesize['width'], $templatesize['height']]);
            $pdf->useTemplate($templateid);
        }
    }
}
