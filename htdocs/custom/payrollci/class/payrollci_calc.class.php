<?php
/* ============================================================================
 * PayrollCI - Moteur de calcul de la paie ivoirienne
 * Conforme au droit du travail de Côte d'Ivoire (2026)
 *
 * Composantes ITS :
 *   - IS  (Impôt sur Salaires)         : proportionnel
 *   - CN  (Contribution Nationale)      : progressif 4 tranches
 *   - IGR (Impôt Général sur le Revenu) : progressif 8 tranches + quotient familial
 *
 * Cotisations sociales CNPS :
 *   - Retraite : 14% (6,3% salarié + 7,7% employeur)
 *   - Prestations familiales : 5,75% employeur (dont 0,75% maternité)
 *   - Accidents du travail : 2-5% employeur selon secteur
 *   - CMU : 500 FCFA/mois chacun
 *
 * Sources :
 *   - Code Général des Impôts de Côte d'Ivoire
 *   - Code de Prévoyance Sociale (CNPS)
 *   - Convention Collective Interprofessionnelle (CCI)
 *   - cleiss.fr/docs/cotisations/cotedivoire.html (2025)
 * ============================================================================
 */

class PayrollCICalc
{
    // ========================= CONSTANTES CNPS 2026 =========================

    /** Taux retraite part salariale */
    const CNPS_RETRAITE_SAL = 0.063;
    /** Taux retraite part patronale */
    const CNPS_RETRAITE_PAT = 0.077;
    /** Plafond mensuel retraite (45 × SMIG) */
    const CNPS_PLAFOND_RETRAITE = 3375000;

    /** Taux prestations familiales patronal (inclut 0,75% maternité) */
    const CNPS_PF_PAT = 0.0575;
    /** Plafond mensuel PF/Maternité/AT */
    const CNPS_PLAFOND_PF = 70000;

    /** CMU mensuelle par personne */
    const CMU_MENSUEL = 500;

    /** SMIG mensuel (depuis 01/01/2023) */
    const SMIG = 75000;

    // ========================= CONSTANTES ITS 2026 ==========================

    /** Taux IS (Impôt sur Salaires) */
    const IS_RATE = 0.015;

    /** Abattement sur salaire brut pour IS/CN (80% du brut imposable) */
    const ABATTEMENT_BRUT = 0.80;

    /** Abattement frais professionnels pour IGR (15%) */
    const ABATTEMENT_FRAIS_PRO = 0.15;

    /**
     * Tranches CN (Contribution Nationale) - mensuelles
     * Sur 80% du salaire brut imposable
     */
    const CN_TRANCHES = [
        ['min' => 0,      'max' => 50000,   'taux' => 0],
        ['min' => 50000,  'max' => 130000,  'taux' => 0.015],
        ['min' => 130000, 'max' => 200000,  'taux' => 0.05],
        ['min' => 200000, 'max' => PHP_INT_MAX, 'taux' => 0.10],
    ];

    /**
     * Formules IGR mensuelles (sur le quotient Q = R/N)
     * Format : [seuil_max_Q, numerateur, denominateur, credit_par_part]
     * IGR = R × (num/den) − credit × N
     */
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
     * Nombre de parts selon situation familiale et enfants
     *
     * @param string $situation  celibataire|marie|divorce|veuf
     * @param int    $nbEnfants  nombre d'enfants à charge
     * @return float nombre de parts (max 5)
     */
    public static function getNombreParts($situation, $nbEnfants = 0)
    {
        $parts = 1.0;

        switch (strtolower($situation)) {
            case 'marie':
            case 'marié':
                $parts = 2.0;
                break;
            case 'veuf':
            case 'veuve':
                $parts = 1.0;
                break;
            case 'divorce':
            case 'divorcé':
            case 'divorcée':
                $parts = 1.0;
                break;
            case 'celibataire':
            case 'célibataire':
            default:
                $parts = 1.0;
                break;
        }

        $parts += ($nbEnfants * 0.5);

        return min($parts, 5.0);
    }

