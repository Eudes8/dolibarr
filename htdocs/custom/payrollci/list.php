<?php
/* ============================================================================
 * PayrollCI - Liste des bulletins de paie
 * ============================================================================
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));

$action    = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset    = $limit * $page;

if (empty($sortfield)) $sortfield = 'p.date_start';
if (empty($sortorder)) $sortorder = 'DESC';

// Filtres
$search_ref       = GETPOST('search_ref', 'alpha');
$search_employee  = GETPOST('search_employee', 'alpha');
$search_month     = GETPOST('search_month', 'int');
$search_year      = GETPOST('search_year', 'int');
$search_status    = GETPOST('search_status', 'int');

// Reset filtres
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $search_ref = '';
    $search_employee = '';
    $search_month = '';
    $search_year = '';
    $search_status = -1;
}

// ==================== AFFICHAGE ====================
llxHeader('', 'Bulletins de Paie');

$form = new Form($db);

// Titre + bouton nouveau
$title = 'Bulletins de Paie - Côte d\'Ivoire';
$newurl = dol_buildpath('/payrollci/card.php', 1).'?action=create';
$newbutton = '<a class="butAction" href="'.$newurl.'">Nouveau bulletin</a>';
print load_fiche_titre($title, $newbutton, 'payrollci@payrollci');

// Requête SQL
$sql = "SELECT p.rowid, p.ref, p.employee_name, p.date_start, p.date_end,";
$sql .= " p.salaire_brut, p.salaire_net, p.net_a_payer, p.status, p.date_creation";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE p.entity = ".((int) $conf->entity);

if ($search_ref) {
    $sql .= " AND p.ref LIKE '%".$db->escape($search_ref)."%'";
}
if ($search_employee) {
    $sql .= " AND p.employee_name LIKE '%".$db->escape($search_employee)."%'";
}
if ($search_month > 0) {
    $sql .= " AND MONTH(p.date_start) = ".((int) $search_month);
}
if ($search_year > 0) {
    $sql .= " AND YEAR(p.date_start) = ".((int) $search_year);
}
if ($search_status >= 0) {
    $sql .= " AND p.status = ".((int) $search_status);
}

// Compteur total
$sqlcount = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as total FROM', $sql);
$resqlcount = $db->query($sqlcount);
$totalrows = 0;
if ($resqlcount) {
    $objcount = $db->fetch_object($resqlcount);
    $totalrows = $objcount->total;
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

// Formulaire de recherche
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<table class="noborder centpercent">';

// En-tête colonnes
print '<tr class="liste_titre">';
print_liste_field_titre('Référence', $_SERVER['PHP_SELF'], 'p.ref', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Employé', $_SERVER['PHP_SELF'], 'p.employee_name', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Période', $_SERVER['PHP_SELF'], 'p.date_start', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Salaire brut', $_SERVER['PHP_SELF'], 'p.salaire_brut', '', '', 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Net à payer', $_SERVER['PHP_SELF'], 'p.net_a_payer', '', '', 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Statut', $_SERVER['PHP_SELF'], 'p.status', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('');
print '</tr>';

// Ligne de filtres
print '<tr class="liste_titre">';
print '<td><input type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="10"></td>';
print '<td><input type="text" name="search_employee" value="'.dol_escape_htmltag($search_employee).'" size="20"></td>';
print '<td>';
$moisList = payrollci_get_mois();
print '<select name="search_month"><option value="">--</option>';
foreach ($moisList as $num => $nom) {
    $sel = ($search_month == $num) ? ' selected' : '';
    print '<option value="'.$num.'"'.$sel.'>'.$nom.'</option>';
}
print '</select>';
print ' <input type="number" name="search_year" value="'.dol_escape_htmltag($search_year).'" size="6" placeholder="Année">';
print '</td>';
print '<td></td><td></td>';
print '<td class="center"><select name="search_status">';
print '<option value="-1">--</option>';
print '<option value="0"'.($search_status === '0' ? ' selected' : '').'>Brouillon</option>';
print '<option value="1"'.($search_status == 1 ? ' selected' : '').'>Validé</option>';
print '</select></td>';
print '<td class="right">';
print '<button type="submit" class="liste_titre button_search" name="button_search" value="x"><span class="fa fa-search"></span></button>';
print ' <button type="submit" class="liste_titre button_removefilter" name="button_removefilter" value="x"><span class="fa fa-remove"></span></button>';
print '</td>';
print '</tr>';

// Données
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';

        // Référence (lien)
        print '<td><a href="'.dol_buildpath('/payrollci/card.php', 1).'?id='.$obj->rowid.'">'.$obj->ref.'</a></td>';

        // Employé
        print '<td>'.$obj->employee_name.'</td>';

        // Période
        $moisNum = intval(date('m', $db->jdate($obj->date_start)));
        $annee = date('Y', $db->jdate($obj->date_start));
        print '<td>'.($moisList[$moisNum] ?? $moisNum).' '.$annee.'</td>';

        // Salaire brut
        print '<td class="right">'.payrollci_format_amount($obj->salaire_brut).'</td>';

        // Net à payer
        print '<td class="right"><b>'.payrollci_format_amount($obj->net_a_payer).'</b></td>';

        // Statut
        if ($obj->status == 1) {
            $badge = '<span class="badge badge-status4">Validé</span>';
        } else {
            $badge = '<span class="badge badge-status0">Brouillon</span>';
        }
        print '<td class="center">'.$badge.'</td>';

        // Actions
        print '<td class="right">';
        print '<a href="'.dol_buildpath('/payrollci/card.php', 1).'?id='.$obj->rowid.'">'.img_picto('Voir', 'eye').'</a>';
        print '</td>';

        print '</tr>';
        $i++;
    }

    if ($num == 0) {
        print '<tr class="oddeven"><td colspan="7" class="center opacitymedium">Aucun bulletin de paie trouvé</td></tr>';
    }
} else {
    dol_print_error($db);
}

print '</table>';
print '</form>';

// Pagination
print_barre_liste('', $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, $totalrows, '', 0, '', '', $limit);

llxFooter();
$db->close();
