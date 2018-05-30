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
 * Privacy Subsystem implementation for format_folderview.
 *
 * @package    format_folderview
 * @author     Rafael Monterroza (rafael.monterroza@blackboard.com)
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_folderview\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use \core_privacy\local\legacy_polyfill;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Implementation of the privacy subsystem plugin provider for the Folderview format.
 *
 * @package    format_folderview
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider
{

    use legacy_polyfill;

    /**
     * Returns meta data about this plugin.
     *
     * @param   collection $datacollected The initialised collection to add items to.
     * @return  collection $datacollected A listing of user data stored through this plugin.
     */
    public static function _get_metadata(collection $datacollected) {

        // This table store the info of which folder the user prefers to be displayed
        // When accessing the course.
        $datacollected->add_database_table('format_folderview_display', [
            'course' => 'privacy:metadata:format_folderview_display:course',
            'userid' => 'privacy:metadata:format_folderview_display:userid',
            'display' => 'privacy:metadata:format_folderview_display:display',
        ], 'privacy:metadata:format_folderview_display');
        $datacollected->add_user_preference('format_folderview', 'privacy:metadata:preference:folderview');

        return $datacollected;
    }

    /**
     * Get the list of contexts that contain information for the specified user.
     *
     * @param  int $userid The user ID.
     * @return contextlist $contextlist The list of context used in this plugin.
     */
    public static function _get_contexts_for_userid($userid) {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} course ON course.id = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {format_folderview_display} fvd ON fvd.course = course.id
				 WHERE fvd.userid = :fdvuserid";
        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'fdvuserid'     => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param  approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;

        // Get all courses ID's.
        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                array_push($courseids, $context->instanceid);
            }
        }
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $sql = "SELECT fvd.id as fvid, fvd.userid, fvd.course, fvd.display
                  FROM {format_folderview_display} fvd
                 WHERE fvd.userid = :userid
                   AND fvd.course $insql";
        $params = ['userid' => $userid] + $inparams;

        $subcontext = ['format_folderview-sections-displayed'];
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            writer::with_context($context)->export_data($subcontext, (object) [
                'fvid' => $record->fvid,
                'userid' => $record->userid,
                'courseid' => $record->course,
                'sectionid' => $record->display,
            ]);
        }
    }

    /**
     * Delete all users data for this specific context.
     *
     * @param  \context $context The context to delete data for.
     */
    public static function _delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $courseid = null;

        if ($context instanceof \context_course) {
            $courseid = $context->instanceid;
        }
        if (empty($courseid)) {
            return;
        }

        $sql = "course = :course";
        $params = ['course' => $courseid];

        $DB->delete_records_select('format_folderview_display', $sql, $params);
    }

    /**
     * Delete all user data for this context list.
     *
     * @param  approved_contextlist $contextlist The contexts to delete data for.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        // Get all courses ID's.
        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                array_push($courseids, $context->instanceid);
            }
        }
        if (empty($courseids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $sql = "course $insql AND userid = :userid";
        $params = array_merge($inparams, ['userid' => $userid]);

        $DB->delete_records_select('format_folderview_display', $sql, $params);
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function _export_user_preferences(int $userid) {
        // We have to bring every preference for this user since the one we're concerned doesn't have a consistent name.
        $preferences = get_user_preferences(null, null, $userid);
        foreach ($preferences as $name => $value) {
            $description = null;
            if (strpos($name, 'format_folderview_') === 0) {
                if ($value) {
                    $prefsplit = explode('_', $name);
                    $description = get_string('privacy:request:preference:sections', 'format_folderview', $prefsplit[2]);
                }
            }
            if ($description !== null) {
                writer::export_user_preference('format_folderview', $name, $value, $description);
            }
        }
    }
}