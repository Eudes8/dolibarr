<?php
/* ============================================================================
 * PayrollCI v3 - Page À propos
 * ============================================================================ */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("admin", "payrollci@payrollci"));

if (!$user->admin) accessforbidden();

llxHeader('', 'À propos de PayrollCI');

$head = payrollci_admin_prepare_head();
print dol_get_fiche_head($head, 'about', 'PayrollCI', -1, 'payrollci@payrollci');

print '<div class="fichecenter">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>PayrollCI - Module de Paie Côte d\'Ivoire</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Version</td><td><b>3.0.0</b> (Intégration Dolibarr complète)</td></tr>';
print '<tr class="oddeven"><td>Compatibilité Dolibarr</td><td>14.0+ / 15.x / 16.x / 17.x / 18.x / 19.x / 20.x</td></tr>';
print '<tr class="oddeven"><td>Licence</td><td>GPL v3.0</td></tr>';
print '<tr class="oddeven"><td>Législation</td><td>Droit du travail de Côte d\'Ivoire - 2026</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Sources juridiques</b></td></tr>';
print '<tr class="oddeven"><td class="titlefield">Convention Collective</td><td>CCI - Convention Collective Interprofessionnelle de Côte d\'Ivoire (2019, mise à jour)</td></tr>';
print '<tr class="oddeven"><td>Code du Travail</td><td>Loi n° 2015-532 du 20 juillet 2015 portant Code du Travail</td></tr>';
print '<tr class="oddeven"><td>Code Général des Impôts</td><td>CGI 2026 - Livre I, Titre II (ITS : IS, CN, IGR)</td></tr>';
print '<tr class="oddeven"><td>CNPS</td><td>Régimes obligatoires : Retraite, PF/Maternité, AT/MP, CMU</td></tr>';
print '<tr class="oddeven"><td>FDFP</td><td>Taxe d\'Apprentissage (0,4%) + Formation Professionnelle Continue (0,6%)</td></tr>';
print '<tr class="oddeven"><td>Impôt Employeur</td><td>IE à 1,2% du brut imposable (CGI)</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2"><b>Fonctionnalités V3</b></td></tr>';
$features = [
    'Calcul automatique complet (CNPS, ITS, charges patronales, transport exonéré)',
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
    'Génération PDF conforme Art. 46.2 CCI',
    '13 secteurs d\'activité avec taux AT différenciés',
    '7 villes avec plafonds de transport exonéré',
    'Quotient familial IGR (célibataire, marié, divorcé, veuf + enfants)',
    'Prime d\'ancienneté auto-calculée (2% après 24 mois, +1%/an, max 25%)',
    'Mode édition complet (modifier un brouillon, recalculer, enregistrer)',
    'Workflow : Brouillon → Validé → Remettre en brouillon',
    'Actions en masse (validation groupée)',
    'Configuration admin avec infos entreprise (CNPS, CMU, RCCM)',
];
foreach ($features as $i => $f) {
    print '<tr class="oddeven"><td class="titlefield">'.($i+1).'</td><td>'.$f.'</td></tr>';
}
print '</table>';

print '</div>';
print dol_get_fiche_end();

llxFooter();
