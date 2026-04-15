<?php
/* ============================================================================
 * PayrollCI v3 - Gestion des extrafields (champs personnalisés)
 * ============================================================================ */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("admin", "payrollci@payrollci"));

if (!$user->admin) accessforbidden();

$extrafields = new ExtraFields($db);
$form = new Form($db);

$elementtype = 'payrollci_payslip';
$textobject = 'Bulletin de paie';

// Actions
require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

// Affichage
llxHeader('', 'Champs personnalisés - PayrollCI');

$head = payrollci_admin_prepare_head();
print dol_get_fiche_head($head, 'extrafields', 'PayrollCI', -1, 'payrollci@payrollci');

print '<div class="fichecenter">';

// Tableau des extrafields existants
$extrafields->fetch_name_optionals_label($elementtype);

print load_fiche_titre('Champs personnalisés des bulletins de paie', '', '');
print '<div class="opacitymedium marginbottom">';
print 'Ajoutez des champs supplémentaires aux bulletins de paie pour stocker des informations spécifiques à votre entreprise.';
print '</div>';

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

// Formulaire création/édition
require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';

print '</div>';
print dol_get_fiche_end();

llxFooter();
