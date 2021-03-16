<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // https://www.kimai.org
 * (c) Kimai-Development-Team since 2006
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Provides the database layer with all functions to read and write data.
 */
class Kimai_Database_Mysql
{
    /**
     * Kimai Global Array
     *
     * @var Kimai_Config
     */
    protected $kga;

    /**
     * @var MySQL
     */
    protected $conn;

    /**
     * Instantiate a new database layer.
     * The provided kimai global array will be stored as a reference.
     *
     * @param Kimai_Config $kga
     * @param bool $autoConnect
     */
    public function __construct(&$kga, $autoConnect = true)
    {
        $this->kga = &$kga;
        if ($autoConnect) {
            $useUtf8 = false;
            if ($kga['server_charset'] === 'utf8') {
                $useUtf8 = true;
            }
            $this->connect(
                $kga['server_hostname'],
                $kga['server_database'],
                $kga['server_username'],
                $kga['server_password'],
                $useUtf8
            );
        }
    }

    /**
     * Connect to the database.
     *
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @param boolean $utf8
     */
    public function connect($host, $database, $username, $password, $utf8 = true)
    {
        if (isset($utf8) && $utf8) {
            $this->conn = new MySQL(true, $database, $host, $username, $password, "utf8");
        } else {
            $this->conn = new MySQL(true, $database, $host, $username, $password);
        }
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->conn->IsConnected();
    }

    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->conn->Error();
    }

    /**
     * @param string $scope
     */
    private function logLastError($scope)
    {
        Kimai_Logger::logfile($scope . ': ' . $this->conn->Error());
    }

    /**
     * @return bool
     */
    public function transaction_begin()
    {
        return $this->conn->TransactionBegin();
    }

    /**
     * @return bool
     */
    public function transaction_end()
    {
        return $this->conn->TransactionEnd();
    }

    /**
     * @return bool
     */
    public function transaction_rollback()
    {
        return $this->conn->TransactionRollback();
    }

    /**
     * Return the connection handler used to connect to the database.
     * This is currently required for extensions to access the database without
     * connecting again.
     */
    public function getConnectionHandler()
    {
        return $this->conn;
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->kga['server_prefix'];
    }

    /**
     * @return string table name including prefix
     */
    public function getProjectTable()
    {
        return $this->kga['server_prefix'] . 'projects';
    }

    /**
     * @return string table name including prefix
     */
    public function getProjectActivitiesTable()
    {
        return $this->kga['server_prefix'] . 'projects_activities';
    }

    /**
     * @return string table name including prefix
     */
    public function getGroupsProjectsTable()
    {
        return $this->kga['server_prefix'] . 'groups_projects';
    }

    /**
     * @return string table name including prefix
     */
    public function getGroupsActivitiesTable()
    {
        return $this->kga['server_prefix'] . 'groups_activities';
    }

    /**
     * @return string table name including prefix
     */
    public function getActivityTable()
    {
        return $this->kga['server_prefix'] . 'activities';
    }

    /**
     * @return string table name including prefix
     */
    public function getCustomerTable()
    {
        return $this->kga['server_prefix'] . 'customers';
    }

    /**
     * @return string table name including prefix
     */
    public function getTimeSheetTable()
    {
        return $this->kga['server_prefix'] . 'timeSheet';
    }

    /**
     * @return string table name including prefix
     */
    public function getExpenseTable()
    {
        return $this->kga['server_prefix'] . 'expenses';
    }

    /**
     * @return string table name including prefix
     */
    public function getUserTable()
    {
        return $this->kga['server_prefix'] . 'users';
    }

    /**
     * @return string table name including prefix
     */
    public function getGroupsUsersTable()
    {
        return $this->kga['server_prefix'] . 'groups_users';
    }

    /**
     * @return string table name including prefix
     */
    public function getPreferencesTable()
    {
        return $this->kga['server_prefix'] . 'preferences';
    }

    /**
     * @return string table name including prefix
     */
    public function getRatesTable()
    {
        return $this->kga['server_prefix'] . 'rates';
    }

    /**
     * @return string table name including prefix
     */
    public function getGroupsCustomersTable()
    {
        return $this->kga['server_prefix'] . 'groups_customers';
    }

    /**
     * Prepare all values of the array so it's save to put them into an sql query.
     * The conversion to utf8 is done here as well, if configured.
     *
     * This method is public since ki_expenses private database layers use it.
     *
     * @param array $data Array which values are being prepared.
     * @return array The same array, except all values are being escaped correctly.
     */
    public function clean_data($data)
    {
        $return = [];
        foreach ($data as $key => $value) {
            if ($key != "pw") {
                $return[$key] = urldecode(strip_tags($data[$key]));
                $return[$key] = str_replace('"', '_', $data[$key]);
                $return[$key] = str_replace("'", '_', $data[$key]);
                $return[$key] = str_replace('\\', '', $data[$key]);
            } else {
                $return[$key] = $data[$key];
            }
        }

        return $return;
    }

    /**
     * associates an Activity with a collection of Projects in the context of a user group.
     * Projects that are currently associated with the Activity but not mentioned in the specified id collection, will get un-assigned.
     * The fundamental difference to assign_activityToProjects(activityID, projectIDs) is that this method is aware of potentially existing assignments
     * that are invisible and thus unmanagable to the user as the user lacks access to the Projects.
     * It is implicitly assumed that the user has access to the Activity and the Projects designated by the method parameters.
     *
     * @param int $activityID the id of the Activity to associate
     * @param array $projectIDs the array of Project ids to associate
     * @param int $group the user's group id
     * @return bool
     */
    public function assignActivityToProjectsForGroup($activityID, $projectIDs, $group)
    {
        $projectIds = array_merge($projectIDs, $this->getNonManagableAssignedElementIds("activity", "project", $activityID, $group));
        return $this->assign_activityToProjects($activityID, $projectIds);
    }

    /**
     * associates a Project with a collection of Activities in the context of a user group.
     * Activities that are currently associated with the Project but not mentioned in the specified id collection, will get un-assigned.
     * The fundamental difference to assign_projectToActivities($projectID, $activityIDs) is that this method is aware of potentially existing assignments
     * that are invisible and thus unmanagable to the user as the user lacks access to the Activities.
     * It is implicitly assumed that the user has access to the Project and the Activities designated by the method parameters.
     *
     * @param int $projectID the id of the Project to associate
     * @param array $activityIDs the array of Activity ids to associate
     * @param int $group the user's group id
     * @return bool
     */
    public function assignProjectToActivitiesForGroup($projectID, $activityIDs, $group)
    {
        $activityIds = array_merge($activityIDs, $this->getNonManagableAssignedElementIds("project", "activity", $projectID, $group));
        return $this->assign_projectToActivities($projectID, $activityIds);
    }

    /**
     * computes an array of (project- or activity-) ids for Project-Activity-Assignments that are unmanage-able for the given group.
     * This method supports Project-Activity-Assignments as seen from both end points.
     * The returned array contains the ids of all those Projects or Activities that are assigned to Activities or Projects but cannot be seen by the user that
     * looks at the assignments.
     * @param string $parentSubject a string designating the parent in the assignment, must be one of "project" or "activity"
     * @param string $subject a string designating the child in the assignment, must be one of "project" or "activity"
     * @param int $parentId the id of the parent
     * @param int $group the id of the user's group
     * @return array the array of ids of those child Projects or Activities that are assigned to the parent Activity or Project but are invisible to the user
     */
    public function getNonManagableAssignedElementIds($parentSubject, $subject, $parentId, $group)
    {
        $resultIds = [];
        $selectedIds = [];
        $allElements = [];
        $viewableElements = [];
        switch ($parentSubject . "_" . $subject) {
            case 'project_activity':
                $selectedIds = $this->project_get_activities($parentId);
                break;
            case 'activity_project':
                $selectedIds = $this->activity_get_projectIds($parentId);
                break;
        }

        //if there are no assignments currently, there's nothing too much that could get deleted :)
        if (count($selectedIds) > 0) {
            switch ($parentSubject . "_" . $subject) {
                case 'project_activity':
                    $allElements = $this->get_activities();
                    $viewableElements = $this->get_activities($group);
                    break;
                case 'activity_project':
                    $allElements = $this->get_projects();
                    $viewableElements = $this->get_projects($group);
                    break;
            }
            //if there are no elements hidden from the group, there's nothing too much that could get deleted either
            if (count($allElements) > count($viewableElements)) {
                //1st, find the ids of the elements that are invisible for the group
                $startvisibleIds = [];
                $idField = $subject . "_ID";
                foreach ($allElements as $allElement) {
                    $seen = false;
                    foreach ($viewableElements as $viewableElement) {
                        if ($viewableElement[$idField] == $allElement[$idField]) {
                            $seen = true;
                            break; //element is viewable, so we can stop here
                        }
                    }
                    if (!$seen) {
                        $startvisibleIds[] = $allElement[$idField];
                    }
                }
                if (count($startvisibleIds) > 0) {
                    //2nd, find the invisible assigned elements and add them to the result array
                    foreach ($selectedIds as $selectedId) {
                        if (in_array($selectedId, $startvisibleIds)) {
                            $resultIds[] = $selectedId;
                        }
                    }
                }
            }
        }
        return $resultIds;
    }

    /**
     * Add a new customer to the database.
     *
     * @param array $data  name, address and other data of the new customer
     * @return int         the customerID of the new customer, false on failure
     */
    public function customer_create($data)
    {
        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);
        $values['comment'] = MySQL::SQLValue($data['comment']);
        if (isset($data['password'])) {
            $values['password'] = MySQL::SQLValue($data['password']);
        } else {
            $values['password'] = "''";
        }
        $values['company'] = MySQL::SQLValue($data['company']);
        $values['vat'] = MySQL::SQLValue($data['vat']);
        $values['contact'] = MySQL::SQLValue($data['contact']);
        $values['street'] = MySQL::SQLValue($data['street']);
        $values['zipcode'] = MySQL::SQLValue($data['zipcode']);
        $values['city'] = MySQL::SQLValue($data['city']);
        $values['country'] = MySQL::SQLValue($data['country']);
        $values['phone'] = MySQL::SQLValue($data['phone']);
        $values['fax'] = MySQL::SQLValue($data['fax']);
        $values['mobile'] = MySQL::SQLValue($data['mobile']);
        $values['mail'] = MySQL::SQLValue($data['mail']);
        $values['homepage'] = MySQL::SQLValue($data['homepage']);
        $values['timezone'] = MySQL::SQLValue($data['timezone']);

        $values['visible'] = MySQL::SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter'] = MySQL::SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table = $this->getCustomerTable();
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('customer_create');
            return false;
        } else {
            return $this->conn->GetLastInsertID();
        }
    }

    /**
     * Returns the data of a customer
     *
     * @param int $customerID  id of the customer
     * @return array the customer's data, false on failure
     */
    public function customer_get_data($customerID)
    {
        $filter['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getCustomerTable();
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('customer_get_data');
            return false;
        }

        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * Edits a customer by replacing his data by the new array
     *
     * @param int $customerID  id of the customer to be edited
     * @param array $data    name, address and other new data of the customer
     * @return boolean       true on success, false on failure
     */
    public function customer_edit($customerID, $data)
    {
        $data = $this->clean_data($data);

        $values = [];

        $strings = [
            'name',
            'comment',
            'password',
            'company',
            'vat',
            'contact',
            'street',
            'zipcode',
            'city',
            'country',
            'phone',
            'fax',
            'mobile',
            'mail',
            'homepage',
            'timezone',
            'passwordResetHash'
        ];
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key]);
            }
        }

        $numbers = ['visible', 'filter'];
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);

        $table = $this->getCustomerTable();
        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * Assigns a customer to 1-n groups by adding entries to the cross table
     *
     * @param int $customerID     id of the customer to which the groups will be assigned
     * @param array $groupIDs    contains one or more groupIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_customerToGroups($customerID, $groupIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_customerToGroups');
            return false;
        }

        $table = $this->getGroupsCustomersTable();
        $filter['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_customerToGroups');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_customerToGroups');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_customerToGroups');
            return false;
        }
    }

    /**
     * returns all IDs of the groups of the given customer
     *
     * @param int $customerID  id of the customer
     * @return array         contains the groupIDs of the groups or false on error
     */
    public function customer_get_groupIDs($customerID)
    {
        $filter['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "groupID";
        $table = $this->getGroupsCustomersTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            return false;
        }

        $groupIDs = [];
        $counter = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
                $counter++;
            }
            return $groupIDs;
        } else {
            $this->logLastError('customer_get_groupIDs');
            return false;
        }
    }

    /**
     * deletes a customer
     *
     * @param int $customerID  id of the customer
     * @return boolean       true on success, false on failure
     */
    public function customer_delete($customerID)
    {
        $values['trash'] = 1;
        $filter['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getCustomerTable();

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);
        return $this->conn->Query($query);
    }

    /**
     * Adds a new project
     *
     * @param array $data  name, comment and other data of the new project
     * @return int         the ID of the new project, false on failure
     */
    public function project_create($data)
    {
        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);
        $values['comment'] = MySQL::SQLValue($data['comment']);
        $values['budget'] = MySQL::SQLValue($data['budget'], MySQL::SQLVALUE_NUMBER);
        $values['effort'] = MySQL::SQLValue($data['effort'], MySQL::SQLVALUE_NUMBER);
        $values['approved'] = MySQL::SQLValue($data['approved'], MySQL::SQLVALUE_NUMBER);
        $values['customerID'] = MySQL::SQLValue($data['customerID'], MySQL::SQLVALUE_NUMBER);
        $values['visible'] = MySQL::SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['internal'] = MySQL::SQLValue($data['internal'], MySQL::SQLVALUE_NUMBER);
        $values['filter'] = MySQL::SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table = $this->getProjectTable();
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('project_create');
            return false;
        }

        $projectID = $this->conn->GetLastInsertID();

        if (isset($data['defaultRate'])) {
            if (is_numeric($data['defaultRate'])) {
                $this->save_rate(null, $projectID, null, $data['defaultRate']);
            } else {
                $this->remove_rate(null, $projectID, null);
            }
        }

        if (isset($data['myRate'])) {
            if (is_numeric($data['myRate'])) {
                $this->save_rate($this->kga['user']['userID'], $projectID, null, $data['myRate']);
            } else {
                $this->remove_rate($this->kga['user']['userID'], $projectID, null);
            }
        }

        if (isset($data['fixedRate'])) {
            if (is_numeric($data['fixedRate'])) {
                $this->save_fixed_rate($projectID, null, $data['fixedRate']);
            } else {
                $this->remove_fixed_rate($projectID, null);
            }
        }

        return $projectID;
    }

    /**
     * Returns the data of a certain project
     *
     * @param int $projectID ID of the project

     * @return array         the project's data (name, comment etc) as array, false on failure
     */
    public function project_get_data($projectID)
    {
        if (!is_numeric($projectID)) {
            return false;
        }

        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getProjectTable();
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('project_get_data');
            return false;
        }

        $result_array = $this->conn->RowArray(0, MYSQLI_ASSOC);
        $result_array['defaultRate'] = $this->get_rate(null, $projectID, null);
        $result_array['myRate'] = $this->get_rate($this->kga['user']['userID'], $projectID, null);
        $result_array['fixedRate'] = $this->get_fixed_rate($projectID, null);
        return $result_array;
    }

    /**
     * Edits a project by replacing its data by the new array
     *
     * @param int $projectID   id of the project to be edited
     * @param array $data     name, comment and other new data of the project
     * @return boolean        true on success, false on failure
     */
    public function project_edit($projectID, $data)
    {
        $data = $this->clean_data($data);
        $values = [];
        $strings = ['name', 'comment'];
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key]);
            }
        }

        $numbers = ['budget', 'customerID', 'visible', 'internal', 'filter', 'effort', 'approved'];
        foreach ($numbers as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            if ($data[$key] == null) {
                $values[$key] = 'NULL';
            } else {
                $values[$key] = MySQL::SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getProjectTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('project_edit');
            return false;
        }

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        if ($this->conn->Query($query)) {
            if (isset($data['defaultRate'])) {
                if (is_numeric($data['defaultRate'])) {
                    $this->save_rate(null, $projectID, null, $data['defaultRate']);
                } else {
                    $this->remove_rate(null, $projectID, null);
                }
            }

            if (isset($data['myRate'])) {
                if (is_numeric($data['myRate'])) {
                    $this->save_rate($this->kga['user']['userID'], $projectID, null, $data['myRate']);
                } else {
                    $this->remove_rate($this->kga['user']['userID'], $projectID, null);
                }
            }

            if (isset($data['fixedRate'])) {
                if (is_numeric($data['fixedRate'])) {
                    $this->save_fixed_rate($projectID, null, $data['fixedRate']);
                } else {
                    $this->remove_fixed_rate($projectID, null);
                }
            }

            if (!$this->conn->TransactionEnd()) {
                $this->logLastError('project_edit');
                return false;
            }
            return true;
        } else {
            $this->logLastError('project_edit');
            if (!$this->conn->TransactionRollback()) {
                $this->logLastError('project_edit');
                return false;
            }
            return false;
        }
    }

    /**
     * Assigns a project to 1-n groups by adding entries to the cross table
     *
     * @param int $projectID        ID of the project to which the groups will be assigned
     * @param array $groupIDs    contains one or more groupIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_projectToGroups($projectID, $groupIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_projectToGroups');
            return false;
        }

        $table = $this->getGroupsProjectsTable();
        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToGroups');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToGroups');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_projectToGroups');
            return false;
        }
    }

    /**
     * returns all the groups of the given project
     *
     * @param array $projectID  ID of the project
     * @return array         contains the groupIDs of the groups or false on error
     */
    public function project_get_groupIDs($projectID)
    {
        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "groupID";
        $table = $this->getGroupsProjectsTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('project_get_groupIDs');
            return false;
        }

        $groupIDs = [];
        $counter = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
                $counter++;
            }
            return $groupIDs;
        } else {
            return false;
        }
    }

    /**
     * deletes a project
     *
     * @param array $projectID  ID of the project
     * @return boolean       true on success, false on failure
     */
    public function project_delete($projectID)
    {
        $values['trash'] = 1;
        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getProjectTable();

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);
        return $this->conn->Query($query);
    }

    /**
     * Adds a new activity
     *
     * @param array $data name, comment and other data of the new activity
     * @param array $activityGroups
     * @return int the activityID of the new project, false on failure
     */
    public function activity_create($data, $activityGroups)
    {
        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);
        $values['comment'] = MySQL::SQLValue($data['comment']);
        $values['visible'] = MySQL::SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter'] = MySQL::SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table = $this->getActivityTable();
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('activity_create');
            return false;
        }

        $activityID = $this->conn->GetLastInsertID();

        if (isset($data['defaultRate'])) {
            if (is_numeric($data['defaultRate'])) {
                foreach ($activityGroups as $activityGroup) {
                    $this->save_rate(null, $activityGroup, $activityID, $data['defaultRate']);
                }
            } else {
                foreach ($activityGroups as $activityGroup) {
                    $this->remove_rate(null, $activityGroup, $activityID);
                }
            }
        }

        if (isset($data['myRate'])) {
            if (is_numeric($data['myRate'])) {
                foreach ($activityGroups as $activityGroup) {
                    $this->save_rate($this->kga['user']['userID'], $activityGroup, $activityID, $data['myRate']);
                }
            } else {
                foreach ($activityGroups as $activityGroup) {
                    $this->remove_rate($this->kga['user']['userID'], $activityGroup, $activityID);
                }
            }
        }

        if (isset($data['fixedRate'])) {
            if (is_numeric($data['fixedRate'])) {
                $this->save_fixed_rate(null, $activityID, $data['fixedRate']);
            } else {
                $this->remove_fixed_rate(null, $activityID);
            }
        }

        return $activityID;
    }

    /**
     * Returns the data of a certain activity
     *
     * @param array $activityID  activityID of the project
     * @return array         the activity's data (name, comment etc) as array, false on failure
     */
    public function activity_get_data($activityID)
    {
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getActivityTable();
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('activity_get_data');
            return false;
        }

        $result_array = $this->conn->RowArray(0, MYSQLI_ASSOC);

        $result_array['defaultRate'] = $this->get_rate(null, null, $result_array['activityID']);
        $result_array['myRate'] = $this->get_rate($this->kga['user']['userID'], null, $result_array['activityID']);
        $result_array['fixedRate'] = $this->get_fixed_rate(null, $result_array['activityID']);

        return $result_array;
    }

    /**
     * Edits an activity by replacing its data by the new array
     *
     * @param array $activityID activityID of the project to be edited
     * @param array $data name, comment and other new data of the activity
     * @param array $activityGroups
     * @return bool true on success, false on failure
     */
    public function activity_edit($activityID, $data, $activityGroups)
    {
        $data = $this->clean_data($data);
        $values = [];
        $strings = ['name', 'comment'];
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key]);
            }
        }

        $numbers = ['visible', 'filter'];
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getActivityTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('activity_edit');
            return false;
        }

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        if ($this->conn->Query($query)) {
            if (isset($data['defaultRate'])) {
                if (is_numeric($data['defaultRate'])) {
                    $this->save_rate(null, null, $activityID, $data['defaultRate']);
                } else {
                    $this->remove_rate(null, null, $activityID);
                }
            }

            if (isset($data['myRate'])) {
                if (is_numeric($data['myRate'])) {
                    $this->save_rate($this->kga['user']['userID'], null, $activityID, $data['myRate']);
                } else {
                    $this->remove_rate($this->kga['user']['userID'], null, $activityID);
                }
            }

            if (isset($data['fixedRate'])) {
                if (is_numeric($data['fixedRate'])) {
                    foreach ($activityGroups as $activityGroup) {
                        $this->save_fixed_rate($activityGroup, $activityID, $data['fixedRate']);
                    }
                } else {
                    foreach ($activityGroups as $activityGroup) {
                        $this->remove_fixed_rate($activityGroup, $activityID);
                    }
                }
            }

            if (!$this->conn->TransactionEnd()) {
                $this->logLastError('activity_edit');
                return false;
            }
            return true;
        } else {
            $this->logLastError('activity_edit');
            if (!$this->conn->TransactionRollback()) {
                $this->logLastError('activity_edit');
                return false;
            }
            return false;
        }
    }

    /**
     * Assigns an activity to 1-n groups by adding entries to the cross table
     *
     * @param int $activityID         activityID of the project to which the groups will be assigned
     * @param array $groupIDs    contains one or more groupIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_activityToGroups($activityID, $groupIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_activityToGroups');
            return false;
        }

        $table = $this->getGroupsActivitiesTable();
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToGroups');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToGroups');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_activityToGroups');
            return false;
        }
    }

    /**
     * Assigns an activity to 1-n projects by adding entries to the cross table
     *
     * @param int $activityID         id of the activity to which projects will be assigned
     * @param array $projectIDs    contains one or more projectIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_activityToProjects($activityID, $projectIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_activityToProjects');
            return false;
        }

        $table = $this->getProjectActivitiesTable();
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToProjects');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToProjects');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_activityToProjects');
            return false;
        }
    }

    /**
     * Assigns 1-n activities to a project by adding entries to the cross table
     *
     * @param int $projectID         id of the project to which activities will be assigned
     * @param array $activityIDs    contains one or more activityIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_projectToActivities($projectID, $activityIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_projectToActivities');
            return false;
        }

        $table = $this->getProjectActivitiesTable();
        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToActivities');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $values['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToActivities');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_projectToActivities');
            return false;
        }
    }

    /**
     * returns all the projects to which the activity was assigned
     *
     * @param int $activityId  activityId of the project
     * @return array         contains the IDs of the projects or false on error
     */
    public function activity_get_projects($activityId)
    {
        $activityId = MySQL::SQLValue($activityId, MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        $query = "SELECT project.*, customer.name as customer_name, customer.visible as customerVisible
                FROM ${p}projects_activities
                JOIN ${p}projects AS project USING (projectID)
                JOIN ${p}customers AS customer USING (customerID)
                WHERE activityID = $activityId AND project.trash=0";

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('activity_get_projects');
            return false;
        }

        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * returns all the project ids to which the activity was assigned
     *
     * @param int $activityID  activityID of the project
     * @return array         contains the IDs of the projects or false on error
     */
    public function activity_get_projectIds($activityID)
    {
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "projectID";
        $table = $this->getProjectActivitiesTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_projectIds');
            return false;
        }

        $projectIDs = [];
        $counter = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $projectIDs[$counter] = $row['projectID'];
                $counter++;
            }
        }
        return $projectIDs;
    }

    /**
     * returns all the group ids of the given activity
     *
     * @param int $activityID  ID of the activity
     * @return array         contains the groupIDs of the groups or false on error
     */
    public function activity_get_groupIDs($activityID)
    {
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "groupID";
        $table = $this->getGroupsActivitiesTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groupIDs');
            return false;
        }

        $groupIDs = [];
        $counter = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
                $counter++;
            }
            return $groupIDs;
        } else {
            return false;
        }
    }

    /**
     * update the data for activity per project, which is budget, approved and effort
     *
     * @param int $projectID
     * @param int $activityID
     * @param array $data
     * @return bool
     */
    public function project_activity_edit($projectID, $activityID, $data)
    {
        $data = $this->clean_data($data);

        $filter['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getProjectActivitiesTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('project_activity_edit [1]');
            return false;
        }

        $query = MySQL::BuildSQLUpdate($table, $data, $filter);
        if ($this->conn->Query($query)) {
            if (!$this->conn->TransactionEnd()) {
                $this->logLastError('project_activity_edit [2]');
                return false;
            }
            return true;
        }

        $this->logLastError('project_activity_edit [3]');

        if (!$this->conn->TransactionRollback()) {
            $this->logLastError('project_activity_edit [4]');
            return false;
        }
        return false;
    }

    /**
     * returns all the activities which were assigned to a project
     *
     * @param int $projectID ID of the project
     *
     * @return bool|array contains the activityIDs of the activities or false on error
     */
    public function project_get_activities($projectID)
    {
        $projectId = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        $query = "SELECT activity.*, activityID, budget, effort, approved
                FROM ${p}projects_activities AS p_a
                JOIN ${p}activities AS activity USING(activityID)
                WHERE projectID = $projectId AND activity.trash=0;";

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('project_get_activities');
            return false;
        }

        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * returns all the activity ids which were assigned to a project
     *
     * @param int $projectID  ID of the project
     * @return array         contains the activityIDs of the activities or false on error
     */
    public function project_get_activityIDs($projectID)
    {
        $projectId = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        $query = "SELECT activityID
                FROM ${p}projects_activities AS p_a
                JOIN ${p}activities AS activity USING(activityID)
                WHERE projectID = $projectId AND activity.trash=0;";

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('project_get_activityIDs');
            return false;
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        $activityIDs = [];
        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $activityIDs[$row['activityID']] = $row['activityID'];
            }
        }
        return $activityIDs;
    }

    /**
     * returns all the groups of the given activity
     *
     * @param array $activityID  activityID of the project
     * @return array         contains the groupIDs of the groups or false on error
     */
    public function activity_get_groups($activityID)
    {
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "groupID";
        $table = $this->getGroupsActivitiesTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groups');
            return false;
        }

        $groupIDs = [];
        $counter = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($this->conn->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
                $counter++;
            }
            return $groupIDs;
        } else {
            return false;
        }
    }

    /**
     * deletes an activity
     *
     * @param array $activityID  activityID of the activity
     * @return boolean       true on success, false on failure
     */
    public function activity_delete($activityID)
    {
        $values['trash'] = 1;
        $filter['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getActivityTable();

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);
        return $this->conn->Query($query);
    }

    /**
     * Assigns a group to 1-n customers by adding entries to the cross table
     * (counterpart to assign_customerToGroups)
     *
     * @param array $groupID      ID of the group to which the customers will be assigned
     * @param array $customerIDs  contains one or more IDs of customers
     * @return boolean            true on success, false on failure
     */
    public function assign_groupToCustomers($groupID, $customerIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_groupToCustomers');
            return false;
        }

        $table = $this->getGroupsCustomersTable();
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);

        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToCustomers');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($customerIDs as $customerID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customerID'] = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToCustomers');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_groupToCustomers');
            return false;
        }
    }

    /**
     * Assigns a group to 1-n projects by adding entries to the cross table
     * (counterpart to assign_projectToGroups)
     *
     * @param array $groupID        groupID of the group to which the projects will be assigned
     * @param array $projectIDs    contains one or more project IDs
     * @return boolean            true on success, false on failure
     */
    public function assign_groupToProjects($groupID, $projectIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_groupToProjects');
            return false;
        }

        $table = $this->getGroupsProjectsTable();
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToProjects');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['projectID'] = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToProjects');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_groupToProjects');
            return false;
        }
    }

    /**
     * Assigns a group to 1-n activities by adding entries to the cross table
     * (counterpart to assign_activityToGroups)
     *
     * @param array $groupID        groupID of the group to which the activities will be assigned
     * @param array $activityIDs    contains one or more activityIDs
     * @return boolean            true on success, false on failure
     */
    public function assign_groupToActivities($groupID, $activityIDs)
    {
        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('assign_groupToActivities');
            return false;
        }

        $table = $this->getGroupsActivitiesTable();
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query = MySQL::BuildSQLDelete($table, $filter);
        $d_result = $this->conn->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToActivities');
            $this->conn->TransactionRollback();
            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query = MySQL::BuildSQLInsert($table, $values);
            $result = $this->conn->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToActivities');
                $this->conn->TransactionRollback();
                return false;
            }
        }

        if ($this->conn->TransactionEnd() == true) {
            return true;
        } else {
            $this->logLastError('assign_groupToActivities');
            return false;
        }
    }

    /**
     * Adds a new user
     *
     * @param array $data  username, email, and other data of the new user
     * @return boolean|int     false on failure, otherwise the new user id
     */
    public function user_create($data)
    {
        // find random but unused user id
        do {
            $data['userID'] = random_number(9);
        } while ($this->user_get_data($data['userID']));

        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);
        $values['userID'] = MySQL::SQLValue($data['userID'], MySQL::SQLVALUE_NUMBER);
        $values['globalRoleID'] = MySQL::SQLValue($data['globalRoleID'], MySQL::SQLVALUE_NUMBER);
        $values['active'] = MySQL::SQLValue($data['active'], MySQL::SQLVALUE_NUMBER);

        // 'mail' and 'password' are just set when actually provided because of compatibility reasons
        if (array_key_exists('mail', $data)) {
            $values['mail'] = MySQL::SQLValue($data['mail']);
        }

        if (array_key_exists('password', $data)) {
            $values['password'] = MySQL::SQLValue($data['password']);
        }

        $table = $this->kga['server_prefix'] . "users";
        $result = $this->conn->InsertRow($table, $values);

        if ($result === false) {
            $this->logLastError('user_create');
            return false;
        }

        if (isset($data['rate'])) {
            if (is_numeric($data['rate'])) {
                $this->save_rate($data['userID'], null, null, $data['rate']);
            } else {
                $this->remove_rate($data['userID'], null, null);
            }
        }

        return $data['userID'];
    }

    /**
     * Returns the data of a certain user
     *
     * @param string $userID  ID of the user
     * @return array         the user's data (username, email-address, status etc) as array, false on failure
     */
    public function user_get_data($userID)
    {
        $filter['userID'] = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "users";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('user_get_data');
            return false;
        }

        // return  $this->conn->getHTML();
        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * Edits a user by replacing his data and preferences by the new array
     *
     * @param int $userID  userID of the user to be edited
     * @param array $data    username, email, and other new data of the user
     * @return boolean       true on success, false on failure
     */
    public function user_edit($userID, $data)
    {
        $data = $this->clean_data($data);
        $strings = ['name', 'mail', 'alias', 'password', 'apikey', 'passwordResetHash'];
        $values = [];

        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key]);
            }
        }

        $numbers = ['trash', 'active', 'lastProject', 'lastActivity', 'lastRecord', 'globalRoleID'];
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = MySQL::SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['userID'] = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getUserTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('user_edit transaction begin');
            return false;
        }

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        if ($this->conn->Query($query)) {
            if (isset($data['rate'])) {
                if (is_numeric($data['rate'])) {
                    $this->save_rate($userID, null, null, $data['rate']);
                } else {
                    $this->remove_rate($userID, null, null);
                }
            }

            if (!$this->conn->TransactionEnd()) {
                $this->logLastError('user_edit transaction end');
                return false;
            }

            return true;
        }

        $this->logLastError('user_edit failed');

        if (!$this->conn->TransactionRollback()) {
            $this->logLastError('user_edit rollback');
            return false;
        }

        return false;
    }

    /**
     * deletes a user
     *
     * @param int $userID  userID of the user
     * @param boolean $moveToTrash whether to delete user or move to trash
     * @return boolean       true on success, false on failure
     */
    public function user_delete($userID, $moveToTrash = false)
    {
        $userID = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);

        if ($moveToTrash) {
            $values['trash'] = 1;
            $filter['userID'] = $userID;
            $table = $this->kga['server_prefix'] . "users";

            $query = MySQL::BuildSQLUpdate($table, $values, $filter);
            return $this->conn->Query($query);
        }

        // if the user should be deleted completely, get rid of all its data from the DB
        $deleteAll = [
            $this->getGroupsUsersTable() => 'groups_user_delete',
            $this->getPreferencesTable() => 'preferences_delete',
            $this->getRatesTable() => 'rates_delete',
            $this->getUserTable() => 'user_delete',

            // we should keep the following data for historical reasons!
            //$this->getTimeSheetTable() => 'timeSheet_delete'
            //$this->getExpenseTable() => 'expense_delete'
        ];

        foreach ($deleteAll as $tableName => $logMsg) {
            $query  = "DELETE FROM " . $tableName . " WHERE userID = " . $userID;
            $result = $this->conn->Query($query);
            if ($result === false) {
                $this->logLastError($logMsg);
                return false;
            }
        }

        return true;
    }

    /**
     * Get a preference for a user. If no user ID is given the current user is used.
     *
     * @param string  $key     name of the preference to fetch
     * @param int $userId  (optional) id of the user to fetch the preference for
     * @return string value of the preference or null if there is no such preference
     */
    protected function user_get_preference($key, $userId = null)
    {
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $table = $this->getPreferencesTable();
        $userId = MySQL::SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $key2 = MySQL::SQLValue($key);

        $query = "SELECT `value` FROM $table WHERE userID = $userId AND `option` = $key2";

        $this->conn->Query($query);

        if ($this->conn->RowCount() == 1) {
            $row = $this->conn->RowArray(0, MYSQLI_NUM);
            return $row[0];
        }

        return null;
    }

    /**
     * Get several preferences for a user. If no user ID is given the current user is used.
     *
     * @param array   $keys    names of the preference to fetch in an array
     * @param int $userId  (optional) id of the user to fetch the preference for
     * @return array  with keys for every found preference and the found value
     */
    public function user_get_preferences(array $keys, $userId = null)
    {
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $table = $this->kga['server_prefix'] . "preferences";
        $userId = MySQL::SQLValue($userId, MySQL::SQLVALUE_NUMBER);

        $preparedKeys = [];
        foreach ($keys as $key) {
            $preparedKeys[] = MySQL::SQLValue($key);
        }

        $keysString = implode(",", $preparedKeys);

        $query = "SELECT `option`,`value` FROM $table WHERE userID = $userId AND `option` IN ($keysString)";

        $this->conn->Query($query);

        $preferences = [];

        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->RowArray();
            $preferences[$row['option']] = $row['value'];
        }

        return $preferences;
    }

    /**
     * Get several preferences for a user which have a common prefix. The returned preferences are striped off
     * the prefix.
     * If no user ID is given the current user is used.
     *
     * @param string  $prefix   prefix all preferenc keys to fetch have in common
     * @param int $userId  (optional) id of the user to fetch the preference for
     * @return array  with keys for every found preference and the found value
     */
    public function user_get_preferences_by_prefix($prefix, $userId = null)
    {
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $prefixLength = strlen($prefix);

        $table = $this->getPreferencesTable();
        $userId = MySQL::SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $prefix = MySQL::SQLValue($prefix . '%');

        $query = "SELECT `option`,`value` FROM $table WHERE userID = $userId AND `option` LIKE $prefix";
        $this->conn->Query($query);

        $preferences = [];

        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->RowArray();
            $key = substr($row['option'], $prefixLength);
            $preferences[$key] = $row['value'];
        }

        return $preferences;
    }

    /**
     * Save one or more preferences for a user. If no user ID is given the current user is used.
     * The array has to assign every preference key a value to store.
     * Example: array ( 'setting1' => 'value1', 'setting2' => 'value2');
     *
     * A prefix can be specified, which will be prepended to every preference key.
     *
     * @param array   $data   key/value pairs to store
     * @param string  $prefix prefix for all preferences
     * @param int $userId (optional) id of another user than the current
     * @return boolean        true on success, false on failure
     */
    public function user_set_preferences(array $data, $prefix = '', $userId = null)
    {
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('user_set_preferences');
            return false;
        }

        $table = $this->kga['server_prefix'] . "preferences";

        $filter['userID'] = MySQL::SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $values['userID'] = $filter['userID'];
        foreach ($data as $key=>$value) {
            $values['option'] = MySQL::SQLValue($prefix . $key);
            $values['value'] = MySQL::SQLValue($value);
            $filter['option'] = $values['option'];

            $this->conn->AutoInsertUpdate($table, $values, $filter);
        }

        return $this->conn->TransactionEnd();
    }

    /**
     * Adds a new group
     *
     * @param array $data  name and other data of the new group
     * @return int         the groupID of the new group, false on failure
     */
    public function group_create($data)
    {
        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);
        $table = $this->kga['server_prefix'] . "groups";
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('group_create');
            return false;
        } else {
            return $this->conn->GetLastInsertID();
        }
    }

    /**
     * Returns the data of a certain group
     *
     * @param array $groupID  groupID of the group
     * @return array         the group's data (name, etc) as array, false on failure
     */
    public function group_get_data($groupID)
    {
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "groups";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('group_get_data');
            return false;
        } else {
            return $this->conn->RowArray(0, MYSQLI_ASSOC);
        }
    }

    /**
     * Returns the data of a certain status
     *
     * @param array $statusID  ID of the group
     * @return array         	 the group's data (name) as array, false on failure
     */
    public function status_get_data($statusID)
    {
        $filter['statusID'] = MySQL::SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "statuses";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('status_get_data');
            return false;
        } else {
            return $this->conn->RowArray(0, MYSQLI_ASSOC);
        }
    }

    /**
     * Returns the number of users in a certain group
     *
     * @param array $groupID   groupID of the group
     * @return int            the number of users in the group
     */
    public function group_count_users($groupID)
    {
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "groups_users";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('group_count_data');
            return false;
        }

        return $this->conn->RowCount() === false ? 0 : $this->conn->RowCount();
    }

    /**
     * Returns the number of time sheet entries with a certain status
     *
     * @param int $statusID   ID of the status
     * @return int            		the number of timesheet entries with this status
     */
    public function status_timeSheetEntryCount($statusID)
    {
        $filter['statusID'] = MySQL::SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table = $this->getTimeSheetTable();
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('status_timeSheetEntryCount');
            return false;
        }

        return $this->conn->RowCount() === false ? 0 : $this->conn->RowCount();
    }

    /**
     * Edits a group by replacing its data by the new array
     *
     * @param array $groupID  groupID of the group to be edited
     * @param array $data    name and other new data of the group
     * @return boolean       true on success, false on failure
     */
    public function group_edit($groupID, $data)
    {
        $data = $this->clean_data($data);

        $values['name'] = MySQL::SQLValue($data['name']);

        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "groups";

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * Edits a status by replacing its data by the new array
     *
     * @param array $statusID  groupID of the status to be edited
     * @param array $data    name and other new data of the status
     * @return boolean       true on success, false on failure
     */
    public function status_edit($statusID, $data)
    {
        $data = $this->clean_data($data);

        $values['status'] = MySQL::SQLValue($data['status']);

        $filter['statusID'] = MySQL::SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "statuses";

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * Set the groups in which the user is a member in.
     * @param int $userId   id of the user
     * @param array $groups  map from group ID to membership role ID
     * @return false|null       true on success, false on failure
     */
    public function setGroupMemberships($userId, array $groups = null, $deleteCurrentGroupUsers = true)
    {
        $table = $this->getGroupsUsersTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('setGroupMemberships');
            return false;
        }

        $data['userID'] = MySQL::SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        if ($deleteCurrentGroupUsers) {
            $result = $this->conn->DeleteRows($table, $data);

            if (!$result) {
                $this->logLastError('setGroupMemberships');
                if (!$this->conn->TransactionRollback()) {
                    $this->logLastError('setGroupMemberships_rollback');
                }
                return false;
            }
        }

        foreach ($groups as $group => $role) {
            $data['groupID'] = MySQL::SQLValue($group, MySQL::SQLVALUE_NUMBER);

            // Check whether a row for userId and groupID already exists
            $columns[] = "groupID";
            $result = $this->conn->SelectRows($table, $data, $columns);
            if ($result === false) {
                $this->logLastError('setGroupMemberships');
                return false;
            }

            if (!$this->conn->RowCount()) {
                // no row for userId and groupID exists
                $data['membershipRoleID'] = MySQL::SQLValue($role, MySQL::SQLVALUE_NUMBER);
                $result = $this->conn->InsertRow($table, $data);
                if ($result === false) {
                    $this->logLastError('setGroupMemberships');
                    if (!$this->conn->TransactionRollback()) {
                        $this->logLastError('setGroupMemberships_rollback');
                    }
                    return false;
                }
                // remove membershipRoleID from $data array so it is not part of next userId, groupID existing row check
                unset($data['membershipRoleID']);
            }
        }

        if (!$this->conn->TransactionEnd()) {
            $this->logLastError('setGroupMemberships');
            return false;
        }

        return true;
    }

    /**
     * Get the groups in which the user is a member in.
     * @param int $userId   id of the user
     * @return array        list of group ids
     */
    public function getGroupMemberships($userId)
    {
        $filter['userID'] = MySQL::SQLValue($userId);
        $columns[] = "groupID";
        $table = $this->getGroupsUsersTable();
        $result = $this->conn->SelectRows($table, $filter, $columns);

        if (!$result) {
            $this->logLastError('getGroupMemberships');
            return null;
        }

        $arr = [];
        if ($this->conn->RowCount()) {
            $this->conn->MoveFirst();
            while (!$this->conn->EndOfSeek()) {
                $row = $this->conn->Row();
                $arr[] = $row->groupID;
            }
        }
        return $arr;
    }

    /**
     * deletes a group
     *
     * @param array $groupID  groupID of the group
     * @return boolean       true on success, false on failure
     */
    public function group_delete($groupID)
    {
        $values['trash'] = 1;
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "groups";
        $query = MySQL::BuildSQLUpdate($table, $values, $filter);
        return $this->conn->Query($query);
    }

    /**
     * deletes a status
     *
     * @param array $statusID  statusID of the status
     * @return boolean       	 true on success, false on failure
     */
    public function status_delete($statusID)
    {
        $filter['statusID'] = MySQL::SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "statuses";
        $query = MySQL::BuildSQLDelete($table, $filter);
        return $this->conn->Query($query);
    }

    /**
     * Edits a configuration variables by replacing the data by the new array
     *
     * @param array $data    variables array
     * @return boolean       true on success, false on failure
     */
    public function configuration_edit($data)
    {
        $data = $this->clean_data($data);

        $table = $this->kga['server_prefix'] . "configuration";

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('configuration_edit');
            return false;
        }

        foreach ($data as $key => $value) {
            $filter['option'] = MySQL::SQLValue($key);
            $values['value'] = MySQL::SQLValue($value);

            $query = MySQL::BuildSQLUpdate($table, $values, $filter);

            $result = $this->conn->Query($query);

            if ($result === false) {
                $this->logLastError('configuration_edit');
                return false;
            }
        }

        if (!$this->conn->TransactionEnd()) {
            $this->logLastError('configuration_edit');
            return false;
        }

        return true;
    }

    /**
     * Returns a list of IDs of all current recordings.
     *
     * @param int $userID ID of user in table users
     * @return array with all IDs of current recordings. This array will be empty if there are none.
     */
    public function get_current_recordings($userID)
    {
        $table = $this->getTimeSheetTable();
        $userID = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $result = $this->conn->Query("SELECT timeEntryID FROM $table WHERE userID = $userID AND start > 0 AND end = 0");

        if ($result === false) {
            $this->logLastError('get_current_recordings');
            return [];
        }

        $IDs = [];

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            $IDs[] = $row->timeEntryID;
        }

        return $IDs;
    }

    /**
     * Return the latest running entry with all information required for the buzzer.
     *
     * @return array with all data
     */
    public function get_latest_running_entry()
    {
        $table = $this->getTimeSheetTable();
        $projectTable = $this->getProjectTable();
        $activityTable = $this->getActivityTable();
        $customerTable = $this->getCustomerTable();

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customerID AS customerID
          FROM $table
              JOIN $projectTable USING(projectID)
              JOIN $customerTable USING(customerID)
              JOIN $activityTable USING(activityID)";

        $result = $this->conn->Query("$select WHERE end = 0 AND userID = " . $this->kga['user']['userID'] . " ORDER BY timeEntryID DESC LIMIT 1");

        if (!$result) {
            return null;
        }
        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * Returns the data of a certain time record
     *
     * @param int $timeEntryID  timeEntryID of the record
     * @return array         the record's data (time, activity id, project id etc) as array, false on failure
     */
    public function timeSheet_get_data($timeEntryID)
    {
        $timeEntryID = MySQL::SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);

        $table = $this->getTimeSheetTable();
        $projectTable = $this->getProjectTable();
        $activityTable = $this->getActivityTable();
        $customerTable = $this->getCustomerTable();

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customerID AS customerID
      				FROM $table
                	JOIN $projectTable USING(projectID)
                	JOIN $customerTable USING(customerID)
                	JOIN $activityTable USING(activityID)";


        if ($timeEntryID) {
            $result = $this->conn->Query("$select WHERE timeEntryID = " . $timeEntryID);
        } else {
            $result = $this->conn->Query("$select WHERE userID = " . $this->kga['user']['userID'] . " ORDER BY timeEntryID DESC LIMIT 1");
        }

        if (!$result) {
            $this->logLastError('timeSheet_get_data');
            return false;
        }

        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * delete time sheet entry
     *
     * @param int $id -> ID of record
     * @return object
     */
    public function timeEntry_delete($id)
    {
        $filter["timeEntryID"] = MySQL::SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $table = $this->getTimeSheetTable();
        $query = MySQL::BuildSQLDelete($table, $filter);
        return $this->conn->Query($query);
    }

    /**
     * create time sheet entry
     *
     * @param array $data array with record data
     * @return bool|int
     */
    public function timeEntry_create($data)
    {
        $data = $this->clean_data($data);

        $values['location'] = MySQL::SQLValue($data['location']);
        $values['comment'] = MySQL::SQLValue($data['comment']);
        $values['description'] = MySQL::SQLValue($data['description']);
        if ($data['trackingNumber'] == '') {
            $values['trackingNumber'] = 'NULL';
        } else {
            $values['trackingNumber'] = MySQL::SQLValue($data['trackingNumber']);
        }
        $values['userID'] = MySQL::SQLValue($data['userID'], MySQL::SQLVALUE_NUMBER);
        $values['projectID'] = MySQL::SQLValue($data['projectID'], MySQL::SQLVALUE_NUMBER);
        $values['activityID'] = MySQL::SQLValue($data['activityID'], MySQL::SQLVALUE_NUMBER);
        $values['commentType'] = MySQL::SQLValue($data['commentType'], MySQL::SQLVALUE_NUMBER);
        $values['start'] = MySQL::SQLValue($data['start'], MySQL::SQLVALUE_NUMBER);
        $values['end'] = MySQL::SQLValue($data['end'], MySQL::SQLVALUE_NUMBER);
        $values['duration'] = MySQL::SQLValue($data['duration'], MySQL::SQLVALUE_NUMBER);
        $values['rate'] = MySQL::SQLValue($data['rate'] ? : 0, MySQL::SQLVALUE_NUMBER);
        $values['fixedRate'] = MySQL::SQLValue($data['fixedRate'] ? : 0, MySQL::SQLVALUE_NUMBER);
        $values['cleared'] = MySQL::SQLValue($data['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values['budget'] = MySQL::SQLValue($data['budget'], MySQL::SQLVALUE_NUMBER);
        $values['approved'] = MySQL::SQLValue($data['approved'], MySQL::SQLVALUE_NUMBER);
        $values['statusID'] = MySQL::SQLValue($data['statusID'], MySQL::SQLVALUE_NUMBER);
        $values['billable'] = MySQL::SQLValue($data['billable'], MySQL::SQLVALUE_NUMBER);

        $table = $this->getTimeSheetTable();
        $success = $this->conn->InsertRow($table, $values);
        if (!$success) {
            $this->logLastError('timeEntry_create');
            return false;
        }
        return $this->conn->GetLastInsertID();
    }

    /**
     * edit time sheet entry
     *
     * @param int $id ID of record
     * @param array $data array with new record data
     * @return bool
     */
    public function timeEntry_edit($id, array $data)
    {
        $data = $this->clean_data($data);

        $original_array = $this->timeSheet_get_data($id);
        $new_array = [];
        $budgetChange = 0;
        $approvedChange = 0;

        foreach ($original_array as $key => $value) {
            if (isset($data[$key]) == true) {
                // budget is added to total budget for activity. So if we change the budget, we need
                // to first subtract the previous entry before adding the new one
                //if($key == 'budget') {
                //	$budgetChange = - $value;
                //} else if($key == 'approved') {
                //	$approvedChange = - $value;
                //}
                $new_array[$key] = $data[$key];
            } else {
                $new_array[$key] = $original_array[$key];
            }
        }

        $values['description'] = MySQL::SQLValue($new_array['description']);
        $values['comment'] = MySQL::SQLValue($new_array['comment']);
        $values['location'] = MySQL::SQLValue($new_array['location']);
        if ($new_array['trackingNumber'] == '') {
            $values['trackingNumber'] = 'NULL';
        } else {
            $values['trackingNumber'] = MySQL::SQLValue($new_array['trackingNumber']);
        }
        $values['userID'] = MySQL::SQLValue($new_array['userID'], MySQL::SQLVALUE_NUMBER);
        $values['projectID'] = MySQL::SQLValue($new_array['projectID'], MySQL::SQLVALUE_NUMBER);
        $values['activityID'] = MySQL::SQLValue($new_array['activityID'], MySQL::SQLVALUE_NUMBER);
        $values['commentType'] = MySQL::SQLValue($new_array['commentType'], MySQL::SQLVALUE_NUMBER);
        $values['start'] = MySQL::SQLValue($new_array['start'], MySQL::SQLVALUE_NUMBER);
        $values['end'] = MySQL::SQLValue($new_array['end'], MySQL::SQLVALUE_NUMBER);
        $values['duration'] = MySQL::SQLValue($new_array['duration'], MySQL::SQLVALUE_NUMBER);
        $values['rate'] = MySQL::SQLValue($new_array['rate'], MySQL::SQLVALUE_NUMBER);
        $values['fixedRate'] = MySQL::SQLValue($new_array['fixedRate'], MySQL::SQLVALUE_NUMBER);
        $values['cleared'] = MySQL::SQLValue($new_array['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values['budget'] = MySQL::SQLValue($new_array['budget'], MySQL::SQLVALUE_NUMBER);
        $values['approved'] = MySQL::SQLValue($new_array['approved'], MySQL::SQLVALUE_NUMBER);
        $values['statusID'] = MySQL::SQLValue($new_array['statusID'], MySQL::SQLVALUE_NUMBER);
        $values['billable'] = MySQL::SQLValue($new_array['billable'], MySQL::SQLVALUE_NUMBER);

        $filter['timeEntryID'] = MySQL::SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $table = $this->getTimeSheetTable();

        if (!$this->conn->TransactionBegin()) {
            $this->logLastError('timeEntry_edit');
            return false;
        }
        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        $success = true;

        if (!$this->conn->Query($query)) {
            $success = false;
        }

        if ($success) {
            if (!$this->conn->TransactionEnd()) {
                $this->logLastError('timeEntry_edit');
                return false;
            }
        } else {
            // $budgetChange += $values['budget'];
            // $approvedChange += $values['approved'];
            // $this->update_evt_budget($values['projectID'], $values['activityID'], $budgetChange);
            // $this->update_evt_approved($values['projectID'], $values['activityID'], $budgetChange);
            $this->logLastError('timeEntry_edit');
            if (!$this->conn->TransactionRollback()) {
                $this->logLastError('timeEntry_edit');
                return false;
            }
        }

        return $success;
    }

    /**
     * saves timeframe of user in database (table conf)
     *
     * @param string $timeFrameBegin unix seconds
     * @param string $timeframeEnd unix seconds
     * @param string $user ID of user
     *
     * @return bool
     */
    public function save_timeframe($timeFrameBegin, $timeframeEnd, $user)
    {
        if ($timeFrameBegin == 0 && $timeframeEnd == 0) {
            $mon = date("n");
            $day = date("j");
            $Y = date("Y");
            $timeFrameBegin = mktime(0, 0, 0, $mon, $day, $Y);
            $timeframeEnd = mktime(23, 59, 59, $mon, $day, $Y);
        }

        if ($timeframeEnd == mktime(23, 59, 59, date('n'), date('j'), date('Y'))) {
            $timeframeEnd = 0;
        }

        $values['timeframeBegin'] = MySQL::SQLValue($timeFrameBegin, MySQL::SQLVALUE_NUMBER);
        $values['timeframeEnd'] = MySQL::SQLValue($timeframeEnd, MySQL::SQLVALUE_NUMBER);

        $table = $this->kga['server_prefix'] . "users";
        $filter['userID'] = MySQL::SQLValue($user, MySQL::SQLVALUE_NUMBER);


        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        if (!$this->conn->Query($query)) {
            $this->logLastError('save_timeframe');
            return false;
        }

        return true;
    }

    /**
     * @param array $entries
     *
     * @return bool
     */
    public function setTimeEntriesAsCleared(array $entries)
    {
        // timesheet entries
        $timeSheetEntries = array_filter($entries, function ($entry) {
            return $entry['type'] == 'timeSheet';
        });

        $ids = array_map(function ($entry) {
            return $this->conn->SQLFix($entry['timeEntryID']);
        }, $timeSheetEntries);

        $update = ['cleared' => 1];

        $where = ['timeEntryID IN (' . implode(',', $ids) . ')'];

        $resultTimeSheet = $this->conn->UpdateRows($this->getTimeSheetTable(), $update, $where);

        // expenses
        $expenses = array_filter($entries, function ($entry) {
            return $entry['type'] == 'expense';
        });

        $ids = array_map(function ($entry) {
            return $this->conn->SQLFix($entry['expenseID']);
        }, $expenses);

        $where = ['expenseID IN (' . implode(',', $ids) . ')'];

        $resultExpenses = $this->conn->UpdateRows($this->getExpenseTable(), $update, $where);

        return $resultTimeSheet && $resultExpenses;
    }

    /**
     * returns list of projects for specific group as array
     *
     * @param array $groups ID of user in database
     * @return array
     */
    public function get_projects(array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        if (empty($groups)) {
            $query = "SELECT project.*, customer.name AS customerName, customer.visible as customerVisible
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  WHERE project.trash=0";
        } else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName, customer.visible as customerVisible
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  JOIN ${p}groups_projects USING(projectID)
                  WHERE ${p}groups_projects.groupID IN (" . implode(',', $groups) . ")
                  AND project.trash=0";
        }

        if ($this->kga->getSettings()->isFlipProjectDisplay()) {
            $query .= " ORDER BY project.visible DESC, customer.visible DESC, customerName, name;";
        } else {
            $query .= " ORDER BY project.visible DESC, customer.visible DESC, name, customerName;";
        }

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_projects');
            return false;
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($rows) {
            $arr = [];
            $i = 0;
            foreach ($rows as $row) {
                $arr[$i]['projectID'] = $row['projectID'];
                $arr[$i]['customerID'] = $row['customerID'];
                $arr[$i]['name'] = $row['name'];
                $arr[$i]['comment'] = $row['comment'];
                $arr[$i]['visible'] = $row['visible'];
                $arr[$i]['filter'] = $row['filter'];
                $arr[$i]['trash'] = $row['trash'];
                $arr[$i]['budget'] = $row['budget'];
                $arr[$i]['effort'] = $row['effort'];
                $arr[$i]['approved'] = $row['approved'];
                $arr[$i]['internal'] = $row['internal'];
                $arr[$i]['customerName'] = $row['customerName'];
                $arr[$i]['customerVisible'] = $row['customerVisible'];
                $i++;
            }
            return $arr;
        }
        return [];
    }

    /**
     * returns list of projects for specific group and specific customer as array
     *
     * @param int $customerID customer id
     * @param array $groups list of group ids
     * @return array
     */
    public function get_projects_by_customer($customerID, array $groups = null)
    {
        $customerID = MySQL::SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        if ($this->kga->getSettings()->isFlipProjectDisplay()) {
            $sort = "customerName, name";
        } else {
            $sort = "name, customerName";
        }

        if (empty($groups)) {
            $query = "SELECT project.*, customer.name AS customerName, customer.visible as customerVisible
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  WHERE customerID = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        } else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName, customer.visible as customerVisible
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  JOIN ${p}groups_projects USING(projectID)
                  WHERE ${p}groups_projects.groupID  IN (" . implode($groups, ',') . ")
                    AND customerID = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }

        $this->conn->Query($query);

        $arr = [];
        $i = 0;

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            $arr[$i]['projectID'] = $row->projectID;
            $arr[$i]['name'] = $row->name;
            $arr[$i]['customerID'] = $row->customerID;
            $arr[$i]['visible'] = $row->visible;
            $arr[$i]['budget'] = $row->budget;
            $arr[$i]['effort'] = $row->effort;
            $arr[$i]['approved'] = $row->approved;
            $arr[$i]['customerName'] = $row->customerName;
            $arr[$i]['customerVisible'] = $row->customerVisible;
            $i++;
        }

        return $arr;
    }

    /**
     * Creates an array of clauses which can be joined together in the WHERE part
     * of a sql query. The clauses describe whether a line should be included
     * depending on the filters set.
     *
     * This method also makes the values SQL-secure.
     *
     * @param array $users list of IDs of users to include
     * @param array $customers list of IDs of customers to include
     * @param array $projects list of IDs of projects to include
     * @param array $activities list of IDs of activities to include
     * @return array list of where clauses to include in the query
     */
    public function timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities = [])
    {
        if (!is_array($users)) {
            $users = [];
        }
        if (!is_array($customers)) {
            $customers = [];
        }
        if (!is_array($projects)) {
            $projects = [];
        }
        if (!is_array($activities)) {
            $activities = [];
        }

        foreach ($users as $i => $value) {
            $users[$i] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
        }
        foreach ($customers as $i => $value) {
            $customers[$i] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
        }
        foreach ($projects as $i => $value) {
            $projects[$i] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
        }
        foreach ($activities as $i => $value) {
            $activities[$i] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
        }

        $whereClauses = [];

        if (count($users) > 0) {
            $whereClauses[] = "userID in (" . implode(',', $users) . ")";
        }

        if (count($customers) > 0) {
            $whereClauses[] = "customerID in (" . implode(',', $customers) . ")";
        }

        if (count($projects) > 0) {
            $whereClauses[] = "projectID in (" . implode(',', $projects) . ")";
        }

        if (count($activities) > 0) {
            $whereClauses[] = "activityID in (" . implode(',', $activities) . ")";
        }

        return $whereClauses;
    }

    /**
     * returns timesheet for specific user as multidimensional array
     *
     * @param int $start start of timeframe in unix seconds
     * @param int $end end of timeframe in unix seconds
     * @param array $users
     * @param array $customers
     * @param array $projects
     * @param array $activities
     * @param bool $limit
     * @param bool $reverse_order
     * @param int $filterCleared where -1 (default) means no filtering, 0 means only not cleared entries, 1 means only cleared entries
     * @param int $startRows
     * @param int $limitRows
     * @param bool $countOnly
     * @param bool $groupedEntries
     * @return array
     */
    public function get_timeSheet(
        $start,
        $end,
        $users = null,
        $customers = null,
        $projects = null,
        $activities = null,
        $limit = false,
        $reverse_order = false,
        $filterCleared = null,
        $startRows = 0,
        $limitRows = 0,
        $countOnly = false,
        $groupedEntries = false
    ) {
        // -1 for disabled, 0 for only not cleared entries
        if (!is_numeric($filterCleared)) {
            $filterCleared = -1;
            if ($this->kga->getSettings()->isHideClearedEntries()) {
                $filterCleared = 0;
            }
        }

        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);
        $filterCleared = MySQL::SQLValue($filterCleared, MySQL::SQLVALUE_NUMBER);
        $limit = MySQL::SQLValue($limit, MySQL::SQLVALUE_BOOLEAN);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if (isset($this->kga['customer'])) {
            $whereClauses[] = "project.internal = 0";
        }

        if ($start) {
            $whereClauses[] = "(end > $start || end = 0)";
        }

        if ($end) {
            $whereClauses[] = "start < $end";
        }

        if ($filterCleared > -1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        if ($limit) {
            if (!empty($limitRows)) {
                $startRows = (int)$startRows;
                $limit = "LIMIT $startRows, $limitRows";
            } else {
                $limit = 'LIMIT ' . $this->kga->getSettings()->getRowLimit();
            }
        } else {
            $limit = '';
        }

        if ($groupedEntries) {
            $query = "SELECT
                DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d') AS `aggrDate`,
                MIN(`start`) AS `start`,
                MAX(`end`) AS `end`,
                SUM(`duration`) AS `duration`,
                GROUP_CONCAT(DISTINCT `userID`) AS `userID`,
                `projectID`,
                `activityID`,
                GROUP_CONCAT(`description` SEPARATOR '\\n') AS `description`,
                GROUP_CONCAT(`timeSheet`.`comment` SEPARATOR ', ') AS `comment`,
                `commentType`,
                `cleared`,
                GROUP_CONCAT(`location` SEPARATOR ', ') AS `location`,
                GROUP_CONCAT(`trackingNumber` SEPARATOR ', ') AS `trackingNumber`,
                `rate`,
                `fixedRate`,
                `timeSheet`.`budget`,
                MIN(`timeSheet`.`approved`) as `approved`,
                `statusID`,
                MIN(`billable`) as `billable`,
                `customer`.`name` AS `customerName`, `customer`.`customerID` as `customerID`,
                `activity`.`name` AS `activityName`,
                `project`.`name` AS `projectName`, `project`.`comment` AS `projectComment`,
                `user`.`name` AS `userName`, `user`.`alias` AS `userAlias`
                FROM `${p}timeSheet` AS `timeSheet`
                    JOIN `${p}projects` AS `project` USING (`projectID`) 
                    JOIN `${p}customers` AS `customer` USING (`customerID`) 
                    JOIN `${p}activities` AS `activity` USING (`activityID`) 
                    JOIN `${p}users` AS `user` USING (`userID`) "
                    . (count($whereClauses) > 0 ? ' WHERE ' : ' ') . implode(' AND ', $whereClauses) .
                    ' GROUP BY `aggrDate`, `projectID`, `activityID`, `rate`, `fixedRate`' .
                    ' ORDER BY `start` ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';
        } else {
            $select = 'SELECT timeSheet.*, 
                status.status,
                customer.name AS customerName, customer.customerID as customerID,
                activity.name AS activityName,
                project.name AS projectName, project.comment AS projectComment,
                user.name AS userName, user.alias AS userAlias ';

            if ($countOnly) {
                $select = 'SELECT COUNT(*) AS total';
                $limit = '';
            }

            $query = "$select
                FROM ${p}timeSheet AS timeSheet
                JOIN ${p}projects AS project USING (projectID)
                JOIN ${p}customers AS customer USING (customerID)
                JOIN ${p}users AS user USING (userID)
                JOIN ${p}statuses AS status USING (statusID)
                JOIN ${p}activities AS activity USING (activityID) "
                . (count($whereClauses) > 0 ? ' WHERE ' : ' ') . implode(' AND ', $whereClauses) .
                ' ORDER BY start ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';
        }

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('get_timeSheet');
        }

        if ($countOnly) {
            $this->conn->MoveFirst();
            $row = $this->conn->Row();
            return $row->total;
        }

        $i = 0;
        $arr = [];

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();

            if (!$groupedEntries) {
                $arr[$i]['timeEntryID'] = $row->timeEntryID;
            }

            // Start time should not be less than the selected start time. This would confuse the user.
            if ($start && $row->start <= $start) {
                $arr[$i]['start'] = $start;
            } else {
                $arr[$i]['start'] = $row->start;
            }

            // End time should not be less than the selected start time. This would confuse the user.
            if ($end && $row->end >= $end) {
                $arr[$i]['end'] = $end;
            } else {
                $arr[$i]['end'] = $row->end;
            }

            if ($row->end != 0) {
                // only calculate time after recording is complete
                $arr[$i]['duration'] = $row->duration;
                $arr[$i]['formattedDuration'] = Kimai_Format::formatDuration($arr[$i]['duration']);
                $arr[$i]['wage_decimal'] = $arr[$i]['duration'] / 3600 * $row->rate;

                $fixedRate = (double)$row->fixedRate;
                if ($fixedRate) {
                    $arr[$i]['wage'] = sprintf("%01.2f", $fixedRate);
                } else {
                    $arr[$i]['wage'] = sprintf("%01.2f", $arr[$i]['wage_decimal']);
                }
            } else {
                $arr[$i]['duration'] = null;
                $arr[$i]['formattedDuration'] = null;
                $arr[$i]['wage_decimal'] = null;
                $arr[$i]['wage'] = null;
            }
            $arr[$i]['budget'] = $row->budget;
            $arr[$i]['approved'] = $row->approved;
            $arr[$i]['rate'] = $row->rate;
            $arr[$i]['projectID'] = $row->projectID;
            $arr[$i]['activityID'] = $row->activityID;
            $arr[$i]['userID'] = $row->userID;
            $arr[$i]['customerName'] = $row->customerName;
            $arr[$i]['customerID'] = $row->customerID;
            $arr[$i]['activityName'] = $row->activityName;
            $arr[$i]['projectName'] = $row->projectName;
            $arr[$i]['projectComment'] = $row->projectComment;
            $arr[$i]['location'] = $row->location;
            $arr[$i]['trackingNumber'] = $row->trackingNumber;
            $arr[$i]['statusID'] = $row->statusID;
            $arr[$i]['status'] = $row->status;
            $arr[$i]['billable'] = $row->billable;
            $arr[$i]['description'] = $row->description;
            $arr[$i]['comment'] = $row->comment;
            $arr[$i]['cleared'] = $row->cleared;
            $arr[$i]['commentType'] = $row->commentType;
            $arr[$i]['userAlias'] = $row->userAlias;
            $arr[$i]['userName'] = $row->userName;
            $i++;
        }
        return $arr;
    }

    /**
     * A drop-in function to replace checkuser() and be compatible with none-cookie environments.
     *
     * @param $kimaiUser
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function checkUserInternal($kimaiUser)
    {
        $p = $this->kga['server_prefix'];

        if (strncmp($kimaiUser, 'customer_', 9) == 0) {
            $customerName = MySQL::SQLValue(substr($kimaiUser, 9));
            $query = "SELECT customerID FROM ${p}customers WHERE name = $customerName AND NOT trash = '1';";
            $this->conn->Query($query);
            $row = $this->conn->RowArray(0, MYSQLI_ASSOC);

            $customerID = $row['customerID'];
            if ($customerID < 1) {
                Kimai_Logger::logfile("Kicking customer $customerName because he is unknown to the system.");
                kickUser();
            }
        } else {
            $query = "SELECT userID FROM ${p}users WHERE name = '$kimaiUser' AND active = '1' AND NOT trash = '1';";
            $this->conn->Query($query);
            $row = $this->conn->RowArray(0, MYSQLI_ASSOC);

            $userID = $row['userID'];
            $name = $kimaiUser;

            if ($userID < 1) {
                Kimai_Logger::logfile("Kicking user $name because he is unknown to the system.");
                kickUser();
            }
        }

        $this->kga['timezone'] = $this->kga['defaultTimezone'];

        // and add user or customer specific settings on top
        if (strncmp($kimaiUser, 'customer_', 9) == 0) {
            $configs = $this->get_customer_config($customerID);
            if ($configs !== null) {
                foreach ($configs as $key => $value) {
                    $this->kga['customer'][$key] = $value;
                }

                $this->kga->setTimezone($this->kga['customer']['timezone']);
            }
        } else {
            $configs = $this->get_user_config($userID);
            if ($configs !== null) {
                $user = new Kimai_User($configs);
                $user->setGroups($this->getGroupMemberships($userID));
                $this->kga->setUser($user);
                Kimai_Registry::setUser($user);

                $this->kga->getSettings()->add(
                    $this->user_get_preferences_by_prefix('ui.', $userID)
                );

                $userTimezone = $this->user_get_preference('timezone', $userID);
                if ($userTimezone != '') {
                    $this->kga->setTimezone($userTimezone);
                }
            }
        }

        date_default_timezone_set($this->kga->getTimezone());

        // skin fallback
        if (!is_dir(WEBROOT . "/skins/" . $this->kga->getSettings()->getSkin())) {
            $this->kga->getSettings()->setSkin($this->kga->getSkin());
        }

        // load user specific translation
        Kimai_Registry::getTranslation()->addTranslations($this->kga->getLanguage());

        if (isset($this->kga['user'])) {
            return $this->kga['user'];
        } else {
            return $this->kga['customer'];
        }
    }

    /**
     * Returns all configuration variables
     *
     * @return array with the options from the configuration table
     */
    protected function getConfigurationData()
    {
        $table = $this->kga['server_prefix'] . "configuration";
        $this->conn->SelectRows($table, ["`option` NOT IN ('version', 'revision')"]);

        $config_data = [];

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            $config_data[$row->option] = $row->value;
        }

        return $config_data;
    }

    /**
     * Prefills the Config (and inherited settings) object with configuration data.
     *
     * @param Kimai_Config $config
     */
    public function initializeConfig(Kimai_Config $config)
    {
        $config->setStatuses($this->getStatuses());

        $allConf = $this->getConfigurationData();
        if (empty($allConf)) {
            return;
        }

        foreach ($allConf as $key => $value) {
            switch ($key) {
                case 'language':
                    if (!empty($value)) {
                        $config->setLanguage($value);
                    }
                    break;

                // TODO move to Kimai_Config as they are NOT user specific!
                // the following system settings are still used in ['conf'] array syntax
                case 'decimalSeparator':
                case 'durationWithSeconds':
                case 'roundTimesheetEntries':
                case 'roundMinutes':
                case 'roundSeconds':
                    $config->getSettings()->set($key, $value);
                    // no break

                case 'adminmail':
                case 'loginTries':
                case 'loginBanTime':
                case 'currency_name':
                case 'currency_sign':
                case 'currency_first':
                case 'show_update_warn':
                case 'check_at_startup':
                case 'show_daySeperatorLines':
                case 'show_gabBreaks':
                case 'show_RecordAgain':
                case 'show_TrackingNr':
                case 'date_format_0':
                case 'date_format_1':
                case 'date_format_2':
                case 'date_format_3':
                case 'table_time_format':
                case 'roundPrecision':
                case 'roundingMethod':
                case 'exactSums':
                case 'defaultVat':
                case 'editLimit':
                case 'defaultStatusID':
                    $config->set($key, $value);
                    break;

                case 'openAfterRecorded':
                case 'showQuickNote':
                case 'quickdelete':
                case 'autoselection':
                case 'noFading':
                case 'showIDs':
                case 'sublistAnnotations':
                case 'user_list_hidden':
                case 'project_comment_flag':
                case 'flip_project_display':
                case 'hideClearedEntries':
                case 'defaultLocation':
                default:
                    $config->getSettings()->set($key, $value);
                    break;
            }
        }
    }

    /**
     * Return all available status entries.
     *
     * @return array
     */
    public function getStatuses()
    {
        $status = [];

        $table = $this->kga['server_prefix'] . "statuses";
        $this->conn->SelectRows($table);

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            $status[$row->statusID] = $row->status; // TODO translate me
        }
        return $status;
    }

    /**
     * Returns a username for the given $apiKey.
     *
     * @param string $apiKey
     * @return string|null
     */
    public function getUserByApiKey($apiKey)
    {
        if (!$apiKey || strlen(trim($apiKey)) == 0) {
            return null;
        }

        $filter = [
            'apikey' => MySQL::SQLValue($apiKey, MySQL::SQLVALUE_TEXT),
            'trash' => MySQL::SQLValue(0, MySQL::SQLVALUE_NUMBER)
        ];

        // get values from user record
        $columns = ["userID", "name"];

        $this->conn->SelectRows($this->getUserTable(), $filter, $columns);
        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        return $row['name'];
    }

    /**
     * returns configuration data for specified user
     *
     * @param int $userID
     * @return array $this->kga
     */
    public function get_user_config($userID)
    {
        $table = $this->getUserTable();
        $filter['userID'] = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "userID";
        $columns[] = "name";
        $columns[] = "trash";
        $columns[] = "active";
        $columns[] = "mail";
        $columns[] = "password";
        $columns[] = "ban";
        $columns[] = "banTime";
        $columns[] = "secure";
        $columns[] = "lastProject";
        $columns[] = "lastActivity";
        $columns[] = "lastRecord";
        $columns[] = "timeframeBegin";
        $columns[] = "timeframeEnd";
        $columns[] = "apikey";
        $columns[] = "globalRoleID";

        $this->conn->SelectRows($table, $filter, $columns);
        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * returns configuration for specified customer
     *
     * @param int $userID
     * @return array
     */
    public function get_customer_config($userID)
    {
        $table = $this->getCustomerTable();
        $filter['customerID'] = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "customerID";
        $columns[] = "name";
        $columns[] = "comment";
        $columns[] = "visible";
        $columns[] = "filter";
        $columns[] = "company";
        $columns[] = "street";
        $columns[] = "zipcode";
        $columns[] = "city";
        $columns[] = "phone";
        $columns[] = "fax";
        $columns[] = "mobile";
        $columns[] = "mail";
        $columns[] = "homepage";
        $columns[] = "trash";
        $columns[] = "password";
        $columns[] = "secure";
        $columns[] = "timezone";

        $this->conn->SelectRows($table, $filter, $columns);
        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * checks if a customer with this name exists
     *
     * @param string $name
     * @return boolean
     */
    public function is_customer_name($name)
    {
        $name = MySQL::SQLValue($name);
        $p = $this->kga['server_prefix'];

        $query = "SELECT customerID FROM ${p}customers WHERE name = $name AND trash = 0";

        $this->conn->Query($query);
        return $this->conn->RowCount() == 1;
    }

    /**
     * returns time summary of current timesheet
     *
     * @param int $start start of timeframe in unix seconds
     * @param int $end end of timeframe in unix seconds
     * @param null $users
     * @param null $customers
     * @param null $projects
     * @param null $activities
     * @param null $filterCleared
     * @return int
     */
    public function get_duration($start, $end, $users = null, $customers = null, $projects = null, $activities = null, $filterCleared = null)
    {
        // -1 for disabled, 0 for only not cleared entries
        if (!is_numeric($filterCleared)) {
            $filterCleared = -1;
            if ($this->kga->getSettings()->isHideClearedEntries()) {
                $filterCleared = 0;
            }
        }

        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }
        if ($filterCleared > -1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        $query = "SELECT start,end,duration FROM ${p}timeSheet
              JOIN ${p}projects USING(projectID)
              JOIN ${p}customers USING(customerID)
              JOIN ${p}users USING(userID)
              JOIN ${p}activities USING(activityID) "
                 .(count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);
        $this->conn->Query($query);

        $this->conn->MoveFirst();
        $sum = 0;
        $consideredStart = 0; // Consider start of selected range if real start is before
        $consideredEnd = 0; // Consider end of selected range if real end is afterwards
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            if ($row->start <= $start && $row->end < $end) {
                $consideredStart = $start;
                $consideredEnd = $row->end;
            } elseif ($row->start <= $start && $row->end >= $end) {
                $consideredStart = $start;
                $consideredEnd = $end;
            } elseif ($row->start > $start && $row->end < $end) {
                $consideredStart = $row->start;
                $consideredEnd = $row->end;
            } elseif ($row->start > $start && $row->end >= $end) {
                $consideredStart = $row->start;
                $consideredEnd = $end;
            }
            $sum += (int)($consideredEnd - $consideredStart);
        }
        return $sum;
    }

    /**
     * returns list of customers in a group as array
     *
     * @param array $groups ID of group in table groups or "all" for all groups
     * @return array
     */
    public function get_customers(array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        if (empty($groups)) {
            $query = "SELECT customerID, name, contact, visible
              FROM ${p}customers
              WHERE trash=0
              ORDER BY visible DESC, name;";
        } else {
            $query = "SELECT DISTINCT customerID, name, contact, visible
              FROM ${p}customers
              JOIN ${p}groups_customers AS g_c USING (customerID)
              WHERE g_c.groupID IN (" . implode(',', $groups) . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_customers');
            return false;
        }

        $i = 0;
        if ($this->conn->RowCount()) {
            $arr = [];
            $this->conn->MoveFirst();
            while (!$this->conn->EndOfSeek()) {
                $row = $this->conn->Row();
                $arr[$i]['customerID'] = $row->customerID;
                $arr[$i]['name'] = $row->name;
                $arr[$i]['contact'] = $row->contact;
                $arr[$i]['visible'] = $row->visible;
                $i++;
            }
            return $arr;
        }
        return [];
    }

    /**
     * Get all available activities.
     *
     * This is either a list of all or a list of all for the given groups.
     *
     * @param array|null $groups
     * @return array|bool
     */
    public function get_activities(array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        if (empty($groups)) {
            $query = "SELECT activityID, name, visible
              FROM ${p}activities
              WHERE trash=0
              ORDER BY visible DESC, name;";
        } else {
            $query = "SELECT DISTINCT activityID, name, visible
              FROM ${p}activities
              JOIN ${p}groups_activities AS g_a USING(activityID)
              WHERE g_a.groupID IN (" . implode(',', $groups) . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities');
            return false;
        }

        $arr = [];
        $i = 0;
        if ($this->conn->RowCount()) {
            $this->conn->MoveFirst();
            while (!$this->conn->EndOfSeek()) {
                $row = $this->conn->Row();
                $arr[$i]['activityID'] = $row->activityID;
                $arr[$i]['name'] = $row->name;
                $arr[$i]['visible'] = $row->visible;
                $i++;
            }
            return $arr;
        }

        return [];
    }

    /**
     * Get an array of activities, which should be displayed for a specific project.
     * Those are activities which were assigned to the project or which are assigned to
     * no project.
     * Two joins can occur:
     *  The JOIN is for filtering the activities by groups.
     *  The LEFT JOIN gives each activity row the project id which it has been assigned
     *  to via the projects_activities table or NULL when there is no assignment. So we only
     *  take rows which have NULL or the project id in that column.
     *
     * @param int $projectID
     * @param array $groups
     * @return array
     */
    public function get_activities_by_project($projectID, array $groups = null)
    {
        $projectID = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        if (empty($groups)) {
            $query = "SELECT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activities AS activity
            LEFT JOIN ${p}projects_activities AS p_a USING(activityID)
            WHERE activity.trash=0
              AND (projectID = $projectID OR projectID IS NULL)
            ORDER BY visible DESC, name;";
        } else {
            $query = "SELECT DISTINCT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activities AS activity
            JOIN ${p}groups_activities USING(activityID)
            LEFT JOIN ${p}projects_activities p_a USING(activityID)
            WHERE `${p}groups_activities`.`groupID`  IN (" . implode(',', $groups) . ")
              AND activity.trash=0
              AND (projectID = $projectID OR projectID IS NULL)
            ORDER BY visible DESC, name;";
        }

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_project');
            return false;
        }

        $arr = [];
        if ($this->conn->RowCount()) {
            $this->conn->MoveFirst();
            while (!$this->conn->EndOfSeek()) {
                $row = $this->conn->Row();
                $arr[$row->activityID]['activityID'] = $row->activityID;
                $arr[$row->activityID]['name'] = $row->name;
                $arr[$row->activityID]['visible'] = $row->visible;
                $arr[$row->activityID]['budget'] = $row->budget;
                $arr[$row->activityID]['approved'] = $row->approved;
                $arr[$row->activityID]['effort'] = $row->effort;
            }
            return $arr;
        }
        return [];
    }

    /**
     * returns list of activities used with specified customer
     *
     * @param int $customer_ID filter for only this ID of a customer
     * @return array
     */
    public function get_activities_by_customer($customer_ID)
    {
        $p = $this->kga['server_prefix'];

        $customer_ID = MySQL::SQLValue($customer_ID, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT DISTINCT activityID, name, visible
          FROM ${p}activities
          WHERE activityID IN
              (SELECT activityID FROM ${p}timeSheet
                WHERE projectID IN (SELECT projectID FROM ${p}projects WHERE customerID = $customer_ID))
            AND trash=0";

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_customer');
            return false;
        }

        $arr = [];
        $i = 0;

        if ($this->conn->RowCount()) {
            $this->conn->MoveFirst();
            while (!$this->conn->EndOfSeek()) {
                $row = $this->conn->Row();
                $arr[$i]['activityID'] = $row->activityID;
                $arr[$i]['name'] = $row->name;
                $arr[$i]['visible'] = $row->visible;
                $i++;
            }
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * returns time of currently running activity recording as array
     *
     * result is meant as params for the stopwatch if the window is reloaded
     *
     * <pre>
     * returns:
     * [all] start time of entry in unix seconds (forgot why I named it this way, sorry ...)
     * [hour]
     * [min]
     * [sec]
     * </pre>
     *
     * @return array
     */
    public function get_current_timer()
    {
        $user = MySQL::SQLValue($this->kga['user']['userID'], MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        $this->conn->Query("SELECT timeEntryID, start FROM ${p}timeSheet WHERE userID = $user AND end = 0;");

        if ($this->conn->RowCount() == 0) {
            $current_timer['all'] = 0;
            $current_timer['hour'] = 0;
            $current_timer['min'] = 0;
            $current_timer['sec'] = 0;
        } else {
            $row = $this->conn->RowArray(0, MYSQLI_ASSOC);

            $start = (int)$row['start'];

            $aktuelleMessung = Kimai_Format::hourminsec(time() - $start);
            $current_timer['all'] = $start;
            $current_timer['hour'] = $aktuelleMessung['h'];
            $current_timer['min'] = $aktuelleMessung['i'];
            $current_timer['sec'] = $aktuelleMessung['s'];
        }
        return $current_timer;
    }

    /**
     * returns the version of the installed Kimai database to compare it with the package version
     *
     * @return array
     *
     * [0] => version number (x.x.x)
     * [1] => revision number
     */
    public function get_DBversion()
    {
        $filter['option'] = MySQL::SQLValue('version');
        $columns[] = "value";
        $table = $this->kga['server_prefix'] . "configuration";
        $result = $this->conn->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            $table = $this->kga['server_prefix'] . "var";
            unset($filter);
            $filter['var'] = MySQL::SQLValue('version');
            $result = $this->conn->SelectRows($table, $filter, $columns);
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        $return[] = $row['value'];

        if ($result == false) {
            $return[0] = "0.5.1";
        }

        $filter['option'] = MySQL::SQLValue('revision');
        $result = $this->conn->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            unset($filter);
            $filter['var'] = MySQL::SQLValue('revision');
            $result = $this->conn->SelectRows($table, $filter, $columns);
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        $return[] = $row['value'];

        return $return;
    }

    /**
     * returns the key for the session of a specific user
     *
     * the key is both stored in the database (users table) and a cookie on the client.
     * when the keys match the user is allowed to access the Kimai GUI.
     * match test is performed via public function userCheck()
     *
     * @param int $user ID of user in table users
     * @return string
     */
    public function get_seq($user)
    {
        if (strncmp($user, 'customer_', 9) == 0) {
            $filter['name'] = MySQL::SQLValue(substr($user, 9));
            $filter['trash'] = 0;
            $table = $this->getCustomerTable();
        } else {
            $filter['name'] = MySQL::SQLValue($user);
            $filter['trash'] = 0;
            $table = $this->getUserTable();
        }

        $columns[] = "secure";

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('get_seq');
            return false;
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        return $row['secure'];
    }

    /**
     * return status names
     *
     * @param array $statusIds
     * @return array
     */
    public function get_status(array $statusIds)
    {
        $p = $this->kga['server_prefix'];
        $statusIds = implode(',', $statusIds);
        $query = "SELECT status FROM ${p}statuses where statusID in ( $statusIds ) order by statusID";
        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('get_status');
            return false;
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        $res = [];
        foreach ($rows as $row) {
            $res[] = $row['status'];
        }
        return $res;
    }

    /**
     * returns array of all status with the status id as key
     *
     * @return array
     */
    public function get_statuses()
    {
        $p = $this->kga['server_prefix'];

        $query = "SELECT * FROM ${p}statuses
        ORDER BY status;";
        $this->conn->Query($query);

        $arr = [];
        $i = 0;

        $this->conn->MoveFirst();
        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        if ($rows === false) {
            return [];
        }

        foreach ($rows as $row) {
            $arr[] = $row;
            $arr[$i]['timeSheetEntryCount'] = $this->status_timeSheetEntryCount($row['statusID']);
            $i++;
        }

        return $arr;
    }

    /**
     * add a new status
     *
     * @param array $status
     * @return bool
     */
    public function status_create($status)
    {
        $values['status'] = MySQL::SQLValue(trim($status['status']));

        $table = $this->kga['server_prefix'] . "statuses";
        $result = $this->conn->InsertRow($table, $values);
        if (!$result) {
            $this->logLastError('add_status');
            return false;
        }
        return true;
    }

    /**
     * returns array of all users
     * [userID] => 23103741
     * [name] => admin
     * [mail] => 0
     * [active] => 0
     *
     * @param int $trash
     * @param array $groups list of group ids the users must be a member of
     * @return array
     */
    public function get_users($trash = 0, array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        $trash = MySQL::SQLValue($trash, MySQL::SQLVALUE_NUMBER);

        if (empty($groups)) {
            $query = "SELECT * FROM ${p}users
                WHERE trash = $trash
                ORDER BY name ;";
        } else {
            $query = "SELECT DISTINCT u.* FROM ${p}users AS u
                JOIN ${p}groups_users AS g_u USING(userID)
                WHERE g_u.groupID IN (" . implode($groups, ',') . ") AND
                trash = $trash
                ORDER BY name ;";
        }
        $this->conn->Query($query);

        $rows = $this->conn->RowArray(0, MYSQLI_ASSOC);

        $i = 0;
        $arr = [];

        $this->conn->MoveFirst();
        while (!$this->conn->EndOfSeek()) {
            $row = $this->conn->Row();
            $arr[$i]['userID'] = $row->userID;
            $arr[$i]['name'] = $row->name;
            $arr[$i]['globalRoleID'] = $row->globalRoleID;
            $arr[$i]['mail'] = $row->mail;
            $arr[$i]['active'] = $row->active;
            $arr[$i]['trash'] = $row->trash;

            if ($row->password != '' && $row->password != '0') {
                $arr[$i]['passwordSet'] = "yes";
            } else {
                $arr[$i]['passwordSet'] = "no";
            }
            $i++;
        }

        return $arr;
    }

    /**
     * returns array of all groups
     * [0]=> array(6) {
     *      ["groupID"] =>  string(1) "1"
     *      ["groupName"] =>  string(5) "admin"
     *      ["userID"] =>  string(9) "1234"
     *      ["trash"] =>  string(1) "0"
     *      ["count_users"] =>  string(1) "2"
     * }
     * [1]=> array(6) {
     *      ["groupID"] =>  string(1) "2"
     *      ["groupName"] =>  string(4) "Test"
     *      ["userID"] =>  string(9) "12345"
     *      ["trash"] =>  string(1) "0"
     *      ["count_users"] =>  string(1) "1"
     *  }
     *
     * @param int $trash
     * @return array
     */
    public function get_groups($trash = 0)
    {
        $p = $this->kga['server_prefix'];

        // Lock tables for alles queries executed until the end of this public function
        $lock = "LOCK TABLE ${p}users READ, ${p}groups READ, ${p}groups_users READ;";
        $result = $this->conn->Query($lock);
        if (!$result) {
            $this->logLastError('get_groups');
            return false;
        }

        if (!$trash) {
            $trashoption = "WHERE ${p}groups.trash !=1";
        }

        $query = "SELECT * FROM ${p}groups $trashoption ORDER BY name;";
        $this->conn->Query($query);

        // rows into array
        $groups = [];
        $i = 0;

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            $groups[] = $row;

            // append user count
            $groups[$i]['count_users'] = $this->group_count_users($row['groupID']);

            $i++;
        }

        // Unlock tables
        $unlock = "UNLOCK TABLES;";
        $result = $this->conn->Query($unlock);
        if (!$result) {
            $this->logLastError('get_groups');
            return false;
        }

        return $groups;
    }

    /**
     * Performed when the stop buzzer is hit.
     *
     * @param int $id id of the entry to stop
     *
     * @return object
     */
    public function stopRecorder($id)
    {
        $table = $this->getTimeSheetTable();

        $activity = $this->timeSheet_get_data($id);

        $filter['timeEntryID'] = $activity['timeEntryID'];
        $filter['end'] = 0; // only update running activities

        $rounded = Kimai_Rounding::roundTimespan(
            $activity['start'],
            time(),
            $this->kga->getRoundPrecisionRecorderTimes(),
            $this->kga->getRoundingMethod()
        );

        $values['start'] = $rounded['start'];
        $values['end'] = $rounded['end'];
        $values['duration'] = $values['end'] - $values['start'];

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * starts timesheet record
     *
     * @param int $projectID ID of project to record
     * @param $activityID
     * @param $user
     * @return int id of the new entry or false on failure
     */
    public function startRecorder($projectID, $activityID, $user)
    {
        $projectID = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $activityID = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $user = MySQL::SQLValue($user, MySQL::SQLVALUE_NUMBER);

        $values['projectID'] = $projectID;
        $values['activityID'] = $activityID;
        $values['start'] = time();
        $values['userID'] = $user;
        $values['statusID'] = $this->kga->getDefaultStatus();

        $rate = $this->get_best_fitting_rate($user, $projectID, $activityID);
        if ($rate) {
            $values['rate'] = $rate;
        }

        $fixedRate = $this->get_best_fitting_fixed_rate($projectID, $activityID);
        if ($fixedRate) {
            $values['fixedRate'] = $fixedRate;
        }

        if ($this->kga->getSettings()->getDefaultLocation() != '') {
            $values['location'] = "'" . $this->kga->getSettings()->getDefaultLocation() . "'";
        }
        $table = $this->getTimeSheetTable();
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('startRecorder');
            return false;
        }

        return $this->conn->GetLastInsertID();
    }

    /**
     * Just edit the project for an entry. This is used for changing the project
     * of a running entry.
     *
     * @param int $timeEntryID id of the timesheet entry
     * @param int $projectID id of the project to change to
     * @return object
     */
    public function timeEntry_edit_project($timeEntryID, $projectID)
    {
        $timeEntryID = MySQL::SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $projectID = MySQL::SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $table = $this->getTimeSheetTable();

        $filter['timeEntryID'] = $timeEntryID;

        $values['projectID'] = $projectID;

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * Just edit the activity for an entry. This is used for changing the activity
     * of a running entry.
     *
     * @param int $timeEntryID id of the timesheet entry
     * @param int $activityID id of the activity to change to
     * @return object
     */
    public function timeEntry_edit_activity($timeEntryID, $activityID)
    {
        $timeEntryID = MySQL::SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $activityID = MySQL::SQLValue($activityID, MySQL::SQLVALUE_NUMBER);

        $table = $this->getTimeSheetTable();

        $filter['timeEntryID'] = $timeEntryID;

        $values['activityID'] = $activityID;

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * return ID of specific user named 'XXX'
     *
     * @param int $name name of user in table users
     * @return int id of the customer
     */
    public function customer_nameToID($name)
    {
        return $this->name2id($this->getCustomerTable(), 'customerID', 'name', $name);
    }

    /**
     * return ID of specific user by name
     *
     * @param int $name name of user in table users
     * @return string|bool
     */
    public function user_name2id($name)
    {
        return $this->name2id($this->getUserTable(), 'userID', 'name', $name);
    }

    /**
     * Query a table for an id by giving the name of an entry.
     *
     * @param string $table
     * @param string $endColumn
     * @param string $filterColumn
     * @param int $value
     * @return string|bool
     */
    private function name2id($table, $endColumn, $filterColumn, $value)
    {
        $filter[$filterColumn] = MySQL::SQLValue($value);
        $filter['trash'] = 0;
        $columns[] = $endColumn;

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('name2id');
            return false;
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);

        if ($row === false) {
            return false;
        }

        return (int)$row[$endColumn];
    }

    /**
     * return name of a user with specific ID
     *
     * @param string $id the user's userID
     * @return int
     */
    public function userIDToName($id)
    {
        $filter['userID'] = MySQL::SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $columns[] = "name";
        $table = $this->getUserTable();

        $result = $this->conn->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('userIDToName');
            return false;
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        return $row['name'];
    }

    /**
     * returns the date of the first timerecord of a user (when did the user join?)
     * this is needed for the datepicker
     * @param int $userID id of user
     * @return int unix seconds of first timesheet record
     */
    public function getjointime($userID)
    {
        $userID = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];

        $query = "SELECT start FROM ${p}timeSheet WHERE userID = $userID ORDER BY start ASC LIMIT 1;";

        $result = $this->conn->Query($query);
        if ($result == false) {
            $this->logLastError('getjointime');
            return false;
        }

        $result_array = $this->conn->RowArray(0, MYSQLI_NUM);

        if ($result_array[0] == 0) {
            return mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        } else {
            return $result_array[0];
        }
    }

    /**
     * returns list of users the given user can watch
     *
     * @param int $user ID of user in table users
     * @return array
     */
    public function get_user_watchable_users($user)
    {
        $userID = MySQL::SQLValue($user['userID'], MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];
        $that = $this;

        if ($this->global_role_allows($user['globalRoleID'], 'core-user-otherGroup-view')) {
            // If user may see other groups we need to filter out groups he's part of but has no permission to see users in.
            $forbidden_groups = array_filter($user['groups'], function ($groupID) use ($userID, $that) {
                $roleID = $that->user_get_membership_role($userID, $groupID);
                return !$that->membership_role_allows($roleID, 'core-user-view');
            });

            $group_filter = "";
            if (count($forbidden_groups) > 0) {
                $group_filter = " AND count(SELECT * FROM ${p}groups_users AS p WHERE u.`userID` = p.`userID` AND `groupID` NOT IN (" . implode(', ', $forbidden_groups) . ")) > 0";
            }

            $query = "SELECT * FROM ${p}users AS u WHERE trash=0 $group_filter ORDER BY name";
            $result = $this->conn->Query($query);
            return $this->conn->RecordsArray(MYSQLI_ASSOC);
        }

        $allowed_groups = array_filter($user['groups'], function ($groupID) use ($userID, $that) {
            $roleID = $that->user_get_membership_role($userID, $groupID);
            return $that->membership_role_allows($roleID, 'core-user-view');
        });

        // user is not allowed to see users of different groups, so he only gets to see himself
        if (empty($allowed_groups)) {
            return [$user];
        }

        // otherwise return the list of all active users within the allowed groups
        return $this->get_users(0, $allowed_groups);
    }

    /**
     * @param int $customer
     * @return array
     */
    public function get_customer_watchable_users($customer)
    {
        $customerID = MySQL::SQLValue($customer['customerID'], MySQL::SQLVALUE_NUMBER);
        $p = $this->kga['server_prefix'];
        $query = "SELECT * FROM ${p}users WHERE trash=0 AND `userID` IN (SELECT DISTINCT `userID` FROM `${p}timeSheet` WHERE `projectID` IN (SELECT `projectID` FROM `${p}projects` WHERE `customerID` = $customerID)) ORDER BY name";
        $result = $this->conn->Query($query);
        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * Checks if a user (given by user ID) can be accessed by another user (given by user array):
     *
     * @see get_watchable_users
     * @param int $user user to check for
     * @param int $userID user to check if watchable
     * @return boolean if watchable, false otherwiese
     */
    public function is_watchable_user($user, $userID)
    {
        $userID = MySQL::SQLValue($user['userID'], MySQL::SQLVALUE_NUMBER);

        $watchableUsers = $this->get_watchable_users($user);
        foreach ($watchableUsers as $watchableUser) {
            if ($watchableUser['userID'] == $userID) {
                return true;
            }
        }
        return false;
    }

    /**
     * returns assoc. array where the index is the ID of a user and the value the time
     * this user has accumulated in the given time with respect to the filtersettings
     *
     * @param int $start from this timestamp
     * @param int $end to this  timestamp
     * @param array $users IDs of user in table users
     * @param array $customers IDs of customer in table customers
     * @param array $projects IDs of project in table projects
     * @param null $activities
     * @return array
     */
    public function get_time_users($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}users.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end, userID, (end - start) / 3600 * rate AS costs, fixedRate
              FROM ${p}timeSheet
              JOIN ${p}projects USING(projectID)
              JOIN ${p}customers USING(customerID)
              JOIN ${p}users USING(userID)
              JOIN ${p}activities USING(activityID) "
                 .(count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) . " ORDER BY start DESC;";
        $result = $this->conn->Query($query);

        if (!$result) {
            $this->logLastError('get_time_users');
            return [];
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        if (!$rows) {
            return [];
        }

        $arr = [];
        $consideredStart = 0;
        $consideredEnd = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd = $row['end'];
            } elseif ($row['start'] <= $start && $row['end'] >= $end) {
                $consideredStart = $start;
                $consideredEnd = $end;
            } elseif ($row['start'] > $start && $row['end'] < $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $row['end'];
            } elseif ($row['start'] > $start && $row['end'] >= $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $end;
            }

            $time = (int)($consideredEnd - $consideredStart);
            $costs = (double)$row['costs'];
            $fixedRate = (double)$row['fixedRate'];

            if (isset($arr[$row['userID']])) {
                $arr[$row['userID']]['time']  += $time;
                if ($fixedRate > 0) {
                    $arr[$row['userID']]['costs'] += $fixedRate;
                } else {
                    $arr[$row['userID']]['costs'] += $costs;
                }
            } else {
                $arr[$row['userID']]['time'] = $time;
                if ($fixedRate > 0) {
                    $arr[$row['userID']]['costs'] = $fixedRate;
                } else {
                    $arr[$row['userID']]['costs'] = $costs;
                }
            }
        }

        return $arr;
    }

    /**
     * returns list of time summary attached to customer ID's within specific timeframe as array
     *
     * @param int $start start of timeframe in unix seconds
     * @param int $end end of timeframe in unix seconds
     * @param array $users filter for only this ID of a user
     * @param array $customers filter for only this ID of a customer
     * @param array $projects filter for only this ID of a project
     * @param array $activities
     * @return array
     */
    public function get_time_customers($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}customers.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end, customerID, (end - start) / 3600 * rate AS costs, fixedRate
              FROM ${p}timeSheet
              LEFT JOIN ${p}projects USING(projectID)
              LEFT JOIN ${p}customers USING(customerID) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->conn->Query($query);
        if (!$result) {
            $this->logLastError('get_time_customers');
            return [];
        }
        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        if (!$rows) {
            return [];
        }

        $arr = [];
        $consideredStart = 0;
        $consideredEnd = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd = $row['end'];
            } elseif ($row['start'] <= $start && $row['end'] >= $end) {
                $consideredStart = $start;
                $consideredEnd = $end;
            } elseif ($row['start'] > $start && $row['end'] < $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $row['end'];
            } elseif ($row['start'] > $start && $row['end'] >= $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $end;
            }

            $costs = (double)$row['costs'];
            $fixedRate = (double)$row['fixedRate'];

            if (isset($arr[$row['customerID']])) {
                $arr[$row['customerID']]['time']  += (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['customerID']]['costs'] += $fixedRate;
                } else {
                    $arr[$row['customerID']]['costs'] += $costs;
                }
            } else {
                $arr[$row['customerID']]['time'] = (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['customerID']]['costs'] = $fixedRate;
                } else {
                    $arr[$row['customerID']]['costs'] = $costs;
                }
            }
        }

        return $arr;
    }

    /**
     * returns list of time summary attached to project ID's within specific timeframe as array
     *
     * @param int $start start time in unix seconds
     * @param int $end end time in unix seconds
     * @param array $users filter for only this ID of a user
     * @param array $customers filter for only this ID of a customer
     * @param array $projects filter for only this ID of a project
     * @param array $activities
     * @return array
     */
    public function get_time_projects($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}projects.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end, projectID, (end - start) / 3600 * rate AS costs, fixedRate
          FROM ${p}timeSheet
          LEFT JOIN ${p}projects USING(projectID)
          LEFT JOIN ${p}customers USING(customerID) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->conn->Query($query);
        if (!$result) {
            $this->logLastError('get_time_projects');
            return [];
        }
        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        if (!$rows) {
            return [];
        }

        $arr = [];
        $consideredStart = 0;
        $consideredEnd = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd = $row['end'];
            } elseif ($row['start'] <= $start && $row['end'] >= $end) {
                $consideredStart = $start;
                $consideredEnd = $end;
            } elseif ($row['start'] > $start && $row['end'] < $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $row['end'];
            } elseif ($row['start'] > $start && $row['end'] >= $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $end;
            }

            $costs = (double)$row['costs'];
            $fixedRate = (double)$row['fixedRate'];

            if (isset($arr[$row['projectID']])) {
                $arr[$row['projectID']]['time']  += (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['projectID']]['costs'] += $fixedRate;
                } else {
                    $arr[$row['projectID']]['costs'] += $costs;
                }
            } else {
                $arr[$row['projectID']]['time'] = (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['projectID']]['costs'] = $fixedRate;
                } else {
                    $arr[$row['projectID']]['costs'] = $costs;
                }
            }
        }
        return $arr;
    }

    /**
     * returns list of time summary attached to activity ID's within specific timeframe as array
     *
     * @param int $start start time in unix seconds
     * @param int $end end time in unix seconds
     * @param array $users filter for only this ID of a user
     * @param array $customers filter for only this ID of a customer
     * @param array $projects filter for only this ID of a project
     * @param array $activities
     * @return array
     */
    public function get_time_activities($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        $start = MySQL::SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end = MySQL::SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}activities.trash = 0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end, activityID, (end - start) / 3600 * rate AS costs, fixedRate
          FROM ${p}timeSheet
          LEFT JOIN ${p}activities USING(activityID)
          LEFT JOIN ${p}projects USING(projectID)
          LEFT JOIN ${p}customers USING(customerID) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->conn->Query($query);
        if (!$result) {
            $this->logLastError('get_time_activities');
            return [];
        }
        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        if (!$rows) {
            return [];
        }

        $arr = [];
        $consideredStart = 0;
        $consideredEnd = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd = $row['end'];
            } elseif ($row['start'] <= $start && $row['end'] >= $end) {
                $consideredStart = $start;
                $consideredEnd = $end;
            } elseif ($row['start'] > $start && $row['end'] < $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $row['end'];
            } elseif ($row['start'] > $start && $row['end'] >= $end) {
                $consideredStart = $row['start'];
                $consideredEnd = $end;
            }

            $costs = (double)$row['costs'];
            $fixedRate = (double)$row['fixedRate'];

            if (isset($arr[$row['activityID']])) {
                $arr[$row['activityID']]['time']  += (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['activityID']]['costs'] += $fixedRate;
                } else {
                    $arr[$row['activityID']]['costs'] += $costs;
                }
            } else {
                $arr[$row['activityID']]['time'] = (int)($consideredEnd - $consideredStart);
                if ($fixedRate > 0) {
                    $arr[$row['activityID']]['costs'] = $fixedRate;
                } else {
                    $arr[$row['activityID']]['costs'] = $costs;
                }
            }
        }
        return $arr;
    }

    /**
     * Save rate to database.
     *
     * @param $userID
     * @param $projectID
     * @param $activityID
     * @param $rate
     * @return bool
     */
    public function save_rate($userID, $projectID, $activityID, $rate)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }
        if (!is_numeric($rate)) {
            return false;
        }


        // build update or insert statement
        if ($this->get_rate($userID, $projectID, $activityID) === false) {
            $query = "INSERT INTO " . $this->kga['server_prefix'] . "rates VALUES($userID,$projectID,$activityID,$rate);";
        } else {
            $query = "UPDATE " . $this->kga['server_prefix'] . "rates SET rate = $rate WHERE " .
                     (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
                     (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                     (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");
        }

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('save_rate');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Read rate from database.
     *
     * @param $userID
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function get_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "rates WHERE " .
                 (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
                 (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->conn->Query($query);

        if ($this->conn->RowCount() == 0) {
            return false;
        }

        $data = $this->conn->rowArray(0, MYSQLI_ASSOC);
        return $data['rate'];
    }

    /**
     * Remove rate from database.
     *
     * @param $userID
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function remove_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }


        $query = "DELETE FROM " . $this->kga['server_prefix'] . "rates WHERE " .
                 (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
                 (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('remove_rate');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Query the database for the best fitting rate for the given user, project and activity.
     *
     * @param $userID
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function get_best_fitting_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "rates WHERE
    (userID = $userID OR userID IS NULL)  AND
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY userID DESC, activityID DESC, projectID DESC
    LIMIT 1;";

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_rate');
            return false;
        }

        if ($this->conn->RowCount() == 0) {
            // no error, but no best fitting rate, return default value
            Kimai_Logger::logfile("get_best_fitting_rate - using default rate 0.00");
            return 0.0;
        }

        $data = $this->conn->rowArray(0, MYSQLI_ASSOC);
        return $data['rate'];
    }

    /**
     * Query the database for all fitting rates for the given user, project and activity.
     *
     * @param $userID
     * @param $projectID
     * @param $activityID
     * @return array|bool
     */
    public function allFittingRates($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "SELECT rate, userID, projectID, activityID FROM " . $this->kga['server_prefix'] . "rates WHERE
            (userID = $userID OR userID IS NULL)  AND
            (projectID = $projectID OR projectID IS NULL)  AND
            (activityID = $activityID OR activityID IS NULL)
            ORDER BY userID DESC, activityID DESC, projectID DESC;";

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingRates');
            return false;
        }

        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * Save fixed rate to database.
     *
     * @param $projectID
     * @param $activityID
     * @param $rate
     * @return bool
     */
    public function save_fixed_rate($projectID, $activityID, $rate)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }
        if (!is_numeric($rate)) {
            return false;
        }

        // build update or insert statement
        if ($this->get_fixed_rate($projectID, $activityID) === false) {
            $query = "INSERT INTO " . $this->kga['server_prefix'] . "fixedRates VALUES($projectID, $activityID, $rate)";
        } else {
            $query = "UPDATE " . $this->kga['server_prefix'] . "fixedRates SET rate = $rate WHERE " .
                     (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                     (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");
        }

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('save_fixed_rate');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Read fixed rate from database.
     *
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function get_fixed_rate($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "fixedRates WHERE " .
                 (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('get_fixed_rate');
            return false;
        }

        if ($this->conn->RowCount() == 0) {
            return false;
        }

        $data = $this->conn->rowArray(0, MYSQLI_ASSOC);
        return $data['rate'];
    }

    /**
     * get the whole budget used for the activity
     *
     * @param int $projectID
     * @param int $activityID
     * @return int
     */
    public function get_budget_used($projectID, $activityID)
    {
        $timeSheet = $this->get_timeSheet(0, time(), null, null, [$projectID], [$activityID]);
        $budgetUsed = 0;
        if (is_array($timeSheet)) {
            foreach ($timeSheet as $timeSheetEntry) {
                $budgetUsed += $timeSheetEntry['wage_decimal'];
            }
        }
        return $budgetUsed;
    }

    /**
     * Read activity budgets
     *
     * @param $projectID
     * @param $activityID
     * @return array|bool
     */
    public function get_activity_budget($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "SELECT budget, approved, effort FROM " . $this->kga['server_prefix'] . "projects_activities WHERE " .
                 (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('get_activity_budget');
            return false;
        }
        $data = $this->conn->rowArray(0, MYSQLI_ASSOC);
        if (!isset($data['budget'])) {
            $data['budget'] = 0;
        }
        if (!isset($data['approved'])) {
            $data['approved'] = 0;
        }

        $timeSheet = $this->get_timeSheet(0, time(), null, null, [$projectID], [$activityID]);
        foreach ($timeSheet as $timeSheetEntry) {
            if (isset($timeSheetEntry['budget'])) {
                $data['budget'] += $timeSheetEntry['budget'];
            }
            if (isset($timeSheetEntry['approved'])) {
                $data['approved'] += $timeSheetEntry['approved'];
            }
        }
        return $data;
    }

    /**
     * Remove fixed rate from database.
     *
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function remove_fixed_rate($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "DELETE FROM " . $this->kga['server_prefix'] . "fixedRates WHERE " .
                 (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('remove_fixed_rate');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Query the database for the best fitting fixed rate for the given user, project and activity.
     *
     * @param $projectID
     * @param $activityID
     * @return bool
     */
    public function get_best_fitting_fixed_rate($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "fixedRates WHERE
            (projectID = $projectID OR projectID IS NULL)  AND
            (activityID = $activityID OR activityID IS NULL)
            ORDER BY activityID DESC, projectID DESC
            LIMIT 1;";

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_fixed_rate');
            return false;
        }

        if ($this->conn->RowCount() == 0) {
            return false;
        }

        $data = $this->conn->rowArray(0, MYSQLI_ASSOC);
        return $data['rate'];
    }

    /**
     * Query the database for all fitting fixed rates for the given user, project and activity.
     *
     * @param $projectID
     * @param $activityID
     * @return array|bool
     */
    public function allFittingFixedRates($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || !is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $query = "SELECT rate, projectID, activityID FROM " . $this->kga['server_prefix'] . "fixedRates WHERE
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY activityID DESC, projectID DESC;";

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingFixedRates');
            return false;
        }

        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * Save a new secure key for a user to the database. This key is stored in the users cookie and used
     * to reauthenticate the user.
     *
     * @param $userId
     * @param $loginKey
     *
     * @return bool
     */
    public function user_loginSetKey($userId, $loginKey)
    {
        $update = [
            'secure' => $this->conn->SQLValue($loginKey),
            'ban' => 0,
            'banTime' => 0
        ];

        $where = [
            'userID' => $this->conn->SQLValue($userId)
        ];

        return $this->conn->UpdateRows($this->getUserTable(), $update, $where);
    }

    /**
     * Save a new secure key for a customer to the database. This key is stored in the clients cookie and used
     * to reauthenticate the customer.
     *
     * @param $customerId
     * @param $loginKey
     *
     * @return bool
     */
    public function customer_loginSetKey($customerId, $loginKey)
    {
        $update = [
            'secure' => $this->conn->SQLValue($loginKey),
        ];

        $where = [
            'customerID' => $this->conn->SQLValue($customerId)
        ];

        return $this->conn->UpdateRows($this->getCustomerTable(), $update, $where);
    }

    /**
     * Update the ban status of a user. This increments the ban counter.
     * Optionally it sets the start time of the ban to the current time.
     *
     * @param $userId
     * @param bool $resetTime
     */
    public function loginUpdateBan($userId, $resetTime = false)
    {
        $table = $this->getUserTable();

        $filter['userID'] = MySQL::SQLValue($userId);

        $values['ban'] = "ban+1";
        if ($resetTime) {
            $values['banTime'] = MySQL::SQLValue(time(), MySQL::SQLVALUE_NUMBER);
        }

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        $result = $this->conn->Query($query);

        if ($result === false) {
            $this->logLastError('loginUpdateBan');
        }
    }

    /**
     * Return all rows for the given sql query.
     *
     * @param string $query the sql query to execute
     * @return array
     */
    public function queryAll($query)
    {
        return $this->conn->QueryArray($query);
    }

    /**
     * checks if given $projectId exists in the db
     *
     * @param int $projectId
     * @return bool
     */
    public function isValidProjectId($projectId)
    {
        $table = $this->getProjectTable();
        $filter = ['projectID' => $projectId, 'trash' => 0];
        return $this->rowExists($table, $filter);
    }

    /**
     * checks if given $activityId exists in the db
     *
     * @param int $activityId
     * @return bool
     */
    public function isValidActivityId($activityId)
    {
        $table = $this->getActivityTable();
        $filter = ['activityID' => $activityId, 'trash' => 0];
        return $this->rowExists($table, $filter);
    }

    /**
     * Check if a user is allowed to access an object for a given action.
     *
     * @param int $userId the ID of the user
     * @param array $objectGroups list of group IDs of the object to check
     * @param string $permission name of the permission to check for
     * @param string $requiredFor (all|any) whether the permission must be present for all groups or at least one
     * @return bool
     */
    public function checkMembershipPermission($userId, $objectGroups, $permission, $requiredFor = 'all')
    {
        $userGroups = $this->getGroupMemberships($userId);
        $commonGroups = array_intersect($userGroups, $objectGroups);

        if (count($commonGroups) == 0) {
            return false;
        }

        foreach ($commonGroups as $commonGroup) {
            $roleId = $this->user_get_membership_role($userId, $commonGroup);

            if ($requiredFor == 'any' && $this->membership_role_allows($roleId, $permission)) {
                return true;
            }
            if ($requiredFor == 'all' && !$this->membership_role_allows($roleId, $permission)) {
                return false;
            }
        }

        return $requiredFor == 'all';
    }

    /**
     * Returns the membership roleID the user has in the given group.
     *
     * @param int $userID the ID of the user
     * @param int $groupID the ID of the group
     * @return int|bool membership roleID or false if user is not in the group
     */
    public function user_get_membership_role($userID, $groupID)
    {
        $filter['userID'] = MySQL::SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $filter['groupID'] = MySQL::SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $columns[] = "membershipRoleID";
        $table = $this->kga['server_prefix'] . "groups_users";

        $result = $this->conn->SelectRows($table, $filter, $columns);

        if ($result === false) {
            return false;
        }

        $row = $this->conn->RowArray(0, MYSQLI_ASSOC);
        return $row['membershipRoleID'];
    }

    /**
     * Check if a membership role gives permission for a specific action.
     *
     * @param int $roleID the ID of the membership role
     * @param string $permission name of the action / permission
     * @return bool true if permissions is granted, false otherwise
     */
    public function membership_role_allows($roleID, $permission)
    {
        $filter['membershipRoleID'] = MySQL::SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[$permission] = 1;
        $columns[] = "membershipRoleID";
        $table = $this->kga['server_prefix'] . "membershipRoles";

        $result = $this->conn->SelectRows($table, $filter, $columns);

        if ($result === false) {
            return false;
        }

        return $this->conn->RowCount() > 0;
    }

    /**
     * Check if a global role gives permission for a specific action.
     *
     * @param int $roleID the ID of the global role
     * @param string $permission name of the action / permission
     * @return bool true if permissions is granted, false otherwise
     */
    public function global_role_allows($roleID, $permission)
    {
        $filter['globalRoleID'] = MySQL::SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[$permission] = 1;
        $columns[] = "globalRoleID";
        $table = $this->kga['server_prefix'] . "globalRoles";

        $result = $this->conn->SelectRows($table, $filter, $columns);

        if ($result === false) {
            $this->logLastError('global_role_allows');
            return false;
        }

        $result = $this->conn->RowCount() > 0;

        /*
        // TODO should we add a setting for debugging permissions?
        Kimai_Logger::logfile("Global role $roleID gave " . ($result ? 'true' : 'false') . " for $permission.");
        */
        return $result;
    }

    /**
     * @param $data
     * @return bool|int
     */
    public function global_role_create($data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = MySQL::SQLValue($value);
            } else {
                $values[$key] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $table = $this->kga['server_prefix'] . "globalRoles";
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('global_role_create');
            return false;
        }

        return $this->conn->GetLastInsertID();
    }

    /**
     * @param $globalRoleID
     * @param $data
     * @return bool
     */
    public function global_role_edit($globalRoleID, $data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = MySQL::SQLValue($value);
            } else {
                $values[$key] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['globalRoleID'] = MySQL::SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "globalRoles";

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_edit');
            return false;
        }

        return true;
    }

    /**
     * @param $globalRoleID
     * @return bool
     */
    public function global_role_delete($globalRoleID)
    {
        $table = $this->kga['server_prefix'] . "globalRoles";
        $filter['globalRoleID'] = MySQL::SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $query = MySQL::BuildSQLDelete($table, $filter);
        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_delete');
            return false;
        }

        return true;
    }

    /**
     * @param $globalRoleID
     * @return array|bool
     */
    public function globalRole_get_data($globalRoleID)
    {
        $filter['globalRoleID'] = MySQL::SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "globalRoles";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('globalRole_get_data');
            return false;
        } else {
            return $this->conn->RowArray(0, MYSQLI_ASSOC);
        }
    }

    /**
     * @param $filter
     * @return array|bool
     */
    public function globalRole_find($filter)
    {
        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            } else {
                $value = MySQL::SQLValue($value);
            }
        }
        $table = $this->kga['server_prefix'] . "globalRoles";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('globalRole_find');
            return false;
        } else {
            return $this->conn->RecordsArray(MYSQLI_ASSOC);
        }
    }

    /**
     * @return array|bool
     */
    public function global_roles()
    {
        $p = $this->kga['server_prefix'];

        $query = "SELECT a.*, COUNT(b.globalRoleID) AS count_users FROM `${p}globalRoles` a LEFT JOIN `${p}users` b USING(globalRoleID) GROUP BY a.globalRoleID";

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('global_roles');
            return false;
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        return $rows;
    }

    /**
     * @param $data
     * @return bool|int
     */
    public function membership_role_create($data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = MySQL::SQLValue($value);
            } else {
                $values[$key] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $table = $this->kga['server_prefix'] . "membershipRoles";
        $result = $this->conn->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('membership_role_create');
            return false;
        }

        return $this->conn->GetLastInsertID();
    }

    /**
     * @param $membershipRoleID
     * @param $data
     * @return object
     */
    public function membership_role_edit($membershipRoleID, $data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = MySQL::SQLValue($value);
            } else {
                $values[$key] = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['membershipRoleID'] = MySQL::SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "membershipRoles";

        $query = MySQL::BuildSQLUpdate($table, $values, $filter);

        return $this->conn->Query($query);
    }

    /**
     * @param $membershipRoleID
     * @return bool
     */
    public function membership_role_delete($membershipRoleID)
    {
        $table = $this->kga['server_prefix'] . "membershipRoles";
        $filter['membershipRoleID'] = MySQL::SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $query = MySQL::BuildSQLDelete($table, $filter);
        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('membership_role_delete');
            return false;
        }

        return true;
    }

    /**
     * @param $membershipRoleID
     * @return array|bool
     */
    public function membershipRole_get_data($membershipRoleID)
    {
        $filter['membershipRoleID'] = MySQL::SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $table = $this->kga['server_prefix'] . "membershipRoles";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_get_data');
            return false;
        }

        return $this->conn->RowArray(0, MYSQLI_ASSOC);
    }

    /**
     * @param $filter
     * @return array|bool
     */
    public function membershipRole_find($filter)
    {
        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = MySQL::SQLValue($value, MySQL::SQLVALUE_NUMBER);
            } else {
                $value = MySQL::SQLValue($value);
            }
        }
        $table = $this->kga['server_prefix'] . "membershipRoles";
        $result = $this->conn->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_find');
            return false;
        }

        return $this->conn->RecordsArray(MYSQLI_ASSOC);
    }

    /**
     * @return array|bool
     */
    public function membership_roles()
    {
        $p = $this->kga['server_prefix'];

        $query = "SELECT a.*, COUNT(DISTINCT b.userID) as count_users FROM `${p}membershipRoles` a LEFT JOIN `${p}groups_users` b USING(membershipRoleID) GROUP BY a.membershipRoleID";

        $result = $this->conn->Query($query);

        if ($result == false) {
            $this->logLastError('membership_roles');
            return false;
        }

        $rows = $this->conn->RecordsArray(MYSQLI_ASSOC);
        return $rows;
    }


    /**
     * checks if a given db row based on the $idColumn & $id exists
     * @param string $table
     * @param array $filter
     * @return bool
     */
    protected function rowExists($table, array $filter)
    {
        $select = $this->conn->SelectRows($table, $filter);

        if (!$select) {
            $this->logLastError('rowExists');
            return false;
        }

        return (bool)$this->conn->RowArray(0, MYSQLI_ASSOC);
    }
}
