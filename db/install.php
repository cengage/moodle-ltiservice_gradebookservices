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
 * Post installation and migration code.
 *
 * Contains code that are run during the installation of report/logs
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Contains codes to be run during installation of gradebookservices
 * This will make the sequence in lmsint_gradebookservices to start in 10001
 * and we will use that to differentiate them from outcomes, where the id is
 * hardcoded to 1000.
 *
 * @global moodle_database $DB
 * @return void
 */
function xmldb_ltiservice_gradebookservices_install() {
    global $DB;
    try {
        if ($DB->record_exists_select('ltiservice_gradebookservices', 'id > 9999')) {
            $success = true;
        } else {
            $dbfamily = $DB->get_dbfamily();
            if ($dbfamily === 'postgres') {
                $sql = 'ALTER SEQUENCE {ltiservice_gradebookservices_id_seq} RESTART WITH 10000';
                $DB->execute($sql);
            } else if ($dbfamily === 'oracle') {
                $prefix = strtoupper($DB->get_prefix());
                $sql = "SELECT sequence_name
                FROM user_sequences
                WHERE sequence_name LIKE ?";
                $rs = $DB->get_recordset_sql($sql, array($prefix.'LTISG%'));
                foreach ($rs as $seq) {
                    $sequencename = $seq->sequence_name;
                }
                $rs->close();
                $sql2 = 'ALTER SEQUENCE '.$sequencename.' INCREMENT BY 10000';
                $DB->execute($sql2);
                $sql3 = 'SELECT '.$sequencename.'.NEXTVAL FROM dual';
                $DB->execute($sql3);
                $sql4 = 'ALTER SEQUENCE '.$sequencename.' INCREMENT BY 1';
                $DB->execute($sql4);
            } else if ($dbfamily === 'mssql') {
                $sql = 'SET IDENTITY_INSERT {ltiservice_gradebookservices} ON
                         INSERT {ltiservice_gradebookservices}(id, toolproxyid) VALUES (10000, 1)
                         DELETE FROM {ltiservice_gradebookservices} WHERE id=10000
                         SET IDENTITY_INSERT {ltiservice_gradebookservices} OFF';
                $DB->execute($sql);
            } else {
                $params = array('id' => 10000, 'toolproxyid' => 1);
                $DB->insert_record_raw('ltiservice_gradebookservices', $params, false, false, true);
                $DB->delete_records('ltiservice_gradebookservices', $params);
            }
        }
    } catch (\Exception $e) {
        $success = false;
    }
}
