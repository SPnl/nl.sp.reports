<?php

class CRM_Reports_Form_Report_AddressLabel extends CRM_Report_Form {

  protected $_addressField = FALSE;
  
  protected $_csvSupported = FALSE;
  protected $_add2groupSupported = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE; 
  
  function __construct() {
    $this->fetchCustom();
    
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'required' => TRUE,
            'default' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => array(
            'required' => true,
          ),
          'postal_code' => array(
            'required' => true,
          ),
          'city' => array(
            'required' => true,
          ),
          'country_id' => array('title' => ts('Country'), 'required' => true),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
        ),
        'filters' => array(
          'join_date' => array(
            'operatorType' => CRM_Report_Form::OP_DATE,
            'pseudofield' => true,
          ),
          'tid' => array(
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' => array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => array(
        ),
        'filters' => array(
          'sid' => array(
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),      
        'grouping' => 'member-fields',
      ),
      'afdeling' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'afdeling',
        'fields' => array(
          'afdeling' => array(
            'required' => true,
            'title' => 'Afdeling',
            'default' => true,
            'name' => 'display_name',
          ),
          'afdeling_id' => array(
            'required' => true,
            'title' => 'Afdeling ID',
            'default' => true,
            'name' => 'id',
          )
        ),
        'grouping' => 'afdeling-fields',
      ),
      'bezorg_gebied' => array (
        'alias' => 'cbzg',
        'fields' => array(
          'deliver_area_name' => array(
            'required' => true,
            'title' => 'Bezorggebied',
            'name' => $this->_custom_fields->name['column_name'],
          ),
          'deliver_per_post' => array(
            'required' => true,
            'title' => 'Per post',
            'name' => $this->_custom_fields->per_post['column_name'],
          ),
        )
      ),
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
    parent::__construct();
  }
  
  protected function fetchCustom() {
    $this->_custom_fields 						= new stdClass();
    $this->_custom_fields->group 				= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Bezorggebieden"));
    $this->_custom_fields->name 				= civicrm_api3('CustomField', 'getsingle', array("name" => "Bezorggebied_naam", "custom_group_id" => $this->_custom_fields->group['id']));
    $this->_custom_fields->start_cijfer_range 	= civicrm_api3('CustomField', 'getsingle', array("name" => "start_cijfer_range", "custom_group_id" => $this->_custom_fields->group['id']));
    $this->_custom_fields->eind_cijfer_range 	= civicrm_api3('CustomField', 'getsingle', array("name" => "eind_cijfer_range", "custom_group_id" => $this->_custom_fields->group['id']));
    $this->_custom_fields->start_letter_range 	= civicrm_api3('CustomField', 'getsingle', array("name" => "start_letter_range", "custom_group_id" => $this->_custom_fields->group['id']));
    $this->_custom_fields->eind_letter_range 	= civicrm_api3('CustomField', 'getsingle', array("name" => "eind_letter_range", "custom_group_id" => $this->_custom_fields->group['id']));
    $this->_custom_fields->per_post 			= civicrm_api3('CustomField', 'getsingle', array("name" => "Per_Post", "custom_group_id" => $this->_custom_fields->group['id']));
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  /*function select() {
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
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }*/

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_membership {$this->_aliases['civicrm_membership']}\n
         INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contact']}.id\n
         LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id 
            AND {$this->_aliases['civicrm_address']}.is_primary = 1\n
         LEFT JOIN `".$this->_custom_fields->group['table_name']." {$this->_aliases['bezorg_gebied']} ON \n
         ( \n
            (SUBSTR(REPLACE($this->_aliases['civicrm_address']}.`postal_code`, ' ', ''), 1, 4) BETWEEN {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->start_cijfer_range['column_name']."` AND {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->eind_cijfer_range['column_name']."`)\n
            AND
            (SUBSTR(REPLACE($this->_aliases['civicrm_address']}.`postal_code`, ' ', ''), -2) BETWEEN {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->start_letter_range['column_name']."` AND {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->eind_letter_range['column_name']."`)\n
          )\n
          LEFT JOIN `civicrm_contact` {$this->_aliases['afdeling']} ON {$this->_aliases['bezorg_gebied']}.entity_id = {$this->_aliases['afdeling']}.id\n
            ";
  }

  /*function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }*/

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['afdeling']}.sort_name, {$this->_aliases['civicrm_address']}.city, {$this->_aliases['civicrm_address']}.postal_code, {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->start_cijfer_range['column_name']."`, {$this->_aliases['bezorg_gebied']}.`".$this->_custom_fields->start_letter_range['column_name']."`, {$this->_aliases['civicrm_contact']}.`sort_name`";
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

      if (array_key_exists('civicrm_contact_display_name', $row) &&
        $rows[$rowNum]['civicrm_contact_display_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
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
      $i = 1;
      foreach ($rows as $row => $value) {
        foreach ($value as $k => $v) {
          $val .= "$v\n";
        }

        $pdf->AddPdfLabel($val);
        $i++;
        
        $newPage = false;
        if ($i > 3) {
          $newPage = true;
        }
        
        if ($newPage) {
          for(;$i <= $labelsPerPage; $i++) {
            $pdf->addPdfLabel('empty label: '.$i.' / '.$labelsPerPage);
          }
          $pdf->AddPdfLabel('new page label');
          $i = 2;
        }
        
        if ($i >= $labelsPerPage) {
          $i = 1;
        }
        
        $val = '';
      }
      $pdf->Output($fileName, 'D');
      
      CRM_Utils_System::civiExit();
    } else {
      parent::endPostProcess($rows);
    }
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
