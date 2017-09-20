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

        $userinfo = $this->get_setting_value('users');

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());

        // The lineitem(s) related with this element.
        $thisactivitylineitems = new backup_nested_element('thisactivitylineitems');
        $thisactivitylineitem = new backup_nested_element('coupled_grade_item', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'toolproxyid', 'lineitemtoolproviderid', 'vendorcode', 'guid'));

        // The lineitem(s) not related with any activity.
        // TODO: This will need to change if this module becomes part of the moodle core.
        $nonactivitylineitems = new backup_nested_element('nonactivitylineitems');
        $nonactivitylineitem = new backup_nested_element('uncoupled_grade_item', array('id'), array(
                'categoryid', 'itemname', 'itemtype', 'itemmodule',
                'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
                'calculation', 'gradetype', 'grademax', 'grademin',
                'scaleid', 'outcomeid', 'gradepass', 'multfactor',
                'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
                'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
                'needsupdate', 'timecreated', 'timemodified', 'toolproxyid', 'lineitemtoolproviderid', 'vendorcode', 'guid'));

        // Grades.
        $gradegrades = new backup_nested_element('grade_grades');
        $gradegrade = new backup_nested_element('grade_grade', array('id'), array(
                'itemid', 'userid', 'rawgrade', 'rawgrademax', 'rawgrademin',
                'rawscaleid', 'usermodified', 'finalgrade', 'hidden',
                'locked', 'locktime', 'exported', 'overridden',
                'excluded', 'feedback', 'feedbackformat', 'information',
                'informationformat', 'timecreated', 'timemodified',
                'aggregationstatus', 'aggregationweight'));

        // Build the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($thisactivitylineitems);
        $thisactivitylineitems->add_child($thisactivitylineitem);
        $subpluginwrapper->add_child($nonactivitylineitems);
        $nonactivitylineitems->add_child($nonactivitylineitem);
        $nonactivitylineitem->add_child($gradegrades);
        $gradegrades->add_child($gradegrade);

        // Define sources.
        $thisactivitylineitemssql = "SELECT g.*,l.toolproxyid,l.lineitemtoolproviderid,t.vendorcode,t.guid
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_tool_proxies} t ON (t.id = l.toolproxyid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti' AND g.iteminstance = ? AND g.itemnumber>10000";
        $nonactivitylineitemssql = "SELECT g.*,l.toolproxyid,l.lineitemtoolproviderid,t.vendorcode,t.guid
                           FROM {grade_items} g
                           JOIN {ltiservice_gradebookservices} l ON (g.itemnumber = l.id)
                           JOIN {lti_tool_proxies} t ON (t.id = l.toolproxyid)
                           WHERE courseid = ?
                           AND g.itemtype='mod' AND g.itemmodule = 'lti' AND g.iteminstance is null AND g.itemnumber>10000";

        $thisactivitylineitemsparams = array('courseid' => backup::VAR_COURSEID, 'iteminstance' => backup::VAR_ACTIVITYID);
        $thisactivitylineitem->set_source_sql($thisactivitylineitemssql, $thisactivitylineitemsparams);

        $nonactivitylineitemsparams = array('courseid' => backup::VAR_COURSEID);
        $nonactivitylineitem->set_source_sql($nonactivitylineitemssql, $nonactivitylineitemsparams);

        if ($userinfo) {
            $gradegrade->set_source_table('grade_grades', array('itemid' => backup::VAR_PARENTID));
        }

        return $subplugin;
    }

}
