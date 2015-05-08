<?php

class CRM_Reports_Form_Report_Relationship extends CRM_Report_Form_Contact_Relationship {

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


}