<?php
/* ============================================================================
 * PayrollCI v4 - Liste des bulletins de paie - Intégration Dolibarr
 * Filtres, pagination, tri, recherche, export
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies"));

$action    = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$toselect  = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'payrollcilist';

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'p.date_start';
$sortorder = GETPOST('sortorder', 'aZ09') ?: 'DESC';
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');
if ($page < 0) $page = 0;
$offset    = $limit * $page;

$search_ref      = GETPOST('search_ref', 'alpha');
$search_employee = GETPOST('search_employee', 'alpha');
$search_status   = GETPOST('search_status', 'int');
$search_ville    = GETPOST('search_ville', 'alpha');
$search_month    = GETPOST('search_month', 'int');
$search_year     = GETPOST('search_year', 'int');

$form = new Form($db);
$formother = new FormOther($db);
$objectstatic = new Payslip($db);

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $search_ref = $search_employee = $search_ville = '';
    $search_status = -1;
    $search_month = $search_year = 0;
}

if ($massaction == 'validate' && !empty($toselect)) {
    foreach ($toselect as $sid) {
        $obj = new Payslip($db);
        $obj->fetch($sid);
        if ($obj->status == Payslip::STATUS_DRAFT) $obj->validate($user);
    }
    setEventMessages('Bulletins validés', null, 'mesgs');
}

$sql = "SELECT p.rowid, p.ref, p.employee_name, p.date_start, p.salaire_brut, p.net_a_payer, p.status, p.ville, p.fk_user,";
$sql .= " p.date_creation, p.total_charges_pat, p.brut_imposable";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE p.entity = ".$conf->entity;

if (!empty($search_ref))      $sql .= natural_search('p.ref', $search_ref);
if (!empty($search_employee)) $sql .= natural_search('p.employee_name', $search_employee);
if ($search_status >= 0 && $search_status !== '') $sql .= " AND p.status = ".((int) $search_status);
if (!empty($search_ville))    $sql .= " AND p.ville = '".$db->escape($search_ville)."'";
if ($search_month > 0)        $sql .= " AND MONTH(p.date_start) = ".((int) $search_month);
if ($search_year > 0)         $sql .= " AND YEAR(p.date_start) = ".((int) $search_year);

$sqlCount = preg_replace('/^SELECT .+ FROM/', 'SELECT COUNT(*) as total FROM', $sql);
$resqlCount = $db->query($sqlCount);
$totalCount = 0;
if ($resqlCount) { $objCount = $db->fetch_object($resqlCount); $totalCount = $objCount->total; }

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$sqlTotals = "SELECT SUM(p.salaire_brut) as total_brut, SUM(p.net_a_payer) as total_net, SUM(p.total_charges_pat) as total_pat, COUNT(*) as nb";
$sqlTotals .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p WHERE p.entity = ".$conf->entity;
if ($search_month > 0) $sqlTotals .= " AND MONTH(p.date_start) = ".((int) $search_month);
if ($search_year > 0) $sqlTotals .= " AND YEAR(p.date_start) = ".((int) $search_year);
$resTotals = $db->query($sqlTotals);
$totals = $db->fetch_object($resTotals);

llxHeader('', 'Bulletins de Paie', '', '', 0, 0, '', '', '', 'mod-payrollci page-list');

$newcardbutton = '';
if ($user->rights->payrollci->creer || $user->admin) {
    $newcardbutton = dolGetButtonTitle('Nouveau bulletin', '', 'fa fa-plus-circle', 'card.php?action=create');
}

print load_fiche_titre('Bulletins de Paie', $newcardbutton, 'object_payrollci@payrollci');

if ($search_month > 0 || $search_year > 0) {
    $moisList = payrollci_get_mois();
    $periodLabel = ($search_month > 0 ? ($moisList[$search_month] ?? '') : 'Toute l\'année').' '.($search_year > 0 ? $search_year : '');
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'chart', 'class="pictofixedwidth"').'<b>Résumé - '.$periodLabel.'</b></td></tr>';
    print '<tr class="oddeven"><td>Nombre de bulletins</td><td class="right"><b>'.$totals->nb.'</b></td>';
    print '<td>Masse salariale brute</td><td class="right"><b>'.payrollci_format_amount($totals->total_brut).' FCFA</b></td></tr>';
    print '<tr class="oddeven"><td>Total net à payer</td><td class="right"><b>'.payrollci_format_amount($totals->total_net).' FCFA</b></td>';
    print '<td>Total charges patronales</td><td class="right"><b>'.payrollci_format_amount($totals->total_pat).' FCFA</b></td></tr>';
    $coutTotal = $totals->total_brut + $totals->total_pat;
    print '<tr class="oddeven"><td colspan="2"></td>';
    print '<td><b>Coût total employeur</b></td><td class="right" style="color:#c0392b;"><b>'.payrollci_format_amount($coutTotal).' FCFA</b></td></tr>';
    print '</table></div><br>';
}

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formfilter">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable noborder centpercent liste">';

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="12" class="flat"></td>';
print '<td class="liste_titre"><input type="text" name="search_employee" value="'.dol_escape_htmltag($search_employee).'" size="20" class="flat"></td>';

print '<td class="liste_titre center">';
print '<select name="search_month" class="flat maxwidth75">';
print '<option value="0">--</option>';
$moisList = payrollci_get_mois();
foreach ($moisList as $num => $nom) {
    $sel = ($num == $search_month) ? ' selected' : '';
    print '<option value="'.$num.'"'.$sel.'>'.substr($nom, 0, 3).'</option>';
}
print '</select>';
print ' <input type="number" name="search_year" value="'.($search_year > 0 ? $search_year : '').'" placeholder="Année" class="flat maxwidth75" min="2020" max="2035">';
print '</td>';

$villes = PayrollCICalc::getVilles();
print '<td class="liste_titre center"><select name="search_ville" class="flat">';
print '<option value="">--</option>';
foreach ($villes as $k => $v) {
    $sel = ($k == $search_ville) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$v['label'].'</option>';
}
print '</select></td>';

print '<td class="liste_titre right"></td>';
print '<td class="liste_titre right"></td>';

print '<td class="liste_titre center"><select name="search_status" class="flat">';
print '<option value="-1">--</option>';
print '<option value="0"'.($search_status === '0' || $search_status === 0 ? ' selected' : '').'>Brouillon</option>';
print '<option value="1"'.($search_status == 1 ? ' selected' : '').'>Validé</option>';
print '</select></td>';

print '<td class="liste_titre center">';
print '<input type="image" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" class="liste_titre" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print ' <input type="image" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"), 'searchclear.png', '', '', 1).'" class="liste_titre" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre('Réf', $_SERVER['PHP_SELF'], 'p.ref', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Employé', $_SERVER['PHP_SELF'], 'p.employee_name', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Période', $_SERVER['PHP_SELF'], 'p.date_start', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Ville', $_SERVER['PHP_SELF'], 'p.ville', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Brut', $_SERVER['PHP_SELF'], 'p.salaire_brut', '', '', 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Net à payer', $_SERVER['PHP_SELF'], 'p.net_a_payer', '', '', 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Statut', $_SERVER['PHP_SELF'], 'p.status', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER['PHP_SELF'], '', '', '', 'class="center" width="60"');
print '</tr>';

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        $objectstatic->id = $obj->rowid;
        $objectstatic->ref = $obj->ref;
        $objectstatic->employee_name = $obj->employee_name;
        $objectstatic->net_a_payer = $obj->net_a_payer;

        print '<tr class="oddeven">';
        print '<td>'.$objectstatic->getNomUrl(1).'</td>';
        print '<td>'.img_picto('', 'user', 'class="pictofixedwidth"').$obj->employee_name.'</td>';

        $mois = intval(date('m', $db->jdate($obj->date_start)));
        print '<td class="center">'.($moisList[$mois] ?? '').' '.date('Y', $db->jdate($obj->date_start)).'</td>';

        $villeLabel = $villes[$obj->ville]['label'] ?? ucfirst($obj->ville);
        print '<td class="center">'.$villeLabel.'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->salaire_brut).'</td>';
        print '<td class="right"><b>'.payrollci_format_amount($obj->net_a_payer).'</b></td>';
        print '<td class="center">'.$objectstatic->LibStatut($obj->status).'</td>';

        print '<td class="center nowraponall">';
        print '<a href="card.php?id='.$obj->rowid.'" title="Voir">'.img_picto('Voir', 'eye').'</a>';
        if ($obj->status == 0 && ($user->rights->payrollci->creer || $user->admin)) {
            print ' <a href="card.php?id='.$obj->rowid.'&action=edit&token='.newToken().'" title="Modifier">'.img_picto('Modifier', 'edit').'</a>';
        }
        print '</td>';
        print '</tr>';
        $i++;
    }

    if ($num == 0) {
        print '<tr class="oddeven"><td colspan="8" class="opacitymedium center">Aucun bulletin de paie trouvé</td></tr>';
    }
    print '</table></div>';
    print_barre_liste('', $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, $totalCount, '', 0, '', '', $limit);
} else {
    dol_print_error($db);
}

print '</form>';
llxFooter();
$db->close();
