<?php
/* ============================================================================
 * PayrollCI v4 - Générateur PDF Bulletin de Paie
 * Format Sage Paie CI - Lignes numérotées, colonnes Part salariale/patronale
 * Réforme ITS 2024 : IBS + RICF (remplace IS + CN + IGR)
 * ============================================================================ */

dol_include_once('/payrollci/class/payrollci_calc.class.php');
dol_include_once('/payrollci/lib/payrollci.lib.php');

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

    // Couleurs (Sage-style)
    private $bleuFonce  = [0, 51, 102];     // En-tête principal
    private $bleuMoyen  = [51, 102, 153];   // Titres sections
    private $gris       = [200, 200, 200];  // Lignes séparation
    private $grisLeger  = [240, 240, 240];  // Fond alternance
    private $blanc      = [255, 255, 255];
    private $noir       = [0, 0, 0];
    private $vertNet    = [0, 128, 0];      // NET A PAYER

    public function __construct($db)
    {
        global $langs;
        $this->db = $db;
        $this->name = 'pdf_bulletinpaie';
        $this->description = 'Bulletin de paie CI - Format Sage (IBS/RICF)';
        $this->type = 'pdf';
        $this->page_largeur = 210;  // A4 portrait
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 8;
        $this->marge_droite = 8;
        $this->marge_haute  = 8;
        $this->marge_basse  = 15;
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf, $langs, $mysoc;

        if (!is_object($outputlangs)) $outputlangs = $langs;
        $outputlangs->loadLangs(array("payrollci@payrollci", "main"));

        $objref = dol_sanitizeFileName($object->ref);
        $dir = $conf->payrollci->dir_output.'/bulletins/'.$objref;
        if (!is_dir($dir)) dol_mkdir($dir);
        $file = $dir.'/'.$objref.'.pdf';

        require_once TCPDF_PATH.'tcpdf.php';
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('PayrollCI v4');
        $pdf->SetTitle('Bulletin de Paie '.$object->ref);
        $pdf->SetAutoPageBreak(false, $this->marge_basse);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pageW = $this->page_largeur - $this->marge_gauche - $this->marge_droite; // 194mm

        // ======================= EN-TÊTE =======================
        $y = $this->_drawHeader($pdf, $object, $mysoc, $conf, $pageW);

        // ======================= INFOS EMPLOYÉ =======================
        $y = $this->_drawEmployeeInfo($pdf, $object, $y, $pageW);

        // ======================= TABLEAU PRINCIPAL =======================
        $y = $this->_drawMainTable($pdf, $object, $y, $pageW);

        // ======================= CUMULS + NET A PAYER =======================
        $y = $this->_drawCumulsAndNet($pdf, $object, $y, $pageW);

        // ======================= VISAS =======================
        $this->_drawVisas($pdf, $y, $pageW);

        $pdf->Output($file, 'F');
        dolChmod($file);
        return 1;
    }

    /**
     * En-tête : BULLETIN DE PAIE + infos entreprise + période
     */
    private function _drawHeader($pdf, $object, $mysoc, $conf, $pageW)
    {
        $x = $this->marge_gauche;
        $y = $this->marge_haute;

        // Bandeau bleu "BULLETIN DE PAIE"
        $pdf->SetFillColor(...$this->bleuFonce);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY($x, $y);
        $pdf->Cell(60, 8, 'BULLETIN DE PAIE', 0, 0, 'L', true);

        // Période à droite du bandeau
        $moisList = payrollci_get_mois();
        $moisNum = intval(date('m', $object->date_start));
        $annee = date('Y', $object->date_start);
        $dateDebut = '01/'.str_pad($moisNum, 2, '0', STR_PAD_LEFT).'/'.$annee;
        $dateFin = date('t', $object->date_start).'/'.str_pad($moisNum, 2, '0', STR_PAD_LEFT).'/'.$annee;

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(...$this->grisLeger);
        $pdf->SetXY($x + 62, $y);
        $pdf->Cell(30, 4, 'Période du :', 1, 0, 'R', true);
        $pdf->Cell(25, 4, $dateDebut, 1, 0, 'C', false);
        $pdf->Cell(10, 4, 'au', 1, 0, 'C', true);
        $pdf->Cell(25, 4, $dateFin, 1, 0, 'C', false);

        $pdf->SetXY($x + 152, $y);
        $pdf->Cell(20, 4, 'Paiement le', 1, 0, 'R', true);
        $pdf->Cell(22, 4, $dateFin, 1, 0, 'C', false);

        // Deuxième ligne : Par + Virement
        $pdf->SetXY($x + 152, $y + 4);
        $pdf->Cell(20, 4, 'Par :', 1, 0, 'R', true);
        $pdf->Cell(22, 4, 'VIREMENT', 1, 0, 'C', false);

        // Infos entreprise
        $y += 10;
        $companyName = !empty($mysoc->name) ? strtoupper($mysoc->name) : 'ENTREPRISE';
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(...$this->bleuFonce);
        $pdf->SetXY($x, $y);
        $pdf->Cell(55, 7, $companyName, 0, 0, 'L');

        // Matricule / Niveau / Coefficient / Indice / Ancienneté / N° SS
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->SetTextColor(0, 0, 0);

        $colStart = $x + 56;
        $labels = ['Matricule', 'Niveau', 'Coefficient', 'Indice', 'Ancienneté', 'N° de Sécurité Sociale'];
        $values = [
            $object->matricule ?: '0001',
            $object->employee_echelon ?: '0',
            '0',
            '0',
            $object->anciennete_mois.' mois',
            $object->numero_cnps ?: '0'
        ];

        $cw = [20, 14, 18, 14, 28, 44]; // widths for each
        $pdf->SetFillColor(...$this->grisLeger);
        for ($i = 0; $i < count($labels); $i++) {
            $pdf->SetXY($colStart, $y);
            $pdf->SetFont('helvetica', '', 5.5);
            $pdf->Cell($cw[$i], 3, $labels[$i], 1, 0, 'C', true);
            $pdf->SetXY($colStart, $y + 3);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell($cw[$i], 4, $values[$i], 1, 0, 'C', false);
            $colStart += $cw[$i];
        }

        // Ligne sous entreprise : adresse
        $y += 8;
        $pdf->SetFont('helvetica', '', 7);
        $addr = '';
        if (!empty($mysoc->address)) $addr = $mysoc->address;
        if (!empty($mysoc->zip)) $addr .= ($addr ? ' ' : '').$mysoc->zip;
        if (!empty($mysoc->town)) $addr .= ' '.$mysoc->town;
        $pdf->SetXY($x, $y);
        $pdf->Cell(55, 4, $addr, 0, 0, 'L');

        // Catégorie + Emploi occupé + Département
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($x + 56, $y);
        $pdf->Cell(20, 3, 'Catégorie', 1, 0, 'C', true);
        $pdf->SetXY($x + 56, $y + 3);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(20, 4, $object->employee_category ?: '-', 1, 0, 'C', false);

        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($x + 80, $y);
        $pdf->Cell(52, 3, 'Emploi occupé', 1, 0, 'C', true);
        $pdf->SetXY($x + 80, $y + 3);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(52, 4, strtoupper($object->employee_job ?: '-'), 1, 0, 'C', false);

        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($x + 136, $y);
        $pdf->Cell(58, 3, 'Département', 1, 0, 'C', true);
        $pdf->SetXY($x + 136, $y + 3);
        $pdf->SetFont('helvetica', 'B', 7);
        $villes = PayrollCICalc::getVilles();
        $villeLabel = strtoupper($villes[$object->ville]['label'] ?? 'ADMINISTRATION');
        $pdf->Cell(58, 4, $villeLabel, 1, 0, 'C', false);

        // Téléphone + Qualification + Horaire + CCN
        $y += 8;
        $pdf->SetFont('helvetica', '', 7);
        $phone = !empty($mysoc->phone) ? $mysoc->phone : '';
        $pdf->SetXY($x, $y);
        $pdf->Cell(55, 4, 'Tél : '.$phone, 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($x + 56, $y);
        $pdf->Cell(20, 3, 'Qualification', 1, 0, 'C', true);
        $pdf->SetXY($x + 80, $y);
        $pdf->Cell(14, 3, 'Horaire', 1, 0, 'C', true);
        $pdf->SetXY($x + 94, $y);
        $pdf->Cell(100, 3, 'CCN : Convention Collective Interprofessionnelle', 1, 0, 'L', true);

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x + 56, $y + 3);
        $pdf->Cell(20, 4, '0', 1, 0, 'C', false);
        $pdf->SetXY($x + 80, $y + 3);
        $pdf->Cell(14, 4, '173,33', 1, 0, 'C', false);

        // Nombre de parts + Sexe + Nom employé
        $y += 8;
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($x + 56, $y);
        $pdf->Cell(34, 3, 'Nombre de parts', 1, 0, 'C', true);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x + 56, $y + 3);
        $pdf->Cell(34, 4, number_format($object->nombre_parts, 1), 1, 0, 'C', false);

        // Sexe + Nom
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($x + 95, $y + 1);
        $pdf->Cell(5, 5, 'M', 0, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($x + 102, $y + 1);
        $pdf->Cell(90, 5, strtoupper($object->employee_name), 0, 0, 'L');

        return $y + 8;
    }

    /**
     * Infos complémentaires : congés, commentaire
     */
    private function _drawEmployeeInfo($pdf, $object, $y, $pageW)
    {
        $x = $this->marge_gauche;

        // Ligne congés (simplifiée)
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetFillColor(...$this->grisLeger);
        $labels2 = ['Repos comp.', 'Congés', 'Dates de congés'];
        $yy = $y;
        foreach ($labels2 as $i => $lbl) {
            $pdf->SetXY($x, $yy);
            $pdf->Cell(25, 3.5, $lbl, 1, 0, 'L', true);
            $pdf->Cell(15, 3.5, '', 1, 0, 'C', false);
            if ($lbl == 'Dates de congés') {
                $pdf->Cell(5, 3.5, 'du', 1, 0, 'C', true);
                $pdf->Cell(22, 3.5, '', 1, 0, 'C', false);
                $pdf->Cell(5, 3.5, 'du', 1, 0, 'C', true);
                $pdf->Cell(22, 3.5, '', 1, 0, 'C', false);
            }
            $yy += 3.5;
        }

        // Commentaire
        $pdf->SetXY($x, $yy);
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->Cell($pageW, 3.5, 'Commentaire :', 1, 0, 'L', true);
        $yy += 3.5;

        return $yy + 1;
    }

    /**
     * Tableau principal avec lignes numérotées - Format Sage
     * Colonnes : N° | DESIGNATION | Nombre | Base | Part salariale (Taux|Gain|Retenue) | Part patronale (Taux|Retenue(+)|Retenue(-))
     */
    private function _drawMainTable($pdf, $object, $y, $pageW)
    {
        $x = $this->marge_gauche;

        // ── EN-TÊTE DU TABLEAU ──
        // Colonnes (largeurs en mm - total = 194)
        $cNum   = 8;    // N°
        $cDesig = 52;   // DESIGNATION
        $cNb    = 12;   // Nombre
        $cBase  = 20;   // Base
        // Part salariale
        $cSTaux = 14;   // Taux
        $cSGain = 18;   // Gain
        $cSRet  = 16;   // Retenue
        // Part patronale
        $cPTaux = 14;   // Taux
        $cPPlus = 18;   // Retenue (+)
        $cPMoins= 22;   // Retenue (-)

        // Première ligne : N° + DESIGNATION + Nombre + Base + Part salariale + Part patronale
        $pdf->SetFillColor(...$this->bleuFonce);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 6);

        // Row 1 header
        $pdf->SetXY($x, $y);
        $pdf->Cell($cNum + $cDesig + $cNb + $cBase, 4, '', 1, 0, 'C', true);
        $pdf->Cell($cSTaux + $cSGain + $cSRet, 4, 'Part salariale', 1, 0, 'C', true);
        $pdf->Cell($cPTaux + $cPPlus + $cPMoins, 4, 'Part patronale', 1, 0, 'C', true);

        $y += 4;
        // Row 2 header (sub-columns)
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 5.5);
        $pdf->Cell($cNum, 4, 'N°', 1, 0, 'C', true);
        $pdf->Cell($cDesig, 4, 'DESIGNATION', 1, 0, 'C', true);
        $pdf->Cell($cNb, 4, 'Nombre', 1, 0, 'C', true);
        $pdf->Cell($cBase, 4, 'Base', 1, 0, 'C', true);
        $pdf->Cell($cSTaux, 4, 'Taux', 1, 0, 'C', true);
        $pdf->Cell($cSGain, 4, 'Gain', 1, 0, 'C', true);
        $pdf->Cell($cSRet, 4, 'Retenue', 1, 0, 'C', true);
        $pdf->Cell($cPTaux, 4, 'Taux', 1, 0, 'C', true);
        $pdf->Cell($cPPlus, 4, 'Retenue (+)', 1, 0, 'C', true);
        $pdf->Cell($cPMoins, 4, 'Retenue (-)', 1, 0, 'C', true);

        $y += 4;

        // ── Fonction helper pour une ligne ──
        $lineH = 3.8;
        $rowNum = 0;
        $drawLine = function($num, $label, $nombre, $base, $sTaux, $sGain, $sRet, $pTaux, $pPlus, $pMoins) use (&$pdf, &$y, $x, $cNum, $cDesig, $cNb, $cBase, $cSTaux, $cSGain, $cSRet, $cPTaux, $cPPlus, $cPMoins, $lineH, &$rowNum) {
            $rowNum++;
            $bgFill = ($rowNum % 2 == 0);
            if ($bgFill) $pdf->SetFillColor(248, 248, 248);
            else $pdf->SetFillColor(255, 255, 255);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetXY($x, $y);
            $pdf->Cell($cNum, $lineH, $num, 'LR', 0, 'C', $bgFill);
            $pdf->Cell($cDesig, $lineH, $label, 'LR', 0, 'L', $bgFill);
            $pdf->Cell($cNb, $lineH, $nombre, 'LR', 0, 'C', $bgFill);
            $pdf->Cell($cBase, $lineH, $base, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cSTaux, $lineH, $sTaux, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cSGain, $lineH, $sGain, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cSRet, $lineH, $sRet, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cPTaux, $lineH, $pTaux, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cPPlus, $lineH, $pPlus, 'LR', 0, 'R', $bgFill);
            $pdf->Cell($cPMoins, $lineH, $pMoins, 'LR', 0, 'R', $bgFill);
            $y += $lineH;
        };

        // Ligne séparatrice bold
        $drawSeparator = function($label, $gain = '', $retenue = '', $pPlus = '', $pMoins = '') use (&$pdf, &$y, &$rowNum, $x, $cNum, $cDesig, $cNb, $cBase, $cSTaux, $cSGain, $cSRet, $cPTaux, $cPPlus, $cPMoins, $lineH) {
            $rowNum++;
            $pdf->SetFillColor(...$this->grisLeger);
            $pdf->SetFont('helvetica', 'B', 6.5);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($x, $y);
            $pdf->Cell($cNum + $cDesig, $lineH + 0.5, $label, 1, 0, 'R', true);
            $pdf->Cell($cNb, $lineH + 0.5, '', 1, 0, 'C', true);
            $pdf->Cell($cBase, $lineH + 0.5, '', 1, 0, 'R', true);
            $pdf->Cell($cSTaux, $lineH + 0.5, '', 1, 0, 'R', true);
            $pdf->Cell($cSGain, $lineH + 0.5, $gain, 1, 0, 'R', true);
            $pdf->Cell($cSRet, $lineH + 0.5, $retenue, 1, 0, 'R', true);
            $pdf->Cell($cPTaux, $lineH + 0.5, '', 1, 0, 'R', true);
            $pdf->Cell($cPPlus, $lineH + 0.5, $pPlus, 1, 0, 'R', true);
            $pdf->Cell($cPMoins, $lineH + 0.5, $pMoins, 1, 0, 'R', true);
            $y += $lineH + 0.5;
        };

        $fmt = function($v) { return ($v > 0) ? number_format(round($v), 0, ',', ' ') : ''; };
        $fmtPct = function($v) { return ($v > 0) ? number_format($v, 2, ',', '').'%' : ''; };
        $jours = '30'; // Standard month

        // ── ÉLÉMENTS DE RÉMUNÉRATION ──
        $drawLine('10', 'SALAIRE BRUT', $jours, $fmt($object->salaire_base), '100,00%', $fmt($object->salaire_base), '', '', '', '');
        if ($object->sursalaire > 0)
            $drawLine('11', 'SURSALAIRE', $jours, $fmt($object->sursalaire), '100,00%', $fmt($object->sursalaire), '', '', '', '');
        if ($object->conges_payes > 0)
            $drawLine('12', 'CONGES PAYES', $jours, '', '100,00%', $fmt($object->conges_payes), '', '', '', '');

        // Prime d'ancienneté
        $tauxAnc = 0;
        if ($object->anciennete_mois >= 24) {
            $tauxAnc = PayrollCICalc::calculerTauxAnciennete($object->anciennete_mois);
        }
        $drawLine('13', 'PRIME D\'ANCIENNETE', $jours, '', $fmtPct($tauxAnc * 100), $fmt($object->prime_anciennete), '', '', '', '');

        if ($object->prime_responsabilite > 0)
            $drawLine('14', 'PRIME DE RESPONSABILITE', $jours, '', '', $fmt($object->prime_responsabilite), '', '', '', '');
        if ($object->prime_rendement > 0)
            $drawLine('15', 'PRIME DE RENDEMENT', $jours, '', '', $fmt($object->prime_rendement), '', '', '', '');
        if ($object->gratification > 0)
            $drawLine('16', 'GRATIFICATIONS', '', '', '', $fmt($object->gratification), '', '', '', '');

        // Autres primes et indemnités imposables
        $autresPrimesImp = $object->prime_technicite + $object->prime_fonction + $object->prime_risque
            + $object->prime_outillage + $object->prime_salissure + $object->prime_caisse
            + $object->prime_assiduite + $object->prime_panier
            + $object->indemnite_logement + $object->indemnite_representation
            + $object->indemnite_expatriation + $object->indemnite_deplacement + $object->indemnite_kilometrique
            + $object->autres_primes;

        $baseTransport = $object->indemnite_transport;
        $totalImpPrimes = $autresPrimesImp;
        if ($totalImpPrimes > 0) {
            $drawLine('17', 'AUTRES PRIMES ET INDEMNITES', $jours, $fmt($totalImpPrimes), '', $fmt($totalImpPrimes), '', '', '', '');
        }

        // Heures supplémentaires
        $totalHS = $object->heures_sup_15 + $object->heures_sup_50 + $object->heures_sup_75 + $object->heures_sup_100;
        $drawLine('18', 'HEURES SUP.', '', '', '', $fmt($totalHS), '', '', '', '');

        // Avantages en nature
        $totalAN = $object->avantage_nature_logement + $object->avantage_nature_vehicule
            + $object->avantage_nature_domestique + $object->avantage_nature_nourriture + $object->avantage_nature_autres;
        $drawLine('19', 'AVANTAGE EN NATURE', $jours, $fmt($totalAN), ($totalAN > 0 ? '100,00%' : ''), $fmt($totalAN), '', '', '', '');

        // ── TOTAL BRUT IMPOSABLE ──
        $drawSeparator('TOTAL BRUT IMPOSABLE :', $fmt($object->brut_imposable), '0');

        // ── IMPÔTS (V4: IBS + RICF au lieu de IS + CN + IGR) ──
        $drawLine('20', 'IBS (IMPOT SUR REVENU SALAIRES)', '', $fmt($object->brut_imposable), 'Progressif', '', $fmt($object->its_ibs), '', '', '');
        $drawLine('21', 'RICF (REDUCTION CHARGES FAMILLE)', '', '', '', '', $fmt($object->its_ricf), '', '', '');
        $drawLine('22', 'ITS NET (IBS - RICF)', '', '', '', '', $fmt($object->its_total), '', '', '');

        // ── CHARGES PATRONALES FISCALES ──
        $tauxContrib = $object->is_expatrie ? '12,00%' : '2,80%';
        $drawLine('23', 'FDFP-TA', '', $fmt($object->brut_imposable), '', '', '', '0,40%', '', $fmt($object->fdfp_ta));
        $drawLine('24', 'FDFP-FPC', '', $fmt($object->brut_imposable), '', '', '', '0,60%', '', $fmt($object->fdfp_fpc));

        // ── CNPS ──
        $plafondAT = min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_PF);
        $plafondRet = min($object->salaire_brut, PayrollCICalc::CNPS_PLAFOND_RETRAITE);
        $drawLine('25', 'CNPS-ACCIDENT DE TRAVAIL', '', $fmt($plafondAT), '', '', '', $fmtPct($object->taux_at), '', $fmt($object->cnps_at_pat));
        $drawLine('26', 'CNPS-PRESTATIONS FAMILIALES', '', $fmt($plafondAT), '', '', '', '5,75%', '', $fmt($object->cnps_pf_pat));
        $drawLine('27', 'CNPS-RETRAITE', '', $fmt($plafondRet), '6,30%', '', $fmt($object->cnps_retraite_sal), '7,70%', '', $fmt($object->cnps_retraite_pat));

        // CMU
        $drawLine('28', 'CMU / CNAM', '', '', '', '', $fmt($object->cmu_sal), '', '', $fmt($object->cmu_pat));

        // Contribution employeur
        $drawLine('29', 'CONTRIBUTION EMPLOYEUR', '', $fmt($object->brut_imposable), '', '', '', $tauxContrib, '', $fmt($object->contribution_employeur));

        // ── TOTAL COTISATIONS ──
        $totalRetSal = $object->its_total + $object->cnps_retraite_sal + $object->cmu_sal;
        $totalRetPat = $object->cnps_at_pat + $object->cnps_pf_pat + $object->cnps_retraite_pat
            + $object->cmu_pat + $object->fdfp_ta + $object->fdfp_fpc + $object->contribution_employeur;
        $drawSeparator('TOTAL COTISATIONS :', '', $fmt($totalRetSal), '', $fmt($totalRetPat));

        // ── Éléments non imposables ──
        if ($object->transport_non_imposable > 0) {
            $drawLine('31', 'INDEMNITE DE TRANSPORT', $jours, $fmt($object->transport_non_imposable), '100,00%', $fmt($object->transport_non_imposable), '', '', '', '');
        }

        // Autres primes non imposables
        $autresNonImp = $object->indemnite_transport - $object->transport_non_imposable;
        // We list any non-imposable portion
        $nonImpTotal = 0;
        if ($nonImpTotal > 0) {
            $drawLine('32', 'AUTRES PRIMES ET INDEMNITES NON IMP.', $jours, $fmt($nonImpTotal), '100,00%', $fmt($nonImpTotal), '', '', '', '');
        }

        // ── Déductions ──
        if ($object->pret_deduction > 0)
            $drawLine('33', 'REMBOURSEMENTS PRETS', '', '', '', '', $fmt($object->pret_deduction), '', '', '');
        if ($object->avance_salaire > 0)
            $drawLine('34', 'AVANCES & ACOMPTES SUR SALAIRE', '', '', '', '', $fmt($object->avance_salaire), '', '', '');
        if ($totalAN > 0)
            $drawLine('35', 'REPRISE AVANTAGE EN NATURE', '', '', '', '', $fmt($totalAN), '', '', '');
        if ($object->autres_retenues + $object->pension_alimentaire + $object->saisie_arret + $object->mutuelle_complementaire > 0) {
            $autresRet = $object->autres_retenues + $object->pension_alimentaire + $object->saisie_arret + $object->mutuelle_complementaire;
            $drawLine('36', 'AUTRES RETENUES', '', '', '', '', $fmt($autresRet), '', '', '');
        }

        // ── ARRONDI DE PAIE ──
        $arrondi = round($object->net_a_payer) - $object->net_a_payer;
        $drawLine('37', 'ARRONDI DE PAIE', '', '', '', '', $fmt(abs($arrondi)), '', '', '');

        // Ligne de fermeture
        $pdf->SetXY($x, $y);
        $pdf->Cell($cNum + $cDesig + $cNb + $cBase + $cSTaux + $cSGain + $cSRet + $cPTaux + $cPPlus + $cPMoins, 0.2, '', 'T', 0, 'C');

        return $y + 1;
    }

    /**
     * Section CUMULS + NET A PAYER
     */
    private function _drawCumulsAndNet($pdf, $object, $y, $pageW)
    {
        $x = $this->marge_gauche;
        $fmt = function($v) { return number_format(round($v), 0, ',', ' '); };

        // Net à payer (calcul intermédiaire affiché)
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(0, 0, 0);

        // Affichage NET intermédiaire au dessus de CUMULS
        $coutEmployeur = $object->salaire_brut + $object->total_charges_pat;
        $pdf->SetXY($x + 115, $y);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(25, 5, $fmt($object->net_a_payer), 0, 0, 'R');

        $y += 6;

        // ── TABLEAU CUMULS ──
        $pdf->SetFillColor(...$this->bleuFonce);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 6);

        // En-tête CUMULS
        $cLabel = 22; $cBrut = 24; $cImp = 24; $cCSal = 22; $cCPat = 22; $cH = 18; $cHS = 16; $cAN = 20; $cNA = 26;
        $pdf->SetXY($x, $y);
        $pdf->Cell($cLabel, 8, 'CUMULS', 1, 0, 'C', true);
        $pdf->Cell($cBrut, 4, 'Salaire Brut', 1, 0, 'C', true);
        $pdf->Cell($cImp, 4, 'Brut', 1, 0, 'C', true);
        $pdf->Cell($cCSal, 4, 'Charges', 1, 0, 'C', true);
        $pdf->Cell($cCPat, 4, 'Charges', 1, 0, 'C', true);
        $pdf->Cell($cH, 4, 'Heures', 1, 0, 'C', true);
        $pdf->Cell($cHS, 4, 'Heures Sup.', 1, 0, 'C', true);
        $pdf->Cell($cAN, 4, 'Avantages en', 1, 0, 'C', true);

        // NET A PAYER en vert/bleu
        $pdf->SetFillColor(...$this->bleuFonce);
        $pdf->Cell($cNA, 8, '', 1, 0, 'C', true);
        // Write "NET A PAYER" inside
        $xNet = $x + $cLabel + $cBrut + $cImp + $cCSal + $cCPat + $cH + $cHS + $cAN;
        $pdf->SetXY($xNet, $y);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell($cNA, 4, 'NET A PAYER', 0, 0, 'C');

        // Sub-headers
        $pdf->SetXY($x + $cLabel, $y + 4);
        $pdf->SetFont('helvetica', 'B', 5.5);
        $pdf->Cell($cBrut, 4, '', 1, 0, 'C', true);
        $pdf->Cell($cImp, 4, 'Imposable', 1, 0, 'C', true);
        $pdf->Cell($cCSal, 4, 'Salariales', 1, 0, 'C', true);
        $pdf->Cell($cCPat, 4, 'Patronales', 1, 0, 'C', true);
        $pdf->Cell($cH, 4, 'Travaillés', 1, 0, 'C', true);
        $pdf->Cell($cHS, 4, '', 1, 0, 'C', true);
        $pdf->Cell($cAN, 4, 'nature', 1, 0, 'C', true);

        $y += 8;

        // Lignes PERIODE et ANNEE
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 7);

        // PERIODE
        $pdf->SetXY($x, $y);
        $pdf->SetFillColor(...$this->grisLeger);
        $pdf->Cell($cLabel, 5, 'PERIODE', 1, 0, 'C', true);
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->Cell($cBrut, 5, $fmt($object->salaire_brut), 1, 0, 'R');
        $pdf->Cell($cImp, 5, $fmt($object->brut_imposable), 1, 0, 'R');
        $pdf->Cell($cCSal, 5, $fmt($object->total_retenues_sal), 1, 0, 'R');
        $pdf->Cell($cCPat, 5, $fmt($object->total_charges_pat), 1, 0, 'R');
        $pdf->Cell($cH, 5, '173,33', 1, 0, 'R');
        $pdf->Cell($cHS, 5, '0', 1, 0, 'R');
        $pdf->Cell($cAN, 5, '0', 1, 0, 'R');

        // NET A PAYER en gros
        $pdf->SetFillColor(255, 255, 200);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell($cNA, 10, $fmt($object->net_a_payer), 1, 0, 'R', true);

        $y += 5;

        // ANNEE (même montants pour premier mois)
        $pdf->SetXY($x, $y);
        $pdf->SetFillColor(...$this->grisLeger);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell($cLabel, 5, 'ANNEE', 1, 0, 'C', true);
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->Cell($cBrut, 5, $fmt($object->salaire_brut), 1, 0, 'R');
        $pdf->Cell($cImp, 5, $fmt($object->brut_imposable), 1, 0, 'R');
        $pdf->Cell($cCSal, 5, $fmt($object->total_retenues_sal), 1, 0, 'R');
        $pdf->Cell($cCPat, 5, $fmt($object->total_charges_pat), 1, 0, 'R');
        $pdf->Cell($cH, 5, '173,33', 1, 0, 'R');
        $pdf->Cell($cHS, 5, '0', 1, 0, 'R');
        $pdf->Cell($cAN, 5, '0', 1, 0, 'R');

        $y += 6;
        return $y;
    }

    /**
     * VISA DE L'EMPLOYEUR + VISA DE L'EMPLOYE
     */
    private function _drawVisas($pdf, $y, $pageW)
    {
        $x = $this->marge_gauche;
        $y += 3;

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell($pageW / 2, 5, 'VISA DE L\'EMPLOYEUR', 'T', 0, 'C');
        $pdf->Cell($pageW / 2, 5, 'VISA DE L\'EMPLOYE', 'T', 0, 'C');

        // Pied de page légal
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->SetXY($x, 285);
        $pdf->Cell($pageW, 3, 'PayrollCI v4 - Conforme Ordonnance n° 2023-719 du 13/09/2023 (IBS/RICF) - Art. 46.2 CCI', 0, 0, 'C');
    }
}
