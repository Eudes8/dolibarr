<?php
/* ============================================================================
 * PayrollCI v4 - Moteur de calcul complet de la paie ivoirienne
 * Conforme à l'Ordonnance n° 2023-719 du 13 septembre 2023
 *
 * RÉFORME ITS 2024 :
 *   - Fusion IS + CN + IGR en ITS unique
 *   - Barème progressif IBS (Art. 119 bis CGI) - 6 tranches
 *   - RICF (Art. 120 CGI) - Réduction d'Impôt pour Charges de Famille
 *   - Suppression de l'abattement de 20%
 *   - Base = Salaire Brut Imposable (directement)
 *   - Contribution employeur : 2,8% local / 12% expatrié
 *
 * Sources : CGI CI 2024, Ordonnance 2023-719, CCI, Code Prévoyance Sociale, CNPS, FDFP
 * ============================================================================
 */

class PayrollCICalc
{
    // ========================= CONSTANTES CNPS 2026 =========================
    const CNPS_RETRAITE_SAL = 0.063;
    const CNPS_RETRAITE_PAT = 0.077;
    const CNPS_PLAFOND_RETRAITE = 3375000;
    const CNPS_PF_PAT = 0.0575;
    const CNPS_PLAFOND_PF = 70000;
    const CMU_MENSUEL = 500;
    const SMIG = 75000;

    // ========================= CHARGES FISCALES PATRONALES ===================
    const CONTRIBUTION_EMPLOYEUR_LOCAL = 0.028;     // 2,8% brut imposable (personnel local)
    const CONTRIBUTION_EMPLOYEUR_EXPATRIE = 0.12;   // 12% brut imposable (personnel expatrié)
    const FDFP_TA_RATE = 0.004;                     // 0,4% taxe d'apprentissage
    const FDFP_FPC_RATE = 0.006;                    // 0,6% formation professionnelle continue

    // ========================= TRANSPORT NON IMPOSABLE =======================
    const TRANSPORT_EXONERE_ABIDJAN = 30000;
    const TRANSPORT_EXONERE_BOUAKE  = 24000;
    const TRANSPORT_EXONERE_AUTRES  = 20000;

    // ========================= PRIME D'ANCIENNETÉ ============================
    const ANCIENNETE_DEBUT_MOIS = 24;
    const ANCIENNETE_TAUX_DEPART = 2;
    const ANCIENNETE_MAJORATION = 1;
    const ANCIENNETE_PLAFOND = 25;

    // ========================= GRATIFICATION =================================
    const GRATIFICATION_TAUX_MAX = 0.75;

    // ========================= HEURES SUPPLÉMENTAIRES ========================
    const HS_TAUX_15  = 0.15;
    const HS_TAUX_50  = 0.50;
    const HS_TAUX_75  = 0.75;
    const HS_TAUX_100 = 1.00;

    // ========================= PENSION EXONÉRATION (Art. 116-9) ==============
    const PENSION_EXONEREE = 320000;   // Fraction exonérée mensuelle pour retraités

    // ========================= IBS - BARÈME MENSUEL (Art. 119 bis CGI) =======
    // Ordonnance n° 2023-719 du 13 septembre 2023
    const IBS_TRANCHES = [
        ['min' => 0,       'max' => 75000,    'taux' => 0.00],
        ['min' => 75000,   'max' => 240000,   'taux' => 0.16],
        ['min' => 240000,  'max' => 800000,   'taux' => 0.21],
        ['min' => 800000,  'max' => 2400000,  'taux' => 0.24],
        ['min' => 2400000, 'max' => 8000000,  'taux' => 0.28],
        ['min' => 8000000, 'max' => PHP_INT_MAX, 'taux' => 0.32],
    ];

    // ========================= RICF - Barème (Art. 120 CGI) ==================
    // 5 500 FCFA par demi-part au-delà de 1 part = 11 000 × (N - 1) par mois
    const RICF_PAR_DEMI_PART = 5500;    // 5 500 F CFA par demi-part
    const RICF_MAX_PARTS = 5.0;

