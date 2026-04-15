ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_ref (ref);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_entity (entity);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_fk_user (fk_user);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_status (status);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_dates (date_start, date_end);
ALTER TABLE llx_payrollci_payslip ADD UNIQUE INDEX uk_payrollci_payslip_ref (ref, entity);
ALTER TABLE llx_payrollci_payslip ADD CONSTRAINT fk_payrollci_payslip_user FOREIGN KEY (fk_user) REFERENCES llx_user(rowid);
