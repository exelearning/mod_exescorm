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
 * Confirmation modal for editing ExeScorm activities.
 *
 * @module mod_exescorm/edit_confirmation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */
import * as Str from 'core/str';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';


/**
 * Initialize the edit confirmation functionality.
 */
export const init = () => {
    const editButtons = document.querySelectorAll('[data-action="edit-exescorm"]');

    editButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const targetUrl = button.getAttribute('data-editurl');
            showConfirmation(targetUrl);
        });
    });
};

/**
 * Show confirmation modal before redirecting to edit.
 *
 * @param {string} targetUrl The URL to redirect to if confirmed
 */
export const showConfirmation = (targetUrl) => {
    Promise.all([
        Str.get_string('edit', 'core'),
        Str.get_string('yes', 'core'),
        Str.get_string('cancel', 'core'),
        Str.get_string('editdialogcontent', 'mod_exescorm'),
        Str.get_string('editdialogcontent:caution', 'mod_exescorm'),
        Str.get_string('editdialogcontent:continue', 'mod_exescorm'),
    ]).then((strings) => {
        // Center the body content using a div with text-center class.
        const warnIcon = '<i class="fa fa-circle-exclamation text-danger"></i> ';
        const bodyContent = strings[3] + '<br><br><strong>' + warnIcon + strings[4] +
            '</strong><br><br>' + '<div class="text-center">' + strings[5] +
            '</div>';
        return ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: strings[0],
            body: bodyContent,
            buttons: {
                save: strings[1],
                cancel: strings[2]
            }
        });
    }).then((modal) => {
        modal.getRoot().on(ModalEvents.save, () => {
            window.location.href = targetUrl;
        });

        modal.getRoot().on(ModalEvents.cancel, () => {
            modal.hide();
        });

        modal.show();
        return modal;
    }).catch(() => {
        window.location.href = targetUrl;
    });
};
