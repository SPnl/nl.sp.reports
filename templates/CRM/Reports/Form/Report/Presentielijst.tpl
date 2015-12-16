{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{literal}
<style type="text/css">
  /**
   * Issue with page breaks and large tables
   * Solution from: http://stackoverflow.com/questions/13516534/how-to-avoid-page-break-inside-table-row-for-wkhtmltopdf#13525758
   *
   */
  table, tr, td, th, tbody, thead, tfoot {
    page-break-inside: avoid !important;
  }
</style>


<script type="text/javascript">
  cj(document).ready(function(){
    cj('#birth_date_from').attr('startOffset',200);
    cj('#birth_date_from').attr('endoffset',0);
    cj('#birth_date_to').attr('startOffset',200);
    cj('#birth_date_to').attr('endoffset',0);
  });
</script>
{/literal}

{include file="CRM/Report/Form.tpl"}
