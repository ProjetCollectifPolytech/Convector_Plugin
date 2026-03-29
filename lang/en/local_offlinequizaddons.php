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
 * English language strings for local_offlinequizaddons.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'OfflineQuiz Addons';
$string['offlinequizaddons:view'] = 'View OfflineQuiz addons';
$string['offlinequizaddons:generate'] = 'Generate normalized OfflineQuiz archives';

$string['temporal_convector'] = 'Temporal Convector';
$string['temporal_description'] = 'Temporal Convector normalizes the number of pages across all OfflineQuiz groups so every generated copy follows the same pagination.';
$string['group_analysis'] = 'Group analysis';
$string['question_details'] = 'Question details';
$string['question_count'] = 'Number of questions';
$string['current_pages'] = 'Current pages';
$string['blank_pages_needed'] = 'Blank pages needed';
$string['final_pages'] = 'Final pages';
$string['pages'] = 'Pages';
$string['group'] = 'Group';
$string['generate_normalized_pdfs'] = 'Generate normalized PDFs';
$string['cannot_generate_normalized_pdfs'] = 'You can review the Temporal Convector analysis, but you do not have permission to generate the normalized archive.';
$string['final_pages_info'] = 'Number of pages: {$a}';
$string['no_normalization_needed'] = 'All exam copies already have the same number of pages. No normalization is needed.';

$string['error_no_offlinequiz'] = 'Error: no OfflineQuiz activity was found.';
$string['error_no_questions'] = 'Error: this exam does not contain any questions. At least one question is required.';
$string['error_invalid_question_type'] = 'Error: question type "{$a}" is not compatible. Only multichoice, essay, shortanswer and truefalse are supported.';
$string['error_pdf_generation'] = 'Error: failed to generate PDF files. Please try again.';
