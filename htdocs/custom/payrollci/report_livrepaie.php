<?php
/* ============================================================================
 * PayrollCI v4 - Livre de Paie (registre mensuel obligatoire)
 * Art. 46.2 CCI - Liste de tous les salariés avec détail des éléments
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));

if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

$month = GETPOST('month', 'int') ?: intval(date('n'));
$year  = GETPOST('year', 'int') ?: intval(date('Y'));

llxHeader('', 'Livre de Paie', '', '', 0, 0, '', '', '', 'mod-payrollci page-report');

$moisList = payrollci_get_mois();
$periodeLabel = $moisList[$month].' '.$year;

print load_fiche_titre(img_picto('', 'list', 'class="pictofixedwidth"').'Livre de Paie - '.$periodeLabel, '', 'object_payrollci@payrollci');

// Filtre période
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

$sql = "SELECT p.* FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE MONTH(p.date_start) = ".((int) $month);
$sql .= " AND YEAR(p.date_start) = ".((int) $year);
$sql .= " AND p.entity = ".$conf->entity;
$sql .= " ORDER BY p.employee_name ASC";

$resql = $db->query($sql);
$totals = ['brut' => 0, 'net' => 0, 'cnps_sal' => 0, 'cnps_pat' => 0, 'its' => 0, 'charges' => 0, 'transport' => 0];

if ($resql && $db->num_rows($resql) > 0) {
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent" style="font-size:0.85em;">';
    print '<tr class="liste_titre">';
    print '<td>N°</td><td>Matricule</td><td>Nom & Prénom</td><td>Cat/Éch</td>';
    print '<td class="right">Sal. base</td><td class="right">Primes</td>';
    print '<td class="right">Brut</td><td class="right">CNPS sal.</td>';
    print '<td class="right">ITS</td><td class="right">Retenues</td>';
    print '<td class="right">Net à payer</td><td class="right">Ch. pat.</td>';
    print '</tr>';

    $num = 0;
    while ($obj = $db->fetch_object($resql)) {
        $num++;
        $totalPrimes = $obj->prime_anciennete + $obj->prime_rendement + $obj->prime_technicite
            + $obj->prime_fonction + $obj->prime_responsabilite + $obj->prime_risque
            + $obj->prime_outillage + $obj->prime_salissure + $obj->prime_caisse
            + $obj->prime_assiduite + $obj->prime_panier + $obj->gratification;

        print '<tr class="oddeven">';
        print '<td>'.$num.'</td>';
        print '<td>'.$obj->matricule.'</td>';
        print '<td><a href="card.php?id='.$obj->rowid.'">'.$obj->employee_name.'</a></td>';
        print '<td>'.$obj->employee_category.'/'.$obj->employee_echelon.'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->salaire_base).'</td>';
        print '<td class="right">'.payrollci_format_amount($totalPrimes).'</td>';
        print '<td class="right"><b>'.payrollci_format_amount($obj->salaire_brut).'</b></td>';
        print '<td class="right">'.payrollci_format_amount($obj->cnps_retraite_sal).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->its_total).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_retenues_sal).'</td>';
        print '<td class="right" style="color:#27ae60;"><b>'.payrollci_format_amount($obj->net_a_payer).'</b></td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_charges_pat).'</td>';
        print '</tr>';

        $totals['brut'] += $obj->salaire_brut;
        $totals['net'] += $obj->net_a_payer;
        $totals['cnps_sal'] += $obj->cnps_retraite_sal;
        $totals['its'] += $obj->its_total;
        $totals['charges'] += $obj->total_charges_pat;
    }

    print '<tr class="liste_total">';
    print '<td colspan="4"><b>TOTAUX ('.$num.' bulletins)</b></td>';
    print '<td></td><td></td>';
    print '<td class="right"><b>'.payrollci_format_amount($totals['brut']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($totals['cnps_sal']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($totals['its']).'</b></td>';
    print '<td class="right"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($totals['net']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($totals['charges']).'</b></td>';
    print '</tr>';
    print '</table></div>';
} else {
    print '<div class="opacitymedium center">'.img_picto('', 'warning', 'class="pictofixedwidth"').'Aucun bulletin de paie pour '.$periodeLabel.'</div>';
}

llxFooter();
$db->close();
