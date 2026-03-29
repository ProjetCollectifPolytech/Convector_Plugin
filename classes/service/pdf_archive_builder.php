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
 * ZIP archive builder for generated Convector PDFs.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\service;

use stdClass;
use ZipArchive;

/**
 * Packages generated PDFs into the downloadable ZIP archive.
 */
class pdf_archive_builder {
    /**
     * Build the final ZIP archive.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param string $tempdir Request temp directory
     * @param array<int, array<string, string>> $pdffiles PDF files to archive
     * @return string|false
     */
    public function build(stdClass $offlinequiz, string $tempdir, array $pdffiles) {
        $zippath = rtrim($tempdir, '\\/') . DIRECTORY_SEPARATOR .
            'offlinequiz_' . $offlinequiz->id . '_normalized_' . time() . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zippath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $folders = $this->get_archive_folder_map();
        foreach ($folders as $folder) {
            $zip->addEmptyDir($folder);
        }

        foreach ($pdffiles as $fileinfo) {
            if (!file_exists($fileinfo['path'])) {
                continue;
            }

            $folder = $folders[$fileinfo['type']] ?? 'Autres';
            $zip->addFile($fileinfo['path'], $folder . '/' . basename($fileinfo['path']));
        }

        return $zip->close() && file_exists($zippath) ? $zippath : false;
    }

    /**
     * Return the folder mapping used inside the ZIP archive.
     *
     * @return array<string, string>
     */
    private function get_archive_folder_map(): array {
        return [
            'questionnaire' => 'Questionnaires',
            'correction' => 'Formulaires de correction',
        ];
    }
}
