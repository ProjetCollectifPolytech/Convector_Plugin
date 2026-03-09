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
 * Base Generator class for PDF generation - provides common functionality.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

defined('MOODLE_INTERNAL') || die();

/**
 * Base generator class with common functionality for all PDF generators.
 */
abstract class base_generator {

    /** @var object The offlinequiz instance */
    protected $offlinequiz;

    /** @var temporal_processor The processor */
    protected $processor;

    /** @var string Temporary directory for PDFs */
    protected $tempdir;

    /**
     * Constructor.
     *
     * @param object $offlinequiz The offlinequiz instance
     * @param temporal_processor $processor The temporal processor
     * @param string $tempdir The temporary directory
     */
    public function __construct($offlinequiz, $processor, $tempdir) {
        $this->offlinequiz = $offlinequiz;
        $this->processor = $processor;
        $this->tempdir = $tempdir;
    }
}
