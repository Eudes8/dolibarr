<?php
/* ============================================================================
 * PayrollCI v4 - Page À propos
 * ============================================================================ */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("admin", "payrollci@payrollci"));

if (!$user->admin) accessforbidden();

llxHeader('', 'À propos de PayrollCI');

$head = payrollci_admin_prepare_head();
print dol_get_fiche_head($head, 'about', 'PayrollCI', -1, 'object_payrollci@payrollci');

print '<div class="fichecenter">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'info', 'class="pictofixedwidth"').'<b>PayrollCI - Module de Paie Côte d\'Ivoire</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Version</td><td><b>4.0.0</b> (Réforme ITS 2024 - IBS/RICF)</td></tr>';
print '<tr class="oddeven"><td>Compatibilité Dolibarr</td><td>14.0+ / 15.x / 16.x / 17.x / 18.x / 19.x / 20.x</td></tr>';
print '<tr class="oddeven"><td>Licence</td><td>GPL v3.0</td></tr>';
print '<tr class="oddeven"><td>Législation</td><td>Droit du travail de Côte d\'Ivoire - 2024/2026</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'bookmark', 'class="pictofixedwidth"').'<b>Sources juridiques</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Convention Collective</td><td>CCI - Convention Collective Interprofessionnelle de Côte d\'Ivoire (2019, mise à jour)</td></tr>';
print '<tr class="oddeven"><td>Code du Travail</td><td>Loi n° 2015-532 du 20 juillet 2015 portant Code du Travail</td></tr>';
print '<tr class="oddeven"><td>Ordonnance ITS</td><td>Ordonnance n° 2023-719 du 13 septembre 2023 portant rationalisation des ITS</td></tr>';
print '<tr class="oddeven"><td>Code Général des Impôts</td><td>CGI Art. 119 bis (barème IBS) + Art. 120 (RICF)</td></tr>';
print '<tr class="oddeven"><td>CNPS</td><td>Régimes obligatoires : Retraite, PF/Maternité, AT/MP, CMU</td></tr>';
print '<tr class="oddeven"><td>FDFP</td><td>Taxe d\'Apprentissage (0,4%) + Formation Professionnelle Continue (0,6%)</td></tr>';
print '<tr class="oddeven"><td>Contribution Employeur</td><td>2,8% local / 12% expatrié (remplace ancien IE 1,2%)</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'star', 'class="pictofixedwidth"').'<b>Nouveautés V4</b></td></tr>';
$news = [
    'Nouveau barème IBS à 6 tranches (0% à 32%) - Ordonnance n° 2023-719',
    'RICF (Réduction pour Charges de Famille) remplace IS+CN+IGR',
    'Date d\'embauche avec calcul automatique de l\'ancienneté',
    'Support des salariés expatriés (contribution employeur 12%)',
    'PDF style Sage Paie (7 colonnes, codes, sections numérotées)',
    'Module États/Rapports : Livre de paie, État 301, Journal de paie',
    'Icônes Dolibarr professionnelles (plus d\'emojis)',
    'Contribution employeur 2,8%/12% (remplace IE 1,2%)',
];
foreach ($news as $i => $n) {
    print '<tr class="oddeven"><td class="titlefield" style="color:#27ae60;"><b>NOUVEAU</b></td><td>'.$n.'</td></tr>';
}
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.img_picto('', 'list', 'class="pictofixedwidth"').'<b>Fonctionnalités complètes</b></td></tr>';
$features = [
    'Calcul automatique complet (CNPS, ITS/IBS/RICF, charges patronales, transport exonéré)',
    'Tous les éléments de rémunération CCI (12 primes, 6 indemnités, 5 avantages en nature, 4 types HS)',
    'Intégration complète Dolibarr (onglets, bandeau, permissions, menus HRM)',
    'Onglet "Bulletins de paie" sur les fiches utilisateurs/employés',
    'Gestion documentaire (PDF, fichiers joints, historique)',
    'Notes publiques et privées sur chaque bulletin',
    'Événements/agenda liés aux bulletins',
    'Objets liés (tiers, contrats, projets)',
    'Support des extrafields (champs personnalisés)',
    'Tableau de bord avec statistiques mensuelles et annuelles',
    'Liste avec filtres avancés, tri, pagination, résumé masse salariale',
    'Génération PDF conforme Art. 46.2 CCI — Style Sage Paie',
    '13 secteurs d\'activité avec taux AT différenciés',
    '7 villes avec plafonds de transport exonéré',
    'Quotient familial RICF (célibataire, marié, divorcé, veuf + enfants, max 5 parts)',
    'Prime d\'ancienneté auto-calculée depuis la date d\'embauche',
    'Mode édition complet (modifier un brouillon, recalculer, enregistrer)',
    'Workflow : Brouillon → Validé → Remettre en brouillon',
    'Actions en masse (validation groupée)',
    'Configuration admin avec infos entreprise (CNPS, CMU, RCCM)',
    'États : Livre de paie mensuel, État 301 (déclaration annuelle), Journal de paie',
];
foreach ($features as $i => $feat) {
    print '<tr class="oddeven"><td class="titlefield">'.($i+1).'</td><td>'.$feat.'</td></tr>';
}
print '</table>';

print '</div>';
print dol_get_fiche_end();
llxFooter();
