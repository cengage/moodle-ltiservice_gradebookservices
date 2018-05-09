# moodle-ltiservice_gradebookservices

An implementation for Moodle of IMS LTI Gradebook Services. This subplugin will allow LTI Tool Providers to use the capabilties defined in the IMS LTI Gradebook Services specifications.

Among other things, it allows a Tool to create Line Items without an explicit Resource Link, allowing for example a link to a Courseware platform to report multiple grades rather than being limited to a single aggregation grade.

See the project page on Moodle docs: https://docs.moodle.org/dev/LTI_Gradebook_Services

This plugin has been tested on Moodle 3.2 upwards, developed using MySQL and tested on Postgres and Oracle.

## history and WARNING!

This plugin adds Assignment and Grade Services from the final draft of the specification to Moodle as an LTI 2.0
service.

During the development effort of the plugin, the LTI 2.0 specifications were deprecated. However
Services were only available to LTI 2.0 deployment. Working with Moodle HQ this service and moodle core
was modified to allow services for 1.1 tools. As a result, a newer version of this plugin is now
integrated directly in Moodle 3.5 onwards. If you install this plugin and update to 3.5, you will need
to manually run an update script to fix the table this plugin is using to the definition 3.5 code expects.

## Migration script

If you install this plugin, you will need to manually update the ltiservice_gradebookservices table.
We provide a script that will update the table and migrate the existing data.

Prior to upgrading to moodle:
1. Remove the plugin code (now included with moodle): mod/lti/service/gradebookservices

After 3.5 code has been deployed:
1. Copy [post_35_upgrade_cli.php](db/post_35_upgrade_cli.php) to moodle_root/mod/lti/service/gradebookservices/db
1. `cd mod/lti/service/gradebookservices/db; php post_35_upgrade_cli.php`
1. Done! You should now be able to carrying on use your LTI 2.0 deployment

## Install

To install this plugin in Moodle:

1. Download the plugin in a zip file from https://github.com/CengageEng/moodle-ltiservice_gradebookservices using the button to "Clone or Download".
2. Rename the main folder that is created inside the zip to: ltiservice_gradebookservices
3. Delete the scripts folder inside the zip. That's not a folder that moodle needs. 
4. Install this subplugin in Moodle as any other plugin following the instructions in this link: https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file 
