<?php
/* ============================================================================
 * PayrollCI - Page de configuration du module
 * ============================================================================
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/payrollci/lib/payrollci.lib.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');

$langs->loadLangs(array("admin", "payrollci@payrollci"));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

// Sauvegarder les paramètres
if ($action == 'update') {
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_NAME', GETPOST('company_name', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_ADDRESS', GETPOST('company_address', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_CNPS', GETPOST('company_cnps', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_CC', GETPOST('company_cc', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PAYROLLCI_DEFAULT_SECTEUR', GETPOST('default_secteur', 'alpha'), 'chaine', 0, '', $conf->entity);

    setEventMessages('Configuration enregistrée', null, 'mesgs');
}

// Affichage
llxHeader('', 'Configuration PayrollCI');

$head = payrollciAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', 'PayrollCI', -1, 'payrollci@payrollci');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// === Entreprise ===
print load_fiche_titre('Informations Entreprise', '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Paramètre</td>';
print '<td>Valeur</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Raison sociale</td>';
print '<td><input type="text" name="company_name" value="'.dol_escape_htmltag($conf->global->PAYROLLCI_COMPANY_NAME).'" size="60"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Adresse</td>';
print '<td><input type="text" name="company_address" value="'.dol_escape_htmltag($conf->global->PAYROLLCI_COMPANY_ADDRESS).'" size="60"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>N° CNPS Employeur</td>';
print '<td><input type="text" name="company_cnps" value="'.dol_escape_htmltag($conf->global->PAYROLLCI_COMPANY_CNPS).'" size="30"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>N° RCCM</td>';
print '<td><input type="text" name="company_cc" value="'.dol_escape_htmltag($conf->global->PAYROLLCI_COMPANY_CC).'" size="30"></td>';
print '</tr>';

$secteurs = PayrollCICalc::getSecteursActivite();
print '<tr class="oddeven">';
print '<td>Secteur d\'activité par défaut</td>';
print '<td><select name="default_secteur">';
foreach ($secteurs as $key => $sect) {
    $selected = ($conf->global->PAYROLLCI_DEFAULT_SECTEUR == $key) ? ' selected' : '';
    print '<option value="'.$key.'"'.$selected.'>'.$sect['label'].' (AT: '.$sect['taux'].'%)</option>';
}
print '</select></td>';
print '</tr>';

print '</table>';

// === Taux en vigueur (informatif) ===
print '<br>';
print load_fiche_titre('Taux en vigueur - Côte d\'Ivoire 2026 (informatifs)', '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Cotisation / Impôt</td>';
print '<td>Taux / Formule</td>';
print '<td>Plafond</td>';
print '</tr>';

print '<tr class="oddeven"><td><b>CNPS - Retraite</b></td>';
print '<td>14% (6,3% salarié + 7,7% employeur)</td>';
print '<td>'.number_format(PayrollCICalc::CNPS_PLAFOND_RETRAITE, 0, ',', ' ').' FCFA/mois</td></tr>';

print '<tr class="oddeven"><td><b>CNPS - Prestations familiales</b></td>';
print '<td>5,75% employeur (dont 0,75% maternité)</td>';
print '<td>'.number_format(PayrollCICalc::CNPS_PLAFOND_PF, 0, ',', ' ').' FCFA/mois</td></tr>';

print '<tr class="oddeven"><td><b>CNPS - Accidents du travail</b></td>';
print '<td>2% à 5% employeur (selon secteur)</td>';
print '<td>'.number_format(PayrollCICalc::CNPS_PLAFOND_PF, 0, ',', ' ').' FCFA/mois</td></tr>';

print '<tr class="oddeven"><td><b>CMU</b></td>';
print '<td>500 FCFA/mois salarié + 500 FCFA/mois employeur</td>';
print '<td>Forfait</td></tr>';

print '<tr class="oddeven"><td><b>IS (Impôt sur Salaires)</b></td>';
print '<td>1,5% sur 80% du salaire brut</td>';
print '<td>-</td></tr>';

print '<tr class="oddeven"><td><b>CN (Contribution Nationale)</b></td>';
print '<td>0-50K: 0% / 50-130K: 1,5% / 130-200K: 5% / >200K: 10%</td>';
print '<td>Sur 80% du brut</td></tr>';

print '<tr class="oddeven"><td><b>IGR (Impôt Général sur le Revenu)</b></td>';
print '<td>Barème progressif 8 tranches (0% à 60%) avec quotient familial</td>';
print '<td>1 à 5 parts</td></tr>';

print '<tr class="oddeven"><td><b>SMIG</b></td>';
print '<td>'.number_format(PayrollCICalc::SMIG, 0, ',', ' ').' FCFA/mois (40h/semaine)</td>';
print '<td>Depuis 01/01/2023</td></tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="Enregistrer">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
