<?php
/* ============================================================================
 * PayrollCI v4 - Journal de Paie Mensuel
 * Écriture comptable synthétique mensuelle
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));

if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

$month = GETPOST('month', 'int') ?: intval(date('n'));
$year  = GETPOST('year', 'int') ?: intval(date('Y'));

llxHeader('', 'Journal de Paie');

$moisList = payrollci_get_mois();
$periodeLabel = $moisList[$month].' '.$year;

print load_fiche_titre(img_picto('', 'accountancy', 'class="pictofixedwidth"').'Journal de Paie - '.$periodeLabel, '', 'object_payrollci@payrollci');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" class="marginbottom">';
print '<div class="center">';
print '<select name="month" class="flat">';
foreach ($moisList as $num => $nom) {
    $sel = ($num == $month) ? ' selected' : '';
    print '<option value="'.$num.'"'.$sel.'>'.$nom.'</option>';
}
print '</select> ';
print '<input type="number" name="year" value="'.$year.'" min="2020" max="2035" class="flat" size="6"> ';
print '<input type="submit" class="button small" value="Afficher">';
print '</div></form>';

// Agrégats du mois
$sql = "SELECT COUNT(*) as nb, SUM(salaire_brut) as brut, SUM(brut_imposable) as imposable,";
$sql .= " SUM(cnps_retraite_sal) as cnps_sal, SUM(cnps_retraite_pat) as cnps_pat_ret,";
$sql .= " SUM(cnps_pf_pat) as cnps_pf, SUM(cnps_at_pat) as cnps_at,";
$sql .= " SUM(cmu_sal) as cmu_sal, SUM(cmu_pat) as cmu_pat,";
$sql .= " SUM(its_ibs) as ibs, SUM(its_ricf) as ricf, SUM(its_total) as its,";
$sql .= " SUM(contribution_employeur) as contrib, SUM(fdfp_ta) as fdfp_ta, SUM(fdfp_fpc) as fdfp_fpc,";
$sql .= " SUM(net_a_payer) as net, SUM(total_charges_pat) as charges_pat,";
$sql .= " SUM(avance_salaire + pret_deduction + pension_alimentaire + saisie_arret + mutuelle_complementaire + autres_retenues) as deductions";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip";
$sql .= " WHERE MONTH(date_start) = ".((int) $month)." AND YEAR(date_start) = ".((int) $year);
$sql .= " AND entity = ".$conf->entity;

$resql = $db->query($sql);
$d = $db->fetch_object($resql);

if ($d && $d->nb > 0) {
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>'.img_picto('', 'accountancy', 'class="pictofixedwidth"').'<b>Compte</b></td>';
    print '<td><b>Libellé</b></td><td class="right"><b>Débit</b></td><td class="right"><b>Crédit</b></td></tr>';

    $debit = $credit = 0;

    // Charges salariales (débit)
    $rows = [
        ['641', 'Rémunérations du personnel', $d->brut, 0],
        ['645.1', 'CNPS Retraite (part patronale)', $d->cnps_pat_ret, 0],
        ['645.2', 'CNPS PF + Maternité', $d->cnps_pf, 0],
        ['645.3', 'CNPS AT/MP', $d->cnps_at, 0],
        ['645.4', 'CMU (part patronale)', $d->cmu_pat, 0],
        ['647.1', 'Contribution employeur', $d->contrib, 0],
        ['647.2', 'FDFP/TA', $d->fdfp_ta, 0],
        ['647.3', 'FDFP/FPC', $d->fdfp_fpc, 0],
        ['431', 'CNPS (cotisations salarié + patronal)', 0, $d->cnps_sal + $d->cnps_pat_ret + $d->cnps_pf + $d->cnps_at],
        ['437', 'CMU (salarié + patronal)', 0, $d->cmu_sal + $d->cmu_pat],
        ['442', 'ITS à reverser au Trésor', 0, $d->its],
        ['447.1', 'Contribution employeur à reverser', 0, $d->contrib],
        ['447.2', 'FDFP à reverser', 0, $d->fdfp_ta + $d->fdfp_fpc],
        ['421', 'Personnel - Rémunérations dues (net)', 0, $d->net],
    ];
    if ($d->deductions > 0) {
        $rows[] = ['425', 'Avances et acomptes au personnel', 0, $d->deductions];
    }

    foreach ($rows as $r) {
        if ($r[2] > 0 || $r[3] > 0) {
            print '<tr class="oddeven">';
            print '<td><b>'.$r[0].'</b></td>';
            print '<td>'.$r[1].'</td>';
            print '<td class="right">'.($r[2] > 0 ? payrollci_format_amount($r[2]) : '').'</td>';
            print '<td class="right">'.($r[3] > 0 ? payrollci_format_amount($r[3]) : '').'</td>';
            print '</tr>';
            $debit += $r[2];
            $credit += $r[3];
        }
    }

    print '<tr class="liste_total">';
    print '<td colspan="2"><b>TOTAUX</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($debit).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($credit).'</b></td>';
    print '</tr>';

    $ecart = abs($debit - $credit);
    if ($ecart > 1) {
        print '<tr><td colspan="2" style="color:#c0392b;">'.img_picto('', 'warning', 'class="pictofixedwidth"').'<b>Écart : '.payrollci_format_amount($ecart).' FCFA</b></td><td colspan="2"></td></tr>';
    } else {
        print '<tr><td colspan="4" style="color:#27ae60;">'.img_picto('', 'tick', 'class="pictofixedwidth"').'<b>Journal équilibré</b></td></tr>';
    }

    print '</table></div>';

    // Résumé
    print '<br><div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'chart', 'class="pictofixedwidth"').'<b>Synthèse '.$periodeLabel.' ('.$d->nb.' bulletins)</b></td></tr>';
    print '<tr class="oddeven"><td>Masse salariale brute</td><td class="right"><b>'.payrollci_format_amount($d->brut).' FCFA</b></td></tr>';
    print '<tr class="oddeven"><td>Total charges patronales</td><td class="right">'.payrollci_format_amount($d->charges_pat).' FCFA</td></tr>';
    $cout = $d->brut + $d->charges_pat;
    print '<tr class="oddeven" style="background-color:#ffeaea;"><td><b>Coût total employeur</b></td><td class="right" style="color:#c0392b;"><b>'.payrollci_format_amount($cout).' FCFA</b></td></tr>';
    print '<tr class="oddeven"><td>Total net versé</td><td class="right" style="color:#27ae60;"><b>'.payrollci_format_amount($d->net).' FCFA</b></td></tr>';
    print '</table></div>';

} else {
    print '<div class="opacitymedium center">'.img_picto('', 'warning', 'class="pictofixedwidth"').'Aucun bulletin pour '.$periodeLabel.'</div>';
}

llxFooter();
$db->close();
