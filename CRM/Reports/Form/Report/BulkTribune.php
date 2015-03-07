<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
class CRM_Reports_Form_Report_BulkTribune extends CRM_Report_Form {

  protected $_addressField = FALSE;
  protected $_summary = NULL;
  protected $_customGroupExtends = FALSE;
  protected $_customGroupGroupBy = FALSE;

  function __construct() {

    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' =>
          array('title' => ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' =>
          array('title' => ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' =>
          array(
            'title' => ts('Contact SubType'),
          ),
        ),
        'filters' =>
         array(
		 'sort_name' =>
          array('title' => ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' =>
          array('no_display' => TRUE),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' =>
        array(
          'membership_type_id' => array(
            'title' => 'Membership Type',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'membership_start_date' => array('title' => ts('Start Date'),
            'default' => TRUE,
          ),
          'membership_end_date' => array('title' => ts('End Date'),
            'default' => TRUE,
          ),
          'join_date' => array('title' => ts('Join Date'),
            'default' => TRUE,
          ),
          'source' => array('title' => 'Source'),
		  'sp_afdeling' => array('title' => 'SP Afdeling', 'dbAlias' => '""', 'required' => TRUE, 'default' => TRUE),
        ),
        'filters' => array(
          'tid' =>
          array(
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' =>
      array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' =>
        array('name' => array('title' => ts('Status'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'sid' =>
          array(
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_address' => NULL,
          'city' => array('required' => TRUE),
          'postal_code' => array('required' => TRUE),
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
          ),
		  'postcode_gebied' => array('title' => 'Gebied', 'dbAlias' => '""', 'required' => TRUE, 'default' => TRUE),
		  'postcode_range' => array('title' => 'Bereik', 'dbAlias' => '""', 'required' => TRUE, 'default' => TRUE),
		  'postcode_methode' => array('title' => 'Methode', 'dbAlias' => '""', 'required' => TRUE, 'default' => TRUE),
        ),
        'grouping' => 'contact-fields',
      )
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
	
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            if (array_key_exists('title', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $bezorggebiedcontact = CRM_Bezorggebieden_Config_BezorggebiedContact::singleton();
    $bezorggebied = CRM_Bezorggebieden_Config_Bezorggebied::singleton();
    $this->_select = "SELECT " . implode(', ', $select) . ", `civicrm_contact_afdeling`.`id` AS `civicrm_contact_afdeling_id`, `civicrm_contact_afdeling`.`display_name` AS `civicrm_contact_afdeling_display_name` ";
    $this->_select .= ", `{$bezorggebiedcontact->getCustomGroupBezorggebiedContact('table_name')}`.`".$bezorggebiedcontact->getCustomFieldBezorggebied('column_name')."` AS `bezorggebied_contact_bezorggebied`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getNaamField('column_name')."` AS `bezorggebied_name`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getStartCijferRangeField('column_name')."` AS `bezorggebied_start_cijfer_range`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getEindCijferRangeField('column_name')."` AS `bezorggebied_eind_cijfer_range`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getStartLetterRangeField('column_name')."` AS `bezorggebied_start_letter_range`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getEindLetterRangeField('column_name')."` AS `bezorggebied_eind_letter_range`";
    $this->_select .= ", `".$bezorggebied->getCustomGroup('table_name')."`.`".$bezorggebied->getBezorgingPerField('column_name')."` AS `bezorggebied_bezorging_per`";
  }

  function from() {

    $bezorggebiedcontact = CRM_Bezorggebieden_Config_BezorggebiedContact::singleton();
    $bezorggebied = CRM_Bezorggebieden_Config_Bezorggebied::singleton();

    $this->_from = NULL;
    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";


    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
    $this->_from .= "
      LEFT JOIN `".$bezorggebiedcontact->getCustomGroupBezorggebiedContact('table_name')."` ON `{$bezorggebiedcontact->getCustomGroupBezorggebiedContact('table_name')}`.`entity_id` = {$this->_aliases['civicrm_contact']}.id
      LEFT JOIN `".$bezorggebied->getCustomGroup('table_name')."` ON `{$bezorggebied->getCustomGroup('table_name')}`.`id` = `{$bezorggebiedcontact->getCustomGroupBezorggebiedContact('table_name')}`.`".$bezorggebiedcontact->getCustomFieldBezorggebied('column_name')."`
      LEFT JOIN `civicrm_contact` `civicrm_contact_afdeling` ON `civicrm_contact_afdeling`.`id` = `{$bezorggebied->getCustomGroup('table_name')}`.`entity_id`
    ";
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";

    if ($this->_contribField) {
      $this->_orderBy .= ", {$this->_aliases['civicrm_contribution']}.receive_date DESC";
    }
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    $contributionTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }
		
	  if (array_key_exists('civicrm_membership_sp_afdeling', $row) && array_key_exists('civicrm_address_postcode_gebied', $row) && array_key_exists('civicrm_address_postcode_range', $row) && array_key_exists('civicrm_address_postcode_methode', $row)) {
      $entryFound = true;
      if (empty($row['bezorggebied_contact_bezorggebied']) || ($row[''] == 'Post')) {
        $rows[$rowNum]['civicrm_membership_sp_afdeling'] = "Drukker";
        $rows[$rowNum]['civicrm_address_postcode_gebied'] = "-";
        $rows[$rowNum]['civicrm_address_postcode_range'] = "-";
        $rows[$rowNum]['civicrm_address_postcode_methode'] = "Per Post";
      } else {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_afdeling_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_membership_sp_afdeling_link'] = $url;
        $rows[$rowNum]['civicrm_membership_sp_afdeling'] = $row['civicrm_contact_afdeling_display_name'];
        $rows[$rowNum]['civicrm_address_postcode_gebied'] = $row['bezorggebied_name'];
        $rows[$rowNum]['civicrm_address_postcode_range'] = $row['bezorggebied_start_cijfer_range']." ". $row['bezorggebied_start_letter_range']." - ". $row['bezorggebied_eind_cijfer_range']." ". $row['bezorggebied_eind_letter_range'];
        $rows[$rowNum]['civicrm_address_postcode_methode'] = $row['bezorggebied_bezorging_per'];
      }
	  }
	  

      if (!$entryFound) {
        break;
      }
    }
  }
}

