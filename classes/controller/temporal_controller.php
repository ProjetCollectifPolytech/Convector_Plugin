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
 * Convector controller.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\controller;

use core\notification;
use local_convector\convector_event_dispatcher;
use local_convector\manager;
use local_convector\service\uploaded_pdf_resolver;
use local_convector\util\download_handler;

/**
 * Handles page display and archive downloads.
 */
class temporal_controller {
    /** @var manager */
    private manager $manager;
    /** @var temporal_context_loader */
    private temporal_context_loader $contextloader;
    /** @var temporal_page_presenter */
    private temporal_page_presenter $presenter;
    /** @var convector_event_dispatcher */
    private convector_event_dispatcher $eventdispatcher;
    /** @var uploaded_pdf_resolver */
    private uploaded_pdf_resolver $uploadedpdfresolver;

    /**
     * Constructor.
     *
     * @param manager $manager
     * @param temporal_context_loader|null $contextloader
     * @param temporal_page_presenter|null $presenter
     * @param convector_event_dispatcher|null $eventdispatcher
     * @param uploaded_pdf_resolver|null $uploadedpdfresolver
     */
    public function __construct(
        manager $manager,
        ?temporal_context_loader $contextloader = null,
        ?temporal_page_presenter $presenter = null,
        ?convector_event_dispatcher $eventdispatcher = null,
        ?uploaded_pdf_resolver $uploadedpdfresolver = null
    ) {
        $this->manager = $manager;
        $this->contextloader = $contextloader ?? new temporal_context_loader();
        $this->presenter = $presenter ?? new temporal_page_presenter();
        $this->eventdispatcher = $eventdispatcher ?? new convector_event_dispatcher();
        $this->uploadedpdfresolver = $uploadedpdfresolver ?? new uploaded_pdf_resolver();
    }

    /**
     * Handle the current request.
     *
     * @param int $cmid Course module id
     * @param string $action Current action
     * @param bool $download Whether a download was requested
     * @return void
     */
    public function handle(int $cmid, string $action, bool $download): void {
        global $OUTPUT;

        $requestcontext = $this->contextloader->load($cmid);

        if ($download && $action === 'generate') {
            $this->handle_download($requestcontext);
            return;
        }

        $pagedata = $this->manager->build_page_data($requestcontext->offlinequiz);
        $cangenerate = has_capability('local/convector:generate', $requestcontext->context);
        $this->eventdispatcher->trigger_page_viewed($requestcontext, $pagedata, $cangenerate);
        echo $OUTPUT->header();
        echo $this->presenter->render($requestcontext, $pagedata, $cangenerate);
        echo $OUTPUT->footer();
    }

    /**
     * Generate and download the normalized archive.
     *
     * @param temporal_request_context $requestcontext Page context
     * @return void
     */
    private function handle_download(temporal_request_context $requestcontext): void {
        require_sesskey();
        require_capability('local/convector:generate', $requestcontext->context);

        $options = $this->uploadedpdfresolver->resolve_options();
        $result = $this->manager->generate_normalized_archive($requestcontext->offlinequiz, $options);
        $this->uploadedpdfresolver->cleanup();

        if (!$result['success']) {
            foreach ($result['errors'] as $error) {
                notification::error($error);
            }

            redirect($requestcontext->url);
        }

        $this->eventdispatcher->trigger_archive_generated($requestcontext, $result);
        download_handler::send_file($result['filepath'], $result['downloadname']);
    }
}
