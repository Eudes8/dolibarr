<?php
/* ============================================================================
 * PayrollCI - Fonctions bibliothèque
 * ============================================================================
 */

/**
 * Prépare les onglets de l'administration du module
 */
function payrollciAdminPrepareHead()
{
    global $langs, $conf;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/payrollci/admin/setup.php", 1);
    $head[$h][1] = 'Configuration';
    $head[$h][2] = 'settings';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'payrollci@payrollci');

    return $head;
}

/**
 * Formate un montant en FCFA
 */
function payrollci_format_amount($amount)
{
    return number_format(round($amount), 0, ',', ' ').' FCFA';
}

/**
 * Retourne les mois en français
 */
function payrollci_get_mois()
{
    return array(
        1  => 'Janvier',
        2  => 'Février',
        3  => 'Mars',
        4  => 'Avril',
        5  => 'Mai',
        6  => 'Juin',
        7  => 'Juillet',
        8  => 'Août',
        9  => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre',
    );
}

/**
 * Retourne les situations familiales
 */
function payrollci_get_situations()
{
    return array(
        'celibataire' => 'Célibataire',
        'marie'       => 'Marié(e)',
        'divorce'     => 'Divorcé(e)',
        'veuf'        => 'Veuf/Veuve',
    );
}
