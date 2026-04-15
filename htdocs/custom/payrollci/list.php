<?php
/* PayrollCI v2 - Liste des bulletins */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci"));
$form = new Form($db);

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'p.date_start';
$sortorder = GETPOST('sortorder', 'aZ09') ?: 'DESC';
$limit = GETPOST('limit', 'int') ?: 25;
$page = GETPOST('page', 'int') ?: 0;
$offset = $limit * $page;

llxHeader('', 'Bulletins de Paie');
print load_fiche_titre('Bulletins de Paie', '<a class="butAction" href="card.php?action=create">Nouveau bulletin</a>', 'payrollci@payrollci');

$sql = "SELECT p.rowid, p.ref, p.employee_name, p.date_start, p.salaire_brut, p.net_a_payer, p.status, p.ville";
$sql .= " FROM ".MAIN_DB_PREFIX."payrollci_payslip as p";
$sql .= " WHERE p.entity = ".$conf->entity;
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print_liste_field_titre('Réf', $_SERVER['PHP_SELF'], 'p.ref', '', '', '', $sortfield, $sortorder);
    print_liste_field_titre('Employé', $_SERVER['PHP_SELF'], 'p.employee_name', '', '', '', $sortfield, $sortorder);
    print_liste_field_titre('Période', $_SERVER['PHP_SELF'], 'p.date_start', '', '', '', $sortfield, $sortorder);
    print_liste_field_titre('Ville', $_SERVER['PHP_SELF'], 'p.ville');
    print_liste_field_titre('Brut', $_SERVER['PHP_SELF'], 'p.salaire_brut', '', '', 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre('Net à payer', $_SERVER['PHP_SELF'], 'p.net_a_payer', '', '', 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre('Statut', $_SERVER['PHP_SELF'], 'p.status', '', '', 'class="center"', $sortfield, $sortorder);
    print '</tr>';

    $moisList = payrollci_get_mois();
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        print '<tr class="oddeven">';
        print '<td><a href="card.php?id='.$obj->rowid.'">'.$obj->ref.'</a></td>';
        print '<td>'.$obj->employee_name.'</td>';
        $mois = intval(date('m', $db->jdate($obj->date_start)));
        print '<td>'.($moisList[$mois] ?? '').' '.date('Y', $db->jdate($obj->date_start)).'</td>';
        print '<td>'.ucfirst($obj->ville).'</td>';
        print '<td class="right">'.payrollci_format_amount($obj->salaire_brut).'</td>';
        print '<td class="right"><b>'.payrollci_format_amount($obj->net_a_payer).'</b></td>';
        $badge = $obj->status == 1 ? '<span class="badge badge-status4">Validé</span>' : '<span class="badge badge-status0">Brouillon</span>';
        print '<td class="center">'.$badge.'</td>';
        print '</tr>';
        $i++;
    }
    if ($num == 0) print '<tr class="oddeven"><td colspan="7" class="opacitymedium center">Aucun bulletin de paie</td></tr>';
    print '</table>';
} else {
    dol_print_error($db);
}

llxFooter();
$db->close();
