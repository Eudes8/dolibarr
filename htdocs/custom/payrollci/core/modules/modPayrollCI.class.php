<?php
/* ============================================================================
 * Module PayrollCI - Descripteur de module Dolibarr
 * Gestion de la paie conforme au droit ivoirien (2026)
 * ============================================================================
 */

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
        $this->description = "Gestion de la paie et génération de bulletins de paie - Droit ivoirien 2026";
        $this->descriptionlong = "Module complet de gestion de la paie conforme au droit du travail de Côte d'Ivoire. "
            ."Calcul automatique des cotisations CNPS (retraite, prestations familiales, AT/MP, CMU) "
            ."et des impôts ITS (IS, CN, IGR avec quotient familial). "
            ."Génération de bulletins de paie en PDF.";

        $this->editor_name = 'PayrollCI';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'payrollci@payrollci';

        $this->module_parts = array(
            'triggers' => 0,
            'login'    => 0,
            'models'   => 1,
        );

        $this->dirs = array(
            "/payrollci/temp",
        );

        $this->config_page_url = array("setup.php@payrollci");

        $this->depends      = array();
        $this->requiredby    = array();
        $this->conflictwith  = array();
        $this->langfiles     = array("payrollci@payrollci");

        // Constantes
        $this->const = array(
            0 => array('PAYROLLCI_COMPANY_NAME', 'chaine', '', 'Nom de l\'entreprise', 0, 'current', 1),
            1 => array('PAYROLLCI_COMPANY_ADDRESS', 'chaine', '', 'Adresse de l\'entreprise', 0, 'current', 1),
            2 => array('PAYROLLCI_COMPANY_CNPS', 'chaine', '', 'Numéro CNPS employeur', 0, 'current', 1),
            3 => array('PAYROLLCI_COMPANY_CC', 'chaine', '', 'Registre du Commerce', 0, 'current', 1),
            4 => array('PAYROLLCI_DEFAULT_SECTEUR', 'chaine', 'commerce', 'Secteur d\'activité par défaut', 0, 'current', 1),
        );

        // Tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero + 1;
        $this->rights[$r][1] = 'Lire les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = $this->numero + 2;
        $this->rights[$r][1] = 'Créer/Modifier les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = $this->numero + 3;
        $this->rights[$r][1] = 'Valider les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'validate';
        $r++;

        $this->rights[$r][0] = $this->numero + 4;
        $this->rights[$r][1] = 'Supprimer les bulletins de paie';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        // Menu principal
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=hrm',
            'type'     => 'left',
            'titre'    => 'Paie CI',
            'prefix'   => '<span class="fas fa-money-bill-wave paddingright"></span>',
            'mainmenu' => 'hrm',
            'leftmenu' => 'payrollci',
            'url'      => '/payrollci/list.php',
            'langs'    => 'payrollci@payrollci',
            'position' => 1000 + $r,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->read',
            'target'   => '',
            'user'     => 0,
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
            'position' => 1000 + $r,
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
            'position' => 1000 + $r,
            'enabled'  => '$conf->payrollci->enabled',
            'perms'    => '$user->rights->payrollci->write',
            'target'   => '',
            'user'     => 0,
        );
        $r++;
    }

    /**
     * Fonction appelée lors de l'activation du module
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/payrollci/sql/');
        if ($result < 0) return -1;

        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     * Fonction appelée lors de la désactivation du module
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
