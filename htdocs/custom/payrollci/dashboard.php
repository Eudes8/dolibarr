<?php
/* ============================================================================
 * PayrollCI v3 - Tableau de bord Paie
 * Statistiques, graphiques, résumé masse salariale
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));

if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

$year = GETPOST('year', 'int') ?: date('Y');

llxHeader('', 'Tableau de bord Paie', '', '', 0, 0, '', '', '', 'mod-payrollci page-dashboard');
print load_fiche_titre('Tableau de bord Paie - '.$year, '', 'payrollci@payrollci');

// Navigation année
print '<div class="center marginbottom">';
print '<a class="button" href="'.$_SERVER['PHP_SELF'].'?year='.($year-1).'">&laquo; '.($year-1).'</a>';
print ' &nbsp; <b>'.$year.'</b> &nbsp; ';
if ($year < date('Y')) print '<a class="button" href="'.$_SERVER['PHP_SELF'].'?year='.($year+1).'">'.($year+1).' &raquo;</a>';
print '</div><br>';

$moisList = payrollci_get_mois();

// ── Stats par mois ──
$sql = "SELECT MONTH(p.date_start) as mois, COUNT(*) as nb,";
$sql .= " SUM(p.salaire_brut) as total_brut, SUM(p.net_a_payer) as total_net,";
$sql .= " SUM(p.total_charges_pat) as total_pat, SUM(p.total_retenues_sal) as total_ret,";
$sql .= " SUM(p.its_total) as total_its, SUM(p.cnps_retraite_sal) as total_cnps_sal";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE YEAR(p.date_start) = ".((int) $year);
$sql .= " AND p.entity = ".$conf->entity;
$sql .= " GROUP BY MONTH(p.date_start)";
$sql .= " ORDER BY mois ASC";

$resql = $db->query($sql);
$stats = array();
$grandTotal = ['nb' => 0, 'brut' => 0, 'net' => 0, 'pat' => 0, 'ret' => 0, 'its' => 0, 'cnps' => 0];

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $stats[$obj->mois] = $obj;
        $grandTotal['nb']   += $obj->nb;
        $grandTotal['brut'] += $obj->total_brut;
        $grandTotal['net']  += $obj->total_net;
        $grandTotal['pat']  += $obj->total_pat;
        $grandTotal['ret']  += $obj->total_ret;
        $grandTotal['its']  += $obj->total_its;
        $grandTotal['cnps'] += $obj->total_cnps_sal;
    }
}

// ── Indicateurs clés ──
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Indicateurs annuels '.$year.'</b></td></tr>';
print '<tr class="oddeven"><td>Nombre total de bulletins</td><td class="right"><b>'.$grandTotal['nb'].'</b></td></tr>';
print '<tr class="oddeven"><td>Masse salariale brute</td><td class="right"><b>'.payrollci_format_amount($grandTotal['brut']).' FCFA</b></td></tr>';
print '<tr class="oddeven"><td>Total net versé aux salariés</td><td class="right" style="color:#27ae60;"><b>'.payrollci_format_amount($grandTotal['net']).' FCFA</b></td></tr>';
print '<tr class="oddeven"><td>Total charges patronales</td><td class="right">'.payrollci_format_amount($grandTotal['pat']).' FCFA</td></tr>';
$coutTotal = $grandTotal['brut'] + $grandTotal['pat'];
print '<tr class="oddeven" style="background-color:#ffeaea;"><td><b>Coût total employeur</b></td><td class="right" style="color:#c0392b;"><b>'.payrollci_format_amount($coutTotal).' FCFA</b></td></tr>';
print '<tr class="oddeven"><td>Total retenues salariales (CNPS + ITS)</td><td class="right">'.payrollci_format_amount($grandTotal['ret']).' FCFA</td></tr>';
print '<tr class="oddeven"><td>&nbsp;&nbsp;dont ITS</td><td class="right">'.payrollci_format_amount($grandTotal['its']).' FCFA</td></tr>';
print '<tr class="oddeven"><td>&nbsp;&nbsp;dont CNPS salarié (retraite)</td><td class="right">'.payrollci_format_amount($grandTotal['cnps']).' FCFA</td></tr>';
print '</table></div><br>';

// ── Tableau mensuel détaillé ──
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Mois</td><td class="center">Bulletins</td>';
print '<td class="right">Masse brute</td><td class="right">Retenues sal.</td>';
print '<td class="right">Net versé</td>';
print '<td class="right">Charges pat.</td>';
print '<td class="right">Coût employeur</td></tr>';

for ($m = 1; $m <= 12; $m++) {
    $s = $stats[$m] ?? null;
    print '<tr class="oddeven">';
    print '<td><a href="list.php?search_month='.$m.'&search_year='.$year.'">'.$moisList[$m].'</a></td>';

    if ($s) {
        $cout = $s->total_brut + $s->total_pat;
        print '<td class="center">'.$s->nb.'</td>';
        print '<td class="right">'.payrollci_format_amount($s->total_brut).'</td>';
        print '<td class="right">'.payrollci_format_amount($s->total_ret).'</td>';
        print '<td class="right" style="color:#27ae60"><b>'.payrollci_format_amount($s->total_net).'</b></td>';
        print '<td class="right">'.payrollci_format_amount($s->total_pat).'</td>';
        print '<td class="right" style="color:#c0392b">'.payrollci_format_amount($cout).'</td>';
    } else {
        print '<td class="center opacitymedium">-</td>';
        print '<td class="right opacitymedium">-</td><td class="right opacitymedium">-</td>';
        print '<td class="right opacitymedium">-</td><td class="right opacitymedium">-</td>';
        print '<td class="right opacitymedium">-</td>';
    }
    print '</tr>';
}

// Ligne total
print '<tr class="liste_total">';
print '<td><b>TOTAL '.$year.'</b></td>';
print '<td class="center"><b>'.$grandTotal['nb'].'</b></td>';
print '<td class="right"><b>'.payrollci_format_amount($grandTotal['brut']).'</b></td>';
print '<td class="right"><b>'.payrollci_format_amount($grandTotal['ret']).'</b></td>';
print '<td class="right"><b>'.payrollci_format_amount($grandTotal['net']).'</b></td>';
print '<td class="right"><b>'.payrollci_format_amount($grandTotal['pat']).'</b></td>';
print '<td class="right"><b>'.payrollci_format_amount($coutTotal).'</b></td>';
print '</tr>';

print '</table></div>';

// ── Top 5 employés (salaire brut annuel) ──
print '<br>';
$sqlTop = "SELECT p.employee_name, SUM(p.salaire_brut) as total_brut, SUM(p.net_a_payer) as total_net, COUNT(*) as nb";
$sqlTop .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sqlTop .= " WHERE YEAR(p.date_start) = ".((int) $year)." AND p.entity = ".$conf->entity;
$sqlTop .= " GROUP BY p.employee_name ORDER BY total_brut DESC LIMIT 10";
$resTops = $db->query($sqlTop);

if ($resTops && $db->num_rows($resTops) > 0) {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="4"><b>Top employés par masse salariale brute '.$year.'</b></td></tr>';
    print '<tr class="liste_titre"><td>Employé</td><td class="center">Bulletins</td><td class="right">Brut annuel</td><td class="right">Net annuel</td></tr>';
    while ($obj = $db->fetch_object($resTops)) {
        print '<tr class="oddeven">';
        print '<td>'.$obj->employee_name.'</td>';
        print '<td class="center">'.$obj->nb.'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_brut).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_net).'</td>';
        print '</tr>';
    }
    print '</table></div>';
}

llxFooter();
$db->close();
