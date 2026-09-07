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
 * English language strings for local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Quiz extension manager';

// Capabilities.
$string['quizextensionmanager:request'] = 'Request a quiz time extension';
$string['quizextensionmanager:manage'] = 'Approve, deny and manage quiz time extension requests';

// Admin settings (site-wide, see settings.php).
$string['settings:maxextension'] = 'Maximum extension allowed';
$string['settings:maxextension_desc'] = 'The maximum extra time (in minutes) that can be granted for a ' .
    'single time extension request. Set to 0 for no limit.';
$string['settings:reasonrequired'] = 'Require a reason';
$string['settings:reasonrequired_desc'] = 'Default setting for whether students must supply a reason when ' .
    'submitting a request. Can be overridden per quiz.';
$string['settings:documentationmode'] = 'Documentation';
$string['settings:documentationmode_desc'] = 'Default setting for whether supporting documentation may or ' .
    'must be uploaded with a request. Can be overridden per quiz.';
$string['documentationmode:none'] = 'Not used';
$string['documentationmode:optional'] = 'Optional';
$string['documentationmode:required'] = 'Required';
$string['settings:allowedfiletypes'] = 'Allowed documentation file types';
$string['settings:allowedfiletypes_desc'] = 'Default list of file types students may upload as supporting ' .
    'documentation. Can be overridden per quiz.';
$string['settings:notifyapprovedsubject'] = 'Approval notification subject';
$string['settings:notifyapprovedsubject_desc'] = 'Subject line template used when notifying a student that ' .
    'their request was approved. Leave blank to use the built-in default. Supports the placeholders ' .
    '{$a->quizname}, {$a->coursename}, {$a->requestedtimeclose}, {$a->grantedtimeclose}, ' .
    '{$a->requestedtimelimit}, {$a->grantedtimelimit}, {$a->requestedattempts}, {$a->grantedattempts} and ' .
    '{$a->reviewreason}, substituted literally (not via get_string).';
$string['settings:notifyapprovedbody'] = 'Approval notification body';
$string['settings:notifyapprovedbody_desc'] = 'Message body template used when notifying a student that ' .
    'their request was approved. Leave blank to use the built-in default. Supports the same placeholders ' .
    'as the approval notification subject.';
$string['settings:notifydeniedsubject'] = 'Denial notification subject';
$string['settings:notifydeniedsubject_desc'] = 'Subject line template used when notifying a student that ' .
    'their request was denied. Leave blank to use the built-in default. Supports the placeholders ' .
    '{$a->quizname}, {$a->coursename} and {$a->reviewreason}, substituted literally (not via get_string).';
$string['settings:notifydeniedbody'] = 'Denial notification body';
$string['settings:notifydeniedbody_desc'] = 'Message body template used when notifying a student that ' .
    'their request was denied. Leave blank to use the built-in default. Supports the same placeholders ' .
    'as the denial notification subject.';

// Privacy.
$string['privacy:metadata:quizextensionmanager_request'] = 'Information about a single quiz time extension ' .
    'request.';
$string['privacy:metadata:quizextensionmanager_request:quizid'] = 'The ID of the quiz the request relates to.';
$string['privacy:metadata:quizextensionmanager_request:courseid'] = 'The ID of the course the quiz belongs to.';
$string['privacy:metadata:quizextensionmanager_request:userid'] = 'The ID of the student who made the request.';
$string['privacy:metadata:quizextensionmanager_request:requestedtimeclose'] = 'The new close date/time ' .
    'requested by the student.';
$string['privacy:metadata:quizextensionmanager_request:requestedtimelimit'] = 'The new time limit requested ' .
    'by the student.';
$string['privacy:metadata:quizextensionmanager_request:requestedattempts'] = 'The new number of attempts ' .
    'requested by the student.';
$string['privacy:metadata:quizextensionmanager_request:currenttimeclose'] = 'A snapshot of the quiz close ' .
    'date/time at the time of the request.';
$string['privacy:metadata:quizextensionmanager_request:currenttimelimit'] = 'A snapshot of the quiz time ' .
    'limit at the time of the request.';
$string['privacy:metadata:quizextensionmanager_request:currentattempts'] = 'A snapshot of the quiz attempts ' .
    'allowed at the time of the request.';
$string['privacy:metadata:quizextensionmanager_request:reason'] = 'The reason given by the student for the request.';
$string['privacy:metadata:quizextensionmanager_request:status'] = 'The status of the request (pending, ' .
    'approved, denied or cancelled).';