    /**
     * Calculer l'IBS (Impôt Brut sur Salaire) selon le barème Art. 119 bis
     *
     * @param float $sbi  Salaire Brut Imposable mensuel
     * @return float  Montant IBS
     */
    public static function calculerIBS($sbi)
    {
        $ibs = 0;
        foreach (self::IBS_TRANCHES as $tr) {
            if ($sbi <= $tr['min']) break;
            $base = min($sbi, $tr['max']) - $tr['min'];
            if ($base > 0) {
                $ibs += $base * $tr['taux'];
            }
        }
        return round($ibs);
    }

    /**
     * Calculer la RICF (Réduction d'Impôt pour Charges de Famille)
     * Art. 120 CGI - 5 500 FCFA par demi-part au-delà de 1 part
     *
     * Formule : RICF = 11 000 × (N - 1) par mois
     * Où N = nombre de parts (max 5)
     *
     * @param float $nombreParts  Nombre de parts fiscales
     * @return float  Montant RICF mensuel
     */
    public static function calculerRICF($nombreParts)
    {
        if ($nombreParts <= 1.0) return 0;
        $nombreParts = min($nombreParts, self::RICF_MAX_PARTS);
        // Nombre de demi-parts au-delà de 1 part
        $demiParts = ($nombreParts - 1.0) * 2;
        return round($demiParts * self::RICF_PAR_DEMI_PART);
    }

    /**
     * Calculer l'ITS final = IBS - RICF
     * Si RICF > IBS, ITS = 0 (excédent non remboursable)
     *
     * @param float $sbi  Salaire Brut Imposable
     * @param float $nombreParts  Nombre de parts
     * @return array ['ibs' => ..., 'ricf' => ..., 'its' => ...]
     */
    public static function calculerITS($sbi, $nombreParts)
    {
        $ibs = self::calculerIBS($sbi);
        $ricf = self::calculerRICF($nombreParts);
        $its = max(0, $ibs - $ricf);
        return [
            'ibs'  => $ibs,
            'ricf' => $ricf,
            'its'  => $its,
        ];
    }

    /**
     * Calculer la prime d'ancienneté à partir de la date d'embauche
     *
     * @param float  $salaireCateg   Salaire catégoriel (de base)
     * @param string $dateEmbauche   Date d'embauche (YYYY-MM-DD)
     * @param string $dateReference  Date de référence (YYYY-MM-DD), défaut = aujourd'hui
     * @return array ['mois' => int, 'taux' => float, 'montant' => float]
     */
    public static function calculerPrimeAnciennete($salaireCateg, $dateEmbauche, $dateReference = '')
    {
        if (empty($dateEmbauche)) {
            return ['mois' => 0, 'taux' => 0, 'montant' => 0];
        }

        if (empty($dateReference)) {
            $dateReference = date('Y-m-d');
        }

        $d1 = new DateTime($dateEmbauche);
        $d2 = new DateTime($dateReference);
        $diff = $d1->diff($d2);
        $ancienneteMois = ($diff->y * 12) + $diff->m;
        if ($diff->invert) $ancienneteMois = 0; // Date future

        if ($ancienneteMois < self::ANCIENNETE_DEBUT_MOIS) {
            return ['mois' => $ancienneteMois, 'taux' => 0, 'montant' => 0];
        }

        $anneesSupp = floor(($ancienneteMois - self::ANCIENNETE_DEBUT_MOIS) / 12);
        $taux = self::ANCIENNETE_TAUX_DEPART + ($anneesSupp * self::ANCIENNETE_MAJORATION);
        $taux = min($taux, self::ANCIENNETE_PLAFOND);
        $montant = round($salaireCateg * $taux / 100);

        return ['mois' => $ancienneteMois, 'taux' => $taux, 'montant' => $montant];
    }

