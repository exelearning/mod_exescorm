/**
 * Bridge between the embedded eXeLearning editor and Moodle.
 *
 * This script runs inside the editor iframe. It reads Moodle configuration
 * injected by editor/index.php, handles importing the current package into
 * the editor, and saves edited packages back to Moodle via AJAX.
 *
 * Based on the same bridge pattern used in wp-exelearning and omeka-s-exelearning.
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
        console.error('[moodle-exe-bridge] No __MOODLE_EXE_CONFIG__ found');
        return;
    }

    console.log('[moodle-exe-bridge] Initializing with config:', config);

    /**
     * Wait for the eXeLearning app to be ready (legacy fallback).
     *
     * @param {number} maxAttempts Maximum attempts before giving up.
     * @return {Promise} Resolves with the app instance.
     */
    function waitForAppLegacy(maxAttempts) {
        maxAttempts = maxAttempts || 100;
        return new Promise(function(resolve, reject) {
            var attempts = 0;
            var check = function() {
                attempts++;
                if (window.eXeLearning && window.eXeLearning.app) {
                    resolve(window.eXeLearning.app);
                } else if (attempts < maxAttempts) {
                    setTimeout(check, 100);
                } else {
                    reject(new Error('App did not initialize'));
                }
            };
            check();
        });
    }

    /**
     * Wait for the Yjs project bridge to be ready.
     *
     * @param {number} maxAttempts Maximum attempts before giving up.
     * @return {Promise} Resolves with the bridge instance.
     */
    function waitForBridge(maxAttempts) {
        maxAttempts = maxAttempts || 150;
        return new Promise(function(resolve, reject) {
            var attempts = 0;
            var check = function() {
                attempts++;
                var bridge = window.eXeLearning?.app?.project?._yjsBridge
                    || window.YjsModules?.getBridge?.();
                if (bridge) {
                    console.log('[moodle-exe-bridge] Bridge found after', attempts, 'attempts');
                    resolve(bridge);
                } else if (attempts < maxAttempts) {
                    setTimeout(check, 200);
                } else {
                    reject(new Error('Project bridge did not initialize'));
                }
            };
            check();
        });
    }

    /**
     * Show or update the loading screen.
     *
     * @param {string} message Message to display.
     * @param {boolean} show Whether to show or hide.
     */
    function updateLoadScreen(message, show) {
        if (show === undefined) {
            show = true;
        }
        var loadScreen = document.getElementById('load-screen-main');
        var loadMessage = loadScreen?.querySelector('.loading-message, p');

        if (loadScreen) {
            if (show) {
                loadScreen.classList.remove('hide');
            } else {
                loadScreen.classList.add('hide');
            }
        }

        if (loadMessage && message) {
            loadMessage.textContent = message;
        }
    }

    /**
     * Import the ELP package from Moodle into the editor.
     */
    async function importPackageFromMoodle() {
        var packageUrl = config.packageUrl;
        if (!packageUrl) {
            console.log('[moodle-exe-bridge] No package URL, starting with empty project');
            return;
        }

        console.log('[moodle-exe-bridge] Starting import from:', packageUrl);

        try {
            updateLoadScreen('Loading project...');

            // Wait for the Yjs bridge to be initialized.
            updateLoadScreen('Waiting for editor...');
            var bridge = await waitForBridge();

            // Fetch the package file.
            updateLoadScreen('Downloading file...');
            var response = await fetch(packageUrl, {credentials: 'include'});
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }

            // Convert to File object.
            var blob = await response.blob();
            console.log('[moodle-exe-bridge] File downloaded, size:', blob.size);
            var filename = packageUrl.split('/').pop().split('?')[0] || 'project.elpx';
            var file = new File([blob], filename, {type: 'application/zip'});

            // Import using the project API or bridge directly.
            updateLoadScreen('Importing content...');
            var project = window.eXeLearning?.app?.project;
            if (typeof project?.importElpxFile === 'function') {
                console.log('[moodle-exe-bridge] Using project.importElpxFile...');
                await project.importElpxFile(file);
            } else if (typeof project?.importFromElpxViaYjs === 'function') {
                console.log('[moodle-exe-bridge] Using project.importFromElpxViaYjs...');
                await project.importFromElpxViaYjs(file, {clearExisting: true});
            } else {
                console.log('[moodle-exe-bridge] Using bridge.importFromElpx...');
                await bridge.importFromElpx(file, {clearExisting: true});
            }

            console.log('[moodle-exe-bridge] Package imported successfully');
        } catch (error) {
            console.error('[moodle-exe-bridge] Import failed:', error);
            updateLoadScreen('Error loading project');
        } finally {
            setTimeout(function() {
                updateLoadScreen('', false);
            }, 500);
        }
    }

    /**
     * Export the current project and save it back to Moodle.
     */
    async function saveToMoodle() {
        // Notify parent window that save is starting.
        notifyParent('save-start');

        try {
            console.log('[moodle-exe-bridge] Starting save...');

            // Get the project bridge for export.
            var project = window.eXeLearning?.app?.project;
            var yjsBridge = project?._yjsBridge
                || window.YjsModules?.getBridge?.()
                || project?.bridge;

            if (!yjsBridge) {
                throw new Error('Project bridge not available');
            }

            // Export using SharedExporters (scorm12 for SCORM packages).
            var blob;
            if (window.SharedExporters?.quickExport) {
                console.log('[moodle-exe-bridge] Using SharedExporters.quickExport...');
                var result = await window.SharedExporters.quickExport(
                    'scorm12',
                    yjsBridge.documentManager,
                    null,
                    yjsBridge.resourceFetcher,
                    {},
                    yjsBridge.assetManager
                );
                if (!result.success || !result.data) {
                    throw new Error('Export failed');
                }
                blob = new Blob([result.data], {type: 'application/zip'});
            } else if (window.SharedExporters?.createExporter) {
                console.log('[moodle-exe-bridge] Using SharedExporters.createExporter...');
                var exporter = window.SharedExporters.createExporter(
                    'scorm12',
                    yjsBridge.documentManager,
                    yjsBridge.assetCache,
                    yjsBridge.resourceFetcher,
                    yjsBridge.assetManager
                );
                var exportResult = await exporter.export();
                if (!exportResult.success || !exportResult.data) {
                    throw new Error('Export failed');
                }
                blob = new Blob([exportResult.data], {type: 'application/zip'});
            } else {
                throw new Error('No exporter available');
            }

            console.log('[moodle-exe-bridge] Export complete, size:', blob.size);

            // Upload to Moodle.
            var formData = new FormData();
            formData.append('package', blob, 'package.zip');
            formData.append('cmid', config.cmid);
            formData.append('sesskey', config.sesskey);

            console.log('[moodle-exe-bridge] Uploading to:', config.saveUrl);

            var saveResponse = await fetch(config.saveUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });

            var saveResult = await saveResponse.json();

            if (saveResult.success) {
                console.log('[moodle-exe-bridge] Save successful, revision:', saveResult.revision);
                showNotification('success', 'Saved successfully!');
                notifyParent('save-complete', {revision: saveResult.revision});
            } else {
                throw new Error(saveResult.error || 'Save failed');
            }
        } catch (error) {
            console.error('[moodle-exe-bridge] Save failed:', error);
            showNotification('error', 'Error: ' + error.message);
            notifyParent('save-error', {error: error.message});
        }
    }

    /**
     * Send a message to the parent window (the Moodle modal).
     *
     * @param {string} type Message type.
     * @param {Object} data Optional payload.
     */
    function notifyParent(type, data) {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                source: 'exescorm-editor',
                type: type,
                data: data || {},
            }, '*');
        }
    }

    /**
     * Show a notification inside the editor.
     *
     * @param {string} type Notification type (success, error).
     * @param {string} message Message to display.
     */
    function showNotification(type, message) {
        var existing = document.getElementById('moodle-exe-notification');
        if (existing) {
            existing.remove();
        }

        var notification = document.createElement('div');
        notification.id = 'moodle-exe-notification';
        notification.style.cssText = 'position:fixed;top:10px;right:10px;z-index:99999;padding:12px 20px;'
            + 'border-radius:4px;color:#fff;font-size:14px;'
            + (type === 'success' ? 'background:#28a745;' : 'background:#dc3545;');
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(function() {
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Initialize the bridge.
     */
    async function init() {
        try {
            console.log('[moodle-exe-bridge] Starting initialization...');

            // Wait for app initialization using the ready promise or legacy polling.
            if (window.eXeLearning?.ready) {
                await window.eXeLearning.ready;
            } else {
                await waitForAppLegacy();
            }
            console.log('[moodle-exe-bridge] App initialized');

            // Import package if URL provided.
            if (config.packageUrl) {
                await importPackageFromMoodle();
            } else {
                console.log('[moodle-exe-bridge] No packageUrl in config, skipping import');
            }

            // Notify parent window that bridge is ready.
            notifyParent('editor-ready');

            // Listen for save shortcuts (Ctrl+S / Cmd+S).
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    saveToMoodle();
                }
            });

            // Listen for messages from parent window (modal save button).
            window.addEventListener('message', function(event) {
                if (event.data && event.data.source === 'exescorm-modal') {
                    if (event.data.type === 'save') {
                        saveToMoodle();
                    }
                }
            });

            console.log('[moodle-exe-bridge] Initialization complete');
        } catch (error) {
            console.error('[moodle-exe-bridge] Initialization failed:', error);
        }
    }

    // Initialize when DOM is ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for debugging.
    window.moodleExeBridge = {
        config: config,
        save: saveToMoodle,
        import: importPackageFromMoodle,
    };

})();
