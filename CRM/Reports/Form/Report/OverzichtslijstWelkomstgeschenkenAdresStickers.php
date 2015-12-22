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
class CRM_Reports_Form_Report_OverzichtslijstWelkomstgeschenkenAdresStickers extends CRM_Report_Form
{

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_contribField = FALSE;

    protected $_add2groupSupported = FALSE;
    protected $_csvSupported = FALSE;
  protected $_summary = TRUE;
  protected $_sections = TRUE;

  protected $_customGroupExtends = array('Membership');
  protected $_customGroupGroupBy = FALSE;
  protected $_autoIncludeIndexedFieldsAsOrderBys = TRUE;

  function __construct()
  {
    $this->_columns = array(
      'civicrm_contact' =>
        array(
          'dao' => 'CRM_Contact_DAO_Contact',
          'grouping' => 'contact-fields',
        ),
      'civicrm_membership' =>
        array(
          'dao' => 'CRM_Member_DAO_Membership',
          'fields' =>
            array(
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
        'contact_b' => array(
            'dao' => 'CRM_Contact_DAO_Contact',
            'fields' =>  array(
                'display_name' =>  array(
                    'title' => ts('Contact B van afdeling'),
                    'required' => TRUE,
                    'default' => TRUE,
                    'no_repeat' => TRUE,
                ),
                'id' => array(
                    'no_display' => TRUE,
                    'required' => TRUE,
                ),
            ),
        ),
      'civicrm_address' =>
        array(
          'dao' => 'CRM_Core_DAO_Address',
          'fields' =>
            array(
              'street_address' => array(
                  'required' => true,
                ),
                'postal_code' => array(
                    'required' => true,
                ),
                'city' => array(
                  'required' => true,
                ),
            ),
          'grouping' => 'contact-fields',
        ),
        'afdeling' => array(
          'dao' => 'CRM_Contact_DAO_Contact',
          'alias' => 'afdeling',
          'fields' =>  array(
            'display_name' =>  array(
              'title' => ts('Afdeling'),
              'required' => TRUE,
              'default' => TRUE,
              'no_repeat' => TRUE,
            ),
            'id' => array(
              'no_display' => TRUE,
              'required' => TRUE,
            ),
          ),
        ),
        'civicrm_relationship_type' => array(
          'dao' => 'CRM_Contact_DAO_RelationshipType',
          'fields' => array(
            'label_a_b' => array(
              'title' => ts('Relationship A-B '),
              'default' => TRUE,
            ),
            'label_b_a' => array(
              'title' => ts('Relationship B-A '),
              'default' => FALSE,
            ),
          ),
          'grouping' => 'relation-fields',
        ),
        'civicrm_relationship' => array(
          'dao' => 'CRM_Contact_DAO_Relationship',
          'filters' =>  array(
            'relationship_type_id' => array(
              'title' => ts('Relationship'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, 'Individual', FALSE, 'label', FALSE),
              'type' => CRM_Utils_Type::T_INT,
            ),
          ),
          'grouping' => 'relation-fields',
        ),
    );

    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;

    parent::__construct();
  }

  function preProcess()
  {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }


  function from()
  {
    $geoConig = CRM_Geostelsel_Config::singleton();
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";


    $this->_from .= "
        LEFT JOIN `{$geoConig->getGeostelselCustomGroup('table_name')}` `geostelsel` ON `geostelsel`.`entity_id` = `{$this->_aliases['civicrm_contact']}`.id";
    $this->_from .= "
        LEFT JOIN `civicrm_contact` `{$this->_aliases['afdeling']}` ON `{$this->_aliases['afdeling']}`.id = `geostelsel`.`{$geoConig->getAfdelingsField('column_name')}`";
    $this->_from .= "
        LEFT JOIN `civicrm_relationship` {$this->_aliases['civicrm_relationship']} ON {$this->_aliases['civicrm_relationship']}.contact_id_b = `{$this->_aliases['afdeling']}`.id AND {$this->_aliases['civicrm_relationship']}.is_active = '1' AND ({$this->_aliases['civicrm_relationship']}.start_date IS NULL OR {$this->_aliases['civicrm_relationship']}.start_date <= NOW()) AND ({$this->_aliases['civicrm_relationship']}.end_date IS NULL OR {$this->_aliases['civicrm_relationship']}.end_date >= NOW())";
    $this->_from .= "
        LEFT JOIN civicrm_relationship_type {$this->_aliases['civicrm_relationship_type']} ON {$this->_aliases['civicrm_relationship']}.relationship_type_id = {$this->_aliases['civicrm_relationship_type']}.id";
    $this->_from .= "
        LEFT JOIN `civicrm_contact` {$this->_aliases['contact_b']} ON {$this->_aliases['contact_b']}.id = {$this->_aliases['civicrm_relationship']}.contact_id_a";

    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['contact_b']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }


  function postProcess()
  {

    $this->beginPostProcess();

    $this->relationType = NULL;
    $relTypes = array();
    $originalRelationTypeIdValue = array();
    if (CRM_Utils_Array::value('relationship_type_id_value', $this->_params)) {
        $originalRelationTypeIdValue = CRM_Utils_Array::value('relationship_type_id_value', $this->_params);
        foreach(CRM_Utils_Array::value('relationship_type_id_value', $this->_params) as $relType) {
            $relTypeId = explode('_', $relType);
            $relTypes[] = intval($relTypeId[0]);
        }
    }
    $this->_params['relationship_type_id_value'] = $relTypes;

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);

    if (!empty($originalRelationTypeIdValue)) {
      // store its old value, CRM-5837
      $this->_params['relationship_type _id_value'] = $originalRelationTypeIdValue;
    }

    $this->endPostProcess($rows);
  }

    function groupBy() {
        $this->_groupBy = "GROUP BY `{$this->_aliases['afdeling']}`.id, {$this->_aliases['contact_b']}.id";
    }

  function alterDisplay(&$rows)
  {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
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

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      if (array_key_exists('civicrm_membership_campaign_id', $row)) {
        if ($value = $row['civicrm_membership_campaign_id']) {
          $rows[$rowNum]['civicrm_membership_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }

    function endPostProcess(&$rows = NULL) {
        if ($this->_outputMode == 'pdf') {
            $format_name = $this->_params['label_format'];
            $format = CRM_Core_BAO_LabelFormat::getByName($format_name);
            if (!$format) {
                throw new Exception('Label format '.$format_name.' not found');
            }
            $labelsPerPage = $format['NX'] * $format['NY'];
            $fileName = 'labels.pdf';
            //echo 'label functionality'; exit();

            $pdf = new CRM_Utils_PDF_Label($format_name, 'mm');
            $pdf->Open();
            $pdf->AddPage();

            //build contact string that needs to be printed
            $val = NULL;
            $i = $labelsPerPage+1;
            foreach ($rows as $row) {
                $label = $this->formatRowAsLabel($row);
                $pdf->AddPdfLabel($label);

                $i++;
                if ($i > $labelsPerPage) {
                    $i = 1;
                }
            }
            $pdf->Output($fileName, 'D');

            CRM_Utils_System::civiExit();
        } else {
            parent::endPostProcess($rows);
        }
    }

    function formatRowAsLabel($row) {
        $val = "";
        $val .= $row['afdeling_display_name'] . "\r\n";
        $val .= $row['contact_b_display_name']. "\r\n";
        $val .= $row['civicrm_address_street_address']."\r\n";
        //isue #333: als een plaatsnaam te lang is dan de plaatsnaam op volgende regel
        //dit is slechts bij benadering.
        if (strlen($row['civicrm_address_city']) > 15) {
            $val .= $row['civicrm_address_postal_code'] . "\r\n" . $row['civicrm_address_city'] . "\r\n";
        } else {
            $val .= $row['civicrm_address_postal_code'] . ' ' . $row['civicrm_address_city'] . "\r\n";
        }
        return $val;
    }

    function buildInstanceAndButtons() {
        CRM_Report_Form_Instance::buildForm($this);

        $label = $this->_id ? ts('Update Report') : ts('Create Report');

        $this->addElement('submit', $this->_instanceButtonName, $label);

        if ($this->_id) {
            $this->addElement('submit', $this->_createNewButtonName, ts('Save a Copy') . '...');
        }
        if ($this->_instanceForm) {
            $this->assign('instanceForm', TRUE);
        }

        $label_formats = CRM_Core_BAO_LabelFormat::getList(true, 'label_format');
        $this->addElement('select', 'label_format', ts('Label format'), $label_formats);

        $label = ts('Print address labels');
        $this->addElement('submit', $this->_pdfButtonName, $label);

        $this->addChartOptions();
        $this->addButtons(array(
                array(
                    'type' => 'submit',
                    'name' => ts('Preview Report'),
                    'isDefault' => TRUE,
                ),
            )
        );
    }
  
}

