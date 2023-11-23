# eXeLearning SCORM activities for Moodle

Activity-type module to create and edit SCORM packages with eXeLearning (online).

You need the eXeLearning online version installed (ws28 or higher) and access to its configuration files to run
this module.

## Compatibility

This plugin version is tested for:

* Moodle 4.1.3+ (Build: 20230526)
* Moodle 3.11.10+ (Build: 20221007)
* Moodle 3.9.2+ (Build: 20200929)

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/exescorm

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
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

## About

Copyright 2023:
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
