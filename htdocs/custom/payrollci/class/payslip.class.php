<?php
/* ============================================================================
 * PayrollCI v3 - Classe métier Bulletin de Paie (intégration Dolibarr)
 * CRUD complet, extrafields, notes, documents, événements, objets liés
 * ============================================================================ */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/payrollci/class/payrollci_calc.class.php');

class Payslip extends CommonObject
{
    public $module = 'payrollci';
    public $element = 'payslip';
    public $table_element = 'payrollci_payslip';
    public $table_element_line = '';
    public $fk_element = 'fk_payslip';
    public $picto = 'payrollci@payrollci';
    public $ismultientitymanaged = 1;

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
    public $date_creation;
    public $date_valid;

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

    // Notes
    public $note_private;
    public $note_public;

    // Statut
    public $status = 0;

    /**
     * Liste de tous les champs numériques (pour boucles INSERT/UPDATE/FETCH)
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
        'ref'          => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 4, 'showoncombobox' => 1, 'searchall' => 1),
        'fk_user'      => array('type' => 'integer:User:user/class/user.class.php:0', 'label' => 'Employee', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1),
        'employee_name' => array('type' => 'varchar(255)', 'label' => 'EmployeeName', 'enabled' => 1, 'position' => 25, 'visible' => 1, 'searchall' => 1),
        'date_start'   => array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1),
        'date_end'     => array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1),
        'salaire_brut' => array('type' => 'price', 'label' => 'SalaireBrut', 'enabled' => 1, 'position' => 50, 'visible' => 1),
        'salaire_net'  => array('type' => 'price', 'label' => 'SalaireNet', 'enabled' => 1, 'position' => 60, 'visible' => 1),
        'net_a_payer'  => array('type' => 'price', 'label' => 'NetAPayer', 'enabled' => 1, 'position' => 70, 'visible' => 1),
        'status'       => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 80, 'visible' => 2),
        'note_public'  => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'position' => 200, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'position' => 201, 'visible' => 0),
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
        global $conf;

        $error = 0;
        $this->ref = $this->getNextNumRef();

        $fields = [
            'ref', 'entity', 'fk_user', 'employee_name', 'employee_job',
            'employee_category', 'employee_echelon', 'numero_cnps', 'numero_cmu', 'matricule',
            'date_start', 'date_end', 'date_creation',
            'situation_familiale', 'nombre_enfants', 'nombre_parts',
            'note_public', 'note_private',
        ];
        $values = [
            "'".$this->db->escape($this->ref)."'",
            ((int) ($conf->entity ?? 1)),
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
            "'".$this->db->escape($this->note_public)."'",
            "'".$this->db->escape($this->note_private)."'",
        ];

        foreach (self::$allNumericFields as $f) {
            $fields[] = $f;
            $values[] = ((float) ($this->$f ?? 0));
        }

        foreach (['secteur_activite', 'ville'] as $f) {
            $fields[] = $f;
            $values[] = "'".$this->db->escape($this->$f)."'";
        }
        $fields[] = 'taux_at';     $values[] = ((float) $this->taux_at);
        $fields[] = 'anciennete_mois'; $values[] = ((int) $this->anciennete_mois);
        $fields[] = 'status';      $values[] = ((int) $this->status);
        $fields[] = 'fk_user_creat'; $values[] = ((int) $user->id);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (".implode(', ', $fields).")";
        $sql .= " VALUES (".implode(', ', $values).")";

        $this->db->begin();
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            // Extrafields
            if (!$error) {
                $result = $this->insertExtraFields();
                if ($result < 0) $error++;
            }

            // Trigger
            if (!$error && !$notrigger) {
                $result = $this->call_trigger('PAYSLIP_CREATE', $user);
                if ($result < 0) $error++;
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                return -1;
            }
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
        if ($id > 0) $sql .= " WHERE rowid = ".((int) $id);
        else $sql .= " WHERE ref = '".$this->db->escape($ref)."'";

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
                $this->date_valid = $this->db->jdate($obj->date_valid);
                $this->situation_familiale = $obj->situation_familiale;
                $this->nombre_enfants = $obj->nombre_enfants;
                $this->nombre_parts = $obj->nombre_parts;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;

                foreach (self::$allNumericFields as $f) {
                    if (isset($obj->$f)) $this->$f = $obj->$f;
                }

                $this->secteur_activite = $obj->secteur_activite;
                $this->taux_at = $obj->taux_at;
                $this->ville = $obj->ville;
                $this->anciennete_mois = $obj->anciennete_mois;
                $this->status = $obj->status;
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;

                // Extrafields
                $this->fetch_optionals();

                return 1;
            }
            return 0;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Mettre à jour un bulletin (V3)
     */
    public function update($user, $notrigger = 0)
    {
        $error = 0;

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " fk_user = ".((int) $this->fk_user);
        $sql .= ", employee_name = '".$this->db->escape($this->employee_name)."'";
        $sql .= ", employee_job = '".$this->db->escape($this->employee_job)."'";
        $sql .= ", employee_category = '".$this->db->escape($this->employee_category)."'";
        $sql .= ", employee_echelon = '".$this->db->escape($this->employee_echelon)."'";
        $sql .= ", numero_cnps = '".$this->db->escape($this->numero_cnps)."'";
        $sql .= ", numero_cmu = '".$this->db->escape($this->numero_cmu)."'";
        $sql .= ", matricule = '".$this->db->escape($this->matricule)."'";
        $sql .= ", date_start = '".$this->db->idate($this->date_start)."'";
        $sql .= ", date_end = '".$this->db->idate($this->date_end)."'";
        $sql .= ", situation_familiale = '".$this->db->escape($this->situation_familiale)."'";
        $sql .= ", nombre_enfants = ".((int) $this->nombre_enfants);
        $sql .= ", nombre_parts = ".((float) $this->nombre_parts);
        $sql .= ", note_public = '".$this->db->escape($this->note_public)."'";
        $sql .= ", note_private = '".$this->db->escape($this->note_private)."'";

        foreach (self::$allNumericFields as $f) {
            $sql .= ", ".$f." = ".((float) ($this->$f ?? 0));
        }

        $sql .= ", secteur_activite = '".$this->db->escape($this->secteur_activite)."'";
        $sql .= ", taux_at = ".((float) $this->taux_at);
        $sql .= ", ville = '".$this->db->escape($this->ville)."'";
        $sql .= ", anciennete_mois = ".((int) $this->anciennete_mois);
        $sql .= ", fk_user_modif = ".((int) $user->id);
        $sql .= " WHERE rowid = ".((int) $this->id);

        $this->db->begin();
        $resql = $this->db->query($sql);
        if ($resql) {
            // Extrafields
            if (!$error) {
                $result = $this->insertExtraFields();
                if ($result < 0) $error++;
            }
            // Trigger
            if (!$error && !$notrigger) {
                $result = $this->call_trigger('PAYSLIP_MODIFY', $user);
                if ($result < 0) $error++;
            }
            if (!$error) {
                $this->db->commit();
                return 1;
            }
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    /**
     * Supprimer un bulletin
     */
    public function delete($user, $notrigger = 0)
    {
        $error = 0;
        $this->db->begin();

        // Trigger avant suppression
        if (!$notrigger) {
            $result = $this->call_trigger('PAYSLIP_DELETE', $user);
            if ($result < 0) $error++;
        }

        if (!$error) {
            // Supprimer extrafields
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields WHERE fk_object = ".((int) $this->id);
            $this->db->query($sql);

            // Supprimer l'objet
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
            if ($this->db->query($sql)) {
                // Supprimer les fichiers PDF
                $this->deleteDocuments();
                $this->db->commit();
                return 1;
            }
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    /**
     * Lancer le calcul et remplir les champs
     */
    public function calculate()
    {
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
    public function validate($user, $notrigger = 0)
    {
        $error = 0;
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " SET status = ".self::STATUS_VALIDATED;
        $sql .= ", date_valid = '".$this->db->idate(dol_now())."'";
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->status = self::STATUS_VALIDATED;
            $this->date_valid = dol_now();

            if (!$notrigger) {
                $result = $this->call_trigger('PAYSLIP_VALIDATE', $user);
                if ($result < 0) $error++;
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    /**
     * Remettre en brouillon
     */
    public function setDraft($user, $notrigger = 0)
    {
        if ($this->status != self::STATUS_VALIDATED) return 0;

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " SET status = ".self::STATUS_DRAFT;
        $sql .= " WHERE rowid = ".((int) $this->id);

        if ($this->db->query($sql)) {
            $this->status = self::STATUS_DRAFT;
            if (!$notrigger) $this->call_trigger('PAYSLIP_UNVALIDATE', $user);
            return 1;
        }
        return -1;
    }

    /**
     * Générer le document PDF via le système Dolibarr
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf;

        $outputlangs->loadLangs(array("payrollci@payrollci"));
        dol_include_once('/payrollci/core/modules/payrollci/doc/pdf_bulletinpaie.modules.php');

        $modelclass = 'pdf_bulletinpaie';
        $srctemplatepath = '';

        $obj = new $modelclass($this->db);
        $result = $obj->write_file($this, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);
        if ($result > 0) {
            $this->last_main_doc = $obj->result['fullpath'] ?? '';
            return 1;
        }
        $this->error = $obj->error;
        return -1;
    }

    /**
     * Supprimer les documents associés
     */
    public function deleteDocuments()
    {
        global $conf;
        $dir = $conf->payrollci->dir_output.'/bulletins/';

        // PDF direct
        $file = $dir.$this->ref.'.pdf';
        if (file_exists($file)) dol_delete_file($file);

        // Répertoire
        $subdir = $dir.dol_sanitizeFileName($this->ref);
        if (is_dir($subdir)) dol_delete_dir_recursive($subdir);
    }

    /**
     * Remplir les infos employé depuis llx_user (V3 : intégration)
     */
    public function fetchUserInfo()
    {
        if (empty($this->fk_user)) return 0;

        $userobj = new User($this->db);
        $result = $userobj->fetch($this->fk_user);
        if ($result > 0) {
            $this->employee_name = trim($userobj->firstname.' '.$userobj->lastname);
            if (empty($this->employee_name)) $this->employee_name = $userobj->login;
            $this->employee_job = $userobj->job ?? '';
            $this->employee_category = $userobj->employee_category ?? '';
            // fk_user : user Dolibarr → numéro, etc. (extrafields si configurés)
            return 1;
        }
        return -1;
    }

    /**
     * Référence suivante
     */
    public function getNextNumRef()
    {
        global $conf;
        $prefix = $conf->global->PAYROLLCI_REF_PREFIX ?? 'BP';
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, ".(strlen($prefix) + 2).") AS UNSIGNED)) as maxref";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE ref LIKE '".$this->db->escape($prefix)."-%'";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $num = ($obj->maxref ? $obj->maxref + 1 : 1);
            return $prefix.'-'.str_pad($num, 6, '0', STR_PAD_LEFT);
        }
        return $prefix.'-000001';
    }

    /**
     * Libellé du statut
     */
    public function getLibStatut($mode = 0)
    {
        return self::LibStatut($this->status, $mode);
    }

    public static function LibStatut($status, $mode = 0)
    {
        global $langs;
        if ($mode == 0) {
            if ($status == self::STATUS_DRAFT) return '<span class="badge badge-status0">Brouillon</span>';
            if ($status == self::STATUS_VALIDATED) return '<span class="badge badge-status4">Validé</span>';
        } elseif ($mode == 1) {
            if ($status == self::STATUS_DRAFT) return 'Brouillon';
            if ($status == self::STATUS_VALIDATED) return 'Validé';
        }
        return 'Inconnu';
    }

    /**
     * URL cliquable avec ou sans picto
     */
    public function getNomUrl($withpicto = 0, $notooltip = 0, $maxlen = 0)
    {
        global $conf, $langs;

        $url = dol_buildpath('/payrollci/card.php', 1).'?id='.$this->id;
        $label = '<u>Bulletin de Paie</u><br>';
        $label .= '<b>Réf :</b> '.$this->ref.'<br>';
        if (!empty($this->employee_name)) $label .= '<b>Employé :</b> '.$this->employee_name.'<br>';
        if (!empty($this->net_a_payer)) $label .= '<b>Net à payer :</b> '.payrollci_format_amount($this->net_a_payer).' FCFA';

        $linkstart = '<a href="'.$url.'"';
        if (empty($notooltip)) {
            $linkstart .= ' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip"';
        }
        $linkstart .= '>';
        $linkend = '</a>';

        $result = $linkstart;
        if ($withpicto) $result .= img_object($label, 'payrollci@payrollci', 'class="paddingright classfortooltip"');
        $result .= $this->ref;
        $result .= $linkend;

        return $result;
    }

    /**
     * Informations pour la fiche (affichage standard Dolibarr)
     */
    public function info($id)
    {
        $sql = "SELECT date_creation, tms, fk_user_creat, fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->tms);
                $this->user_creation_id = $obj->fk_user_creat;
                $this->user_modification_id = $obj->fk_user_modif;
            }
        }
    }

    /**
     * Retourne le nombre de bulletins pour un utilisateur
     */
    public function countForUser($fk_user)
    {
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_user = ".((int) $fk_user);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->nb;
        }
        return 0;
    }
}