    /**
     * Montant transport exonéré selon la ville
     */
    public static function getTransportExonere($ville = 'abidjan')
    {
        switch (strtolower(trim($ville))) {
            case 'abidjan': return self::TRANSPORT_EXONERE_ABIDJAN;
            case 'bouake':
            case 'bouaké': return self::TRANSPORT_EXONERE_BOUAKE;
            default: return self::TRANSPORT_EXONERE_AUTRES;
        }
    }

    /**
     * Nombre de parts fiscales (Art. 120-2° CGI)
     */
    public static function getNombreParts($situation, $nbEnfants = 0)
    {
        $parts = 1.0;
        switch (strtolower(trim($situation))) {
            case 'marie': case 'marié': case 'mariee': case 'mariée':
                $parts = 2.0;
                break;
            case 'veuf': case 'veuve':
                $parts = 1.0;
                break;
            case 'divorce': case 'divorcé': case 'divorcée':
                $parts = 1.0;
                break;
            default: // célibataire
                $parts = 1.0;
                break;
        }
        $parts += ($nbEnfants * 0.5);
        return min($parts, self::RICF_MAX_PARTS);
    }

    /**
     * Taux de la contribution employeur selon le type de personnel
     */
    public static function getTauxContributionEmployeur($isExpatrie = false)
    {
        return $isExpatrie ? self::CONTRIBUTION_EMPLOYEUR_EXPATRIE : self::CONTRIBUTION_EMPLOYEUR_LOCAL;
    }

