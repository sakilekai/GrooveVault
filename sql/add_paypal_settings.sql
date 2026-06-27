-- Add PayPal settings to an existing GrooveVault database.
-- Run in phpMyAdmin or: mysql -u root groovevault < sql/add_paypal_settings.sql

INSERT INTO settings (setting_key, setting_value) VALUES
  ('paypal_client_id',     ''),
  ('paypal_client_secret', ''),
  ('paypal_mode',          'sandbox')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
