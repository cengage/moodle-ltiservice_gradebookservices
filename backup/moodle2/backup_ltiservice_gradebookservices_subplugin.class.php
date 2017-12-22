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
 * This file contains the class for restore of this gradebookservices plugin
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/lti/locallib.php');

/**
 * Provides the information to backup gradebookservices lineitems
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_ltiservice_gradebookservices_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element
     */
    protected function define_lti_subplugin_structure() {
        global $DB;

        $userinfo = $this->get_setting_value('users');
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        // The lineitem(s) related with this element.
        $thisactivitylineitems = new backup_nested_element('thisactivitylineitems');
        $thisactivitylineitemslti2 = new backup_nested_element('thisactivitylineitemslti2');
        $thisactivitylineitemsltiad = new backup_nested_element('thisactivitylineitemsltiad');
        $thisactivitylineitemlti2 = new backup_nested_element('coupled_grade_item_lti2', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'toolproxyid', 'baseurl', 'tag', 'vendorcode', 'guid'));
        $thisactivitylineitemltiad = new backup_nested_element('coupled_grade_item_ltiad', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'typeid', 'baseurl', 'tag'));

        // The lineitem(s) not related with any activity.
        // TODO: This will need to change if this module becomes part of the moodle core.
        $nonactivitylineitems = new backup_nested_element('nonactivitylineitems');
        $nonactivitylineitemslti2 = new backup_nested_element('nonactivitylineitemslti2');
        $nonactivitylineitemsltiad = new backup_nested_element('nonactivitylineitemsltiad');
        $nonactivitylineitemlti2 = new backup_nested_element('uncoupled_grade_item_lti2', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'toolproxyid', 'baseurl', 'tag', 'vendorcode', 'guid'));

        $nonactivitylineitemltiad = new backup_nested_element('uncoupled_grade_item_ltiad', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'typeid', 'baseurl', 'tag'));

        // Grades.
        $gradegradeslti2 = new backup_nested_element('grade_grades_lti2');
        $gradegradelti2 = new backup_nested_element('grade_grade_lti2', array('id'), array(
                'itemid', 'userid', 'rawgrade', 'rawgrademax', 'rawgrademin',
                'rawscaleid', 'usermodified', 'finalgrade', 'hidden',
                'locked', 'locktime', 'exported', 'overridden',
                'excluded', 'feedback', 'feedbackformat', 'information',
                'informationformat', 'timecreated', 'timemodified',
                'aggregationstatus', 'aggregationweight'));
        $gradegradesltiad = new backup_nested_element('grade_grades_ltiad');
        $gradegradeltiad = new backup_nested_element('grade_grade_ltiad', array('id'), array(
                'itemid', 'userid', 'rawgrade', 'rawgrademax', 'rawgrademin',
                'rawscaleid', 'usermodified', 'finalgrade', 'hidden',
                'locked', 'locktime', 'exported', 'overridden',
                'excluded', 'feedback', 'feedbackformat', 'information',
                'informationformat', 'timecreated', 'timemodified',
                'aggregationstatus', 'aggregationweight'));

        // Build the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($thisactivitylineitems);
        $thisactivitylineitems->add_child($thisactivitylineitemslti2);
        $thisactivitylineitemslti2->add_child($thisactivitylineitemlti2);
        $thisactivitylineitems->add_child($thisactivitylineitemsltiad);
        $thisactivitylineitemsltiad->add_child($thisactivitylineitemltiad);

        $subpluginwrapper->add_child($nonactivitylineitems);
        $nonactivitylineitems->add_child($nonactivitylineitemslti2);
        $nonactivitylineitemslti2->add_child($nonactivitylineitemlti2);
        $nonactivitylineitemlti2->add_child($gradegradeslti2);
        $gradegradeslti2->add_child($gradegradelti2);
        $nonactivitylineitems->add_child($nonactivitylineitemsltiad);
        $nonactivitylineitemsltiad->add_child($nonactivitylineitemltiad);
        $nonactivitylineitemltiad->add_child($gradegradesltiad);
        $gradegradesltiad->add_child($gradegradeltiad);

        // Define sources.
        $thisactivitylineitemslti2sql = "SELECT g.*,l.toolproxyid,l.baseurl,l.tag,t.vendorcode,t.guid
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_tool_proxies} t ON (t.id = l.toolproxyid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti'
                           AND g.iteminstance = ? AND g.itemnumber>10000 AND l.typeid is null";
        $thisactivitylineitemsltiadsql = "SELECT g.*,l.typeid,l.baseurl,l.tag
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_types} t ON (t.id = l.typeid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti'
                           AND g.iteminstance = ? AND g.itemnumber>10000 AND l.toolproxyid is null";

        $typeid = $DB->get_field('lti', 'typeid' , ['id' => $this->task->get_activityid()]);

        if ($typeid != 0) {
            if ($DB->record_exists('lti_types', ['id' => $typeid])) {
                $toolproxyid = $DB->get_field('lti_types', 'toolproxyid' , ['id' => $typeid]);
                $baseurl = $DB->get_field('lti_types', 'baseurl' , ['id' => $typeid]);
            } else {
                // If and activity is asigned to a type that doesn't exists we don't want to backup any related lineitems.
                // This should not happen, but we cover here this just in case.
                $toolproxyid = '0';
                $baseurl = 'NOVALIDTYPE';
            }
        } else { // This activity comes from and old backup
            // 1. Let's check if the activity is coupled. If so, find the values in the GBS element.
            if ($gbsrecord = $DB->get_record('ltiservice_gradebookservices', ['ltilinkid' => $this->task->get_activityid()])) {
                $typeid = $gbsrecord->typeid;
                $toolproxyid = $gbsrecord->toolproxyid;
                $baseurl = $gbsrecord->baseurl;
            } else { // 2. If it is uncoupled... we will need to guess the right activity typeid
                // Guess the typeid for the activity.
                $toolurl = $DB->get_field('lti', 'toolurl' , ['id' => $this->task->get_activityid()]);
                $tool = lti_get_tool_by_url_match($toolurl, $this->task->get_courseid());
                if (!$tool) {
                    $securetoolurl = $DB->get_field('lti', 'securetoolurl' , ['id' => $this->task->get_activityid()]);
                    $tool = lti_get_tool_by_url_match($securetoolurl,  $this->task->get_courseid());
                }
                if ($tool) {
                    $typeid = $tool->id;
                }
                // If we have a valid typeid then.
                if ($typeid != 0) {
                    $toolproxyid = $DB->get_field('lti_types', 'toolproxyid' , ['id' => $typeid]);
                    $baseurl = $DB->get_field('lti_types', 'baseurl' , ['id' => $typeid]);
                } else { // If not, we won't add any grade_item here.
                    $typeid = '0';
                    $toolproxyid = '0';
                    $baseurl = 'NOVALIDTYPE';
                }
            }
        }
        $nonactivitylineitemslti2sql = "SELECT g.*,l.toolproxyid,l.baseurl,l.tag,t.vendorcode,t.guid
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_tool_proxies} t ON (t.id = l.toolproxyid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti'
                           AND g.iteminstance is null
                           AND g.itemnumber>10000 AND l.typeid is null AND l.toolproxyid = ?";
        $nonactivitylineitemsltiadsql = "SELECT g.*,l.typeid,l.baseurl,l.tag
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_types} t ON (t.id = l.typeid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti'
                           AND g.iteminstance is null
                           AND g.itemnumber>10000 AND l.typeid = ? AND l.baseurl = ? AND l.toolproxyid is null";

        $thisactivitylineitemsparams = ['courseid' => backup::VAR_COURSEID, 'iteminstance' => backup::VAR_ACTIVITYID];
        $thisactivitylineitemlti2->set_source_sql($thisactivitylineitemslti2sql, $thisactivitylineitemsparams);
        $thisactivitylineitemltiad->set_source_sql($thisactivitylineitemsltiadsql, $thisactivitylineitemsparams);
        $nonactivitylineitemslti2params = [backup::VAR_COURSEID, backup_helper::is_sqlparam($toolproxyid)];
        $nonactivitylineitemsltiadparams = [backup::VAR_COURSEID,
                backup_helper::is_sqlparam($typeid), backup_helper::is_sqlparam($baseurl)];
        $nonactivitylineitemlti2->set_source_sql($nonactivitylineitemslti2sql, $nonactivitylineitemslti2params);
        $nonactivitylineitemltiad->set_source_sql($nonactivitylineitemsltiadsql, $nonactivitylineitemsltiadparams);

        if ($userinfo) {
            $gradegradelti2->set_source_table('grade_grades', ['itemid' => backup::VAR_PARENTID]);
            $gradegradeltiad->set_source_table('grade_grades', ['itemid' => backup::VAR_PARENTID]);
        }

        return $subplugin;
    }

}
