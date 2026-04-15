<?php
/* ============================================================================
 * PayrollCI - Génération PDF Bulletin de Paie
 * Conforme aux mentions obligatoires Art. 46.2 CCI Côte d'Ivoire
 *
 * Mentions obligatoires du bulletin de paie (Art. 46.2 CCI) :
 *   - Nom et prénom du salarié
 *   - Nom/raison sociale et adresse de l'employeur
 *   - Numéro matricule du salarié
 *   - Période de paie
 *   - Nombre de jours travaillés
 *   - Catégorie et échelon professionnel
 *   - Salaire brut (primes, heures sup, congés)
 *   - Cotisations salariales
 *   - Prélèvements sociaux et fiscaux
 *   - Rémunération nette
 *   - Numéros CNPS et CNAM
 * ============================================================================
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/payrollci/class/payrollci_calc.class.php');

class pdf_bulletinpaie
{
    public $db;
    public $name;
    public $description;
    public $type;
    public $page_largeur;
    public $page_hauteur;
    public $format;
    public $marge_gauche;
    public $marge_droite;
    public $marge_haute;
    public $marge_basse;

    /**
     * Constructor
     */
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $this->name = "bulletinpaie";
        $this->description = "Bulletin de paie - Côte d'Ivoire";
        $this->type = 'pdf';

        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche  = 10;
        $this->marge_droite  = 10;
        $this->marge_haute   = 10;
        $this->marge_basse   = 15;
    }

    /**
     * Génère le PDF du bulletin de paie
     *
     * @param Payslip $object    Objet bulletin
     * @param string  $outputdir Répertoire de sortie
     * @return int 1=OK, -1=Erreur
     */
    public function write_file($object, $outputdir = '')
    {
        global $conf, $langs, $mysoc;

        if (empty($outputdir)) {
            $outputdir = $conf->payrollci->dir_output.'/bulletins';
        }

        if (!is_dir($outputdir)) {
            dol_mkdir($outputdir);
        }

        $filename = $outputdir.'/'.$object->ref.'.pdf';

        $pdf = pdf_getInstance($this->format);
        $pdf->SetAutoPageBreak(false, $this->marge_basse);
        $pdf->SetCreator("Dolibarr - PayrollCI");
        $pdf->SetAuthor("PayrollCI");
        $pdf->SetTitle("Bulletin de Paie ".$object->ref);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        $pdf->AddPage();

        $companyName    = $conf->global->PAYROLLCI_COMPANY_NAME ?: $mysoc->name;
        $companyAddress = $conf->global->PAYROLLCI_COMPANY_ADDRESS ?: $mysoc->address;
        $companyCNPS    = $conf->global->PAYROLLCI_COMPANY_CNPS ?: '';
        $companyCC      = $conf->global->PAYROLLCI_COMPANY_CC ?: '';

        $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        $x = $this->marge_gauche;
        $y = $this->marge_haute;

        // ==================== EN-TÊTE ====================
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 8, 'BULLETIN DE PAIE', 0, 1, 'C');
        $y += 10;

        // Période
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $mois = $moisFr[intval(date('m', $object->date_start))] ?? '';
        $annee = date('Y', $object->date_start);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 6, 'Période : '.$mois.' '.$annee, 0, 1, 'C');
        $y += 8;

        // ==================== EMPLOYEUR / SALARIÉ ====================
        $colW = $w / 2;

        // Cadre employeur
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($x, $y);
        $pdf->Cell($colW - 2, 6, 'EMPLOYEUR', 1, 0, 'C', false);
        $pdf->SetXY($x + $colW + 2, $y);
        $pdf->Cell($colW - 2, 6, utf8_decode('SALARIÉ'), 1, 0, 'C', false);
        $y += 7;

        $pdf->SetFont('Helvetica', '', 8);
        $lineH = 5;

        // Employeur details
        $empY = $y;
        $pdf->SetXY($x, $empY);
        $pdf->Cell($colW - 2, $lineH, utf8_decode($companyName), 'LR', 1);
        $empY += $lineH;
        $pdf->SetXY($x, $empY);
        $pdf->Cell($colW - 2, $lineH, utf8_decode($companyAddress), 'LR', 1);
        $empY += $lineH;
        $pdf->SetXY($x, $empY);
        $pdf->Cell($colW - 2, $lineH, 'CNPS Employeur: '.$companyCNPS, 'LR', 1);
        $empY += $lineH;
        $pdf->SetXY($x, $empY);
        $pdf->Cell($colW - 2, $lineH, 'RCCM: '.$companyCC, 'LRB', 1);

        // Salarié details
        $salY = $y;
        $salX = $x + $colW + 2;
        $pdf->SetXY($salX, $salY);
        $pdf->Cell($colW - 2, $lineH, 'Nom: '.utf8_decode($object->employee_name), 'LR', 1);
        $salY += $lineH;
        $pdf->SetXY($salX, $salY);
        $pdf->Cell($colW - 2, $lineH, 'Emploi: '.utf8_decode($object->employee_job), 'LR', 1);
        $salY += $lineH;
        $pdf->SetXY($salX, $salY);
        $catLib = utf8_decode($object->employee_category.' / '.$object->employee_echelon);
        $pdf->Cell($colW - 2, $lineH, utf8_decode('Catég./Éch.: ').$catLib, 'LR', 1);
        $salY += $lineH;
        $pdf->SetXY($salX, $salY);
        $parts = PayrollCICalc::getLibelleSituation($object->situation_familiale, $object->nombre_enfants);
        $pdf->Cell($colW - 2, $lineH, utf8_decode($parts.' ('.$object->nombre_parts.' parts)'), 'LR', 1);
        $salY += $lineH;
        $pdf->SetXY($salX, $salY - $lineH);
        $pdf->Cell($colW - 2, $lineH, '', 'LRB', 1); // fermer le cadre

        $y = max($empY, $salY) + 3;

        // Numéros sociaux
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w/3, 4, 'CNPS: '.$object->numero_cnps, 0, 0);
        $pdf->Cell($w/3, 4, 'CMU/CNAM: '.$object->numero_cmu, 0, 0);
        $pdf->Cell($w/3, 4, utf8_decode('Réf: ').$object->ref, 0, 1, 'R');
        $y += 6;

        // ==================== TABLEAU PRINCIPAL ====================
        $col1 = 80;  // Libellé
        $col2 = 25;  // Base
        $col3 = 20;  // Taux
        $col4 = 33;  // Part salariale
        $col5 = 32;  // Part patronale

        // En-tête tableau
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(41, 128, 185);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 6, utf8_decode('DÉSIGNATION'), 1, 0, 'C', true);
        $pdf->Cell($col2, 6, 'BASE', 1, 0, 'C', true);
        $pdf->Cell($col3, 6, 'TAUX', 1, 0, 'C', true);
        $pdf->Cell($col4, 6, utf8_decode('RETENUE SALARIÉ'), 1, 0, 'C', true);
        $pdf->Cell($col5, 6, utf8_decode('CHARGE PATRONALE'), 1, 1, 'C', true);
        $y += 6;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 8);

        // ---- SECTION GAINS ----
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(230, 240, 250);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1 + $col2 + $col3 + $col4 + $col5, 5, utf8_decode('GAINS / RÉMUNÉRATION'), 1, 1, 'L', true);
        $y += 5;

        $pdf->SetFont('Helvetica', '', 8);
        $gains = [
            ['Salaire de base', $object->salaire_base],
            ["Prime d'ancienneté", $object->prime_anciennete],
            ['Indemnité de transport', $object->prime_transport],
            ['Indemnité de logement', $object->prime_logement],
            ['Prime de responsabilité', $object->prime_responsabilite],
            ['Prime de salissure', $object->prime_salissure],
            ['Heures supplémentaires 25%', $object->heures_sup_25],
            ['Heures supplémentaires 50%', $object->heures_sup_50],
            ['Autres primes', $object->autres_primes],
            ['Congés payés', $object->conges_payes],
        ];

        foreach ($gains as $gain) {
            if ($gain[1] > 0) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($col1, 5, utf8_decode('  '.$gain[0]), 'LR', 0);
                $pdf->Cell($col2, 5, '', 'LR', 0, 'R');
                $pdf->Cell($col3, 5, '', 'LR', 0, 'R');
                $pdf->Cell($col4, 5, number_format($gain[1], 0, ',', ' '), 'LR', 0, 'R');
                $pdf->Cell($col5, 5, '', 'LR', 1, 'R');
                $y += 5;
            }
        }

        // Total brut
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(220, 235, 250);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1 + $col2 + $col3, 6, '  SALAIRE BRUT', 1, 0, 'L', true);
        $pdf->Cell($col4, 6, number_format($object->salaire_brut, 0, ',', ' '), 1, 0, 'R', true);
        $pdf->Cell($col5, 6, '', 1, 1, 'R', true);
        $y += 7;

        // ---- SECTION CNPS ----
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(230, 240, 250);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1 + $col2 + $col3 + $col4 + $col5, 5, 'COTISATIONS SOCIALES (CNPS)', 1, 1, 'L', true);
        $y += 5;

        $pdf->SetFont('Helvetica', '', 8);
        $plafondRet = min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE);
        $plafondPF = min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF);

        // Retraite
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  Retraite (Assurance Vieillesse)'), 'LR', 0);
        $pdf->Cell($col2, 5, number_format($plafondRet, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col3, 5, '6,3% / 7,7%', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, number_format($object->cnps_retraite_sal, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col5, 5, number_format($object->cnps_retraite_pat, 0, ',', ' '), 'LR', 1, 'R');
        $y += 5;

        // Prestations familiales
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  Prestations familiales + Maternité'), 'LR', 0);
        $pdf->Cell($col2, 5, number_format($plafondPF, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col3, 5, '5,75%', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, '-', 'LR', 0, 'C');
        $pdf->Cell($col5, 5, number_format($object->cnps_pf_pat, 0, ',', ' '), 'LR', 1, 'R');
        $y += 5;

        // AT/MP
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  Accidents du travail / Mal. Prof.'), 'LR', 0);
        $pdf->Cell($col2, 5, number_format($plafondPF, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col3, 5, number_format($object->taux_at, 1, ',', '').'%', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, '-', 'LR', 0, 'C');
        $pdf->Cell($col5, 5, number_format($object->cnps_at_pat, 0, ',', ' '), 'LR', 1, 'R');
        $y += 5;

        // CMU
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  CMU (Couverture Maladie Universelle)'), 'LR', 0);
        $pdf->Cell($col2, 5, 'Forfait', 'LR', 0, 'R');
        $pdf->Cell($col3, 5, '500/mois', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, number_format($object->cmu_sal, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col5, 5, number_format($object->cmu_pat, 0, ',', ' '), 'LR', 1, 'R');
        $y += 6;

        // ---- SECTION ITS ----
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(230, 240, 250);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1 + $col2 + $col3 + $col4 + $col5, 5, utf8_decode('IMPÔTS SUR TRAITEMENTS ET SALAIRES (ITS)'), 1, 1, 'L', true);
        $y += 5;

        $pdf->SetFont('Helvetica', '', 8);
        $baseFiscale = round($object->salaire_brut * 0.80);

        // IS
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  IS (Impôt sur Salaires)'), 'LR', 0);
        $pdf->Cell($col2, 5, number_format($baseFiscale, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col3, 5, '1,5%', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, number_format($object->its_is, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col5, 5, '', 'LR', 1, 'R');
        $y += 5;

        // CN
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, '  CN (Contribution Nationale)', 'LR', 0);
        $pdf->Cell($col2, 5, number_format($baseFiscale, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col3, 5, 'Progressif', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, number_format($object->its_cn, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col5, 5, '', 'LR', 1, 'R');
        $y += 5;

        // IGR
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1, 5, utf8_decode('  IGR (Impôt Général sur le Revenu)'), 'LR', 0);
        $pdf->Cell($col2, 5, $object->nombre_parts.' parts', 'LR', 0, 'R');
        $pdf->Cell($col3, 5, 'Progressif', 'LR', 0, 'C');
        $pdf->Cell($col4, 5, number_format($object->its_igr, 0, ',', ' '), 'LR', 0, 'R');
        $pdf->Cell($col5, 5, '', 'LR', 1, 'R');
        $y += 5;

        // Total ITS
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY($x, $y);
        $pdf->Cell($col1 + $col2 + $col3, 5, '  TOTAL ITS', 1, 0, 'L');
        $pdf->Cell($col4, 5, number_format($object->its_total, 0, ',', ' '), 1, 0, 'R');
        $pdf->Cell($col5, 5, '', 1, 1, 'R');
        $y += 6;

        // ---- AVANCES ET DEDUCTIONS ----
        if ($object->avance_salaire > 0 || $object->pret_deduction > 0 || $object->autres_retenues > 0) {
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(230, 240, 250);
            $pdf->SetXY($x, $y);
            $pdf->Cell($col1 + $col2 + $col3 + $col4 + $col5, 5, utf8_decode('AUTRES DÉDUCTIONS'), 1, 1, 'L', true);
            $y += 5;

            $pdf->SetFont('Helvetica', '', 8);
            $deductions = [
                ['Avance sur salaire', $object->avance_salaire],
                ['Remboursement de prêt', $object->pret_deduction],
                ['Autres retenues', $object->autres_retenues],
            ];
            foreach ($deductions as $ded) {
                if ($ded[1] > 0) {
                    $pdf->SetXY($x, $y);
                    $pdf->Cell($col1 + $col2 + $col3, 5, utf8_decode('  '.$ded[0]), 'LR', 0);
                    $pdf->Cell($col4, 5, number_format($ded[1], 0, ',', ' '), 'LR', 0, 'R');
                    $pdf->Cell($col5, 5, '', 'LR', 1, 'R');
                    $y += 5;
                }
            }
            $y += 1;
        }

        // ==================== RÉCAPITULATIF ====================
        $y += 2;
        $recapW = $w / 2 + 20;
        $recapX = $x + $w - $recapW;
        $valW = 40;
        $labW = $recapW - $valW;

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(41, 128, 185);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($recapX, $y);
        $pdf->Cell($recapW, 6, utf8_decode('RÉCAPITULATIF'), 1, 1, 'C', true);
        $y += 6;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 8);

        $recap = [
            ['Salaire brut', $object->salaire_brut],
            ['Retenue CNPS (6,3% + CMU)', $object->cnps_retraite_sal + $object->cmu_sal],
            ['Retenue ITS (IS + CN + IGR)', $object->its_total],
        ];

        if ($object->avance_salaire > 0 || $object->pret_deduction > 0 || $object->autres_retenues > 0) {
            $recap[] = ['Autres déductions', $object->avance_salaire + $object->pret_deduction + $object->autres_retenues];
        }

        foreach ($recap as $line) {
            $pdf->SetXY($recapX, $y);
            $pdf->Cell($labW, 5, utf8_decode('  '.$line[0]), 'LR', 0);
            $pdf->Cell($valW, 5, number_format($line[1], 0, ',', ' ').' F', 'LR', 1, 'R');
            $y += 5;
        }

        // Total retenues
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(240, 220, 220);
        $totalRetAll = $object->total_retenues_sal + $object->avance_salaire + $object->pret_deduction + $object->autres_retenues;
        $pdf->SetXY($recapX, $y);
        $pdf->Cell($labW, 6, '  TOTAL RETENUES', 1, 0, 'L', true);
        $pdf->Cell($valW, 6, number_format($totalRetAll, 0, ',', ' ').' F', 1, 1, 'R', true);
        $y += 7;

        // NET À PAYER
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetFillColor(39, 174, 96);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($recapX, $y);
        $pdf->Cell($labW, 10, utf8_decode('  NET À PAYER'), 1, 0, 'L', true);
        $pdf->Cell($valW, 10, number_format($object->net_a_payer, 0, ',', ' ').' F', 1, 1, 'R', true);
        $y += 12;

        $pdf->SetTextColor(0, 0, 0);

        // Charges patronales
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($recapX, $y);
        $pdf->Cell($labW, 4, '  Total charges patronales :', 0, 0);
        $pdf->Cell($valW, 4, number_format($object->total_charges_pat, 0, ',', ' ').' F', 0, 1, 'R');
        $y += 4;
        $pdf->SetXY($recapX, $y);
        $coutTotal = $object->salaire_brut + $object->total_charges_pat;
        $pdf->Cell($labW, 4, utf8_decode('  Coût total employeur :'), 0, 0);
        $pdf->Cell($valW, 4, number_format($coutTotal, 0, ',', ' ').' F', 0, 1, 'R');
        $y += 8;

        // ==================== PIED DE PAGE ====================
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 4, utf8_decode('Ce bulletin est établi conformément au Code du Travail de Côte d\'Ivoire (Art. 32.5.2) et à la Convention Collective Interprofessionnelle (Art. 46.2).'), 0, 1, 'C');
        $y += 4;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 4, utf8_decode('Conserver ce bulletin sans limitation de durée (Art. 46.1.3 CCI). Taux CNPS et ITS applicables en 2026.'), 0, 1, 'C');
        $y += 6;

        // Signatures
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w / 2, 5, "L'employeur", 0, 0, 'C');
        $pdf->Cell($w / 2, 5, utf8_decode("Le salarié"), 0, 1, 'C');
        $y += 15;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w / 2, 5, 'Signature et cachet', 0, 0, 'C');
        $pdf->Cell($w / 2, 5, 'Signature', 0, 1, 'C');

        // Date de paiement
        $y += 8;
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 4, utf8_decode('Date d\'émission : '.date('d/m/Y')), 0, 1, 'R');

        // Écrire le fichier
        $pdf->Output($filename, 'F');

        if (file_exists($filename)) {
            return 1;
        }
        return -1;
    }
}
