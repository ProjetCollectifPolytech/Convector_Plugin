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
 * Temporal Convector view page.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

// Get parameters
$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);
$download = optional_param('download', false, PARAM_BOOL);

// Get the course module and verify that it is an OfflineQuiz
$cm = get_coursemodule_from_id('offlinequiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance], '*', MUST_EXIST);

// Require login and get context
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check capability
require_capability('local/offlinequizaddons:view', $context);

// Set up the page
$PAGE->set_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('temporal_convector', 'local_offlinequizaddons'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

// Process the temporal convector
$processor = new \local_offlinequizaddons\temporal_processor($offlinequiz);

// Handle download action
if ($download && $action === 'generate') {
    if (!$processor->validate()) {
        foreach ($processor->get_errors() as $error) {
            \core\notification::error($error);
        }
        redirect(new moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]));
    }

    // Generate PDFs
    $pdfgen = new \local_offlinequizaddons\pdf_generator($offlinequiz, $processor);
    $zipfile = $pdfgen->generate_normalized_pdfs();

    if ($zipfile && file_exists($zipfile)) {
        clearstatcache(true, $zipfile);
        
        usleep(300000);

        // Verify file is readable and has content
        $filesize = filesize($zipfile);
        if ($filesize > 0) {
            $content = file_get_contents($zipfile);
            
            if ($content !== false && strlen($content) > 0) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Send headers and content
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipfile) . '"');
                header('Content-Length: ' . strlen($content));
                header('Cache-Control: private');
                header('Pragma: public');
                
                echo $content;
                exit;
            } else {
                \core\notification::error(get_string('error_pdf_generation', 'local_offlinequizaddons') . ' (Cannot read ZIP)');
                redirect(new moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]));
            }
        } else {
            \core\notification::error(get_string('error_pdf_generation', 'local_offlinequizaddons') . ' (File size: 0)');
            redirect(new moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]));
        }
    } else {
        \core\notification::error(get_string('error_pdf_generation', 'local_offlinequizaddons'));
        redirect(new moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]));
    }
}

// Output the page
echo $OUTPUT->header();

// Hide the activity description and offlinequiz breadcrumb item
echo html_writer::tag('style', '
    .activity-description { display: none; }
    .breadcrumb-item a[href*="mod/offlinequiz/view.php"],
    .breadcrumb-item:has(a[href*="mod/offlinequiz/view.php"]) {
        display: none !important;
    }
');

// Display page heading
echo $OUTPUT->heading(get_string('temporal_convector', 'local_offlinequizaddons'));

// Display description
echo html_writer::div(
    get_string('temporal_description', 'local_offlinequizaddons'),
    'alert alert-info'
);

// Validate the offlinequiz and display analysis
if (!$processor->validate()) {
    foreach ($processor->get_errors() as $error) {
        echo $OUTPUT->notification($error, 'error');
    }
} else {
    $analysis = $processor->analyze_copies();
    
    echo $OUTPUT->heading(get_string('group_analysis', 'local_offlinequizaddons'), 3);
    
    $table = new html_table();
    $table->head = [
        get_string('group', 'offlinequiz'),
        get_string('question_count', 'local_offlinequizaddons'),
        get_string('current_pages', 'local_offlinequizaddons'),
        get_string('blank_pages_needed', 'local_offlinequizaddons'),
        get_string('final_pages', 'local_offlinequizaddons')
    ];
    $table->attributes['class'] = 'generaltable table-striped';

    $blankpages = $processor->calculate_blank_pages();
    foreach ($blankpages as $groupid => $data) {
        $groupinfo = $analysis['groups'][$groupid];
        $row = new html_table_row([
            $data['groupname'],
            $groupinfo['questioncount'],
            $data['currentpages'],
            $data['blankpages'],
            $data['targetpages']
        ]);

        if ($data['blankpages'] > 0) {
            $row->attributes['class'] = 'table-warning';
        }
        
        $table->data[] = $row;
    }

    echo html_writer::table($table);

    echo html_writer::start_tag('div', ['class' => 'mt-4']);
    echo $OUTPUT->heading(get_string('question_details', 'local_offlinequizaddons'), 3);
    
    foreach ($analysis['groups'] as $groupid => $groupdata) {
        echo html_writer::start_tag('details', ['class' => 'mb-3']);
        echo html_writer::tag('summary', 
            $groupdata['groupname'] . ' (' . $groupdata['questioncount'] . ' ' . 
            get_string('questions', 'question') . ')',
            ['class' => 'btn btn-link']
        );
        
        $qtable = new html_table();
        $qtable->head = [
            get_string('question'),
            get_string('questiontype', 'question'),
            get_string('pages', 'local_offlinequizaddons')
        ];
        $qtable->attributes['class'] = 'table table-sm';
        
        foreach ($groupdata['questions'] as $qdata) {
            $questiontype = get_string('pluginname', 'qtype_' . $qdata['type']);
            
            if ($qdata['type'] === 'multichoice') {
                $correctanswers = $DB->count_records_select('question_answers', 
                    'question = ? AND fraction > 0', 
                    [$qdata['id']]
                );
                
                if ($correctanswers === 1) {
                    $questiontype = 'Choix unique';
                } else {
                    $questiontype = 'Choix multiple';
                }
            }
            
            $qtable->data[] = [
                $qdata['name'],
                $questiontype,
                $qdata['page']
            ];
        }
        
        echo html_writer::table($qtable);
        echo html_writer::end_tag('details');
    }
    echo html_writer::end_tag('div');

    // Display action buttons
    echo html_writer::start_tag('div', ['class' => 'mt-4 text-center']);
    
    if ($analysis['needsnormalization']) {
        $generateurl = new moodle_url('/local/offlinequizaddons/temporal.php', [
            'id' => $cm->id,
            'action' => 'generate',
            'download' => 1
        ]);
        $finalpagecount = $analysis['maxpages'] + 2;
        
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $generateurl->out_omit_querystring()]);
        foreach ($generateurl->params() as $key => $value) {
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
        }
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('generate_normalized_pdfs', 'local_offlinequizaddons'),
            'class' => 'btn',
            'style' => 'background-color: #0f6cbf; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 4px; cursor: pointer;'
        ]);
        echo html_writer::div(
            get_string('final_pages_info', 'local_offlinequizaddons', $finalpagecount),
            '',
            ['style' => 'margin-top: 8px; color: #555; font-size: 14px;']
        );
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::div(
            get_string('no_normalization_needed', 'local_offlinequizaddons'),
            'alert alert-success'
        );
    }
    
    echo html_writer::end_tag('div');
}

// Back button
echo html_writer::start_tag('div', ['class' => 'mt-4']);
$backurl = new moodle_url('/local/offlinequizaddons/view.php', ['id' => $cm->id]);
echo $OUTPUT->single_button($backurl, get_string('back'), 'get');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