    /**
     * Calcul complet V4 du bulletin de paie
     * Conforme à la réforme ITS 2024 (Ordonnance n° 2023-719)
     *
     * @param array $p  Tous les paramètres
     * @return array    Résultat complet
     */
    public static function calculerBulletin($p)
    {
        $r = [];

        // ====== 1. ÉLÉMENTS DE RÉMUNÉRATION ======
        $salaire_base       = floatval($p['salaire_base'] ?? 0);
        $sursalaire         = floatval($p['sursalaire'] ?? 0);

        // Primes CCI
        $prime_anc          = floatval($p['prime_anciennete'] ?? 0);
        $prime_rendement    = floatval($p['prime_rendement'] ?? 0);
        $prime_technicite   = floatval($p['prime_technicite'] ?? 0);
        $prime_fonction     = floatval($p['prime_fonction'] ?? 0);
        $prime_resp         = floatval($p['prime_responsabilite'] ?? 0);
        $prime_risque       = floatval($p['prime_risque'] ?? 0);
        $prime_outillage    = floatval($p['prime_outillage'] ?? 0);
        $prime_salissure    = floatval($p['prime_salissure'] ?? 0);
        $prime_caisse       = floatval($p['prime_caisse'] ?? 0);
        $prime_assiduite    = floatval($p['prime_assiduite'] ?? 0);
        $prime_panier       = floatval($p['prime_panier'] ?? 0);
        $gratification      = floatval($p['gratification'] ?? 0);

        // Indemnités
        $indem_transport    = floatval($p['indemnite_transport'] ?? 0);
        $indem_logement     = floatval($p['indemnite_logement'] ?? 0);
        $indem_represent    = floatval($p['indemnite_representation'] ?? 0);
        $indem_expat        = floatval($p['indemnite_expatriation'] ?? 0);
        $indem_deplac       = floatval($p['indemnite_deplacement'] ?? 0);
        $indem_km           = floatval($p['indemnite_kilometrique'] ?? 0);

        // Avantages en nature
        $an_logement        = floatval($p['avantage_nature_logement'] ?? 0);
        $an_vehicule        = floatval($p['avantage_nature_vehicule'] ?? 0);
        $an_domestique      = floatval($p['avantage_nature_domestique'] ?? 0);
        $an_nourriture      = floatval($p['avantage_nature_nourriture'] ?? 0);
        $an_autres          = floatval($p['avantage_nature_autres'] ?? 0);
        $total_an = $an_logement + $an_vehicule + $an_domestique + $an_nourriture + $an_autres;

        // Heures supplémentaires
        $hs_15              = floatval($p['heures_sup_15'] ?? 0);
        $hs_50              = floatval($p['heures_sup_50'] ?? 0);
        $hs_75              = floatval($p['heures_sup_75'] ?? 0);
        $hs_100             = floatval($p['heures_sup_100'] ?? 0);

        // Autres
        $conges_payes       = floatval($p['conges_payes'] ?? 0);
        $autres_primes      = floatval($p['autres_primes'] ?? 0);

        // Paramètres
        $situation   = $p['situation_familiale'] ?? 'celibataire';
        $nb_enfants  = intval($p['nombre_enfants'] ?? 0);
        $taux_at     = floatval($p['taux_at'] ?? 0.02);
        $ville       = $p['ville'] ?? 'abidjan';
        $is_expatrie = !empty($p['is_expatrie']);
        $date_embauche = $p['date_embauche'] ?? '';
        $nombre_parts = self::getNombreParts($situation, $nb_enfants);

        // Calcul automatique prime d'ancienneté si date_embauche fournie
        if (!empty($date_embauche) && empty($p['prime_anciennete'])) {
            $anc = self::calculerPrimeAnciennete($salaire_base, $date_embauche);
            $prime_anc = $anc['montant'];
            $r['anciennete_mois'] = $anc['mois'];
            $r['anciennete_taux'] = $anc['taux'];
        } else {
            $r['anciennete_mois'] = intval($p['anciennete_mois'] ?? 0);
            $r['anciennete_taux'] = 0;
        }

        // ====== 2. CALCUL DU SALAIRE BRUT ======
        $salaire_brut = $salaire_base + $sursalaire
            + $prime_anc + $prime_rendement + $prime_technicite
            + $prime_fonction + $prime_resp + $prime_risque
            + $prime_outillage + $prime_salissure + $prime_caisse
            + $prime_assiduite + $prime_panier + $gratification
            + $indem_transport + $indem_logement + $indem_represent
            + $indem_expat + $indem_deplac + $indem_km
            + $total_an
            + $hs_15 + $hs_50 + $hs_75 + $hs_100
            + $conges_payes + $autres_primes;

        $r['salaire_brut'] = round($salaire_brut);

        // ====== 3. BRUT IMPOSABLE ======
        $plafond_transport = self::getTransportExonere($ville);
        $transport_non_imposable = min($indem_transport, $plafond_transport);
        $r['transport_non_imposable'] = round($transport_non_imposable);

        $brut_imposable = $salaire_brut - $transport_non_imposable;
        $r['brut_imposable'] = round($brut_imposable);

        // ====== 4. COTISATIONS CNPS ======
        $assiette_cnps = $salaire_brut - $indem_deplac - $indem_km;
        $assiette_cnps = max($assiette_cnps, self::SMIG);

        $assiette_retraite = min($assiette_cnps, self::CNPS_PLAFOND_RETRAITE);
        $cnps_retraite_sal = round($assiette_retraite * self::CNPS_RETRAITE_SAL);
        $cnps_retraite_pat = round($assiette_retraite * self::CNPS_RETRAITE_PAT);

        $assiette_pf = min($assiette_cnps, self::CNPS_PLAFOND_PF);
        $cnps_pf_pat = round($assiette_pf * self::CNPS_PF_PAT);
        $cnps_at_pat = round($assiette_pf * $taux_at);

        $cmu_sal = self::CMU_MENSUEL;
        $cmu_pat = self::CMU_MENSUEL;

        $r['cnps_retraite_sal'] = $cnps_retraite_sal;
        $r['cmu_sal'] = $cmu_sal;
        $r['cnps_retraite_pat'] = $cnps_retraite_pat;
        $r['cnps_pf_pat'] = $cnps_pf_pat;
        $r['cnps_at_pat'] = $cnps_at_pat;
        $r['cmu_pat'] = $cmu_pat;

        $total_charges_sociales = $cnps_retraite_pat + $cnps_pf_pat + $cnps_at_pat + $cmu_pat;
        $r['total_charges_sociales'] = $total_charges_sociales;

        // ====== 5. CHARGES FISCALES PATRONALES (Réforme 2024) ======
        $taux_contrib = self::getTauxContributionEmployeur($is_expatrie);
        $contribution_employeur = round($brut_imposable * $taux_contrib);
        $fdfp_ta = round($brut_imposable * self::FDFP_TA_RATE);
        $fdfp_fpc = round($brut_imposable * self::FDFP_FPC_RATE);
        $total_charges_fiscales = $contribution_employeur + $fdfp_ta + $fdfp_fpc;

        $r['contribution_employeur'] = $contribution_employeur;
        $r['taux_contribution_employeur'] = $taux_contrib;
        $r['is_expatrie'] = $is_expatrie;
        $r['fdfp_ta'] = $fdfp_ta;
        $r['fdfp_fpc'] = $fdfp_fpc;
        $r['total_charges_fiscales'] = $total_charges_fiscales;

        // ====== 6. ITS NOUVEAU RÉGIME (Ordonnance 2023-719) ======
        // Base = Salaire Brut Imposable (SBI) directement, SANS abattement de 20%
        $its_result = self::calculerITS($brut_imposable, $nombre_parts);

        $r['its_ibs']   = $its_result['ibs'];
        $r['its_ricf']  = $its_result['ricf'];
        $r['its_total'] = $its_result['its'];

        // ====== 7. TOTAUX ======
        $total_retenues_sal = $cnps_retraite_sal + $cmu_sal + $its_result['its'];
        $r['total_retenues_sal'] = $total_retenues_sal;

        $total_charges_pat = $total_charges_sociales + $total_charges_fiscales;
        $r['total_charges_pat'] = $total_charges_pat;

        $salaire_net_imposable = round($salaire_brut - $cnps_retraite_sal - $cmu_sal);
        $r['salaire_net_imposable'] = $salaire_net_imposable;

        $salaire_net = round($salaire_brut - $total_retenues_sal);
        $r['salaire_net'] = $salaire_net;

        // ====== 8. DÉDUCTIONS DIVERSES ======
        $avance       = floatval($p['avance_salaire'] ?? 0);
        $pret         = floatval($p['pret_deduction'] ?? 0);
        $pension_alim = floatval($p['pension_alimentaire'] ?? 0);
        $saisie       = floatval($p['saisie_arret'] ?? 0);
        $mutuelle     = floatval($p['mutuelle_complementaire'] ?? 0);
        $autres_ret   = floatval($p['autres_retenues'] ?? 0);

        $total_deductions = $avance + $pret + $pension_alim + $saisie + $mutuelle + $autres_ret;
        $net_a_payer = round($salaire_net - $total_deductions);

        $r['avance_salaire'] = $avance;
        $r['pret_deduction'] = $pret;
        $r['pension_alimentaire'] = $pension_alim;
        $r['saisie_arret'] = $saisie;
        $r['mutuelle_complementaire'] = $mutuelle;
        $r['autres_retenues'] = $autres_ret;
        $r['net_a_payer'] = $net_a_payer;
        $r['nombre_parts'] = $nombre_parts;
        $r['prime_anciennete'] = $prime_anc;

        return $r;
    }

