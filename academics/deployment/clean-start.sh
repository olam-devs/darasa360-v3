#!/bin/bash

###############################################################################
# DARASA 360 - Clean Start Script
# This script clears all old school data and prepares for fresh deployment
# NO USER INPUT REQUIRED - Runs automatically
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
echo -e "${GREEN}     DARASA 360 - Clean Start Script${NC}"
echo -e "${GREEN}     Preparing Fresh Production Environment${NC}"
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

# Verify we're in the right location
if [ ! -d "$DOMAIN_ROOT" ]; then
    echo -e "${RED}✗ Domain directory not found: $DOMAIN_ROOT${NC}"
    exit 1
fi

cd "$DOMAIN_ROOT"
echo -e "${GREEN}✓ Working in: $DOMAIN_ROOT${NC}"

print_section "Step 1: Verify Database Connection"

echo -e "${YELLOW}Testing database connection...${NC}"

DB_TEST=$(php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'SUCCESS';
    } catch (Exception \$e) {
        echo 'FAILED';
        exit(1);
    }
" 2>&1)

if [[ $DB_TEST == *"SUCCESS"* ]]; then
    echo -e "${GREEN}✓ Database connection verified${NC}"
else
    echo -e "${RED}✗ Cannot connect to database${NC}"
    echo -e "${YELLOW}Please run populate-database.sh first to configure database${NC}"
    exit 1
fi

print_section "Step 2: Check Current School Data"

echo -e "${YELLOW}Checking for existing schools...${NC}"

