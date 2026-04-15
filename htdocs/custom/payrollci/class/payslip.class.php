<?php
/* ============================================================================
 * PayrollCI - Classe métier Bulletin de Paie
 * Gestion CRUD et calcul des bulletins de paie
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

    // Période
    public $date_start;
    public $date_end;

    // Situation familiale
    public $situation_familiale = 'celibataire';
    public $nombre_enfants = 0;
    public $nombre_parts = 1.0;

    // Éléments de rémunération
    public $salaire_base = 0;
    public $prime_anciennete = 0;
    public $prime_transport = 0;
    public $prime_logement = 0;
    public $prime_responsabilite = 0;
    public $prime_salissure = 0;
    public $heures_sup_25 = 0;
    public $heures_sup_50 = 0;
    public $autres_primes = 0;
    public $conges_payes = 0;
    public $salaire_brut = 0;

    // CNPS
    public $cnps_retraite_sal = 0;
    public $cmu_sal = 0;
    public $cnps_retraite_pat = 0;
    public $cnps_pf_pat = 0;
    public $cnps_at_pat = 0;
    public $cmu_pat = 0;

    // ITS
    public $its_is = 0;
    public $its_cn = 0;
    public $its_igr = 0;
    public $its_total = 0;

    // Totaux
    public $total_retenues_sal = 0;
    public $total_charges_pat = 0;
    public $salaire_net_imposable = 0;
    public $salaire_net = 0;

    // Avances
    public $avance_salaire = 0;
    public $pret_deduction = 0;
    public $autres_retenues = 0;
    public $net_a_payer = 0;

    // Secteur
    public $secteur_activite = 'commerce';
    public $taux_at = 2.00;

    // Statut
    public $status = 0;

    /**
     * @var array Champs de la table
     */
    public $fields = array(
        'rowid'          => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0),
        'ref'            => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 4, 'showoncombobox' => 1),
        'fk_user'        => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Employee', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1),
        'date_start'     => array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1),
        'date_end'       => array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1),
        'salaire_brut'   => array('type' => 'price', 'label' => 'SalaireBrut', 'enabled' => 1, 'position' => 50, 'visible' => 1),
        'salaire_net'    => array('type' => 'price', 'label' => 'SalaireNet', 'enabled' => 1, 'position' => 60, 'visible' => 1),
        'net_a_payer'    => array('type' => 'price', 'label' => 'NetAPayer', 'enabled' => 1, 'position' => 70, 'visible' => 1),
        'status'         => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 80, 'visible' => 2),
    );

    /**
     * Constructor
     */
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

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= "ref, entity, fk_user, employee_name, employee_job, employee_category, employee_echelon,";
        $sql .= "numero_cnps, numero_cmu, date_start, date_end, date_creation,";
        $sql .= "situation_familiale, nombre_enfants, nombre_parts,";
        $sql .= "salaire_base, prime_anciennete, prime_transport, prime_logement,";
        $sql .= "prime_responsabilite, prime_salissure, heures_sup_25, heures_sup_50,";
        $sql .= "autres_primes, conges_payes, salaire_brut,";
        $sql .= "cnps_retraite_sal, cmu_sal, cnps_retraite_pat, cnps_pf_pat, cnps_at_pat, cmu_pat,";
        $sql .= "its_is, its_cn, its_igr, its_total,";
        $sql .= "total_retenues_sal, total_charges_pat, salaire_net_imposable, salaire_net,";
        $sql .= "avance_salaire, pret_deduction, autres_retenues, net_a_payer,";
        $sql .= "secteur_activite, taux_at, status, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."'";
        $sql .= ", ".((int) $this->entity);
        $sql .= ", ".((int) $this->fk_user);
        $sql .= ", '".$this->db->escape($this->employee_name)."'";
        $sql .= ", '".$this->db->escape($this->employee_job)."'";
        $sql .= ", '".$this->db->escape($this->employee_category)."'";
        $sql .= ", '".$this->db->escape($this->employee_echelon)."'";
        $sql .= ", '".$this->db->escape($this->numero_cnps)."'";
        $sql .= ", '".$this->db->escape($this->numero_cmu)."'";
        $sql .= ", '".$this->db->idate($this->date_start)."'";
        $sql .= ", '".$this->db->idate($this->date_end)."'";
        $sql .= ", '".$this->db->idate(dol_now())."'";
        $sql .= ", '".$this->db->escape($this->situation_familiale)."'";
        $sql .= ", ".((int) $this->nombre_enfants);
        $sql .= ", ".((float) $this->nombre_parts);
        $sql .= ", ".((float) $this->salaire_base);
        $sql .= ", ".((float) $this->prime_anciennete);
        $sql .= ", ".((float) $this->prime_transport);
        $sql .= ", ".((float) $this->prime_logement);
        $sql .= ", ".((float) $this->prime_responsabilite);
        $sql .= ", ".((float) $this->prime_salissure);
        $sql .= ", ".((float) $this->heures_sup_25);
        $sql .= ", ".((float) $this->heures_sup_50);
        $sql .= ", ".((float) $this->autres_primes);
        $sql .= ", ".((float) $this->conges_payes);
        $sql .= ", ".((float) $this->salaire_brut);
        $sql .= ", ".((float) $this->cnps_retraite_sal);
        $sql .= ", ".((float) $this->cmu_sal);
        $sql .= ", ".((float) $this->cnps_retraite_pat);
        $sql .= ", ".((float) $this->cnps_pf_pat);
        $sql .= ", ".((float) $this->cnps_at_pat);
        $sql .= ", ".((float) $this->cmu_pat);
        $sql .= ", ".((float) $this->its_is);
        $sql .= ", ".((float) $this->its_cn);
        $sql .= ", ".((float) $this->its_igr);
        $sql .= ", ".((float) $this->its_total);
        $sql .= ", ".((float) $this->total_retenues_sal);
        $sql .= ", ".((float) $this->total_charges_pat);
        $sql .= ", ".((float) $this->salaire_net_imposable);
        $sql .= ", ".((float) $this->salaire_net);
        $sql .= ", ".((float) $this->avance_salaire);
        $sql .= ", ".((float) $this->pret_deduction);
        $sql .= ", ".((float) $this->autres_retenues);
        $sql .= ", ".((float) $this->net_a_payer);
        $sql .= ", '".$this->db->escape($this->secteur_activite)."'";
        $sql .= ", ".((float) $this->taux_at);
        $sql .= ", ".((int) $this->status);
        $sql .= ", ".((int) $user->id);
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
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->situation_familiale = $obj->situation_familiale;
                $this->nombre_enfants = $obj->nombre_enfants;
                $this->nombre_parts = $obj->nombre_parts;
                $this->salaire_base = $obj->salaire_base;
                $this->prime_anciennete = $obj->prime_anciennete;
                $this->prime_transport = $obj->prime_transport;
                $this->prime_logement = $obj->prime_logement;
                $this->prime_responsabilite = $obj->prime_responsabilite;
                $this->prime_salissure = $obj->prime_salissure;
                $this->heures_sup_25 = $obj->heures_sup_25;
                $this->heures_sup_50 = $obj->heures_sup_50;
                $this->autres_primes = $obj->autres_primes;
                $this->conges_payes = $obj->conges_payes;
                $this->salaire_brut = $obj->salaire_brut;
                $this->cnps_retraite_sal = $obj->cnps_retraite_sal;
                $this->cmu_sal = $obj->cmu_sal;
                $this->cnps_retraite_pat = $obj->cnps_retraite_pat;
                $this->cnps_pf_pat = $obj->cnps_pf_pat;
                $this->cnps_at_pat = $obj->cnps_at_pat;
                $this->cmu_pat = $obj->cmu_pat;
                $this->its_is = $obj->its_is;
                $this->its_cn = $obj->its_cn;
                $this->its_igr = $obj->its_igr;
                $this->its_total = $obj->its_total;
                $this->total_retenues_sal = $obj->total_retenues_sal;
                $this->total_charges_pat = $obj->total_charges_pat;
                $this->salaire_net_imposable = $obj->salaire_net_imposable;
                $this->salaire_net = $obj->salaire_net;
                $this->avance_salaire = $obj->avance_salaire;
                $this->pret_deduction = $obj->pret_deduction;
                $this->autres_retenues = $obj->autres_retenues;
                $this->net_a_payer = $obj->net_a_payer;
                $this->secteur_activite = $obj->secteur_activite;
                $this->taux_at = $obj->taux_at;
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
        $params = [
            'salaire_base'         => $this->salaire_base,
            'prime_anciennete'     => $this->prime_anciennete,
            'prime_transport'      => $this->prime_transport,
            'prime_logement'       => $this->prime_logement,
            'prime_responsabilite' => $this->prime_responsabilite,
            'prime_salissure'      => $this->prime_salissure,
            'heures_sup_25'        => $this->heures_sup_25,
            'heures_sup_50'        => $this->heures_sup_50,
            'autres_primes'        => $this->autres_primes,
            'conges_payes'         => $this->conges_payes,
            'situation_familiale'  => $this->situation_familiale,
            'nombre_enfants'       => $this->nombre_enfants,
            'taux_at'              => $this->taux_at / 100,
            'avance_salaire'       => $this->avance_salaire,
            'pret_deduction'       => $this->pret_deduction,
            'autres_retenues'      => $this->autres_retenues,
        ];

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
     * Générer la référence suivante
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

    /**
     * Retourner le libellé du statut
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     * Libellé statut
     */
    public static function LibStatut($status, $mode = 0)
    {
        if ($status == self::STATUS_DRAFT) return 'Brouillon';
        if ($status == self::STATUS_VALIDATED) return 'Validé';
        return 'Inconnu';
    }

    /**
     * Retourner lien cliquable
     */
    public function getNomUrl($withpicto = 0, $notooltip = 0)
    {
        $url = dol_buildpath('/payrollci/card.php', 1).'?id='.$this->id;
        $label = '<u>Bulletin de Paie</u><br><b>Réf:</b> '.$this->ref;
        $link = '<a href="'.$url.'" title="'.dol_escape_htmltag($label).'">';
        $linkend = '</a>';
        $result = $link.$this->ref.$linkend;
        return $result;
    }
}
