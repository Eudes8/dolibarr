<?php
/* ============================================================================
 * PayrollCI v2 - Classe métier Bulletin de Paie (complète)
 * Gestion CRUD avec tous les éléments de rémunération ivoiriens
 * ============================================================================
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/payrollci/class/payrollci_calc.class.php');

class Payslip extends CommonObject
{
    public $module = 'payrollci';
    public $element = 'payslip';
    public $table_element = 'payrollci_payslip';
    public $picto = 'payrollci@payrollci';

    const STATUS_DRAFT     = 0;
    const STATUS_VALIDATED = 1;

    // Employé
    public $fk_user;
    public $employee_name;
    public $employee_job;
    public $employee_category;
    public $employee_echelon;
    public $numero_cnps;
    public $numero_cmu;
    public $matricule;

    // Période
    public $date_start;
    public $date_end;

    // Situation familiale
    public $situation_familiale = 'celibataire';
    public $nombre_enfants = 0;
    public $nombre_parts = 1.0;

    // Salaire de base
    public $salaire_base = 0;
    public $sursalaire = 0;

    // Primes CCI
    public $prime_anciennete = 0;
    public $prime_rendement = 0;
    public $prime_technicite = 0;
    public $prime_fonction = 0;
    public $prime_responsabilite = 0;
    public $prime_risque = 0;
    public $prime_outillage = 0;
    public $prime_salissure = 0;
    public $prime_caisse = 0;
    public $prime_assiduite = 0;
    public $prime_panier = 0;
    public $gratification = 0;

    // Indemnités
    public $indemnite_transport = 0;
    public $transport_non_imposable = 0;
    public $indemnite_logement = 0;
    public $indemnite_representation = 0;
    public $indemnite_expatriation = 0;
    public $indemnite_deplacement = 0;
    public $indemnite_kilometrique = 0;

    // Avantages en nature
    public $avantage_nature_logement = 0;
    public $avantage_nature_vehicule = 0;
    public $avantage_nature_domestique = 0;
    public $avantage_nature_nourriture = 0;
    public $avantage_nature_autres = 0;

    // Heures supplémentaires
    public $heures_sup_15 = 0;
    public $heures_sup_50 = 0;
    public $heures_sup_75 = 0;
    public $heures_sup_100 = 0;

    // Autres gains
    public $conges_payes = 0;
    public $autres_primes = 0;

    // Totaux bruts
    public $salaire_brut = 0;
    public $brut_imposable = 0;

    // CNPS salarié
    public $cnps_retraite_sal = 0;
    public $cmu_sal = 0;

    // CNPS patronal
    public $cnps_retraite_pat = 0;
    public $cnps_pf_pat = 0;
    public $cnps_at_pat = 0;
    public $cmu_pat = 0;

    // Charges fiscales patronales
    public $impot_employeur = 0;
    public $fdfp_ta = 0;
    public $fdfp_fpc = 0;

    // ITS
    public $its_is = 0;
    public $its_cn = 0;
    public $its_igr = 0;
    public $its_total = 0;

    // Totaux
    public $total_retenues_sal = 0;
    public $total_charges_sociales = 0;
    public $total_charges_fiscales = 0;
    public $total_charges_pat = 0;
    public $salaire_net_imposable = 0;
    public $salaire_net = 0;

    // Déductions
    public $avance_salaire = 0;
    public $pret_deduction = 0;
    public $pension_alimentaire = 0;
    public $saisie_arret = 0;
    public $mutuelle_complementaire = 0;
    public $autres_retenues = 0;
    public $net_a_payer = 0;

    // Paramètres
    public $secteur_activite = 'commerce';
    public $taux_at = 2.00;
    public $ville = 'abidjan';
    public $anciennete_mois = 0;

    // Statut
    public $status = 0;

    /**
     * Liste de tous les champs numériques de rémunération (pour boucles)
     */
    private static $allNumericFields = [
        'salaire_base', 'sursalaire',
        'prime_anciennete', 'prime_rendement', 'prime_technicite',
        'prime_fonction', 'prime_responsabilite', 'prime_risque',
        'prime_outillage', 'prime_salissure', 'prime_caisse',
        'prime_assiduite', 'prime_panier', 'gratification',
        'indemnite_transport', 'transport_non_imposable',
        'indemnite_logement', 'indemnite_representation',
        'indemnite_expatriation', 'indemnite_deplacement', 'indemnite_kilometrique',
        'avantage_nature_logement', 'avantage_nature_vehicule',
        'avantage_nature_domestique', 'avantage_nature_nourriture', 'avantage_nature_autres',
        'heures_sup_15', 'heures_sup_50', 'heures_sup_75', 'heures_sup_100',
        'conges_payes', 'autres_primes',
        'salaire_brut', 'brut_imposable',
        'cnps_retraite_sal', 'cmu_sal',
        'cnps_retraite_pat', 'cnps_pf_pat', 'cnps_at_pat', 'cmu_pat',
        'impot_employeur', 'fdfp_ta', 'fdfp_fpc',
        'its_is', 'its_cn', 'its_igr', 'its_total',
        'total_retenues_sal', 'total_charges_sociales', 'total_charges_fiscales',
        'total_charges_pat', 'salaire_net_imposable', 'salaire_net',
        'avance_salaire', 'pret_deduction', 'pension_alimentaire',
        'saisie_arret', 'mutuelle_complementaire', 'autres_retenues',
        'net_a_payer',
    ];

    public $fields = array(
        'rowid'        => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0),
        'ref'          => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 4),
        'fk_user'      => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Employee', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1),
        'date_start'   => array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1),
        'date_end'     => array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1),
        'salaire_brut' => array('type' => 'price', 'label' => 'SalaireBrut', 'enabled' => 1, 'position' => 50, 'visible' => 1),
        'salaire_net'  => array('type' => 'price', 'label' => 'SalaireNet', 'enabled' => 1, 'position' => 60, 'visible' => 1),
        'net_a_payer'  => array('type' => 'price', 'label' => 'NetAPayer', 'enabled' => 1, 'position' => 70, 'visible' => 1),
        'status'       => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 80, 'visible' => 2),
    );

    public function __construct($db)
    {
        global $langs;
        $this->db = $db;
    }

    /**
     * Créer un nouveau bulletin
     */
    public function create($user, $notrigger = 0)
    {
        $this->ref = $this->getNextNumRef();

        $fields = [
            'ref', 'entity', 'fk_user', 'employee_name', 'employee_job',
            'employee_category', 'employee_echelon', 'numero_cnps', 'numero_cmu', 'matricule',
            'date_start', 'date_end', 'date_creation',
            'situation_familiale', 'nombre_enfants', 'nombre_parts',
        ];
        $values = [
            "'".$this->db->escape($this->ref)."'",
            ((int) ($this->entity ?? 1)),
            ((int) $this->fk_user),
            "'".$this->db->escape($this->employee_name)."'",
            "'".$this->db->escape($this->employee_job)."'",
            "'".$this->db->escape($this->employee_category)."'",
            "'".$this->db->escape($this->employee_echelon)."'",
            "'".$this->db->escape($this->numero_cnps)."'",
            "'".$this->db->escape($this->numero_cmu)."'",
            "'".$this->db->escape($this->matricule)."'",
            "'".$this->db->idate($this->date_start)."'",
            "'".$this->db->idate($this->date_end)."'",
            "'".$this->db->idate(dol_now())."'",
            "'".$this->db->escape($this->situation_familiale)."'",
            ((int) $this->nombre_enfants),
            ((float) $this->nombre_parts),
        ];

        // Tous les champs numériques
        foreach (self::$allNumericFields as $f) {
            $fields[] = $f;
            $values[] = ((float) ($this->$f ?? 0));
        }

        // Paramètres
        foreach (['secteur_activite', 'ville'] as $f) {
            $fields[] = $f;
            $values[] = "'".$this->db->escape($this->$f)."'";
        }
        $fields[] = 'taux_at';
        $values[] = ((float) $this->taux_at);
        $fields[] = 'anciennete_mois';
        $values[] = ((int) $this->anciennete_mois);
        $fields[] = 'status';
        $values[] = ((int) $this->status);
        $fields[] = 'fk_user_creat';
        $values[] = ((int) $user->id);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= implode(', ', $fields);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $values);
        $sql .= ")";

        $this->db->begin();
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Charger un bulletin
     */
    public function fetch($id, $ref = '')
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$this->table_element;
        if ($id > 0) {
            $sql .= " WHERE rowid = ".((int) $id);
        } else {
            $sql .= " WHERE ref = '".$this->db->escape($ref)."'";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->entity = $obj->entity;
                $this->fk_user = $obj->fk_user;
                $this->employee_name = $obj->employee_name;
                $this->employee_job = $obj->employee_job;
                $this->employee_category = $obj->employee_category;
                $this->employee_echelon = $obj->employee_echelon;
                $this->numero_cnps = $obj->numero_cnps;
                $this->numero_cmu = $obj->numero_cmu;
                $this->matricule = $obj->matricule;
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->situation_familiale = $obj->situation_familiale;
                $this->nombre_enfants = $obj->nombre_enfants;
                $this->nombre_parts = $obj->nombre_parts;

                // Tous les champs numériques
                foreach (self::$allNumericFields as $f) {
                    if (isset($obj->$f)) $this->$f = $obj->$f;
                }

                $this->secteur_activite = $obj->secteur_activite;
                $this->taux_at = $obj->taux_at;
                $this->ville = $obj->ville;
                $this->anciennete_mois = $obj->anciennete_mois;
                $this->status = $obj->status;
                $this->fk_user_creat = $obj->fk_user_creat;
                return 1;
            }
            return 0;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Lancer le calcul et remplir les champs
     */
    public function calculate()
    {
        // Collecter tous les paramètres
        $params = [];
        foreach (self::$allNumericFields as $f) {
            $params[$f] = $this->$f;
        }
        $params['situation_familiale'] = $this->situation_familiale;
        $params['nombre_enfants'] = $this->nombre_enfants;
        $params['taux_at'] = $this->taux_at / 100;
        $params['ville'] = $this->ville;

        $result = PayrollCICalc::calculerBulletin($params);

        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Valider le bulletin
     */
    public function validate($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " SET status = ".self::STATUS_VALIDATED;
        $sql .= ", date_valid = '".$this->db->idate(dol_now())."'";
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->status = self::STATUS_VALIDATED;
            return 1;
        }
        return -1;
    }

    /**
     * Référence suivante
     */
    public function getNextNumRef()
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 5) AS UNSIGNED)) as maxref";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE ref LIKE 'BP-%'";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $num = ($obj->maxref ? $obj->maxref + 1 : 1);
            return 'BP-'.str_pad($num, 6, '0', STR_PAD_LEFT);
        }
        return 'BP-000001';
    }

    public function getLibStatut($mode = 0) { return self::LibStatut($this->status, $mode); }
    public static function LibStatut($status, $mode = 0)
    {
        if ($status == self::STATUS_DRAFT) return 'Brouillon';
        if ($status == self::STATUS_VALIDATED) return 'Validé';
        return 'Inconnu';
    }

    public function getNomUrl($withpicto = 0, $notooltip = 0)
    {
        $url = dol_buildpath('/payrollci/card.php', 1).'?id='.$this->id;
        return '<a href="'.$url.'">'.$this->ref.'</a>';
    }
}