    /**
     * Calcul complet du bulletin de paie
     *
     * @param array $params Paramètres du calcul
     *   - salaire_base        (float) Salaire de base mensuel
     *   - prime_anciennete    (float) Prime d'ancienneté
     *   - prime_transport     (float) Indemnité de transport
     *   - prime_logement      (float) Indemnité de logement
     *   - prime_responsabilite(float) Prime de responsabilité
     *   - prime_salissure     (float) Prime de salissure
     *   - heures_sup_25       (float) Montant heures sup 25%
     *   - heures_sup_50       (float) Montant heures sup 50%
     *   - autres_primes       (float) Autres primes
     *   - conges_payes        (float) Congés payés
     *   - situation_familiale (string) Situation familiale
     *   - nombre_enfants      (int) Nombre d'enfants
     *   - taux_at             (float) Taux accident du travail (0.02 à 0.05)
     *   - avance_salaire      (float) Avance sur salaire
     *   - pret_deduction      (float) Remboursement de prêt
     *   - autres_retenues     (float) Autres retenues
     *
     * @return array Résultat complet du calcul
     */
    public static function calculerBulletin($params)
    {
        $result = [];

        // ====== 1. SALAIRE BRUT ======
        $salaire_base    = floatval($params['salaire_base'] ?? 0);
        $prime_anc       = floatval($params['prime_anciennete'] ?? 0);
        $prime_transport = floatval($params['prime_transport'] ?? 0);
        $prime_logement  = floatval($params['prime_logement'] ?? 0);
        $prime_resp      = floatval($params['prime_responsabilite'] ?? 0);
        $prime_salis     = floatval($params['prime_salissure'] ?? 0);
        $heures_sup_25   = floatval($params['heures_sup_25'] ?? 0);
        $heures_sup_50   = floatval($params['heures_sup_50'] ?? 0);
        $autres_primes   = floatval($params['autres_primes'] ?? 0);
        $conges_payes    = floatval($params['conges_payes'] ?? 0);

        $salaire_brut = $salaire_base + $prime_anc + $prime_transport
                      + $prime_logement + $prime_resp + $prime_salis
                      + $heures_sup_25 + $heures_sup_50
                      + $autres_primes + $conges_payes;

        $result['salaire_brut'] = round($salaire_brut);

        // ====== 2. COTISATIONS CNPS ======
        $situation = $params['situation_familiale'] ?? 'celibataire';
        $nb_enfants = intval($params['nombre_enfants'] ?? 0);
        $taux_at = floatval($params['taux_at'] ?? 0.02);
        $nombre_parts = self::getNombreParts($situation, $nb_enfants);

        // Part salariale - Retraite
        $assiette_retraite = min($salaire_brut, self::CNPS_PLAFOND_RETRAITE);
        $cnps_retraite_sal = round($assiette_retraite * self::CNPS_RETRAITE_SAL);

        // CMU salariale
        $cmu_sal = self::CMU_MENSUEL;

        // Part patronale
        $cnps_retraite_pat = round($assiette_retraite * self::CNPS_RETRAITE_PAT);
        $assiette_pf = min($salaire_brut, self::CNPS_PLAFOND_PF);
        $cnps_pf_pat = round($assiette_pf * self::CNPS_PF_PAT);
        $cnps_at_pat = round($assiette_pf * $taux_at);
        $cmu_pat = self::CMU_MENSUEL;

        $result['cnps_retraite_sal'] = $cnps_retraite_sal;
        $result['cmu_sal'] = $cmu_sal;
        $result['cnps_retraite_pat'] = $cnps_retraite_pat;
        $result['cnps_pf_pat'] = $cnps_pf_pat;
        $result['cnps_at_pat'] = $cnps_at_pat;
        $result['cmu_pat'] = $cmu_pat;

        // ====== 3. ITS (Impôts sur Traitements et Salaires) ======

        // 3a. Salaire brut imposable × 80%
        $salaire_mensuel = round($salaire_brut * self::ABATTEMENT_BRUT);

        // 3b. IS (Impôt sur Salaires) = 1,5% du salaire mensuel
        $its_is = round($salaire_mensuel * self::IS_RATE);

        // 3c. CN (Contribution Nationale) - progressif sur salaire mensuel
        $its_cn = self::calculerCN($salaire_mensuel);

        // 3d. IGR (Impôt Général sur le Revenu)
        // R = (salaire_mensuel - IS - CN) × 85%
        $r = round(($salaire_mensuel - $its_is - $its_cn) * (1 - self::ABATTEMENT_FRAIS_PRO));
        $its_igr = self::calculerIGR($r, $nombre_parts);

        $its_total = $its_is + $its_cn + $its_igr;

        $result['its_is'] = $its_is;
        $result['its_cn'] = $its_cn;
        $result['its_igr'] = $its_igr;
        $result['its_total'] = $its_total;

        // ====== 4. TOTAUX ======

        // Total retenues salariales
        $total_retenues_sal = $cnps_retraite_sal + $cmu_sal + $its_total;
        $result['total_retenues_sal'] = $total_retenues_sal;

        // Total charges patronales
        $total_charges_pat = $cnps_retraite_pat + $cnps_pf_pat + $cnps_at_pat + $cmu_pat;
        $result['total_charges_pat'] = $total_charges_pat;

        // Salaire net imposable = brut - CNPS salariale
        $salaire_net_imposable = round($salaire_brut - $cnps_retraite_sal - $cmu_sal);
        $result['salaire_net_imposable'] = $salaire_net_imposable;

        // Salaire net = brut - toutes retenues salariales
        $salaire_net = round($salaire_brut - $total_retenues_sal);
        $result['salaire_net'] = $salaire_net;

        // Avances et déductions
        $avance     = floatval($params['avance_salaire'] ?? 0);
        $pret       = floatval($params['pret_deduction'] ?? 0);
        $autres_ret = floatval($params['autres_retenues'] ?? 0);

        $net_a_payer = round($salaire_net - $avance - $pret - $autres_ret);

        $result['avance_salaire']   = $avance;
        $result['pret_deduction']   = $pret;
        $result['autres_retenues']  = $autres_ret;
        $result['net_a_payer']      = $net_a_payer;
        $result['nombre_parts']     = $nombre_parts;

        return $result;
    }

