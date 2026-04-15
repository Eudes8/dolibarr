<?php
/* ============================================================================
 * PayrollCI v3 - Onglet Objets liés
 * Permet de lier le bulletin à d'autres objets Dolibarr (tiers, contrats, etc.)
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies", "other"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Payslip($db);
if ($id > 0 || !empty($ref)) $object->fetch($id, $ref);

$permissiontoadd = $user->rights->payrollci->creer || $user->admin;
if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

// ── Actions ──
if ($action == 'addlink' && $permissiontoadd) {
    $targettype = GETPOST('targettype', 'alpha');
    $targetid   = GETPOST('targetid', 'int');
    if ($targettype && $targetid > 0) {
        $object->add_object_linked($targettype, $targetid);
        setEventMessages('Lien ajouté', null, 'mesgs');
    }
}
if ($action == 'dellink' && $permissiontoadd) {
    $dellink = GETPOST('dellinkid', 'int');
    if ($dellink > 0) {
        $object->deleteObjectLinked(null, '', null, '', $dellink);
        setEventMessages('Lien supprimé', null, 'mesgs');
    }
}

// ── Affichage ──
llxHeader('', 'Objets liés - '.$object->ref);

$head = payrollci_prepare_head($object);
print dol_get_fiche_head($head, 'linked', 'Bulletin de Paie', -1, 'payrollci@payrollci');

$linkback = '<a href="'.dol_buildpath('/payrollci/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// Afficher les objets liés (standard Dolibarr)
$object->fetchObjectLinked();

if (is_array($object->linkedObjects) && count($object->linkedObjects) > 0) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Type</td><td>Référence</td><td>Date</td><td></td></tr>';

    foreach ($object->linkedObjects as $objecttype => $objects) {
        foreach ($objects as $linkedobj) {
            print '<tr class="oddeven">';
            print '<td>'.$objecttype.'</td>';
            print '<td>'.$linkedobj->getNomUrl(1).'</td>';
            print '<td>'.dol_print_date($linkedobj->date ?? $linkedobj->date_creation, 'day').'</td>';
            if ($permissiontoadd) {
                print '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=dellink&dellinkid='.$linkedobj->id.'&token='.newToken().'">'.img_picto('Supprimer', 'delete').'</a></td>';
            } else {
                print '<td></td>';
            }
            print '</tr>';
        }
    }
    print '</table>';
} else {
    print '<div class="opacitymedium">Aucun objet lié</div>';
}

// Formulaire pour ajouter un lien
if ($permissiontoadd) {
    print '<br>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="addlink">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="3">Ajouter un lien</td></tr>';
    print '<tr class="oddeven">';
    print '<td>Type d\'objet</td>';
    print '<td><select name="targettype" class="flat">';
    print '<option value="user">Utilisateur</option>';
    print '<option value="societe">Société / Tiers</option>';
    if (isModEnabled('contrat')) print '<option value="contrat">Contrat</option>';
    if (isModEnabled('projet')) print '<option value="project">Projet</option>';
    print '</select></td>';
    print '<td>ID <input type="number" name="targetid" min="1" class="flat maxwidth100"> ';
    print '<input type="submit" class="button" value="Ajouter"></td>';
    print '</tr></table></form>';
}

print '</div>';
print dol_get_fiche_end();

llxFooter();
$db->close();
