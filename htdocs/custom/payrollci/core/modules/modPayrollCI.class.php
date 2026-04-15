<?php
/* ============================================================================
 * PayrollCI v3 - Module descriptor - Intégration complète Dolibarr
 * ============================================================================ */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modPayrollCI extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;
        $this->numero = 500000;
        $this->rights_class = 'payrollci';
        $this->family = "hr";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Gestion complète de la paie - Droit ivoirien 2026 (v3)";
        $this->descriptionlong = "Module de paie 100% intégré à Dolibarr, conforme au droit du travail de Côte d'Ivoire. Inclut tous les éléments CCI, charges CNPS, ITS, charges fiscales patronales (IE, FDFP), avantages en nature, heures supplémentaires, transport exonéré, ancienneté auto. Onglet paie sur les fiches utilisateurs/employés.";
        $this->editor_name = 'PayrollCI';
        $this->version = '3.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'payrollci@payrollci';

        // Module parts
        $this->module_parts = array(
            'triggers' => 1,
            'login'    => 0,
            'substitutions' => 0,
            'menus'    => 0,
            'tpl'      => 0,
            'barcode'  => 0,
            'models'   => 1,
            'theme'    => 0,
            'css'      => array(),
            'hooks'    => array(
                'data' => array('usercard', 'globalcard'),
                'entity' => '0',
            ),
            'moduleforexternal' => 0,
        );

        $this->dirs = array('/payrollci/temp', '/payrollci/bulletins');
        $this->config_page_url = array("setup.php@payrollci");

        // Onglet "Bulletins de paie" sur les fiches utilisateurs
        $this->tabs = array(
            'user:+payrollci:BulletinsDePaie:payrollci@payrollci:$conf->payrollci->enabled:/payrollci/tab_user.php?fk_user=__ID__',
        );

        // Dictionnaires
        $this->dictionaries = array();

        // Boxes / Widgets
        $this->boxes = array(
            0 => array(
                'file'    => 'box_payrollci_last@payrollci',
                'note'    => 'Derniers bulletins de paie',
                'enabledbydefaulton' => 'Home',
            ),
        );

        // Cron jobs
        $this->cronjobs = array();

        // Constantes
        $this->const = array(
            0 => array('PAYROLLCI_DEFAULT_SECTEUR', 'chaine', 'commerce', 'Secteur par défaut', 0, 'current', 1),
            1 => array('PAYROLLCI_DEFAULT_VILLE', 'chaine', 'abidjan', 'Ville par défaut', 0, 'current', 1),
        );

        // Permissions
        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 500001;
        $this->rights[$r][1] = 'Lire les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'lire';
        $this->rights[$r][5] = '';
        $r++;
        $this->rights[$r][0] = 500002;
        $this->rights[$r][1] = 'Créer/modifier les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
        $this->rights[$r][5] = '';
        $r++;
        $this->rights[$r][0] = 500003;
        $this->rights[$r][1] = 'Supprimer les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';
        $this->rights[$r][5] = '';
        $r++;
        $this->rights[$r][0] = 500004;
        $this->rights[$r][1] = 'Exporter les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';
        $this->rights[$r][5] = '';

        // Menus
        $this->menu = array();
        $r = 0;

        // Menu principal dans HRM
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm',
            'type'     => 'left',
            'titre'    => 'Paie CI',
            'prefix'   => img_picto('', 'payrollci@payrollci', 'class="paddingright"'),
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

        // Sous-menu : Nouveau bulletin
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Nouveau bulletin',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_new',
            'url'      => '/payrollci/card.php?action=create',
            'langs'    => 'payrollci@payrollci',
            'position' => 101,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->creer',
        );
        $r++;

        // Sous-menu : Liste des bulletins
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Liste des bulletins',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_list',
            'url'      => '/payrollci/list.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 102,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
        );
        $r++;

        // Sous-menu : Tableau de bord
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Tableau de bord',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_dashboard',
            'url'      => '/payrollci/dashboard.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 103,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->lire',
        );
        $r++;

        // Sous-menu : Configuration (admin seulement)
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm,fk_leftmenu=payrollci',
            'type'     => 'left',
            'titre'    => 'Configuration',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci_setup',
            'url'      => '/payrollci/admin/setup.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 110,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->admin',
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
