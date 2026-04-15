<?php
/* PayrollCI v2 - Module descriptor */

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
        $this->description = "Gestion complète de la paie - Droit ivoirien 2026 (v2)";
        $this->descriptionlong = "Module de paie conforme au droit du travail de Côte d'Ivoire. Inclut tous les éléments CCI, charges CNPS, ITS, charges fiscales patronales (IE, FDFP), avantages en nature, heures supplémentaires (15/50/75/100%), transport exonéré, prime d'ancienneté auto.";
        $this->editor_name = 'PayrollCI';
        $this->version = '2.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'payrollci@payrollci';

        $this->module_parts = array('triggers' => 0);
        $this->dirs = array('/payrollci/temp', '/payrollci/bulletins');
        $this->config_page_url = array("setup.php@payrollci");

        $this->tabs = array();
        $this->dictionaries = array();
        $this->boxes = array();
        $this->cronjobs = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 500001;
        $this->rights[$r][1] = 'Lire les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;
        $this->rights[$r][0] = 500002;
        $this->rights[$r][1] = 'Créer/modifier les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;
        $this->rights[$r][0] = 500003;
        $this->rights[$r][1] = 'Supprimer les bulletins';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';

        $this->menu = array();
        $r = 0;
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm',
            'type'     => 'left',
            'titre'    => 'Bulletins de paie',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci',
            'url'      => '/payrollci/list.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 100,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->read',
            'target'   => '',
            'user'     => 0,
        );
        $r++;
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
            'perms'    => '$user->rights->payrollci->write',
        );
        $r++;
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
            'perms'    => '$user->rights->payrollci->read',
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
