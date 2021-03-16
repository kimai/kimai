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

$all_column_headers = [
    'date',
    'from',
    'to',
    'time',
    'dec_time',
    'rate',
    'wage',
    'budget',
    'approved',
    'status',
    'billable',
    'customer',
    'project',
    'activity',
    'description',
    'comment',
    'location',
    'trackingNumber',
    'user',
    'cleared'
];
// Determine if the expenses extension is used.
$expense_ext_available = false;
if (file_exists('../ki_expenses/private_db_layer_mysql.php')) {
    include '../ki_expenses/private_db_layer_mysql.php';
    $expense_ext_available = true;
}
include 'private_db_layer_mysql.php';

/**
 * Get a combined array with time recordings and expenses to export.
 *
 * @param int $start Time from which to take entries into account.
 * @param int $end Time until which to take entries into account.
 * @param array $users Array of user IDs to filter by.
 * @param array $customers Array of customer IDs to filter by.
 * @param array $projects Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 * @param bool $limit sbould the amount of entries be limited
 * @param bool $reverse_order should the entries be put out in reverse order
 * @param string $default_location use this string if no location is set for the entry
 * @param int $filter_cleared (-1: show all, 0:only cleared 1: only not cleared) entries
 * @param int $filter_type (-1 show time and expenses, 0: only show time entries, 1: only show expenses)
 * @param bool $limitCommentSize should comments be cut off, when they are too long
 * @param int $filter_refundable
 * @param bool $groupedEntries
 * @return array with time recordings and expenses chronologically sorted
 */
