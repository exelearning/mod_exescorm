# CHANGELOG

## v4.0.2 – 2026-07-07

- Allow saving the plugin settings page without requiring an optional package template file.
- Use eXeLearning's native `?exe-teacher=1` URL parameter to reveal teacher-layer content instead of the previous CSS injection approach.
- Fix Playground environments loading an outdated embedded editor version when `.editor-version` had already been updated.
- Automatically keep the Playground blueprint editor URL in sync with `.editor-version` on each release.
- Review and complete the Spanish (es) translation.
- Review all language packs and add placeholder translations for newly introduced strings in incomplete translations.

---

## v4.0.1 – 2026-06-09

- Fix admin upgrade settings loop where the Styles upload widget was repeatedly reintroduced after upgrading to v4.0.0.
- Fix TOC navigation loop that caused additional iframe reloads and incorrect rendering in nested sections.
- Fix TOC menu accessibility issues and add keyboard navigation support.
- Fix embedded editor version detection in release packages so the correct eXeLearning version is reported.
- Fix editor modal overlay positioning in embedded and popup contexts.
- Update `iframe` title when changing its `src` to improve accessibility.
- Correct README license reference from "version 2" to "GNU GPL v3 or later", aligning documentation with the LICENSE file and source `@license` headers.
- Automatically install and configure the embedded editor in Playground environments.

---

## v4.0.0 – 2025-04-30

- Version jump to 4.0.0 to align numbering with eXeLearning for consistency across related projects.
- Introduce fully integrated embedded eXeLearning editor inside Moodle, enabling SCORM content creation and editing without leaving the platform.
- Add editor bootstrap system (`editor/index.php`, `editor/static.php`, `editor/save.php`) with iframe-based loading and postMessage communication layer for save/load operations.
- Implement admin settings interface to install, update and uninstall the editor from GitHub releases, including improved UX feedback with spinner states and multilingual status messages.
- Add external AJAX API (`manage_embedded_editor`) for installation, status management and operational control of the embedded editor.
- Introduce source resolution strategy prioritizing `moodledata` over bundled assets for editor resource loading.
- Add editor modal overlay (`editor_modal.js`) providing fullscreen editing experience with "Save to Moodle" action, unsaved changes detection and loading indicators.
- Add postMessage bridge (`moodle_exe_bridge.js`) supporting document lifecycle events such as `OPEN_FILE`, `REQUEST_EXPORT` and real-time change tracking via Yjs.
- Implement proxy layer for `exeonlinebaseuri` and `exescorm` module to enhance security and isolate backend communication within the Moodle plugin.
- Improve admin-aware error handling with differentiated messaging for administrators ("install from plugin settings") and standard users ("contact your administrator").
- Add blueprint configuration for default setup and sample ELPX activities in Playground environments.
- Extend multilingual support (en, es, ca, eu, gl) across editor management and runtime interfaces.
- Introduce new capability `mod/exescorm:manageembeddededitor` to control editor management operations.
- Add CI support for Moodle Playground PR previews and automated checks for new editor releases.
- Ensure compatibility with subpath deployments by restricting trusted origins to scheme + host in the postMessage security model.
- Replace iframe error handling with inline HTML rendering to avoid Moodle exception screens in embedded contexts.
- Add alternative upload endpoint (`manage_embedded_editor_upload.php`) for environments without direct GitHub access (e.g., Playground/WASM).
- Add compatibility with eXeLearning 4 while maintaining support from eXeLearning 2.9 online onwards.
- Update activity icons to align with the latest eXeLearning design.
- Resolve HTML validation issues in Mustache templates by correcting form attribute usage.
- Correct PHPDoc annotations and complete parameter definitions across multiple components.

---

## v1.1 – 2025-06-18

### Development & tooling
- Add Docker support and Makefile for development, linting and fixing tasks.
- Improve build workflow.
- Add `composer.json` for dependency management.

### Moodle integration & compatibility
- Provide compatibility for eXe 3 and 2.9.
- Fix "Edit on eXeLearning and return to course" button functionality.
- Hide edit option when there is no `exeonlinebaseuri` setting.
- Implement proxy for `exeonlinebaseuri` and `exescorm` module to enhance security in the Moodle plugin.

### Backend & parameters
- Review parameters: pass `HOST_IP` to Moodle and refine API key handling.
- Remove unnecessary IP detection logic.
- Add provider field to payload.

### Fixes
- Fix parameter order when creating eXe user and resolve malformed Moodle site name handling.
- Resolve HTML validation errors in Mustache templates by updating form attributes.
- Correct PHPDoc tags and complete parameter lists in various files.

### Defaults & requirements
- Introduce new default value for mandatory files list (required for eXeLearning v3.0.0).

---

## v1.0 – 2024-03-20

**First release of mod_exescorm**

Moodle activity module for creating and editing SCORM packages using [eXeLearning (online version)](https://github.com/exelearning/iteexe_online).

Requires the eXeLearning online version to be installed and access to its configuration files in order to operate correctly.
