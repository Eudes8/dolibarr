<?php
/* PayrollCI v2 - Page de configuration */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("admin", "payrollci@payrollci"));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

if ($action == 'update') {
    dolibarr_set_const($db, 'PAYROLLCI_DEFAULT_SECTEUR', GETPOST('secteur', 'alpha'));
    dolibarr_set_const($db, 'PAYROLLCI_DEFAULT_VILLE', GETPOST('ville', 'alpha'));
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_CNPS', GETPOST('company_cnps', 'alpha'));
    setEventMessages('Configuration sauvegardée', null, 'mesgs');
}

llxHeader('', 'Configuration PayrollCI');
print load_fiche_titre('Configuration du module PayrollCI v2', '', 'payrollci@payrollci');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Paramètres par défaut</b></td></tr>';

// Secteur
$secteurs = PayrollCICalc::getSecteursActivite();
$defSec = $conf->global->PAYROLLCI_DEFAULT_SECTEUR ?? 'commerce';
print '<tr class="oddeven"><td>Secteur d\'activité par défaut</td><td>';
print '<select name="secteur" class="flat">';
foreach ($secteurs as $k => $s) {
    $sel = ($k == $defSec) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$s['label'].' (AT: '.$s['taux'].'%)</option>';
}
print '</select></td></tr>';

// Ville
$villes = PayrollCICalc::getVilles();
$defVille = $conf->global->PAYROLLCI_DEFAULT_VILLE ?? 'abidjan';
print '<tr class="oddeven"><td>Ville par défaut (transport exonéré)</td><td>';
print '<select name="ville" class="flat">';
foreach ($villes as $k => $v) {
    $sel = ($k == $defVille) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$v['label'].' ('.number_format($v['plafond'], 0, ',', ' ').' F/mois)</option>';
}
print '</select></td></tr>';

// CNPS entreprise
print '<tr class="oddeven"><td>N° CNPS de l\'entreprise</td><td>';
print '<input type="text" name="company_cnps" value="'.($conf->global->PAYROLLCI_COMPANY_CNPS ?? '').'" size="30" class="flat">';
print '</td></tr>';

print '</table>';

// ── Barèmes de référence ──
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3"><b>Barèmes en vigueur (2026 - lecture seule)</b></td></tr>';
print '<tr class="oddeven"><td>CNPS Retraite</td><td>14% (6,3% salarié + 7,7% employeur)</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_RETRAITE, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CNPS PF + Maternité</td><td>5,75% employeur</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_PF, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CMU</td><td>500 F/mois chacun</td><td></td></tr>';
print '<tr class="oddeven"><td>Impôt Employeur (IE)</td><td>1,2% du brut imposable</td><td></td></tr>';
print '<tr class="oddeven"><td>FDFP/TA</td><td>0,4% masse salariale</td><td></td></tr>';
print '<tr class="oddeven"><td>FDFP/FPC</td><td>0,6% masse salariale</td><td></td></tr>';
print '<tr class="oddeven"><td>Transport exonéré</td><td>Abidjan: 30 000 F | Bouaké: 24 000 F | Autres: 20 000 F</td><td></td></tr>';
print '<tr class="oddeven"><td>Prime d\'ancienneté</td><td>2% après 24 mois, +1%/an, max 25%</td><td>Sur salaire catégoriel</td></tr>';
print '<tr class="oddeven"><td>Heures supplémentaires</td><td>15% (41è-46è h) | 50% (>46h) | 75% (nuit/dim) | 100% (nuit+dim)</td><td></td></tr>';
print '</table>';

print '<br><div class="center"><input type="submit" class="button" value="Sauvegarder"></div>';
print '</form>';

llxFooter();
