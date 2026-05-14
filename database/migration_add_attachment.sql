-- Add optional attachment (picture) support to incidents.
-- Run this if your database already exists and was created before this column existed.
-- Usage: mysql -u root -p campus_incident_system < database/migration_add_attachment.sql

USE campus_incident_system;

ALTER TABLE incidents ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL AFTER status;
