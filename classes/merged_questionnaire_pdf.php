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
 * FPDI wrapper used to merge questionnaire and answer-sheet PDFs.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/autoload.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Rewrites the footer of merged PDFs while hiding source pagination.
 */
class merged_questionnaire_pdf extends \setasign\Fpdi\Tcpdf\Fpdi {
    /** @var array<int, string> */
    private array $pagetypes = [];

    /**
     * Remember the source type for the current merged page.
     *
     * @param string $type Source page type
     * @return void
     */
    public function register_page_type(string $type): void {
        $this->pagetypes[$this->PageNo()] = $type;
    }

    /**
     * Resolve the source type for the current page.
     *
     * @return string
     */
    private function get_current_page_type(): string {
        return $this->pagetypes[$this->PageNo()] ?? 'questionnaire';
    }

    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod,Squiz.Commenting.FunctionComment.Missing
    /**
     * Render the merged-document footer.
     *
     * @return void
     */
    public function Footer() {
        $pagewidth = $this->getPageWidth();
        $pageheight = $this->getPageHeight();
        $pagetype = $this->get_current_page_type();

        $this->SetFillColor(255, 255, 255);

        if ($pagetype === 'answer_sheet') {
            $this->Rect(($pagewidth - 60) / 2, $pageheight - 16, 60, 8, 'F');
        } else {
            $this->Rect(($pagewidth - 70) / 2, $pageheight - 28, 70, 16, 'F');
        }

        $this->SetY(-11);
        $this->SetFont(offlinequiz_get_pdffont(), 'I', 8);
        $this->Cell(
            0,
            4,
            offlinequiz_str_html_pdf(get_string('page')) . ' ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
            0,
            0,
            'C'
        );
    }
    // phpcs:enable
}
