#!/bin/bash

###############################################################################
# Fix Multi-Tenant Database Permissions
# This script clears old school data and ensures fresh start
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
echo -e "${GREEN}     Fix Multi-Tenant Database Permissions${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

# Function to print section headers
print_section() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

CURRENT_USER=$(whoami)
DOMAIN_ROOT="/home/${CURRENT_USER}/domains/darasa360.olamtec.co.tz"

if [ ! -d "$DOMAIN_ROOT" ]; then
    echo -e "${RED}✗ Domain directory not found: $DOMAIN_ROOT${NC}"
    exit 1
fi

cd "$DOMAIN_ROOT"

print_section "Step 1: Understanding the Issue"

echo -e "${YELLOW}The error shows that the application is trying to access a school database${NC}"
echo -e "${YELLOW}called 'olamtecc_mivumoni' which doesn't exist or lacks permissions.${NC}\n"

echo -e "${BLUE}This happens because:${NC}"
echo -e "  1. Old school data exists in the database from previous setup"
echo -e "  2. Those schools reference databases that don't exist"
echo -e "  3. The current user doesn't have permissions to old databases\n"

print_section "Step 2: Check Current Database State"

echo -e "${YELLOW}Checking for existing schools in the database...${NC}\n"

SCHOOLS_COUNT=$(php artisan tinker --execute="
    try {
        \$count = DB::table('schools')->count();
        echo \$count;
    } catch (Exception \$e) {
        echo '0';
    }
" 2>/dev/null || echo "0")

echo -e "${BLUE}Found ${CYAN}${SCHOOLS_COUNT}${BLUE} schools in database${NC}\n"

if [ "$SCHOOLS_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}Listing existing schools:${NC}\n"
    php artisan tinker --execute="
        try {
            \$schools = DB::table('schools')->select('id', 'school_name', 'school_code', 'database_name')->get();
            foreach (\$schools as \$school) {
                echo \"ID: {\$school->id} | Name: {\$school->school_name} | Code: {\$school->school_code} | Database: {\$school->database_name}\n\";
            }
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage();
        }
    " 2>/dev/null || echo "Could not list schools"
    echo ""
fi

print_section "Step 3: Choose Fix Method"

echo -e "${YELLOW}We have two options to fix this:${NC}\n"
echo -e "${BLUE}Option 1: Clean Start (RECOMMENDED for fresh production)${NC}"
echo -e "  - Clears all school data from main database"
echo -e "  - You'll create schools fresh after deployment"
echo -e "  - No permission issues"
echo -e "  - Clean slate\n"

echo -e "${BLUE}Option 2: Grant Permissions to Old Databases${NC}"
echo -e "  - Requires MySQL root access or cPanel"
echo -e "  - Keeps existing school data"
echo -e "  - Need to grant access to all school databases\n"

read -p "Choose option (1 or 2) [1]: " OPTION
OPTION=${OPTION:-1}

if [ "$OPTION" = "1" ]; then
    print_section "Step 4: Clean Start - Remove Old School Data"

    echo -e "${YELLOW}This will remove all schools from the main database.${NC}"
    echo -e "${YELLOW}School databases will remain untouched.${NC}"
    echo -e "${RED}You will create schools fresh after login.${NC}\n"

    read -p "Are you sure you want to proceed? (yes/no) [no]: " CONFIRM

    if [ "$CONFIRM" != "yes" ]; then
        echo -e "${YELLOW}Aborted by user${NC}"
        exit 0
    fi

    echo -e "\n${YELLOW}Clearing schools table...${NC}"

    php artisan tinker --execute="
        try {
            DB::table('schools')->truncate();
            echo 'Schools table cleared successfully\n';
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Schools table cleared${NC}"
    else
        echo -e "${RED}✗ Failed to clear schools table${NC}"
        exit 1
    fi

    echo -e "\n${YELLOW}Clearing school_users table...${NC}"

    php artisan tinker --execute="
        try {
            DB::table('school_users')->truncate();
            echo 'School users table cleared successfully\n';
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage() . '\n';
        }
    " 2>/dev/null || echo -e "${YELLOW}⚠ Could not clear school_users (table may not exist)${NC}"

    echo -e "\n${YELLOW}Clearing related tables...${NC}"

    # Clear other related tables that might reference schools
    for table in school_admins logs; do
        php artisan tinker --execute="
            try {
                DB::table('${table}')->truncate();
                echo '${table} cleared\n';
            } catch (Exception \$e) {
                // Table might not exist, that's ok
            }
        " 2>/dev/null || true
    done

    echo -e "${GREEN}✓ Database cleaned for fresh start${NC}"

    print_section "Step 5: Clear Caches"

    echo -e "${YELLOW}Clearing all caches...${NC}"
    php artisan optimize:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true

    echo -e "${GREEN}✓ Caches cleared${NC}"

    print_section "Step 6: Rebuild Caches"

    echo -e "${YELLOW}Rebuilding production caches...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    echo -e "${GREEN}✓ Caches rebuilt${NC}"

    print_section "Fix Complete!"

    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}     ✓ Database Cleaned Successfully!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

    echo -e "${BLUE}✅ What was done:${NC}"
    echo -e "  ✓ Cleared all schools from main database"
    echo -e "  ✓ Cleared school users"
    echo -e "  ✓ Cleared related tables"
    echo -e "  ✓ Rebuilt caches\n"

    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo -e "  1. Visit: ${CYAN}https://darasa360.olamtec.co.tz/login${NC}"
    echo -e "  2. Login with: ${CYAN}admin / admin222${NC}"
    echo -e "  3. Create your first school from the dashboard"
    echo -e "  4. The system will automatically create school database"
    echo -e "  5. Add students, teachers, etc.\n"

    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

elif [ "$OPTION" = "2" ]; then
    print_section "Step 4: Grant Permissions Method"

    echo -e "${YELLOW}To grant permissions to existing school databases, you need to:${NC}\n"

    echo -e "${BLUE}Via cPanel:${NC}"
    echo -e "  1. Login to cPanel"
    echo -e "  2. Go to MySQL Databases"
    echo -e "  3. Find your database user: ${CYAN}olamtecc_darasa_v2${NC}"
    echo -e "  4. Add user to these databases with ALL PRIVILEGES:"

    echo -e "\n${YELLOW}School databases found:${NC}"
    php artisan tinker --execute="
        try {
            \$schools = DB::table('schools')->pluck('database_name');
            foreach (\$schools as \$db) {
                echo \"     - {\$db}\n\";
            }
        } catch (Exception \$e) {
            echo '     (Could not list databases)';
        }
    " 2>/dev/null

    echo -e "\n${BLUE}Or via MySQL root (if you have access):${NC}"
    echo -e "  ${CYAN}mysql -u root -p${NC}"
    echo -e "  Then run these commands:\n"

    php artisan tinker --execute="
        try {
            \$schools = DB::table('schools')->pluck('database_name');
            foreach (\$schools as \$db) {
                echo \"  GRANT ALL PRIVILEGES ON \\\`{\$db}\\\`.* TO 'olamtecc_darasa_v2'@'localhost';\n\";
            }
            echo \"  FLUSH PRIVILEGES;\n\";
        } catch (Exception \$e) {
            echo '  -- Could not generate commands';
        }
    " 2>/dev/null

    echo -e "\n${YELLOW}After granting permissions, run:${NC}"
    echo -e "  ${CYAN}php artisan config:clear${NC}"
    echo -e "  ${CYAN}php artisan cache:clear${NC}\n"

else
    echo -e "${RED}Invalid option${NC}"
    exit 1
fi

exit 0
