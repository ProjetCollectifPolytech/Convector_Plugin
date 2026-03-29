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
 * Composition root for the Convector facade.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

use local_convector\service\offlinequiz_question_repository;
use local_convector\service\pdf_generation_service;
use local_convector\service\temporal_analysis_service;

/**
 * Builds fully-wired manager instances.
 */
class manager_factory {
    /**
     * Create a default manager instance.
     *
     * @return manager
     */
    public static function create_default(): manager {
        return (new self())->create();
    }

    /**
     * Build one manager with the plugin's standard dependency graph.
     *
     * @return manager
     */
    public function create(): manager {
        $repository = new offlinequiz_question_repository();
        $analysisservice = new temporal_analysis_service($repository);

        return new manager(
            $analysisservice,
            new pdf_generation_service()
        );
    }
}