    /**
     * Calcul du salaire réel horaire (base heures sup.)
     */
    public static function calculerSalaireReelHoraire($params)
    {
        $base_hs = floatval($params['salaire_base'] ?? 0)
                 + floatval($params['sursalaire'] ?? 0)
                 + floatval($params['prime_technicite'] ?? 0)
                 + floatval($params['prime_rendement'] ?? 0)
                 + floatval($params['prime_fonction'] ?? 0)
                 + floatval($params['prime_responsabilite'] ?? 0);
        return round($base_hs / 173.33);
    }

    /**
     * Libellé situation familiale
     */
    public static function getLibelleSituation($situation, $nbEnfants)
    {
        $lib = '';
        switch (strtolower(trim($situation))) {
            case 'celibataire': case 'célibataire': $lib = 'Célibataire'; break;
            case 'marie': case 'marié': $lib = 'Marié(e)'; break;
            case 'divorce': case 'divorcé': $lib = 'Divorcé(e)'; break;
            case 'veuf': case 'veuve': $lib = 'Veuf/Veuve'; break;
            default: $lib = ucfirst($situation);
        }
        if ($nbEnfants > 0) $lib .= ', '.$nbEnfants.' enfant'.($nbEnfants > 1 ? 's' : '');
        return $lib;
    }