function export_get_data(
    $start,
    $end,
    $users = null,
    $customers = null,
    $projects = null,
    $activities = null,
    $limit = false,
    $reverse_order = false,
    $default_location = '',
    $filter_cleared = -1,
    $filter_type = -1,
    $limitCommentSize = true,
    $filter_refundable = -1,
    $groupedEntries = false
) {
    global $expense_ext_available;
    $database = Kimai_Registry::getDatabase();
    $timeSheetEntries = [];
    $expenses = [];
    if ($filter_type != 1) {
        $timeSheetEntries = $database->get_timeSheet(
            $start,
            $end,
            $users,
            $customers,
            $projects,
            $activities,
            $limit,
            $reverse_order,
            $filter_cleared,
            0,
            0,
            false,
            $groupedEntries
        );
    }

    if ($filter_type != 0 && $expense_ext_available) {
        $expenses = get_expenses(
            $start,
            $end,
            $users,
            $customers,
            $projects,
            $limit,
            $reverse_order,
            $filter_refundable,
            $filter_cleared
        );
    }
    $result_arr = [];
    $timeSheetEntries_index = 0;
    $expenses_index = 0;
    $keys = [
        'type',
        'id',
        'time_in',
        'time_out',
        'duration',
        'formattedDuration',
        'decimalDuration',
        'rate',
        'wage',
        'wage_decimal',
        'budget',
        'approved',
        'statusID',
        'status',
        'billable',
        'customerID',
        'customerName',
        'projectID',
        'projectName',
        'description',
        'projectComment',
        'activityID',
        'activityName',
        'comment',
        'commentType',
        'location',
        'trackingNumber',
        'username',
        'cleared'
    ];
    $timeSheetEntriesCount = count($timeSheetEntries);
    $expensesCount = count($expenses);
    while ($timeSheetEntries_index < $timeSheetEntriesCount && $expenses_index < $expensesCount) {
        $arr = [];
        foreach ($keys as $key) {
            $arr[$key] = null;
        }
        $arr['location'] = $default_location;

        if ((!$reverse_order && ($timeSheetEntries[$timeSheetEntries_index]['start'] > $expenses[$expenses_index]['timestamp']))
            || ($reverse_order && ($timeSheetEntries[$timeSheetEntries_index]['start'] < $expenses[$expenses_index]['timestamp']))
        ) {
            if ($timeSheetEntries[$timeSheetEntries_index]['end'] != 0) {
                // active recordings will be omitted
                $arr['type'] = 'timeSheet';
                if (isset($timeSheetEntries[$timeSheetEntries_index]['timeEntryID'])) {
                    $arr['id'] = $timeSheetEntries[$timeSheetEntries_index]['timeEntryID'];
                }
                $arr['time_in'] = $timeSheetEntries[$timeSheetEntries_index]['start'];
                $arr['time_out'] = $timeSheetEntries[$timeSheetEntries_index]['end'];
                $arr['duration'] = $timeSheetEntries[$timeSheetEntries_index]['duration'];
                $arr['formattedDuration'] = $timeSheetEntries[$timeSheetEntries_index]['formattedDuration'];
                $arr['decimalDuration'] = sprintf('%01.2f', $timeSheetEntries[$timeSheetEntries_index]['duration'] / 3600);
                $arr['rate'] = $timeSheetEntries[$timeSheetEntries_index]['rate'];
                $arr['wage'] = $timeSheetEntries[$timeSheetEntries_index]['wage'];
                $arr['wage_decimal'] = $timeSheetEntries[$timeSheetEntries_index]['wage_decimal'];
                $arr['budget'] = $timeSheetEntries[$timeSheetEntries_index]['budget'];
                $arr['approved'] = $timeSheetEntries[$timeSheetEntries_index]['approved'];
                $arr['statusID'] = $timeSheetEntries[$timeSheetEntries_index]['statusID'];
                $arr['status'] = $timeSheetEntries[$timeSheetEntries_index]['status'];
                $arr['billable'] = $timeSheetEntries[$timeSheetEntries_index]['billable'];
                $arr['customerID'] = $timeSheetEntries[$timeSheetEntries_index]['customerID'];
                $arr['customerName'] = $timeSheetEntries[$timeSheetEntries_index]['customerName'];
                $arr['projectID'] = $timeSheetEntries[$timeSheetEntries_index]['projectID'];
                $arr['projectName'] = $timeSheetEntries[$timeSheetEntries_index]['projectName'];
                $arr['description'] = $timeSheetEntries[$timeSheetEntries_index]['description'];
                $arr['projectComment'] = $timeSheetEntries[$timeSheetEntries_index]['projectComment'];
                $arr['activityID'] = $timeSheetEntries[$timeSheetEntries_index]['activityID'];
                $arr['activityName'] = $timeSheetEntries[$timeSheetEntries_index]['activityName'];
                if ($limitCommentSize) {
                    $arr['comment'] = Kimai_Format::addEllipsis($timeSheetEntries[$timeSheetEntries_index]['comment'], 150);
                } else {
                    $arr['comment'] = $timeSheetEntries[$timeSheetEntries_index]['comment'];
                }
                $arr['commentType'] = $timeSheetEntries[$timeSheetEntries_index]['commentType'];
                $arr['location'] = $timeSheetEntries[$timeSheetEntries_index]['location'];
                $arr['trackingNumber'] = $timeSheetEntries[$timeSheetEntries_index]['trackingNumber'];
                $arr['username'] = $timeSheetEntries[$timeSheetEntries_index]['userName'];
                $arr['cleared'] = $timeSheetEntries[$timeSheetEntries_index]['cleared'];
                $result_arr[] = $arr;
            }
            $timeSheetEntries_index++;
        } else {
            $arr['type'] = 'expense';
            $arr['id'] = $expenses[$expenses_index]['expenseID'];
            $arr['time_in'] = $expenses[$expenses_index]['timestamp'];
            $arr['time_out'] = $expenses[$expenses_index]['timestamp'];
            $arr['wage'] = sprintf('%01.2f', $expenses[$expenses_index]['value'] * $expenses[$expenses_index]['multiplier']);
            $arr['customerID'] = $expenses[$expenses_index]['customerID'];
            $arr['customerName'] = $expenses[$expenses_index]['customerName'];
            $arr['projectID'] = $expenses[$expenses_index]['projectID'];
            $arr['projectName'] = $expenses[$expenses_index]['projectName'];
            $arr['description'] = $expenses[$expenses_index]['designation'];
            $arr['projectComment'] = $expenses[$expenses_index]['projectComment'];
            if ($limitCommentSize) {
                $arr['comment'] = Kimai_Format::addEllipsis($expenses[$expenses_index]['comment'], 150);
            } else {
                $arr['comment'] = $expenses[$expenses_index]['comment'];
            }
            $arr['activityName'] = $expenses[$expenses_index]['designation'];
            $arr['comment'] = $expenses[$expenses_index]['comment'];
            $arr['commentType'] = $expenses[$expenses_index]['commentType'];
            $arr['username'] = $expenses[$expenses_index]['userName'];
            $arr['cleared'] = $expenses[$expenses_index]['cleared'];
            $result_arr[] = $arr;
            $expenses_index++;
        }
    }
    while ($timeSheetEntries_index < count($timeSheetEntries)) {
        if ($timeSheetEntries[$timeSheetEntries_index]['end'] != 0) {
            // active recordings will be omitted
            $arr = [];
            foreach ($keys as $key) {
                $arr[$key] = null;
            }
            $arr['location'] = $default_location;

            $arr['type'] = 'timeSheet';
            $arr['id'] = $timeSheetEntries[$timeSheetEntries_index]['timeEntryID'];
            $arr['time_in'] = $timeSheetEntries[$timeSheetEntries_index]['start'];
            $arr['time_out'] = $timeSheetEntries[$timeSheetEntries_index]['end'];
            $arr['duration'] = $timeSheetEntries[$timeSheetEntries_index]['duration'];
            $arr['formattedDuration'] = $timeSheetEntries[$timeSheetEntries_index]['formattedDuration'];
            $arr['decimalDuration'] = sprintf('%01.2f', $timeSheetEntries[$timeSheetEntries_index]['duration'] / 3600);
            $arr['rate'] = $timeSheetEntries[$timeSheetEntries_index]['rate'];
            $arr['wage'] = $timeSheetEntries[$timeSheetEntries_index]['wage'];
            $arr['wage_decimal'] = $timeSheetEntries[$timeSheetEntries_index]['wage_decimal'];
            $arr['budget'] = $timeSheetEntries[$timeSheetEntries_index]['budget'];
            $arr['approved'] = $timeSheetEntries[$timeSheetEntries_index]['approved'];
            $arr['statusID'] = $timeSheetEntries[$timeSheetEntries_index]['statusID'];
            $arr['status'] = $timeSheetEntries[$timeSheetEntries_index]['status'];
            $arr['billable'] = $timeSheetEntries[$timeSheetEntries_index]['billable'];
            $arr['customerID'] = $timeSheetEntries[$timeSheetEntries_index]['customerID'];
            $arr['customerName'] = $timeSheetEntries[$timeSheetEntries_index]['customerName'];
            $arr['projectID'] = $timeSheetEntries[$timeSheetEntries_index]['projectID'];
            $arr['projectName'] = $timeSheetEntries[$timeSheetEntries_index]['projectName'];
            $arr['projectComment'] = $timeSheetEntries[$timeSheetEntries_index]['projectComment'];
            $arr['activityID'] = $timeSheetEntries[$timeSheetEntries_index]['activityID'];
            $arr['activityName'] = $timeSheetEntries[$timeSheetEntries_index]['activityName'];
            $arr['description'] = $timeSheetEntries[$timeSheetEntries_index]['description'];
            if ($limitCommentSize) {
                $arr['comment'] = Kimai_Format::addEllipsis($timeSheetEntries[$timeSheetEntries_index]['comment'], 150);
            } else {
                $arr['comment'] = $timeSheetEntries[$timeSheetEntries_index]['comment'];
            }
            $arr['commentType'] = $timeSheetEntries[$timeSheetEntries_index]['commentType'];
            $arr['location'] = $timeSheetEntries[$timeSheetEntries_index]['location'];
            $arr['trackingNumber'] = $timeSheetEntries[$timeSheetEntries_index]['trackingNumber'];
            $arr['username'] = $timeSheetEntries[$timeSheetEntries_index]['userName'];
            $arr['cleared'] = $timeSheetEntries[$timeSheetEntries_index]['cleared'];
            $result_arr[] = $arr;
        }
        $timeSheetEntries_index++;
    }
    while ($expenses_index < count($expenses)) {
        $arr = [];
        foreach ($keys as $key) {
            $arr[$key] = null;
        }
        $arr['location'] = $default_location;

        $arr['type'] = 'expense';
        $arr['id'] = $expenses[$expenses_index]['expenseID'];
        $arr['time_in'] = $expenses[$expenses_index]['timestamp'];
        $arr['time_out'] = $expenses[$expenses_index]['timestamp'];
        $arr['wage'] = sprintf('%01.2f', $expenses[$expenses_index]['value'] * $expenses[$expenses_index]['multiplier']);
        $arr['customerID'] = $expenses[$expenses_index]['customerID'];
        $arr['customerName'] = $expenses[$expenses_index]['customerName'];
        $arr['projectID'] = $expenses[$expenses_index]['projectID'];
        $arr['projectName'] = $expenses[$expenses_index]['projectName'];
        $arr['description'] = $expenses[$expenses_index]['designation'];
        $arr['projectComment'] = $expenses[$expenses_index]['projectComment'];
        if ($limitCommentSize) {
            $arr['comment'] = Kimai_Format::addEllipsis($expenses[$expenses_index]['comment'], 150);
        } else {
            $arr['comment'] = $expenses[$expenses_index]['comment'];
        }
        $arr['commentType'] = $expenses[$expenses_index]['commentType'];
        $arr['username'] = $expenses[$expenses_index]['userName'];
        $arr['cleared'] = $expenses[$expenses_index]['cleared'];
        $expenses_index++;
        $result_arr[] = $arr;
    }

    return $result_arr;
}

