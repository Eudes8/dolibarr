<?php
/* ============================================================================
 * PayrollCI v4 - Fiche bulletin de paie - Intégration Dolibarr
 * Réforme ITS 2024 : IBS + RICF / Date d'embauche / Expatrié
 * Onglets, mode édition, documents, notes, événements, objets liés
 * ============================================================================ */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/payrollci/class/payslip.class.php');
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

$langs->loadLangs(array("payrollci@payrollci", "companies", "other"));

$id      = GETPOST('id', 'int');
$ref     = GETPOST('ref', 'alpha');
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'aZ');
$cancel  = GETPOST('cancel', 'aZ');

$object = new Payslip($db);
$form = new Form($db);
$formfile = new FormFile($db);

if ($id > 0 || !empty($ref)) {
    $object->fetch($id, $ref);
    $id = $object->id;
}

// Vérification des permissions
$permissiontoread   = $user->rights->payrollci->lire || $user->admin;
$permissiontoadd    = $user->rights->payrollci->creer || $user->admin;
$permissiontodelete = $user->rights->payrollci->supprimer || $user->admin;

if (!$permissiontoread) accessforbidden();

// ==================== ACTIONS ====================

if ($cancel) {
    if ($action == 'create') { header('Location: list.php'); exit; }
    $action = '';
}

// ── CREATION ──
if ($action == 'add' && $permissiontoadd) {
    _readFormData($object, $db, $conf);
    $object->calculate();

    $result = $object->create($user);
    if ($result > 0) {
        setEventMessages('Bulletin de paie créé avec succès', null, 'mesgs');
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$result);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'create';
    }
}

// ── MISE A JOUR ──
if ($action == 'update' && $permissiontoadd) {
    _readFormData($object, $db, $conf);
    $object->calculate();

    $result = $object->update($user);
    if ($result > 0) {
        setEventMessages('Bulletin mis à jour', null, 'mesgs');
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'edit';
    }
}

// ── VALIDATION ──
if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd) {
    $result = $object->validate($user);
    if ($result > 0) { setEventMessages('Bulletin validé avec succès', null, 'mesgs'); }
    else { setEventMessages($object->error, null, 'errors'); }
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
    exit;
}

// ── REMETTRE EN BROUILLON ──
if ($action == 'confirm_setdraft' && $confirm == 'yes' && $permissiontoadd) {
    $result = $object->setDraft($user);
    if ($result > 0) { setEventMessages('Bulletin remis en brouillon', null, 'mesgs'); }
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
    exit;
}

// ── SUPPRESSION ──
if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
    $result = $object->delete($user);
    if ($result > 0) { setEventMessages('Bulletin supprimé', null, 'mesgs'); header('Location: list.php'); exit; }
    setEventMessages($object->error, null, 'errors');
}

