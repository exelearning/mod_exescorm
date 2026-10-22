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
define(['core/str', 'core/modal_events', 'core/log', 'require'], function(Str, ModalEvents, Log, require) {

    var modalPromise = null;
    var modalIsOpen = false;
    var currentTargetUrl = null;
    var initialized = false;

    /**
     * Initialize the edit confirmation functionality.
     */
    var init = function() {
        if (initialized) {
            return;
        }
        initialized = true;

        var editButtons = document.querySelectorAll('[data-action="edit-exescorm"]');

        editButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var targetUrl = button.getAttribute('data-editurl');
                showConfirmation(targetUrl).catch(function(error) {
                    Log.error('Failed to show the eXeLearning edit confirmation.', error);
                });
            });
        });
    };

    /**
     * Load an AMD module dynamically.
     *
     * @param {string} moduleName The AMD module name
     * @returns {Promise}
     */
    var loadModule = function(moduleName) {
        return new Promise(function(resolve, reject) {
            require([moduleName], resolve, reject);
        });
    };

    /**
     * Create a save/cancel modal using the API available in this Moodle version.
     *
     * @param {Array} strings The localized modal strings
     * @param {string} bodyContent The modal body
     * @returns {Promise}
     */
    var createModal = function(strings, bodyContent) {
        if (window.M && window.M.cfg && Number(window.M.cfg.version) >= 2023100900) {
            return loadModule('core/modal_save_cancel').then(function(ModalSaveCancel) {
                return ModalSaveCancel.create({
                    title: strings[0],
                    body: bodyContent,
                    buttons: {
                        save: strings[1],
                        cancel: strings[2],
                    },
                    removeOnClose: true,
                });
            });
        }

        return loadModule('core/modal_factory').then(function(ModalFactory) {
            return ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: strings[0],
                body: bodyContent,
                buttons: {
                    save: strings[1],
                    cancel: strings[2],
                },
            });
        });
    };

    /**
     * Show confirmation modal before redirecting to edit.
     *
     * @param {string} targetUrl The URL to redirect to if confirmed
     */
    var showConfirmation = function(targetUrl) {
        if (modalIsOpen) {
            return Promise.resolve();
        }

        currentTargetUrl = targetUrl;
        modalIsOpen = true;

        modalPromise = Promise.all([
            Str.get_string('edit', 'core'),
            Str.get_string('yes', 'core'),
            Str.get_string('cancel', 'core'),
            Str.get_string('editdialogcontent', 'mod_exescorm'),
            Str.get_string('editdialogcontent:caution', 'mod_exescorm'),
            Str.get_string('editdialogcontent:continue', 'mod_exescorm'),
        ]).then(function(strings) {
            var warnIcon = '<i class="fa fa-circle-exclamation text-danger"></i> ';
            var bodyContent = strings[3] + '<br><br><strong>' + warnIcon + strings[4] +
                '</strong><br><br><div class="text-center">' + strings[5] + '</div>';
            return createModal(strings, bodyContent);
        }).then(function(modal) {
            modal.getRoot().on(ModalEvents.save, function() {
                window.location.href = currentTargetUrl;
            });

            modal.getRoot().on(ModalEvents.hidden, function() {
                modalIsOpen = false;
                currentTargetUrl = null;
            });

            modalPromise = null;
            modal.show();
        }).catch(function(error) {
            modalPromise = null;
            modalIsOpen = false;
            currentTargetUrl = null;
            throw error;
        });

        return modalPromise;
    };

    return {
        init: init,
        showConfirmation: showConfirmation,
    };
});
