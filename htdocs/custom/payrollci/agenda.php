<?php
/* ============================================================================
 * PayrollCI v3 - Onglet Événements/Agenda du bulletin
 * Affiche l'historique des actions (création, validation, PDF, etc.)
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies", "other", "agenda"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Payslip($db);
if ($id > 0 || !empty($ref)) $object->fetch($id, $ref);

if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

// ── Affichage ──
$title = $langs->trans("Events").' - '.$object->ref;
llxHeader('', $title);

$head = payrollci_prepare_head($object);
print dol_get_fiche_head($head, 'agenda', 'Bulletin de Paie', -1, 'payrollci@payrollci');

$linkback = '<a href="'.dol_buildpath('/payrollci/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// Charger et afficher les événements liés
$morehtmlcenter = '';
$modulename = 'payrollci';
$permissiontoadd = $user->rights->payrollci->creer || $user->admin;

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
$formactions = new FormActions($db);

// Afficher les événements liés à l'objet
$out = $formactions->showactions($object, 'payslip', 0, 1, '', 10, '', $morehtmlcenter);
print $out;

print '</div>';
print dol_get_fiche_end();

llxFooter();
$db->close();
