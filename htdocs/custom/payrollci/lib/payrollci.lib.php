<?php
/* ============================================================================
 * PayrollCI v4 - Fonctions bibliothèque
 * ============================================================================ */

function payrollci_prepare_head($object)
{
    global $langs, $db;
    $h = 0; $head = array();

    $head[$h][0] = dol_buildpath('/payrollci/card.php', 1).'?id='.$object->id;
    $head[$h][1] = img_picto('', 'object_payrollci@payrollci', 'class="pictofixedwidth"').'Bulletin';
    $head[$h][2] = 'card';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/document.php', 1).'?id='.$object->id;
    $head[$h][1] = img_picto('', 'document', 'class="pictofixedwidth"').'Documents';
    $head[$h][2] = 'document';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/note.php', 1).'?id='.$object->id;
    $head[$h][1] = img_picto('', 'note', 'class="pictofixedwidth"').'Notes';
    $head[$h][2] = 'note';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/agenda.php', 1).'?id='.$object->id;
    $head[$h][1] = img_picto('', 'calendar', 'class="pictofixedwidth"').'Événements';
    $head[$h][2] = 'agenda';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/linked.php', 1).'?id='.$object->id;
    $head[$h][1] = img_picto('', 'link', 'class="pictofixedwidth"').'Objets liés';
    $head[$h][2] = 'linked';
    $h++;

    return $head;
}

function payrollci_admin_prepare_head()
{
    global $langs;
    $h = 0; $head = array();

    $head[$h][0] = dol_buildpath('/payrollci/admin/setup.php', 1);
    $head[$h][1] = img_picto('', 'setup', 'class="pictofixedwidth"').'Configuration';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/admin/extrafields.php', 1);
    $head[$h][1] = img_picto('', 'generic', 'class="pictofixedwidth"').'Champs complémentaires';
    $head[$h][2] = 'extrafields';
    $h++;

    $head[$h][0] = dol_buildpath('/payrollci/admin/about.php', 1);
    $head[$h][1] = img_picto('', 'info', 'class="pictofixedwidth"').'À propos';
    $head[$h][2] = 'about';
    $h++;

    return $head;
}

function payrollci_get_mois()
{
    return array(
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    );
}

function payrollci_get_situations()
{
    return array(
        'celibataire'       => 'Célibataire',
        'marie'             => 'Marié(e)',
        'divorce'           => 'Divorcé(e)',
        'veuf'              => 'Veuf/Veuve',
        'celibataire_enfant'=> 'Célibataire avec enfant(s)',
    );
}

function payrollci_format_amount($amount)
{
    return number_format(round($amount), 0, ',', ' ');
}
