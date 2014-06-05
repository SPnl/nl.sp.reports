<?php

class CRM_Reports_Form_Report_OverzichtslijstTelling extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Membership');
  protected $_customGroupGroupBy = FALSE; 
  
  protected $lokaalLidRelTypeIds = array();
  protected $regioRelTypeIds = array();
  
  protected $_add2groupSupported = FALSE;
  
  protected $_exposeContactID = FALSE;
  
  function __construct() {
    $this->lokaalLidRelTypeIds = $this->getLokaalLidRelationshipTypes();
    $this->regioRelTypeIds = $this->getRegioRelationshipTypes();
    
    $this->_columns = array(
     'civicrm_regio' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'regio_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
            'name' => 'id'
          ),
          'regio_display_name' => array(
            'title' => ts('Regio'),
            'default' => TRUE,
            'name' => 'display_name'
          ),          
        ),
        'filters' => array(
        ),
        'order_bys' => array(
          'regio_display_name' => array(
            'title' => ts('Regio'),
            'default' => TRUE,
            'section' => true,
            'name' => 'display_name'
          ),
        ),
        'group_bys' => array(
          'regio_id' => array(
            'title' => ts('Regio'),
            'default' => TRUE,
            'name' => 'id'
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_afdeling' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'afdeling_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
            'name' => 'id'
          ),
          'afdeling_display_name' => array(
            'title' => ts('Afdeling'),
            'default' => TRUE,
            'name' => 'display_name'
          ),          
        ),
        'filters' => array(
        ),
        'order_bys' => array(
          'afdeling_display_name' => array(
            'title' => ts('Afdeling'),
            'default' => TRUE,
            'section' => true,
            'name' => 'display_name'
          ),
        ),
        'group_bys' => array(
          'afdeling_id' => array(
            'title' => ts('Afdeling'),
            'default' => TRUE,
            'name' => 'id'
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
          'membership_type_id' => array(
            'title' => 'Membership Type',
            'default' => TRUE,
          ),
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
        'order_bys' => array(
          'membership_type_id' => array(
            'title' => ts('Membership Type'),
            'default' => true,
            'section' => true,
          )
        ),
        'group_bys' => array(
          'membership_type_id' => array(
            'title' => ts('Membership Type'),
            'default' => true,
          )
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' => array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => array(
          'name' => array(
            'title' => ts('Status'),
            'default' => TRUE,
          ),
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
        'order_bys' => array (
          'name' => array (
            'title' => ts('Status'),
            'default' => true,
            'section' => true,
          ),          
        ), 
        'group_bys' => array (
          'sid' => array (
            'title' => ts('Status'),
            'default' => true,
            'name' => 'id',
          ),          
        ),        
        'grouping' => 'member-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'filters' => array(
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'options' => array('' => ' -- '.ts('none').' --') + CRM_Core_PseudoConstant::stateProvinceForCountry(1152),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'birth_date' => array(
            'name' => 'birth_date',
            'title' => ts('Year of birth'),
            'dbAlias' => 'YEAR(contact_civireport.birth_date)',
          ),
          'membercount' => array(
            'title' => ts('Count'),
            'dbAlias' => 'contact_civireport.id',
            'statistics' => array(
               'count_distinct' => ts('Total'),
              ),
            'required' => TRUE,
            'default' => TRUE,
          ), 
        ),
        'filters' => array(
        ),
        'group_bys' => array(
          'birth_date' => array(
            'title' => ts('Year of birth'),
            'dbAlias' => 'YEAR(contact_civireport.birth_date)'
          )
        ),
        'grouping' => 'contact-fields',
      ),
    );
    $this->_groupFilter = TRUE;
    $this->_tagFilter = FALSE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Overzichtslijst opsomming (telling)'));
    parent::preProcess();
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id 
               LEFT JOIN civicrm_relationship `afdelingsrelatie` 
                          ON `afdelingsrelatie`.`relationship_type_id` IN (".implode(",", $this->lokaalLidRelTypeIds).")
                          AND `afdelingsrelatie`.`contact_id_a`  = `{$this->_aliases['civicrm_contact']}`.`id`  
               LEFT JOIN civicrm_contact {$this->_aliases['civicrm_afdeling']} 
                          ON {$this->_aliases['civicrm_afdeling']}.id = `afdelingsrelatie`.`contact_id_b`
               LEFT JOIN civicrm_relationship `regiorelatie` 
                          ON `regiorelatie`.`relationship_type_id` IN (".implode(",", $this->regioRelTypeIds).")
                          AND `regiorelatie`.`contact_id_a`  = `{$this->_aliases['civicrm_afdeling']}`.`id`  
               LEFT JOIN civicrm_contact {$this->_aliases['civicrm_regio']} 
                          ON {$this->_aliases['civicrm_regio']}.id = `regiorelatie`.`contact_id_b`
               ";
                             

    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function storeWhereHavingClauseArray(){
    parent::storeWhereHavingClauseArray();
    
    $start_date = 'NOW()';
    $end_date = 'NOW()';
    
    //check if join date is parsed as filter, if so set the relationship dates to this value
    $fieldName = 'join_date';
    $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
    $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
    $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
    $fromTime = CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params);
    $toTime   = CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params);
    list($fromDate, $toDate) = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);
    if ($fromDate) {
      $end_date = $fromDate;
    }
    if ($toDate) {
      $start_date = $toDate;
    }
    
    $this->_whereClauses[] = $this->addActiveRelationshipWhere($this->_aliases['civicrm_membership'], $start_date, $end_date);
    $this->_whereClauses[] = $this->addActiveRelationshipWhere('regiorelatie', $start_date, $end_date);
    $this->_whereClauses[] = $this->addActiveRelationshipWhere('afdelingsrelatie', $start_date, $end_date);
  }
  
  protected function addActiveRelationshipWhere($relationship_table, $start_date, $end_date) {
    $where = '((`%1$s`.`start_date` IS NULL OR `%1$s`.`start_date` <= %2$s) AND (`%1$s`.`end_date` IS NULL OR `%1$s`.`end_date` >= %3$s) )';
    return sprintf($where, $relationship_table, $start_date, $end_date);
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);
    
//echo $sql; exit();
    
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

      if (array_key_exists('civicrm_afdeling_afdeling_display_name', $row) &&
        $rows[$rowNum]['civicrm_afdeling_afdeling_display_name'] &&
        array_key_exists('civicrm_afdeling_afdeling_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_afdeling_afdeling_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_afdeling_afdeling_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_afdeling_afdeling_display_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }
      
      if (array_key_exists('civicrm_regio_regio_display_name', $row) &&
        $rows[$rowNum]['civicrm_regio_regio_display_name'] &&
        array_key_exists('civicrm_regio_regio_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_regio_regio_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_regio_regio_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_regio_regio_display_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }
  
  protected function getLokaalLidRelationshipTypes() {
    $return = array();
    
    if (class_exists('CRM_Geostelsel_RelationshipTypes')) {
      $rel = CRM_Geostelsel_RelationshipTypes::singleton();
      $return = $rel->getLokaalLidRelationshipTypeIds();
    }
    
    return $return;
  }
  
  protected function getRegioRelationshipTypes() {
    $return = array();
    
    if (class_exists('CRM_Geostelsel_RelationshipTypes')) {
      $rel = CRM_Geostelsel_RelationshipTypes::singleton();
      $return = $rel->getRegioRelationshipTypeIds();
    }
    
    return $return;
  }
}