$string['privacy:metadata:quizextensionmanager_request:grantedtimeclose'] = 'The close date/time actually ' .
    'granted to the student.';
$string['privacy:metadata:quizextensionmanager_request:grantedtimelimit'] = 'The time limit actually ' .
    'granted to the student.';
$string['privacy:metadata:quizextensionmanager_request:grantedattempts'] = 'The number of attempts actually ' .
    'granted to the student.';
$string['privacy:metadata:quizextensionmanager_request:quizoverrideid'] = 'The ID of the quiz user override ' .
    'created or updated as a result of this request.';
$string['privacy:metadata:quizextensionmanager_request:reviewerid'] = 'The ID of the teacher who reviewed the request.';
$string['privacy:metadata:quizextensionmanager_request:reviewreason'] = 'The comment left by the reviewer ' .
    'when approving or denying the request.';
$string['privacy:metadata:quizextensionmanager_request:timecreated'] = 'The time the request was created.';
$string['privacy:metadata:quizextensionmanager_request:timereviewed'] = 'The time the request was approved or denied.';
$string['privacy:metadata:quizextensionmanager_request:timemodified'] = 'The time the request was last modified.';
$string['privacy:metadata:quizextensionmanager_documentation'] = 'Supporting documentation files uploaded ' .
    'by the student with their request.';

// Message providers.
$string['messageprovider:extensionapproved'] = 'Quiz time extension request approved';
$string['messageprovider:extensiondenied'] = 'Quiz time extension request denied';

// Pages.
$string['page:newrequest'] = 'Request a quiz time extension';
$string['page:editrequest'] = 'Edit your quiz time extension request';
$string['page:myrequests'] = 'My extension requests';
$string['page:manage'] = 'Extension requests';
$string['page:report'] = 'Extension request report';
$string['page:quizsettings'] = 'Extension request settings';
$string['page:coursesettings'] = 'Extension request course settings';

// Request form.
$string['form:currentvalues'] = 'Current quiz settings';
$string['form:requestedvalues'] = 'Your request';
$string['form:currenttimeclose'] = 'Current close date';
$string['form:currenttimelimit'] = 'Current time limit';
$string['form:currentattempts'] = 'Current attempts allowed';
$string['form:requestedtimeclose'] = 'Requested new close date';
$string['form:requestedtimelimit'] = 'Requested new time limit';
$string['form:needsadditionalattempt'] = 'I need an additional attempt at this quiz';
$string['form:reason'] = 'Reason';
$string['form:documentation'] = 'Supporting documentation';
$string['form:submit'] = 'Submit request';
$string['form:student'] = 'Student';
$string['form:grantedtimeclose'] = 'Granted close date';
$string['form:grantedtimelimit'] = 'Granted time limit';
$string['form:grantedattempts'] = 'Granted number of attempts';
$string['form:reviewreason'] = 'Comment (optional)';

// Quiz settings form.
$string['quizsettings:enabled'] = 'Allow extension requests for this quiz';
$string['quizsettings:requirereason'] = 'Require a reason';
$string['quizsettings:requestwindowtype'] = 'Request window';
$string['quizsettings:requestwindowdays'] = 'Days after close date';
$string['quizsettings:requestwindowdays_help'] = 'Number of days after the quiz close date during which ' .
    'requests are still allowed. Leave blank to use the course default.';
$string['quizsettings:requestwindowdate'] = 'Request window closes on';
$string['requestwindowtype:daysafterclose'] = 'Number of days after the close date';
$string['requestwindowtype:fixeddate'] = 'A fixed date';
$string['requestwindowtype:untilcourseend'] = 'Until the end of the course';

// Course settings form.
$string['coursesettings:maxrequestsperstudent'] = 'Maximum requests per student';
$string['coursesettings:maxrequestsperstudent_help'] = 'The maximum number of approved extension requests a ' .
    'student may have across this course. Denied and cancelled requests do not count against this limit. ' .
    'Leave blank for unlimited.';
$string['coursesettings:defaultrequestwindowdays'] = 'Default request window (days after close)';
$string['coursesettings:defaultrequestwindowdays_help'] = 'Default number of days after a quiz\'s close date ' .
    'during which requests are allowed, used when a quiz does not specify its own value and its request ' .
    'window type is "Number of days after the close date".';

// Actions.
$string['action:approve'] = 'Approve';
$string['action:deny'] = 'Deny';
$string['action:edit'] = 'Edit';
$string['action:cancel'] = 'Cancel';
$string['action:newrequest'] = 'New request';

