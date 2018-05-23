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
 * @author     Rafael Monterroza (rafael.monterroza@blackboard.com)
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \format_folderview\privacy\provider;
use \core_privacy\local\request\approved_contextlist;

/**
 * Testcase class for Folderview format event observer class.
 *
 * @package    format_folderview
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_folderview_privacy_testcase extends \core_privacy\tests\provider_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Ensure that get_metadata exports valid content.
     */
    public function test_get_metadata() {
        $items = new collection('format_folderview');
        $result = provider::get_metadata($items);
        $this->assertSame($items, $result);
        $this->assertInstanceOf(collection::class, $result);
    }

    private function create_display_data_for_testing($courseid, $userid, $display) {
        global $DB;

        $DB->insert_record('format_folderview_display', (object) array(
            'course'   => $courseid,
            'userid'   => $userid,
            'display'  => $display,
        ));
    }

    /**
     * Ensure that get_contexts_for_userid exports a valid context list.
     */
    public function test_get_contexts_for_userid() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($user->id));

        $this->create_display_data_for_testing($course->id, $user->id, 2);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $coursecontext = \context_course::instance($course->id);
        $this->assertEquals($coursecontext->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Ensure that export_user_data exports valid content.
     */
    public function test_export_user_data() {
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $this->create_display_data_for_testing($course1->id, $user1->id, 3);

        $coursecontext1 = \context_course::instance($course1->id);
        $writer = writer::with_context($coursecontext1);
        $this->assertFalse($writer->has_any_data());

        $approvedlist = new approved_contextlist($user1, 'format_folderview', [$coursecontext1->id]);
        provider::export_user_data($approvedlist);
        $data = $writer->get_data(['format_folderview-sections-displayed']);

        $this->assertEquals($course1->id, $data->courseid);
        $this->assertEquals($user1->id, $data->userid);
        $this->assertEquals(3, $data->sectionid);
    }

    /**
     * Ensure that delete_data_for_all_users_in_context delete the users data in the given context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $this->create_display_data_for_testing($course1->id, $user1->id, 4);
        $user2 = $this->getDataGenerator()->create_user();
        $this->create_display_data_for_testing($course1->id, $user2->id, 5);
        $this->assertEquals(2, $DB->count_records('format_folderview_display', []));

        $coursecontext1 = \context_course::instance($course1->id);
        provider::delete_data_for_all_users_in_context($coursecontext1);
        $this->assertEquals(0, $DB->count_records('format_folderview_display', []));
    }

    /**
     * Ensure that delete_data_for_user delete just the expected user data.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $this->create_display_data_for_testing($course1->id, $user1->id, 6);
        $user2 = $this->getDataGenerator()->create_user();
        $this->create_display_data_for_testing($course1->id, $user2->id, 7);
        $this->assertEquals(2, $DB->count_records('format_folderview_display', []));

        $coursecontext1 = \context_course::instance($course1->id);
        $approvedcontextlist1 = new approved_contextlist($user1, 'format_folderview', [$coursecontext1->id]);
        provider::delete_data_for_user($approvedcontextlist1);

        $this->assertEquals(0, $DB->count_records('format_folderview_display', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('format_folderview_display', ['userid' => $user2->id]));

        $approvedcontextlist2 = new approved_contextlist($user2, 'format_folderview', [$coursecontext1->id]);
        provider::delete_data_for_user($approvedcontextlist2);

        $this->assertEquals(0, $DB->count_records('format_folderview_display', []));
    }

    /**
     * Ensure that export_user_preferences returns no data if the user has no preferences stored.
     */
    public function test_export_user_preferences_no_data() {
        $user = \core_user::get_user_by_username('admin');
        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());

        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Ensure that export_user_preferences returns some data when there are preferences stored.
     */
    public function test_export_user_preferences_with_data() {
        $this->setAdminUser();

        $user = \core_user::get_user_by_username('admin');
        set_user_preference('format_folderview_1', '3,4,5');
        set_user_preference('format_folderview_2', '7,8,9');

        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());

        $this->assertTrue($writer->has_any_data());
        $preferences = $writer->get_user_preferences('format_folderview');

        $this->assertCount(2, (array) $preferences);
    }
}