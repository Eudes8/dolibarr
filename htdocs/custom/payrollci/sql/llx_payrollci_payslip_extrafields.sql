-- PayrollCI v4 - Extrafields table
CREATE TABLE IF NOT EXISTS llx_payrollci_payslip_extrafields (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object INTEGER NOT NULL,
    import_key VARCHAR(14)
) ENGINE=innodb;

ALTER TABLE llx_payrollci_payslip_extrafields ADD INDEX idx_payrollci_payslip_extrafields_fk (fk_object);
