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
 * English language strings for local_offlinequizaddons plugin.
 *
 * @package    local_offlinequizaddons
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'OfflineQuiz Addons';
$string['offlinequizaddons:view'] = 'View OfflineQuiz addons';
$string['tabname'] = 'Addons';
$string['helloworld'] = 'Hello World';
$string['welcome'] = 'Welcome to OfflineQuiz Addons';
$string['mainpagetitle'] = 'OfflineQuiz Addons';

// Temporal Convector strings
$string['temporal_convector'] = 'Temporal Convector';
$string['temporal_description'] = 'The Temporal Convector normalizes the number of pages across all exam copies. This ensures that all exam copies have the same number of pages, preventing layout inconsistencies.';
$string['analysis_summary'] = 'Analysis Summary';
$string['group_analysis'] = 'Group Analysis';
$string['question_details'] = 'Question Details';
$string['max_pages'] = 'Maximum pages';
$string['needs_normalization'] = 'Needs normalization';
$string['question_count'] = 'Number of questions';
$string['current_pages'] = 'Current pages';
$string['blank_pages_needed'] = 'Blank pages needed';
$string['final_pages'] = 'Final pages';
$string['pages'] = 'Pages';
$string['group'] = 'Group';
$string['generate_normalized_pdfs'] = 'Generate Normalized PDFs';
$string['no_normalization_needed'] = 'All exam copies already have the same number of pages. No normalization is needed.';
$string['temporal_info'] = 'This exam currently has {$a->currentpages} pages and will be normalized to {$a->targetpages} pages.';
$string['blankpage'] = 'Blank page for normalization';

// Instructions for PDF cover page
$string['instructions'] = 'Instructions';
$string['instructions_text'] = "- Fill out the form completely\n- Mark your answers clearly\n- Use only black or blue pen\n- Do not fold or damage this form";

// Error messages
$string['error_no_offlinequiz'] = 'Error: No offline quiz found.';
$string['error_no_questions'] = 'Error: This exam does not contain any questions. At least one question is required.';
$string['error_invalid_question_type'] = 'Error: Question type "{$a}" is not compatible. Only multiple choice and essay questions are supported.';
$string['error_pdf_generation'] = 'Error: Failed to generate PDF files. Please try again.';
