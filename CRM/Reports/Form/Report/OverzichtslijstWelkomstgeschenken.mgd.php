<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_OverzichtslijstWelkomstgeschenken',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Overzichtslijst Welkomstgeschenken',
      'description' => 'Overzichtslijst van status en type welkomstgeschenk per lidmaatschap',
      'class_name' => 'CRM_Reports_Form_Report_OverzichtslijstWelkomstgeschenken',
      'report_url' => 'nl.sp.reports/OverzichtslijstWelkomstgeschenken',
      'component' => 'CiviMember',
    ),
  ),
);