<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Reports_Form_Report_MismatchContribution',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Mismatch Contribution and Membership financial type',
      'description' => 'Mismatch between the financial type of the contribution and of the membership)',
      'class_name' => 'CRM_Reports_Form_Report_MismatchContribution',
      'report_url' => 'nl.sp.reports/mismatchcontribution',
      'component' => 'CiviContribute',
    ),
  ),
);