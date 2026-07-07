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
 * Scales and centers an imported PDF page onto a fixed A4 portrait page.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

/**
 * Adds a fitted page that preserves the source aspect ratio inside an A4 page.
 */
class custom_page_fitter {
    /** @var float Target page width in millimetres (A4 portrait). */
    public const TARGET_WIDTH = 210.0;
    /** @var float Target page height in millimetres (A4 portrait). */
    public const TARGET_HEIGHT = 297.0;

    /**
     * Add a new A4 page and draw the imported template scaled to fit and centered.
     *
     * @param \setasign\Fpdi\Tcpdf\Fpdi $pdf Destination PDF instance.
     * @param string $templateid Imported template identifier.
     * @param array $templatesize Template size array from getTemplateSize.
     * @return void
     */
    public static function add_fitted_page(
        \setasign\Fpdi\Tcpdf\Fpdi $pdf,
        string $templateid,
        array $templatesize
    ): void {
        $srcwidth = (float) ($templatesize['width'] ?? 0);
        $srcheight = (float) ($templatesize['height'] ?? 0);

        if ($srcwidth <= 0 || $srcheight <= 0) {
            $pdf->AddPage('P', [self::TARGET_WIDTH, self::TARGET_HEIGHT]);
            $pdf->useTemplate($templateid);
            return;
        }

        $scale = min(self::TARGET_WIDTH / $srcwidth, self::TARGET_HEIGHT / $srcheight);
        $scaledwidth = $srcwidth * $scale;
        $scaledheight = $srcheight * $scale;
        $x = (self::TARGET_WIDTH - $scaledwidth) / 2;
        $y = (self::TARGET_HEIGHT - $scaledheight) / 2;

        $pdf->AddPage('P', [self::TARGET_WIDTH, self::TARGET_HEIGHT]);
        $pdf->useTemplate($templateid, $x, $y, $scaledwidth, $scaledheight);
    }
}
