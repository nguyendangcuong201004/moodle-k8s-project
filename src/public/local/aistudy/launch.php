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
 * Course launch endpoint for AI Study deep-link.
 *
 * @package    local_aistudy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/aistudy/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);

require_login($course);

$context = context_course::instance($course->id);
require_capability('local/aistudy:launch', $context);

$targeturl = local_aistudy_build_launch_url($course);

if (filter_var(get_config('local_aistudy', 'openinnewtab'), FILTER_VALIDATE_BOOLEAN)) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/aistudy/launch.php', ['id' => $course->id]));
    $PAGE->set_title(get_string('launchaistudy', 'local_aistudy'));
    $returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);

    echo $OUTPUT->header();
    $button = html_writer::link($targeturl, get_string('launchaistudy', 'local_aistudy'), [
        'class' => 'btn btn-primary',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]);
    echo html_writer::div($button, 'mb-3');
    $launchscript = 'window.open(' . json_encode($targeturl->out(false)) . ", '_blank', 'noopener'); " .
        'window.location = ' . json_encode($returnurl->out(false)) . ';';
    echo html_writer::script($launchscript);
    echo $OUTPUT->footer();
    exit;
}

redirect($targeturl);
