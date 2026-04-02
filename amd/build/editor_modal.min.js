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

import {get_string as getString} from 'core/str';
import Log from 'core/log';
import Prefetch from 'core/prefetch';

let overlay = null;
let iframe = null;
let saveBtn = null;
let loadingModal = null;
let isSaving = false;
let hasUnsavedChanges = false;

/**
 * Prefetch language strings used by the modal.
 */
const prefetchStrings = () => {
    Prefetch.prefetchStrings('mod_exescorm', [
        'editembedded', 'saving', 'savedsuccess', 'savetomoodle', 'savingwait', 'unsavedchanges',
    ]);
    Prefetch.prefetchStrings('core', ['closebuttontitle']);
};

const setSaveLabel = async(key, fallback) => {
    if (!saveBtn) {
        return;
    }
    try {
        const component = key === 'closebuttontitle' ? 'core' : 'mod_exescorm';
        const text = await getString(key, component);
        saveBtn.innerHTML = '<i class="fa fa-graduation-cap mr-1" aria-hidden="true"></i> ' + text;
    } catch {
        saveBtn.innerHTML = '<i class="fa fa-graduation-cap mr-1" aria-hidden="true"></i> ' + fallback;
    }
};

const createLoadingModal = async() => {
    const modal = document.createElement('div');
    modal.className = 'exescorm-loading-modal';
    modal.id = 'exescorm-loading-modal';

    let savingText = 'Saving...';
    let waitText = 'Please wait while the file is being saved.';
    try {
        savingText = await getString('saving', 'mod_exescorm');
        waitText = await getString('savingwait', 'mod_exescorm');
    } catch {
        // Use defaults.
    }

    modal.innerHTML = `
        <div class="exescorm-loading-modal__content">
            <div class="exescorm-loading-modal__spinner"></div>
            <h3 class="exescorm-loading-modal__title">${savingText}</h3>
            <p class="exescorm-loading-modal__message">${waitText}</p>
        </div>
    `;

    document.body.appendChild(modal);
    return modal;
};

const showLoadingModal = async() => {
    if (!loadingModal) {
        loadingModal = await createLoadingModal();
    }
    loadingModal.classList.add('is-visible');
};

const hideLoadingModal = () => {
    if (loadingModal) {
        loadingModal.classList.remove('is-visible');
    }
};

const removeLoadingModal = () => {
    if (loadingModal) {
        loadingModal.remove();
        loadingModal = null;
    }
};

const checkUnsavedChanges = async() => {
    if (!hasUnsavedChanges) {
        return false;
    }
    let message = 'You have unsaved changes. Are you sure you want to close?';
    try {
        message = await getString('unsavedchanges', 'mod_exescorm');
    } catch {
        // Use default.
    }
    return !window.confirm(message);
};

/**
 * Send a save request to the editor iframe.
 */
const triggerSave = async() => {
    if (isSaving || !iframe || !iframe.contentWindow) {
        return;
    }

    isSaving = true;
    if (saveBtn) {
        saveBtn.disabled = true;
    }
    await setSaveLabel('saving', 'Saving...');
    await showLoadingModal();

    iframe.contentWindow.postMessage({
        source: 'exescorm-modal',
        type: 'save',
    }, '*');
};

/**
 * Handle postMessage events from the editor iframe.
 * @param {MessageEvent} event
 */
const handleMessage = async(event) => {
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
            await setSaveLabel('savedsuccess', 'Saved successfully');
            close(true);
            setTimeout(() => {
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
            await setSaveLabel('savetomoodle', 'Save to Moodle');
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
const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        close(false);
    }
};

/**
 * Close the editor modal and clean up.
 * @param {boolean} skipConfirm
 */
export const close = async(skipConfirm) => {
    if (!overlay) {
        return;
    }
    if (!skipConfirm) {
        const shouldCancel = await checkUnsavedChanges();
        if (shouldCancel) {
            return;
        }
    }

    const wasShowingLoader = isSaving || (skipConfirm === true);

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
        setTimeout(() => {
            hideLoadingModal();
            removeLoadingModal();
        }, 1500);
    } else {
        hideLoadingModal();
        removeLoadingModal();
    }
};

/**
 * Open the embedded editor in a fullscreen modal overlay.
 *
 * @param {number} cmid Course module ID
 * @param {string} editorUrl URL of the editor bootstrap page
 * @param {string} activityName Activity name for the title bar
 */
export const open = async(cmid, editorUrl, activityName) => {
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
    const header = document.createElement('div');
    header.className = 'exescorm-editor-header';

    const title = document.createElement('span');
    title.className = 'exescorm-editor-title';
    title.textContent = activityName || '';
    header.appendChild(title);

    const buttonGroup = document.createElement('div');
    buttonGroup.className = 'exescorm-editor-buttons';

    // Save button.
    saveBtn = document.createElement('button');
    saveBtn.className = 'btn btn-primary mr-2';
    saveBtn.id = 'exescorm-editor-save';
    saveBtn.disabled = true;
    await setSaveLabel('savetomoodle', 'Save to Moodle');
    saveBtn.addEventListener('click', triggerSave);

    // Close button.
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-secondary';
    closeBtn.id = 'exescorm-editor-close';
    try {
        closeBtn.textContent = await getString('closebuttontitle', 'core');
    } catch {
        closeBtn.textContent = 'Close';
    }
    closeBtn.addEventListener('click', () => close(false));

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

/**
 * Initialize the module by setting up click handlers for editor buttons.
 */
export const init = () => {
    prefetchStrings();

    // Delegate click events for embedded editor buttons.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="mod_exescorm/editor-open"]');
        if (btn) {
            e.preventDefault();
            const cmid = btn.dataset.cmid;
            const editorUrl = btn.dataset.editorurl;
            const name = btn.dataset.activityname;
            open(cmid, editorUrl, name);
        }
    });
};
