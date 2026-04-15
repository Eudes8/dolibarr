<?php
/* ============================================================================
 * PayrollCI v2 - Fiche bulletin de paie (tous éléments CI)
 * ============================================================================
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');
dol_include_once('/payrollci/core/modules/payrollci/doc/pdf_bulletinpaie.modules.php');

$langs->loadLangs(array("payrollci@payrollci"));

$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'aZ');

$object = new Payslip($db);
$form = new Form($db);

if ($id > 0) $object->fetch($id);

// ==================== ACTIONS ====================
if ($action == 'add') {
    // Employé
    $object->fk_user            = GETPOST('fk_user', 'int');
    $object->employee_name      = GETPOST('employee_name', 'alpha');
    $object->employee_job       = GETPOST('employee_job', 'alpha');
    $object->employee_category  = GETPOST('employee_category', 'alpha');
    $object->employee_echelon   = GETPOST('employee_echelon', 'alpha');
    $object->numero_cnps        = GETPOST('numero_cnps', 'alpha');
    $object->numero_cmu         = GETPOST('numero_cmu', 'alpha');
    $object->matricule          = GETPOST('matricule', 'alpha');

    // Période
    $mois = GETPOST('date_startmonth', 'int');
    $annee = GETPOST('date_startyear', 'int');
    $object->date_start = dol_mktime(0, 0, 0, $mois, 1, $annee);
    $object->date_end   = dol_mktime(0, 0, 0, $mois, date('t', mktime(0, 0, 0, $mois, 1, $annee)), $annee);

    // Situation
    $object->situation_familiale = GETPOST('situation_familiale', 'alpha');
    $object->nombre_enfants     = GETPOST('nombre_enfants', 'int');

    // Paramètres
    $object->secteur_activite = GETPOST('secteur_activite', 'alpha');
    $object->ville            = GETPOST('ville', 'alpha');
    $object->anciennete_mois  = GETPOST('anciennete_mois', 'int');

    $secteurs = PayrollCICalc::getSecteursActivite();
    $object->taux_at = $secteurs[$object->secteur_activite]['taux'] ?? 2.0;

    // Salaire de base
    $object->salaire_base   = price2num(GETPOST('salaire_base', 'alpha'));
    $object->sursalaire     = price2num(GETPOST('sursalaire', 'alpha'));

    // Primes
    $primes = ['prime_anciennete','prime_rendement','prime_technicite','prime_fonction',
               'prime_responsabilite','prime_risque','prime_outillage','prime_salissure',
               'prime_caisse','prime_assiduite','prime_panier','gratification'];
    foreach ($primes as $p) $object->$p = price2num(GETPOST($p, 'alpha'));

    // Auto-calcul ancienneté si anciennete_mois renseigné et prime non saisie
    if ($object->anciennete_mois > 0 && $object->prime_anciennete == 0) {
        $object->prime_anciennete = PayrollCICalc::calculerPrimeAnciennete(
            $object->salaire_base, $object->anciennete_mois
        );
    }

    // Indemnités
    $indems = ['indemnite_transport','indemnite_logement','indemnite_representation',
               'indemnite_expatriation','indemnite_deplacement','indemnite_kilometrique'];
    foreach ($indems as $i) $object->$i = price2num(GETPOST($i, 'alpha'));

    // Avantages en nature
    $avnat = ['avantage_nature_logement','avantage_nature_vehicule',
              'avantage_nature_domestique','avantage_nature_nourriture','avantage_nature_autres'];
    foreach ($avnat as $a) $object->$a = price2num(GETPOST($a, 'alpha'));

    // Heures supplémentaires
    $hs = ['heures_sup_15','heures_sup_50','heures_sup_75','heures_sup_100'];
    foreach ($hs as $h) $object->$h = price2num(GETPOST($h, 'alpha'));

    // Autres gains
    $object->conges_payes  = price2num(GETPOST('conges_payes', 'alpha'));
    $object->autres_primes = price2num(GETPOST('autres_primes', 'alpha'));

    // Déductions
    $deds = ['avance_salaire','pret_deduction','pension_alimentaire',
             'saisie_arret','mutuelle_complementaire','autres_retenues'];
    foreach ($deds as $d) $object->$d = price2num(GETPOST($d, 'alpha'));

    // Calcul automatique
    $object->calculate();

    $result = $object->create($user);
    if ($result > 0) {
        setEventMessages('Bulletin de paie créé avec succès', null, 'mesgs');
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$result);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
        $action = 'create';
    }
}

if ($action == 'confirm_validate' && $confirm == 'yes') {
    $result = $object->validate($user);
    if ($result > 0) setEventMessages('Bulletin validé', null, 'mesgs');
}

if ($action == 'builddoc') {
    $pdfGenerator = new pdf_bulletinpaie($db);
    $result = $pdfGenerator->write_file($object);
    if ($result > 0) setEventMessages('PDF généré avec succès', null, 'mesgs');
    else setEventMessages('Erreur lors de la génération du PDF', null, 'errors');
}

if ($action == 'confirm_delete' && $confirm == 'yes') {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."payrollci_payslip WHERE rowid = ".((int) $object->id);
    $db->query($sql);
    setEventMessages('Bulletin supprimé', null, 'mesgs');
    header('Location: list.php');
    exit;
}

// ==================== AFFICHAGE ====================
llxHeader('', 'Bulletin de Paie', '', '', 0, 0, '', '', '', 'mod-payrollci');

// Helper pour un champ de saisie numérique
function _field($name, $label, $required = false) {
    $req = $required ? ' required' : '';
    $cls = $required ? ' fieldrequired' : '';
    return '<td class="'.$cls.'">'.$label.'</td>'
         . '<td><input type="number" name="'.$name.'" value="0" min="0" step="1000" class="flat maxwidth150"'.$req.'></td>';
}

// === FORMULAIRE DE CRÉATION ===
if ($action == 'create') {
    print load_fiche_titre('Nouveau Bulletin de Paie (Complet CI)', '', 'payrollci@payrollci');
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    print dol_get_fiche_head(array(), '', '', 0);

    print '<table class="border centpercent tableforfieldcreate">';

    // ──── INFORMATIONS EMPLOYÉ ────
    print '<tr class="liste_titre"><td colspan="4"><b>👤 INFORMATIONS EMPLOYÉ</b></td></tr>';

    print '<tr><td class="titlefieldcreate fieldrequired">Employé Dolibarr</td>';
    print '<td colspan="3">'.$form->select_dolusers('', 'fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth400').'</td></tr>';

    print '<tr><td class="fieldrequired">Nom complet</td>';
    print '<td><input type="text" name="employee_name" value="" size="40" required></td>';
    print '<td>Matricule</td>';
    print '<td><input type="text" name="matricule" value="" size="20"></td></tr>';

    print '<tr><td>Poste / Fonction</td>';
    print '<td><input type="text" name="employee_job" value="" size="30"></td>';
    print '<td>Catégorie / Échelon</td>';
    print '<td><input type="text" name="employee_category" size="10" placeholder="ex: A"> / <input type="text" name="employee_echelon" size="10" placeholder="ex: 3"></td></tr>';

    print '<tr><td>N° CNPS</td>';
    print '<td><input type="text" name="numero_cnps" value="" size="20"></td>';
    print '<td>N° CMU/CNAM</td>';
    print '<td><input type="text" name="numero_cmu" value="" size="20"></td></tr>';

    // ──── SITUATION FAMILIALE ────
    print '<tr class="liste_titre"><td colspan="4"><b>👨‍👩‍👧‍👦 SITUATION FAMILIALE (calcul IGR)</b></td></tr>';

    $situations = payrollci_get_situations();
    print '<tr><td>Situation</td><td><select name="situation_familiale" class="flat">';
    foreach ($situations as $k => $v) print '<option value="'.$k.'">'.$v.'</option>';
    print '</select></td>';
    print '<td>Enfants à charge</td>';
    print '<td><input type="number" name="nombre_enfants" value="0" min="0" max="10" class="flat" size="5"></td></tr>';

    // ──── PÉRIODE & PARAMÈTRES ────
    print '<tr class="liste_titre"><td colspan="4"><b>📅 PÉRIODE & PARAMÈTRES</b></td></tr>';

    $moisList = payrollci_get_mois();
    print '<tr><td class="fieldrequired">Mois / Année</td><td>';
    print '<select name="date_startmonth" class="flat">';
    $cm = date('n');
    foreach ($moisList as $num => $nom) {
        $sel = ($num == $cm) ? ' selected' : '';
        print '<option value="'.$num.'"'.$sel.'>'.$nom.'</option>';
    }
    print '</select> <input type="number" name="date_startyear" value="'.date('Y').'" min="2020" max="2030" size="6" class="flat">';
    print '</td>';

    // Ancienneté
    print '<td>Ancienneté (mois)</td>';
    print '<td><input type="number" name="anciennete_mois" value="0" min="0" max="600" class="flat" size="5"> <em style="color:#888">auto-calcul prime si &gt; 24</em></td></tr>';

    // Secteur et ville
    print '<tr><td>Secteur d\'activité</td><td>';
    $secteurs = PayrollCICalc::getSecteursActivite();
    $defSec = $conf->global->PAYROLLCI_DEFAULT_SECTEUR ?? 'commerce';
    print '<select name="secteur_activite" class="flat">';
    foreach ($secteurs as $k => $s) {
        $sel = ($k == $defSec) ? ' selected' : '';
        print '<option value="'.$k.'"'.$sel.'>'.$s['label'].' (AT: '.$s['taux'].'%)</option>';
    }
    print '</select></td>';

    print '<td>Ville (transport exonéré)</td><td>';
    $villes = PayrollCICalc::getVilles();
    print '<select name="ville" class="flat">';
    foreach ($villes as $k => $v) {
        $sel = ($k == 'abidjan') ? ' selected' : '';
        print '<option value="'.$k.'"'.$sel.'>'.$v['label'].' ('.number_format($v['plafond'], 0, ',', ' ').' F)</option>';
    }
    print '</select></td></tr>';

    // ──── SALAIRE DE BASE ────
    print '<tr class="liste_titre"><td colspan="4"><b>💰 SALAIRE DE BASE (FCFA)</b></td></tr>';
    print '<tr>';
    print _field('salaire_base', 'Salaire catégoriel (base) *', true);
    print _field('sursalaire', 'Sursalaire');
    print '</tr>';

    // ──── PRIMES CCI ────
    print '<tr class="liste_titre"><td colspan="4"><b>🏅 PRIMES (Convention Collective CI)</b></td></tr>';

    $champsPrimes = [
        ['prime_anciennete', 'Prime d\'ancienneté'],
        ['prime_rendement', 'Prime de rendement'],
        ['prime_technicite', 'Prime de technicité'],
        ['prime_fonction', 'Prime de fonction'],
        ['prime_responsabilite', 'Prime de responsabilité'],
        ['prime_risque', 'Prime de risque/danger'],
        ['prime_outillage', 'Prime d\'outillage'],
        ['prime_salissure', 'Prime de salissure'],
        ['prime_caisse', 'Prime de caisse'],
        ['prime_assiduite', 'Prime d\'assiduité/ponctualité'],
        ['prime_panier', 'Prime de panier (repas nuit)'],
        ['gratification', 'Gratification / 13ème mois'],
    ];
    for ($i = 0; $i < count($champsPrimes); $i += 2) {
        print '<tr>';
        print _field($champsPrimes[$i][0], $champsPrimes[$i][1]);
        if (isset($champsPrimes[$i+1])) print _field($champsPrimes[$i+1][0], $champsPrimes[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    // ──── INDEMNITÉS ────
    print '<tr class="liste_titre"><td colspan="4"><b>🚗 INDEMNITÉS (FCFA)</b></td></tr>';

    $champsIndem = [
        ['indemnite_transport', 'Indemnité de transport'],
        ['indemnite_logement', 'Indemnité de logement'],
        ['indemnite_representation', 'Indemnité de représentation'],
        ['indemnite_expatriation', 'Indemnité d\'expatriation'],
        ['indemnite_deplacement', 'Indemnité de déplacement'],
        ['indemnite_kilometrique', 'Indemnité kilométrique'],
    ];
    for ($i = 0; $i < count($champsIndem); $i += 2) {
        print '<tr>';
        print _field($champsIndem[$i][0], $champsIndem[$i][1]);
        if (isset($champsIndem[$i+1])) print _field($champsIndem[$i+1][0], $champsIndem[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    // ──── AVANTAGES EN NATURE ────
    print '<tr class="liste_titre"><td colspan="4"><b>🏠 AVANTAGES EN NATURE (barème administratif, FCFA)</b></td></tr>';

    $champsAN = [
        ['avantage_nature_logement', 'Logement'],
        ['avantage_nature_vehicule', 'Véhicule'],
        ['avantage_nature_domestique', 'Personnel domestique'],
        ['avantage_nature_nourriture', 'Nourriture'],
        ['avantage_nature_autres', 'Autres avantages'],
    ];
    for ($i = 0; $i < count($champsAN); $i += 2) {
        print '<tr>';
        print _field($champsAN[$i][0], $champsAN[$i][1]);
        if (isset($champsAN[$i+1])) print _field($champsAN[$i+1][0], $champsAN[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    // ──── HEURES SUPPLÉMENTAIRES ────
    print '<tr class="liste_titre"><td colspan="4"><b>⏰ HEURES SUPPLÉMENTAIRES (montants FCFA — Art. CCI)</b></td></tr>';
    print '<tr><td colspan="4" style="color:#666;font-size:0.9em;">';
    print '<em>15% = 41ème-46ème h | 50% = au-delà 46h | 75% = nuit OU dimanche/férié | 100% = nuit + dimanche/férié</em>';
    print '</td></tr>';

    $champsHS = [
        ['heures_sup_15', 'HS 15% (41ème-46ème h)'],
        ['heures_sup_50', 'HS 50% (au-delà 46h)'],
        ['heures_sup_75', 'HS 75% (nuit/dim/férié)'],
        ['heures_sup_100', 'HS 100% (nuit + dim/férié)'],
    ];
    print '<tr>';
    print _field($champsHS[0][0], $champsHS[0][1]);
    print _field($champsHS[1][0], $champsHS[1][1]);
    print '</tr><tr>';
    print _field($champsHS[2][0], $champsHS[2][1]);
    print _field($champsHS[3][0], $champsHS[3][1]);
    print '</tr>';

    // ──── AUTRES GAINS ────
    print '<tr class="liste_titre"><td colspan="4"><b>📋 AUTRES GAINS (FCFA)</b></td></tr>';
    print '<tr>';
    print _field('conges_payes', 'Congés payés');
    print _field('autres_primes', 'Autres primes');
    print '</tr>';

    // ──── DÉDUCTIONS ────
    print '<tr class="liste_titre"><td colspan="4"><b>➖ DÉDUCTIONS (FCFA)</b></td></tr>';

    $champsDed = [
        ['avance_salaire', 'Avance sur salaire'],
        ['pret_deduction', 'Remboursement de prêt'],
        ['pension_alimentaire', 'Pension alimentaire'],
        ['saisie_arret', 'Saisie-arrêt sur salaire'],
        ['mutuelle_complementaire', 'Mutuelle complémentaire'],
        ['autres_retenues', 'Autres retenues'],
    ];
    for ($i = 0; $i < count($champsDed); $i += 2) {
        print '<tr>';
        print _field($champsDed[$i][0], $champsDed[$i][1]);
        if (isset($champsDed[$i+1])) print _field($champsDed[$i+1][0], $champsDed[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    print '</table>';
    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="Calculer et créer le bulletin">';
    print ' &nbsp; <a class="button button-cancel" href="list.php">Annuler</a>';
    print '</div>';
    print '</form>';
}

// === AFFICHAGE D'UN BULLETIN ===
elseif ($object->id > 0) {

    if ($action == 'validate') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Valider le bulletin',
            'Êtes-vous sûr de vouloir valider ce bulletin ? Action irréversible.',
            'confirm_validate', '', 0, 1
        );
    }
    if ($action == 'delete') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Supprimer le bulletin',
            'Êtes-vous sûr de vouloir supprimer ce bulletin ?',
            'confirm_delete', '', 0, 1
        );
    }

    print load_fiche_titre('Bulletin de Paie: '.$object->ref, '', 'payrollci@payrollci');

    $statusLabel = ($object->status == Payslip::STATUS_VALIDATED)
        ? '<span class="badge badge-status4">Validé</span>'
        : '<span class="badge badge-status0">Brouillon</span>';

    print '<div class="fichecenter">';

    // ──── INFOS GÉNÉRALES ────
    print '<table class="border centpercent tableforfield">';
    print '<tr class="liste_titre"><td colspan="4"><b>👤 INFORMATIONS GÉNÉRALES</b></td></tr>';
    print '<tr><td width="25%">Référence</td><td>'.$object->ref.'</td>';
    print '<td width="25%">Statut</td><td>'.$statusLabel.'</td></tr>';

    $moisList = payrollci_get_mois();
    $periodeLabel = ($moisList[intval(date('m', $object->date_start))] ?? '').' '.date('Y', $object->date_start);
    $secteurs = PayrollCICalc::getSecteursActivite();
    $villes = PayrollCICalc::getVilles();
    print '<tr><td>Période</td><td>'.$periodeLabel.'</td>';
    print '<td>Secteur / Ville</td><td>'.($secteurs[$object->secteur_activite]['label'] ?? '').
          ' | '.($villes[$object->ville]['label'] ?? $object->ville).'</td></tr>';

    print '<tr><td>Employé</td><td><b>'.$object->employee_name.'</b>';
    if ($object->matricule) print ' (Mat: '.$object->matricule.')';
    print '</td>';
    print '<td>Poste</td><td>'.$object->employee_job.'</td></tr>';

    print '<tr><td>Cat. / Éch.</td><td>'.$object->employee_category.' / '.$object->employee_echelon.'</td>';
    $sitLabel = PayrollCICalc::getLibelleSituation($object->situation_familiale, $object->nombre_enfants);
    print '<td>Situation</td><td>'.$sitLabel.' ('.$object->nombre_parts.' parts)</td></tr>';

    print '<tr><td>N° CNPS</td><td>'.$object->numero_cnps.'</td>';
    print '<td>Ancienneté</td><td>'.$object->anciennete_mois.' mois</td></tr>';
    print '</table><br>';

    // ──── DÉTAIL DE LA PAIE ────
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Désignation</td><td class="right">Base</td><td class="right">Taux</td>';
    print '<td class="right">Retenue salarié</td><td class="right">Charge patronale</td></tr>';

    // Helper afficher une ligne de gain si > 0
    $showGain = function($label, $amount) {
        if ($amount > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$label.'</td>';
            print '<td></td><td></td>';
            print '<td class="right">'.payrollci_format_amount($amount).'</td><td></td></tr>';
        }
    };

    // ── GAINS ──
    print '<tr class="liste_titre"><td colspan="5"><b>💰 GAINS / RÉMUNÉRATION</b></td></tr>';

    $showGain('Salaire catégoriel (base)', $object->salaire_base);
    $showGain('Sursalaire', $object->sursalaire);

    // Primes
    $primesList = [
        ['Prime d\'ancienneté', $object->prime_anciennete],
        ['Prime de rendement', $object->prime_rendement],
        ['Prime de technicité', $object->prime_technicite],
        ['Prime de fonction', $object->prime_fonction],
        ['Prime de responsabilité', $object->prime_responsabilite],
        ['Prime de risque/danger', $object->prime_risque],
        ['Prime d\'outillage', $object->prime_outillage],
        ['Prime de salissure', $object->prime_salissure],
        ['Prime de caisse', $object->prime_caisse],
        ['Prime d\'assiduité', $object->prime_assiduite],
        ['Prime de panier', $object->prime_panier],
        ['Gratification / 13ème mois', $object->gratification],
    ];
    foreach ($primesList as $g) $showGain($g[0], $g[1]);

    // Indemnités
    $showGain('Indemnité de transport', $object->indemnite_transport);
    $showGain('Indemnité de logement', $object->indemnite_logement);
    $showGain('Indemnité de représentation', $object->indemnite_representation);
    $showGain('Indemnité d\'expatriation', $object->indemnite_expatriation);
    $showGain('Indemnité de déplacement', $object->indemnite_deplacement);
    $showGain('Indemnité kilométrique', $object->indemnite_kilometrique);

    // Avantages en nature
    $showGain('Av. nature: Logement', $object->avantage_nature_logement);
    $showGain('Av. nature: Véhicule', $object->avantage_nature_vehicule);
    $showGain('Av. nature: Domestique', $object->avantage_nature_domestique);
    $showGain('Av. nature: Nourriture', $object->avantage_nature_nourriture);
    $showGain('Av. nature: Autres', $object->avantage_nature_autres);

    // Heures sup
    if ($object->heures_sup_15 > 0) {
        print '<tr class="oddeven"><td>&nbsp;&nbsp;Heures sup. 15% (41è-46è h)</td>';
        print '<td></td><td class="right">15%</td>';
        print '<td class="right">'.payrollci_format_amount($object->heures_sup_15).'</td><td></td></tr>';
    }
    if ($object->heures_sup_50 > 0) {
        print '<tr class="oddeven"><td>&nbsp;&nbsp;Heures sup. 50% (>46h)</td>';
        print '<td></td><td class="right">50%</td>';
        print '<td class="right">'.payrollci_format_amount($object->heures_sup_50).'</td><td></td></tr>';
    }
    if ($object->heures_sup_75 > 0) {
        print '<tr class="oddeven"><td>&nbsp;&nbsp;Heures sup. 75% (nuit/dim/férié)</td>';
        print '<td></td><td class="right">75%</td>';
        print '<td class="right">'.payrollci_format_amount($object->heures_sup_75).'</td><td></td></tr>';
    }
    if ($object->heures_sup_100 > 0) {
        print '<tr class="oddeven"><td>&nbsp;&nbsp;Heures sup. 100% (nuit+dim/férié)</td>';
        print '<td></td><td class="right">100%</td>';
        print '<td class="right">'.payrollci_format_amount($object->heures_sup_100).'</td><td></td></tr>';
    }

    $showGain('Congés payés', $object->conges_payes);
    $showGain('Autres primes', $object->autres_primes);

    // SALAIRE BRUT
    print '<tr class="liste_total"><td><b>SALAIRE BRUT</b></td>';
    print '<td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->salaire_brut).'</b></td><td></td></tr>';

    // BRUT IMPOSABLE
    if ($object->transport_non_imposable > 0) {
        print '<tr class="oddeven" style="color:#888"><td>&nbsp;&nbsp;<em>Transport non imposable déduit</em></td>';
        print '<td></td><td></td>';
        print '<td class="right"><em>- '.payrollci_format_amount($object->transport_non_imposable).'</em></td><td></td></tr>';
    }
    print '<tr class="oddeven" style="font-weight:bold"><td>&nbsp;&nbsp;BRUT IMPOSABLE</td>';
    print '<td colspan="2"></td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td><td></td></tr>';

    // ── CNPS ──
    print '<tr class="liste_titre"><td colspan="5"><b>🏛️ COTISATIONS SOCIALES (CNPS)</b></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Retraite (Assurance Vieillesse)</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE)).'</td>';
    print '<td class="right">6,3% / 7,7%</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_retraite_sal).'</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_retraite_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Prestations familiales + Maternité</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF)).'</td>';
    print '<td class="right">5,75%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_pf_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Accidents du travail</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF)).'</td>';
    print '<td class="right">'.$object->taux_at.'%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_at_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;CMU (Couverture Maladie Universelle)</td>';
    print '<td class="right">Forfait</td><td class="right">500 F/mois</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_sal).'</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_pat).'</td></tr>';

    // ── CHARGES FISCALES PATRONALES ──
    print '<tr class="liste_titre"><td colspan="5"><b>🏢 CHARGES FISCALES PATRONALES</b></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Impôt Employeur (IE)</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">1,2%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->impot_employeur).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;FDFP / Taxe d\'Apprentissage (TA)</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">0,4%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->fdfp_ta).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;FDFP / Formation Prof. Continue (FPC)</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">0,6%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->fdfp_fpc).'</td></tr>';

    // ── ITS ──
    print '<tr class="liste_titre"><td colspan="5"><b>📊 IMPÔTS SUR TRAITEMENTS & SALAIRES (ITS)</b></td></tr>';

    $baseFiscale = round($object->brut_imposable * 0.80);
    print '<tr class="oddeven"><td>&nbsp;&nbsp;IS (Impôt sur Salaires)</td>';
    print '<td class="right">'.payrollci_format_amount($baseFiscale).'</td>';
    print '<td class="right">1,5%</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_is).'</td><td></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;CN (Contribution Nationale)</td>';
    print '<td class="right">'.payrollci_format_amount($baseFiscale).'</td>';
    print '<td class="right">Progressif</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_cn).'</td><td></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;IGR (Impôt Général sur le Revenu)</td>';
    print '<td class="right">'.$object->nombre_parts.' parts</td>';
    print '<td class="right">Progressif</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_igr).'</td><td></td></tr>';

    print '<tr class="liste_total"><td><b>TOTAL ITS</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->its_total).'</b></td><td></td></tr>';

    // ── DÉDUCTIONS ──
    $hasDed = ($object->avance_salaire + $object->pret_deduction + $object->pension_alimentaire
             + $object->saisie_arret + $object->mutuelle_complementaire + $object->autres_retenues) > 0;
    if ($hasDed) {
        print '<tr class="liste_titre"><td colspan="5"><b>➖ AUTRES DÉDUCTIONS</b></td></tr>';
        $deds = [
            ['Avance sur salaire', $object->avance_salaire],
            ['Remboursement de prêt', $object->pret_deduction],
            ['Pension alimentaire', $object->pension_alimentaire],
            ['Saisie-arrêt sur salaire', $object->saisie_arret],
            ['Mutuelle complémentaire', $object->mutuelle_complementaire],
            ['Autres retenues', $object->autres_retenues],
        ];
        foreach ($deds as $d) {
            if ($d[1] > 0) {
                print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$d[0].'</td><td colspan="2"></td>';
                print '<td class="right">'.payrollci_format_amount($d[1]).'</td><td></td></tr>';
            }
        }
    }

    // ── RÉCAPITULATIF ──
    print '<tr class="liste_titre"><td colspan="5"><b>📋 RÉCAPITULATIF</b></td></tr>';

    $totalDed = $object->avance_salaire + $object->pret_deduction + $object->pension_alimentaire
              + $object->saisie_arret + $object->mutuelle_complementaire + $object->autres_retenues;
    $totalRetGlobal = $object->total_retenues_sal + $totalDed;

    print '<tr class="oddeven"><td><b>Total retenues salariales (CNPS + ITS)</b></td>';
    print '<td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->total_retenues_sal).'</b></td><td></td></tr>';

    if ($totalDed > 0) {
        print '<tr class="oddeven"><td><b>Total déductions supplémentaires</b></td>';
        print '<td colspan="2"></td>';
        print '<td class="right"><b>'.payrollci_format_amount($totalDed).'</b></td><td></td></tr>';
    }

    print '<tr class="oddeven"><td><b>Total charges patronales (sociales + fiscales)</b></td>';
    print '<td colspan="2"></td><td></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->total_charges_pat).'</b></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;dont charges sociales</td><td colspan="2"></td><td></td>';
    print '<td class="right">'.payrollci_format_amount($object->total_charges_sociales).'</td></tr>';
    print '<tr class="oddeven"><td>&nbsp;&nbsp;dont charges fiscales (IE + FDFP)</td><td colspan="2"></td><td></td>';
    print '<td class="right">'.payrollci_format_amount($object->total_charges_fiscales).'</td></tr>';

    // NET À PAYER
    print '<tr style="background-color:#27ae60;color:white;font-size:1.2em;">';
    print '<td><b>NET À PAYER</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->net_a_payer).' FCFA</b></td>';
    print '<td class="right"><b>Coût total employeur: '.payrollci_format_amount($object->salaire_brut + $object->total_charges_pat).' FCFA</b></td></tr>';

    print '</table></div>';

    // === BOUTONS ===
    print '<div class="tabsAction">';
    if ($object->status == Payslip::STATUS_DRAFT) {
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken().'">Valider</a>';
    }
    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=builddoc&token='.newToken().'">Générer PDF</a>';

    $pdfpath = $conf->payrollci->dir_output.'/bulletins/'.$object->ref.'.pdf';
    if (file_exists($pdfpath)) {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/document.php?modulepart=payrollci&file=bulletins/'.$object->ref.'.pdf" target="_blank">Télécharger PDF</a>';
    }
    if ($object->status == Payslip::STATUS_DRAFT) {
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'">Supprimer</a>';
    }
    print '</div>';
}
else {
    header('Location: list.php');
    exit;
}

llxFooter();
$db->close();
