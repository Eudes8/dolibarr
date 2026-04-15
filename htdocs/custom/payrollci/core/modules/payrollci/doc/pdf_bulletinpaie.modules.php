<?php
/* ============================================================================
 * PayrollCI v3 - Générateur PDF de bulletin de paie
 * Format conforme Art. 46.2 CCI de Côte d'Ivoire
 *
 * Mentions obligatoires : employeur, salarié, période, salaire, cotisations,
 *   avantages en nature, primes, retenues, net à payer
 * ============================================================================
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

class pdf_bulletinpaie
{
    public $db;
    public $page_largeur;
    public $page_hauteur;
    public $marge_gauche;
    public $marge_droite;
    public $marge_haute;
    public $marge_basse;

    public function __construct($db)
    {
        $this->db = $db;
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 15;
    }

    /**
     * Écrire le PDF
     */
    public function write_file($object, $outputlangs = null, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf, $mysoc;

        // V3: stocker dans un sous-répertoire par référence (standard Dolibarr)
        $dir = $conf->payrollci->dir_output.'/bulletins/'.dol_sanitizeFileName($object->ref);
        if (!is_dir($dir)) dol_mkdir($dir);
        $file = $dir.'/'.$object->ref.'.pdf';

        // Aussi garder dans le répertoire parent pour compatibilité V2
        $dirParent = $conf->payrollci->dir_output.'/bulletins';
        if (!is_dir($dirParent)) dol_mkdir($dirParent);
        $fileParent = $dirParent.'/'.$object->ref.'.pdf';

        $pdf = pdf_getInstance('', 'mm', 'A4');
        $pdf->SetAutoPageBreak(1, $this->marge_basse);
        $pdf->SetCreator("PayrollCI v3 Dolibarr");
        $pdf->SetTitle('Bulletin de Paie '.$object->ref);

        $pdf->Open();
        $pdf->AddPage();

        $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite; // 190mm

        // ═══════════ EN-TÊTE ═══════════
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($this->marge_gauche, $this->marge_haute);
        $pdf->Cell($w, 8, 'BULLETIN DE PAIE', 0, 1, 'C');

        // Infos employeur
        $pdf->SetFont('', '', 8);
        $y = 20;
        $pdf->SetXY($this->marge_gauche, $y);
        if (!empty($mysoc->name)) {
            $pdf->Cell(90, 4, 'Employeur: '.$mysoc->name, 0, 1);
            $y += 4;
            if ($mysoc->address) { $pdf->SetXY($this->marge_gauche, $y); $pdf->Cell(90, 4, $mysoc->address, 0, 1); $y += 4; }
        }

        // Réf / Période
        $moisList = payrollci_get_mois();
        $periode = ($moisList[intval(date('m', $object->date_start))] ?? '').' '.date('Y', $object->date_start);
        $pdf->SetXY($this->marge_gauche + 100, 20);
        $pdf->SetFont('', 'B', 9);
        $pdf->Cell(90, 4, 'Réf: '.$object->ref, 0, 1, 'R');
        $pdf->SetXY($this->marge_gauche + 100, 24);
        $pdf->SetFont('', '', 9);
        $pdf->Cell(90, 4, 'Période: '.$periode, 0, 1, 'R');

        // ═══════════ INFOS SALARIÉ ═══════════
        $y = max($y, 30) + 2;
        $pdf->SetDrawColor(0, 100, 0);
        $pdf->SetFillColor(230, 245, 230);
        $pdf->SetFont('', 'B', 8);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell($w, 5, '  INFORMATIONS DU SALARIE', 1, 1, 'L', true);
        $y += 5;

        $pdf->SetFont('', '', 8);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(50, 4, 'Nom: '.$object->employee_name, 0); $pdf->Cell(50, 4, 'Poste: '.$object->employee_job, 0);
        $pdf->Cell(45, 4, 'Cat/Éch: '.$object->employee_category.'/'.$object->employee_echelon, 0);
        $pdf->Cell(45, 4, 'Mat: '.$object->matricule, 0, 1);
        $y += 4;
        $pdf->SetXY($this->marge_gauche, $y);
        $sitLabel = PayrollCICalc::getLibelleSituation($object->situation_familiale, $object->nombre_enfants);
        $pdf->Cell(50, 4, 'CNPS: '.$object->numero_cnps, 0); $pdf->Cell(50, 4, $sitLabel.' ('.$object->nombre_parts.' parts)', 0);
        $pdf->Cell(45, 4, 'Anc: '.$object->anciennete_mois.' mois', 0);
        $villes = PayrollCICalc::getVilles();
        $pdf->Cell(45, 4, 'Ville: '.($villes[$object->ville]['label'] ?? $object->ville), 0, 1);
        $y += 6;

        // ═══════════ TABLEAU PRINCIPAL ═══════════
        // Colonnes : Désignation(80) | Base(28) | Taux(22) | Retenue(30) | Patronal(30)
        $col = [80, 28, 22, 30, 30];

        // En-tête colonnes
        $pdf->SetFillColor(39, 174, 96);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 7);
        $pdf->SetXY($this->marge_gauche, $y);
        $headers = ['Désignation', 'Base', 'Taux', 'Retenue salarié', 'Charge patronale'];
        for ($i = 0; $i < 5; $i++) {
            $align = $i == 0 ? 'L' : 'R';
            $pdf->Cell($col[$i], 5, $headers[$i], 1, 0, $align, true);
        }
        $y += 5;
        $pdf->SetTextColor(0, 0, 0);

        // Fonction helper pour ligne
        $fmtA = function($v) { return ($v > 0) ? number_format($v, 0, ',', ' ') : ''; };

        $printRow = function($label, $base, $taux, $retenue, $patronal, $bold = false) use (&$y, &$pdf, $col, $fmtA) {
            if ($y > 270) { $pdf->AddPage(); $y = $this->marge_haute; }
            $pdf->SetFont('', $bold ? 'B' : '', 7);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell($col[0], 4, '  '.$label, 'LR', 0, 'L');
            $pdf->Cell($col[1], 4, $fmtA($base), 'LR', 0, 'R');
            $pdf->Cell($col[2], 4, $taux, 'LR', 0, 'R');
            $pdf->Cell($col[3], 4, $fmtA($retenue), 'LR', 0, 'R');
            $pdf->Cell($col[4], 4, $fmtA($patronal), 'LR', 0, 'R');
            $y += 4;
        };

        $printSection = function($title) use (&$y, &$pdf, $w, $col) {
            if ($y > 270) { $pdf->AddPage(); $y = $this->marge_haute; }
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell($w, 4, '  '.$title, 1, 0, 'L', true);
            $y += 4;
        };

        $printTotal = function($label, $retenue, $patronal = 0) use (&$y, &$pdf, $col, $fmtA) {
            if ($y > 270) { $pdf->AddPage(); $y = $this->marge_haute; }
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell($col[0]+$col[1]+$col[2], 4, '  '.$label, 1, 0, 'L');
            $pdf->Cell($col[3], 4, $fmtA($retenue), 1, 0, 'R');
            $pdf->Cell($col[4], 4, $fmtA($patronal), 1, 0, 'R');
            $y += 4;
        };

        // ── GAINS ──
        $printSection('GAINS / REMUNERATION');

        $gains = [
            ['Salaire catégoriel (base)', $object->salaire_base],
            ['Sursalaire', $object->sursalaire],
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
            ['Prime de panier (nuit)', $object->prime_panier],
            ['Gratification / 13ème mois', $object->gratification],
            ['Indemnité de transport', $object->indemnite_transport],
            ['Indemnité de logement', $object->indemnite_logement],
            ['Indemnité de représentation', $object->indemnite_representation],
            ['Indemnité d\'expatriation', $object->indemnite_expatriation],
            ['Indemnité de déplacement', $object->indemnite_deplacement],
            ['Indemnité kilométrique', $object->indemnite_kilometrique],
            ['Av. nature: Logement', $object->avantage_nature_logement],
            ['Av. nature: Véhicule', $object->avantage_nature_vehicule],
            ['Av. nature: Domestique', $object->avantage_nature_domestique],
            ['Av. nature: Nourriture', $object->avantage_nature_nourriture],
            ['Av. nature: Autres', $object->avantage_nature_autres],
        ];
        foreach ($gains as $g) {
            if ($g[1] > 0) $printRow($g[0], 0, '', $g[1], 0);
        }

        // Heures sup
        $hsList = [
            ['Heures sup. 15% (41è-46è h)', $object->heures_sup_15, '15%'],
            ['Heures sup. 50% (>46h)', $object->heures_sup_50, '50%'],
            ['Heures sup. 75% (nuit/dim)', $object->heures_sup_75, '75%'],
            ['Heures sup. 100% (nuit+dim)', $object->heures_sup_100, '100%'],
        ];
        foreach ($hsList as $h) {
            if ($h[1] > 0) $printRow($h[0], 0, $h[2], $h[1], 0);
        }
        if ($object->conges_payes > 0) $printRow('Congés payés', 0, '', $object->conges_payes, 0);
        if ($object->autres_primes > 0) $printRow('Autres primes', 0, '', $object->autres_primes, 0);

        $printTotal('SALAIRE BRUT', $object->salaire_brut);

        // Brut imposable
        if ($object->transport_non_imposable > 0) {
            $printRow('Transport non imposable déduit', 0, '', 0, 0);
        }
        $printRow('BRUT IMPOSABLE', 0, '', $object->brut_imposable, 0, true);

        // ── CNPS ──
        $printSection('COTISATIONS SOCIALES (CNPS)');
        $printRow('Retraite', min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE), '6,3%/7,7%', $object->cnps_retraite_sal, $object->cnps_retraite_pat);
        $printRow('Prest. familiales + Maternité', min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF), '5,75%', 0, $object->cnps_pf_pat);
        $printRow('Accidents du travail', min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF), $object->taux_at.'%', 0, $object->cnps_at_pat);
        $printRow('CMU', 0, '500F/mois', $object->cmu_sal, $object->cmu_pat);

        // ── CHARGES FISCALES PATRONALES ──
        $printSection('CHARGES FISCALES PATRONALES');
        $printRow('Impôt Employeur (IE)', $object->brut_imposable, '1,2%', 0, $object->impot_employeur);
        $printRow('FDFP/Taxe Apprentissage (TA)', $object->brut_imposable, '0,4%', 0, $object->fdfp_ta);
        $printRow('FDFP/Form. Prof. Continue (FPC)', $object->brut_imposable, '0,6%', 0, $object->fdfp_fpc);

        // ── ITS ──
        $printSection('IMPOTS SUR TRAITEMENTS & SALAIRES (ITS)');
        $baseFiscale = round($object->brut_imposable * 0.80);
        $printRow('IS (Impôt sur Salaires)', $baseFiscale, '1,5%', $object->its_is, 0);
        $printRow('CN (Contribution Nationale)', $baseFiscale, 'Progressif', $object->its_cn, 0);
        $printRow('IGR (Impôt Général Revenu)', 0, $object->nombre_parts.' parts', $object->its_igr, 0);
        $printTotal('TOTAL ITS', $object->its_total);

        // ── DÉDUCTIONS ──
        $totalDed = $object->avance_salaire + $object->pret_deduction + $object->pension_alimentaire
                  + $object->saisie_arret + $object->mutuelle_complementaire + $object->autres_retenues;
        if ($totalDed > 0) {
            $printSection('AUTRES DEDUCTIONS');
            $deds = [
                ['Avance sur salaire', $object->avance_salaire],
                ['Remboursement prêt', $object->pret_deduction],
                ['Pension alimentaire', $object->pension_alimentaire],
                ['Saisie-arrêt', $object->saisie_arret],
                ['Mutuelle complémentaire', $object->mutuelle_complementaire],
                ['Autres retenues', $object->autres_retenues],
            ];
            foreach ($deds as $d) {
                if ($d[1] > 0) $printRow($d[0], 0, '', $d[1], 0);
            }
        }

        // ── RÉCAPITULATIF ──
        $printSection('RECAPITULATIF');
        $printTotal('Total retenues salariales (CNPS+ITS)', $object->total_retenues_sal);
        if ($totalDed > 0) $printTotal('Total déductions suppl.', $totalDed);
        $printTotal('Total charges patronales', 0, $object->total_charges_pat);

        // ═══════════ NET À PAYER ═══════════
        if ($y > 275) { $pdf->AddPage(); $y = $this->marge_haute; }
        $pdf->SetFillColor(39, 174, 96);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 10);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell($w * 0.6, 7, '  NET A PAYER', 1, 0, 'L', true);
        $pdf->Cell($w * 0.4, 7, number_format($object->net_a_payer, 0, ',', ' ').' FCFA  ', 1, 0, 'R', true);
        $y += 7;

        // Coût total employeur
        $coutTotal = $object->salaire_brut + $object->total_charges_pat;
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 7);
        $pdf->SetXY($this->marge_gauche, $y + 1);
        $pdf->Cell($w, 4, 'Coût total employeur: '.number_format($coutTotal, 0, ',', ' ').' FCFA', 0, 0, 'R');
        $y += 6;

        // ═══════════ PIED DE PAGE ═══════════
        $pdf->SetXY($this->marge_gauche, $y + 3);
        $pdf->SetFont('', '', 6);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell($w/2, 3, 'Calculé selon le droit ivoirien 2026 (CGI, CCI, CNPS, FDFP)', 0, 0, 'L');
        $pdf->Cell($w/2, 3, 'Généré par PayrollCI v3 - Dolibarr', 0, 0, 'R');

        $pdf->Close();
        $pdf->Output($file, 'F');
        if (!empty($conf->global->MAIN_UMASK)) @chmod($file, octdec($conf->global->MAIN_UMASK));

        // Copie de compatibilité V2
        if (isset($fileParent) && $fileParent != $file) {
            @copy($file, $fileParent);
        }

        $this->result = array('fullpath' => $file);
        return 1;
    }
}
