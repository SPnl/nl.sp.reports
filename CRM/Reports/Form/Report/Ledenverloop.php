<?php
/**
 * Created by PhpStorm.
 * User: jaap
 * Date: 11/6/15
 * Time: 5:00 PM
 */

class CRM_Reports_Form_Report_Ledenverloop extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE;

  protected $_add2groupSupported = FALSE;

  protected $_exposeContactID = FALSE;

  protected $start_period;

  protected $end_period;

  protected $months;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
            'name' => 'id'
          ),
          'display_name' => array(
            'title' => ts('Afdeling'),
            'default' => TRUE,
            'name' => 'display_name',
            'required' => true,
          ),
        ),
        'filters' => array(
          'display_name' =>  array(
              'title' => ts('Afdelingsnaam'),
            ),
            'period' => array(
              'title' => ts('Perioe'),
              'type' => CRM_Utils_Type::T_DATE,
              'operatorType' => CRM_Report_Form::OP_DATE,
              'pseudofield' => true,
            )
        ),
        'grouping' => 'contact-fields',
      ),
    );
    parent::__construct();
  }

  function from() {
    $this->_from = " FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom} ";
  }

  function storeWhereHavingClauseArray() {
    $this->start_period = new DateTime();
    $this->start_period->modify('-1 year');
    $this->end_period = new DateTime();

    //check if join date is parsed as filter, if so set the relationship dates to this value
    $fieldName = 'period';
    $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
    $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
    $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
    $fromTime = CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params);
    $toTime   = CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params);
    list($fromDate, $toDate) = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);
    if ($fromDate && $toDate) {
      $this->start_period = new DateTime($fromDate);
      $this->end_period = new DateTime($toDate);
    } elseif ($fromDate) {
      $this->start_period = new DateTime($fromDate);
      $this->end_period = new DateTime($fromDate);
      $this->end_period->modify('+1 year');
    } elseif ($toDate) {
      $this->start_period = new DateTime($toDate);
      $this->start_period->modify('-1 year');
      $this->end_period = new DateTime($toDate);
    }

    parent::storeWhereHavingClauseArray();

    $this->_whereClauses[] = "{$this->_aliases['civicrm_contact']}.contact_sub_type like '%SP_Afdeling%'";
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
    foreach($rows as $rowIndex => $row) {
      $contact_id = $row['civicrm_contact_id'];

      foreach($this->months as $tstamp => $label) {
        $date = new DateTime();
        $date->setTimestamp($tstamp);
        $ledenAantal = $this->getAantalLeden($contact_id, $date);
        if (empty($ledenAantal)) {
          $ledenAantal = '0';
        }
        $rows[$rowIndex][$tstamp] = $ledenAantal;
      }
    }
  }

  /**
   * Returns the total members of a department in a certain period
   *
   * @param $afdeling_id
   * @param $qStartDate
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private static function getAantalLeden($afdeling_id, $date) {
    $cg_ledentelling = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Ledentelling'));
    $cf_aantal_leden = civicrm_api3('CustomField', 'getvalue', array('name' => 'Aantal_leden', 'return' => 'column_name', 'custom_grooup_id' => $cg_ledentelling['id']));
    $leden_telling_activity = CRM_Core_OptionGroup::getValue('activity_type', 'leden_telling', 'name');
    $sql = "SELECT SUM(`".$cf_aantal_leden."`) AS aantal_leden
            FROM `civicrm_activity`
            INNER JOIN `".$cg_ledentelling['table_name']."` ON `".$cg_ledentelling['table_name']."`.entity_id = civicrm_activity.id
            INNER JOIN civicrm_activity_contact ON civicrm_activity.id = civicrm_activity_contact.activity_id
            where civicrm_activity.activity_type_id = %1
            and civicrm_activity.is_deleted = 0
            and civicrm_activity.is_current_revision = 1
            and civicrm_activity_contact.record_type_id = 3
            AND civicrm_activity_contact.contact_id = %2
            AND MONTH(civicrm_activity.activity_date_time) = MONTH(%3)
            AND YEAR(civicrm_activity.activity_date_time) = YEAR(%3)";
    $params[1] = array($leden_telling_activity, 'Integer');
    $params[2] = array($afdeling_id, 'Integer');
    $params[3] = array($date->format('Y-m-d'), 'String');

    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

  function modifyColumnHeaders() {
    $months = array(
      1 => 'Jan',
      2 => 'Feb',
      3 => 'Mrt',
      4 => 'Apr',
      5 => 'Mei',
      6 => 'Jun',
      7 => 'Jul',
      8 => 'Aug',
      9 => 'Sep',
      10 => 'Okt',
      11 => 'Nov',
      12 => 'Dec',
    );


    // use this method to modify $this->_columnHeaders
    $date = clone $this->start_period;
    $date->modify('first day of this month');
    $end_date = clone $this->end_period;
    $end_date->modify('last day of this month');
    while ($date <= $end_date) {
      $date->modify('first day of this month');
      $this->months[$date->getTimestamp()] = $months[$date->format('n')] .' '.$date->format('y');
      $this->_columnHeaders[$date->getTimestamp()] = array('title' => $months[$date->format('n')] .' '.$date->format('y'));
      $date->modify('+1 month');
    }
  }

}