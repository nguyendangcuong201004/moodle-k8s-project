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
 * Public API for local_aistudy.
 *
 * @package    local_aistudy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a course-level launch link to AI Study.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course object.
 * @param context $context Course context.
 * @return void
 */
function local_aistudy_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if ($course->id == SITEID || isguestuser()) {
        return;
    }

    if (!has_capability('local/aistudy:launch', $context)) {
        return;
    }

    $url = new moodle_url('/local/aistudy/launch.php', ['id' => $course->id]);
    $navigation->add(
        get_string('launchaistudy', 'local_aistudy'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_aistudy_launch',
        new pix_icon('i/link', '')
    );
}

/**
 * Builds a stable AI Study course key for shared course workspace.
 *
 * @param stdClass $course Course object.
 * @return string
 */
function local_aistudy_build_course_key(stdClass $course): string {
    $shortname = core_text::strtolower((string)$course->shortname);
    $shortname = preg_replace('/[^a-z0-9]+/', '-', $shortname);
    $shortname = trim($shortname, '-');

    if ($shortname === '') {
        $shortname = 'course-' . $course->id;
    }

    return 'moodle-' . $course->id . '-' . $shortname;
}

/**
 * Returns configured AI Study base URL.
 *
 * @return string
 */
function local_aistudy_get_base_url(): string {
    $baseurl = trim((string)get_config('local_aistudy', 'baseurl'));
    if ($baseurl === '') {
        $baseurl = 'https://studyai-tr-l-h-c-t-p-th-ng-minh-239593837608.asia-southeast1.run.app/';
    }

    return $baseurl;
}

/**
 * Builds a deep link URL to AI Study with course context.
 *
 * @param stdClass $course Course object.
 * @return moodle_url
 */
function local_aistudy_build_launch_url(stdClass $course): moodle_url {
    global $CFG;

    $coursekey = local_aistudy_build_course_key($course);

    $params = [
        'source' => 'moodle',
        'courseid' => (string)$course->id,
        'shortname' => (string)$course->shortname,
        'fullname' => (string)$course->fullname,
        'coursekey' => $coursekey,
        'subject' => $coursekey,
        'moodlehost' => (string)$CFG->wwwroot,
    ];

    return new moodle_url(local_aistudy_get_base_url(), $params);
}
