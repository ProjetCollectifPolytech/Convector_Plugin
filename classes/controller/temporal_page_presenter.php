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
 * Presenter for the Convector page.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\controller;

use html_table;
use html_table_row;
use html_writer;
use moodle_url;

/**
 * Renders the page body from prepared analysis data.
 */
class temporal_page_presenter {
    /**
     * Render the main page body.
     *
     * @param temporal_request_context $requestcontext Page context
     * @param array<string, mixed> $pagedata Manager page data
     * @param bool $cangenerate Whether the current user can generate files
     * @return string
     */
    public function render(temporal_request_context $requestcontext, array $pagedata, bool $cangenerate): string {
        global $OUTPUT;

        $output = $OUTPUT->heading(get_string('temporal_convector', 'local_convector'));
        $output .= $OUTPUT->notification(get_string('temporal_description', 'local_convector'), 'info');

        if (!$pagedata['valid']) {
            foreach ($pagedata['errors'] as $error) {
                $output .= $OUTPUT->notification($error, 'error');
            }

            return $output . $this->render_back_button($requestcontext);
        }

        $analysis = $pagedata['analysis'];
        $output .= $OUTPUT->heading(get_string('group_analysis', 'local_convector'), 3);
        $output .= html_writer::table($this->build_group_table($analysis['groups'], $analysis['blankpages']));
        $output .= $this->render_question_details($analysis['groups']);
        $output .= $this->render_actions($requestcontext, $analysis, $cangenerate);
        $output .= $this->render_back_button($requestcontext);

        return $output;
    }

    /**
     * Build the summary table for all groups.
     *
     * @param array<int, array<string, mixed>> $groups Group analysis
     * @param array<int, array<string, mixed>> $blankpages Blank page plan
     * @return html_table
     */
    private function build_group_table(array $groups, array $blankpages): html_table {
        $table = new html_table();
        $table->head = [
            get_string('group', 'offlinequiz'),
            get_string('question_count', 'local_convector'),
            get_string('current_pages', 'local_convector'),
            get_string('blank_pages_needed', 'local_convector'),
            get_string('final_pages', 'local_convector'),
        ];
        $table->attributes['class'] = 'generaltable table-striped';

        foreach ($blankpages as $groupid => $data) {
            $groupinfo = $groups[$groupid];
            $row = new html_table_row([
                $data['groupname'],
                $groupinfo['questioncount'],
                $data['currentpages'],
                $data['blankpages'],
                $data['targetpages'],
            ]);

            if ((int) $data['blankpages'] > 0) {
                $row->attributes['class'] = 'table-warning';
            }

            $table->data[] = $row;
        }

        return $table;
    }

