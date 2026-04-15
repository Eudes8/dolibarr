<?php
/* ============================================================================
 * PayrollCI v4 - État 301 (CDIR 301 - Déclaration Annuelle des Salaires)
 * Due avant le 30 mai (papier) ou 30 juin (en ligne)
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));

if (!($user->rights->payrollci->lire || $user->admin)) accessforbidden();

$year = GETPOST('year', 'int') ?: intval(date('Y')) - 1;

llxHeader('', 'État 301', '', '', 0, 0, '', '', '', 'mod-payrollci page-report');
print load_fiche_titre(img_picto('', 'tax', 'class="pictofixedwidth"').'État 301 - Déclaration Annuelle des Salaires '.$year, '', 'object_payrollci@payrollci');

// Navigation
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" class="marginbottom">';
print '<div class="center">';
print 'Année : <input type="number" name="year" value="'.$year.'" min="2020" max="2035" class="flat" size="6"> ';
print '<input type="submit" class="button small" value="Afficher">';
print '</div></form>';

// Données agrégées par employé
$sql = "SELECT p.employee_name, p.matricule, p.numero_cnps, p.situation_familiale, p.nombre_enfants, p.nombre_parts,";
$sql .= " SUM(p.salaire_brut) as total_brut, SUM(p.brut_imposable) as total_imposable,";
$sql .= " SUM(p.its_ibs) as total_ibs, SUM(p.its_ricf) as total_ricf, SUM(p.its_total) as total_its,";
$sql .= " SUM(p.cnps_retraite_sal) as total_cnps_sal, SUM(p.cnps_retraite_pat) as total_cnps_pat,";
$sql .= " SUM(p.contribution_employeur) as total_contrib,";
$sql .= " SUM(p.net_a_payer) as total_net, COUNT(*) as nb_bulletins,";
$sql .= " MIN(p.date_start) as premiere_paie, MAX(p.date_start) as derniere_paie";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE YEAR(p.date_start) = ".((int) $year)." AND p.entity = ".$conf->entity;
$sql .= " GROUP BY p.employee_name, p.matricule, p.numero_cnps, p.situation_familiale, p.nombre_enfants, p.nombre_parts";
$sql .= " ORDER BY p.employee_name ASC";

$resql = $db->query($sql);

// En-tête entreprise
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'company', 'class="pictofixedwidth"').'<b>SECTION A - Identification de l\'employeur</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Raison sociale</td><td><b>'.getDolGlobalString('MAIN_INFO_SOCIETE_NOM').'</b></td></tr>';
print '<tr class="oddeven"><td>N° CNPS</td><td>'.getDolGlobalString('PAYROLLCI_COMPANY_CNPS').'</td></tr>';
print '<tr class="oddeven"><td>RCCM</td><td>'.getDolGlobalString('PAYROLLCI_COMPANY_RCCM').'</td></tr>';
print '<tr class="oddeven"><td>Exercice fiscal</td><td>'.$year.'</td></tr>';
print '</table></div><br>';

// Section B : Déclarations individuelles
$grandTotals = ['brut' => 0, 'imposable' => 0, 'ibs' => 0, 'ricf' => 0, 'its' => 0, 'cnps_sal' => 0, 'contrib' => 0, 'net' => 0];

if ($resql && $db->num_rows($resql) > 0) {
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent" style="font-size:0.85em;">';
    print '<tr class="liste_titre"><td colspan="11">'.img_picto('', 'user', 'class="pictofixedwidth"').'<b>SECTION B - Déclarations individuelles</b></td></tr>';
    print '<tr class="liste_titre">';
    print '<td>Matricule</td><td>Nom</td><td>N° CNPS</td><td class="center">Parts</td><td class="center">Mois</td>';
    print '<td class="right">Brut annuel</td><td class="right">Base IBS</td>';
    print '<td class="right">IBS</td><td class="right">RICF</td><td class="right">ITS net</td>';
    print '<td class="right">Net versé</td>';
    print '</tr>';

    while ($obj = $db->fetch_object($resql)) {
        print '<tr class="oddeven">';
        print '<td>'.$obj->matricule.'</td>';
        print '<td>'.$obj->employee_name.'</td>';
        print '<td>'.$obj->numero_cnps.'</td>';
        print '<td class="center">'.$obj->nombre_parts.'</td>';
        print '<td class="center">'.$obj->nb_bulletins.'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_brut).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_imposable).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_ibs).'</td>';
        print '<td class="right" style="color:#27ae60;">'.payrollci_format_amount($obj->total_ricf).'</td>';
        print '<td class="right"><b>'.payrollci_format_amount($obj->total_its).'</b></td>';
        print '<td class="right">'.payrollci_format_amount($obj->total_net).'</td>';
        print '</tr>';

        $grandTotals['brut'] += $obj->total_brut;
        $grandTotals['imposable'] += $obj->total_imposable;
        $grandTotals['ibs'] += $obj->total_ibs;
        $grandTotals['ricf'] += $obj->total_ricf;
        $grandTotals['its'] += $obj->total_its;
        $grandTotals['net'] += $obj->total_net;
    }

    print '<tr class="liste_total">';
    print '<td colspan="5"><b>TOTAUX</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['brut']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['imposable']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['ibs']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['ricf']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['its']).'</b></td>';
    print '<td class="right"><b>'.payrollci_format_amount($grandTotals['net']).'</b></td>';
    print '</tr>';
    print '</table></div><br>';

    // Section C : Récapitulatif
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'chart', 'class="pictofixedwidth"').'<b>SECTION C - Récapitulatif / Vérification</b></td></tr>';
    print '<tr class="oddeven"><td class="titlefield">Masse salariale brute annuelle</td><td class="right"><b>'.payrollci_format_amount($grandTotals['brut']).' FCFA</b></td></tr>';
    print '<tr class="oddeven"><td>Base imposable totale</td><td class="right">'.payrollci_format_amount($grandTotals['imposable']).' FCFA</td></tr>';
    print '<tr class="oddeven"><td>Total IBS brut</td><td class="right">'.payrollci_format_amount($grandTotals['ibs']).' FCFA</td></tr>';
    print '<tr class="oddeven"><td>Total RICF</td><td class="right" style="color:#27ae60;">- '.payrollci_format_amount($grandTotals['ricf']).' FCFA</td></tr>';
    print '<tr class="oddeven" style="background-color:#fff3cd;"><td><b>Total ITS net versé au Trésor</b></td><td class="right"><b>'.payrollci_format_amount($grandTotals['its']).' FCFA</b></td></tr>';
    print '<tr class="oddeven"><td>Total net versé aux salariés</td><td class="right">'.payrollci_format_amount($grandTotals['net']).' FCFA</td></tr>';
    print '</table></div>';
} else {
    print '<div class="opacitymedium center">'.img_picto('', 'warning', 'class="pictofixedwidth"').'Aucun bulletin de paie pour l\'année '.$year.'</div>';
}

print '<br><div class="opacitymedium center" style="font-size:0.85em;">';
print img_picto('', 'info', 'class="pictofixedwidth"').'<em>État 301 (CDIR 301) — Délai de dépôt : 30 mai (papier) / 30 juin (en ligne) de l\'année suivante</em>';
print '</div>';

llxFooter();
$db->close();
