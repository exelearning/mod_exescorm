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

/**
 * Prefetch language strings used by the modal.
 */
const prefetchStrings = () => {
    Prefetch.prefetchStrings('mod_exescorm', ['editembedded', 'saving', 'savedsuccess', 'savetomoodle']);
    Prefetch.prefetchStrings('core', ['close']);
};

/**
 * Open the embedded editor in a fullscreen modal overlay.
 *
 * @param {number} cmid Course module ID
 * @param {string} editorUrl URL of the editor bootstrap page
 * @param {string} activityName Activity name for the title bar
 */
export const open = (cmid, editorUrl, activityName) => {
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
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn btn-primary mr-2';
    saveBtn.id = 'exescorm-editor-save';
    getString('savetomoodle', 'mod_exescorm').then((label) => {
        saveBtn.textContent = label;
        return;
    }).catch();

    saveBtn.addEventListener('click', () => {
        triggerSave(saveBtn);
    });

    // Close button.
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-secondary';
    closeBtn.id = 'exescorm-editor-close';
    getString('close', 'core').then((label) => {
        closeBtn.textContent = label;
        return;
    }).catch();

    closeBtn.addEventListener('click', () => {
        close();
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

/**
 * Send a save request to the editor iframe.
 * @param {HTMLElement} saveBtn
 */
const triggerSave = (saveBtn) => {
    if (!iframe || !iframe.contentWindow) {
        return;
    }
    saveBtn.disabled = true;
    getString('saving', 'mod_exescorm').then((label) => {
        saveBtn.textContent = label;
        return;
    }).catch();

    iframe.contentWindow.postMessage({
        source: 'exescorm-modal',
        type: 'save',
    }, '*');
};

/**
 * Handle postMessage events from the editor iframe.
 * @param {MessageEvent} event
 */
const handleMessage = (event) => {
    if (!event.data || event.data.source !== 'exescorm-editor') {
        return;
    }

    const saveBtn = document.getElementById('exescorm-editor-save');

    switch (event.data.type) {
        case 'save-complete':
            Log.debug('[editor_modal] Save complete, revision:', event.data.data.revision);
            if (saveBtn) {
                getString('savedsuccess', 'mod_exescorm').then((label) => {
                    saveBtn.textContent = label;
                    saveBtn.disabled = false;
                    // Reload the page after a short delay to show updated content.
                    setTimeout(() => {
                        close();
                        window.location.reload();
                    }, 1000);
                    return;
                }).catch();
            }
            break;

        case 'save-error':
            Log.error('[editor_modal] Save error:', event.data.data.error);
            if (saveBtn) {
                getString('savetomoodle', 'mod_exescorm').then((label) => {
                    saveBtn.textContent = label;
                    saveBtn.disabled = false;
                    return;
                }).catch();
            }
            break;

        case 'save-start':
            Log.debug('[editor_modal] Save started');
            break;

        case 'editor-ready':
            Log.debug('[editor_modal] Editor is ready');
            break;
    }
};

/**
 * Handle keydown events (Escape to close).
 * @param {KeyboardEvent} event
 */
const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        close();
    }
};

/**
 * Close the editor modal and clean up.
 */
export const close = () => {
    if (overlay) {
        overlay.remove();
        overlay = null;
        iframe = null;
        document.body.style.overflow = '';
        window.removeEventListener('message', handleMessage);
        document.removeEventListener('keydown', handleKeydown);
    }
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
