-- ============================================================================
-- Module PayrollCI v2 - Index et contraintes
-- ============================================================================

ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_ref (ref);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_fk_user (fk_user);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_date (date_start, date_end);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_status (status);
ALTER TABLE llx_payrollci_payslip ADD INDEX idx_payrollci_payslip_entity (entity);
ALTER TABLE llx_payrollci_payslip ADD UNIQUE INDEX uk_payrollci_payslip_ref (ref, entity);
