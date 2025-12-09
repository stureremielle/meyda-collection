-- meyda_privileges.sql
-- NOTE: On many shared hosts (including AlwaysData) you cannot create DB users via SQL;
-- create DB users via the hosting control panel. The SQL below is valid for self-managed MySQL.

CREATE USER IF NOT EXISTS 'meyda_admin'@'localhost' IDENTIFIED BY 'ChangeThisStrong!2025';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER ON meyda_collection.* TO 'meyda_admin'@'localhost';

CREATE USER IF NOT EXISTS 'meyda_report'@'localhost' IDENTIFIED BY 'ReadOnly2025!';
GRANT SELECT ON meyda_collection.* TO 'meyda_report'@'localhost';

FLUSH PRIVILEGES;

-- If your host disallows CREATE USER, create users in the control panel and grant appropriate rights.
