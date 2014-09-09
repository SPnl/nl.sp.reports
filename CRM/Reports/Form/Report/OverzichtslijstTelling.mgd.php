<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_OverzichtslijstTelling',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Overzichtslijst opsomming (telling)',
      'description' => 'Laat een gegevenslijst (afdelingsnaam en/of regio met aantallen leden) zien',
      'class_name' => 'CRM_Reports_Form_Report_OverzichtslijstTelling',
      'report_url' => 'nl.sp.reports/overzichtslijsttelling',
      'component' => 'CiviMember',
    ),
  ),
);