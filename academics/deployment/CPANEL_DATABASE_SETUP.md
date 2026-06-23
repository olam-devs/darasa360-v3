# Create School Databases via cPanel

Since you don't have GRANT privileges, use cPanel's MySQL Database interface instead of phpMyAdmin SQL.

## Your School Databases:

Based on your schools, you need to create and grant permissions for:

1. `school_tanzania_menneonite_secondary_school_1772473837`
2. `school_tanzania_menneonite_secondary_school_1772473991`

Your database user: `olamtecc_darasa_v2`

---

## Step-by-Step Instructions

### Step 1: Login to cPanel

Access your cPanel (usually provided by your hosting provider)

### Step 2: Open MySQL Databases

1. Find and click **"MySQL Databases"** (in the Databases section)

### Step 3: Create Each School Database

For each database name above:

#### 3a. Create Database

1. Scroll to **"Create New Database"** section
2. In the "New Database" field, enter:
   ```
   school_tanzania_menneonite_secondary_school_1772473837
   ```
   ⚠️ **IMPORTANT**: Enter the FULL name exactly as shown above

3. Click **"Create Database"**

4. You should see a success message

5. Click **"Go Back"**

6. Repeat for the second database:
   ```
   school_tanzania_menneonite_secondary_school_1772473991
   ```

### Step 4: Add User to Databases

Now grant your user access to these databases:

1. Scroll down to **"Add User To Database"** section

2. In the **"User"** dropdown, select:
   ```
   olamtecc_darasa_v2
   ```

3. In the **"Database"** dropdown, select:
   ```
   olamtecc_school_tanzania_menneonite_secondary_school_1772473837
   ```
   (Note: cPanel may add a prefix like "olamtecc_" automatically)

4. Click **"Add"**

5. On the next page (Manage User Privileges):
   - Click **"ALL PRIVILEGES"** checkbox at the top
   - Or manually check all boxes
   - Click **"Make Changes"**

6. You should see "User added to database"

7. **REPEAT** for the second database:
   - User: `olamtecc_darasa_v2`
   - Database: `olamtecc_school_tanzania_menneonite_secondary_school_1772473991`
   - Grant ALL PRIVILEGES

### Step 5: Verify Databases Were Created

1. Scroll to **"Current Databases"** section
2. You should see your newly created databases listed:
   - `olamtecc_school_tanzania_menneonite_secondary_school_1772473837`
   - `olamtecc_school_tanzania_menneonite_secondary_school_1772473991`

3. Next to each database, you should see your user `olamtecc_darasa_v2` listed

---

## Important Notes

### Database Name Prefixes

cPanel might automatically add your username as a prefix to database names:
- You enter: `school_tanzania_menneonite_secondary_school_1772473837`
- cPanel creates: `olamtecc_school_tanzania_menneonite_secondary_school_1772473837`

**This is NORMAL and OK!** The application will still work because the database_url already contains the correct database name.

### Character Limits

If the database name is too long (over 64 characters), cPanel will give an error. In that case:

**Option A: Create with shortened name**
- Instead of the full name, use: `school_tmss_1772473837`
- Update the database_url in the schools table to match

**Option B: Contact hosting support**
- Ask them to increase the database name character limit
- Or ask them to create the databases for you

---

## After Creating Databases

### Step 1: Clear Laravel Caches

```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### Step 2: Test Database Access

Create a test script:

```bash
cat > test-school-db.sh << 'EOF'
#!/bin/bash
php artisan tinker --execute="
    \$schools = DB::table('schools')->get();
    foreach (\$schools as \$school) {
        echo \"Testing {\$school->name}...\n\";
        try {
            // Extract database name from URL
            preg_match('/\/([^\/]+)$/', \$school->database_url, \$matches);
            \$dbName = \$matches[1];

            // Test connection
            config(['database.connections.tenant.database' => \$dbName]);
            DB::connection('tenant')->getPdo();
            echo \"  ✓ Database {\$dbName} accessible!\n\";
        } catch (Exception \$e) {
            echo \"  ✗ Failed: \" . \$e->getMessage() . \"\n\";
        }
    }
"
EOF

chmod +x test-school-db.sh
bash test-school-db.sh
```

### Step 3: Try School Features

1. Visit: https://darasa360.olamtec.co.tz
2. Login as admin
3. View school details - should work now!
4. Login as school owner - should work now!

---

## Troubleshooting

### Error: "Database name too long"

The full database names are very long. If cPanel rejects them:

1. Go back to your server
2. Update the database names in the schools table:

```bash
php artisan tinker --execute="
    DB::table('schools')->where('id', 1)->update([
        'database_url' => 'mysql://username:password@localhost/school_tmss_1772473837'
    ]);
    DB::table('schools')->where('id', 2)->update([
        'database_url' => 'mysql://username:password@localhost/school_tmss_1772473991'
    ]);
    echo 'Updated database URLs\n';
"
```

3. Then create databases in cPanel with the shorter names:
   - `school_tmss_1772473837`
   - `school_tmss_1772473991`

### Error: "Database already exists"

Good! It means the database was created. Just skip to Step 4 and add your user to it.

### Error: "User already added to database"

Good! It means permissions were already granted. Skip to the testing step.

### Still getting 500 errors?

Check the Laravel logs:

```bash
tail -50 storage/logs/laravel.log
```

Look for database connection errors and share them.

---

## Quick Summary

1. ✅ Login to cPanel
2. ✅ Go to MySQL Databases
3. ✅ Create database: `school_tanzania_menneonite_secondary_school_1772473837`
4. ✅ Create database: `school_tanzania_menneonite_secondary_school_1772473991`
5. ✅ Add user `olamtecc_darasa_v2` to first database (ALL PRIVILEGES)
6. ✅ Add user `olamtecc_darasa_v2` to second database (ALL PRIVILEGES)
7. ✅ Clear caches: `php artisan config:clear && php artisan cache:clear`
8. ✅ Test: `bash test-school-db.sh`
9. ✅ Try viewing school and logging in as owner

---

**That's it! Once you complete these steps, all school features will work!**
