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
 * Site-wide admin settings for local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_quizextensionmanager', get_string('pluginname', 'local_quizextensionmanager'));
    $ADMIN->add('localplugins', $settings);

    // Maximum extension allowed, in minutes. 0 = no site-wide limit.
    // TODO: decide final units/validation (minutes vs. hours) and enforce this cap when requests are approved.
    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/maxextension',
        get_string('settings:maxextension', 'local_quizextensionmanager'),
        get_string('settings:maxextension_desc', 'local_quizextensionmanager'),
        0,
        PARAM_INT
    ));

    // Whether a reason is required by default. Can be overridden per quiz.
    $settings->add(new admin_setting_configcheckbox(
        'local_quizextensionmanager/reasonrequired',
        get_string('settings:reasonrequired', 'local_quizextensionmanager'),
        get_string('settings:reasonrequired_desc', 'local_quizextensionmanager'),
        1
    ));

    // Default documentation mode: none / optional / required. Can be overridden per quiz.
    $settings->add(new admin_setting_configselect(
        'local_quizextensionmanager/documentationmode',
        get_string('settings:documentationmode', 'local_quizextensionmanager'),
        get_string('settings:documentationmode_desc', 'local_quizextensionmanager'),
        'none',
        [
            'none'     => get_string('documentationmode:none', 'local_quizextensionmanager'),
            'optional' => get_string('documentationmode:optional', 'local_quizextensionmanager'),
            'required' => get_string('documentationmode:required', 'local_quizextensionmanager'),
        ]
    ));

    // Default allowed documentation file types. Can be overridden per quiz.
    // admin_setting_filetypes gives admins the same file-type browser widget
    // used for e.g. assignment submission types, rather than a bare text box.
    $settings->add(new admin_setting_filetypes(
        'local_quizextensionmanager/allowedfiletypes',
        get_string('settings:allowedfiletypes', 'local_quizextensionmanager'),
        get_string('settings:allowedfiletypes_desc', 'local_quizextensionmanager'),
        'document image'
    ));

    // Notification templates.
    // TODO: decide on and document the full set of supported placeholders (e.g. {$a->quizname}).
    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifyapprovedsubject',
        get_string('settings:notifyapprovedsubject', 'local_quizextensionmanager'),
        get_string('settings:notifyapprovedsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifyapprovedbody',
        get_string('settings:notifyapprovedbody', 'local_quizextensionmanager'),
        get_string('settings:notifyapprovedbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifydeniedsubject',
        get_string('settings:notifydeniedsubject', 'local_quizextensionmanager'),
        get_string('settings:notifydeniedsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifydeniedbody',
        get_string('settings:notifydeniedbody', 'local_quizextensionmanager'),
        get_string('settings:notifydeniedbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifynewrequestsubject',
        get_string('settings:notifynewrequestsubject', 'local_quizextensionmanager'),
        get_string('settings:notifynewrequestsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifynewrequestbody',
        get_string('settings:notifynewrequestbody', 'local_quizextensionmanager'),
        get_string('settings:notifynewrequestbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));
}
