# Setting Up School Databases on Evolution Hosting

Evolution hosting doesn't use cPanel. Here are your options to create school databases.

## Your School Databases Needed:

1. `school_tanzania_menneonite_secondary_school_1772473837`
2. `school_tanzania_menneonite_secondary_school_1772473991`

User: `olamtecc_darasa_v2` (needs ALL PRIVILEGES)

---

## First: Check if Databases Already Exist

Run this script to see if the databases are already created:

```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz
php deployment/check-databases-exist.php
```

If they already exist, you're done! Just clear caches and test.

---

## Option 1: Evolution Web Control Panel

Evolution likely has a custom web-based control panel.

### How to access:

1. Check your Evolution hosting welcome email for control panel URL
2. Common Evolution panel URLs:
   - `https://panel.evolution.co.tz`
   - `https://manage.evolution.co.tz`
   - `https://cp.evolution.co.tz`
   - Or ask Evolution support for the panel URL

### Once in the panel:

1. Look for "Databases" or "MySQL" section
2. Create new databases with the names above
3. Assign user `olamtecc_darasa_v2` with ALL privileges

---

## Option 2: Contact Evolution Support (RECOMMENDED)

If you can't find the database management interface, contact Evolution support.

### Support Contact Methods:

- **Email**: support@evolution.co.tz (check your welcome email for correct address)
- **Phone**: Check Evolution website for support number
- **Support Ticket**: Login to Evolution client area and create ticket

### What to Tell Them:

Copy and paste this template:

```
Subject: Request to Create MySQL Databases for School Management System

Hello Evolution Support Team,

I need help creating MySQL databases for my school management application
on domain: darasa360.olamtec.co.tz

Please create the following databases and grant permissions:

Database 1: school_tanzania_menneonite_secondary_school_1772473837
Database 2: school_tanzania_menneonite_secondary_school_1772473991

MySQL User: olamtecc_darasa_v2
Required Privileges: ALL PRIVILEGES on both databases

Character Set: utf8mb4
Collation: utf8mb4_unicode_ci

Thank you for your assistance!
```

---

## Option 3: Command Line (If You Have MySQL Root Access)

If Evolution gave you MySQL root credentials, you can create them via SSH:

```bash
mysql -u root -p
```

Then run:

```sql
CREATE DATABASE school_tanzania_menneonite_secondary_school_1772473837
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE school_tanzania_menneonite_secondary_school_1772473991
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON school_tanzania_menneonite_secondary_school_1772473837.*
    TO 'olamtecc_darasa_v2'@'localhost';

GRANT ALL PRIVILEGES ON school_tanzania_menneonite_secondary_school_1772473991.*
    TO 'olamtecc_darasa_v2'@'localhost';

-- Wildcard for future schools
GRANT ALL PRIVILEGES ON school_%.* TO 'olamtecc_darasa_v2'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

---

## Option 4: Check if Databases Auto-Created

Some Laravel applications can auto-create databases. Let's try:

```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz

php artisan tinker --execute="
    try {
        \$db = 'school_tanzania_menneonite_secondary_school_1772473837';
        DB::statement(\"CREATE DATABASE IF NOT EXISTS \`\$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\");
        echo 'Database created!\n';
    } catch (Exception \$e) {
        echo 'Cannot create database: ' . \$e->getMessage() . '\n';
        echo 'You need to use another method.\n';
    }
"
```

If this fails, use Option 1 or 2 above.

---

## After Databases Are Created

### Step 1: Verify databases exist

```bash
php deployment/check-databases-exist.php
```

Should show "✓ Database EXISTS and you have access!"

### Step 2: Clear Laravel caches

```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### Step 3: Test database connectivity

```bash
php artisan tinker --execute="
    \$schools = DB::table('schools')->where('status', 'active')->get();
    foreach (\$schools as \$school) {
        preg_match('/\/([^\/]+)$/', \$school->database_url, \$matches);
        \$dbName = \$matches[1];

        echo \"Testing \$dbName... \";
        try {
            config(['database.connections.test.database' => \$dbName]);
            DB::connection('test')->getPdo();
            echo \"✓ OK\n\";
        } catch (Exception \$e) {
            echo \"✗ FAILED: \" . \$e->getMessage() . \"\n\";
        }
    }
"
```

### Step 4: Test school features

1. Visit: https://darasa360.olamtec.co.tz
2. Login as admin
3. Click on school to view details - should work!
4. Login as school owner - should work!

---

## Troubleshooting

### "Databases already exist but still getting 500 errors"

The databases might exist but without proper permissions. Contact Evolution support:

```
Subject: Grant MySQL User Permissions

Hello,

User 'olamtecc_darasa_v2' needs ALL PRIVILEGES on these databases:
- school_tanzania_menneonite_secondary_school_1772473837
- school_tanzania_menneonite_secondary_school_1772473991

Please grant the permissions.

Thank you!
```

### "Database name too long"

Evolution might have a character limit. Ask them to:
1. Create with full name, OR
2. Allow longer database names, OR
3. We can shorten the database names in the application

### "Still getting errors after databases created"

Check Laravel logs:

```bash
tail -100 storage/logs/laravel.log
```

Share the error with me and I'll help debug.

---

## Quick Summary

1. ✅ Run `check-databases-exist.php` to see current status
2. ✅ Contact Evolution support with the template above
3. ✅ Wait for databases to be created (usually quick)
4. ✅ Clear caches
5. ✅ Test school features
6. ✅ Everything works! 🎉

---

**Most likely, you'll need to contact Evolution support. They're usually very responsive and can create the databases quickly.**
