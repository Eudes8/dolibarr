<?php
/* ============================================================================
 * PayrollCI v3 - Onglet "Bulletins de paie" sur la fiche utilisateur/employé
 * Apparaît sur la fiche de chaque utilisateur Dolibarr
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "users"));

$fk_user = GETPOST('fk_user', 'int') ?: GETPOST('id', 'int');
if (empty($fk_user)) accessforbidden();

$permissiontoread = $user->rights->payrollci->lire || $user->admin;
if (!$permissiontoread) accessforbidden();

$userobj = new User($db);
$userobj->fetch($fk_user);

// ── Affichage ──
llxHeader('', 'Bulletins de paie - '.$userobj->getFullName($langs));

// Onglets de la fiche utilisateur (standard Dolibarr)
$head = user_prepare_head($userobj);
print dol_get_fiche_head($head, 'payrollci', $langs->trans("User"), -1, 'user');

// Mini-bandeau utilisateur
print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">Employé</td><td><b>'.$userobj->getFullName($langs).'</b></td></tr>';
if ($userobj->job) print '<tr><td>Poste</td><td>'.$userobj->job.'</td></tr>';
if ($userobj->email) print '<tr><td>Email</td><td>'.$userobj->email.'</td></tr>';
print '</table></div><br>';

// ── Requête bulletins ──
$sql = "SELECT p.rowid, p.ref, p.date_start, p.salaire_brut, p.net_a_payer, p.status,";
$sql .= " p.total_charges_pat, p.total_retenues_sal, p.brut_imposable";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE p.fk_user = ".((int) $fk_user);
$sql .= " AND p.entity = ".$conf->entity;
$sql .= " ORDER BY p.date_start DESC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    // Totaux de l'employé
    $totalBrut = 0; $totalNet = 0; $totalPat = 0;

    $newButton = '';
    if ($user->rights->payrollci->creer || $user->admin) {
        $newButton = '<a class="butAction" href="'.dol_buildpath('/payrollci/card.php', 1).'?action=create&fk_user='.$fk_user.'">Nouveau bulletin</a>';
    }
    print load_fiche_titre('Bulletins de paie ('.$num.')', $newButton, '');

    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Réf</td><td class="center">Période</td>';
    print '<td class="right">Brut</td><td class="right">Retenues sal.</td>';
    print '<td class="right">Net à payer</td>';
    print '<td class="right">Charges pat.</td>';
    print '<td class="center">Statut</td><td></td></tr>';

    $moisList = payrollci_get_mois();
    $objectstatic = new Payslip($db);

    while ($obj = $db->fetch_object($resql)) {
        $objectstatic->id = $obj->rowid;
        $objectstatic->ref = $obj->ref;
        $objectstatic->employee_name = $userobj->getFullName($langs);
        $objectstatic->net_a_payer = $obj->net_a_payer;

        $totalBrut += $obj->salaire_brut;
        $totalNet  += $obj->net_a_payer;
        $totalPat  += $obj->total_charges_pat;

        $mois = intval(date('m', $db->jdate($obj->date_start)));
        $an = date('Y', $db->jdate($obj->date_start));

        print '<tr class="oddeven">';
        print '<td>'.$objectstatic->getNomUrl(1).'</td>';
        print '<td class="center">'.$moisList[$mois].' '.$an.'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->salaire_brut).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_retenues_sal).'</td>';
        print '<td class="right"><b>'.payrollci_format_amount($obj->net_a_payer).'</b></td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_charges_pat).'</td>';
        print '<td class="center">'.$objectstatic->LibStatut($obj->status).'</td>';
        print '<td class="center"><a href="'.dol_buildpath('/payrollci/card.php', 1).'?id='.$obj->rowid.'">'.img_picto('Voir', 'eye').'</a></td>';
        print '</tr>';
    }

    if ($num == 0) {
        print '<tr class="oddeven"><td colspan="8" class="opacitymedium center">Aucun bulletin pour cet employé</td></tr>';
    } else {
        // Ligne totaux
        print '<tr class="liste_total">';
        print '<td><b>TOTAL ('.$num.' bulletins)</b></td><td></td>';
        print '<td class="right"><b>'.payrollci_format_amount($totalBrut).'</b></td><td></td>';
        print '<td class="right"><b>'.payrollci_format_amount($totalNet).'</b></td>';
        print '<td class="right"><b>'.payrollci_format_amount($totalPat).'</b></td>';
        print '<td colspan="2"></td></tr>';

        // Moyenne mensuelle
        print '<tr class="oddeven" style="font-style:italic;color:#888">';
        print '<td>Moyenne mensuelle</td><td></td>';
        print '<td class="right">'.payrollci_format_amount($totalBrut / $num).'</td><td></td>';
        print '<td class="right">'.payrollci_format_amount($totalNet / $num).'</td>';
        print '<td class="right">'.payrollci_format_amount($totalPat / $num).'</td>';
        print '<td colspan="2"></td></tr>';
    }

    print '</table></div>';
} else {
    dol_print_error($db);
}

print dol_get_fiche_end();
llxFooter();
$db->close();
