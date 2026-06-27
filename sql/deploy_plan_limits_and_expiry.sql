-- ============================================================================
-- GrooveVault — deployment migration
-- Bundles two changes:
--   1) Pro plan channel limit = 12 (Starter 5, Annual unlimited)
--   2) Subscription expiry (fixed-term, manual renewal): monthly = 1 month,
--      annual = 1 year. Adds expires_at + an 'expired' status and backfills.
--
-- Safe to run once. Re-running the ALTERs may error with "duplicate column" /
-- be a no-op (harmless); the UPDATEs are idempotent.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Per-plan channel limits  (NULL = unlimited)
-- ---------------------------------------------------------------------------
UPDATE plans SET channel_limit = 5    WHERE code = 'starter';
UPDATE plans SET channel_limit = 12   WHERE code = 'pro';
UPDATE plans SET channel_limit = NULL WHERE code = 'annual';

-- ---------------------------------------------------------------------------
-- 2. Subscription expiry support
-- ---------------------------------------------------------------------------
ALTER TABLE subscriptions
    ADD COLUMN expires_at DATETIME NULL AFTER started_at;

ALTER TABLE subscriptions
    MODIFY COLUMN status ENUM('active','suspended','cancelled','expired') NOT NULL DEFAULT 'active';

-- Backfill expiry for subscriptions that already exist.
UPDATE subscriptions
   SET expires_at = DATE_ADD(COALESCE(started_at, created_at), INTERVAL 1 MONTH)
 WHERE status = 'active' AND billing_period = 'monthly' AND expires_at IS NULL;

UPDATE subscriptions
   SET expires_at = DATE_ADD(COALESCE(started_at, created_at), INTERVAL 1 YEAR)
 WHERE status = 'active' AND billing_period = 'annual' AND expires_at IS NULL;
