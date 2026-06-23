# Grant School Database Permissions via phpMyAdmin

Since you have phpMyAdmin access but not MySQL root access, follow these steps:

## Method 1: Run the PHP Script (EASIEST)

### Step 1: Upload the script to your server

```bash
# The script is already in your deployment folder
# Just make sure it's uploaded to the server
```

### Step 2: Run the script

**Option A: Via browser**
```
https://darasa360.olamtec.co.tz/deployment/grant-permissions-phpmyadmin.php
```

**Option B: Via command line**
```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz
php deployment/grant-permissions-phpmyadmin.php
```

This will:
- Show you all school databases
- Try to grant permissions automatically
- Generate SQL file if automatic grant fails
- Show you exact SQL commands to run

---

## Method 2: Manual phpMyAdmin Steps

### Step 1: Get the SQL commands

Run this on your server:
```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz
php deployment/grant-permissions-phpmyadmin.php > permissions.txt
cat permissions.txt
```

Or just run the script and copy the SQL commands shown.

### Step 2: Login to phpMyAdmin

Access phpMyAdmin through your cPanel or hosting control panel.

### Step 3: Open SQL Tab

1. Click on **any database** in the left sidebar (doesn't matter which one)
2. Click the **"SQL"** tab at the top
3. You'll see a text area where you can enter SQL commands

### Step 4: Copy and Paste SQL Commands

Copy the SQL commands from the script output and paste them into the SQL tab.

The commands will look like this:

```sql
-- School: Your School Name
CREATE DATABASE IF NOT EXISTS `olamtecc_olam_school_001` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `olamtecc_olam_school_001`.* TO 'olamtecc_darasa_v2'@'localhost';

-- Wildcard permissions for future schools
GRANT ALL PRIVILEGES ON `olam_school_%`.* TO 'olamtecc_darasa_v2'@'localhost';
GRANT ALL PRIVILEGES ON `olamtecc_olam_school_%`.* TO 'olamtecc_darasa_v2'@'localhost';

FLUSH PRIVILEGES;
```

### Step 5: Click "Go"

Click the **"Go"** button at the bottom of the SQL tab.

### Step 6: Verify Success

You should see a message like:
- "Your SQL query has been executed successfully"
- Or a green checkmark

---

## Method 3: Using phpMyAdmin User Privileges Interface

### Step 1: Access User Accounts

1. In phpMyAdmin, click on **"User accounts"** tab at the top
2. Find your user: `olamtecc_darasa_v2`
3. Click **"Edit privileges"** next to the user

### Step 2: Grant Database Privileges

1. Scroll down to **"Database-specific privileges"**
2. Select your school database from dropdown (e.g., `olamtecc_olam_school_001`)
3. Click **"Go"**

### Step 3: Check All Privileges

1. Click **"Check all"** to select all privileges
2. OR manually check these essential privileges:
   - SELECT
   - INSERT
   - UPDATE
   - DELETE
   - CREATE
   - DROP
   - ALTER
   - INDEX
   - CREATE TEMPORARY TABLES
   - LOCK TABLES
   - EXECUTE
   - CREATE VIEW
   - SHOW VIEW
   - CREATE ROUTINE
   - ALTER ROUTINE
   - EVENT
   - TRIGGER

3. Click **"Go"** to save

### Step 4: Repeat for Each School Database

Repeat Steps 2-3 for each school database you created.

---

## After Granting Permissions

### Test the permissions:

```bash
cd /home/olamtecc/domains/darasa360.olamtec.co.tz

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan optimize

# Test school database access
bash test-permissions.sh
```

### Then try:

1. ✅ View school details in admin dashboard
2. ✅ Login as school owner
3. ✅ Access all school features

---

## Troubleshooting

### Error: "Access denied"

This means you need to use Method 2 or 3 above. Your database user doesn't have permission to grant privileges to other databases.

### Error: "Database does not exist"

The school database wasn't created. Run this in phpMyAdmin SQL tab:

```sql
CREATE DATABASE IF NOT EXISTS `your_school_database_name`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Replace `your_school_database_name` with the actual database name from your school.

### Still getting 500 errors?

1. Check Laravel logs:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

2. Make sure database name in `schools` table matches the actual database name in phpMyAdmin

3. Verify permissions were granted by going to phpMyAdmin → User accounts → Edit privileges for your user

---

## Quick Reference: What Database Names to Use

Your school databases follow this pattern:
- Main database: `olamtecc_darasa_v2`
- School databases: `olamtecc_olam_school_XXX` (where XXX is the school code)

To see all your school databases, run:
```bash
php artisan tinker --execute="DB::table('schools')->pluck('database_name')->each(fn(\$db) => print(\$db.'\n'));"
```

---

**After completing these steps, all school-related 500 errors should be resolved!**
