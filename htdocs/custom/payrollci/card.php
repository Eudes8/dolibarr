<?php
/* ============================================================================
 * PayrollCI - Fiche bulletin de paie (création / édition / visualisation)
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

if ($id > 0) {
    $object->fetch($id);
}

// ==================== ACTIONS ====================

// Créer un bulletin
if ($action == 'add') {
    $object->fk_user            = GETPOST('fk_user', 'int');
    $object->employee_name      = GETPOST('employee_name', 'alpha');
    $object->employee_job       = GETPOST('employee_job', 'alpha');
    $object->employee_category  = GETPOST('employee_category', 'alpha');
    $object->employee_echelon   = GETPOST('employee_echelon', 'alpha');
    $object->numero_cnps        = GETPOST('numero_cnps', 'alpha');
    $object->numero_cmu         = GETPOST('numero_cmu', 'alpha');
    $object->date_start         = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), 1, GETPOST('date_startyear', 'int'));
    $mois = GETPOST('date_startmonth', 'int');
    $annee = GETPOST('date_startyear', 'int');
    $object->date_end           = dol_mktime(0, 0, 0, $mois, date('t', mktime(0, 0, 0, $mois, 1, $annee)), $annee);
    $object->situation_familiale = GETPOST('situation_familiale', 'alpha');
    $object->nombre_enfants     = GETPOST('nombre_enfants', 'int');
    $object->salaire_base       = price2num(GETPOST('salaire_base', 'alpha'));
    $object->prime_anciennete   = price2num(GETPOST('prime_anciennete', 'alpha'));
    $object->prime_transport    = price2num(GETPOST('prime_transport', 'alpha'));
    $object->prime_logement     = price2num(GETPOST('prime_logement', 'alpha'));
    $object->prime_responsabilite = price2num(GETPOST('prime_responsabilite', 'alpha'));
    $object->prime_salissure    = price2num(GETPOST('prime_salissure', 'alpha'));
    $object->heures_sup_25      = price2num(GETPOST('heures_sup_25', 'alpha'));
    $object->heures_sup_50      = price2num(GETPOST('heures_sup_50', 'alpha'));
    $object->autres_primes      = price2num(GETPOST('autres_primes', 'alpha'));
    $object->conges_payes       = price2num(GETPOST('conges_payes', 'alpha'));
    $object->avance_salaire     = price2num(GETPOST('avance_salaire', 'alpha'));
    $object->pret_deduction     = price2num(GETPOST('pret_deduction', 'alpha'));
    $object->autres_retenues    = price2num(GETPOST('autres_retenues', 'alpha'));
    $object->secteur_activite   = GETPOST('secteur_activite', 'alpha');

    $secteurs = PayrollCICalc::getSecteursActivite();
    $object->taux_at = $secteurs[$object->secteur_activite]['taux'] ?? 2.0;

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

// Valider
if ($action == 'confirm_validate' && $confirm == 'yes') {
    $result = $object->validate($user);
    if ($result > 0) {
        setEventMessages('Bulletin validé', null, 'mesgs');
    }
}

// Générer PDF
if ($action == 'builddoc') {
    $pdfGenerator = new pdf_bulletinpaie($db);
    $result = $pdfGenerator->write_file($object);
    if ($result > 0) {
        setEventMessages('PDF généré avec succès', null, 'mesgs');
    } else {
        setEventMessages('Erreur lors de la génération du PDF', null, 'errors');
    }
}

// Supprimer
if ($action == 'confirm_delete' && $confirm == 'yes') {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."payrollci_payslip WHERE rowid = ".((int) $object->id);
    $db->query($sql);
    setEventMessages('Bulletin supprimé', null, 'mesgs');
    header('Location: list.php');
    exit;
}

// ==================== AFFICHAGE ====================
llxHeader('', 'Bulletin de Paie');

// === FORMULAIRE DE CRÉATION ===
if ($action == 'create') {
    print load_fiche_titre('Nouveau Bulletin de Paie', '', 'payrollci@payrollci');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';

    print dol_get_fiche_head(array(), '', '', 0);

    print '<table class="border centpercent tableforfieldcreate">';

    // Employé
    print '<tr class="liste_titre"><td colspan="4"><b>INFORMATIONS EMPLOYÉ</b></td></tr>';

    print '<tr><td class="titlefieldcreate fieldrequired">Employé (utilisateur Dolibarr)</td>';
    print '<td colspan="3">'.$form->select_dolusers('', 'fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth400').'</td></tr>';

    print '<tr><td class="fieldrequired">Nom complet</td>';
    print '<td><input type="text" name="employee_name" value="" size="40" required></td>';
    print '<td>Poste / Fonction</td>';
    print '<td><input type="text" name="employee_job" value="" size="30"></td></tr>';

    print '<tr><td>Catégorie</td>';
    print '<td><input type="text" name="employee_category" value="" size="20" placeholder="ex: A, B, C..."></td>';
    print '<td>Échelon</td>';
    print '<td><input type="text" name="employee_echelon" value="" size="20" placeholder="ex: 1, 2, 3..."></td></tr>';

    print '<tr><td>N° CNPS</td>';
    print '<td><input type="text" name="numero_cnps" value="" size="20"></td>';
    print '<td>N° CMU/CNAM</td>';
    print '<td><input type="text" name="numero_cmu" value="" size="20"></td></tr>';

    // Situation familiale
    print '<tr class="liste_titre"><td colspan="4"><b>SITUATION FAMILIALE (pour calcul IGR)</b></td></tr>';

    $situations = payrollci_get_situations();
    print '<tr><td>Situation</td><td>';
    print '<select name="situation_familiale">';
    foreach ($situations as $key => $label) {
        print '<option value="'.$key.'">'.$label.'</option>';
    }
    print '</select></td>';
    print '<td>Nombre d\'enfants à charge</td>';
    print '<td><input type="number" name="nombre_enfants" value="0" min="0" max="10" size="5"></td></tr>';

    // Période
    print '<tr class="liste_titre"><td colspan="4"><b>PÉRIODE DE PAIE</b></td></tr>';

    $moisList = payrollci_get_mois();
    print '<tr><td class="fieldrequired">Mois</td><td>';
    print '<select name="date_startmonth">';
    $currentMonth = date('n');
    foreach ($moisList as $num => $nom) {
        $sel = ($num == $currentMonth) ? ' selected' : '';
        print '<option value="'.$num.'"'.$sel.'>'.$nom.'</option>';
    }
    print '</select>';
    print ' <input type="number" name="date_startyear" value="'.date('Y').'" min="2020" max="2030" size="6">';
    print '</td><td colspan="2"></td></tr>';

    // Secteur d'activité
    print '<tr><td>Secteur d\'activité</td><td colspan="3">';
    $secteurs = PayrollCICalc::getSecteursActivite();
    $defaultSecteur = $conf->global->PAYROLLCI_DEFAULT_SECTEUR ?: 'commerce';
    print '<select name="secteur_activite">';
    foreach ($secteurs as $key => $sect) {
        $sel = ($key == $defaultSecteur) ? ' selected' : '';
        print '<option value="'.$key.'"'.$sel.'>'.$sect['label'].' (Taux AT: '.$sect['taux'].'%)</option>';
    }
    print '</select></td></tr>';

    // Éléments de rémunération
    print '<tr class="liste_titre"><td colspan="4"><b>ÉLÉMENTS DE RÉMUNÉRATION (FCFA)</b></td></tr>';

    $champs_remun = [
        ['salaire_base', 'Salaire de base *', true],
        ['prime_anciennete', 'Prime d\'ancienneté', false],
        ['prime_transport', 'Indemnité de transport', false],
        ['prime_logement', 'Indemnité de logement', false],
        ['prime_responsabilite', 'Prime de responsabilité', false],
        ['prime_salissure', 'Prime de salissure', false],
        ['heures_sup_25', 'Heures sup. 25%', false],
        ['heures_sup_50', 'Heures sup. 50%', false],
        ['autres_primes', 'Autres primes', false],
        ['conges_payes', 'Congés payés', false],
    ];

    for ($i = 0; $i < count($champs_remun); $i += 2) {
        print '<tr>';
        for ($j = 0; $j < 2 && ($i + $j) < count($champs_remun); $j++) {
            $c = $champs_remun[$i + $j];
            $req = $c[2] ? ' required' : '';
            print '<td>'.$c[1].'</td>';
            print '<td><input type="number" name="'.$c[0].'" value="0" min="0" step="1000" size="15"'.$req.'></td>';
        }
        if (($i + 1) >= count($champs_remun)) {
            print '<td></td><td></td>';
        }
        print '</tr>';
    }

    // Déductions
    print '<tr class="liste_titre"><td colspan="4"><b>AVANCES ET DÉDUCTIONS (FCFA)</b></td></tr>';

    print '<tr><td>Avance sur salaire</td>';
    print '<td><input type="number" name="avance_salaire" value="0" min="0" step="1000" size="15"></td>';
    print '<td>Remboursement prêt</td>';
    print '<td><input type="number" name="pret_deduction" value="0" min="0" step="1000" size="15"></td></tr>';

    print '<tr><td>Autres retenues</td>';
    print '<td><input type="number" name="autres_retenues" value="0" min="0" step="1000" size="15"></td>';
    print '<td colspan="2"></td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="Calculer et créer le bulletin">';
    print ' &nbsp; ';
    print '<a class="button button-cancel" href="list.php">Annuler</a>';
    print '</div>';

    print '</form>';
}

// === AFFICHAGE D'UN BULLETIN EXISTANT ===
elseif ($object->id > 0) {

    // Confirmation de validation
    if ($action == 'validate') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Valider le bulletin',
            'Êtes-vous sûr de vouloir valider ce bulletin ? Cette action est irréversible.',
            'confirm_validate', '', 0, 1
        );
    }

    // Confirmation de suppression
    if ($action == 'delete') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Supprimer le bulletin',
            'Êtes-vous sûr de vouloir supprimer ce bulletin ?',
            'confirm_delete', '', 0, 1
        );
    }

    print load_fiche_titre('Bulletin de Paie: '.$object->ref, '', 'payrollci@payrollci');

    // Statut
    $statusLabel = ($object->status == Payslip::STATUS_VALIDATED)
        ? '<span class="badge badge-status4">Validé</span>'
        : '<span class="badge badge-status0">Brouillon</span>';

    print '<div class="fichecenter">';

    // === Informations générales ===
    print '<table class="border centpercent tableforfield">';

    print '<tr class="liste_titre"><td colspan="4"><b>INFORMATIONS GÉNÉRALES</b></td></tr>';

    print '<tr><td width="25%">Référence</td><td>'.$object->ref.'</td>';
    print '<td width="25%">Statut</td><td>'.$statusLabel.'</td></tr>';

    $moisList = payrollci_get_mois();
    $periodeLabel = ($moisList[intval(date('m', $object->date_start))] ?? '').' '.date('Y', $object->date_start);
    print '<tr><td>Période</td><td>'.$periodeLabel.'</td>';
    $secteurs = PayrollCICalc::getSecteursActivite();
    print '<td>Secteur</td><td>'.($secteurs[$object->secteur_activite]['label'] ?? $object->secteur_activite).' (AT: '.$object->taux_at.'%)</td></tr>';

    print '<tr><td>Employé</td><td><b>'.$object->employee_name.'</b></td>';
    print '<td>Poste</td><td>'.$object->employee_job.'</td></tr>';

    print '<tr><td>Catégorie / Échelon</td><td>'.$object->employee_category.' / '.$object->employee_echelon.'</td>';
    $sitLabel = PayrollCICalc::getLibelleSituation($object->situation_familiale, $object->nombre_enfants);
    print '<td>Situation familiale</td><td>'.$sitLabel.' ('.$object->nombre_parts.' parts)</td></tr>';

    print '<tr><td>N° CNPS</td><td>'.$object->numero_cnps.'</td>';
    print '<td>N° CMU/CNAM</td><td>'.$object->numero_cmu.'</td></tr>';

    print '</table>';

    print '<br>';

    // === Détail de la paie ===
    print '<table class="noborder centpercent">';

    // En-tête
    print '<tr class="liste_titre">';
    print '<td>Désignation</td>';
    print '<td class="right">Base</td>';
    print '<td class="right">Taux</td>';
    print '<td class="right">Retenue salarié</td>';
    print '<td class="right">Charge patronale</td>';
    print '</tr>';

    // -- GAINS --
    print '<tr class="liste_titre"><td colspan="5"><b>GAINS / RÉMUNÉRATION</b></td></tr>';

    $gains = [
        ['Salaire de base', $object->salaire_base],
        ['Prime d\'ancienneté', $object->prime_anciennete],
        ['Indemnité de transport', $object->prime_transport],
        ['Indemnité de logement', $object->prime_logement],
        ['Prime de responsabilité', $object->prime_responsabilite],
        ['Prime de salissure', $object->prime_salissure],
        ['Heures sup. 25%', $object->heures_sup_25],
        ['Heures sup. 50%', $object->heures_sup_50],
        ['Autres primes', $object->autres_primes],
        ['Congés payés', $object->conges_payes],
    ];
    foreach ($gains as $g) {
        if ($g[1] > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$g[0].'</td>';
            print '<td class="right"></td><td class="right"></td>';
            print '<td class="right">'.payrollci_format_amount($g[1]).'</td>';
            print '<td class="right"></td></tr>';
        }
    }
    print '<tr class="liste_total"><td><b>SALAIRE BRUT</b></td>';
    print '<td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->salaire_brut).'</b></td>';
    print '<td class="right"></td></tr>';

    // -- CNPS --
    print '<tr class="liste_titre"><td colspan="5"><b>COTISATIONS CNPS</b></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Retraite (Assurance Vieillesse)</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE)).'</td>';
    print '<td class="right">6,3% / 7,7%</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_retraite_sal).'</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_retraite_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Prestations familiales + Maternité</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF)).'</td>';
    print '<td class="right">5,75%</td>';
    print '<td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_pf_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;Accidents du travail / Mal. Prof.</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF)).'</td>';
    print '<td class="right">'.$object->taux_at.'%</td>';
    print '<td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->cnps_at_pat).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;CMU</td>';
    print '<td class="right">Forfait</td>';
    print '<td class="right">500 F/mois</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_sal).'</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_pat).'</td></tr>';

    // -- ITS --
    print '<tr class="liste_titre"><td colspan="5"><b>IMPÔTS SUR TRAITEMENTS ET SALAIRES (ITS)</b></td></tr>';

    $baseFiscale = round($object->salaire_brut * 0.80);
    print '<tr class="oddeven"><td>&nbsp;&nbsp;IS (Impôt sur Salaires)</td>';
    print '<td class="right">'.payrollci_format_amount($baseFiscale).'</td>';
    print '<td class="right">1,5%</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_is).'</td>';
    print '<td class="right"></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;CN (Contribution Nationale)</td>';
    print '<td class="right">'.payrollci_format_amount($baseFiscale).'</td>';
    print '<td class="right">Progressif</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_cn).'</td>';
    print '<td class="right"></td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;IGR (Impôt Général sur le Revenu)</td>';
    print '<td class="right">'.$object->nombre_parts.' parts</td>';
    print '<td class="right">Progressif</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_igr).'</td>';
    print '<td class="right"></td></tr>';

    print '<tr class="liste_total"><td><b>TOTAL ITS</b></td>';
    print '<td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->its_total).'</b></td>';
    print '<td class="right"></td></tr>';

    // -- Déductions --
    if ($object->avance_salaire > 0 || $object->pret_deduction > 0 || $object->autres_retenues > 0) {
        print '<tr class="liste_titre"><td colspan="5"><b>AUTRES DÉDUCTIONS</b></td></tr>';
        if ($object->avance_salaire > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;Avance sur salaire</td><td colspan="2"></td>';
            print '<td class="right">'.payrollci_format_amount($object->avance_salaire).'</td><td></td></tr>';
        }
        if ($object->pret_deduction > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;Remboursement de prêt</td><td colspan="2"></td>';
            print '<td class="right">'.payrollci_format_amount($object->pret_deduction).'</td><td></td></tr>';
        }
        if ($object->autres_retenues > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;Autres retenues</td><td colspan="2"></td>';
            print '<td class="right">'.payrollci_format_amount($object->autres_retenues).'</td><td></td></tr>';
        }
    }

    // -- RÉCAPITULATIF --
    print '<tr class="liste_titre"><td colspan="5"><b>RÉCAPITULATIF</b></td></tr>';

    print '<tr class="oddeven"><td><b>Total retenues salariales</b></td>';
    print '<td colspan="2"></td>';
    $totalRet = $object->total_retenues_sal + $object->avance_salaire + $object->pret_deduction + $object->autres_retenues;
    print '<td class="right"><b>'.payrollci_format_amount($totalRet).'</b></td>';
    print '<td class="right"></td></tr>';

    print '<tr class="oddeven"><td><b>Total charges patronales</b></td>';
    print '<td colspan="2"></td><td></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->total_charges_pat).'</b></td></tr>';

    print '<tr style="background-color:#27ae60;color:white;font-size:1.2em;">';
    print '<td><b>NET À PAYER</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->net_a_payer).'</b></td>';
    print '<td class="right"><b>Coût employeur: '.payrollci_format_amount($object->salaire_brut + $object->total_charges_pat).'</b></td></tr>';

    print '</table>';

    print '</div>';

    // === BOUTONS D'ACTION ===
    print '<div class="tabsAction">';

    if ($object->status == Payslip::STATUS_DRAFT) {
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken().'">Valider</a>';
    }

    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=builddoc&token='.newToken().'">Générer PDF</a>';

    // Lien vers le PDF
    $pdfpath = $conf->payrollci->dir_output.'/bulletins/'.$object->ref.'.pdf';
    if (file_exists($pdfpath)) {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/document.php?modulepart=payrollci&file=bulletins/'.$object->ref.'.pdf" target="_blank">Télécharger PDF</a>';
    }

    if ($object->status == Payslip::STATUS_DRAFT) {
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'">Supprimer</a>';
    }

    print '</div>';
}

// Page par défaut - redirection vers la liste
else {
    header('Location: list.php');
    exit;
}

llxFooter();
$db->close();
