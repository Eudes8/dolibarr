<?php
/* ============================================================================
 * PayrollCI v4 - Configuration admin
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
print dol_get_fiche_head($head, 'settings', 'PayrollCI', -1, 'object_payrollci@payrollci');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// Paramètres entreprise
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'company', 'class="pictofixedwidth"').'<b>Informations entreprise (apparaissent sur les bulletins)</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">N° CNPS de l\'entreprise</td><td>';
print '<input type="text" name="company_cnps" value="'.getDolGlobalString('PAYROLLCI_COMPANY_CNPS').'" size="30" class="flat"></td></tr>';
print '<tr class="oddeven"><td>N° CMU employeur</td><td>';
print '<input type="text" name="company_cmu" value="'.getDolGlobalString('PAYROLLCI_COMPANY_CMU').'" size="30" class="flat"></td></tr>';
print '<tr class="oddeven"><td>RCCM</td><td>';
print '<input type="text" name="company_rccm" value="'.getDolGlobalString('PAYROLLCI_COMPANY_RCCM').'" size="30" class="flat"></td></tr>';
print '<tr class="oddeven"><td>Convention collective appliquée</td><td>';
print '<input type="text" name="company_cc" value="'.getDolGlobalString('PAYROLLCI_COMPANY_CC', 'CCI - Convention Collective Interprofessionnelle').'" size="60" class="flat"></td></tr>';
print '</table><br>';

// Paramètres par défaut
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'setup', 'class="pictofixedwidth"').'<b>Paramètres par défaut</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Préfixe des références</td><td>';
print '<input type="text" name="ref_prefix" value="'.getDolGlobalString('PAYROLLCI_REF_PREFIX', 'BP').'" size="10" class="flat">';
print ' <em style="color:#888">Ex: BP → BP-000001</em></td></tr>';

$secteurs = PayrollCICalc::getSecteursActivite();
$defSec = getDolGlobalString('PAYROLLCI_DEFAULT_SECTEUR', 'commerce');
print '<tr class="oddeven"><td>Secteur d\'activité par défaut</td><td>';
print '<select name="secteur" class="flat">';
foreach ($secteurs as $k => $s) {
    $sel = ($k == $defSec) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$s['label'].' (AT: '.$s['taux'].'%)</option>';
}
print '</select></td></tr>';

$villes = PayrollCICalc::getVilles();
$defVille = getDolGlobalString('PAYROLLCI_DEFAULT_VILLE', 'abidjan');
print '<tr class="oddeven"><td>Ville par défaut</td><td>';
print '<select name="ville" class="flat">';
foreach ($villes as $k => $v) {
    $sel = ($k == $defVille) ? ' selected' : '';
    print '<option value="'.$k.'"'.$sel.'>'.$v['label'].' ('.number_format($v['plafond'], 0, ',', ' ').' F/mois)</option>';
}
print '</select></td></tr>';
print '</table><br>';

// Barèmes V4
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.img_picto('', 'security', 'class="pictofixedwidth"').'<b>Barèmes CNPS en vigueur (lecture seule)</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">CNPS Retraite</td><td>14% (6,3% salarié + 7,7% employeur)</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_RETRAITE, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CNPS PF + Maternité</td><td>5,75% employeur</td><td>Plafond: '.number_format(PayrollCICalc::CNPS_PLAFOND_PF, 0, ',', ' ').' F</td></tr>';
print '<tr class="oddeven"><td>CMU</td><td>500 F/mois par personne</td><td>Salarié + Employeur</td></tr>';
print '</table><br>';

// V4: Charges patronales fiscales
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.img_picto('', 'company', 'class="pictofixedwidth"').'<b>Charges fiscales patronales (Ordonnance 2023-719)</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Contribution employeur (local)</td><td>2,8% du brut imposable</td><td>Remplace l\'ancien IE 1,2%</td></tr>';
print '<tr class="oddeven"><td>Contribution employeur (expatrié)</td><td>12,0% du brut imposable</td><td>Salariés expatriés</td></tr>';
print '<tr class="oddeven"><td>FDFP/TA</td><td>0,4% masse salariale</td><td>Inchangé</td></tr>';
print '<tr class="oddeven"><td>FDFP/FPC</td><td>0,6% masse salariale</td><td>Inchangé</td></tr>';
print '<tr class="oddeven"><td>Transport exonéré</td><td>Abidjan: 30 000F | Bouaké: 24 000F | Autres: 20 000F</td><td></td></tr>';
print '</table><br>';

// V4: Barème IBS
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.img_picto('', 'tax', 'class="pictofixedwidth"').'<b>Barème IBS mensuel (CGI Art. 119 bis - Ordonnance n° 2023-719)</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Tranche 1</td><td>0 – 75 000 FCFA</td><td>0%</td></tr>';
print '<tr class="oddeven"><td>Tranche 2</td><td>75 001 – 240 000 FCFA</td><td>16%</td></tr>';
print '<tr class="oddeven"><td>Tranche 3</td><td>240 001 – 800 000 FCFA</td><td>21%</td></tr>';
print '<tr class="oddeven"><td>Tranche 4</td><td>800 001 – 2 400 000 FCFA</td><td>24%</td></tr>';
print '<tr class="oddeven"><td>Tranche 5</td><td>2 400 001 – 8 000 000 FCFA</td><td>28%</td></tr>';
print '<tr class="oddeven"><td>Tranche 6</td><td>Au-dessus de 8 000 000 FCFA</td><td>32%</td></tr>';
print '</table><br>';

// V4: RICF
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.img_picto('', 'receive', 'class="pictofixedwidth"').'<b>RICF - Réduction pour Charges de Famille (CGI Art. 120)</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Formule</td><td>RICF = 11 000 × (N − 1) par mois</td><td>N = nombre de parts fiscales (max 5)</td></tr>';
print '<tr class="oddeven"><td>Parts</td><td>Célibataire: 1 | Marié: 2 | +0,5/enfant | +0,5 veuf/divorcé avec enfant</td><td>Maximum 5 parts</td></tr>';
print '<tr class="oddeven"><td>Règle</td><td>Si RICF ≥ IBS → ITS = 0</td><td>L\'ITS ne peut pas être négatif</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.img_picto('', 'clock', 'class="pictofixedwidth"').'<b>Autres barèmes</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Prime d\'ancienneté</td><td>2% après 24 mois, +1%/an, max 25%</td><td>Sur salaire catégoriel</td></tr>';
print '<tr class="oddeven"><td>Heures supplémentaires</td><td>15% (41è-46è h) | 50% (>46h) | 75% (nuit/dim) | 100% (nuit+dim)</td><td></td></tr>';
print '</table>';

print '<br><div class="center"><input type="submit" class="button" value="Sauvegarder"></div>';
print '</form>';

print dol_get_fiche_end();
llxFooter();
