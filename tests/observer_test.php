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
 * Testcase class for Folderview format event observer class.
 *
 * @package    format_folderview
 * @author     Sam Chaffee
 * @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Testcase class for Folderview format event observer class.
 *
 * @package    format_folderview
 * @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_folderview_observer_testcase extends advanced_testcase {

    public function test_course_content_deleted_observed() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['format' => 'folderview']);
        $user   = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('format_folderview_' . $course->id, 'something');
        format_folderview_course_set_display($course->id, 2);

        $event = \core\event\course_content_deleted::create(array(
            'objectid' => $course->id,
            'context' => context_course::instance($course->id),
            'other' => array('shortname' => $course->shortname,
                             'fullname' => $course->fullname,
                              'options' => [])
        ));

        format_folderview\observer::course_content_deleted($event);

        $this->assertFalse($DB->record_exists('format_folderview_display', ['course' => $course->id]));
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => 'format_folderview_' . $course->id]));
    }
}