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

use local_convector\event\archive_generated;
use local_convector\event\page_viewed;
use local_convector\service\offlinequiz_activity_service;

require_once(__DIR__ . '/convector_testcase.php');

/**
 * Tests for the Convector event dispatcher.
 *
 * @package    local_convector
 */
final class convector_event_dispatcher_test extends convector_testcase {
    public function test_trigger_page_viewed_emits_expected_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        [, $cm] = $this->create_offlinequiz_activity($course);
        $requestcontext = (new offlinequiz_activity_service())->load_request_context($cm->id);

        $sink = $this->redirectEvents();
        (new convector_event_dispatcher())->trigger_page_viewed($requestcontext, [
            'valid' => true,
            'errors' => [],
            'analysis' => [
                'needsnormalization' => true,
                'groups' => [
                    1 => ['groupname' => 'Group A'],
                    2 => ['groupname' => 'Group B'],
                ],
            ],
        ], true);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(page_viewed::class, $event);
        $this->assertSame((int) $cm->id, (int) $event->objectid);
        $this->assertSame(1, $event->other['valid']);
        $this->assertSame(1, $event->other['can_generate']);
        $this->assertSame(1, $event->other['needs_normalization']);
        $this->assertSame(2, $event->other['group_count']);
    }

    public function test_trigger_archive_generated_emits_expected_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        [, $cm] = $this->create_offlinequiz_activity($course);
        $requestcontext = (new offlinequiz_activity_service())->load_request_context($cm->id);

        $sink = $this->redirectEvents();
        (new convector_event_dispatcher())->trigger_archive_generated($requestcontext, [
            'downloadname' => 'convector.zip',
            'analysis' => [
                'maxpages' => 6,
                'groups' => [
                    1 => ['groupname' => 'Group A'],
                ],
            ],
        ]);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(archive_generated::class, $event);
        $this->assertSame((int) $cm->id, (int) $event->objectid);
        $this->assertSame('convector.zip', $event->other['downloadname']);
        $this->assertSame(1, $event->other['group_count']);
        $this->assertSame(8, $event->other['final_pages']);
    }
}
