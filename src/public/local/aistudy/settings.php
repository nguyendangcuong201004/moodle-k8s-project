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
 * Settings for local_aistudy.
 *
 * @package    local_aistudy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aistudy', get_string('pluginname', 'local_aistudy'));

    $settings->add(new admin_setting_configtext(
        'local_aistudy/baseurl',
        get_string('baseurl', 'local_aistudy'),
        get_string('baseurl_desc', 'local_aistudy'),
        'https://studyai-tr-l-h-c-t-p-th-ng-minh-239593837608.asia-southeast1.run.app/',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aistudy/openinnewtab',
        get_string('openinnewtab', 'local_aistudy'),
        get_string('openinnewtab_desc', 'local_aistudy'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
