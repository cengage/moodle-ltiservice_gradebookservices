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
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Restore subplugin class.
 *
 * Provides the necessary information
 * needed to restore the lineitems related with the lti activity (coupled),
 * and all the uncoupled ones from the course.
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ltiservice_gradebookservices_subplugin extends restore_subplugin{

    protected function define_lti_subplugin_structure() {

        $userinfo = $this->get_setting_value('users');

        $paths = array();
        $elename = $this->get_namefor('coupledgradeitemlti2');
        $elepath = $this->get_pathfor('/thisactivitylineitems/thisactivitylineitemslti2/coupled_grade_item_lti2');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = $this->get_namefor('coupledgradeitemltiad');
        $elepath = $this->get_pathfor('/thisactivitylineitems/thisactivitylineitemsltiad/coupled_grade_item_ltiad');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = $this->get_namefor('uncoupledgradeitemlti2');
        $elepath = $this->get_pathfor('/nonactivitylineitems/nonactivitylineitemslti2/uncoupled_grade_item_lti2');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = $this->get_namefor('uncoupledgradeitemltiad');
        $elepath = $this->get_pathfor('/nonactivitylineitems/nonactivitylineitemsltiad/uncoupled_grade_item_ltiad');
        $paths[] = new restore_path_element($elename, $elepath);
        if ($userinfo) {
            $elename = $this->get_namefor('gradegradelti2');
            $elepath = $this->get_pathfor('/nonactivitylineitems/nonactivitylineitemslti2/'.
                    'uncoupled_grade_item_lti2/grade_grades_lti2/grade_grade_lti2');
            $paths[] = new restore_path_element($elename, $elepath);
            $elename = $this->get_namefor('gradegradeltiad');
            $elepath = $this->get_pathfor('/nonactivitylineitems/nonactivitylineitemsltiad/'.
                    'uncoupled_grade_item_ltiad/grade_grades_ltiad/grade_grade_ltiad');
            $paths[] = new restore_path_element($elename, $elepath);
        }
        return $paths;
    }

    /**
     * Processes one coupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_coupledgradeitemlti2($data) {
        global $DB;
        $data = (object)$data;
        // The coupled lineitems are restored as any other grade item
        // so we will only create the entry in the ltiservice_gradebookservices table.
        // As we can't update the grade_item because it has not been created yet,
        // we store the previousid, so we can relate this entry with the new grede item.

        // We will try to find a valid toolproxy in the system.
        $newtoolproxyid = $this->find_proxy_id($data);

        try {
            $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                    'toolproxyid' => $newtoolproxyid,
                    'ltilinkid' => $this->get_new_parentid('lti'),
                    'typeid' => null,
                    'baseurl' => $data->baseurl,
                    'tag' => $data->tag,
                    'previousid' => $data->itemnumber
            ));
        } catch (\Exception $e) {
            debugging('Error restoring the lti gradebookservicescreating: ' . $e->getTraceAsString());
        }
    }

    /**
     * Processes one coupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_coupledgradeitemltiad($data) {
        global $DB;
        $data = (object)$data;
        // The coupled lineitems are restored as any other grade item
        // so we will only create the entry in the ltiservice_gradebookservices table.
        // As we can't update the grade_item because it has not been created yet,
        // we store the previousid, so we can relate this entry with the new grede item.

        // We will try to find a valid type in the system.
        $courseid = $this->task->get_courseid();
        $newtypeid = $this->find_typeid($data, $courseid);
        try {
            $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                    'toolproxyid' => null,
                    'ltilinkid' => $this->get_new_parentid('lti'),
                    'typeid' => $newtypeid,
                    'baseurl' => $data->baseurl,
                    'tag' => $data->tag,
                    'previousid' => $data->itemnumber
            ));
        } catch (\Exception $e) {
            debugging('Error restoring the lti gradebookservicescreating: ' . $e->getTraceAsString());
        }
    }

    /**
     * Processes one uncoupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_uncoupledgradeitemlti2($data) {
        global $DB;
        $data = (object)$data;
        // We will try to find a valid toolproxy in the system.
        $newtoolproxyid = $this->find_proxy_id($data);
        $courseid = $this->task->get_courseid();
        try {
            $sql = 'SELECT * FROM {grade_items} gi
                    INNER JOIN {ltiservice_gradebookservices} gbs ON gbs.id = gi.itemnumber
                    AND courseid =? and gbs.previousid=?';
            $conditions = array('courseid' => $courseid, 'previousid' => $data->itemnumber);
            // We will check if the record has been restored by a previous activity
            // and if not, we will restore it creating the right grade item and the
            // right entry in the ltiservice_gradebookservices table.
            if (!$DB->record_exists_sql($sql, $conditions)) {
                // Restore the lineitem.
                $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                        'toolproxyid' => $newtoolproxyid,
                        'ltilinkid' => null,
                        'typeid' => null,
                        'baseurl' => $data->baseurl,
                        'tag' => $data->tag,
                        'previousid' => $data->itemnumber
                ));
                $oldid = $data->id;
                $params = array();
                $params['itemname'] = $data->itemname;
                $params['gradetype'] = GRADE_TYPE_VALUE;
                $params['grademax']  = $data->grademax;
                $params['grademin']  = $data->grademin;
                $item = new \grade_item(array('id' => 0, 'courseid' => $courseid));
                \grade_item::set_properties($item, $params);
                $item->itemtype = 'mod';
                $item->itemmodule = 'lti';
                $item->itemnumber = $gradebookservicesid;
                $item->idnumber = $data->idnumber;
                $id = $item->insert('mod/ltiservice_gradebookservices');
                $this->set_mapping('uncoupled_grade_item_lti2', $oldid, $id);
            }
        } catch (\Exception $e) {
            debugging('Error restoring the lti gradebookservicescreating: ' . $e->getTraceAsString());
        }
    }

    /**
     * Processes one uncoupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_uncoupledgradeitemltiad($data) {
        global $DB;
        $data = (object)$data;
        // We will try to find a valid type in the system.
        $courseid = $this->task->get_courseid();
        $newtypeid = $this->find_typeid($data, $courseid);
        try {
            $sql = 'SELECT * FROM {grade_items} gi
                    INNER JOIN {ltiservice_gradebookservices} gbs ON gbs.id = gi.itemnumber
                    AND courseid =? and gbs.previousid=?';
            $conditions = array('courseid' => $courseid, 'previousid' => $data->itemnumber);
            // We will check if the record has been restored by a previous activity
            // and if not, we will restore it creating the right grade item and the
            // right entry in the ltiservice_gradebookservices table.
            if (!$DB->record_exists_sql($sql, $conditions)) {
                // Restore the lineitem.
                $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                        'toolproxyid' => null,
                        'ltilinkid' => null,
                        'typeid' => $newtypeid,
                        'baseurl' => $data->baseurl,
                        'tag' => $data->tag,
                        'previousid' => $data->itemnumber
                ));
                $oldid = $data->id;
                $params = array();
                $params['itemname'] = $data->itemname;
                $params['gradetype'] = GRADE_TYPE_VALUE;
                $params['grademax']  = $data->grademax;
                $params['grademin']  = $data->grademin;
                $item = new \grade_item(array('id' => 0, 'courseid' => $courseid));
                \grade_item::set_properties($item, $params);
                $item->itemtype = 'mod';
                $item->itemmodule = 'lti';
                $item->itemnumber = $gradebookservicesid;
                $item->idnumber = $data->idnumber;
                $id = $item->insert('mod/ltiservice_gradebookservices');
                $this->set_mapping('uncoupled_grade_item_ltiad', $oldid, $id);
            }
        } catch (\Exception $e) {
            debugging('Error restoring the lti gradebookservicescreating: ' . $e->getTraceAsString());
        }
    }

    /**
     * Processes the grades from the uncoupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_gradegradelti2($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $olduserid = $data->userid;

        $data->itemid = $this->get_mappingid('uncoupled_grade_item_lti2', $data->itemid, null);
        $data->userid = $this->get_mappingid('user', $data->userid, null);

        if (!empty($data->userid)) {
            $data->usermodified = $this->get_mappingid('user', $data->usermodified, null);
            $data->locktime     = $this->apply_date_offset($data->locktime);

            $gradeexists = $DB->record_exists('grade_grades', array('userid' => $data->userid, 'itemid' => $data->itemid));
            if ($gradeexists) {
                $message = "User id '{$data->userid}' already has a grade entry for grade item id '{$data->itemid}'";
                $this->log($message, backup::LOG_DEBUG);
            } else {
                $newitemid = $DB->insert_record('grade_grades', $data);
                $this->set_mapping('grade_grades', $oldid, $newitemid);
            }
        } else {
            $message = "Mapped user id not found for user id '{$olduserid}', grade item id '{$data->itemid}'";
            $this->log($message, backup::LOG_DEBUG);
        }
    }

    /**
     * Processes the grades from the uncoupled lineitem element
     * @param mixed $data
     * @return void
     */
    public function process_ltiservice_gradebookservices_gradegradeltiad($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $olduserid = $data->userid;

        $data->itemid = $this->get_mappingid('uncoupled_grade_item_ltiad', $data->itemid, null);
        $data->userid = $this->get_mappingid('user', $data->userid, null);

        if (!empty($data->userid)) {
            $data->usermodified = $this->get_mappingid('user', $data->usermodified, null);
            $data->locktime     = $this->apply_date_offset($data->locktime);

            $gradeexists = $DB->record_exists('grade_grades', array('userid' => $data->userid, 'itemid' => $data->itemid));
            if ($gradeexists) {
                $message = "User id '{$data->userid}' already has a grade entry for grade item id '{$data->itemid}'";
                $this->log($message, backup::LOG_DEBUG);
            } else {
                $newitemid = $DB->insert_record('grade_grades', $data);
                $this->set_mapping('grade_grades', $oldid, $newitemid);
            }
        } else {
            $message = "Mapped user id not found for user id '{$olduserid}', grade item id '{$data->itemid}'";
            $this->log($message, backup::LOG_DEBUG);
        }
    }

    /**
     * Find the better toolproxy that matches with the lineitem.
     * If none is found, then we set it to 0. Note this is
     * interim solution until MDL-34161 - Fix restore to support course/site tools & submissions
     * is implemented.
     *
     * @param mixed $data
     * @return integer $newtoolproxyid
     */
    private function find_proxy_id($data) {
        global $DB;
        $newtoolproxyid = 0;
        $oldtoolproxyguid = $data->guid;
        $oldtoolproxyvendor = $data->vendorcode;

        $dbtoolproxyjsonparams = array('guid' => $oldtoolproxyguid, 'vendorcode' => $oldtoolproxyvendor);
        $dbtoolproxy = $DB->get_field('lti_tool_proxies', 'id', $dbtoolproxyjsonparams, IGNORE_MISSING);
        if ($dbtoolproxy) {
            $newtoolproxyid = $dbtoolproxy;
        }
        return $newtoolproxyid;
    }

    /**
     * Find the better typeid that matches with the lineitem.
     * If none is found, then we set it to 0. Note this is
     * interim solution until MDL-34161 - Fix restore to support course/site tools & submissions
     * is implemented.
     *
     * @param mixed $data
     * @return integer $newtypeid
     */
    private function find_typeid($data, $courseid) {
        global $DB;
        $newtypeid = 0;
        $oldtypeid = $data->typeid;

        $dbtypeidparameter = array('id' => $oldtypeid, 'course' => $courseid);
        // We will check if the typeid is specific for the course.
        // And if not, we will check if is a tool for all moodle
        // If none of the previus we will return 0.
        $dbtype = $DB->get_field('lti_types', 'id', $dbtypeidparameter, IGNORE_MISSING);
        if ($dbtype) {
            $newtypeid = $dbtype;
        } else {
            $dbtypeidparameter = array('id' => $oldtypeid, 'course' => 1);
            $dbtype = $DB->get_field('lti_types', 'id', $dbtypeidparameter, IGNORE_MISSING);
            if ($dbtype) {
                $newtypeid = $dbtype;
            }
        }
        return $newtypeid;
    }

}
