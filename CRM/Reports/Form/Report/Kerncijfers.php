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
        'sp_begin'        => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], NULL, $wk, 'join'),
        'sp_new'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], $wk, $wkEnd, 'join'),
        'sp_expired'      => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['Grace', 'Expired', 'Cancelled'], $wk, $wkEnd, 'end'),
        'sp_deceased'     => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['Deceased'], $wk, $wkEnd, 'end'),
        'sp_end'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'rood_begin'      => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], NULL, $wk, 'join'),
        'rood_new'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], $wk, $wkEnd, 'join'),
        'rood_expired'    => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['Grace', 'Expired', 'Cancelled', 'Deceased'], $wk, $wkEnd, 'end'),
        'rood_end'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'other_tribune'   => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Audio-Tribune Betaald'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'other_tribproef' => $this->_getCount(['Abonnee Blad-Tribune Proef'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'other_spanning'  => $this->_getCount(['Abonnee SPanning Betaald'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'other_donateurs' => $this->_getCount(['SP Donateur'], ['New', 'Current'], NULL, $wkEnd, 'join'),
        'other_total'     => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Blad-Tribune Proef', 'Abonnee Audio-Tribune Betaald', 'Abonnee SPanning Betaald', 'SP Donateur'], ['New', 'Current'], NULL, $wkEnd, 'join'),
      ];
    }

    // Cijfers per jaar

    for ($year = 0; $year < $yearCount; $year++) {
      $yr = new \DateTime('01/01');
      if ($year > 0) {
        $yr->sub(new \DateInterval('P' . $year . 'Y'));
        $yf    = (int) $yr->format('Y');
        $key   = (int) $yr->format('Y');
        $title = '(' . $yf . ')';
      }
      else {
        $yf    = (int) $yr->format('Y');
        $key   = 'ycur';
        $title = $yf;
      }
      $this->_columnHeaders[$key] = ['title' => $title, 'type' => 1];

      $yrEnd = clone $yr;
      $yrEnd->add(new \DateInterval('P1Y'));

      $data[$key] = [
        'sp_begin'        => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], NULL, $yr, 'join'),
        'sp_new'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], $yr, $yrEnd, 'join'),
        'sp_expired'      => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['Grace', 'Expired', 'Cancelled'], $yr, $yrEnd, 'end'),
        'sp_deceased'     => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['Deceased'], $yr, $yrEnd, 'end'),
        'sp_end'          => $this->_getCount(['Lid SP', 'Lid SP en ROOD'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'rood_begin'      => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], NULL, $yr, 'join'),
        'rood_new'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], $yr, $yrEnd, 'join'),
        'rood_expired'    => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['Grace', 'Expired', 'Cancelled', 'Deceased'], $yr, $yrEnd, 'end'),
        'rood_end'        => $this->_getCount(['Lid SP en ROOD', 'Lid ROOD'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'other_tribune'   => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Audio-Tribune Betaald'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'other_tribproef' => $this->_getCount(['Abonnee Blad-Tribune Proef'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'other_spanning'  => $this->_getCount(['Abonnee SPanning Betaald'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'other_donateurs' => $this->_getCount(['SP Donateur'], ['New', 'Current'], NULL, $yrEnd, 'join'),
        'other_total'     => $this->_getCount(['Abonnee Blad-Tribune Betaald', 'Abonnee Blad-Tribune Proef', 'Abonnee Audio-Tribune Betaald', 'Abonnee SPanning Betaald', 'SP Donateur'], ['New', 'Current'], NULL, $yrEnd, 'join'),
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
  private function _getCount($membershipTypes, $membershipStatuses, $from = NULL, $to = NULL, $dateChkType = 'join') {

    foreach ($membershipTypes as &$t) {
      $t = array_search($t, $this->_membershipTypes);
    }
    $membershipTypeString = implode(',', $membershipTypes);

    foreach ($membershipStatuses as &$s) {
      $s = array_search($s, $this->_membershipStatuses);
    }
    $membershipStatusString = implode(',', $membershipStatuses);

    $query = "SELECT COUNT(*) FROM civicrm_membership
          WHERE membership_type_id IN ({$membershipTypeString})
          AND status_id IN ({$membershipStatusString})
        ";

    switch ($dateChkType) {

      case 'join':
        if ($from) {
          $query .= "AND join_date >= '" . $from->format('Y-m-d') . "'";
        }
        if ($to) {
          $query .= "AND join_date <= '" . $to->format('Y-m-d') . "'";
        }
        break;
      case 'end':
        if ($from) {
          $query .= "AND end_date >= '" . $from->format('Y-m-d') . "'";
        }
        if ($to) {
          $query .= "AND end_date <= '" . $to->format('Y-m-d') . "'";
        }
        break;
      case 'both':
        // Not necessary?
        break;
    }

    return CRM_Core_DAO::singleValueQuery($query);
  }

}
