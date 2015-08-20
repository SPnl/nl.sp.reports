<?php

class CRM_Reports_Form_Report_MembershipDetail extends CRM_Report_Form_Member_Detail {

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_membership']['order_bys']['membership_start_date'] = array(
      'title' => ts('Start date'),
    );
    $this->_columns['civicrm_membership']['order_bys']['membership_end_date'] = array(
      'title' => ts('End date'),
    );
    $this->_columns['civicrm_membership']['order_bys']['join_date'] = array(
      'title' => ts('Join date'),
      'default' => '1',
      'default_weight' => '-1',
      'default_order' => 'ASC'
    );
  }

  function orderBy() {
    $this->_orderBy  = "";
    $this->_sections = array();
    $this->storeOrderByArray();
    if(!empty($this->_orderByArray) && !$this->_rollup == 'WITH ROLLUP'){
      $this->_orderBy = "ORDER BY " . implode(', ', $this->_orderByArray);
    }
    $this->assign('sections', $this->_sections);
  }

}