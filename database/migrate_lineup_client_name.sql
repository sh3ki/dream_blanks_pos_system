-- Add client_name column to project_lineups so walk-in name is preserved
ALTER TABLE `project_lineups`
    ADD COLUMN `client_name` VARCHAR(255) NULL DEFAULT NULL AFTER `invoice_id`;

-- Backfill existing rows from the invoice → client relationship
UPDATE `project_lineups` pl
LEFT JOIN `invoices` i ON i.id = pl.invoice_id
LEFT JOIN `clients`  c ON c.id = i.client_id
SET pl.client_name = COALESCE(c.full_name, 'Walk-in')
WHERE pl.deleted_at IS NULL AND pl.client_name IS NULL;
