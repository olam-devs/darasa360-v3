#!/bin/bash

###############################################################################
# Evolution Server - Populate Existing Database Script
# This script configures Laravel to use existing database and runs migrations
# NO MYSQL ROOT ACCESS REQUIRED
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

clear

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     DARASA 360 - Database Population Script${NC}"
echo -e "${GREEN}     Configure and Populate Existing Database${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

# Function to print section headers
print_section() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Get current user
CURRENT_USER=$(whoami)
DOMAIN_ROOT="/home/${CURRENT_USER}/domains/darasa360.olamtec.co.tz"

# Check if we're in the right location
if [ ! -d "$DOMAIN_ROOT" ]; then
    echo -e "${RED}✗ Domain directory not found: $DOMAIN_ROOT${NC}"
    exit 1
fi

cd "$DOMAIN_ROOT"
echo -e "${GREEN}✓ Working directory: $DOMAIN_ROOT${NC}"

print_section "Step 1: Enter Database Credentials"

echo -e "${YELLOW}Please enter your database details:${NC}\n"

# Database host
read -p "Database Host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}
echo -e "${GREEN}✓ Host: $DB_HOST${NC}"

# Database name
read -p "Database Name: " DB_NAME
if [ -z "$DB_NAME" ]; then
    echo -e "${RED}✗ Database name is required!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Database: $DB_NAME${NC}"

# Database user
read -p "Database Username: " DB_USER
if [ -z "$DB_USER" ]; then
    echo -e "${RED}✗ Database username is required!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Username: $DB_USER${NC}"

# Database password
read -sp "Database Password: " DB_PASSWORD
echo ""
if [ -z "$DB_PASSWORD" ]; then
    echo -e "${RED}✗ Database password is required!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Password entered${NC}"

print_section "Step 2: Test Database Connection"

echo -e "${YELLOW}Testing database connection...${NC}"

