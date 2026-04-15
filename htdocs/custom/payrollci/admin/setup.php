<?php
/* ============================================================================
 * PayrollCI v3 - Configuration admin avec onglets Dolibarr
 * ============================================================================ */

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
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_CMU', GETPOST('company_cmu', 'alpha'));
    dolibarr_set_const($db, 'PAYROLLCI_REF_PREFIX', GETPOST('ref_prefix', 'alpha'));
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_RCCM', GETPOST('company_rccm', 'alpha'));
    dolibarr_set_const($db, 'PAYROLLCI_COMPANY_CC', GETPOST('company_cc', 'alpha'));
    setEventMessages('Configuration sauvegardée', null, 'mesgs');
}

llxHeader('', 'Configuration PayrollCI');

$head = payrollci_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', 'PayrollCI', -1, 'payrollci@payrollci');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// ── Paramètres entreprise ──
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Informations entreprise (apparaissent sur les bulletins)</b></td></tr>';

print '<tr class="oddeven"><td class="titlefield">N° CNPS de l\'entreprise</td><td>';
print '<input type="text" name="company_cnps" value="'.($conf->global->PAYROLLCI_COMPANY_CNPS ?? '').'" size="30" class="flat"></td></tr>';

print '<tr class="oddeven"><td>N° CMU employeur</td><td>';
print '<input type="text" name="company_cmu" value="'.($conf->global->PAYROLLCI_COMPANY_CMU ?? '').'" size="30" class="flat"></td></tr>';

print '<tr class="oddeven"><td>RCCM</td><td>';
print '<input type="text" name="company_rccm" value="'.($conf->global->PAYROLLCI_COMPANY_RCCM ?? '').'" size="30" class="flat"></td></tr>';

print '<tr class="oddeven"><td>Convention collective appliquée</td><td>';
print '<input type="text" name="company_cc" value="'.($conf->global->PAYROLLCI_COMPANY_CC ?? 'CCI - Convention Collective Interprofessionnelle').'" size="60" class="flat"></td></tr>';

print '</table><br>';

// ── Paramètres par défaut ──
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Paramètres par défaut (pré-remplis à la création)</b></td></tr>';

// Préfixe référence
print '<tr class="oddeven"><td class="titlefield">Préfixe des références</td><td>';
print '<input type="text" name="ref_prefix" value="'.($conf->global->PAYROLLCI_REF_PREFIX ?? 'BP').'" size="10" class="flat">';
print ' <em style="color:#888">Ex: BP → BP-000001, BULL → BULL-000001</em></td></tr>';

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
print '<tr class="oddeven"><td>Ville par défaut</td><td>';
print '<select name="ville" class="flat">';
foreach ($villes as $k => $v) {
    $sel = ($k == $defVille) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$v['label'].' ('.number_format($v['plafond'], 0, ',', ' ').' F/mois)</option>';
}
print '</select></td></tr>';

print '</table><br>';

// ── Barèmes de référence (lecture seule) ──
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3"><b>Barèmes en vigueur 2026 (lecture seule - codés dans le module)</b></td></tr>';

print '<tr class="oddeven"><td class="titlefield">CNPS Retraite</td><td>14% (6,3% salarié + 7,7% employeur)</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_RETRAITE, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CNPS PF + Maternité</td><td>5,75% employeur</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_PF, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CMU</td><td>500 F/mois par personne</td><td>Salarié + Employeur</td></tr>';
print '<tr class="oddeven"><td>Impôt Employeur (IE)</td><td>1,2% du brut imposable</td><td></td></tr>';
print '<tr class="oddeven"><td>FDFP/TA</td><td>0,4% masse salariale</td><td></td></tr>';
print '<tr class="oddeven"><td>FDFP/FPC</td><td>0,6% masse salariale</td><td></td></tr>';
print '<tr class="oddeven"><td>Transport exonéré</td><td>Abidjan: 30 000F | Bouaké: 24 000F | Autres: 20 000F</td><td></td></tr>';
print '<tr class="oddeven"><td>Prime d\'ancienneté</td><td>2% après 24 mois, +1%/an, max 25%</td><td>Sur salaire catégoriel</td></tr>';
print '<tr class="oddeven"><td>Heures supplémentaires</td><td>15% (41è-46è h) | 50% (>46h) | 75% (nuit/dim) | 100% (nuit+dim)</td><td></td></tr>';

// ITS barème
print '<tr class="liste_titre"><td colspan="3"><b>Barème IS / CN / IGR</b></td></tr>';
print '<tr class="oddeven"><td>IS (Impôt sur Salaires)</td><td>1,5% sur (brut imposable × 80%)</td><td>Minimum: 0</td></tr>';
print '<tr class="oddeven"><td>CN (Contribution Nationale)</td><td colspan="2">0-50 000: 0% | 50 001-130 000: 1,5% | 130 001-200 000: 5% | >200 000: 10%</td></tr>';
print '<tr class="oddeven"><td>IGR (quotient familial)</td><td colspan="2">0-25%: 0% | 25-45%: 10% | 45-100%: 15% | 100-250%: 20% | 250-500%: 25% | >500%: 35%</td></tr>';

print '</table>';

print '<br><div class="center"><input type="submit" class="button" value="Sauvegarder"></div>';
print '</form>';

print dol_get_fiche_end();
llxFooter();
