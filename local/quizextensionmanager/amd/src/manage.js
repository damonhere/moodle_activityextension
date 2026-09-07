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
 * Approve/deny a pending extension request from the teacher dashboard via an
 * AJAX modal, without a full page reload. See PLUGIN_SPEC.md v0.4.
 *
 * @module     local_quizextensionmanager/manage
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';
import Notification from 'core/notification';

const SELECTORS = {
    APPROVE: '[data-action="approve-request"]',
    DENY: '[data-action="deny-request"]',
    TABLE: '#local-quizextensionmanager-pending-table',
    EMPTY: '#local-quizextensionmanager-empty',
};

const COMPONENT = 'local_quizextensionmanager';

let listenersRegistered = false;

/**
 * Remove a row from the pending-requests table after it has been actioned,
 * and reveal the "no pending requests" message if none remain.
 *
 * @param {String} requestid
 */
const removeRow = (requestid) => {
    const table = document.querySelector(SELECTORS.TABLE);
    if (!table) {
        return;
    }

    const row = table.querySelector(`[data-requestid="${requestid}"]`);
    if (row) {
        row.remove();
    }

    if (!table.querySelector('tbody tr')) {
        table.hidden = true;
        const empty = document.querySelector(SELECTORS.EMPTY);
        if (empty) {
            empty.hidden = false;
        }
    }
};

/**
 * Show the approve or deny form in a modal, and remove the row on success.
 *
 * @param {Object} config
 * @param {String} config.formClass fully-qualified PHP dynamic_form class name
 * @param {String} config.requestid
 * @param {String} config.titleKey lang string key (this component) for the modal title
 * @param {String} config.saveKey lang string key (this component) for the modal's save button
 * @param {String} config.successKey lang string key (this component) for the success toast
 */
const showActionModal = ({formClass, requestid, titleKey, saveKey, successKey}) => {
    Promise.all([
        getString(titleKey, COMPONENT),
        getString(saveKey, COMPONENT),
    ]).then(([title, saveButtonText]) => {
        const modalForm = new ModalForm({
            formClass,
            args: {requestid},
            modalConfig: {title},
            saveButtonText,
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
            removeRow(requestid);
            addToast(getString(successKey, COMPONENT), {type: 'success'});
        });

        modalForm.show();

        return modalForm;
    }).catch(Notification.exception);
};

/**
 * Attach a single delegated click listener for the Approve/Deny buttons
 * rendered in the pending-requests table.
 */
const registerListeners = () => {
    document.addEventListener('click', (e) => {
        const approveButton = e.target.closest(SELECTORS.APPROVE);
        if (approveButton) {
            e.preventDefault();
            showActionModal({
                formClass: 'local_quizextensionmanager\\form\\approve_form',
                requestid: approveButton.dataset.requestid,
                titleKey: 'modal:approvetitle',
                saveKey: 'action:approve',
                successKey: 'notify:approved',
            });
            return;
        }

        const denyButton = e.target.closest(SELECTORS.DENY);
        if (denyButton) {
            e.preventDefault();
            showActionModal({
                formClass: 'local_quizextensionmanager\\form\\deny_form',
                requestid: denyButton.dataset.requestid,
                titleKey: 'modal:denytitle',
                saveKey: 'action:deny',
                successKey: 'notify:denied',
            });
        }
    });
};

/**
 * Initialise the pending-requests dashboard's AJAX approve/deny behaviour.
 */
export const init = () => {
    if (!listenersRegistered) {
        registerListeners();
        listenersRegistered = true;
    }
};
