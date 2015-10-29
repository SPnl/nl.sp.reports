<?php

class CRM_Reports_Form_Report_Kerncijfers extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_noFields = TRUE;

  private $_membershipTypes;
  private $_membershipStatuses;
  private $_rowHeaders;

  public function __construct() {

    $this->_columns     = [];
    $this->_groupFilter = FALSE;
    $this->_tagFilter   = FALSE;
    parent::__construct();
  }

  public function preProcess() {
    $this->assign('reportTitle', ts('Kerncijfers SP'));
    parent::preProcess();
  }

  public function postProcess() {

    // Lange of korte weergave based on context

    $context = $_GET['context'];
    switch ($context) {
      case 'dashlet':
        $weekCount = 2;
        $yearCount = 1;
        break;
      default:
        $weekCount = 13;
        $yearCount = 2;
        break;
    }

    $this->beginPostProcess();

    $this->_membershipTypes    = CRM_Member_PseudoConstant::membershipType();
    $this->_membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();

    $this->_columnHeaders = [
      'name' => ['title' => 'Omschrijving', 'type' => 0],
    ];
    $this->_rowHeaders    = [
      'sp_begin'        => ['section' => 'SP', 'name' => 'Beginstand'],
      'sp_new'          => ['section' => 'SP', 'name' => 'Ingeschreven'],
      'sp_expired'      => ['section' => 'SP', 'name' => 'Uitgeschreven'],
      'sp_deceased'     => ['section' => 'SP', 'name' => 'Overleden'],
      'sp_end'          => ['section' => 'SP', 'name' => 'Eindstand'],
      'rood_begin'      => ['section' => 'ROOD', 'name' => 'Beginstand'],
      'rood_new'        => ['section' => 'ROOD', 'name' => 'Ingeschreven'],
      'rood_expired'    => ['section' => 'ROOD', 'name' => 'Uitgeschreven'],
      'rood_end'        => ['section' => 'ROOD', 'name' => 'Eindstand'],
      'other_tribune'   => ['section' => 'Overig', 'name' => 'Abonnees Tribune'],
      'other_tribproef' => ['section' => 'Overig', 'name' => 'Proefabonnees Tribune'],
      'other_spanning'  => ['section' => 'Overig', 'name' => 'Abonnees Spanning'],
      'other_donateurs' => ['section' => 'Overig', 'name' => 'Donateurs'],
      'other_total'     => ['section' => 'Overig', 'name' => 'Totaal'],
    ];

    $rows = [];
    $data = [];

    // Cijfers per week

    for ($week = $weekCount - 1; $week >= 0; $week--) {

      $wk = new \DateTime('monday this week');
      if ($week > 0) {
        $wk->sub(new \DateInterval('P' . $week . 'W'));
      }
      $wf                               = (int) $wk->format('W');
      $this->_columnHeaders['wk' . $wf] = ['title' => 'Wk&nbsp;' . $wf, 'type' => 1];

      $wkEnd = clone $wk;
      $wkEnd->add(new \DateInterval('P1W'));

      $data['wk' . $wf] = [
        'sp_begin'        => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], NULL, $wk, 'join'),
        'sp_new'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $wk, $wkEnd, 'join'),
        'sp_expired'      => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $wk, $wkEnd, 'end_not_deceased'),
        'sp_deceased'     => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $wk, $wkEnd, 'deceased'),
        'sp_end'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], NULL, $wkEnd, 'join'),
        'rood_begin'      => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], NULL, $wk, 'join'),
        'rood_new'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], $wk, $wkEnd, 'join'),
        'rood_expired'    => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], $wk, $wkEnd, 'end'),
        'rood_end'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], NULL, $wkEnd, 'join'),
        'other_tribune'   => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Audio-Tribune Betaald'], NULL, $wkEnd, 'join'),
        'other_tribproef' => $this->_getCount(['Abonnee Blad-Tribune Proef'], NULL, $wkEnd, 'join'),
        'other_spanning'  => $this->_getCount(['Abonnee SPanning Betaald'], NULL, $wkEnd, 'join'),
        'other_donateurs' => $this->_getCount(['SP Donateur'], NULL, $wkEnd, 'join'),
        'other_total'     => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Blad-Tribune Proef', 'Abonnee Audio-Tribune Betaald', 'Abonnee SPanning Betaald', 'SP Donateur'], NULL, $wkEnd, 'join'),
      ];
    }

    // Cijfers per jaar

    for ($year = 0; $year < $yearCount; $year++) {
      $yr    = new \DateTime('01/01');
      $yrEnd = clone $yr;
      if ($year > 0) {
        $interval = new \DateInterval('P' . $year . 'Y');
        $yr->sub($interval);
        $yrEnd->sub($interval)->add(new \DateInterval('P1Y'));

        $yf    = (int) $yr->format('Y');
        $key   = (int) $yr->format('Y');
        $title = '(' . $yf . ')';
      }
      else {
        $yf = (int) $yr->format('Y');
        $yrEnd->add(new \DateInterval('P364D')); // 364 dagen ivm einddata 31-12

        $key   = 'ycur';
        $title = $yf;
      }
      $this->_columnHeaders[$key] = ['title' => $title, 'type' => 1];


      $data[$key] = [
        'sp_begin'        => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], NULL, $yr, 'join'),
        'sp_new'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $yr, $yrEnd, 'join'),
        'sp_expired'      => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $yr, $yrEnd, 'end_not_deceased'),
        'sp_deceased'     => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], $yr, $yrEnd, 'deceased'),
        'sp_end'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], NULL, $yrEnd, 'join'),
        'rood_begin'      => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], NULL, $yr, 'join'),
        'rood_new'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], $yr, $yrEnd, 'join'),
        'rood_expired'    => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], $yr, $yrEnd, 'end'),
        'rood_end'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], NULL, $yrEnd, 'join'),
        'other_tribune'   => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Audio-Tribune Betaald'], NULL, $yrEnd, 'join'),
        'other_tribproef' => $this->_getCount(['Abonnee Blad-Tribune Proef'], NULL, $yrEnd, 'join'),
        'other_spanning'  => $this->_getCount(['Abonnee SPanning Betaald'], NULL, $yrEnd, 'join'),
        'other_donateurs' => $this->_getCount(['SP Donateur'], NULL, $yrEnd, 'join'),
        'other_total'     => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Blad-Tribune Proef', 'Abonnee Audio-Tribune Betaald', 'Abonnee SPanning Betaald', 'SP Donateur'], NULL, $yrEnd, 'join'),
      ];
    }

    // Data uitschrijven in rijen...:

    foreach ($this->_rowHeaders as $rkey => $row) {

      foreach ($this->_columnHeaders as $ckey => $cheader) {
        if ($ckey == 'name') {
          continue;
        }

        $row[$ckey] = $data[$ckey][$rkey];
      }
      $rows[] = $row;
    }

    // Section headers and totals

    $this->assign('sections', [
      'section' => [
        'title' => 'Kerncijfers',
        'name'  => 'section',
        'type'  => 1,
      ],
    ]);

    $this->assign('sectionTotals', [
      'SP'     => $data['ycur']['sp_end'],
      'ROOD'   => $data['ycur']['rood_end'],
      'Overig' => $data['ycur']['other_total'],
    ]);

    // Finalize

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  // Feitelijke counts uitvoeren
  private function _getCount($membershipTypes, $from = NULL, $to, $type = 'join') {

    foreach ($membershipTypes as &$t) {
      $t = array_search($t, $this->_membershipTypes);
    }
    $membershipTypeString = implode(',', $membershipTypes);

    $statuses = ['New', 'Current', 'Grace', 'Expired', 'Cancelled']; // ie excluding Pending
    if($type == 'deceased') {
      $statuses = ['Deceased'];
    } elseif($type != 'end_not_deceased') {
      $statuses[] = 'Deceased';
    }
    foreach ($statuses as &$s) {
      $s = array_search($s, $this->_membershipStatuses);
    }
    $membershipStatusString = implode(',', $statuses);

    $query = "SELECT COUNT(*) FROM civicrm_membership
          WHERE membership_type_id IN ({$membershipTypeString})
          AND status_id IN ({$membershipStatusString})
        ";

    if ($type == 'join') {
      if ($from) {
        $query .= "AND join_date >= '" . $from->format('Y-m-d') . "' ";
      }
      $query .= "AND join_date < '" . $to->format('Y-m-d') . "'
                   AND (end_date IS NULL OR end_date >= '" . $to->format('Y-m-d') . "')
                   ";
    }
    else {
      if ($from) {
        $query .= "AND end_date >= '" . $from->format('Y-m-d') . "' ";
      }
      $query .= "AND end_date < '" . $to->format('Y-m-d') . "'
      ";
    }

    return CRM_Core_DAO::singleValueQuery($query);
  }

}
