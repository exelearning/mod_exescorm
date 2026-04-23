/**
 * Bridge between embedded eXeLearning and Moodle save endpoint.
 *
 * This script does not access editor internals. It talks to eXe exclusively
 * through EmbeddingBridge postMessage protocol (OPEN_FILE / REQUEST_EXPORT).
 *
 * @module      mod_exescorm/moodle_exe_bridge
 * @copyright   2025 eXeLearning
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-console */

(function() {
    'use strict';

    var config = window.__MOODLE_EXE_CONFIG__;
    if (!config) {
        console.error('[moodle-exe-bridge] Missing __MOODLE_EXE_CONFIG__');
        return;
    }

    var embeddingConfig = window.__EXE_EMBEDDING_CONFIG__ || {};
    var hasInitialProjectUrl = !!embeddingConfig.initialProjectUrl;

    var editorWindow = window;
    var parentWindow = window.parent && window.parent !== window ? window.parent : null;
    var state = {
        ready: false,
        importing: false,
        imported: hasInitialProjectUrl,
        saving: false,
    };

    var monitoredYdoc = null;
    var changeNotified = false;

    var pendingRequests = Object.create(null);

    function createRequestId(prefix) {
        return (prefix || 'req') + '-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
    }

    function updateLoadScreen(message, visible) {
        if (visible === undefined) {
            visible = true;
        }

        var loadScreen = document.getElementById('load-screen-main');
        if (!loadScreen) {
            return;
        }

        var loadMessage = loadScreen.querySelector('.loading-message, p');
        if (loadMessage && message) {
            loadMessage.textContent = message;
        }

        if (visible) {
            loadScreen.classList.remove('hide');
        } else {
            loadScreen.classList.add('hide');
        }
    }

    function notifyParent(type, data) {
        if (!parentWindow) {
            return;
        }

        parentWindow.postMessage({
            source: 'exescorm-editor',
            type: type,
            data: data || {},
        }, '*');
    }

    function postProtocolMessage(message) {
        if (!parentWindow) {
            return;
        }
        parentWindow.postMessage(message, '*');
    }

    function monitorDocumentChanges() {
        try {
            var app = window.eXeLearning && window.eXeLearning.app;
            var ydoc = app && app.project && app.project._yjsBridge
                && app.project._yjsBridge.documentManager && app.project._yjsBridge.documentManager.ydoc;
            if (!ydoc || typeof ydoc.on !== 'function') {
                return;
            }
            if (ydoc === monitoredYdoc) {
                return;
            }
            monitoredYdoc = ydoc;
            changeNotified = false;
            ydoc.on('update', function() {
                if (!changeNotified) {
                    changeNotified = true;
                    postProtocolMessage({type: 'DOCUMENT_CHANGED'});
                }
            });
        } catch (error) {
            console.warn('[moodle-exe-bridge] Change monitor failed:', error);
        }
    }

    async function notifyWhenDocumentLoaded() {
        try {
            var timeout = 30000;
            var start = Date.now();
            while (Date.now() - start < timeout) {
                var app = window.eXeLearning && window.eXeLearning.app;
                var manager = app && app.project && app.project._yjsBridge
                    && app.project._yjsBridge.documentManager;
                if (manager) {
                    postProtocolMessage({type: 'DOCUMENT_LOADED'});
                    monitorDocumentChanges();
                    return;
                }
                await new Promise(function(resolve) {
                    setTimeout(resolve, 150);
                });
            }
        } catch (error) {
            console.warn('[moodle-exe-bridge] DOCUMENT_LOADED monitor failed:', error);
        }
    }

    // Re-attach ydoc monitor when the parent sends messages that may replace the document.
    window.addEventListener('message', function() {
        setTimeout(monitorDocumentChanges, 500);
    });

    function postToEditor(type, data, transfer, timeoutMs) {
        if (!type) {
            return Promise.reject(new Error('Missing message type'));
        }

        var requestId = createRequestId(type.toLowerCase());

        return new Promise(function(resolve, reject) {
            var timer = setTimeout(function() {
                delete pendingRequests[requestId];
                reject(new Error(type + ' timed out'));
            }, timeoutMs || 30000);

            pendingRequests[requestId] = {
                resolve: resolve,
                reject: reject,
                timer: timer,
                requestType: type,
            };

            try {
                if (transfer && transfer.length) {
                    editorWindow.postMessage({type: type, requestId: requestId, data: data || {}}, window.location.origin, transfer);
                } else {
                    editorWindow.postMessage({type: type, requestId: requestId, data: data || {}}, window.location.origin);
                }
            } catch (error) {
                clearTimeout(timer);
                delete pendingRequests[requestId];
                reject(error);
            }
        });
    }

    function settleRequest(requestId, error, payload) {
        var pending = pendingRequests[requestId];
        if (!pending) {
            return false;
        }

        clearTimeout(pending.timer);
        delete pendingRequests[requestId];

        if (error) {
            pending.reject(error instanceof Error ? error : new Error(String(error)));
        } else {
            pending.resolve(payload || {});
        }

        return true;
    }

    function getFilenameFromUrl(url) {
        if (!url) {
            return 'project.elpx';
        }

        var clean = url.split('?')[0] || '';
        var parts = clean.split('/');
        return parts[parts.length - 1] || 'project.elpx';
    }

    async function importPackageFromMoodle() {
        if (!config.packageUrl || state.importing || state.imported) {
            return;
        }

        state.importing = true;

        try {
            updateLoadScreen('Downloading project...', true);

            var response = await fetch(config.packageUrl, {credentials: 'include'});
            if (!response.ok) {
                throw new Error('Could not download package (HTTP ' + response.status + ')');
            }

            var bytes = await response.arrayBuffer();
            var filename = getFilenameFromUrl(config.packageUrl);

            updateLoadScreen('Opening project...', true);

            await postToEditor('OPEN_FILE', {
                bytes: bytes,
                filename: filename,
            }, [bytes], 60000);

            state.imported = true;
            console.log('[moodle-exe-bridge] Package opened:', filename);
        } finally {
            state.importing = false;
            updateLoadScreen('', false);
        }
    }

    async function uploadExportToMoodle(bytes, filename) {
        if (!bytes || !bytes.byteLength) {
            throw new Error('Export is empty');
        }

        var uploadName = filename || 'package.zip';
        var blob = bytes instanceof Blob ? bytes : new Blob([bytes], {type: 'application/zip'});

        var formData = new FormData();
        formData.append('package', blob, uploadName);
        formData.append('cmid', String(config.cmid));
        formData.append('sesskey', config.sesskey);

        var response = await fetch(config.saveUrl, {
            method: 'POST',
            credentials: 'include',
            body: formData,
        });

        var result;
        try {
            result = await response.json();
        } catch (jsonError) {
            throw new Error('Invalid save response from Moodle');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error((result && result.error) ? result.error : ('Save failed (HTTP ' + response.status + ')'));
        }

        return result;
    }

    async function saveToMoodle() {
        if (state.saving) {
            return;
        }

        state.saving = true;
        notifyParent('save-start');

        try {
            var exportResponse = await postToEditor('REQUEST_EXPORT', {
                format: 'scorm12',
                filename: 'package.zip',
            }, null, 120000);

            var bytes = exportResponse.bytes;
            if (!bytes && exportResponse.blob) {
                bytes = await exportResponse.blob.arrayBuffer();
            }

            var saveResult = await uploadExportToMoodle(bytes, exportResponse.filename || 'package.zip');

            notifyParent('save-complete', {
                revision: saveResult.revision,
            });
        } catch (error) {
            console.error('[moodle-exe-bridge] Save failed:', error);
            notifyParent('save-error', {
                error: error.message || 'Unknown error',
            });
        } finally {
            state.saving = false;
        }
    }

    async function maybeImport() {
        if (hasInitialProjectUrl) {
            // Fast-path: eXe bootstraps initial package via __EXE_EMBEDDING_CONFIG__.initialProjectUrl.
            state.imported = true;
            return;
        }
        if (!state.ready || state.imported || state.importing) {
            return;
        }

        try {
            await importPackageFromMoodle();
        } catch (error) {
            console.error('[moodle-exe-bridge] Import failed:', error);
            notifyParent('save-error', {error: 'Import failed: ' + (error.message || 'Unknown error')});
        }
    }

    function handleProtocolMessage(message) {
        if (!message || !message.requestId || !message.type) {
            return;
        }

        if (message.type === 'OPEN_FILE_SUCCESS' || message.type === 'SAVE_FILE' || message.type === 'EXPORT_FILE' || message.type === 'PROJECT_INFO'
            || message.type === 'STATE' || message.type === 'CONFIGURE_SUCCESS' || message.type === 'SET_TRUSTED_ORIGINS_SUCCESS') {
            settleRequest(message.requestId, null, message);
            return;
        }

        if (message.type.endsWith('_ERROR')) {
            settleRequest(message.requestId, message.error || (message.type + ' failed'));
        }
    }

    function handleParentMessage(event) {
        if (!event || !event.data) {
            return;
        }

        var message = event.data;

        if (message.type === 'EXELEARNING_READY') {
            state.ready = true;
            notifyParent('editor-ready');
            maybeImport();
            return;
        }

        handleProtocolMessage(message);
    }

    function handleFrameMessage(event) {
        if (!event || !event.data) {
            return;
        }

        var message = event.data;

        if (message.source === 'exescorm-modal' && message.type === 'save') {
            saveToMoodle();
            return;
        }

        handleProtocolMessage(message);
    }

    async function init() {
        window.addEventListener('message', handleFrameMessage);

        if (parentWindow && typeof parentWindow.addEventListener === 'function') {
            parentWindow.addEventListener('message', handleParentMessage);
        }

        notifyWhenDocumentLoaded();

        // Fallback probe in case EXELEARNING_READY was emitted before listeners attached.
        var probeAttempts = 0;
        var probe = setInterval(function() {
            probeAttempts++;
            if (state.ready || probeAttempts > 20) {
                clearInterval(probe);
                return;
            }

            postToEditor('GET_STATE', {}, null, 3000).then(function() {
                if (!state.ready) {
                    state.ready = true;
                    notifyParent('editor-ready');
                    maybeImport();
                }
            }).catch(function() {
                // Ignore until next probe.
            });
        }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
