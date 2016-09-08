<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Reports_Form_Report_MissingContribution extends CRM_Report_Form {

  protected function getYears() {
    $dao = CRM_Core_DAO::executeQuery("SELECT DISTINCT YEAR(join_date) as year FROM civicrm_membership ORDER BY year");
    $years = array();
    $last_year = false;
    while($dao->fetch()) {
      if (!$last_year) {
        $last_year = $dao->year;
        $years[$last_year] = $last_year;
      }
      while ($last_year && $last_year <= ($dao->year - 1)) {
        $last_year ++;
        $years[$last_year] = $last_year;
      }
    }
    return $years;
  }

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
          'years' => array(
            'title' => ts('Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Utils_TYpe::T_INT,
            'options' => $this->getYears(),
            'pseudofield' => true,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' => array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
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
        'filters' => array(
          'type_id' => array(
            'name' => 'id',
            'title' => ts('Membership type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
      ),
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
    $this->_customGroupExtends = array();
    $this->_exposeContactID = FALSE;
    parent::__construct();
  }

  function select() {
    parent::select();

    $this->_select = "SELECT DISTINCT " . implode(', ', $this->_selectClauses) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
          FROM  civicrm_contact {$this->_aliases['civicrm_contact']}
          INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']} ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contact']}.id 
          {$this->_aclFrom}
          LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']} ON {$this->_aliases['civicrm_membership_status']}.id = {$this->_aliases['civicrm_membership']}.status_id
          LEFT  JOIN civicrm_membership_type {$this->_aliases['civicrm_membership_type']} ON {$this->_aliases['civicrm_membership_type']}.id = {$this->_aliases['civicrm_membership']}.membership_type_id
    ";
  }

  function where() {
    parent::where();
    $year = $this->_submitValues['years_value'];
    $nextYear = $year + 1;

    $this->_where .= "AND (
(not {$this->_aliases['civicrm_contact']}.id IN (
	select contr1.contact_id from civicrm_membership_payment mp1
    inner join civicrm_contribution contr1 on mp1.contribution_id = contr1.id
    where contr1.receive_date >= '{$year}-01-01' and contr1.receive_date < '{$year}-04-01'
    and mp1.membership_id = {$this->_aliases['civicrm_membership']}.id
) AND {$this->_aliases['civicrm_membership']}.start_date < '{$year}-01-01')
or (not {$this->_aliases['civicrm_contact']}.id IN (
	select contr2.contact_id from civicrm_membership_payment mp2
    inner join civicrm_contribution contr2 on mp2.contribution_id = contr2.id
    where contr2.receive_date >= '{$year}-04-01' and contr2.receive_date < '{$year}-07-01'
    and mp2.membership_id = {$this->_aliases['civicrm_membership']}.id
) AND {$this->_aliases['civicrm_membership']}.start_date < '{$year}-04-01')
or (not {$this->_aliases['civicrm_contact']}.id IN (
	select contr3.contact_id from civicrm_membership_payment mp3
    inner join civicrm_contribution contr3 on mp3.contribution_id = contr3.id
    where contr3.receive_date >= '{$year}-07-01' and contr3.receive_date < '{$year}-10-01'
    and mp3.membership_id = {$this->_aliases['civicrm_membership']}.id
) AND {$this->_aliases['civicrm_membership']}.start_date < '{$year}-07-01')
or (not {$this->_aliases['civicrm_contact']}.id IN (
	select contr4.contact_id from civicrm_membership_payment mp4
    inner join civicrm_contribution contr4 on mp4.contribution_id = contr4.id
    where contr4.receive_date >= '{$year}-10-01' and contr4.receive_date < '{$nextYear}-01-01'
    and mp4.membership_id = {$this->_aliases['civicrm_membership']}.id
) AND {$this->_aliases['civicrm_membership']}.start_date < '{$year}-10-01'))";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name";
  }

  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
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