    /**
     * Render the per-group question details.
     *
     * @param array<int, array<string, mixed>> $groups Group analysis
     * @return string
     */
    private function render_question_details(array $groups): string {
        global $OUTPUT;

        $output = html_writer::start_div('mt-4');
        $output .= $OUTPUT->heading(get_string('question_details', 'local_convector'), 3);

        foreach ($groups as $groupdata) {
            $output .= html_writer::start_tag('details', ['class' => 'mb-3']);
            $output .= html_writer::tag(
                'summary',
                $groupdata['groupname'] . ' (' . $groupdata['questioncount'] . ' ' . get_string('questions', 'question') . ')',
                ['class' => 'btn btn-link']
            );

            $table = new html_table();
            $table->head = [
                get_string('question'),
                get_string('questiontype', 'question'),
                get_string('pages', 'local_convector'),
            ];
            $table->attributes['class'] = 'table table-sm';

            foreach ($groupdata['questions'] as $questiondata) {
                $table->data[] = [
                    $questiondata['name'],
                    $this->resolve_question_type_label($questiondata['type']),
                    $questiondata['page'],
                ];
            }

            $output .= html_writer::table($table);
            $output .= html_writer::end_tag('details');
        }

        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Render the call to action section.
     *
     * @param temporal_request_context $requestcontext Page context
     * @param array<string, mixed> $analysis Analysis data
     * @param bool $cangenerate Whether generation is allowed
     * @return string
     */
    private function render_actions(
        temporal_request_context $requestcontext,
        array $analysis,
        bool $cangenerate
    ): string {
        $output = html_writer::start_div('mt-4 text-center');

        if (!$analysis['needsnormalization']) {
            $output .= html_writer::div(
                get_string('no_normalization_needed', 'local_convector'),
                'alert alert-success'
            );
            $output .= html_writer::end_div();
            return $output;
        }

        $output .= html_writer::div(
            get_string('final_pages_info', 'local_convector', $analysis['maxpages'] + 2),
            'text-muted mb-2'
        );

        if ($cangenerate) {
            $output .= $this->render_generate_form($requestcontext);
        } else {
            $output .= html_writer::div(
                get_string('cannot_generate_normalized_pdfs', 'local_convector'),
                'alert alert-warning'
            );
        }

        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Render the normalized-PDF generation form.
     *
     * @param temporal_request_context $requestcontext Page context
     * @return string
     */
    private function render_generate_form(temporal_request_context $requestcontext): string {
        $generateurl = new moodle_url('/local/convector/temporal.php');

        $output = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $generateurl->out(false),
            'enctype' => 'multipart/form-data',
            'class' => 'convector-generate-form',
        ]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $requestcontext->cm->id]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'generate']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'download', 'value' => 1]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        $output .= $this->render_toggle_file_field(
            'customfirstpage',
            get_string('custom_first_page', 'local_convector'),
            get_string('custom_first_page_help', 'local_convector')
        );
        $output .= $this->render_toggle_file_field(
            'customlastpage',
            get_string('custom_last_page', 'local_convector'),
            get_string('custom_last_page_help', 'local_convector')
        );

        $output .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('generate_normalized_pdfs', 'local_convector'),
            'class' => 'btn btn-primary mt-2',
        ]);
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Render a checkbox-toggled file upload field.
     *
     * The file drop zone is hidden and disabled until the checkbox is checked.
     *
     * @param string $fieldname Form field name
     * @param string $label Checkbox label
     * @param string $help Help text
     * @return string
     */
    private function render_toggle_file_field(string $fieldname, string $label, string $help): string {
        $containerid = $fieldname . '_container';
        $toggle = 'var c=document.getElementById(\'' . $containerid . '\');'
            . 'c.style.display=this.checked?\'block\':\'none\';'
            . 'c.querySelector(\'input[type=file]\').disabled=!this.checked;';

        $output = html_writer::start_div('form-group mb-2');
        $output .= html_writer::start_tag('label', ['class' => 'd-block font-weight-bold']);
        $output .= html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'use' . $fieldname,
            'value' => 1,
            'onchange' => $toggle,
        ]);
        $output .= ' ' . $label;
        $output .= html_writer::end_tag('label');

        $output .= html_writer::start_div('form-group mb-2', ['id' => $containerid, 'style' => 'display:none;']);
        $output .= html_writer::empty_tag('input', [
            'type' => 'file',
            'name' => $fieldname,
            'accept' => '.pdf,application/pdf',
            'class' => 'form-control-file',
            'disabled' => 'disabled',
        ]);
        $output .= html_writer::div($help, 'form-text text-muted small');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Render the back button.
     *
     * @param temporal_request_context $requestcontext Page context
     * @return string
     */
    private function render_back_button(temporal_request_context $requestcontext): string {
        global $OUTPUT;

        $backurl = new moodle_url('/mod/offlinequiz/view.php', ['id' => $requestcontext->cm->id]);
        return html_writer::div($OUTPUT->single_button($backurl, get_string('back'), 'get'), 'mt-4');
    }

    /**
     * Resolve a display label for one Moodle question type.
     *
     * @param string $qtype Question type identifier
     * @return string
     */
    private function resolve_question_type_label(string $qtype): string {
        $stringmanager = get_string_manager();

        if ($stringmanager->string_exists('pluginname', 'qtype_' . $qtype)) {
            return get_string('pluginname', 'qtype_' . $qtype);
        }

        return $qtype;
    }
}
