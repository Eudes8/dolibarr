-- ============================================================================
-- Module PayrollCI v4 - Bulletin de Paie Côte d'Ivoire
-- Conforme à l'Ordonnance n° 2023-719 du 13/09/2023 (Réforme ITS)
-- Sources: CGI CI 2024, CCI, Code du Travail, CNPS, FDFP
-- ============================================================================

CREATE TABLE llx_payrollci_payslip (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,
    entity          INTEGER DEFAULT 1 NOT NULL,

    -- ===== EMPLOYÉ =====
    fk_user         INTEGER NOT NULL,
    employee_name   VARCHAR(255),
    employee_job    VARCHAR(255),
    employee_category VARCHAR(50),
    employee_echelon VARCHAR(50),
    numero_cnps     VARCHAR(50),
    numero_cmu      VARCHAR(50),
    matricule       VARCHAR(50),
    is_expatrie     TINYINT(1) DEFAULT 0,

    -- ===== DATES =====
    date_embauche   DATE,
    date_start      DATE NOT NULL,
    date_end        DATE NOT NULL,
    date_creation   DATETIME,
    date_valid      DATETIME,

    -- ===== SITUATION FAMILIALE (RICF) =====
    situation_familiale VARCHAR(20) DEFAULT 'celibataire',
    nombre_enfants  INTEGER DEFAULT 0,
    nombre_parts    DOUBLE(4,1) DEFAULT 1.0,

    -- ===== SALAIRE DE BASE =====
    salaire_base        DOUBLE(24,8) DEFAULT 0,
    sursalaire          DOUBLE(24,8) DEFAULT 0,

    -- ===== PRIMES (Convention Collective CI) =====
    prime_anciennete    DOUBLE(24,8) DEFAULT 0,
    prime_rendement     DOUBLE(24,8) DEFAULT 0,
    prime_technicite    DOUBLE(24,8) DEFAULT 0,
    prime_fonction      DOUBLE(24,8) DEFAULT 0,
    prime_responsabilite DOUBLE(24,8) DEFAULT 0,
    prime_risque        DOUBLE(24,8) DEFAULT 0,
    prime_outillage     DOUBLE(24,8) DEFAULT 0,
    prime_salissure     DOUBLE(24,8) DEFAULT 0,
    prime_caisse        DOUBLE(24,8) DEFAULT 0,
    prime_assiduite     DOUBLE(24,8) DEFAULT 0,
    prime_panier        DOUBLE(24,8) DEFAULT 0,
    gratification       DOUBLE(24,8) DEFAULT 0,

    -- ===== INDEMNITÉS =====
    indemnite_transport     DOUBLE(24,8) DEFAULT 0,
    transport_non_imposable DOUBLE(24,8) DEFAULT 0,
    indemnite_logement      DOUBLE(24,8) DEFAULT 0,
    indemnite_representation DOUBLE(24,8) DEFAULT 0,
    indemnite_expatriation  DOUBLE(24,8) DEFAULT 0,
    indemnite_deplacement   DOUBLE(24,8) DEFAULT 0,
    indemnite_kilometrique  DOUBLE(24,8) DEFAULT 0,

    -- ===== AVANTAGES EN NATURE =====
    avantage_nature_logement  DOUBLE(24,8) DEFAULT 0,
    avantage_nature_vehicule  DOUBLE(24,8) DEFAULT 0,
    avantage_nature_domestique DOUBLE(24,8) DEFAULT 0,
    avantage_nature_nourriture DOUBLE(24,8) DEFAULT 0,
    avantage_nature_autres    DOUBLE(24,8) DEFAULT 0,

    -- ===== HEURES SUPPLÉMENTAIRES =====
    heures_sup_15       DOUBLE(24,8) DEFAULT 0,
    heures_sup_50       DOUBLE(24,8) DEFAULT 0,
    heures_sup_75       DOUBLE(24,8) DEFAULT 0,
    heures_sup_100      DOUBLE(24,8) DEFAULT 0,

    -- ===== CONGÉS & AUTRES GAINS =====
    conges_payes        DOUBLE(24,8) DEFAULT 0,
    autres_primes       DOUBLE(24,8) DEFAULT 0,

    -- ===== TOTAUX BRUTS =====
    salaire_brut            DOUBLE(24,8) DEFAULT 0,
    brut_imposable          DOUBLE(24,8) DEFAULT 0,

    -- ===== RETENUES SALARIALES CNPS =====
    cnps_retraite_sal   DOUBLE(24,8) DEFAULT 0,
    cmu_sal             DOUBLE(24,8) DEFAULT 0,

    -- ===== CHARGES PATRONALES CNPS =====
    cnps_retraite_pat   DOUBLE(24,8) DEFAULT 0,
    cnps_pf_pat         DOUBLE(24,8) DEFAULT 0,
    cnps_at_pat         DOUBLE(24,8) DEFAULT 0,
    cmu_pat             DOUBLE(24,8) DEFAULT 0,

    -- ===== CHARGES FISCALES PATRONALES (Réforme 2024) =====
    contribution_employeur DOUBLE(24,8) DEFAULT 0,
    fdfp_ta             DOUBLE(24,8) DEFAULT 0,
    fdfp_fpc            DOUBLE(24,8) DEFAULT 0,

    -- ===== ITS NOUVEAU RÉGIME (Ordonnance 2023-719) =====
    its_ibs             DOUBLE(24,8) DEFAULT 0,
    its_ricf            DOUBLE(24,8) DEFAULT 0,
    its_total           DOUBLE(24,8) DEFAULT 0,

    -- ===== TOTAUX =====
    total_retenues_sal      DOUBLE(24,8) DEFAULT 0,
    total_charges_sociales  DOUBLE(24,8) DEFAULT 0,
    total_charges_fiscales  DOUBLE(24,8) DEFAULT 0,
    total_charges_pat       DOUBLE(24,8) DEFAULT 0,
    salaire_net_imposable   DOUBLE(24,8) DEFAULT 0,
    salaire_net             DOUBLE(24,8) DEFAULT 0,

    -- ===== DÉDUCTIONS DIVERSES =====
    avance_salaire      DOUBLE(24,8) DEFAULT 0,
    pret_deduction      DOUBLE(24,8) DEFAULT 0,
    pension_alimentaire DOUBLE(24,8) DEFAULT 0,
    saisie_arret        DOUBLE(24,8) DEFAULT 0,
    mutuelle_complementaire DOUBLE(24,8) DEFAULT 0,
    autres_retenues     DOUBLE(24,8) DEFAULT 0,

    -- ===== NET À PAYER =====
    net_a_payer         DOUBLE(24,8) DEFAULT 0,

    -- ===== PARAMÈTRES =====
    secteur_activite    VARCHAR(100) DEFAULT 'commerce',
    taux_at             DOUBLE(6,2) DEFAULT 2.00,
    ville               VARCHAR(50) DEFAULT 'abidjan',
    anciennete_mois     INTEGER DEFAULT 0,

    -- ===== LIENS DOLIBARR =====
    fk_soc              INTEGER DEFAULT NULL,
    fk_project          INTEGER DEFAULT NULL,
    fk_contrat          INTEGER DEFAULT NULL,

    -- ===== STATUT =====
    status              INTEGER DEFAULT 0,
    note_private        TEXT,
    note_public         TEXT,

    fk_user_creat       INTEGER,
    fk_user_modif       INTEGER,
    tms                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    import_key          VARCHAR(14)
) ENGINE=InnoDB;
