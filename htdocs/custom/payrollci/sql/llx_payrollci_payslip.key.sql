-- PayrollCI v4 - Indexes
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_ref (ref);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_entity (entity);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_fk_user (fk_user);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_date_start (date_start);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_status (status);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_date_embauche (date_embauche);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_fk_soc (fk_soc);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_fk_project (fk_project);
