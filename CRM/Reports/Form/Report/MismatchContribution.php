<?php

class CRM_Reports_Form_Report_MismatchContribution extends CRM_Report_Form {

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'title' => ts('id'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' => array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => array(
          'name' => array(
            'title' => ts('Membership Status'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'sid' => array(
            'name' => 'id',
            'title' => ts('Membership Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_type' => array(
        'dao' => 'CRM_Member_DAO_MembershipType',
        'alias' => 'mem_type',
        'fields' => array(
          'name' => array(
            'title' => ts('Membership type'),
            'default' => TRUE,
          ),
          'financial_type_id' => array(
            'title' => ts('Membership financial type'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contribution_financial_type_id' => array(
            'name' => 'financial_type_id',
            'title' => ts('Contribution Financial Type'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
          ),
          'receive_date' => array(
            'title' => ts('Contribution receive date'),
            'default' => TRUE
          ),
          'total_amount' => array(
            'title' => ts('Amount'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'receive_date' =>  array(
              'title' => ts('Contribution Receive date'),
              'operatorType' => CRM_Report_Form::OP_DATE
          ),
        ),
        'grouping' => 'contribution-fields',
      ),
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
    $this->_customGroupExtends = array();
    $this->_exposeContactID = false;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Mismatch between financial type of contribution and membership'));
    parent::preProcess();
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
          FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
          INNER JOIN civicrm_membership_payment mp ON {$this->_aliases['civicrm_contribution']}.id = mp.contribution_id
          INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']} ON {$this->_aliases['civicrm_membership']}.id = mp.membership_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
          INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id {$this->_aclFrom}
          LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']} ON {$this->_aliases['civicrm_membership_status']}.id = {$this->_aliases['civicrm_membership']}.status_id
          LEFT  JOIN civicrm_membership_type {$this->_aliases['civicrm_membership_type']} ON {$this->_aliases['civicrm_membership_type']}.id = {$this->_aliases['civicrm_membership']}.membership_type_id
    ";
  }

  function where() {
    parent::where();

    $this->_where .= " AND {$this->_aliases['civicrm_membership_type']}.financial_type_id != {$this->_aliases['civicrm_contribution']}.financial_type_id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name";
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
    $contributionTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    foreach ($rows as $rowNum => $row) {
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_financial_type_id'] = $contributionTypes[$value];
      }
      if ($value = CRM_Utils_Array::value('civicrm_membership_type_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_membership_type_financial_type_id'] = $contributionTypes[$value];
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
      }

      if (array_key_exists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_display_name', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
      }
    }
  }
}
