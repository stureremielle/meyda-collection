# MeyDa Collection - Minimal PHP + MySQL Shop

Files added:
- `meyda_schema.sql` - DDL: 7 tables + constraints
- `meyda_seed.sql` - DML: small seed data (limited products)
- `meyda_privileges.sql` - DCL examples (create user / grant)
- `config.php` - PDO database config (edit before deploy)
- `index.php` - Minimal PHP storefront (products, cart, checkout)
- `styles.css` - Neutral styling, no gradients, few images

Requirements
- PHP 7.4+ (8.x recommended) with `pdo_mysql` enabled
- MySQL 5.7+ or MariaDB equivalent

Quick deploy (AlwaysData / shared host)
1. Create a MySQL database and user in the AlwaysData control panel.
2. Edit `config.php` with the DB host/name/user/password and set `DEFAULT_USER_ID` to a valid `user` id created by the seed (or create one via the control panel).
3. Import the schema and seed using phpMyAdmin or the `mysql` CLI:

```powershell
mysql -u your_db_user -p -h your_db_host your_db_name < meyda_schema.sql
mysql -u your_db_user -p -h your_db_host your_db_name < meyda_seed.sql
```

4. Upload `index.php`, `config.php`, and `styles.css` to your web root (WWW) using SFTP/FTP or the host file manager.
5. Ensure PHP version is set correctly in the control panel and HTTPS is enabled.

Security notes
- Replace placeholder password hashes in `meyda_seed.sql` with real hashes using `password_hash()` in PHP.
- Do not commit real DB credentials to source control.
- For production: add authentication, CSRF protection, input validation, and HTTPS.

If you want, I can:
- Add a simple staff login page
- Help import the SQL into AlwaysData step-by-step
- Harden `config.php` to use environment variables
