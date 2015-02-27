<?php

class CRM_Reports_Form_Report_Kerncijfers extends CRM_Report_Form {

	protected $_summary = NULL;
	protected $_noFields = TRUE;

	private $_membershipTypes;
	private $_membershipStatuses;

	public function __construct() {
		$this->_columns = array();
		$this->_groupFilter = false;
		$this->_tagFilter   = false;
		parent::__construct();
	}

	public function preProcess() {
		$this->assign('reportTitle', ts('Kerncijfers SP'));
		parent::preProcess();
	}

	public function postProcess() {

		$this->beginPostProcess();

		$this->_membershipTypes    = CRM_Member_PseudoConstant::membershipType();
		$this->_membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();

		$lastWeek = new \DateTime('monday last week');
		$thisWeek = new \DateTime('monday this week');
		$thisYear = new \DateTime('01/01');
		$now      = new \DateTime;

		$this->_columnHeaders = array(
			'name'       => array('title' => 'Naam'),
			'count_lastweek' => array('title' => 'Wk&nbsp;' . (int) $lastWeek->format('W')),
			'count_thisweek' => array('title' => 'Wk&nbsp;' . (int) $thisWeek->format('W')),
			'count_year' => array('title' => $thisWeek->format('Y')),
		);

		$rows = array(
			array(
				'name'       => 'Ledencijfer SP',
				'count_year' => $this->_getCount('lid_sp'),
			),
			array(
				'name'       => 'Ledencijfer ROOD',
				'count_year' => $this->_getCount('lid_rood'),
			),
			array(
				'name'       => 'Ingeschreven SP-leden',
				'count_lastweek' => $this->_getCount('lid_sp_new', $lastWeek, $thisWeek),
				'count_thisweek' => $this->_getCount('lid_sp_new', $thisWeek, $now),
				'count_year' => $this->_getCount('lid_sp_new', $thisYear, $now),
			),
			array(
				'name'       => 'Uitgeschreven SP-leden',
				'count_lastweek' => $this->_getCount('lid_sp_end', $lastWeek, $thisWeek),
				'count_thisweek' => $this->_getCount('lid_sp_end', $thisWeek, $now),
				'count_year' => $this->_getCount('lid_sp_end', $thisYear, $now),
			),
			array(
				'name'       => 'Overleden SP-leden',
				'count_lastweek' => $this->_getCount('lid_sp_deceased', $lastWeek, $thisWeek),
				'count_thisweek' => $this->_getCount('lid_sp_deceased', $thisWeek, $now),
				'count_year' => $this->_getCount('lid_sp_deceased', $thisYear, $now),
			),
			array(
				'name'       => 'Ingeschreven ROOD-leden',
				'count_lastweek' => $this->_getCount('lid_rood_new', $lastWeek, $thisWeek),
				'count_thisweek' => $this->_getCount('lid_rood_new', $thisWeek, $now),
				'count_year' => $this->_getCount('lid_rood_new', $thisYear, $now),
			),
			array(
				'name'       => 'Uitgeschreven ROOD-leden',
				'count_lastweek' => $this->_getCount('lid_rood_end', $lastWeek, $thisWeek),
				'count_thisweek' => $this->_getCount('lid_rood_end', $thisWeek, $now),
				'count_year' => $this->_getCount('lid_rood_end', $thisYear, $now),
			),
		);

		$this->formatDisplay($rows);
		$this->doTemplateAssignment($rows);
		$this->endPostProcess($rows);
	}

	private function _getCount($type, $from = null, $to = null) {

		switch ($type) {

			case 'lid_sp':
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid SP') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('New') . ',' . $this->_membershipStatus('Current') . ')';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_rood':
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid ROOD') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('New') . ',' . $this->_membershipStatus('Current') . ')';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_sp_new':
				if (!$from || !$to)
					return false;
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid SP') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('New') . ',' . $this->_membershipStatus('Current') . ') AND join_date >= "' . $from->format('Y-m-d') . '" AND join_date <= "' . $to->format('Y-m-d') . '"';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_rood_new':
				if (!$from || !$to)
					return false;
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid ROOD') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('New') . ',' . $this->_membershipStatus('Current') . ')  AND join_date >= "' . $from->format('Y-m-d') . '" AND join_date <= "' . $to->format('Y-m-d') . '"';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_sp_end':
				if (!$from || !$to)
					return false;
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid SP') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('Cancelled') . ',' . $this->_membershipStatus('Expired') . ',' . $this->_membershipStatus('Deceased') . ')  AND end_date >= "' . $from->format('Y-m-d') . '" AND end_date <= "' . $to->format('Y-m-d') . '"';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_rood_end':
				if (!$from || !$to)
					return false;
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid ROOD') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('Cancelled') . ',' . $this->_membershipStatus('Expired') . ',' . $this->_membershipStatus('Deceased') . ') AND end_date >= "' . $from->format('Y-m-d') . '" AND end_date <= "' . $to->format('Y-m-d') . '"';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;

			case 'lid_sp_deceased':
				if (!$from || !$to)
					return false;
				$query = 'SELECT COUNT(*) FROM civicrm_membership WHERE membership_type_id IN (' . $this->_membershipType('Lid SP') . ',' . $this->_membershipType('Lid SP en ROOD') . ') AND status_id IN (' . $this->_membershipStatus('Deceased') . ') AND end_date >= "' . $from->format('Y-m-d') . '" AND end_date <= "' . $to->format('Y-m-d') . '"';
//				echo $query;
				return CRM_Core_DAO::singleValueQuery($query);
				break;
		}
	}

	private function _membershipType($string) {
		return array_search($string, $this->_membershipTypes);
	}

	private function _membershipStatus($string) {
		return array_search($string, $this->_membershipStatuses);
	}

}
