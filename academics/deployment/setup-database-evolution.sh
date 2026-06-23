#!/bin/bash

###############################################################################
# Evolution Server - Database Setup Script
# This script creates and configures the database for DARASA 360
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
echo -e "${GREEN}     Evolution Server - Database Setup${NC}"
echo -e "${GREEN}     DARASA 360 School Management System${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

# Function to print section headers
print_section() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Function to generate secure password
generate_password() {
    openssl rand -base64 16 | tr -d "=+/" | cut -c1-16
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

print_section "Step 1: Gather Database Information"

# Default values
DEFAULT_DB_NAME="darasa360_main"
DEFAULT_DB_USER="darasa360_user"
DB_HOST="localhost"

echo -e "${YELLOW}Enter database details (press Enter for defaults):${NC}\n"

# Database name
read -p "Database name [$DEFAULT_DB_NAME]: " DB_NAME
DB_NAME=${DB_NAME:-$DEFAULT_DB_NAME}
echo -e "${GREEN}✓ Database name: $DB_NAME${NC}"

# Database user
read -p "Database user [$DEFAULT_DB_USER]: " DB_USER
DB_USER=${DB_USER:-$DEFAULT_DB_USER}
echo -e "${GREEN}✓ Database user: $DB_USER${NC}"

# Database password
echo -e "\n${YELLOW}Choose password option:${NC}"
echo "1) Generate secure password automatically"
echo "2) Enter custom password"
read -p "Choice [1]: " PASSWORD_CHOICE
PASSWORD_CHOICE=${PASSWORD_CHOICE:-1}

if [ "$PASSWORD_CHOICE" = "1" ]; then
    DB_PASSWORD=$(generate_password)
    echo -e "${GREEN}✓ Generated secure password${NC}"
else
    read -sp "Enter database password: " DB_PASSWORD
    echo ""
    read -sp "Confirm password: " DB_PASSWORD_CONFIRM
    echo ""

    if [ "$DB_PASSWORD" != "$DB_PASSWORD_CONFIRM" ]; then
        echo -e "${RED}✗ Passwords don't match!${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Password confirmed${NC}"
fi

print_section "Step 2: MySQL Root Access"

echo -e "${YELLOW}To create the database and user, we need MySQL root access.${NC}"
echo -e "${YELLOW}Enter your MySQL root password:${NC}"
read -sp "MySQL root password: " MYSQL_ROOT_PASSWORD
echo ""

# Test MySQL connection
if ! mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" &>/dev/null; then
    echo -e "${RED}✗ MySQL root authentication failed!${NC}"
    echo -e "${YELLOW}Possible reasons:${NC}"
    echo -e "  1. Wrong password"
    echo -e "  2. Root access not allowed via command line"
    echo -e "  3. Use cPanel to create database instead"
    exit 1
fi

echo -e "${GREEN}✓ MySQL root access confirmed${NC}"

print_section "Step 3: Create Database and User"

echo -e "${YELLOW}Creating database: $DB_NAME${NC}"

# Create database
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Drop user if exists (to ensure clean creation)
DROP USER IF EXISTS '${DB_USER}'@'localhost';

-- Create user
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';

-- Grant privileges on main database
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

-- Grant privileges on school-specific databases (pattern: olam_school_*)
GRANT ALL PRIVILEGES ON \`olam_school_%\`.* TO '${DB_USER}'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database and user created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create database or user${NC}"
    exit 1
fi

print_section "Step 4: Test Database Connection"

echo -e "${YELLOW}Testing connection with new credentials...${NC}"

if mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE \`${DB_NAME}\`; SELECT 1;" &>/dev/null; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    exit 1
fi

print_section "Step 5: Update .env Configuration"

# Backup current .env
if [ -f ".env" ]; then
    cp .env ".env.backup.$(date +%Y%m%d_%H%M%S)"
    echo -e "${GREEN}✓ Backed up current .env file${NC}"
fi

# Create or update .env
if [ ! -f ".env" ] && [ -f ".env.production" ]; then
    cp .env.production .env
    echo -e "${GREEN}✓ Created .env from .env.production${NC}"
fi

echo -e "${YELLOW}Updating database credentials in .env...${NC}"

# Update database configuration
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env

echo -e "${GREEN}✓ .env file updated${NC}"

# Verify .env has been updated
echo -e "${BLUE}Database configuration in .env:${NC}"
grep "^DB_" .env

print_section "Step 6: Generate Application Key"

if grep -q "APP_KEY=base64:GENERATE_NEW_KEY_ON_SERVER" .env || ! grep -q "^APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate --force
    echo -e "${GREEN}✓ Application key generated${NC}"
else
    echo -e "${GREEN}✓ Application key already set${NC}"
fi

print_section "Step 7: Test Laravel Database Connection"

echo -e "${YELLOW}Testing Laravel database connection...${NC}"

php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'Database connection successful!\n';
    } catch (Exception \$e) {
        echo 'Connection failed: ' . \$e->getMessage() . '\n';
        exit(1);
    }
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Laravel can connect to database${NC}"
else
    echo -e "${RED}✗ Laravel cannot connect to database${NC}"
    exit 1
fi

print_section "Step 8: Run Migrations"

echo -e "${YELLOW}Do you want to run migrations now? (y/n)${NC}"
read -p "Run migrations? [y]: " RUN_MIGRATIONS
RUN_MIGRATIONS=${RUN_MIGRATIONS:-y}

if [ "$RUN_MIGRATIONS" = "y" ]; then
    echo -e "${YELLOW}Running migrations...${NC}"
    php artisan migrate --force

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Migrations completed successfully${NC}"
    else
        echo -e "${RED}✗ Migrations failed${NC}"
        echo -e "${YELLOW}You can run migrations manually later with:${NC}"
        echo -e "${CYAN}php artisan migrate --force${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Skipping migrations${NC}"
fi

print_section "Step 9: Seed Super Admin"

echo -e "${YELLOW}Do you want to create the super admin user now? (y/n)${NC}"
read -p "Seed super admin? [y]: " SEED_ADMIN
SEED_ADMIN=${SEED_ADMIN:-y}

if [ "$SEED_ADMIN" = "y" ]; then
    echo -e "${YELLOW}Seeding super admin...${NC}"
    php artisan db:seed --class=SuperAdminSeeder --force

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Super admin created successfully${NC}"
        echo -e "${BLUE}Login credentials:${NC}"
        echo -e "  Username: ${CYAN}admin${NC}"
        echo -e "  Password: ${CYAN}admin222${NC}"
        echo -e "${RED}⚠ CHANGE THIS PASSWORD AFTER FIRST LOGIN!${NC}"
    else
        echo -e "${RED}✗ Seeding failed${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Skipping super admin seeding${NC}"
fi

print_section "Step 10: Optimize Application"

echo -e "${YELLOW}Optimizing application for production...${NC}"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo -e "${GREEN}✓ Application optimized${NC}"

print_section "Database Setup Complete!"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Database Setup Completed Successfully!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📊 Database Information:${NC}"
echo -e "  Database Name: ${CYAN}${DB_NAME}${NC}"
echo -e "  Database User: ${CYAN}${DB_USER}${NC}"
echo -e "  Database Password: ${CYAN}${DB_PASSWORD}${NC}"
echo -e "  Host: ${CYAN}${DB_HOST}${NC}\n"

echo -e "${YELLOW}⚠ IMPORTANT: Save these credentials securely!${NC}\n"

echo -e "${BLUE}🔐 Super Admin Login:${NC}"
echo -e "  URL: ${CYAN}https://darasa360.olamtec.co.tz/login${NC}"
echo -e "  Username: ${CYAN}admin${NC}"
echo -e "  Password: ${CYAN}admin222${NC}"
echo -e "${RED}  ⚠ CHANGE PASSWORD AFTER FIRST LOGIN!${NC}\n"

echo -e "${BLUE}📝 Next Steps:${NC}"
echo -e "  1. Visit ${CYAN}https://darasa360.olamtec.co.tz${NC}"
echo -e "  2. Login with admin/admin222"
echo -e "  3. Change your password immediately"
echo -e "  4. Create your first school\n"

# Save credentials to file
CREDS_FILE="${DOMAIN_ROOT}/database-credentials-$(date +%Y%m%d_%H%M%S).txt"
cat > "$CREDS_FILE" <<EOF
═══════════════════════════════════════════════════════════
DARASA 360 - Database Credentials
Generated: $(date)
═══════════════════════════════════════════════════════════

DATABASE INFORMATION:
  Host: ${DB_HOST}
  Database: ${DB_NAME}
  Username: ${DB_USER}
  Password: ${DB_PASSWORD}

APPLICATION ACCESS:
  URL: https://darasa360.olamtec.co.tz/login
  Username: admin
  Password: admin222

  ⚠ CHANGE PASSWORD AFTER FIRST LOGIN!

═══════════════════════════════════════════════════════════
KEEP THIS FILE SECURE AND DELETE AFTER SAVING CREDENTIALS
═══════════════════════════════════════════════════════════
EOF

chmod 600 "$CREDS_FILE"
echo -e "${GREEN}✓ Credentials saved to: ${CREDS_FILE}${NC}"
echo -e "${YELLOW}⚠ Please save these credentials and delete this file!${NC}\n"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
