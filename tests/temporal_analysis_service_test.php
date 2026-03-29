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

namespace local_convector;

use local_convector\service\offlinequiz_question_repository;
use local_convector\service\temporal_analysis_service;

/**
 * Tests for the temporal analysis service.
 *
 * @package    local_convector
 */
final class temporal_analysis_service_test extends \advanced_testcase {
    public function test_validate_rejects_unsupported_question_types(): void {
        $service = new temporal_analysis_service(new temporal_analysis_fake_repository([
            1 => [
                'groupname' => 'Group 1',
                'questions' => [
                    (object) ['id' => 10, 'name' => 'Essay', 'qtype' => 'essay', 'page' => 1],
                    (object) ['id' => 11, 'name' => 'Description', 'qtype' => 'description', 'page' => 2],
                ],
            ],
        ]));

        $errors = $service->validate((object) ['id' => 7]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('description', $errors[0]);
    }

    public function test_build_analysis_computes_blank_pages_per_group(): void {
        $service = new temporal_analysis_service(new temporal_analysis_fake_repository([
            1 => [
                'groupname' => 'Group A',
                'questions' => [
                    (object) ['id' => 10, 'name' => 'Q1', 'qtype' => 'essay', 'page' => 1],
                    (object) ['id' => 11, 'name' => 'Q2', 'qtype' => 'multichoice', 'page' => 2],
                ],
            ],
            2 => [
                'groupname' => 'Group B',
                'questions' => [
                    (object) ['id' => 20, 'name' => 'Q1', 'qtype' => 'essay', 'page' => 1],
                ],
            ],
        ]));

        $analysis = $service->build_analysis((object) ['id' => 9]);

        $this->assertSame(2, $analysis['maxpages']);
        $this->assertTrue($analysis['needsnormalization']);
        $this->assertSame(0, $analysis['blankpages'][1]['blankpages']);
        $this->assertSame(1, $analysis['blankpages'][2]['blankpages']);
        $this->assertSame(4, $analysis['blankpages'][1]['targetpages']);
    }
}

/**
 * Repository stub used by temporal analysis tests.
 */
final class temporal_analysis_fake_repository extends offlinequiz_question_repository {
    /** @var array<int, array<string, mixed>> */
    private array $groups;

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    public function __construct(array $groups) {
        $this->groups = $groups;
    }

    public function get_grouped_questions(\stdClass $offlinequiz): array {
        return $this->groups;
    }
}
