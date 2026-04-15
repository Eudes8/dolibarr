<?php
/* ============================================================================
 * PayrollCI v2 - Moteur de calcul complet de la paie ivoirienne
 * Conforme au droit du travail de Côte d'Ivoire (2026)
 *
 * Éléments couverts :
 * ─── GAINS ───
 *  Salaire de base + Sursalaire + 12 primes CCI + 6 indemnités
 *  + 5 avantages en nature + 4 types d'heures sup. (15%/50%/75%/100%)
 *  + Gratification/13ème mois + Congés payés
 *
 * ─── COTISATIONS SOCIALES CNPS ───
 *  Retraite 14% (6,3% sal + 7,7% pat), plafond 3 375 000 F
 *  PF 5,75% pat, AT 2-5% pat, plafond 70 000 F
 *  CMU 500 F/mois chacun
 *
 * ─── CHARGES FISCALES PATRONALES ───
 *  Impôt Employeur (IE) 1,2% du brut imposable
 *  FDFP/TA (Taxe d'Apprentissage) 0,4% de la masse salariale
 *  FDFP/FPC (Formation Prof. Continue) 0,6% de la masse salariale
 *
 * ─── ITS (Impôts sur Traitements et Salaires) ───
 *  IS 1,5% sur 80% brut imposable
 *  CN progressif 4 tranches sur 80% brut imposable
 *  IGR progressif 8 tranches avec quotient familial
 *
 * ─── RETENUES ───
 *  Avance, prêt, pension alimentaire, saisie-arrêt, mutuelle
 *
 * Sources : CGI CI, Code Prévoyance Sociale, CCI, FDFP, CNPS
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
    const IMPOT_EMPLOYEUR_RATE = 0.012;   // 1,2% du brut imposable
    const FDFP_TA_RATE = 0.004;           // 0,4% de la masse salariale
    const FDFP_FPC_RATE = 0.006;          // 0,6% de la masse salariale

    // ========================= CONSTANTES ITS 2026 ==========================
    const IS_RATE = 0.015;
    const ABATTEMENT_BRUT = 0.80;
    const ABATTEMENT_FRAIS_PRO = 0.15;

    // ========================= TRANSPORT NON IMPOSABLE =======================
    const TRANSPORT_EXONERE_ABIDJAN = 30000;
    const TRANSPORT_EXONERE_BOUAKE  = 24000;
    const TRANSPORT_EXONERE_AUTRES  = 20000;

    // ========================= PRIME D'ANCIENNETÉ ============================
    const ANCIENNETE_DEBUT_MOIS = 24;   // 2% après 24 mois
    const ANCIENNETE_TAUX_DEPART = 2;   // 2%
    const ANCIENNETE_MAJORATION = 1;    // +1% par 12 mois
    const ANCIENNETE_PLAFOND = 25;      // Max 25%

    // ========================= GRATIFICATION =================================
    const GRATIFICATION_TAUX_MAX = 0.75; // Max 75% du salaire catégoriel

    // ========================= HEURES SUPPLÉMENTAIRES ========================
    const HS_TAUX_15  = 0.15;  // 41ème à 46ème heure
    const HS_TAUX_50  = 0.50;  // Au-delà de 46h
    const HS_TAUX_75  = 0.75;  // Nuit OU Dimanche/Férié (heures de jour)
    const HS_TAUX_100 = 1.00;  // Nuit + Dimanche/Férié

    // ========================= CN TRANCHES ===================================
    const CN_TRANCHES = [
        ['min' => 0,      'max' => 50000,   'taux' => 0],
        ['min' => 50000,  'max' => 130000,  'taux' => 0.015],
        ['min' => 130000, 'max' => 200000,  'taux' => 0.05],
        ['min' => 200000, 'max' => PHP_INT_MAX, 'taux' => 0.10],
    ];

    // ========================= IGR FORMULES ==================================
    const IGR_FORMULES = [
        ['max_q' => 25000,    'num' => 0,  'den' => 1,   'credit' => 0],
        ['max_q' => 45583,    'num' => 10, 'den' => 110,  'credit' => 2273],
        ['max_q' => 81583,    'num' => 15, 'den' => 115,  'credit' => 4076],
        ['max_q' => 126583,   'num' => 20, 'den' => 120,  'credit' => 7031],
        ['max_q' => 220333,   'num' => 25, 'den' => 125,  'credit' => 11250],
        ['max_q' => 389083,   'num' => 35, 'den' => 135,  'credit' => 24306],
        ['max_q' => 842166,   'num' => 45, 'den' => 145,  'credit' => 44181],
        ['max_q' => PHP_INT_MAX, 'num' => 60, 'den' => 160, 'credit' => 98633],
    ];

    /**
     * Calculer la prime d'ancienneté automatiquement
     * @param float $salaireCateg  Salaire catégoriel (de base)
     * @param int   $ancienneteMois  Nombre de mois d'ancienneté
     * @return float  Montant de la prime
     */
    public static function calculerPrimeAnciennete($salaireCateg, $ancienneteMois)
    {
        if ($ancienneteMois < self::ANCIENNETE_DEBUT_MOIS) return 0;

        // 2% à 24 mois, +1% par tranche de 12 mois, max 25%
        $anneesSupp = floor(($ancienneteMois - self::ANCIENNETE_DEBUT_MOIS) / 12);
        $taux = self::ANCIENNETE_TAUX_DEPART + ($anneesSupp * self::ANCIENNETE_MAJORATION);
        $taux = min($taux, self::ANCIENNETE_PLAFOND);

        return round($salaireCateg * $taux / 100);
    }

    /**
     * Montant transport exonéré selon la ville
     */
    public static function getTransportExonere($ville = 'abidjan')
    {
        switch (strtolower($ville)) {
            case 'abidjan': return self::TRANSPORT_EXONERE_ABIDJAN;
            case 'bouake':
            case 'bouaké': return self::TRANSPORT_EXONERE_BOUAKE;
            default: return self::TRANSPORT_EXONERE_AUTRES;
        }
    }

    /**
     * Nombre de parts fiscales
     */
    public static function getNombreParts($situation, $nbEnfants = 0)
    {
        $parts = 1.0;
        switch (strtolower($situation)) {
            case 'marie': case 'marié': case 'mariee': case 'mariée':
                $parts = 2.0; break;
            case 'veuf': case 'veuve':
                $parts = 1.0; break;
            case 'divorce': case 'divorcé': case 'divorcée':
                $parts = 1.0; break;
            default:
                $parts = 1.0; break;
        }
        $parts += ($nbEnfants * 0.5);
        return min($parts, 5.0);
    }

    /**
     * Calcul complet V2 du bulletin de paie
     *
     * @param array $p  Tous les paramètres
     * @return array    Résultat complet
     */
    public static function calculerBulletin($p)
    {
        $r = [];

        // ====== 1. ÉLÉMENTS DE RÉMUNÉRATION ======

        // Salaire de base et sursalaire
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

        // Heures supplémentaires (taux CCI corrects)
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
        $nombre_parts = self::getNombreParts($situation, $nb_enfants);

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
        // Transport non imposable = min(indemnité transport, plafond ville)
        $plafond_transport = self::getTransportExonere($ville);
        $transport_non_imposable = min($indem_transport, $plafond_transport);
        $r['transport_non_imposable'] = round($transport_non_imposable);

        // Brut imposable = Brut - transport exonéré
        // (les indemnités ayant caractère de remboursement de frais peuvent être exclues)
        $brut_imposable = $salaire_brut - $transport_non_imposable;
        $r['brut_imposable'] = round($brut_imposable);

        // ====== 4. COTISATIONS CNPS ======

        // Assiette CNPS = salaire brut incluant avantages en nature (Art. 23 CPS)
        // sauf indemnités de remboursement de frais
        $assiette_cnps = $salaire_brut - $indem_deplac - $indem_km;
        $assiette_cnps = max($assiette_cnps, self::SMIG); // plancher = SMIG

        // Retraite
        $assiette_retraite = min($assiette_cnps, self::CNPS_PLAFOND_RETRAITE);
        $cnps_retraite_sal = round($assiette_retraite * self::CNPS_RETRAITE_SAL);
        $cnps_retraite_pat = round($assiette_retraite * self::CNPS_RETRAITE_PAT);

        // PF et AT (plafond 70 000)
        $assiette_pf = min($assiette_cnps, self::CNPS_PLAFOND_PF);
        $cnps_pf_pat = round($assiette_pf * self::CNPS_PF_PAT);
        $cnps_at_pat = round($assiette_pf * $taux_at);

        // CMU
        $cmu_sal = self::CMU_MENSUEL;
        $cmu_pat = self::CMU_MENSUEL;

        $r['cnps_retraite_sal'] = $cnps_retraite_sal;
        $r['cmu_sal'] = $cmu_sal;
        $r['cnps_retraite_pat'] = $cnps_retraite_pat;
        $r['cnps_pf_pat'] = $cnps_pf_pat;
        $r['cnps_at_pat'] = $cnps_at_pat;
        $r['cmu_pat'] = $cmu_pat;

        // Total charges sociales patronales
        $total_charges_sociales = $cnps_retraite_pat + $cnps_pf_pat + $cnps_at_pat + $cmu_pat;
        $r['total_charges_sociales'] = $total_charges_sociales;

        // ====== 5. CHARGES FISCALES PATRONALES ======
        $impot_employeur = round($brut_imposable * self::IMPOT_EMPLOYEUR_RATE);
        $fdfp_ta = round($brut_imposable * self::FDFP_TA_RATE);
        $fdfp_fpc = round($brut_imposable * self::FDFP_FPC_RATE);
        $total_charges_fiscales = $impot_employeur + $fdfp_ta + $fdfp_fpc;

        $r['impot_employeur'] = $impot_employeur;
        $r['fdfp_ta'] = $fdfp_ta;
        $r['fdfp_fpc'] = $fdfp_fpc;
        $r['total_charges_fiscales'] = $total_charges_fiscales;

        // ====== 6. ITS (Impôts sur Traitements et Salaires - part salariale) ======

        // Base = 80% du brut imposable
        $base_its = round($brut_imposable * self::ABATTEMENT_BRUT);

        // IS = 1,5% de la base
        $its_is = round($base_its * self::IS_RATE);

        // CN progressif
        $its_cn = self::calculerCN($base_its);

        // IGR : R = (base - IS - CN) × 85%
        $revenu_net = round(($base_its - $its_is - $its_cn) * (1 - self::ABATTEMENT_FRAIS_PRO));
        $its_igr = self::calculerIGR($revenu_net, $nombre_parts);

        $its_total = $its_is + $its_cn + $its_igr;

        $r['its_is'] = $its_is;
        $r['its_cn'] = $its_cn;
        $r['its_igr'] = $its_igr;
        $r['its_total'] = $its_total;

        // ====== 7. TOTAUX ======

        // Total retenues salariales = CNPS salarié + CMU salarié + ITS total
        $total_retenues_sal = $cnps_retraite_sal + $cmu_sal + $its_total;
        $r['total_retenues_sal'] = $total_retenues_sal;

        // Total charges patronales = sociales + fiscales
        $total_charges_pat = $total_charges_sociales + $total_charges_fiscales;
        $r['total_charges_pat'] = $total_charges_pat;

        // Salaire net imposable = brut - CNPS salariale - CMU salariale
        $salaire_net_imposable = round($salaire_brut - $cnps_retraite_sal - $cmu_sal);
        $r['salaire_net_imposable'] = $salaire_net_imposable;

        // Salaire net = brut - toutes retenues salariales
        $salaire_net = round($salaire_brut - $total_retenues_sal);
        $r['salaire_net'] = $salaire_net;

        // ====== 8. DÉDUCTIONS DIVERSES ======
        $avance             = floatval($p['avance_salaire'] ?? 0);
        $pret               = floatval($p['pret_deduction'] ?? 0);
        $pension_alim       = floatval($p['pension_alimentaire'] ?? 0);
        $saisie             = floatval($p['saisie_arret'] ?? 0);
        $mutuelle           = floatval($p['mutuelle_complementaire'] ?? 0);
        $autres_ret         = floatval($p['autres_retenues'] ?? 0);

        $total_deductions = $avance + $pret + $pension_alim + $saisie + $mutuelle + $autres_ret;

        // Net à payer = salaire net - déductions
        $net_a_payer = round($salaire_net - $total_deductions);

        $r['avance_salaire'] = $avance;
        $r['pret_deduction'] = $pret;
        $r['pension_alimentaire'] = $pension_alim;
        $r['saisie_arret'] = $saisie;
        $r['mutuelle_complementaire'] = $mutuelle;
        $r['autres_retenues'] = $autres_ret;
        $r['net_a_payer'] = $net_a_payer;
        $r['nombre_parts'] = $nombre_parts;

        return $r;
    }

    /**
     * Calcul de la Contribution Nationale (CN)
     */
    public static function calculerCN($salaireMensuel)
    {
        $cn = 0;
        foreach (self::CN_TRANCHES as $tranche) {
            if ($salaireMensuel <= $tranche['min']) break;
            $base = min($salaireMensuel, $tranche['max']) - $tranche['min'];
            if ($base > 0) $cn += $base * $tranche['taux'];
        }
        return round($cn);
    }

    /**
     * Calcul de l'IGR
     */
    public static function calculerIGR($R, $N)
    {
        if ($R <= 0 || $N <= 0) return 0;
        $Q = $R / $N;
        foreach (self::IGR_FORMULES as $f) {
            if ($Q <= $f['max_q']) {
                if ($f['num'] == 0) return 0;
                $igr = ($R * $f['num'] / $f['den']) - ($f['credit'] * $N);
                return max(0, round($igr));
            }
        }
        return 0;
    }

    /**
     * Calcul du salaire réel horaire (base heures sup.)
     * Inclut : catégoriel + sursalaire + technicité + rendement + fonction + responsabilité
     * Exclut : ancienneté, assiduité, transport, déplacement, logement, panier, outillage, salissure
     */
    public static function calculerSalaireReelHoraire($params)
    {
        $base_hs = floatval($params['salaire_base'] ?? 0)
                 + floatval($params['sursalaire'] ?? 0)
                 + floatval($params['prime_technicite'] ?? 0)
                 + floatval($params['prime_rendement'] ?? 0)
                 + floatval($params['prime_fonction'] ?? 0)
                 + floatval($params['prime_responsabilite'] ?? 0);
        return round($base_hs / 173.33); // 40h × 52 semaines / 12 mois ≈ 173,33h
    }

    /**
     * Libellé situation familiale
     */
    public static function getLibelleSituation($situation, $nbEnfants)
    {
        $lib = '';
        switch (strtolower($situation)) {
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
}