// Modal titles (AJAX approve/deny, see PLUGIN_SPEC.md v0.4).
$string['modal:approvetitle'] = 'Approve extension request';
$string['modal:denytitle'] = 'Deny extension request';

// Tables.
$string['table:quiz'] = 'Quiz';
$string['table:student'] = 'Student';
$string['table:status'] = 'Status';
$string['table:requestedtimeclose'] = 'Requested close date';
$string['table:requestedtimelimit'] = 'Requested time limit';
$string['table:requestedattempts'] = 'Requested attempts';
$string['table:grantedtimeclose'] = 'Granted close date';
$string['table:reason'] = 'Reason';
$string['table:timecreated'] = 'Submitted';
$string['table:reviewer'] = 'Reviewer';
$string['table:actions'] = 'Actions';
$string['table:view'] = 'View';

// Statuses.
$string['status:all'] = 'All';
$string['status:pending'] = 'Pending';
$string['status:approved'] = 'Approved';
$string['status:denied'] = 'Denied';
$string['status:cancelled'] = 'Cancelled';

// Entry-point links (quiz view page / my requests).
$string['link:requestextension'] = 'Request extension';
$string['link:pendingrequest'] = 'You have a pending extension request';
$string['link:viewrequests'] = 'View your extension requests';

// mod_quiz user override reason (shown in Moodle's own "quiz overrides" listing).
$string['override:reason'] = 'Granted via Quiz extension manager request #{$a}.';

// Notices / confirmations.
$string['notify:requestsaved'] = 'Your extension request has been saved.';
$string['notify:requestcancelled'] = 'Your extension request has been cancelled.';
$string['notify:approved'] = 'The request has been approved.';
$string['notify:denied'] = 'The request has been denied.';
$string['notify:settingssaved'] = 'Settings saved.';
$string['confirm:cancelrequest'] = 'Are you sure you want to cancel this extension request?';
$string['norequests'] = 'You have not made any extension requests.';
$string['nopendingrequests'] = 'There are no pending extension requests for this quiz.';
$string['quota:used'] = '{$a->used} of {$a->max} extension requests used.';

// Misc display.
$string['unlimited'] = 'unlimited';
$string['unchanged'] = 'unchanged';
$string['noclosedate'] = 'No close date';
$string['nolimit'] = 'No time limit';

// Notification templates (defaults used when the corresponding site setting is blank).
$string['notify:approvedsubject:default'] = 'Your extension request for {$a->quizname} has been approved';
$string['notify:approvedbody:default'] = 'Your request for a time extension on "{$a->quizname}" in ' .
    '{$a->coursename} has been approved.' . "\n\n" .
    'Requested close date: {$a->requestedtimeclose}' . "\n" .
    'Granted close date: {$a->grantedtimeclose}' . "\n" .
    'Requested time limit: {$a->requestedtimelimit}' . "\n" .
    'Granted time limit: {$a->grantedtimelimit}' . "\n" .
    'Requested attempts: {$a->requestedattempts}' . "\n" .
    'Granted attempts: {$a->grantedattempts}' . "\n\n" .
    'Reviewer comment: {$a->reviewreason}';
$string['notify:deniedsubject:default'] = 'Your extension request for {$a->quizname} has been denied';
$string['notify:deniedbody:default'] = 'Your request for a time extension on "{$a->quizname}" in ' .
    '{$a->coursename} has been denied.' . "\n\n" .
    'Reviewer comment: {$a->reviewreason}';

// Eligibility rejection reasons.
$string['eligibility:noquiz'] = 'This quiz could not be found.';
$string['eligibility:disabled'] = 'Extension requests are not enabled for this quiz.';
$string['eligibility:notopenyet'] = 'This quiz is not open yet.';
$string['eligibility:windowclosed'] = 'The window for requesting an extension for this quiz has closed.';
$string['eligibility:pendingexists'] = 'You already have a pending extension request for this quiz.';
$string['eligibility:quotaexceeded'] = 'You have used all of your available extension requests for this course.';

// Errors.
$string['error:notowner'] = 'You do not have permission to modify this request.';
$string['error:notpending'] = 'This request can no longer be edited, cancelled or reviewed because it is not pending.';
$string['error:nochange'] = 'Please request a change to at least one of the close date, time limit or attempts.';
$string['error:invalidnumber'] = 'Please enter a valid non-negative whole number.';
