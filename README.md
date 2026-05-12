# eXeLearning SCORM activities for Moodle

[![Preview in Moodle Playground](https://raw.githubusercontent.com/ateeducacion/action-moodle-playground-pr-preview/refs/heads/main/assets/playground-preview-button.svg)](https://moodle-playground.com/?blueprint-url=https://raw.githubusercontent.com/exelearning/mod_exescorm/refs/heads/main/blueprint.json)

Activity-type module to create and edit SCORM packages with eXeLearning inside Moodle.

The plugin can run in either of two editing modes, selected by the site administrator under _Site administration > Plugins > Activity modules > eXeLearning (SCORM) > Editor mode_:

* **eXeLearning Online (remote server, default)** — connects to an existing eXeLearning Online instance using its base URL and signing key. This is the default mode shipped by the plugin and the recommended choice when an organisation operates a shared eXeLearning Online deployment.
* **Embedded editor (integrated)** — a self-contained build of the eXeLearning editor ships with the plugin (or can be installed by an administrator from the plugin settings page). It runs directly inside Moodle, so **no external server is required**.

Both modes produce the same kind of activity; pick the one that best fits your infrastructure.

## Compatibility

This plugin works on every supported Moodle release from **Moodle 4.2** (the minimum required, see `version.php`: `$plugin->requires = 2023042400`) up to the latest Moodle 5.2.x stable. It is verified on the LTS branches commonly deployed in production:

| Moodle branch         | Status                                |
| --------------------- | ------------------------------------- |
| 4.2.x                 | Supported (minimum required version)  |
| 4.3.x                 | Supported                             |
| 4.4.x                 | Supported                             |
| 4.5.x (LTS)           | Supported                             |
| 5.0.x                 | Supported                             |
| 5.1.x                 | Supported                             |
| 5.2.x (latest stable) | Supported                             |

Older Moodle releases (3.x, 4.0 and 4.1) are **not** supported. The plugin is expected to keep working with newer Moodle releases as they appear; if you find an incompatibility please open an issue at <https://github.com/exelearning/mod_exescorm/issues>.

### Requirements

* **Moodle**: 4.2 or later (see table above).
* **PHP**: any version required by the Moodle release in use — the plugin does not add extra PHP requirements on top of Moodle's own.
* **Database**: any database supported by the Moodle release in use.
* **Browser**: any modern, evergreen browser with JavaScript enabled.
* **Required for _eXeLearning Online_ mode (default)**: an eXeLearning Online instance and access to its configuration files / signing key. Not needed when running the plugin in _Embedded editor_ mode.

## Installation

> **Important:** It is recommended to install from a [release ZIP](https://github.com/exelearning/mod_exescorm/releases), which includes the embedded editor pre-built for optimal performance. If the release ZIP does not include the editor, or if you want to install a newer version, administrators can download it from GitHub Releases via the embedded editor controls in the plugin settings.

### Installing via uploaded ZIP file

1. Download the latest ZIP from [Releases](https://github.com/exelearning/mod_exescorm/releases).
2. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
3. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
4. Check the plugin validation report and finish the installation.

### Installing manually

1. Download and extract the latest ZIP from [Releases](https://github.com/exelearning/mod_exescorm/releases).
2. Place the extracted contents in `{your/moodle/dirroot}/mod/exescorm`.
3. Log in to your Moodle site as an admin and go to _Site administration >
   Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Configuration

Go to the URL:

    {your/moodle/dirroot}/admin/settings.php?section=modsettingexescorm

  * Remote URI: *exescorm  | exeonlinebaseuri*
    * eXeLearning (online) base URI

  * Signing Key: *exescorm | hmackey1*
    * Key used to sign data sent to the eXeLearning server, to check the data origin. Use up to 32 characters.

  * Token expiration: *exescorm | tokenexpiration*
    * Max time (in seconds) to edit the package in eXeLearning and get back to Moodle.

  * New package template: *exescorm | template*
    * Package uploaded there will be used as the default package for new activities.

  * Send template: *exescorm | sendtemplate*
    * Sends uploaded (or default) template to eXeLearning when creating a new activity.

  * Mandatory files RE list: *exescorm | mandatoryfileslist*
    * A mandatory files list can be configurad here. Enter each mandatory file as a PHP regular expression (RE) on a new line.

  * Forbidden files RE list: *exescorm | forbiddenfileslist*
    * A forbidden files list can be configurad here. Enter each forbidden file as a PHP regular expression (RE) on a new line.

## Embedded Editor Management

The plugin supports two local editor sources with the following precedence:

1. **Admin-installed** (moodledata): Downloaded from GitHub Releases via the plugin settings.
2. **Bundled** (plugin): Included in the plugin release ZIP at `dist/static/`.

The admin-installed version takes priority over the bundled one. If neither source is available, the embedded editor mode cannot be used.

## Development

For development setup, build instructions, and contributing guidelines, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Support

Please report bugs and feature requests on the GitHub issue tracker:
<https://github.com/exelearning/mod_exescorm/issues>

## About

Copyright 2023-2026:
Centro Nacional de Desarrollo Curricular en Sistemas no Propietarios (CeDeC) /
INTEF (Instituto Nacional de Tecnologías Educativas y de Formación del Profesorado)

### License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should receive a copy of the GNU General Public License
along with this program.
