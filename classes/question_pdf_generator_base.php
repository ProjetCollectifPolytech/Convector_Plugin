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
 * Shared generation flow for question-based PDF artifacts.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

use local_convector\service\offlinequiz_group_context_loader;
use local_convector\service\question_pdf_document_builder;
use local_convector\service\question_pdf_question_renderer;
use local_convector\service\question_pdf_rendering_data_loader;
use stdClass;

/**
 * Base flow for questionnaire-style PDF generation.
 */
abstract class question_pdf_generator_base extends base_generator {
    /** @var question_pdf_document_builder */
    private question_pdf_document_builder $documentbuilder;

    /** @var question_pdf_rendering_data_loader */
    private question_pdf_rendering_data_loader $renderingdataloader;

    /** @var question_pdf_question_renderer */
    private question_pdf_question_renderer $questionrenderer;

    /**
     * Constructor.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param string $tempdir Temporary directory
     * @param offlinequiz_group_context_loader|null $groupcontextloader Group-context loader
     * @param question_pdf_document_builder|null $documentbuilder Document builder
     * @param question_pdf_rendering_data_loader|null $renderingdataloader Rendering-data loader
     * @param question_pdf_question_renderer|null $questionrenderer Question renderer
     */
    public function __construct(
        stdClass $offlinequiz,
        string $tempdir,
        ?offlinequiz_group_context_loader $groupcontextloader = null,
        ?question_pdf_document_builder $documentbuilder = null,
        ?question_pdf_rendering_data_loader $renderingdataloader = null,
        ?question_pdf_question_renderer $questionrenderer = null
    ) {
        parent::__construct($offlinequiz, $tempdir, $groupcontextloader);
        $this->documentbuilder = $documentbuilder ?? new question_pdf_document_builder();
        $this->renderingdataloader = $renderingdataloader ?? new question_pdf_rendering_data_loader();
        $this->questionrenderer = $questionrenderer ?? new question_pdf_question_renderer();
    }

    /**
     * Generate one PDF file for one OfflineQuiz group.
     *
     * @param int $groupid OfflineQuiz group id
     * @param array $data Group normalization data
     * @return string|false
     */
    public function generate($groupid, $data) {
        $groupcontext = $this->load_group_context((int) $groupid);
        if ($groupcontext === null) {
            return false;
        }

        $translator = null;

        try {
            return $this->with_disabled_shuffle(function () use (&$translator, $data, $groupcontext) {
                $translator = new \offlinequiz_html_translator();
                $pdf = $this->documentbuilder->create_document(
                    $this->offlinequiz,
                    $groupcontext,
                    $this->get_cover_heading_string_identifier()
                );

                $renderingdata = $this->renderingdataloader->load($this->offlinequiz, $groupcontext);
                if ($renderingdata === null) {
                    return false;
                }

                $this->questionrenderer->render(
                    $pdf,
                    $this->offlinequiz,
                    $groupcontext,
                    $renderingdata,
                    $translator,
                    $this->should_render_correction_answers()
                );
                $this->after_questions_rendered($pdf, $data);

                return $this->write_document(
                    $pdf,
                    $groupcontext['groupletter'],
                    get_string($this->get_output_prefix_string_identifier(), 'offlinequiz')
                );
            });
        } catch (\Throwable $e) {
            debugging(
                'Convector failed to generate ' . static::class . ' for group ' .
                $groupcontext['group']->id . ': ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return false;
        } finally {
            if ($translator instanceof \offlinequiz_html_translator) {
                $translator->remove_temp_files();
            }
        }
    }

    /**
     * Return the cover-page heading string identifier.
     *
     * @return string
     */
    abstract protected function get_cover_heading_string_identifier(): string;

    /**
     * Return the output filename prefix string identifier.
     *
     * @return string
     */
    abstract protected function get_output_prefix_string_identifier(): string;

    /**
     * Whether answer markup must include correction hints.
     *
     * @return bool
     */
    abstract protected function should_render_correction_answers(): bool;

    /**
     * Hook for generators that need to append extra pages after rendering.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param array $data Group normalization data
     * @return void
     */
    protected function after_questions_rendered(\offlinequiz_question_pdf $pdf, array $data): void {
        return;
    }

    /**
     * Persist the rendered PDF into the request temp directory.
     *
     * @param \offlinequiz_question_pdf $pdf PDF document
     * @param string $groupletter Group letter
     * @param string $prefix Output prefix
     * @return string|false
     */
    private function write_document(\offlinequiz_question_pdf $pdf, string $groupletter, string $prefix) {
        $filepath = $this->create_timestamped_file_path($prefix, $groupletter);
        $pdfcontent = $pdf->Output('', 'S');

        return file_put_contents($filepath, $pdfcontent) === false ? false : $filepath;
    }
}