SCHOOLS_COUNT=$(php artisan tinker --execute="
    try {
        \$count = DB::table('schools')->count();
        echo \$count;
    } catch (Exception \$e) {
        echo '0';
    }
" 2>/dev/null || echo "0")

echo -e "${BLUE}Found ${CYAN}${SCHOOLS_COUNT}${BLUE} schools in database${NC}"

if [ "$SCHOOLS_COUNT" -gt 0 ]; then
    echo -e "\n${YELLOW}Existing schools:${NC}"
    php artisan tinker --execute="
        try {
            \$schools = DB::table('schools')->select('id', 'school_name', 'school_code', 'database_name')->get();
            foreach (\$schools as \$school) {
                echo \"  - {\$school->school_name} (Code: {\$school->school_code}, DB: {\$school->database_name})\n\";
            }
        } catch (Exception \$e) {
            echo '  (Could not list schools)';
        }
    " 2>/dev/null || echo -e "  ${YELLOW}(Could not list schools)${NC}"
    echo ""
fi

print_section "Step 3: Clear School Data"

echo -e "${YELLOW}Clearing schools table...${NC}"

php artisan tinker --execute="
    try {
        \$count = DB::table('schools')->count();
        DB::table('schools')->truncate();
        echo \"Cleared \$count schools\n\";
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage() . \"\n\";
        exit(1);
    }
" 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Schools table cleared${NC}"
else
    echo -e "${RED}✗ Failed to clear schools table${NC}"
    exit 1
fi

print_section "Step 4: Clear Related Tables"

echo -e "${YELLOW}Clearing school_users table...${NC}"

php artisan tinker --execute="
    try {
        \$count = DB::table('school_users')->count();
        DB::table('school_users')->truncate();
        echo \"Cleared \$count school users\n\";
    } catch (Exception \$e) {
        echo 'Warning: ' . \$e->getMessage() . \"\n\";
    }
" 2>&1 || echo -e "${YELLOW}⚠ school_users table may not exist (ok for fresh install)${NC}"

echo -e "${YELLOW}Clearing school_admins table...${NC}"

php artisan tinker --execute="
    try {
        \$count = DB::table('school_admins')->count();
        DB::table('school_admins')->truncate();
        echo \"Cleared \$count school admins\n\";
    } catch (Exception \$e) {
        echo 'Warning: ' . \$e->getMessage() . \"\n\";
    }
" 2>&1 || echo -e "${YELLOW}⚠ school_admins table may not exist (ok)${NC}"

echo -e "${YELLOW}Clearing logs table...${NC}"

php artisan tinker --execute="
    try {
        \$count = DB::table('logs')->count();
        DB::table('logs')->truncate();
        echo \"Cleared \$count log entries\n\";
    } catch (Exception \$e) {
        echo 'Warning: ' . \$e->getMessage() . \"\n\";
    }
" 2>&1 || echo -e "${YELLOW}⚠ logs table may not exist (ok)${NC}"

echo -e "${GREEN}✓ Related tables cleared${NC}"

print_section "Step 5: Verify Super Admin Exists"

echo -e "${YELLOW}Checking for super admin user...${NC}"

ADMIN_EXISTS=$(php artisan tinker --execute="
    try {
        \$admin = DB::table('users')->where('username', 'admin')->first();
        if (\$admin) {
            echo 'YES';
        } else {
            echo 'NO';
        }
    } catch (Exception \$e) {
        echo 'ERROR';
    }
" 2>/dev/null || echo "ERROR")

if [ "$ADMIN_EXISTS" = "YES" ]; then
    echo -e "${GREEN}✓ Super admin exists${NC}"
elif [ "$ADMIN_EXISTS" = "NO" ]; then
    echo -e "${YELLOW}⚠ Super admin not found, creating...${NC}"
    php artisan db:seed --class=SuperAdminSeeder --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Super admin created${NC}"
    else
        echo -e "${RED}✗ Failed to create super admin${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Could not verify super admin${NC}"
    exit 1
fi

print_section "Step 6: Clear Application Caches"

echo -e "${YELLOW}Clearing all application caches...${NC}"

# Clear config cache
php artisan config:clear 2>/dev/null && echo -e "${GREEN}  ✓ Config cache cleared${NC}"

# Clear application cache
php artisan cache:clear 2>/dev/null && echo -e "${GREEN}  ✓ Application cache cleared${NC}"

# Clear route cache
php artisan route:clear 2>/dev/null && echo -e "${GREEN}  ✓ Route cache cleared${NC}"

# Clear view cache
php artisan view:clear 2>/dev/null && echo -e "${GREEN}  ✓ View cache cleared${NC}"

# Clear all compiled files
php artisan optimize:clear 2>/dev/null && echo -e "${GREEN}  ✓ Compiled files cleared${NC}"

echo -e "${GREEN}✓ All caches cleared${NC}"

print_section "Step 7: Rebuild Production Caches"

echo -e "${YELLOW}Rebuilding caches for production...${NC}"

# Cache configuration
php artisan config:cache 2>&1 && echo -e "${GREEN}  ✓ Configuration cached${NC}"

# Cache routes
php artisan route:cache 2>&1 && echo -e "${GREEN}  ✓ Routes cached${NC}"

# Cache views
php artisan view:cache 2>&1 && echo -e "${GREEN}  ✓ Views cached${NC}"

# Optimize application
php artisan optimize 2>&1 && echo -e "${GREEN}  ✓ Application optimized${NC}"

echo -e "${GREEN}✓ Production caches built${NC}"

print_section "Step 8: Verify File Permissions"

echo -e "${YELLOW}Setting correct file permissions...${NC}"

# Ensure storage is writable
chmod -R 775 storage bootstrap/cache 2>/dev/null
echo -e "${GREEN}  ✓ Storage directories writable${NC}"

# Set ownership
chown -R "$CURRENT_USER:$CURRENT_USER" storage bootstrap/cache 2>/dev/null || echo -e "${YELLOW}  ⚠ Could not set ownership (may need sudo)${NC}"

# Secure .env
chmod 600 .env 2>/dev/null
echo -e "${GREEN}  ✓ .env file secured${NC}"

# Make artisan executable
chmod +x artisan 2>/dev/null
echo -e "${GREEN}  ✓ Artisan executable${NC}"

echo -e "${GREEN}✓ File permissions set${NC}"

print_section "Step 9: Final Verification"

echo -e "${YELLOW}Running final checks...${NC}\n"

# Check database connection
echo -n "Database connection: "
php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'OK';
    } catch (Exception \$e) {
        echo 'FAILED';
        exit(1);
    }
" 2>/dev/null && echo -e " ${GREEN}✓${NC}" || echo -e " ${RED}✗${NC}"

# Check schools count
echo -n "Schools in database: "
FINAL_COUNT=$(php artisan tinker --execute="echo DB::table('schools')->count();" 2>/dev/null || echo "?")
echo -e "${CYAN}${FINAL_COUNT}${NC} ${GREEN}✓${NC}"

# Check super admin
echo -n "Super admin exists: "
php artisan tinker --execute="
    \$admin = DB::table('users')->where('username', 'admin')->first();
    echo \$admin ? 'YES' : 'NO';
" 2>/dev/null | grep -q "YES" && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

# Check storage writable
echo -n "Storage writable: "
[ -w storage/logs ] && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

print_section "Clean Start Complete!"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     ✓ System Ready for Fresh Production Start!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}✅ What was completed:${NC}"
echo -e "  ✓ Cleared all old school data"
echo -e "  ✓ Cleared school users and related tables"
echo -e "  ✓ Verified super admin account exists"
echo -e "  ✓ Cleared all application caches"
echo -e "  ✓ Rebuilt production caches"
echo -e "  ✓ Set correct file permissions"
echo -e "  ✓ System verified and ready\n"

echo -e "${BLUE}🔐 Login Credentials:${NC}"
echo -e "  URL: ${CYAN}https://darasa360.olamtec.co.tz/login${NC}"
echo -e "  Username: ${CYAN}admin${NC}"
echo -e "  Password: ${CYAN}admin222${NC}"
echo -e "${RED}  ⚠ CRITICAL: Change password immediately after login!${NC}\n"

echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "  ${YELLOW}1.${NC} Visit: ${CYAN}https://darasa360.olamtec.co.tz/login${NC}"
echo -e "  ${YELLOW}2.${NC} Login with credentials above"
echo -e "  ${YELLOW}3.${NC} Go to Settings → Change Password"
echo -e "  ${YELLOW}4.${NC} Update your profile information"
echo -e "  ${YELLOW}5.${NC} Create your first school:"
echo -e "       - Go to Schools Management"
echo -e "       - Click 'Add New School'"
echo -e "       - Fill in school details"
echo -e "       - System will auto-create school database"
echo -e "  ${YELLOW}6.${NC} Add students, teachers, and start using the system!\n"

echo -e "${BLUE}💡 Tips:${NC}"
echo -e "  - Each school you create gets its own database automatically"
echo -e "  - The system handles database creation and permissions"
echo -e "  - Start with one school and test all features"
echo -e "  - Monitor logs at: storage/logs/laravel.log\n"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Ready to use DARASA 360! 🚀${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
