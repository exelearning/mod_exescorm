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
 * Modal controller for the embedded eXeLearning editor.
 *
 * Creates a fullscreen overlay with an iframe loading the editor,
 * a save button and a close button. Handles postMessage communication
 * with the editor bridge running inside the iframe.
 *
 * @module      mod_exescorm/editor_modal
 * @copyright   2025 eXeLearning
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-console */

define(['core/str', 'core/log', 'core/prefetch'], function(Str, Log, Prefetch) {

    var overlay = null;
    var iframe = null;
    var saveBtn = null;
    var loadingModal = null;
    var isSaving = false;
    var hasUnsavedChanges = false;

    var getString = Str.get_string;

    /**
     * Prefetch language strings used by the modal.
     */
    var prefetchStrings = function() {
        Prefetch.prefetchStrings('mod_exescorm', [
            'editembedded', 'saving', 'savedsuccess', 'savetomoodle', 'savingwait', 'unsavedchanges',
        ]);
        Prefetch.prefetchStrings('core', ['closebuttontitle']);
    };

    var setSaveLabel = function(key, fallback) {
        if (!saveBtn) {
            return Promise.resolve();
        }
        var component = key === 'closebuttontitle' ? 'core' : 'mod_exescorm';
        return getString(key, component).then(function(text) {
            saveBtn.innerHTML = '<i class="fa fa-graduation-cap mr-1" aria-hidden="true"></i> ' + text;
        }).catch(function() {
            saveBtn.innerHTML = '<i class="fa fa-graduation-cap mr-1" aria-hidden="true"></i> ' + fallback;
        });
    };

    var createLoadingModal = function() {
        var modal = document.createElement('div');
        modal.className = 'exescorm-loading-modal';
        modal.id = 'exescorm-loading-modal';

        return Promise.all([
            getString('saving', 'mod_exescorm').catch(function() { return 'Saving...'; }),
            getString('savingwait', 'mod_exescorm').catch(function() {
                return 'Please wait while the file is being saved.';
            }),
        ]).then(function(strings) {
            modal.innerHTML =
                '<div class="exescorm-loading-modal__content">' +
                '<div class="exescorm-loading-modal__spinner"></div>' +
                '<h3 class="exescorm-loading-modal__title">' + strings[0] + '</h3>' +
                '<p class="exescorm-loading-modal__message">' + strings[1] + '</p>' +
                '</div>';
            document.body.appendChild(modal);
            return modal;
        });
    };

    var showLoadingModal = function() {
        if (loadingModal) {
            loadingModal.classList.add('is-visible');
            return Promise.resolve();
        }
        return createLoadingModal().then(function(modal) {
            loadingModal = modal;
            loadingModal.classList.add('is-visible');
        });
    };

    var hideLoadingModal = function() {
        if (loadingModal) {
            loadingModal.classList.remove('is-visible');
        }
    };

    var removeLoadingModal = function() {
        if (loadingModal) {
            loadingModal.remove();
            loadingModal = null;
        }
    };

    var checkUnsavedChanges = function() {
        if (!hasUnsavedChanges) {
            return Promise.resolve(false);
        }
        return getString('unsavedchanges', 'mod_exescorm').catch(function() {
            return 'You have unsaved changes. Are you sure you want to close?';
        }).then(function(message) {
            return !window.confirm(message);
        });
    };

    /**
     * Send a save request to the editor iframe.
     */
    var triggerSave = function() {
        if (isSaving || !iframe || !iframe.contentWindow) {
            return;
        }

        isSaving = true;
        if (saveBtn) {
            saveBtn.disabled = true;
        }
        setSaveLabel('saving', 'Saving...').then(function() {
            return showLoadingModal();
        }).then(function() {
            iframe.contentWindow.postMessage({
                source: 'exescorm-modal',
                type: 'save',
            }, '*');
        });
    };

    /**
     * Close the editor modal and clean up.
     * @param {boolean} skipConfirm
     */
    var closeModal = function(skipConfirm) {
        if (!overlay) {
            return Promise.resolve();
        }
        var doClose = function() {
            var wasShowingLoader = isSaving || (skipConfirm === true);

            overlay.remove();
            overlay = null;
            iframe = null;
            saveBtn = null;
            isSaving = false;
            hasUnsavedChanges = false;

            document.body.style.overflow = '';
            window.removeEventListener('message', handleMessage);
            document.removeEventListener('keydown', handleKeydown);

            if (wasShowingLoader) {
                setTimeout(function() {
                    hideLoadingModal();
                    removeLoadingModal();
                }, 1500);
            } else {
                hideLoadingModal();
                removeLoadingModal();
            }
        };

        if (!skipConfirm) {
            return checkUnsavedChanges().then(function(shouldCancel) {
                if (!shouldCancel) {
                    doClose();
                }
            });
        }
        doClose();
        return Promise.resolve();
    };

    /**
     * Handle postMessage events from the editor iframe.
     * @param {MessageEvent} event
     */
    var handleMessage = function(event) {
        if (!event.data) {
            return;
        }

        // Handle protocol messages (DOCUMENT_CHANGED, DOCUMENT_LOADED).
        if (event.data.type === 'DOCUMENT_CHANGED') {
            hasUnsavedChanges = true;
            return;
        }

        if (event.data.type === 'DOCUMENT_LOADED') {
            if (saveBtn && !isSaving) {
                saveBtn.disabled = false;
            }
            return;
        }

        // Handle bridge messages.
        if (event.data.source !== 'exescorm-editor') {
            return;
        }

        switch (event.data.type) {
            case 'save-complete':
                Log.debug('[editor_modal] Save complete, revision:', event.data.data.revision);
                hasUnsavedChanges = false;
                setSaveLabel('savedsuccess', 'Saved successfully');
                closeModal(true);
                setTimeout(function() {
                    window.location.reload();
                }, 400);
                break;

            case 'save-error':
                Log.error('[editor_modal] Save error:', event.data.data.error);
                isSaving = false;
                hideLoadingModal();
                if (saveBtn) {
                    saveBtn.disabled = false;
                }
                setSaveLabel('savetomoodle', 'Save to Moodle');
                break;

            case 'save-start':
                Log.debug('[editor_modal] Save started');
                break;

            case 'editor-ready':
                Log.debug('[editor_modal] Editor is ready');
                break;

            default:
                break;
        }
    };

    /**
     * Handle keydown events (Escape to close).
     * @param {KeyboardEvent} event
     */
    var handleKeydown = function(event) {
        if (event.key === 'Escape') {
            closeModal(false);
        }
    };

    /**
     * Open the embedded editor in a fullscreen modal overlay.
     *
     * @param {number} cmid Course module ID
     * @param {string} editorUrl URL of the editor bootstrap page
     * @param {string} activityName Activity name for the title bar
     */
    var openModal = function(cmid, editorUrl, activityName) {
        Log.debug('[editor_modal] Opening editor for cmid:', cmid);

        if (overlay) {
            Log.debug('[editor_modal] Modal already open');
            return;
        }

        // Create the overlay container.
        overlay = document.createElement('div');
        overlay.id = 'exescorm-editor-overlay';
        overlay.className = 'exescorm-editor-overlay';

        // Build the header bar.
        var header = document.createElement('div');
        header.className = 'exescorm-editor-header';

        var title = document.createElement('span');
        title.className = 'exescorm-editor-title';
        title.textContent = activityName || '';
        header.appendChild(title);

        var buttonGroup = document.createElement('div');
        buttonGroup.className = 'exescorm-editor-buttons';

        // Save button.
        saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-primary mr-2';
        saveBtn.id = 'exescorm-editor-save';
        saveBtn.disabled = true;
        setSaveLabel('savetomoodle', 'Save to Moodle');
        saveBtn.addEventListener('click', triggerSave);

        // Close button.
        var closeBtn = document.createElement('button');
        closeBtn.className = 'btn btn-secondary';
        closeBtn.id = 'exescorm-editor-close';
        getString('closebuttontitle', 'core').then(function(text) {
            closeBtn.textContent = text;
        }).catch(function() {
            closeBtn.textContent = 'Close';
        });
        closeBtn.addEventListener('click', function() {
            closeModal(false);
        });

        buttonGroup.appendChild(saveBtn);
        buttonGroup.appendChild(closeBtn);
        header.appendChild(buttonGroup);
        overlay.appendChild(header);

        // Create the iframe.
        iframe = document.createElement('iframe');
        iframe.className = 'exescorm-editor-iframe';
        iframe.src = editorUrl;
        iframe.setAttribute('allow', 'fullscreen');
        iframe.setAttribute('frameborder', '0');
        overlay.appendChild(iframe);

        // Append to body.
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Listen for messages from the editor iframe.
        window.addEventListener('message', handleMessage);

        // Listen for Escape key.
        document.addEventListener('keydown', handleKeydown);
    };

    return {
        init: function() {
            prefetchStrings();

            // Delegate click events for embedded editor buttons.
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-action="mod_exescorm/editor-open"]');
                if (btn) {
                    e.preventDefault();
                    var cmid = btn.dataset.cmid;
                    var editorUrl = btn.dataset.editorurl;
                    var name = btn.dataset.activityname;
                    openModal(cmid, editorUrl, name);
                }
            });
        },
        open: openModal,
        close: closeModal,
    };
});