    /**
     * Calcul de la Contribution Nationale (CN)
     * Barème progressif sur 80% du salaire brut imposable
     *
     * @param float $salaireMensuel  80% du salaire brut
     * @return int  Montant CN arrondi
     */
    public static function calculerCN($salaireMensuel)
    {
        $cn = 0;
        foreach (self::CN_TRANCHES as $tranche) {
            if ($salaireMensuel <= $tranche['min']) break;
            $base = min($salaireMensuel, $tranche['max']) - $tranche['min'];
            if ($base > 0) {
                $cn += $base * $tranche['taux'];
            }
        }
        return round($cn);
    }

    /**
     * Calcul de l'IGR (Impôt Général sur le Revenu)
     * Utilise le quotient familial et les formules officielles
     *
     * @param float $R  Revenu net imposable mensuel = (80%×Brut - IS - CN) × 85%
     * @param float $N  Nombre de parts fiscales
     * @return int  Montant IGR arrondi (≥ 0)
     */
    public static function calculerIGR($R, $N)
    {
        if ($R <= 0 || $N <= 0) return 0;

        $Q = $R / $N;

        foreach (self::IGR_FORMULES as $formule) {
            if ($Q <= $formule['max_q']) {
                if ($formule['num'] == 0) return 0;

                $igr = ($R * $formule['num'] / $formule['den']) - ($formule['credit'] * $N);
                return max(0, round($igr));
            }
        }

        return 0;
    }

    /**
     * Retourne le libellé des parts
     */
    public static function getLibelleSituation($situation, $nbEnfants)
    {
        $lib = '';
        switch (strtolower($situation)) {
            case 'celibataire':
            case 'célibataire':
                $lib = 'Célibataire';
                break;
            case 'marie':
            case 'marié':
                $lib = 'Marié(e)';
                break;
            case 'divorce':
            case 'divorcé':
                $lib = 'Divorcé(e)';
                break;
            case 'veuf':
            case 'veuve':
                $lib = 'Veuf/Veuve';
                break;
            default:
                $lib = ucfirst($situation);
        }
        if ($nbEnfants > 0) {
            $lib .= ', '.$nbEnfants.' enfant'.($nbEnfants > 1 ? 's' : '');
        }
        return $lib;
    }

    /**
     * Liste des secteurs d'activité et taux AT correspondants
     */
    public static function getSecteursActivite()
    {
        return [
            'commerce'      => ['label' => 'Commerce', 'taux' => 2.0],
            'services'      => ['label' => 'Services', 'taux' => 2.0],
            'industrie'     => ['label' => 'Industrie légère', 'taux' => 3.0],
            'btp'           => ['label' => 'BTP / Construction', 'taux' => 4.0],
            'agriculture'   => ['label' => 'Agriculture', 'taux' => 3.0],
            'mines'         => ['label' => 'Mines / Extraction', 'taux' => 5.0],
            'transport'     => ['label' => 'Transport', 'taux' => 3.5],
            'autre'         => ['label' => 'Autre', 'taux' => 2.0],
        ];
    }
}
