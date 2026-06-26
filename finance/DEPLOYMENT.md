# Darasa Finance - Deployment Guide

## Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM (for asset compilation)
- Web server (Apache/Nginx)
- SSL certificate (recommended for production)

## Step 1: Server Setup

### 1.1 Clone the Repository
```bash
git clone <your-repo-url> /var/www/darasa-finance
cd /var/www/darasa-finance/darasa_finance_app
```

### 1.2 Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 1.3 Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Step 2: Environment Configuration

### 2.1 Create Environment File
```bash
cp .env.example .env
php artisan key:generate
```

### 2.2 Configure Database Connections

Edit `.env` file with your database credentials:

```env
# Main Application Settings
APP_NAME="Darasa Finance"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Tenant Database (School Data)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=darasa_finance
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Central Database (SuperAdmin, Schools Management)
CENTRAL_DB_DATABASE=darasa_central

# Tenant Database (usually same as DB_DATABASE)
TENANT_DB_DATABASE=darasa_finance

# SMS Configuration (Optional)
SMS_API_TOKEN=your_sms_api_token
SMS_SENDER_NAME="DARASA 360"
```

### 2.3 Domain Configuration

For multiple domains/subdomains, configure your web server:

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name darasa360.com www.darasa360.com;
    root /var/www/darasa-finance/darasa_finance_app/public;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name darasa360.com www.darasa360.com;
    root /var/www/darasa-finance/darasa_finance_app/public;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Step 3: Database Setup

### 3.1 Create Databases
```sql
CREATE DATABASE darasa_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE darasa_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON darasa_central.* TO 'your_db_user'@'localhost';
GRANT ALL PRIVILEGES ON darasa_finance.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3.2 Run Migrations

```bash
# Run central database migrations (SuperAdmin, Schools, etc.)
php artisan migrate --database=central --path=database/migrations/central

# Run tenant database migrations (School data)
php artisan migrate
```

### 3.3 Setup Default SuperAdmin and Sync School

```bash
# This command will:
# 1. Create default super admin (admin@darasa360.com / Darasa@2024)
# 2. Sync existing school from tenant database to central
php artisan central:setup --create-admin --sync-school
```

**Important:** Change the default password immediately after first login!

## Step 4: SuperAdmin Access

### Default Credentials
- **URL:** `https://your-domain.com/superadmin/login`
- **Email:** `admin@darasa360.com`
- **Password:** `Darasa@2024`

### Changing SuperAdmin Password
After logging in:
1. Go to Dashboard
2. Click on Profile/Settings
3. Change password

Or via command line:
```bash
php artisan tinker
>>> $admin = \App\Models\Central\SuperAdmin::first();
>>> $admin->update(['password' => bcrypt('NewSecurePassword123!')]);
```

## Step 5: Post-Deployment Tasks

### 5.1 Cache Configuration (Production)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.2 Set Up Scheduler (Cron Job)
Add to crontab (`crontab -e`):
```
* * * * * cd /var/www/darasa-finance/darasa_finance_app && php artisan schedule:run >> /dev/null 2>&1
```

### 5.3 Set Up Queue Worker (Optional)
If using queues for SMS sending:
```bash
# Using Supervisor
[program:darasa-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/darasa-finance/darasa_finance_app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

## Step 6: Adding New Schools

### Option A: Auto-Create Database
1. Login to SuperAdmin
2. Go to Schools > Create New School
3. Leave "Use Existing Database" unchecked
4. Fill in school details
5. System will auto-create database with all tables

### Option B: Use Existing Database
1. Create database manually with schema
2. Login to SuperAdmin
3. Go to Schools > Create New School
4. Check "Use Existing Database"
5. Enter the database name
6. Fill in school details

## Troubleshooting

### SMS Credits Not Showing
```bash
# Sync school data
php artisan central:setup --sync-school

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Permission Errors
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .
```

### Database Connection Issues
Check `.env` settings and ensure:
1. Both databases exist
2. User has access to both databases
3. Correct credentials are set

### View Debug Information
Access: `https://your-domain.com/api/sms/debug-school` (when logged in as accountant)

## Security Checklist

- [ ] Change default SuperAdmin password
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure HTTPS/SSL
- [ ] Set secure file permissions
- [ ] Regular database backups
- [ ] Keep dependencies updated

## Support

For issues or feature requests, contact support or check the documentation.
