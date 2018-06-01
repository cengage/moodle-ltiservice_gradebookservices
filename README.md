# moodle-ltiservice_gradebookservices

An implementation for Moodle of IMS LTI Gradebook Services. This subplugin will allow LTI Tool Providers
to use the capabilities defined in the IMS LTI Gradebook Services specifications.

Among other things, it allows a Tool to create Line Items without an explicit Resource Link,
allowing for example a link to a Courseware platform to report
multiple grades rather than being limited to a single aggregation grade.

See the project page on Moodle docs: https://docs.moodle.org/dev/LTI_Gradebook_Services

This plugin has been tested on Moodle 3.2 upwards, developed using MySQL and tested on Postgres and Oracle.
It's a slighlty modified version of the version of the plugin included in 3.5 to address
some shortcomings around subplugin backup/restore that were addressed with moodle 3.5.

## History

This plugin adds support to IMS LTI Advantage Assignment and Grade Services as an LTI 2.0
service.

During the development effort of the plugin, the LTI 2.0 specifications were deprecated. However
Services were only available to LTI 2.0 deployment. Working with Moodle HQ this service and moodle core
was modified to allow services for 1.1 tools. As a result, a newer version of this plugin is now
integrated directly in Moodle 3.5 onwards.

## Upgrading to moodle 3.5

Moodle 3.5 includes a slightly different version of this plugin. It is recommended to remove the
plugin code prior to upgrading. **Do not uninstall** the plugin as this would remove the mapping
data required for grade exchange.

No database change are needed as both this plugin and moodle 3.5
uses the same table definition to support this feature. After update to 3.5, LTI 2.0 tools should
be able to keep using the service. The service will also be available for manual deployments (LTI 1.1).

## Install

To install this plugin in Moodle:

1. Download the plugin zip file `ltiservice_gradebookservices.zip`.
2. Install this subplugin in Moodle as any other plugin following the instructions in this link: https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file 
