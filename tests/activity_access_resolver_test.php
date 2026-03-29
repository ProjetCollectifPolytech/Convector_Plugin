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

use local_offlinequizaddons\integration\activity_access_resolver;

/**
 * Tests for the activity access resolver.
 *
 * @package    local_offlinequizaddons
 */
final class activity_access_resolver_test extends offlinequizaddons_testcase {
    public function test_resolve_returns_temporal_link_for_offlinequiz(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        [, $cm] = $this->create_offlinequiz_activity($course);

        $resolver = new activity_access_resolver();
        $accessdata = $resolver->resolve($cm, \context_course::instance($course->id));

        $this->assertNotNull($accessdata);
        $this->assertSame('offlinequizaddons_temporal', $accessdata['nodekey']);
        $this->assertSame(get_string('temporal_convector', 'local_offlinequizaddons'), $accessdata['label']);
        $this->assertSame(
            (new \moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]))->out(false),
            $accessdata['url']->out(false)
        );
    }

    public function test_resolve_returns_null_for_unsupported_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $cm = $this->create_label_cm($course);

        $resolver = new activity_access_resolver();

        $this->assertNull($resolver->resolve($cm, \context_course::instance($course->id)));
    }
}
