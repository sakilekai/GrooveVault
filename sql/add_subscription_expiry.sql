-- Subscription expiry support: store when each subscription lapses and allow an
-- 'expired' status. Monthly plans last 1 month, annual plans last 1 year.

ALTER TABLE subscriptions
    ADD COLUMN expires_at DATETIME NULL AFTER started_at;

ALTER TABLE subscriptions
    MODIFY COLUMN status ENUM('active','suspended','cancelled','expired') NOT NULL DEFAULT 'active';

-- Backfill expiry for existing active subscriptions based on their billing period.
UPDATE subscriptions
   SET expires_at = DATE_ADD(COALESCE(started_at, created_at), INTERVAL 1 MONTH)
 WHERE status = 'active' AND billing_period = 'monthly' AND expires_at IS NULL;

UPDATE subscriptions
   SET expires_at = DATE_ADD(COALESCE(started_at, created_at), INTERVAL 1 YEAR)
 WHERE status = 'active' AND billing_period = 'annual' AND expires_at IS NULL;