# Test connection using mysql command
if command -v mysql &> /dev/null; then
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE \`${DB_NAME}\`; SELECT 1;" &>/dev/null; then
        echo -e "${GREEN}✓ Database connection successful!${NC}"
    else
        echo -e "${RED}✗ Cannot connect to database!${NC}"
        echo -e "${YELLOW}Please verify:${NC}"
        echo -e "  - Database exists: $DB_NAME"
        echo -e "  - Username is correct: $DB_USER"
        echo -e "  - Password is correct"
        echo -e "  - User has permissions on database"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ mysql command not found, skipping connection test${NC}"
    echo -e "${YELLOW}We'll test with Laravel instead${NC}"
fi

print_section "Step 3: Backup and Update .env File"

# Backup current .env if it exists
if [ -f ".env" ]; then
    BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
    cp .env "$BACKUP_FILE"
    echo -e "${GREEN}✓ Backed up .env to: $BACKUP_FILE${NC}"
fi

# Create .env from template if it doesn't exist
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        echo -e "${GREEN}✓ Created .env from .env.production${NC}"
    elif [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Created .env from .env.example${NC}"
    else
        echo -e "${RED}✗ No .env template found!${NC}"
        exit 1
    fi
fi

echo -e "${YELLOW}Updating .env with database credentials...${NC}"

# Update database configuration
sed -i.bak "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
sed -i.bak "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i.bak "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i.bak "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env

# Ensure production settings
sed -i.bak "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i.bak "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i.bak "s|^APP_URL=.*|APP_URL=https://darasa360.olamtec.co.tz|" .env

# Remove backup files created by sed
rm -f .env.bak

echo -e "${GREEN}✓ .env file updated${NC}"

# Show database config
echo -e "\n${BLUE}Database configuration:${NC}"
grep "^DB_" .env | while read line; do
    if [[ $line == DB_PASSWORD* ]]; then
        echo -e "  DB_PASSWORD=********"
    else
        echo -e "  $line"
    fi
done

print_section "Step 4: Generate Application Key"

# Check if APP_KEY needs to be generated
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=base64:GENERATE_NEW_KEY_ON_SERVER" .env || ! grep -q "^APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate --force
    echo -e "${GREEN}✓ Application key generated${NC}"
else
    echo -e "${GREEN}✓ Application key already exists${NC}"
fi

print_section "Step 5: Clear All Caches"

echo -e "${YELLOW}Clearing all caches...${NC}"

php artisan optimize:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

echo -e "${GREEN}✓ Caches cleared${NC}"

print_section "Step 6: Test Laravel Database Connection"

echo -e "${YELLOW}Testing Laravel database connection...${NC}"

CONNECTION_TEST=$(php artisan tinker --execute="
    try {
        \$pdo = DB::connection()->getPdo();
        echo 'SUCCESS';
    } catch (Exception \$e) {
        echo 'FAILED: ' . \$e->getMessage();
        exit(1);
    }
" 2>&1)

if [[ $CONNECTION_TEST == *"SUCCESS"* ]]; then
    echo -e "${GREEN}✓ Laravel connected to database successfully!${NC}"
else
    echo -e "${RED}✗ Laravel cannot connect to database${NC}"
    echo -e "${RED}Error: $CONNECTION_TEST${NC}"
    exit 1
fi

print_section "Step 7: Run Database Migrations"

echo -e "${YELLOW}Running database migrations...${NC}"
echo -e "${BLUE}This will create all necessary tables...${NC}\n"

php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully!${NC}"
else
    echo -e "${RED}✗ Migrations failed!${NC}"
    echo -e "${YELLOW}Check the error messages above${NC}"
    exit 1
fi

print_section "Step 8: Seed Super Admin User"

echo -e "${YELLOW}Creating super admin user...${NC}"

php artisan db:seed --class=SuperAdminSeeder --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Super admin user created!${NC}"
    echo -e "\n${BLUE}Super Admin Credentials:${NC}"
    echo -e "  Username: ${CYAN}admin${NC}"
    echo -e "  Password: ${CYAN}admin222${NC}"
    echo -e "${RED}  ⚠ CRITICAL: Change this password immediately after first login!${NC}\n"
else
    echo -e "${RED}✗ Failed to create super admin${NC}"
    echo -e "${YELLOW}You may need to create the admin user manually${NC}"
fi

print_section "Step 9: Seed Additional Data"

echo -e "${YELLOW}Seeding additional required data...${NC}"

# Seed comment templates
if php artisan db:seed --class=CommentTemplatesSeeder --force 2>/dev/null; then
    echo -e "${GREEN}✓ Comment templates seeded${NC}"
else
    echo -e "${YELLOW}⚠ Comment templates seeder not found or failed (optional)${NC}"
fi

# Seed support modules
if php artisan db:seed --class=SupportModulesSeeder --force 2>/dev/null; then
    echo -e "${GREEN}✓ Support modules seeded${NC}"
else
    echo -e "${YELLOW}⚠ Support modules seeder not found or failed (optional)${NC}"
fi

print_section "Step 10: Optimize for Production"

echo -e "${YELLOW}Optimizing application for production...${NC}"

# Cache configuration
php artisan config:cache
echo -e "${GREEN}✓ Configuration cached${NC}"

# Cache routes
php artisan route:cache
echo -e "${GREEN}✓ Routes cached${NC}"

# Cache views
php artisan view:cache
echo -e "${GREEN}✓ Views cached${NC}"

# Optimize application
php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"

print_section "Step 11: Verify File Permissions"

echo -e "${YELLOW}Checking file permissions...${NC}"

# Ensure storage is writable
chmod -R 775 storage bootstrap/cache 2>/dev/null
chown -R "$CURRENT_USER:$CURRENT_USER" storage bootstrap/cache 2>/dev/null || true

echo -e "${GREEN}✓ Storage permissions set${NC}"

# Secure .env
chmod 600 .env
echo -e "${GREEN}✓ .env file secured${NC}"

print_section "Database Setup Complete!"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     ✓ Database Configured and Populated Successfully!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📊 Database Information:${NC}"
echo -e "  Host: ${CYAN}${DB_HOST}${NC}"
echo -e "  Database: ${CYAN}${DB_NAME}${NC}"
echo -e "  Username: ${CYAN}${DB_USER}${NC}"
echo -e "  Password: ${CYAN}********${NC}\n"

echo -e "${BLUE}🔐 Login Credentials:${NC}"
echo -e "  URL: ${CYAN}https://darasa360.olamtec.co.tz/login${NC}"
echo -e "  Username: ${CYAN}admin${NC}"
echo -e "  Password: ${CYAN}admin222${NC}"
echo -e "${RED}  ⚠ CRITICAL: Change password after first login!${NC}\n"

echo -e "${BLUE}✅ Next Steps:${NC}"
echo -e "  1. Visit: ${CYAN}https://darasa360.olamtec.co.tz${NC}"
echo -e "  2. Login with: ${CYAN}admin / admin222${NC}"
echo -e "  3. Change your password immediately"
echo -e "  4. Update your profile information"
echo -e "  5. Create your first school\n"

echo -e "${BLUE}📋 Migration Summary:${NC}"
php artisan migrate:status | head -20

echo -e "\n${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Ready to use DARASA 360!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
