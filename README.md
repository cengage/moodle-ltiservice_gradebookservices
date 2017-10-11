# moodle-ltiservice_gradebookservices

An implementation for Moodle of IMS LTI Gradebook Services. This subplugin will allow LTI Tool Providers to use the capabilties defined in the IMS LTI Gradebook Services specifications.

Among other things, it allows a Tool to create Line Items without an explicit Resource Link, allowing for example a link to a Courseware platform to report multiple grades rather than being limited to a single aggregation grade.

See the project page on Moodle docs: https://docs.moodle.org/dev/LTI_Gradebook_Services

This plugin has been tested on Moodle 3.2 upwards, developed using MySQL and tested on Postgres and Oracle.

## Install

To install this plugin in Moodle:

1. Download the plugin in a zip file from https://github.com/CengageEng/moodle-ltiservice_gradebookservices using the button to "Clone or Download".
2. Rename the main folder that is created inside the zip to: ltiservice_gradebookservices
3. Install this subplugin in Moodle as any other plugin following the instructions in this link: https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file 
