-- ============================================================================
-- Module PayrollCI - Bulletin de Paie Côte d'Ivoire
-- Table principale des bulletins de paie
-- ============================================================================

CREATE TABLE llx_payrollci_payslip (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,
    entity          INTEGER DEFAULT 1 NOT NULL,

    -- Employé
    fk_user         INTEGER NOT NULL,
    employee_name   VARCHAR(255),
    employee_job    VARCHAR(255),
    employee_category VARCHAR(50),
    employee_echelon VARCHAR(50),
    numero_cnps     VARCHAR(50),
    numero_cmu      VARCHAR(50),

    -- Période
    date_start      DATE NOT NULL,
    date_end        DATE NOT NULL,
    date_creation   DATETIME,
    date_valid      DATETIME,

    -- Situation familiale pour IGR
    situation_familiale VARCHAR(20) DEFAULT 'celibataire',
    nombre_enfants  INTEGER DEFAULT 0,
    nombre_parts    DOUBLE(4,1) DEFAULT 1.0,

    -- Éléments de rémunération
    salaire_base        DOUBLE(24,8) DEFAULT 0,
    prime_anciennete    DOUBLE(24,8) DEFAULT 0,
    prime_transport     DOUBLE(24,8) DEFAULT 0,
    prime_logement      DOUBLE(24,8) DEFAULT 0,
    prime_responsabilite DOUBLE(24,8) DEFAULT 0,
    prime_salissure     DOUBLE(24,8) DEFAULT 0,
    heures_sup_25       DOUBLE(24,8) DEFAULT 0,
    heures_sup_50       DOUBLE(24,8) DEFAULT 0,
    autres_primes       DOUBLE(24,8) DEFAULT 0,
    conges_payes        DOUBLE(24,8) DEFAULT 0,
    salaire_brut        DOUBLE(24,8) DEFAULT 0,

    -- Retenues CNPS (part salariale)
    cnps_retraite_sal   DOUBLE(24,8) DEFAULT 0,
    cmu_sal             DOUBLE(24,8) DEFAULT 0,

    -- Charges patronales CNPS
    cnps_retraite_pat   DOUBLE(24,8) DEFAULT 0,
    cnps_pf_pat         DOUBLE(24,8) DEFAULT 0,
    cnps_at_pat         DOUBLE(24,8) DEFAULT 0,
    cmu_pat             DOUBLE(24,8) DEFAULT 0,

    -- ITS (Impôts sur Traitements et Salaires)
    its_is              DOUBLE(24,8) DEFAULT 0,
    its_cn              DOUBLE(24,8) DEFAULT 0,
    its_igr             DOUBLE(24,8) DEFAULT 0,
    its_total           DOUBLE(24,8) DEFAULT 0,

    -- Totaux
    total_retenues_sal  DOUBLE(24,8) DEFAULT 0,
    total_charges_pat   DOUBLE(24,8) DEFAULT 0,
    salaire_net_imposable DOUBLE(24,8) DEFAULT 0,
    salaire_net         DOUBLE(24,8) DEFAULT 0,

    -- Avances et acomptes
    avance_salaire      DOUBLE(24,8) DEFAULT 0,
    pret_deduction      DOUBLE(24,8) DEFAULT 0,
    autres_retenues     DOUBLE(24,8) DEFAULT 0,
    net_a_payer         DOUBLE(24,8) DEFAULT 0,

    -- Secteur d'activité (pour taux AT)
    secteur_activite    VARCHAR(100) DEFAULT 'commerce',
    taux_at             DOUBLE(6,2) DEFAULT 2.00,

    -- Statut
    status              INTEGER DEFAULT 0,
    note_private        TEXT,
    note_public         TEXT,

    fk_user_creat       INTEGER,
    fk_user_modif       INTEGER,
    tms                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    import_key          VARCHAR(14)
) ENGINE=InnoDB;
