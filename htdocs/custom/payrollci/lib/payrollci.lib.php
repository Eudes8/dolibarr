<?php
/* PayrollCI v2 - Fonctions utilitaires */

function payrollci_format_amount($amount) {
    if ($amount == 0) return '-';
    return number_format($amount, 0, ',', ' ');
}

function payrollci_get_situations() {
    return [
        'celibataire' => 'Célibataire',
        'marie'       => 'Marié(e)',
        'divorce'     => 'Divorcé(e)',
        'veuf'        => 'Veuf/Veuve',
    ];
}

function payrollci_get_mois() {
    return [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
}
