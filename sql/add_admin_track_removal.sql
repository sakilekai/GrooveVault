-- GrooveVault — admin moderation of individual tracks.
-- Lets an operator "remove" a single track/video from a channel without
-- deleting the row: it stays visible to the owner/public marked
-- "Removed by admin" and becomes unplayable.

ALTER TABLE tracks
  ADD COLUMN removed_by_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER position,
  ADD COLUMN removed_at       DATETIME NULL DEFAULT NULL    AFTER removed_by_admin,
  ADD COLUMN removed_by       INT UNSIGNED NULL DEFAULT NULL AFTER removed_at;
