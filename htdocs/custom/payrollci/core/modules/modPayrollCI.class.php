<?php
/* ============================================================================
 * PayrollCI v4 - Module descriptor
 * ============================================================================ */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modPayrollCI extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero = 500100;
        $this->rights_class = 'payrollci';
        $this->family = "hr";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module de paie Côte d'Ivoire - Réforme ITS 2024 (IBS/RICF)";
        $this->descriptionlong = "Gestion de la paie conforme au droit ivoirien. Calcul CNPS, ITS (IBS+RICF), charges patronales, génération PDF style Sage, états réglementaires.";
        $this->editor_name = 'PayrollCI';
        $this->version = '4.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'payrollci@payrollci';
        $this->module_parts = array('triggers' => 1, 'hooks' => array('usercard'));

        $this->dirs = array('/payrollci/temp', '/payrollci/bulletins');
        $this->config_page_url = array("setup.php@payrollci");

        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Permissions
        $r = 0;
        $this->rights[$r][0] = 500101;
        $this->rights[$r][1] = 'Lire les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'lire';
        $r++;
        $this->rights[$r][0] = 500102;
        $this->rights[$r][1] = 'Créer/modifier les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
        $r++;
        $this->rights[$r][0] = 500103;
        $this->rights[$r][1] = 'Valider les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'valider';
        $r++;
        $this->rights[$r][0] = 500104;
        $this->rights[$r][1] = 'Supprimer les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        // Top menu under HRM
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm',
            'type'     => 'left',
            'titre'    => 'Paie CI',
            'prefix'   => img_picto('', 'payrollci@payrollci', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci',
            'url'      => '/payrollci/list.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 100,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Nouveau bulletin',
            'prefix'   => img_picto('', 'add', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_new',
            'url'      => '/payrollci/card.php?action=create',
            'langs'    => 'payrollci@payrollci',
            'position' => 101,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->creer',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Liste des bulletins',
            'prefix'   => img_picto('', 'list', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_list',
            'url'      => '/payrollci/list.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 102,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Tableau de bord',
            'prefix'   => img_picto('', 'chart', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_dashboard',
            'url'      => '/payrollci/dashboard.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 103,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        // Reports submenu
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Livre de paie',
            'prefix'   => img_picto('', 'list', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_livrepaie',
            'url'      => '/payrollci/report_livrepaie.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 110,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'État 301',
            'prefix'   => img_picto('', 'tax', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_etat301',
            'url'      => '/payrollci/report_etat301.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 111,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Journal de paie',
            'prefix'   => img_picto('', 'accountancy', 'class="pictofixedwidth"'),
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_journal',
            'url'      => '/payrollci/report_journal.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 112,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
            'target'   => '',
            'user'     => 0,
        );
        $r++;

        // Tabs
        $this->tabs = array(
            'user:+payrollci:Bulletins de paie:payrollci@payrollci:$conf->payrollci->enabled:/payrollci/tab_user.php?fk_user=__ID__',
        );
    }

    public function init($options = '')
    {
        $sql = array();
        $result = $this->_load_tables('/payrollci/sql/');
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
