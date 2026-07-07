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
 * Generation options DTO for the Convector workflow.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

/**
 * Carries per-generation options through the Convector pipeline.
 */
class generation_options {
    /** @var bool Whether the answer sheet must be merged into the questionnaire. */
    public bool $includeanswersheet;

    /** @var string|null Path to the custom first-page PDF, or null when none was uploaded. */
    public ?string $customfirstpagepath;

    /** @var string|null Path to the custom last-page PDF, or null when none was uploaded. */
    public ?string $customlastpagepath;

    /**
     * Constructor.
     *
     * @param bool $includeanswersheet Whether to include the answer sheet
     * @param string|null $customfirstpagepath Custom first-page PDF path
     * @param string|null $customlastpagepath Custom last-page PDF path
     */
    public function __construct(
        bool $includeanswersheet = true,
        ?string $customfirstpagepath = null,
        ?string $customlastpagepath = null
    ) {
        $this->includeanswersheet = $includeanswersheet;
        $this->customfirstpagepath = $customfirstpagepath;
        $this->customlastpagepath = $customlastpagepath;
    }

    /**
     * Return a default options instance (answer sheet included, no custom pages).
     *
     * @return self
     */
    public static function default(): self {
        return new self(true, null, null);
    }

    /**
     * Whether at least one custom PDF page has been provided.
     *
     * @return bool
     */
    public function has_custom_pages(): bool {
        return $this->customfirstpagepath !== null || $this->customlastpagepath !== null;
    }

    /**
     * Whether the native answer sheet must be skipped (excluded or replaced by custom last page).
     *
     * @return bool
     */
    public function should_skip_answer_sheet(): bool {
        return !$this->includeanswersheet || $this->customlastpagepath !== null;
    }

    /**
     * Whether the default cover page (page 1) is replaced by a custom first page.
     *
     * @return bool
     */
    public function should_replace_cover_page(): bool {
        return $this->customfirstpagepath !== null;
    }
}