    /**
     * Secteurs d'activité et taux AT
     */
    public static function getSecteursActivite()
    {
        return [
            'commerce'    => ['label' => 'Commerce', 'taux' => 2.0],
            'services'    => ['label' => 'Services', 'taux' => 2.0],
            'industrie'   => ['label' => 'Industrie légère', 'taux' => 3.0],
            'btp'         => ['label' => 'BTP / Construction', 'taux' => 4.0],
            'agriculture' => ['label' => 'Agriculture', 'taux' => 3.0],
            'mines'       => ['label' => 'Mines / Extraction', 'taux' => 5.0],
            'transport'   => ['label' => 'Transport', 'taux' => 3.5],
            'petrole'     => ['label' => 'Pétrole / Énergie', 'taux' => 4.0],
            'banque'      => ['label' => 'Banque / Assurance', 'taux' => 2.0],
            'telecom'     => ['label' => 'Télécommunications', 'taux' => 2.0],
            'sante'       => ['label' => 'Santé', 'taux' => 3.0],
            'hotellerie'  => ['label' => 'Hôtellerie / Restauration', 'taux' => 2.5],
            'autre'       => ['label' => 'Autre', 'taux' => 2.0],
        ];
    }

    /**
     * Liste des villes pour le transport exonéré
     */
    public static function getVilles()
    {
        return [
            'abidjan'     => ['label' => 'Abidjan', 'plafond' => self::TRANSPORT_EXONERE_ABIDJAN],
            'bouake'      => ['label' => 'Bouaké', 'plafond' => self::TRANSPORT_EXONERE_BOUAKE],
            'yamoussoukro'=> ['label' => 'Yamoussoukro', 'plafond' => self::TRANSPORT_EXONERE_AUTRES],
            'san_pedro'   => ['label' => 'San-Pédro', 'plafond' => self::TRANSPORT_EXONERE_AUTRES],
            'korhogo'     => ['label' => 'Korhogo', 'plafond' => self::TRANSPORT_EXONERE_AUTRES],
            'daloa'       => ['label' => 'Daloa', 'plafond' => self::TRANSPORT_EXONERE_AUTRES],
            'autre'       => ['label' => 'Autre ville', 'plafond' => self::TRANSPORT_EXONERE_AUTRES],
        ];
    }

    /**
     * Détail du calcul IBS par tranche (pour affichage dans le bulletin PDF)
     */
    public static function getDetailIBS($sbi)
    {
        $details = [];
        foreach (self::IBS_TRANCHES as $tr) {
            if ($sbi <= $tr['min']) break;
            $base = min($sbi, $tr['max']) - $tr['min'];
            if ($base > 0) {
                $montant = round($base * $tr['taux']);
                $details[] = [
                    'min'     => $tr['min'],
                    'max'     => min($sbi, $tr['max']),
                    'base'    => $base,
                    'taux'    => $tr['taux'],
                    'montant' => $montant,
                ];
            }
        }
        return $details;
    }
}
