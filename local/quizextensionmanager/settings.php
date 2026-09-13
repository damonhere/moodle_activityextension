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
        new lang_string('settings:maxextension', 'local_quizextensionmanager'),
        new lang_string('settings:maxextension_desc', 'local_quizextensionmanager'),
        0,
        PARAM_INT
    ));

    // Whether a reason is required by default. Can be overridden per quiz.
    $settings->add(new admin_setting_configcheckbox(
        'local_quizextensionmanager/reasonrequired',
        new lang_string('settings:reasonrequired', 'local_quizextensionmanager'),
        new lang_string('settings:reasonrequired_desc', 'local_quizextensionmanager'),
        1
    ));

    // Default documentation mode: none / optional / required. Can be overridden per quiz.
    $settings->add(new admin_setting_configselect(
        'local_quizextensionmanager/documentationmode',
        new lang_string('settings:documentationmode', 'local_quizextensionmanager'),
        new lang_string('settings:documentationmode_desc', 'local_quizextensionmanager'),
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
    //
    // Its output_html() (see lib/adminlib.php) calls $this->visiblename->out(),
    // so $visiblename *must* be a lang_string object here, not a plain string
    // -- passing get_string()'s return value (a plain string with no ->out()
    // method) throws "Call to a member function out() on string" the moment
    // this settings page is ever viewed. Confirmed against every real core
    // usage of admin_setting_filetypes (admin/settings/appearance.php,
    // mod/assign/submission/file/settings.php, media/player/videojs/settings.php),
    // which all pass new lang_string(...) for this exact reason.
    $settings->add(new admin_setting_filetypes(
        'local_quizextensionmanager/allowedfiletypes',
        new lang_string('settings:allowedfiletypes', 'local_quizextensionmanager'),
        new lang_string('settings:allowedfiletypes_desc', 'local_quizextensionmanager'),
        // Comma-separated to match MoodleQuickForm_filetypes::exportValue()'s
        // own canonical format (see the note in request.php) -- not a space,
        // even though this default predates the admin ever saving this page.
        'document,image'
    ));

    // Notification templates.
    // TODO: decide on and document the full set of supported placeholders (e.g. {$a->quizname}).
    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifyapprovedsubject',
        new lang_string('settings:notifyapprovedsubject', 'local_quizextensionmanager'),
        new lang_string('settings:notifyapprovedsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifyapprovedbody',
        new lang_string('settings:notifyapprovedbody', 'local_quizextensionmanager'),
        new lang_string('settings:notifyapprovedbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifydeniedsubject',
        new lang_string('settings:notifydeniedsubject', 'local_quizextensionmanager'),
        new lang_string('settings:notifydeniedsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifydeniedbody',
        new lang_string('settings:notifydeniedbody', 'local_quizextensionmanager'),
        new lang_string('settings:notifydeniedbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_quizextensionmanager/notifynewrequestsubject',
        new lang_string('settings:notifynewrequestsubject', 'local_quizextensionmanager'),
        new lang_string('settings:notifynewrequestsubject_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_quizextensionmanager/notifynewrequestbody',
        new lang_string('settings:notifynewrequestbody', 'local_quizextensionmanager'),
        new lang_string('settings:notifynewrequestbody_desc', 'local_quizextensionmanager'),
        '',
        PARAM_RAW
    ));
}
