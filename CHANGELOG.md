# CHANGELOG

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