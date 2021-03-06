<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Financial_Page_AJAX {
  /*
   * Function to get financial accounts of required account relationship
   * $financialAccountType array with key account relationship and value financial account type option groups
   *
   */
  function jqFinancial($config) {
    if (!isset($_GET['_value']) ||
      empty($_GET['_value'])) {
      CRM_Utils_System::civiExit();
    }

    if ($_GET['_value'] == 'select') {
      $result = CRM_Contribute_PseudoConstant::financialAccount();
    }
    else {
      $financialAccountType = array(
        '5' => 5, //expense
        '3' => 1, //AR relation
        '1' => 3, //revenue
        '6' => 1, // asset
        '7' => 4, //cost of sales
        '8' => 1, //premium inventory
        '9' => 3, //discount account is
      );

      $financialAccountType = "{$financialAccountType[$_GET['_value']]}";
      $result = CRM_Contribute_PseudoConstant::financialAccount(NULL, $financialAccountType);
    }
    $elements = array(
      array(
        'name'  => ts('- select -'),
        'value' => 'select',
      )
    );

    if (!empty($result)){
      foreach ($result as $id => $name) {
        $elements[] = array(
          'name'  => $name,
          'value' => $id,
        );
      }
    }
    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  function jqFinancialRelation($config) {
    if (!isset($_GET['_value']) ||
      empty($_GET['_value'])) {
      CRM_Utils_System::civiExit();
    }

    if ($_GET['_value'] == 'select') {
      $result = CRM_Core_PseudoConstant::accountOptionValues('account_relationship');
    }
    else {
      $financialAccountType = array(
        '5' => array(5), //expense
        '1' => array(3, 6, 8), //Asset
        '3' => array(1, 9), //revenue
        '4' => array(7), //cost of sales
      );
      $financialAccountTypeId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $_GET['_value'], 'financial_account_type_id');
      $result = CRM_Core_PseudoConstant::accountOptionValues('account_relationship');
    }

    $elements = array(
      array(
        'name'  => ts('- Select Financial Account Relationship -'),
        'value' => 'select',
      )
    );

    $countResult = count($financialAccountType[$financialAccountTypeId]);
    if (!empty($result)) {
      foreach ($result as $id => $name) {
        if (in_array($id, $financialAccountType[$financialAccountTypeId])  && $_GET['_value'] != 'select') {
          if ($countResult != 1){
            $elements[] = array(
              'name'  => $name,
              'value' => $id,
            );
          }
          else {
            $elements[] = array(
              'name'     => $name,
              'value'    => $id,
              'selected' => 'Selected',
            );
          }
        }
        elseif ($_GET['_value'] == 'select'){
          $elements[] = array(
            'name'  => $name,
            'value' => $id,
          );
        }
      }
    }
    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  function jqFinancialType($config) {
    if (! isset($_GET['_value']) ||
      empty($_GET['_value'])) {
      CRM_Utils_System::civiExit();
    }

    $elements = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Product', $_GET['_value'], 'financial_type_id');
    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Callback to perform action on batch records.
   */
  static function assignRemove() {
    $op = CRM_Utils_Type::escape($_POST['op'], 'String');
    $recordBAO = CRM_Utils_Type::escape($_POST['recordBAO'], 'String');
    foreach ($_POST['records'] as $record) {
      $recordID = CRM_Utils_Type::escape($record, 'Positive', FALSE);
      if ($recordID) {
        $records[] = $recordID;
      }
    }

    $entityID  = CRM_Utils_Array::value('entityID', $_POST);
    $methods = array(
      'assign' => 'addBatchEntity',
      'remove' => 'removeBatchEntity',
      'reopen' => 'create',
      'close' => 'create',
      'delete' => 'deleteBatch',
    );
    if ($op == 'close') {
      $totals = CRM_Batch_BAO_Batch::batchTotals($records);
    }
    $response = array('status' => 'record-updated-fail');
    // first munge and clean the recordBAO and get rid of any non alpha numeric characters
    $recordBAO = CRM_Utils_String::munge($recordBAO);
    $recordClass = explode('_', $recordBAO);
    // make sure recordClass is in the CRM namespace and
    // at least 3 levels deep
    if ($recordClass[0] == 'CRM' && count($recordClass) >= 3) {
      foreach ($records as $recordID) {
        $params = array();
        $ids = null;
        switch ($op) {
          case 'assign':
          case 'remove':
            $recordPID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $recordID, 'payment_instrument_id');
            $batchPID = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $entityID, 'payment_instrument_id');
            $paymentInstrument =  CRM_Core_OptionGroup::getLabel('payment_instrument',$batchPID);
            if ($op == 'remove' || ($recordPID == $batchPID && $op == 'assign') || !isset($batchPID)) {
              $params = array(
                'entity_id' => $recordID,
                'entity_table' => 'civicrm_financial_trxn',
                'batch_id' => $entityID,
              );
            }
            else {
              $response = array('status' => ts("This batch is configured to include only transactions using %1 payment method. If you want to include other transactions, please edit the batch first and modify the Payment Method.", array( 1 => $paymentInstrument)));
            }
            break;
          case 'close':
            // Update totals when closing a batch
            $params = $totals[$recordID];
          case 'reopen':
            $status = $op == 'close' ? 'Closed' : 'Open';
            $ids['batchID'] = $recordID;
            $batchStatus = CRM_Core_PseudoConstant::accountOptionValues('batch_status');
            $params['status_id'] = CRM_Utils_Array::key($status, $batchStatus);
            $session = CRM_Core_Session::singleton();
            $params['modified_date'] = date('YmdHis');
            $params['modified_id'] = $session->get('userID');
            $params['id'] = $recordID;
            $context = "financialBatch";
            break;

          case 'export':
            CRM_Utils_System::redirect("civicrm/financial/batch/export?reset=1&id=$recordID");
            break;

          case 'delete':
            $params = $recordID;
            $context = "financialBatch";
            break;
        }

        if (method_exists($recordBAO, $methods[$op]) & !empty($params)) {
          if (isset($context)) {
            $updated = call_user_func_array(array($recordBAO, $methods[$op]), array(&$params, $ids, $context));
          }
          else {
            $updated = call_user_func_array(array($recordBAO, $methods[$op]), array(&$params, $ids));
          }
          if ($updated) {
            $response = array('status' => 'record-updated-success');
          }
        }
      }
    }
    echo json_encode($response);
    CRM_Utils_System::civiExit();
  }

  static function getFinancialTransactionsList() {
    $sortMapper =
      array(
        0 => '', 1 => '', 2 => 'sort_name',
        3 => 'amount', 4 => 'trxn_id', 5 => 'transaction_date', 6 => 'payment_method', 7 => 'status', 8 => 'name',
      );

    $sEcho     = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset    = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount  = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort      = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';
    $context   = isset($_REQUEST['context']) ? CRM_Utils_Type::escape($_REQUEST['context'], 'String') : NULL;
    $entityID  = isset($_REQUEST['entityID']) ? CRM_Utils_Type::escape($_REQUEST['entityID'], 'String') : NULL;
    $notPresent = isset($_REQUEST['notPresent']) ? CRM_Utils_Type::escape($_REQUEST['notPresent'], 'String') : NULL;
    $statusID  = isset($_REQUEST['statusID']) ? CRM_Utils_Type::escape($_REQUEST['statusID'], 'String') : NULL;
    $search    = isset($_REQUEST['search']) ? TRUE : FALSE;

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $returnvalues =
      array(
        'civicrm_financial_trxn.payment_instrument_id as payment_method',
        'civicrm_contribution.contact_id as contact_id',
        'civicrm_grant.contact_id as contact_id_grant',
        'civicrm_contribution.id as contributionID',
        'civicrm_grant.id as grantID',
        'contact_a.sort_name',
        'civicrm_financial_trxn.total_amount as amount',
        'civicrm_financial_trxn.trxn_id as trxn_id',
        'contact_a.contact_type',
        'contact_a.contact_sub_type',
        'civicrm_financial_trxn.trxn_date as transaction_date',
        'name',
        'civicrm_contribution.currency as currency',
        'civicrm_financial_trxn.status_id as status',
        'civicrm_financial_trxn.check_number as check_number',
      );

    $columnHeader =
      array(
        'contact_type' => '',
        'sort_name' => ts('Contact Name'),
        'amount'   => ts('Amount'),
        'trxn_id'  => ts('Trxn ID'),
        'transaction_date' => ts('Received'),
        'payment_method' => ts('Payment Method'),
        'status'  => ts('Status'),
        'name' => ts('Type'),
      );

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset']   = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort']     = CRM_Utils_Array::value('sortBy', $params);
    $params['total']    = 0;

    // get batch list
    if (isset($notPresent)) {
      $financialItem = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params);
      if ($search) {
        $unassignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params, TRUE);
      }
      else {
        $unassignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, NULL, TRUE);
      }
      while ($unassignedTransactions->fetch()) {
        $unassignedTransactionsCount[] = $unassignedTransactions->id;
      }
      if (!empty($unassignedTransactionsCount)) {
        $params['total'] = count($unassignedTransactionsCount);
      }

    }
    else {
      $financialItem = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, NULL, $params);
      $assignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues);
      while ($assignedTransactions->fetch()) {
        $assignedTransactionsCount[] = $assignedTransactions->id;
      }
      if (!empty($assignedTransactionsCount)) {
        $params['total'] = count($assignedTransactionsCount);
      }
    }
    $financialitems = array();
    while ($financialItem->fetch()) {
      $row[$financialItem->id] = array();
      foreach ($columnHeader as $columnKey => $columnValue) {
        if ($financialItem->contact_sub_type && $columnKey == 'contact_type') {
          $row[$financialItem->id][$columnKey] = $financialItem->contact_sub_type;
          continue;
        }
        $row[$financialItem->id][$columnKey] = $financialItem->$columnKey;
        if ($columnKey == 'sort_name' && $financialItem->$columnKey) {
          $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=".$financialItem->contact_id);
          $row[$financialItem->id][$columnKey] = '<a href='.$url.'>'.$financialItem->$columnKey.'</a>';
        }
        elseif ($columnKey == 'payment_method' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_OptionGroup::getLabel('payment_instrument', $financialItem->$columnKey);
          if ($row[$financialItem->id][$columnKey] == 'Check') {
            $row[$financialItem->id][$columnKey] = $row[$financialItem->id][$columnKey].' ('.$financialItem->check_number.')';
          }
        }
        elseif ($columnKey == 'amount' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Money::format($financialItem->$columnKey, $financialItem->currency);
        }
        elseif ($columnKey == 'transaction_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] =  CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'status' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_OptionGroup::getLabel('contribution_status', $financialItem->$columnKey);
        }
      }
      if ($financialItem->grantID) {
        $entityID = $financialItem->grantID;
        $contactID = $financialItem->contact_id_grant;
        $url = "civicrm/contact/view/grant";
      }
      elseif ($financialItem->contributionID) {
        $entityID = $financialItem->contributionID;
        $contactID = $financialItem->contact_id;
        $url = "civicrm/contact/view/contribution";
      }
      if ($statusID == CRM_Core_OptionGroup::getValue('batch_status','Open')) {
        if (isset($notPresent)) {
          $js = "enableActions('x')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_x_". $financialItem->id."' name='mark_x_". $financialItem->id."' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(CRM_Financial_Form_BatchTransaction::links(), null, array('id' => $financialItem->id, 'contid' => $entityID, 'cid' => $contactID, 'url' => $url));
        }
        else {
          $js = "enableActions('y')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_y_". $financialItem->id."' name='mark_y_". $financialItem->id."' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(CRM_Financial_Page_BatchTransaction::links(), null, array('id' => $financialItem->id, 'contid' => $entityID, 'cid' => $contactID, 'url' => $url));
        }
      }
      else {
        $row[$financialItem->id]['check'] = NULL;
        $links = CRM_Financial_Page_BatchTransaction::links();
        unset($links['remove']);
        $row[$financialItem->id]['action'] = CRM_Core_Action::formLink($links, null, array('id' => $financialItem->id, 'contid' => $entityID, 'cid' => $contactID, 'url' => $url));
      }
      $row[$financialItem->id]['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage(CRM_Utils_Array::value('contact_sub_type',$row[$financialItem->id]) ? CRM_Utils_Array::value('contact_sub_type',$row[$financialItem->id]) : CRM_Utils_Array::value('contact_type',$row[$financialItem->id]) ,false, $contactID);
      $financialitems = $row;
    }

    $iFilteredTotal = $iTotal =  $params['total'];
    $selectorElements =
      array(
        'check', 'contact_type', 'sort_name',
        'amount', 'trxn_id', 'transaction_date', 'payment_method', 'status', 'name', 'action',
      );

    echo CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  static function bulkAssignRemove() {
    $checkIDs = $_REQUEST['ID'];
    $entityID = CRM_Utils_Type::escape($_REQUEST['entityID'], 'String');
    $action   = CRM_Utils_Type::escape($_REQUEST['action'], 'String');
    foreach ($checkIDs as $key => $value) {
      if ((substr($value,0,7) == "mark_x_" && $action == 'Assign') || (substr($value,0,7) == "mark_y_" && $action == 'Remove')) {
        $contributions = explode("_",$value);
        $cIDs[] = $contributions[2];
      }
    }

    $batchPID = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $entityID, 'payment_instrument_id');
    $paymentInstrument =  CRM_Core_OptionGroup::getLabel('payment_instrument',$batchPID);
    foreach ($cIDs as $key => $value) {
      $recordPID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $value, 'payment_instrument_id');
      if ($action == 'Remove' || ($recordPID == $batchPID && $action == 'Assign')  || !isset($batchPID)) {
        $params =
          array(
            'entity_id' => $value,
            'entity_table' => 'civicrm_financial_trxn',
            'batch_id' => $entityID,
          );
        if ($action == 'Assign') {
          $updated = CRM_Batch_BAO_Batch::addBatchEntity($params);
        }
        else {
          $updated = CRM_Batch_BAO_Batch::removeBatchEntity($params);
        }
      }
    }
    if ($updated) {
      $status = array('status' => 'record-updated-success');
    }
    else {
      $status = array('status' => ts("This batch is configured to include only transactions using %1 payment method. If you want to include other transactions, please edit the batch first and modify the Payment Method.", array( 1 => $paymentInstrument)));
    }
    echo json_encode($status);
    CRM_Utils_System::civiExit();
  }

  static function getBatchSummary() {
    $batchID = CRM_Utils_Type::escape($_REQUEST['batchID'], 'String');
    $params = array('id' => $batchID);
    $batchInfo = CRM_Batch_BAO_Batch::retrieve($params, $value);
    $batchTotals = CRM_Batch_BAO_Batch::batchTotals(array($batchID));
    $batchSummary =
      array(
        'created_by' => CRM_Contact_BAO_Contact::displayName($batchInfo->created_id),
        'status' => CRM_Core_OptionGroup::getLabel('batch_status', $batchInfo->status_id),
        'description' => $batchInfo->description,
        'payment_instrument' => CRM_Core_OptionGroup::getLabel('payment_instrument', $batchInfo->payment_instrument_id),
        'item_count' => $batchInfo->item_count,
        'assigned_item_count' => $batchTotals[$batchID]['item_count'],
        'total' => CRM_Utils_Money::format($batchInfo->total),
        'assigned_total' => CRM_Utils_Money::format($batchTotals[$batchID]['total']),
        'opened_date' => CRM_Utils_Date::customFormat($batchInfo->created_date),
      );

    echo json_encode($batchSummary);
    CRM_Utils_System::civiExit();
  }
}
