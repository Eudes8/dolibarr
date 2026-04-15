<?php
/* ============================================================================
 * PayrollCI v3 - Onglet Documents du bulletin
 * Gestion des PDF et fichiers joints via le système Dolibarr
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies", "other"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'aZ');

$object = new Payslip($db);
if ($id > 0 || !empty($ref)) $object->fetch($id, $ref);

$permissiontoread = $user->rights->payrollci->lire || $user->admin;
$permissiontoadd  = $user->rights->payrollci->creer || $user->admin;
if (!$permissiontoread) accessforbidden();

$upload_dir = $conf->payrollci->dir_output.'/bulletins/'.dol_sanitizeFileName($object->ref);
if (!is_dir($upload_dir)) dol_mkdir($upload_dir);

// Gérer l'ancien PDF (V2)
$oldpdf = $conf->payrollci->dir_output.'/bulletins/'.$object->ref.'.pdf';
$newpdf = $upload_dir.'/'.$object->ref.'.pdf';
if (file_exists($oldpdf) && !file_exists($newpdf)) copy($oldpdf, $newpdf);

// ── Actions fichiers ──
include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

if ($action == 'builddoc') {
    $object->generateDocument('pdf_bulletinpaie', $langs);
    setEventMessages('PDF généré avec succès', null, 'mesgs');
}

// ── Affichage ──
llxHeader('', 'Documents - '.$object->ref, '', '', 0, 0, '', '', '', 'mod-payrollci page-document');

$head = payrollci_prepare_head($object);
print dol_get_fiche_head($head, 'documents', 'Bulletin de Paie', -1, 'payrollci@payrollci');

// Bandeau
$linkback = '<a href="'.dol_buildpath('/payrollci/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

print '<div class="fichecenter"><div class="underbanner clearboth"></div>';

// Zone documents
$formfile = new FormFile($db);
$objref = dol_sanitizeFileName($object->ref);
$filedir = $conf->payrollci->dir_output.'/bulletins/'.$objref;
$urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;

print $formfile->showdocuments(
    'payrollci',
    $objref,
    $filedir,
    $urlsource,
    $permissiontoadd, // generate
    $permissiontoadd, // delete
    'pdf_bulletinpaie',
    1, // genifempty
    0,
    0,
    28,
    0,
    '',
    0,
    '',
    '',
    '',
    null
);

print '</div>';

print dol_get_fiche_end();
llxFooter();
$db->close();
