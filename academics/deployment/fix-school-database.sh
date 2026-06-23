#!/bin/bash

###############################################################################
# Fix School Database Permissions
# This script helps diagnose and fix school database access issues
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
echo -e "${GREEN}     Fix School Database Permissions${NC}"
echo -e "${GREEN}     Diagnose and Fix School Login Issues${NC}"
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

print_section "Step 1: Check Main Database Connection"

echo -e "${YELLOW}Testing main database connection...${NC}"

MAIN_DB_TEST=$(php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'SUCCESS';
    } catch (Exception \$e) {
        echo 'FAILED: ' . \$e->getMessage();
        exit(1);
    }
" 2>&1)

if [[ $MAIN_DB_TEST == *"SUCCESS"* ]]; then
    echo -e "${GREEN}✓ Main database connection OK${NC}"
else
    echo -e "${RED}✗ Main database connection failed${NC}"
    echo -e "${RED}$MAIN_DB_TEST${NC}"
    exit 1
fi

# Get database credentials from .env
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2)

echo -e "${BLUE}Main database user: ${CYAN}${DB_USER}${NC}"

print_section "Step 2: List Created Schools"

echo -e "${YELLOW}Fetching schools from database...${NC}\n"

SCHOOLS_DATA=$(php artisan tinker --execute="
    try {
        \$schools = DB::table('schools')->select('id', 'school_name', 'school_code', 'database_name')->get();
        if (\$schools->count() === 0) {
            echo 'NO_SCHOOLS';
        } else {
            foreach (\$schools as \$school) {
                echo \$school->id . '|' . \$school->school_name . '|' . \$school->school_code . '|' . \$school->database_name . '\n';
            }
        }
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
        exit(1);
    }
" 2>&1)

if [[ $SCHOOLS_DATA == "NO_SCHOOLS" ]]; then
    echo -e "${YELLOW}No schools found in database${NC}"
    echo -e "${BLUE}Please create a school from the admin dashboard first${NC}"
    exit 0
elif [[ $SCHOOLS_DATA == ERROR* ]]; then
    echo -e "${RED}✗ Failed to fetch schools${NC}"
    echo -e "${RED}$SCHOOLS_DATA${NC}"
    exit 1
else
    echo -e "${BLUE}Schools found:${NC}"
    echo "$SCHOOLS_DATA" | while IFS='|' read -r id name code db_name; do
        if [ -n "$id" ]; then
            echo -e "  ${CYAN}$id${NC}. $name (Code: $code)"
            echo -e "     Database: ${YELLOW}$db_name${NC}"
        fi
    done
    echo ""
fi

print_section "Step 3: Check School Databases Exist"

echo -e "${YELLOW}Checking if school databases exist...${NC}\n"

# Get list of database names
DB_NAMES=$(echo "$SCHOOLS_DATA" | awk -F'|' '{print $4}' | grep -v '^$')

for DB_NAME in $DB_NAMES; do
    echo -e "${BLUE}Checking database: ${CYAN}${DB_NAME}${NC}"

    # Try to check if database exists
    DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SHOW DATABASES LIKE '${DB_NAME}';" 2>&1)

    if [[ $DB_EXISTS == *"${DB_NAME}"* ]]; then
        echo -e "  ${GREEN}✓ Database exists${NC}"

        # Try to access it
        ACCESS_TEST=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE \`${DB_NAME}\`; SELECT 1;" 2>&1)

        if [ $? -eq 0 ]; then
            echo -e "  ${GREEN}✓ Can access database${NC}"
        else
            echo -e "  ${RED}✗ Cannot access database (permission denied)${NC}"
            echo -e "  ${YELLOW}Need to grant permissions!${NC}"
        fi
    else
        echo -e "  ${RED}✗ Database does NOT exist${NC}"
        echo -e "  ${YELLOW}Database needs to be created${NC}"
    fi
    echo ""
done

print_section "Step 4: Solution - Grant Permissions via cPanel"

echo -e "${YELLOW}To fix the school login issue, you need to grant database permissions.${NC}\n"

echo -e "${BLUE}=== Manual Fix via cPanel (RECOMMENDED) ===${NC}\n"

echo -e "${YELLOW}Step 1: Login to cPanel${NC}\n"

echo -e "${YELLOW}Step 2: Go to 'MySQL Databases'${NC}\n"

echo -e "${YELLOW}Step 3: Scroll to 'Add User To Database' section${NC}\n"

echo -e "${YELLOW}Step 4: Grant permissions for each school database:${NC}\n"

echo "$SCHOOLS_DATA" | awk -F'|' '{print $4}' | grep -v '^$' | while read DB_NAME; do
    echo -e "  ${CYAN}→ User:${NC} ${DB_USER}"
    echo -e "  ${CYAN}→ Database:${NC} ${DB_NAME}"
    echo -e "  ${CYAN}→ Privileges:${NC} ALL PRIVILEGES"
    echo ""
done

echo -e "${YELLOW}Step 5: Click 'Make Changes' for each database${NC}\n"

print_section "Step 5: Alternative - Grant via MySQL Root"

echo -e "${YELLOW}If you have MySQL root access, run these commands:${NC}\n"

echo -e "${BLUE}mysql -u root -p${NC}\n"

echo -e "${YELLOW}Then copy and paste these SQL commands:${NC}\n"

echo "$SCHOOLS_DATA" | awk -F'|' '{print $4}' | grep -v '^$' | while read DB_NAME; do
    echo -e "-- Grant access to ${DB_NAME}"
    echo -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
done

echo -e "FLUSH PRIVILEGES;"
echo -e "EXIT;\n"

print_section "Step 6: Test After Granting Permissions"

echo -e "${YELLOW}After granting permissions, run this test:${NC}\n"

cat > "${DOMAIN_ROOT}/test-school-db.sh" << 'TESTEOF'
#!/bin/bash

echo "Testing school database access..."

DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)

php artisan tinker --execute="
    try {
        \$schools = DB::table('schools')->get();
        foreach (\$schools as \$school) {
            echo \"Testing {\$school->database_name}...\";
            config(['database.connections.tenant.database' => \$school->database_name]);
            DB::connection('tenant')->getPdo();
            echo \" OK\n\";
        }
        echo \"\nAll school databases accessible!\n\";
    } catch (Exception \$e) {
        echo \"\nError: \" . \$e->getMessage() . \"\n\";
    }
"
TESTEOF

chmod +x test-school-db.sh

echo -e "${GREEN}Created test script: ${CYAN}test-school-db.sh${NC}"
echo -e "${BLUE}Run it with: ${CYAN}bash test-school-db.sh${NC}\n"

print_section "Step 7: Clear Caches After Fix"

echo -e "${YELLOW}After granting permissions, clear caches:${NC}\n"

echo -e "${CYAN}php artisan config:clear${NC}"
echo -e "${CYAN}php artisan cache:clear${NC}"
echo -e "${CYAN}php artisan optimize${NC}\n"

print_section "Summary"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Diagnosis Complete${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📋 What you need to do:${NC}\n"

echo -e "${YELLOW}1. Grant database permissions via cPanel:${NC}"
echo "$SCHOOLS_DATA" | awk -F'|' '{print $4}' | grep -v '^$' | while read DB_NAME; do
    echo -e "   ${CYAN}→${NC} Add user ${CYAN}${DB_USER}${NC} to database ${CYAN}${DB_NAME}${NC} with ALL PRIVILEGES"
done

echo -e "\n${YELLOW}2. Run test script:${NC}"
echo -e "   ${CYAN}bash test-school-db.sh${NC}"

echo -e "\n${YELLOW}3. Clear caches:${NC}"
echo -e "   ${CYAN}php artisan config:clear && php artisan cache:clear${NC}"

echo -e "\n${YELLOW}4. Try school owner login again${NC}\n"

echo -e "${BLUE}💡 Note:${NC} The 500 error happens because your database user doesn't"
echo -e "have permissions to access the school databases. Once you grant"
echo -e "permissions via cPanel, the login will work.\n"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
