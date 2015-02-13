<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_BulkTribune',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Bulk Tribune',
      'description' => 'Geeft een overzicht van alle tribune leden met de afdeling en bezorggebied',
      'class_name' => 'CRM_Reports_Form_Report_BulkTribune',
      'report_url' => 'nl.sp.reports/BulkTribune',
      'component' => 'CiviMember',
    ),
  ),
);