// ── GENERATION PDF ──
if ($action == 'builddoc' && $permissiontoread) {
    $object->generateDocument('pdf_bulletinpaie', $langs);
    setEventMessages('PDF généré avec succès', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
    exit;
}

// ── MISE A JOUR NOTES ──
if ($action == 'setnote_public' && $permissiontoadd) {
    $object->note_public = GETPOST('note_public', 'restricthtml');
    $object->update($user, 1);
}
if ($action == 'setnote_private' && $permissiontoadd) {
    $object->note_private = GETPOST('note_private', 'restricthtml');
    $object->update($user, 1);
}

// ==================== AFFICHAGE ====================
$title = 'Bulletin de Paie';
if ($object->ref) $title .= ' '.$object->ref;
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-payrollci page-card');

// ═══════════ MODE CREATION ═══════════
if ($action == 'create') {
    if (!$permissiontoadd) accessforbidden();

    print load_fiche_titre('Nouveau Bulletin de Paie', '', 'object_payrollci@payrollci');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';

    print dol_get_fiche_head(array(), '');
    _printFormFields($object, $form, $conf, 'create');
    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="Calculer et créer le bulletin">';
    print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="Annuler">';
    print '</div>';
    print '</form>';
}

// ═══════════ MODE EDITION ═══════════
elseif ($action == 'edit' && $object->id > 0) {
    if (!$permissiontoadd) accessforbidden();
    if ($object->status != Payslip::STATUS_DRAFT) {
        setEventMessages('Impossible de modifier un bulletin validé', null, 'errors');
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
        exit;
    }

    $head = payrollci_prepare_head($object);
    print dol_get_fiche_head($head, 'card', 'Bulletin de Paie', -1, 'object_payrollci@payrollci');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';

    _printFormFields($object, $form, $conf, 'edit');

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="Recalculer et enregistrer">';
    print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="Annuler">';
    print '</div>';
    print '</form>';

    print dol_get_fiche_end();
}

// ═══════════ MODE CONSULTATION ═══════════
elseif ($object->id > 0) {

    // Boîtes de confirmation
    if ($action == 'validate') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Valider le bulletin',
            'Êtes-vous sûr de vouloir valider ce bulletin ? Il ne pourra plus être modifié directement.',
            'confirm_validate', '', 0, 1
        );
    }
    if ($action == 'setdraft') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Remettre en brouillon',
            'Remettre ce bulletin en mode brouillon pour modifications ?',
            'confirm_setdraft', '', 0, 1
        );
    }
    if ($action == 'delete') {
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            'Supprimer le bulletin',
            'Êtes-vous sûr de vouloir supprimer ce bulletin et ses documents ?',
            'confirm_delete', '', 0, 1
        );
    }

    $head = payrollci_prepare_head($object);
    print dol_get_fiche_head($head, 'card', 'Bulletin de Paie', -1, 'object_payrollci@payrollci');

    // ── Bandeau principal (standard Dolibarr) ──
    $linkback = '<a href="'.dol_buildpath('/payrollci/list.php', 1).'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '<b>'.$object->employee_name.'</b>';
    if ($object->employee_job) $morehtmlref .= ' - '.$object->employee_job;
    if ($object->is_expatrie) $morehtmlref .= ' <span class="badge badge-warning" title="Salarié expatrié (contribution employeur 12%)">Expatrié</span>';
    if ($object->fk_user > 0) {
        $userstatic = new User($db);
        $userstatic->fetch($object->fk_user);
        $morehtmlref .= '<br>'.img_picto('', 'user', 'class="pictofixedwidth"').$userstatic->getNomUrl(1);
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';

    // ── INFOS GÉNÉRALES ──
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    $moisList = payrollci_get_mois();
    $periodeLabel = ($moisList[intval(date('m', $object->date_start))] ?? '').' '.date('Y', $object->date_start);
    print '<tr><td class="titlefield">'.img_picto('', 'calendar', 'class="pictofixedwidth"').'Période</td><td>'.$periodeLabel.'</td></tr>';

    print '<tr><td>'.img_picto('', 'badge', 'class="pictofixedwidth"').'Matricule</td><td>'.$object->matricule.'</td></tr>';
    print '<tr><td>'.img_picto('', 'category', 'class="pictofixedwidth"').'Cat. / Éch.</td><td>'.$object->employee_category.' / '.$object->employee_echelon.'</td></tr>';
    print '<tr><td>'.img_picto('', 'security', 'class="pictofixedwidth"').'N° CNPS</td><td>'.$object->numero_cnps.'</td></tr>';
    print '<tr><td>'.img_picto('', 'health', 'class="pictofixedwidth"').'N° CMU/CNAM</td><td>'.$object->numero_cmu.'</td></tr>';

    $sitLabel = PayrollCICalc::getLibelleSituation($object->situation_familiale, $object->nombre_enfants);
    print '<tr><td>'.img_picto('', 'family', 'class="pictofixedwidth"').'Situation familiale</td><td>'.$sitLabel.' ('.$object->nombre_parts.' parts)</td></tr>';

    // V4: Affichage date d'embauche et ancienneté auto-calculée
    if (!empty($object->date_embauche)) {
        print '<tr><td>'.img_picto('', 'calendar', 'class="pictofixedwidth"').'Date d\'embauche</td><td>'.dol_print_date($object->date_embauche, 'day').'</td></tr>';
    }
    print '<tr><td>'.img_picto('', 'clock', 'class="pictofixedwidth"').'Ancienneté</td><td>'.$object->anciennete_mois.' mois';
    if ($object->anciennete_mois >= 24) {
        $tauxAnc = PayrollCICalc::calculerTauxAnciennete($object->anciennete_mois);
        print ' ('.($tauxAnc * 100).'%)';
    }
    print '</td></tr>';

    $secteurs = PayrollCICalc::getSecteursActivite();
    $villes = PayrollCICalc::getVilles();
    print '<tr><td>'.img_picto('', 'company', 'class="pictofixedwidth"').'Secteur d\'activité</td><td>'.($secteurs[$object->secteur_activite]['label'] ?? '').' (AT: '.$object->taux_at.'%)</td></tr>';
    print '<tr><td>'.img_picto('', 'globe', 'class="pictofixedwidth"').'Ville</td><td>'.($villes[$object->ville]['label'] ?? ucfirst($object->ville)).'</td></tr>';

    print '</table>';
    print '</div>'; // fichehalfleft

    print '<div class="fichehalfright">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    // Montants clés en résumé
    print '<tr><td class="titlefield">'.img_picto('', 'money-bill-alt', 'class="pictofixedwidth"').'Salaire brut</td><td class="right"><b>'.payrollci_format_amount($object->salaire_brut).' FCFA</b></td></tr>';
    print '<tr><td>Brut imposable</td><td class="right">'.payrollci_format_amount($object->brut_imposable).' FCFA</td></tr>';
    print '<tr><td>CNPS salarié (retraite)</td><td class="right">'.payrollci_format_amount($object->cnps_retraite_sal).' FCFA</td></tr>';
    print '<tr><td>CMU salarié</td><td class="right">'.payrollci_format_amount($object->cmu_sal).' FCFA</td></tr>';

    // V4: ITS détail
    print '<tr><td>'.img_picto('', 'tax', 'class="pictofixedwidth"').'IBS (Impôt sur le Revenu des Salaires)</td><td class="right">'.payrollci_format_amount($object->its_ibs).' FCFA</td></tr>';
    print '<tr><td>'.img_picto('', 'receive', 'class="pictofixedwidth"').'RICF (Réduction pour charges de famille)</td><td class="right" style="color:#27ae60;">- '.payrollci_format_amount($object->its_ricf).' FCFA</td></tr>';
    print '<tr><td><b>Total ITS net</b></td><td class="right"><b>'.payrollci_format_amount($object->its_total).' FCFA</b></td></tr>';

    print '<tr><td>Total retenues salariales</td><td class="right">'.payrollci_format_amount($object->total_retenues_sal).' FCFA</td></tr>';

    $totalDed = $object->avance_salaire + $object->pret_deduction + $object->pension_alimentaire
              + $object->saisie_arret + $object->mutuelle_complementaire + $object->autres_retenues;
    if ($totalDed > 0) {
        print '<tr><td>Déductions supplémentaires</td><td class="right">'.payrollci_format_amount($totalDed).' FCFA</td></tr>';
    }

    print '<tr><td>Total charges patronales</td><td class="right">'.payrollci_format_amount($object->total_charges_pat).' FCFA</td></tr>';

    print '<tr style="background-color:#e8f5e9;"><td><b>'.img_picto('', 'money-bill-alt', 'class="pictofixedwidth"').'NET À PAYER</b></td>';
    print '<td class="right" style="font-size:1.2em;color:#27ae60;"><b>'.payrollci_format_amount($object->net_a_payer).' FCFA</b></td></tr>';

    $coutTotal = $object->salaire_brut + $object->total_charges_pat;
    print '<tr><td>Coût total employeur</td><td class="right"><b>'.payrollci_format_amount($coutTotal).' FCFA</b></td></tr>';

    // Dates
    print '<tr><td>Date de création</td><td class="right">'.dol_print_date($object->date_creation, 'dayhour').'</td></tr>';
    if ($object->date_valid) {
        print '<tr><td>Date de validation</td><td class="right">'.dol_print_date($object->date_valid, 'dayhour').'</td></tr>';
    }

    print '</table>';
    print '</div>'; // fichehalfright

    print '</div>'; // fichecenter
    print '<div class="clearboth"></div>';

    // ═══════════ DÉTAIL COMPLET DE LA PAIE ═══════════
    print '<br>';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Désignation</td><td class="right">Base</td><td class="right">Taux</td>';
    print '<td class="right">Retenue salarié</td><td class="right">Charge patronale</td></tr>';

    // Helper
    $showGain = function($label, $amount) {
        if ($amount > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$label.'</td><td></td><td></td>';
            print '<td class="right">'.payrollci_format_amount($amount).'</td><td></td></tr>';
        }
    };

    // ── GAINS ──
    print '<tr class="liste_titre"><td colspan="5">'.img_picto('', 'money-bill-alt', 'class="pictofixedwidth"').'<b>GAINS / RÉMUNÉRATION</b></td></tr>';
    $showGain('Salaire catégoriel (base)', $object->salaire_base);
    $showGain('Sursalaire', $object->sursalaire);

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

    $showGain('Indemnité de transport', $object->indemnite_transport);
    $showGain('Indemnité de logement', $object->indemnite_logement);
    $showGain('Indemnité de représentation', $object->indemnite_representation);
    $showGain('Indemnité d\'expatriation', $object->indemnite_expatriation);
    $showGain('Indemnité de déplacement', $object->indemnite_deplacement);
    $showGain('Indemnité kilométrique', $object->indemnite_kilometrique);

    $showGain('Av. nature: Logement', $object->avantage_nature_logement);
    $showGain('Av. nature: Véhicule', $object->avantage_nature_vehicule);
    $showGain('Av. nature: Domestique', $object->avantage_nature_domestique);
    $showGain('Av. nature: Nourriture', $object->avantage_nature_nourriture);
    $showGain('Av. nature: Autres', $object->avantage_nature_autres);

    $hsList = [
        ['Heures sup. 15% (41è-46è h)', $object->heures_sup_15, '15%'],
        ['Heures sup. 50% (>46h)', $object->heures_sup_50, '50%'],
        ['Heures sup. 75% (nuit/dim)', $object->heures_sup_75, '75%'],
        ['Heures sup. 100% (nuit+dim)', $object->heures_sup_100, '100%'],
    ];
    foreach ($hsList as $h) {
        if ($h[1] > 0) {
            print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$h[0].'</td><td></td>';
            print '<td class="right">'.$h[2].'</td>';
            print '<td class="right">'.payrollci_format_amount($h[1]).'</td><td></td></tr>';
        }
    }
    $showGain('Congés payés', $object->conges_payes);
    $showGain('Autres primes', $object->autres_primes);

    print '<tr class="liste_total"><td><b>SALAIRE BRUT</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->salaire_brut).'</b></td><td></td></tr>';

    if ($object->transport_non_imposable > 0) {
        print '<tr class="oddeven" style="color:#888"><td>&nbsp;&nbsp;<em>Transport non imposable déduit</em></td><td colspan="2"></td>';
        print '<td class="right"><em>- '.payrollci_format_amount($object->transport_non_imposable).'</em></td><td></td></tr>';
    }
    print '<tr class="oddeven" style="font-weight:bold"><td>&nbsp;&nbsp;BRUT IMPOSABLE</td><td colspan="2"></td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td><td></td></tr>';

    // ── CNPS ──
    print '<tr class="liste_titre"><td colspan="5">'.img_picto('', 'security', 'class="pictofixedwidth"').'<b>COTISATIONS SOCIALES (CNPS)</b></td></tr>';
    print '<tr class="oddeven"><td>&nbsp;&nbsp;Retraite</td>';
    print '<td class="right">'.payrollci_format_amount(min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE)).'</td>';
    print '<td class="right">6,3%/7,7%</td>';
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

    print '<tr class="oddeven"><td>&nbsp;&nbsp;CMU</td>';
    print '<td class="right">Forfait</td><td class="right">500F/mois</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_sal).'</td>';
    print '<td class="right">'.payrollci_format_amount($object->cmu_pat).'</td></tr>';

    // ── CHARGES FISCALES PATRONALES (V4: Contribution Employeur) ──
    print '<tr class="liste_titre"><td colspan="5">'.img_picto('', 'company', 'class="pictofixedwidth"').'<b>CHARGES FISCALES PATRONALES</b></td></tr>';
    $tauxContrib = $object->is_expatrie ? '12,0%' : '2,8%';
    $labelContrib = $object->is_expatrie ? 'Contribution employeur (expatrié)' : 'Contribution employeur (local)';
    print '<tr class="oddeven"><td>&nbsp;&nbsp;'.$labelContrib.'</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">'.$tauxContrib.'</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->contribution_employeur).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;FDFP/TA</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">0,4%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->fdfp_ta).'</td></tr>';

    print '<tr class="oddeven"><td>&nbsp;&nbsp;FDFP/FPC</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">0,6%</td><td class="right">-</td>';
    print '<td class="right">'.payrollci_format_amount($object->fdfp_fpc).'</td></tr>';

    // ── ITS V4 (IBS + RICF) ──
    print '<tr class="liste_titre"><td colspan="5">'.img_picto('', 'tax', 'class="pictofixedwidth"').'<b>IMPÔTS SUR TRAITEMENTS & SALAIRES (ITS)</b></td></tr>';
    print '<tr class="oddeven"><td colspan="5" style="color:#555;font-size:0.85em;padding-left:20px;">';
    print '<em>Ordonnance n° 2023-719 du 13/09/2023 — Barème IBS mensuel (CGI Art. 119 bis)</em></td></tr>';

    // Détail IBS par tranche
    $detailIBS = PayrollCICalc::getDetailIBS($object->brut_imposable);
    if (!empty($detailIBS)) {
        $trancheNum = 0;
        foreach ($detailIBS as $tranche) {
            $trancheNum++;
            if ($tranche['impot'] > 0) {
                print '<tr class="oddeven"><td>&nbsp;&nbsp;Tranche '.$trancheNum.' (';
                print payrollci_format_amount($tranche['min']).' - '.($tranche['max'] > 0 ? payrollci_format_amount($tranche['max']) : '...').')</td>';
                print '<td class="right">'.payrollci_format_amount($tranche['base']).'</td>';
                print '<td class="right">'.($tranche['taux'] * 100).'%</td>';
                print '<td class="right">'.payrollci_format_amount($tranche['impot']).'</td><td></td></tr>';
            }
        }
    }

    print '<tr class="oddeven" style="font-weight:bold;"><td>&nbsp;&nbsp;IBS (total brut)</td>';
    print '<td class="right">'.payrollci_format_amount($object->brut_imposable).'</td>';
    print '<td class="right">Progressif</td>';
    print '<td class="right">'.payrollci_format_amount($object->its_ibs).'</td><td></td></tr>';

    print '<tr class="oddeven" style="color:#27ae60;"><td>&nbsp;&nbsp;RICF (réduction '.$object->nombre_parts.' parts)</td>';
    print '<td class="right">11 000 x ('.number_format($object->nombre_parts, 1).' - 1)</td>';
    print '<td class="right">-</td>';
    print '<td class="right">- '.payrollci_format_amount($object->its_ricf).'</td><td></td></tr>';

    print '<tr class="liste_total"><td><b>TOTAL ITS NET</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->its_total).'</b></td><td></td></tr>';

    // ── DÉDUCTIONS ──
    if ($totalDed > 0) {
        print '<tr class="liste_titre"><td colspan="5">'.img_picto('', 'payment', 'class="pictofixedwidth"').'<b>AUTRES DÉDUCTIONS</b></td></tr>';
        $deds = [
            ['Avance sur salaire', $object->avance_salaire],
            ['Remboursement de prêt', $object->pret_deduction],
            ['Pension alimentaire', $object->pension_alimentaire],
            ['Saisie-arrêt', $object->saisie_arret],
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

    // NET
    print '<tr style="background-color:#27ae60;color:white;font-size:1.2em;">';
    print '<td><b>NET À PAYER</b></td><td colspan="2"></td>';
    print '<td class="right"><b>'.payrollci_format_amount($object->net_a_payer).' FCFA</b></td>';
    print '<td class="right"><b>Coût employeur: '.payrollci_format_amount($coutTotal).' FCFA</b></td></tr>';

    print '</table></div>';

    // ── Extrafields ──
    print '<div class="fichecenter"><div class="fichehalfleft">';
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
    print '</div></div>';

    print dol_get_fiche_end();

    // ═══════════ ZONE DOCUMENTS (sous la fiche) ═══════════
    $objref = dol_sanitizeFileName($object->ref);
    $diroutput = $conf->payrollci->dir_output.'/bulletins';
    $filedir = $diroutput.'/'.$objref;
    $urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
    $genallowed = $permissiontoread;
    $delallowed = $permissiontoadd;

    if (!is_dir($filedir)) dol_mkdir($filedir);

    $oldpdf = $diroutput.'/'.$object->ref.'.pdf';
    $newpdf = $filedir.'/'.$object->ref.'.pdf';
    if (file_exists($oldpdf) && !file_exists($newpdf)) copy($oldpdf, $newpdf);

    print $formfile->showdocuments('payrollci', $objref, $filedir, $urlsource, $genallowed, $delallowed, 'pdf_bulletinpaie', 0, 0, 0, 28, 0, '', 0, '', '', '', null);

    // ═══════════ BOUTONS D'ACTION ═══════════
    print '<div class="tabsAction">';
    if ($object->status == Payslip::STATUS_DRAFT && $permissiontoadd) {
        print dolGetButtonAction('', 'Modifier', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken(), '');
        print dolGetButtonAction('', 'Valider', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken(), '');
    }
    if ($object->status == Payslip::STATUS_VALIDATED && $permissiontoadd) {
        print dolGetButtonAction('', 'Remettre en brouillon', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=setdraft&token='.newToken(), '');
    }
    print dolGetButtonAction('', 'Générer PDF', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=builddoc&token='.newToken(), '');

    if ($permissiontodelete && $object->status == Payslip::STATUS_DRAFT) {
        print dolGetButtonAction('', 'Supprimer', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '');
    }
    print '</div>';

    // ── Informations système ──
    print '<div class="fichecenter"><div class="fichehalfleft">';
    $object->info($object->id);
    print '</div></div>';
}

else {
    header('Location: list.php');
    exit;
}

llxFooter();
$db->close();


// ==================== FONCTIONS UTILITAIRES ====================

/**
 * Lire les données du formulaire et remplir l'objet
 */
function _readFormData(&$object, $db, $conf)
{
    $object->fk_user            = GETPOST('fk_user', 'int');
    $object->employee_name      = GETPOST('employee_name', 'alpha');
    $object->employee_job       = GETPOST('employee_job', 'alpha');
    $object->employee_category  = GETPOST('employee_category', 'alpha');
    $object->employee_echelon   = GETPOST('employee_echelon', 'alpha');
    $object->numero_cnps        = GETPOST('numero_cnps', 'alpha');
    $object->numero_cmu         = GETPOST('numero_cmu', 'alpha');
    $object->matricule          = GETPOST('matricule', 'alpha');
    $object->is_expatrie        = GETPOST('is_expatrie', 'int') ? 1 : 0;

    $mois  = GETPOST('date_startmonth', 'int');
    $annee = GETPOST('date_startyear', 'int');
    $object->date_start = dol_mktime(0, 0, 0, $mois, 1, $annee);
    $object->date_end   = dol_mktime(0, 0, 0, $mois, date('t', mktime(0, 0, 0, $mois, 1, $annee)), $annee);

    // V4: Date d'embauche
    $embDay   = GETPOST('date_embaucheday', 'int');
    $embMonth = GETPOST('date_embauchemonth', 'int');
    $embYear  = GETPOST('date_embaucheyear', 'int');
    if ($embDay > 0 && $embMonth > 0 && $embYear > 0) {
        $object->date_embauche = dol_mktime(0, 0, 0, $embMonth, $embDay, $embYear);
    }

    $object->situation_familiale = GETPOST('situation_familiale', 'alpha');
    $object->nombre_enfants     = GETPOST('nombre_enfants', 'int');
    $object->secteur_activite   = GETPOST('secteur_activite', 'alpha');
    $object->ville              = GETPOST('ville', 'alpha');

    $secteurs = PayrollCICalc::getSecteursActivite();
    $object->taux_at = $secteurs[$object->secteur_activite]['taux'] ?? 2.0;

    // Liens optionnels
    $object->fk_soc     = GETPOST('fk_soc', 'int') ?: null;
    $object->fk_project = GETPOST('fk_project', 'int') ?: null;
    $object->fk_contrat = GETPOST('fk_contrat', 'int') ?: null;

    // Tous les champs numériques
    $numFields = [
        'salaire_base', 'sursalaire',
        'prime_anciennete', 'prime_rendement', 'prime_technicite', 'prime_fonction',
        'prime_responsabilite', 'prime_risque', 'prime_outillage', 'prime_salissure',
        'prime_caisse', 'prime_assiduite', 'prime_panier', 'gratification',
        'indemnite_transport', 'indemnite_logement', 'indemnite_representation',
        'indemnite_expatriation', 'indemnite_deplacement', 'indemnite_kilometrique',
        'avantage_nature_logement', 'avantage_nature_vehicule',
        'avantage_nature_domestique', 'avantage_nature_nourriture', 'avantage_nature_autres',
        'heures_sup_15', 'heures_sup_50', 'heures_sup_75', 'heures_sup_100',
        'conges_payes', 'autres_primes',
        'avance_salaire', 'pret_deduction', 'pension_alimentaire',
        'saisie_arret', 'mutuelle_complementaire', 'autres_retenues',
    ];
    foreach ($numFields as $f) {
        $object->$f = price2num(GETPOST($f, 'alpha'));
    }

    // Notes
    $object->note_public  = GETPOST('note_public', 'restricthtml');
    $object->note_private = GETPOST('note_private', 'restricthtml');

    // Auto-fill depuis utilisateur Dolibarr
    if (empty($object->employee_name) && $object->fk_user > 0) {
        $object->fetchUserInfo();
    }
}

/**
 * Afficher le formulaire (create ou edit)
 */
function _printFormFields($object, $form, $conf, $mode = 'create')
{
    print '<table class="border centpercent tableforfieldcreate">';

    // ──── INFORMATIONS EMPLOYÉ ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'user', 'class="pictofixedwidth"').'<b>INFORMATIONS EMPLOYÉ</b></td></tr>';

    print '<tr><td class="titlefieldcreate fieldrequired">Employé Dolibarr</td>';
    print '<td colspan="3">'.$form->select_dolusers($object->fk_user ?? '', 'fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth400');
    print ' <em style="color:#888;font-size:0.9em">Si sélectionné, les infos seront auto-remplies</em></td></tr>';

    print '<tr><td class="fieldrequired">Nom complet</td>';
    print '<td><input type="text" name="employee_name" value="'.dol_escape_htmltag($object->employee_name).'" size="40" required></td>';
    print '<td>Matricule</td>';
    print '<td><input type="text" name="matricule" value="'.dol_escape_htmltag($object->matricule).'" size="20"></td></tr>';

    print '<tr><td>Poste / Fonction</td>';
    print '<td><input type="text" name="employee_job" value="'.dol_escape_htmltag($object->employee_job).'" size="30"></td>';
    print '<td>Catégorie / Échelon</td>';
    print '<td><input type="text" name="employee_category" value="'.dol_escape_htmltag($object->employee_category).'" size="10" placeholder="ex: A">';
    print ' / <input type="text" name="employee_echelon" value="'.dol_escape_htmltag($object->employee_echelon).'" size="10" placeholder="ex: 3"></td></tr>';

    print '<tr><td>N° CNPS</td>';
    print '<td><input type="text" name="numero_cnps" value="'.dol_escape_htmltag($object->numero_cnps).'" size="20"></td>';
    print '<td>N° CMU/CNAM</td>';
    print '<td><input type="text" name="numero_cmu" value="'.dol_escape_htmltag($object->numero_cmu).'" size="20"></td></tr>';

    // V4: Expatrié
    $chkExp = (!empty($object->is_expatrie)) ? ' checked' : '';
    print '<tr><td>Salarié expatrié</td>';
    print '<td><input type="checkbox" name="is_expatrie" value="1"'.$chkExp.'>';
    print ' <em style="color:#888;font-size:0.9em">Contribution employeur à 12% au lieu de 2,8%</em></td>';
    print '<td></td><td></td></tr>';

    // ──── SITUATION FAMILIALE ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'family', 'class="pictofixedwidth"').'<b>SITUATION FAMILIALE (calcul RICF)</b></td></tr>';
    $situations = payrollci_get_situations();
    print '<tr><td>Situation</td><td><select name="situation_familiale" class="flat">';
    foreach ($situations as $k => $v) {
        $sel = (($object->situation_familiale ?? 'celibataire') == $k) ? ' selected' : '';
        print '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
    }
    print '</select></td>';
    print '<td>Enfants à charge</td>';
    print '<td><input type="number" name="nombre_enfants" value="'.($object->nombre_enfants ?? 0).'" min="0" max="10" class="flat" size="5"></td></tr>';

    // ──── PÉRIODE & PARAMÈTRES ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'calendar', 'class="pictofixedwidth"').'<b>PÉRIODE & PARAMÈTRES</b></td></tr>';
    $moisList = payrollci_get_mois();
    $curMonth = $object->date_start ? intval(date('m', $object->date_start)) : intval(date('n'));
    $curYear  = $object->date_start ? intval(date('Y', $object->date_start)) : intval(date('Y'));

    print '<tr><td class="fieldrequired">Mois / Année</td><td>';
    print '<select name="date_startmonth" class="flat">';
    foreach ($moisList as $num => $nom) {
        $sel = ($num == $curMonth) ? ' selected' : '';
        print '<option value="'.$num.'"'.$sel.'>'.$nom.'</option>';
    }
    print '</select> <input type="number" name="date_startyear" value="'.$curYear.'" min="2020" max="2035" size="6" class="flat">';
    print '</td>';

    // V4: Date d'embauche (remplace ancienneté manuelle)
    print '<td class="fieldrequired">Date d\'embauche</td>';
    print '<td>';
    print $form->selectDate($object->date_embauche ?? -1, 'date_embauche', 0, 0, 1, '', 1, 1);
    print ' <em style="color:#888;font-size:0.9em">'.img_picto('', 'info', 'class="pictofixedwidth"').'Ancienneté et prime auto-calculées</em>';
    print '</td></tr>';

    // Secteur et ville
    $secteurs = PayrollCICalc::getSecteursActivite();
    $defSec = $object->secteur_activite ?? (getDolGlobalString('PAYROLLCI_DEFAULT_SECTEUR', 'commerce'));
    print '<tr><td>Secteur d\'activité</td><td>';
    print '<select name="secteur_activite" class="flat">';
    foreach ($secteurs as $k => $s) {
        $sel = ($k == $defSec) ? ' selected' : '';
        print '<option value="'.$k.'"'.$sel.'>'.$s['label'].' (AT: '.$s['taux'].'%)</option>';
    }
    print '</select></td>';

    $villes = PayrollCICalc::getVilles();
    $defVille = $object->ville ?? (getDolGlobalString('PAYROLLCI_DEFAULT_VILLE', 'abidjan'));
    print '<td>Ville</td><td>';
    print '<select name="ville" class="flat">';
    foreach ($villes as $k => $v) {
        $sel = ($k == $defVille) ? ' selected' : '';
        print '<option value="'.$k.'"'.$sel.'>'.$v['label'].' ('.number_format($v['plafond'], 0, ',', ' ').' F)</option>';
    }
    print '</select></td></tr>';

    // Helper champ numérique
    $field = function($name, $label, $required = false) use ($object) {
        $val = $object->$name ?? 0;
        $req = $required ? ' required' : '';
        $cls = $required ? ' fieldrequired' : '';
        return '<td class="'.$cls.'">'.$label.'</td>'
             . '<td><input type="number" name="'.$name.'" value="'.$val.'" min="0" step="1000" class="flat maxwidth150"'.$req.'></td>';
    };

    // ──── SALAIRE DE BASE ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'money-bill-alt', 'class="pictofixedwidth"').'<b>SALAIRE DE BASE (FCFA)</b></td></tr>';
    print '<tr>'.$field('salaire_base', 'Salaire catégoriel *', true).$field('sursalaire', 'Sursalaire').'</tr>';

    // ──── PRIMES CCI ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'star', 'class="pictofixedwidth"').'<b>PRIMES (Convention Collective CI)</b></td></tr>';
    print '<tr><td colspan="4" style="color:#555;font-size:0.9em;padding-left:20px;"><em>'.img_picto('', 'info', 'class="pictofixedwidth"').'La prime d\'ancienneté est auto-calculée à partir de la date d\'embauche (2% après 24 mois, +1%/an, max 25%)</em></td></tr>';
    $champsPrimes = [
        ['prime_anciennete', 'Prime d\'ancienneté (auto)'], ['prime_rendement', 'Prime de rendement'],
        ['prime_technicite', 'Prime de technicité'], ['prime_fonction', 'Prime de fonction'],
        ['prime_responsabilite', 'Prime de responsabilité'], ['prime_risque', 'Prime de risque/danger'],
        ['prime_outillage', 'Prime d\'outillage'], ['prime_salissure', 'Prime de salissure'],
        ['prime_caisse', 'Prime de caisse'], ['prime_assiduite', 'Prime d\'assiduité'],
        ['prime_panier', 'Prime de panier (repas nuit)'], ['gratification', 'Gratification / 13ème mois'],
    ];
    for ($i = 0; $i < count($champsPrimes); $i += 2) {
        print '<tr>'.$field($champsPrimes[$i][0], $champsPrimes[$i][1]);
        if (isset($champsPrimes[$i+1])) print $field($champsPrimes[$i+1][0], $champsPrimes[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    // ──── INDEMNITÉS ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'receive', 'class="pictofixedwidth"').'<b>INDEMNITÉS (FCFA)</b></td></tr>';
    $champsIndem = [
        ['indemnite_transport', 'Indemnité de transport'], ['indemnite_logement', 'Indemnité de logement'],
        ['indemnite_representation', 'Indemnité de représentation'], ['indemnite_expatriation', 'Indemnité d\'expatriation'],
        ['indemnite_deplacement', 'Indemnité de déplacement'], ['indemnite_kilometrique', 'Indemnité kilométrique'],
    ];
    for ($i = 0; $i < count($champsIndem); $i += 2) {
        print '<tr>'.$field($champsIndem[$i][0], $champsIndem[$i][1]);
        if (isset($champsIndem[$i+1])) print $field($champsIndem[$i+1][0], $champsIndem[$i+1][1]);
        print '</tr>';
    }

    // ──── AVANTAGES EN NATURE ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'resource', 'class="pictofixedwidth"').'<b>AVANTAGES EN NATURE (FCFA)</b></td></tr>';
    $champsAN = [
        ['avantage_nature_logement', 'Logement'], ['avantage_nature_vehicule', 'Véhicule'],
        ['avantage_nature_domestique', 'Personnel domestique'], ['avantage_nature_nourriture', 'Nourriture'],
        ['avantage_nature_autres', 'Autres avantages'],
    ];
    for ($i = 0; $i < count($champsAN); $i += 2) {
        print '<tr>'.$field($champsAN[$i][0], $champsAN[$i][1]);
        if (isset($champsAN[$i+1])) print $field($champsAN[$i+1][0], $champsAN[$i+1][1]);
        else print '<td></td><td></td>';
        print '</tr>';
    }

    // ──── HEURES SUPPLÉMENTAIRES ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'clock', 'class="pictofixedwidth"').'<b>HEURES SUPPLÉMENTAIRES (FCFA)</b></td></tr>';
    print '<tr><td colspan="4" style="color:#666;font-size:0.9em;padding-left:20px;"><em>15% = 41è-46è h | 50% = >46h | 75% = nuit/dim | 100% = nuit+dim</em></td></tr>';
    $champsHS = [
        ['heures_sup_15', 'HS 15%'], ['heures_sup_50', 'HS 50%'],
        ['heures_sup_75', 'HS 75%'], ['heures_sup_100', 'HS 100%'],
    ];
    print '<tr>'.$field($champsHS[0][0], $champsHS[0][1]).$field($champsHS[1][0], $champsHS[1][1]).'</tr>';
    print '<tr>'.$field($champsHS[2][0], $champsHS[2][1]).$field($champsHS[3][0], $champsHS[3][1]).'</tr>';

    // ──── AUTRES GAINS ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'payment', 'class="pictofixedwidth"').'<b>AUTRES GAINS (FCFA)</b></td></tr>';
    print '<tr>'.$field('conges_payes', 'Congés payés').$field('autres_primes', 'Autres primes').'</tr>';

    // ──── DÉDUCTIONS ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'generic', 'class="pictofixedwidth"').'<b>DÉDUCTIONS (FCFA)</b></td></tr>';
    $champsDed = [
        ['avance_salaire', 'Avance sur salaire'], ['pret_deduction', 'Remboursement de prêt'],
        ['pension_alimentaire', 'Pension alimentaire'], ['saisie_arret', 'Saisie-arrêt'],
        ['mutuelle_complementaire', 'Mutuelle complémentaire'], ['autres_retenues', 'Autres retenues'],
    ];
    for ($i = 0; $i < count($champsDed); $i += 2) {
        print '<tr>'.$field($champsDed[$i][0], $champsDed[$i][1]);
        if (isset($champsDed[$i+1])) print $field($champsDed[$i+1][0], $champsDed[$i+1][1]);
        print '</tr>';
    }

    // ──── NOTES ────
    print '<tr class="liste_titre"><td colspan="4">'.img_picto('', 'note', 'class="pictofixedwidth"').'<b>NOTES</b></td></tr>';
    print '<tr><td>Note publique</td><td colspan="3">';
    print '<textarea name="note_public" rows="3" class="flat quatrevingtpercent">'
        .dol_escape_htmltag($object->note_public ?? '').'</textarea></td></tr>';
    print '<tr><td>Note privée</td><td colspan="3">';
    print '<textarea name="note_private" rows="3" class="flat quatrevingtpercent">'
        .dol_escape_htmltag($object->note_private ?? '').'</textarea></td></tr>';

    print '</table>';
}
