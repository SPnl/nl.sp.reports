<?php

class CRM_Reports_Form_Report_RelationshipAdresSticker extends CRM_Report_Form_Contact_Relationship {

  protected $_add2groupSupported = FALSE;
  protected $_csvSupported = FALSE;
  protected $_summary = NULL;

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields']['sort_name_a']['required'] = true;
    $this->_columns['civicrm_contact']['fields']['sort_name_a']['name'] = 'display_name';
    $this->_columns['civicrm_contact_b']['fields']['sort_name_b']['required'] = true;
    $this->_columns['civicrm_contact_b']['fields']['sort_name_b']['name'] = 'display_name';

    $this->_columns['civicrm_relationship']['filters']['is_active']['default'] = 1;
    $this->_columns['civicrm_relationship']['filters']['relationship_type_id']['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
    $this->_columns['civicrm_relationship']['filters']['relationship_type_id']['options'] = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, 'Individual', FALSE, 'label', FALSE);

    $this->_columns['civicrm_address']['fields']['street_address'] = array(
      'title' => ts('Street address'),
      'name' => 'street_address',
      'required' => true,
    );
    $this->_columns['civicrm_address']['fields']['postal_code'] = array(
      'title' => ts('Postal code'),
      'name' => 'postal_code',
      'required' => true,
    );
    $this->_columns['civicrm_address']['fields']['city'] = array(
      'title' => ts('City'),
      'name' => 'city',
      'required' => true,
    );
    $this->_columns['civicrm_address']['fields']['country_id'] = array(
      'title' => ts('Country'),
      'name' => 'country_id',
      'required' => true,
    );
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name";
  }

  function from() {
    $this->_from = "
        FROM civicrm_relationship {$this->_aliases['civicrm_relationship']}

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_a =
                             {$this->_aliases['civicrm_contact']}.id )

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact_b']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_b =
                             {$this->_aliases['civicrm_contact_b']}.id )

             {$this->_aclFrom} ";


    $this->_from .= "
        INNER  JOIN civicrm_address {$this->_aliases['civicrm_address']}
                     ON ( {$this->_aliases['civicrm_address']}.contact_id =
                           {$this->_aliases['civicrm_contact']}.id AND
                           {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";

    $this->_from .= "
        INNER JOIN civicrm_relationship_type {$this->_aliases['civicrm_relationship_type']}
                        ON ( {$this->_aliases['civicrm_relationship']}.relationship_type_id  =
                             {$this->_aliases['civicrm_relationship_type']}.id  ) ";

    // include Email Field
    if ($this->_emailField_a) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                       ON ( {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_email']}.contact_id AND
                            {$this->_aliases['civicrm_email']}.is_primary = 1 )";
    }
    if ($this->_emailField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_email_b']}.contact_id AND
                            {$this->_aliases['civicrm_email_b']}.is_primary = 1 )";
    }
  }

  function where() {
    $whereClauses = $havingClauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {

          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          elseif ($tableName == 'civicrm_relationship' && $fieldName == 'is_active') {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);

            if (($op == 'eq' && $value == '1') || ($op == 'neq' && $value == '0')) {
              $clause = "({$field['dbAlias']} = '1' AND ({$field['alias']}.start_date IS NULL OR {$field['alias']}.start_date <= CURDATE()) AND ({$field['alias']}.end_date IS NULL OR {$field['alias']}.end_date >= CURDATE()))";
            } else {
              $clause = "NOT ({$field['dbAlias']} = '1' AND ({$field['alias']}.start_date IS NULL OR {$field['alias']}.start_date <= CURDATE()) AND ({$field['alias']}.end_date IS NULL OR {$field['alias']}.end_date >= CURDATE()))";
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {

              if ($tableName == 'civicrm_relationship_type' &&
                ($fieldName == 'contact_type_a' || $fieldName == 'contact_type_b')
              ) {
                $cTypes = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                $contactTypes = $contactSubTypes = array();
                if (!empty($cTypes)) {
                  foreach ($cTypes as $ctype) {
                    $getTypes = CRM_Utils_System::explode('_', $ctype, 2);
                    if ($getTypes[1] && !in_array($getTypes[1], $contactSubTypes)) {
                      $contactSubTypes[] = $getTypes[1];
                    }
                    elseif ($getTypes[0] && !in_array($getTypes[0], $contactTypes)) {
                      $contactTypes[] = $getTypes[0];
                    }
                  }
                }

                if (!empty($contactTypes)) {
                  $clause = $this->whereClause($field,
                    $op,
                    $contactTypes,
                    CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                  );
                }

                if (!empty($contactSubTypes)) {
                  if ($fieldName == 'contact_type_a') {
                    $field['name'] = 'contact_sub_type_a';
                  }
                  else {
                    $field['name'] = 'contact_sub_type_b';
                  }
                  $field['dbAlias'] = $field['alias'] . '.' . $field['name'];
                  $subTypeClause = $this->whereClause($field,
                    $op,
                    $contactSubTypes,
                    CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                  );
                  if ($clause) {
                    $clause = '(' . $clause . ' OR ' . $subTypeClause . ')';
                  }
                  else {
                    $clause = $subTypeClause;
                  }
                }
              }
              else {
                $clause = $this->whereClause($field,
                  $op,
                  CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                );
              }
            }
          }

          if (!empty($clause)) {
            if (CRM_Utils_Array::value('having', $field)) {
              $havingClauses[] = $clause;
            }
            else {
              $whereClauses[] = $clause;
            }
          }
        }
      }
    }

    if (empty($whereClauses)) {
      $this->_where = 'WHERE ( 1 ) ';
      $this->_having = '';
    }
    else {
      $this->_where = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = 'HAVING ' . implode(' AND ', $havingClauses);
    }
  }

  function statistics(&$rows) {
    return array();
  }

  function postProcess() {
    $this->beginPostProcess();

    $this->relationType = NULL;
    $relTypes = array();
    $originalRelationTypeIdValue = array();
    if (CRM_Utils_Array::value('relationship_type_id_value', $this->_params)) {
      $originalRelationTypeIdValue = CRM_Utils_Array::value('relationship_type_id_value', $this->_params);
      foreach(CRM_Utils_Array::value('relationship_type_id_value', $this->_params) as $relType) {
        $relTypeId = explode('_', $relType);
        $relTypes[] = intval($relTypeId[0]);
      }
    }
    $this->_params['relationship_type_id_value'] = $relTypes;

    $this->buildACLClause(array($this->_aliases['civicrm_contact'], $this->_aliases['civicrm_contact_b']));
    $sql = $this->buildQuery();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);

    if (!empty($originalRelationTypeIdValue)) {
      // store its old value, CRM-5837
      $this->_params['relationship_type _id_value'] = $originalRelationTypeIdValue;
    }
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name_a', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          true
        );
        $rows[$rowNum]['civicrm_contact_sort_name_a_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_a_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_b_sort_name_b', $row) &&
        array_key_exists('civicrm_contact_b_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_b_id'],
          true
        );
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_link'] = $url;
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
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
      $i = $labelsPerPage+1;
      foreach ($rows as $row) {
        $label = $this->formatRowAsLabel($row);
        $pdf->AddPdfLabel($label);

        $i++;
        if ($i > $labelsPerPage) {
          $i = 1;
        }
      }
      $pdf->Output($fileName, 'D');

      CRM_Utils_System::civiExit();
    } else {
      parent::endPostProcess($rows);
    }
  }

  function formatRowAsLabel($row) {
    $val .= "";
    $val .= $row['civicrm_contact_b_sort_name_b'] . "\r\n";
    $val .= $row['civicrm_contact_sort_name_a']. "\r\n";
    $val .= $row['civicrm_address_street_address']."\r\n";
    //isue #333: als een plaatsnaam te lang is dan de plaatsnaam op volgende regel
    //dit is slechts bij benadering.
    if (strlen($row['civicrm_address_city']) > 15) {
      $val .= $row['civicrm_address_postal_code'] . "\r\n" . $row['civicrm_address_city'] . "\r\n";
    } else {
      $val .= $row['civicrm_address_postal_code'] . ' ' . $row['civicrm_address_city'] . "\r\n";
    }
    return $val;
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