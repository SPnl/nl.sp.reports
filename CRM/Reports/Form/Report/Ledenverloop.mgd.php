<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
    array (
      'name' => 'CRM_Reports_Form_Report_Ledenverloop',
      'entity' => 'ReportTemplate',
      'params' =>
        array (
          'version' => 3,
          'label' => 'Ledenverloop per afdeling',
          'description' => 'Verloop van leden aantallen voor een afdeling',
          'class_name' => 'CRM_Reports_Form_Report_Ledenverloop',
          'report_url' => 'nl.sp.reports/ledenverloop',
          'component' => 'CiviMember',
        ),
    ),
);