-- ============================================================================
-- Module PayrollCI v3 - Table extrafields pour bulletins de paie
-- Permet d'ajouter des champs personnalisés via l'admin Dolibarr
-- ============================================================================

CREATE TABLE llx_payrollci_payslip_extrafields (
    rowid       INTEGER AUTO_INCREMENT PRIMARY KEY,
    tms         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object   INTEGER NOT NULL,
    import_key  VARCHAR(14)
) ENGINE=InnoDB;

ALTER TABLE llx_payrollci_payslip_extrafields ADD INDEX idx_payrollci_payslip_extrafields_fk_object (fk_object);