/**
 * Merge the expense annotations with the timesheet annotations. The result will
 * be the timesheet array, which has to be passed as the first argument.
 *
 * @param array the timesheet annotations array
 * @param array the expense annotations array
 */
function merge_annotations(&$timeSheetEntries, &$expenses)
{
    foreach ($expenses as $id => $costs) {
        if (! isset($timeSheetEntries[$id])) {
            $timeSheetEntries[$id]['costs'] = $costs;
        } else {
            $timeSheetEntries[$id]['costs'] += $costs;
        }
    }
}

/**
 * Get annotations for the user sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int $start Time from which to take entries into account.
 * @param int $end Time until which to take entries into account.
 * @param array $users Array of user IDs to filter by.
 * @param array $customers Array of customer IDs to filter by.
 * @param array $projects Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 * @return array Array which assigns every user (via his ID) the data to show.
 */
function export_get_user_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available;
    $database = Kimai_Registry::getDatabase();
    $arr = $database->get_time_users($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $expenses = expenses_by_user($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $expenses);
    }

    return $arr;
}

/**
 * Get annotations for the customer sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int $start Time from which to take entries into account.
 * @param int $end Time until which to take entries into account.
 * @param array $users Array of user IDs to filter by.
 * @param array $customers Array of customer IDs to filter by.
 * @param array $projects Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 * @return array Array which assigns every customer (via his ID) the data to show.
 */
