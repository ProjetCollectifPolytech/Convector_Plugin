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
 * Uploaded PDF resolver for the Convector generation form.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use local_convector\generation_options;

/**
 * Resolves uploaded custom-page PDFs and the answer-sheet toggle.
 */
class uploaded_pdf_resolver {
    /** @var string */
    private string $tempdir;

    /** @var string[] */
    private array $managedpaths = [];

    /**
     * Constructor.
     *
     * @param string|null $tempdir Request-scoped temp directory
     */
    public function __construct(?string $tempdir = null) {
        $this->tempdir = $tempdir ?? make_temp_directory('convector/uploads/' . str_replace('.', '', uniqid('run_', true)));
    }

    /**
     * Build the generation options from the current request.
     *
     * @return generation_options
     */
    public function resolve_options(): generation_options {
        $includeanswersheet = optional_param('includeanswersheet', true, PARAM_BOOL);

        return new generation_options(
            $includeanswersheet,
            $this->resolve_uploaded_file('customfirstpage'),
            $this->resolve_uploaded_file('customlastpage')
        );
    }

    /**
     * Remove every uploaded file managed by this resolver.
     *
     * @return void
     */
    public function cleanup(): void {
        foreach ($this->managedpaths as $path) {
            if (is_string($path) && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Resolve one uploaded PDF into a temp file path.
     *
     * @param string $fieldname Form field name
     * @return string|null Resolved path, or null when no valid file was uploaded
     */
    private function resolve_uploaded_file(string $fieldname): ?string {
        if (empty($_FILES[$fieldname]['tmp_name']) || ($_FILES[$fieldname]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $originalname = (string) $_FILES[$fieldname]['name'];
        if (strtolower(pathinfo($originalname, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $destination = rtrim($this->tempdir, '\\/') . DIRECTORY_SEPARATOR . $fieldname . '_' . uniqid('', true) . '.pdf';
        if (!move_uploaded_file($_FILES[$fieldname]['tmp_name'], $destination)) {
            return null;
        }

        $this->managedpaths[] = $destination;
        return $destination;
    }
}
