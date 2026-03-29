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
 * Temporal Convector controller.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\controller;

use core\notification;
use local_offlinequizaddons\manager;
use local_offlinequizaddons\util\download_handler;

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

    /**
     * Constructor.
     *
     * @param manager $manager
     * @param temporal_context_loader|null $contextloader
     * @param temporal_page_presenter|null $presenter
     */
    public function __construct(
        manager $manager,
        ?temporal_context_loader $contextloader = null,
        ?temporal_page_presenter $presenter = null
    ) {
        $this->manager = $manager;
        $this->contextloader = $contextloader ?? new temporal_context_loader();
        $this->presenter = $presenter ?? new temporal_page_presenter();
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

        echo $OUTPUT->header();
        echo $this->presenter->render(
            $requestcontext,
            $pagedata,
            has_capability('local/offlinequizaddons:generate', $requestcontext->context)
        );
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
        require_capability('local/offlinequizaddons:generate', $requestcontext->context);

        $result = $this->manager->generate_normalized_archive($requestcontext->offlinequiz);
        if (!$result['success']) {
            foreach ($result['errors'] as $error) {
                notification::error($error);
            }

            redirect($requestcontext->url);
        }

        download_handler::send_file($result['filepath'], $result['downloadname']);
    }
}