function export_get_customer_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available;
    $database = Kimai_Registry::getDatabase();
    $arr = $database->get_time_customers($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $expenses = expenses_by_customer($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $expenses);
    }

    return $arr;
}

/**
 * Get annotations for the project sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int $start Time from which to take entries into account.
 * @param int $end Time until which to take entries into account.
 * @param array $users Array of user IDs to filter by.
 * @param array $customers Array of customer IDs to filter by.
 * @param array $projects Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 * @return array Array which assigns every project (via his ID) the data to show.
 */
function export_get_project_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available;
    $database = Kimai_Registry::getDatabase();
    $arr = $database->get_time_projects($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $expenses = expenses_by_project($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $expenses);
    }

    return $arr;
}

/**
 * Get annotations for the activity sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int $start Time from which to take entries into account.
 * @param int $end Time until which to take entries into account.
 * @param array $users Array of user IDs to filter by.
 * @param array $customers Array of customer IDs to filter by.
 * @param array $projects Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 * @return array Array which assigns every taks (via his ID) the data to show.
 */
function export_get_activity_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    $database = Kimai_Registry::getDatabase();
    return $database->get_time_activities($start, $end, $users, $customers, $projects, $activities);
}

/**
 * Prepare a string to be printed as a single field in the csv file.
 *
 * @param string $field String to prepare.
 * @param string $column_delimiter Character used to delimit columns.
 * @param string $quote_char Character used to quote strings.
 * @return string Correctly formatted string.
 */
function csv_prepare_field($field, $column_delimiter, $quote_char)
{
    if (strpos($field, $column_delimiter) === false && strpos($field, $quote_char) === false && strpos($field, "\n") === false) {
        return $field;
    }
    $field = str_replace($quote_char, $quote_char . $quote_char, $field);
    return $quote_char . $field . $quote_char;
}
