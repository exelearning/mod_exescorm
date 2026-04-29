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
 * Embedded eXeLearning editor bootstrap page.
 *
 * Loads the static editor and injects Moodle configuration so the editor
 * can communicate with Moodle (load/save packages).
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot . '/mod/exescorm/lib.php');

/**
 * Output a visible error page inside the editor iframe.
 *
 * @param string $message The error message to display.
 */
function exescorm_editor_error_page(string $message): void {
    $escapedmessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body {
        display: flex; align-items: center; justify-content: center;
        min-height: 100vh; margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: #f8f9fa; color: #333;
    }
    .error-box {
        max-width: 520px; padding: 2rem; text-align: center;
        background: #fff; border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,.1);
        border-left: 4px solid #dc3545;
    }
    .error-box h2 { margin: 0 0 .75rem; color: #dc3545; font-size: 1.25rem; }
    .error-box p { margin: 0; line-height: 1.5; }
</style>
</head>
<body>
<div class="error-box">
    <h2>⚠ Error</h2>
    <p>{$escapedmessage}</p>
</div>
</body>
</html>
HTML;
    die;
}

$id = required_param('id', PARAM_INT); // Course module ID.

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$exescorm = $DB->get_record('exescorm', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);
require_sesskey();

// Build the package URL for the editor to import.
$packageurl = exescorm_get_package_url($exescorm, $context);

// Build the save endpoint URL.
$saveurl = new moodle_url('/mod/exescorm/editor/save.php');

// Serve editor resources through static.php (slash arguments) to ensure
// files are always accessible regardless of web server configuration.
$editorbaseurl = $CFG->wwwroot . '/mod/exescorm/editor/static.php/' . $cm->id;

// Read the editor template from the active local source.
$editorindexsource = exescorm_get_embedded_editor_index_source();
if ($editorindexsource === null) {
    if (is_siteadmin()) {
        exescorm_editor_error_page(get_string('embeddednotinstalledadmin', 'mod_exescorm'));
    } else {
        exescorm_editor_error_page(get_string('embeddednotinstalledcontactadmin', 'mod_exescorm'));
    }
}
$html = @file_get_contents($editorindexsource);
if ($html === false || empty($html)) {
    exescorm_editor_error_page(get_string('editormissing', 'mod_exescorm'));
}

// Inject <base> tag pointing directly to the static directory.
$basetag = '<base href="' . htmlspecialchars($editorbaseurl, ENT_QUOTES, 'UTF-8') . '/">';
$html = preg_replace('/(<head[^>]*>)/i', '$1' . $basetag, $html);

// Fix explicit "./" relative paths in attributes.
$html = preg_replace(
    '/(?<=["\'])\.\//',
    htmlspecialchars($editorbaseurl, ENT_QUOTES, 'UTF-8') . '/',
    $html
);

// Build Moodle configuration for the bridge script.
$moodleconfig = json_encode([
    'cmid' => $cm->id,
    'contextid' => $context->id,
    'sesskey' => sesskey(),
    'packageUrl' => $packageurl ? $packageurl->out(false) : '',
    'saveUrl' => $saveurl->out(false),
    'activityName' => format_string($exescorm->name),
    'wwwroot' => $CFG->wwwroot,
    'editorBaseUrl' => $editorbaseurl,
]);

// Extract the origin (scheme + host) from wwwroot for postMessage trust.
$parsedwwwroot = parse_url($CFG->wwwroot);
$wwwrootorigin = $parsedwwwroot['scheme'] . '://' . $parsedwwwroot['host']
    . (!empty($parsedwwwroot['port']) ? ':' . $parsedwwwroot['port'] : '');

$embeddingconfig = json_encode([
    'basePath' => $editorbaseurl,
    'parentOrigin' => $wwwrootorigin,
    'trustedOrigins' => [$wwwrootorigin],
    'initialProjectUrl' => $packageurl ? $packageurl->out(false) : '',
    'hideUI' => [
        'fileMenu' => true,
        'saveButton' => true,
        'userMenu' => true,
    ],
    'platform' => 'moodle',
    'pluginVersion' => get_config('mod_exescorm', 'version'),
]);

// Approved style registry consumed by the editor's themeRegistryOverride
// hook (see exelearning/exelearning#1722). Filters built-ins, appends
// admin-uploaded styles, and blocks install-from-content paths.
$themeoverride = json_encode(
    \mod_exescorm\local\styles_service::build_theme_registry_override()
);

// Inject configuration scripts before </head>.
// The static editor boot sequence reassigns window.eXeLearning and
// window.eXeLearning.config repeatedly (the inline script in index.html
// resets the whole object, and app.bundle.js later parses 'config' from a
// JSON string back into an object), so a plain assignment of
// themeRegistryOverride never reaches the editor. Wrap the injection in a
// self-restoring defineProperty getter/setter so the override and the
// userStyles mirror survive every reset.
$configscript = <<<EOT
<script>
    window.__MOODLE_EXE_CONFIG__ = $moodleconfig;
    window.__EXE_EMBEDDING_CONFIG__ = $embeddingconfig;
    (function() {
        var OVERRIDE = $themeoverride;
        function injectConfig(cfg) {
            if (!cfg || typeof cfg !== "object" || Array.isArray(cfg)) return cfg;
            cfg.themeRegistryOverride = OVERRIDE;
            // Mirror blockImportInstall onto the pre-existing userStyles
            // flag (ONLINE_THEMES_INSTALL) so the install-from-project
            // modal is also suppressed end-to-end.
            cfg.userStyles = OVERRIDE && OVERRIDE.blockImportInstall ? 0 : 1;
            return cfg;
        }
        function trapConfig(target) {
            if (!target || typeof target !== "object") return;
            var stored = injectConfig(target.config);
            try {
                Object.defineProperty(target, "config", {
                    configurable: true,
                    enumerable: true,
                    get: function() { return stored; },
                    set: function(v) { stored = injectConfig(v); }
                });
            } catch (e) {
                target.config = stored;
            }
        }
        var rootValue = window.eXeLearning;
        trapConfig(rootValue);
        try {
            Object.defineProperty(window, "eXeLearning", {
                configurable: true,
                get: function() { return rootValue; },
                set: function(v) { rootValue = v; trapConfig(v); }
            });
        } catch (e) {
            window.eXeLearning = rootValue || {};
            trapConfig(window.eXeLearning);
        }
    })();

    // The static editor's ResourceFetcher rejects on missing CSS / iDevice
    // resources, which surfaces as an "Uncaught (in promise)" that aborts
    // the Yjs theme bind and leaves the editor unresponsive. WP and Omeka-S
    // ship the same workaround: swallow 404s on .css / idevices URLs and
    // return an empty stylesheet so the editor keeps booting.
    // Disable any new service-worker registration (the static editor's
    // preview-sw.js is served from the same static.php router; environments
    // that proxy or cache that router — e.g. moodle-playground — return a
    // 404 there and the registration error spams the console without
    // blocking anything).
    (function() {
        if ("serviceWorker" in navigator) {
            try {
                var registerOriginal = navigator.serviceWorker.register
                    ? navigator.serviceWorker.register.bind(navigator.serviceWorker)
                    : null;
                navigator.serviceWorker.register = function(scriptURL, options) {
                    if (typeof scriptURL === "string" && scriptURL.indexOf("preview-sw.js") !== -1) {
                        return Promise.resolve({ scope: "" });
                    }
                    return registerOriginal
                        ? registerOriginal(scriptURL, options)
                        : Promise.resolve({ scope: "" });
                };
            } catch (e) {
                // Some embeds make navigator.serviceWorker non-writable; ignore.
            }
        }

        var originalFetch = window.fetch;
        if (originalFetch) {
            window.fetch = function(input, init) {
                var url = typeof input === "string" ? input : (input && input.url) || "";
                return originalFetch.apply(this, arguments).then(function(response) {
                    if (!response.ok && (url.indexOf(".css") !== -1 || url.indexOf("idevices") !== -1)) {
                        console.warn("[mod_exescorm] Fetch 404 fallback:", url);
                        return new Response("/* empty fallback */", {
                            status: 200,
                            headers: { "Content-Type": "text/css" }
                        });
                    }
                    return response;
                }).catch(function(error) {
                    if (url.indexOf(".css") !== -1 || url.indexOf("idevices") !== -1) {
                        console.warn("[mod_exescorm] Fetch error fallback:", url);
                        return new Response("/* empty fallback */", {
                            status: 200,
                            headers: { "Content-Type": "text/css" }
                        });
                    }
                    throw error;
                });
            };
        }

        var patchJQuery = function(\$) {
            if (!\$ || !\$.ajaxTransport) return;
            \$.ajaxTransport("+*", function(options) {
                var url = options.url || "";
                if (!(url.indexOf(".css") !== -1 || url.indexOf("idevices") !== -1)) return;
                return {
                    send: function(headers, completeCallback) {
                        var xhr = new XMLHttpRequest();
                        xhr.open(options.type || "GET", url, true);
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                completeCallback(xhr.status, xhr.statusText, { text: xhr.responseText });
                            } else {
                                console.warn("[mod_exescorm] jQuery 404 fallback:", url);
                                completeCallback(200, "OK", { text: "/* empty fallback */" });
                            }
                        };
                        xhr.onerror = function() {
                            console.warn("[mod_exescorm] jQuery error fallback:", url);
                            completeCallback(200, "OK", { text: "/* empty fallback */" });
                        };
                        xhr.send();
                    },
                    abort: function() {}
                };
            });
        };
        if (window.jQuery) {
            patchJQuery(window.jQuery);
        } else {
            try {
                Object.defineProperty(window, "jQuery", {
                    configurable: true,
                    set: function(val) {
                        Object.defineProperty(window, "jQuery", {
                            configurable: true, writable: true, enumerable: true, value: val
                        });
                        patchJQuery(val);
                    },
                    get: function() { return undefined; }
                });
            } catch (e) {
                // jQuery already defined non-configurable; nothing to patch.
            }
        }
    })();
</script>
EOT;

// Inject bridge script before </body>.
$bridgescript = '<script src="' . $CFG->wwwroot . '/mod/exescorm/amd/src/moodle_exe_bridge.js"></script>';

$html = str_replace('</head>', $configscript . "\n" . '</head>', $html);
$html = str_replace('</body>', $bridgescript . "\n" . '</body>', $html);

// Output the processed HTML.
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
echo $html;
