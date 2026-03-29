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

namespace local_offlinequizaddons;

use local_offlinequizaddons\service\pdf_generation_service;
use local_offlinequizaddons\service\temporal_analysis_service;

/**
 * Tests for the Convector manager facade.
 *
 * @package    local_offlinequizaddons
 */
final class manager_test extends \advanced_testcase {
    public function test_build_page_data_returns_validation_errors(): void {
        $manager = new manager(
            new manager_fake_analysis_service(['Validation error'], []),
            new manager_fake_pdf_generation_service(null)
        );

        $pagedata = $manager->build_page_data((object) ['id' => 1]);

        $this->assertFalse($pagedata['valid']);
        $this->assertSame(['Validation error'], $pagedata['errors']);
        $this->assertNull($pagedata['analysis']);
    }

    public function test_generate_normalized_archive_returns_download_payload_on_success(): void {
        $analysis = [
            'blankpages' => [
                1 => ['blankpages' => 0, 'targetpages' => 4],
            ],
        ];
        $manager = new manager(
            new manager_fake_analysis_service([], $analysis),
            new manager_fake_pdf_generation_service([
                'filepath' => '/tmp/generated.zip',
                'downloadname' => 'generated.zip',
            ])
        );

        $result = $manager->generate_normalized_archive((object) ['id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertSame('/tmp/generated.zip', $result['filepath']);
        $this->assertSame('generated.zip', $result['downloadname']);
    }

    public function test_generate_normalized_archive_surfaces_generation_failure(): void {
        $analysis = [
            'blankpages' => [
                1 => ['blankpages' => 1, 'targetpages' => 5],
            ],
        ];
        $manager = new manager(
            new manager_fake_analysis_service([], $analysis),
            new manager_fake_pdf_generation_service(null)
        );

        $result = $manager->generate_normalized_archive((object) ['id' => 1]);

        $this->assertFalse($result['success']);
        $this->assertSame([get_string('error_pdf_generation', 'local_offlinequizaddons')], $result['errors']);
    }
}

/**
 * Analysis-service stub used by manager tests.
 */
final class manager_fake_analysis_service extends temporal_analysis_service {
    /** @var string[] */
    private array $errors;

    /** @var array<string, mixed> */
    private array $analysis;

    /**
     * @param string[] $errors
     * @param array<string, mixed> $analysis
     */
    public function __construct(array $errors, array $analysis) {
        $this->errors = $errors;
        $this->analysis = $analysis;
    }

    public function validate(?\stdClass $offlinequiz): array {
        return $this->errors;
    }

    public function build_analysis(\stdClass $offlinequiz): array {
        return $this->analysis;
    }
}

/**
 * PDF-generation stub used by manager tests.
 */
final class manager_fake_pdf_generation_service extends pdf_generation_service {
    /** @var array<string, string>|null */
    private ?array $result;

    /**
     * @param array<string, string>|null $result
     */
    public function __construct(?array $result) {
        $this->result = $result;
    }

    public function generate_normalized_archive(\stdClass $offlinequiz, array $blankpages): ?array {
        return $this->result;
    }
}
