<?php
/* ============================================================================
 * PayrollCI v3 - Fonctions utilitaires + préparation des onglets Dolibarr
 * ============================================================================ */

/**
 * Prépare les onglets de la fiche bulletin de paie
 */
function payrollci_prepare_head($object)
{
    global $langs, $conf, $db;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/payrollci/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Fiche");
    $head[$h][2] = 'card';
    $h++;

    // Onglet Documents
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $upload_dir = $conf->payrollci->dir_output.'/bulletins/'.dol_sanitizeFileName($object->ref);
    $nbFiles = 0;
    if (is_dir($upload_dir)) {
        $filearray = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$');
        $nbFiles = count($filearray);
    }
    // Aussi compter le PDF direct
    $pdfFile = $conf->payrollci->dir_output.'/bulletins/'.$object->ref.'.pdf';
    if (file_exists($pdfFile)) $nbFiles = max($nbFiles, 1);

    $head[$h][0] = dol_buildpath('/payrollci/document.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    if ($nbFiles > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFiles.'</span>';
    $head[$h][2] = 'documents';
    $h++;

    // Onglet Notes
    $head[$h][0] = dol_buildpath('/payrollci/note.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Notes");
    if (!empty($object->note_private) || !empty($object->note_public)) {
        $head[$h][1] .= '<span class="badge marginleftonlyshort">...</span>';
    }
    $head[$h][2] = 'notes';
    $h++;

    // Onglet Événements/Agenda
    if (isModEnabled('agenda')) {
        $head[$h][0] = dol_buildpath('/payrollci/agenda.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans("Events");
        $head[$h][2] = 'agenda';
        $h++;
    }

    // Onglet Objets liés
    $head[$h][0] = dol_buildpath('/payrollci/linked.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("LinkedObjects");
    $head[$h][2] = 'linked';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'payrollci', 'remove');

    return $head;
}

/**
 * Prépare les onglets administration
 */
function payrollci_admin_prepare_head()
{
    global $langs, $conf;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/payrollci/admin/setup.php', 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/admin/about.php', 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/admin/extrafields.php', 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'payrollci_admin');

    return $head;
}

/**
 * Formatage montant FCFA
 */
function payrollci_format_amount($amount) {
    if ($amount == 0) return '-';
    return number_format(round($amount), 0, ',', ' ');
}

/**
 * Situations familiales
 */
function payrollci_get_situations() {
    return [
        'celibataire' => 'Célibataire',
        'marie'       => 'Marié(e)',
        'divorce'     => 'Divorcé(e)',
        'veuf'        => 'Veuf/Veuve',
    ];
}

/**
 * Mois en français
 */
function payrollci_get_mois() {
    return [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
}
