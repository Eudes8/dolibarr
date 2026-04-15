<?php
/* ============================================================================
 * PayrollCI v3 - Onglet Notes du bulletin
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Payslip($db);
if ($id > 0 || !empty($ref)) $object->fetch($id, $ref);

$permissiontoadd = $user->rights->payrollci->creer || $user->admin;
if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

// ── Actions notes ──
if ($action == 'setnote_public' && $permissiontoadd) {
    $object->note_public = GETPOST('note_public', 'restricthtml');
    $object->update($user, 1);
}
if ($action == 'setnote_private' && $permissiontoadd) {
    $object->note_private = GETPOST('note_private', 'restricthtml');
    $object->update($user, 1);
}

// ── Affichage ──
llxHeader('', 'Notes - '.$object->ref);

$head = payrollci_prepare_head($object);
print dol_get_fiche_head($head, 'notes', 'Bulletin de Paie', -1, 'payrollci@payrollci');

$linkback = '<a href="'.dol_buildpath('/payrollci/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// Utiliser le template standard Dolibarr pour les notes
$cssclass = 'titlefield';
$permission = $permissiontoadd;
include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

print '</div>';
print dol_get_fiche_end();

llxFooter();
$db->close();
