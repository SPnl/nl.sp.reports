<?php

// Een beetje een bij elkaar gehackt rapport om deelnemers per evenement per afdeling te tonen:

class CRM_Reports_Form_Report_RegioconfOverview extends CRM_Report_Form_Event {

  private $_geoConfig;

  public function __construct() {

    $this->_columns = [
      'civicrm_participant' => [
        'dao'      => 'CRM_Event_DAO_Participant',
        'fields'   =>
          [
            'participant_id'     => [
              'title'      => 'Participant ID',
              'no_display' => TRUE,
            ],
            'participant_record' => [
              'name'       => 'id',
              'no_display' => TRUE,
              'required'   => TRUE,
            ],
            'event_id'           => [
              'no_display' => TRUE,
              'type'       => CRM_Utils_Type::T_STRING,
            ],
          ],
        'grouping' => 'event-fields',
        'filters'  =>
          [
            'event_id' => [
              'name'         => 'event_id',
              'title'        => ts('Event'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options'      => $this->getEventFilterOptions(),
            ],
          ],
      ],
      'civicrm_contact'     => [
        'dao'      => 'CRM_Contact_DAO_Contact',
        'grouping' => 'contact-fields',
      ],
      'report_custom'       => [
        'fields' => [
          'regio'              => [
            'title'    => 'Regio',
            'required' => TRUE,
            'default'  => TRUE,
          ],
          'afdeling'           => [
            'title'    => 'Afdeling',
            'required' => TRUE,
            'default'  => TRUE,
          ],
          'count_members'      => [
            'title'    => 'Leden',
            'required' => TRUE,
            'default'  => TRUE,
          ],
          'count_participants' => [
            'title'    => 'Aangemeld',
            'required' => TRUE,
            'default'  => TRUE,
          ],
          'count_voting'       => [
            'title'    => 'Deelnemers',
            'required' => TRUE,
            'default'  => TRUE,
          ],
          'count_guests'       => [
            'title'    => 'Gasten',
            'required' => TRUE,
            'default'  => TRUE,
          ],
        ],
      ],
    ];

    $config           = CRM_Geostelsel_Config::singleton();
    $this->_geoConfig = [
      'table'    => $config->getGeostelselCustomGroup('table_name'),
      'afdeling' => $config->getAfdelingsField('column_name'),
      'regio'    => $config->getRegioField('column_name'),
    ];

    parent::__construct();
  }

  public function preProcess() {
    $this->assign('reportTitle', ts('Deelnemerstelling per afdeling'));
    parent::preProcess();
  }

  public function select() {

    parent::select();
    $this->_select = "SELECT
     cr.display_name AS report_custom_regio,
     ca.display_name AS report_custom_afdeling,
     COUNT(DISTINCT cm.id) AS report_custom_count_members,
     COUNT(DISTINCT cp0.id) AS report_custom_count_participants,
     COUNT(DISTINCT cp1.id) AS report_custom_count_voting,
     COUNT(DISTINCT cp2.id) AS report_custom_count_guests
     ";
  }

  public function from() {

    // Dit doen we hier (alvast) zodat hij hieronder beschikbaar is

    $this->setParams($this->controller->exportValues($this->_name));
    $this->storeWhereHavingClauseArray();

    // Benodigde variabelen voor ON-clauses in joins

    $whereClause  = count($this->_whereClauses) > 0 ? array_shift($this->_whereClauses) : "";
    if($whereClause) {
      $whereClauses = [
        " AND " . str_replace($this->_aliases['civicrm_participant'], 'cp0', $whereClause),
        " AND " . str_replace($this->_aliases['civicrm_participant'], 'cp1', $whereClause),
        " AND " . str_replace($this->_aliases['civicrm_participant'], 'cp2', $whereClause),
      ];
    } else {
      $whereClauses = ['', '', ''];
    }

    $membershipTypes = [
      civicrm_api3('MembershipType', 'getvalue', ['return' => 'id', 'name' => 'Lid SP']),
      civicrm_api3('MembershipType', 'getvalue', ['return' => 'id', 'name' => 'Lid SP en ROOD']),
      civicrm_api3('MembershipType', 'getvalue', ['return' => 'id', 'name' => 'Lid ROOD']),
    ];
    $membershipStatuses = [
      civicrm_api3('MembershipStatus', 'getvalue', ['return' => 'id', 'name' => 'New']),
      civicrm_api3('MembershipStatus', 'getvalue', ['return' => 'id', 'name' => 'Current']),
    ];

    $allParticipantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantRoleDeelnemer = array_search('Deelnemer', $allParticipantRoles);
    $participantRoleGast = array_search('Gast', $allParticipantRoles);

    $allParticipantStatuses = CRM_Event_PseudoConstant::participantStatus();
    $participantStatuses = [array_search('Registered', $allParticipantStatuses), array_search('Attended', $allParticipantStatuses)];

    // Feitelijke FROM-query:

    $this->_from = "FROM civicrm_contact {$this->_aliases['civicrm_contact']}
                    {$this->_aclFrom}
                    LEFT JOIN {$this->_geoConfig['table']} cgeo
                    ON {$this->_aliases['civicrm_contact']}.id = cgeo.entity_id
                    LEFT JOIN civicrm_contact ca
                    ON cgeo.`{$this->_geoConfig['afdeling']}` = ca.id
                    LEFT JOIN civicrm_contact cr
                    ON cgeo.`{$this->_geoConfig['regio']}` = cr.id
                    LEFT OUTER JOIN civicrm_membership cm
                    ON cm.contact_id = {$this->_aliases['civicrm_contact']}.id AND cm.status_id IN ("  . implode(',',$membershipStatuses) . ") AND cm.membership_type_id IN ("  . implode(',',$membershipTypes) . ")
                    LEFT OUTER JOIN civicrm_participant cp0
                    ON cp0.contact_id = {$this->_aliases['civicrm_contact']}.id {$whereClauses[0]} AND cp0.status_id IN ("  . implode(',',$participantStatuses) . ")
                    LEFT OUTER JOIN civicrm_participant cp1
                    ON cp1.contact_id = {$this->_aliases['civicrm_contact']}.id {$whereClauses[1]} AND cp1.role_id = {$participantRoleDeelnemer} AND cp1.status_id IN ("  . implode(',',$participantStatuses) . ")
                    LEFT OUTER JOIN civicrm_participant cp2
                    ON cp2.contact_id = {$this->_aliases['civicrm_contact']}.id {$whereClauses[2]} AND cp2.role_id = {$participantRoleGast} AND cp2.status_id IN ("  . implode(',',$participantStatuses) . ")
                    ";

  }

  public function groupBy() {
    $this->_groupBy = "GROUP BY cgeo.`{$this->_geoConfig['afdeling']}` ";
  }

  public function orderBy() {
    $this->_orderBy = "ORDER BY cr.display_name ASC, ca.display_name ASC ";
  }

  public function limit() {
    $this->_limit = NULL;
  }

  public function where() {
    $this->_where = NULL;
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    // echo $sql; exit;

    $rows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => &$row) {
      foreach ($row as $fieldId => &$field) {
        $field = (is_numeric($field) && $field == 0) ? "-" : $field;
      }
    }
  }

}