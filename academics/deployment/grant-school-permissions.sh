#!/bin/bash

###############################################################################
# Grant School Database Permissions - AUTOMATIC
# This script grants permissions to all school databases
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
echo -e "${GREEN}     Grant School Database Permissions${NC}"
echo -e "${GREEN}     Fix 500 Errors for School Access${NC}"
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

print_section "Step 1: Get Database Credentials"

# Get credentials from .env
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2)
DB_MAIN=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)

echo -e "${BLUE}Database Host:${NC} ${CYAN}${DB_HOST}${NC}"
echo -e "${BLUE}Database User:${NC} ${CYAN}${DB_USER}${NC}"
echo -e "${BLUE}Main Database:${NC} ${CYAN}${DB_MAIN}${NC}"

print_section "Step 2: Find All School Databases"

echo -e "${YELLOW}Fetching schools from database...${NC}\n"

# Create a temporary PHP script to get school databases
cat > /tmp/get_schools.php << 'PHPEOF'
<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $schools = DB::table('schools')->select('id', 'school_name', 'school_code', 'database_name')->get();

    if ($schools->isEmpty()) {
        echo "NO_SCHOOLS\n";
        exit(0);
    }

    foreach ($schools as $school) {
        echo "{$school->id}|{$school->school_name}|{$school->school_code}|{$school->database_name}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
PHPEOF

mv /tmp/get_schools.php ./

SCHOOLS_DATA=$(php get_schools.php 2>&1)
rm get_schools.php

if [[ $SCHOOLS_DATA == "NO_SCHOOLS" ]]; then
    echo -e "${YELLOW}No schools found in database${NC}"
    echo -e "${BLUE}Create a school from the admin dashboard first${NC}"
    exit 0
elif [[ $SCHOOLS_DATA == ERROR* ]]; then
    echo -e "${RED}✗ Failed to fetch schools${NC}"
    echo -e "${RED}$SCHOOLS_DATA${NC}"
    exit 1
fi

echo -e "${BLUE}Schools found:${NC}"
SCHOOL_DBS=()
while IFS='|' read -r id name code db_name; do
    if [ -n "$id" ]; then
        echo -e "  ${CYAN}$id${NC}. $name (Code: $code)"
        echo -e "     Database: ${YELLOW}$db_name${NC}"
        SCHOOL_DBS+=("$db_name")
    fi
done <<< "$SCHOOLS_DATA"
echo ""

if [ ${#SCHOOL_DBS[@]} -eq 0 ]; then
    echo -e "${YELLOW}No school databases to process${NC}"
    exit 0
fi

print_section "Step 3: Choose Permission Granting Method"

echo -e "${YELLOW}How do you want to grant permissions?${NC}\n"
echo -e "${BLUE}1) Automatic (requires MySQL root password)${NC}"
echo -e "${BLUE}2) Show cPanel instructions${NC}"
echo -e "${BLUE}3) Generate SQL commands to run manually${NC}\n"

read -p "Choose option (1/2/3) [2]: " METHOD
METHOD=${METHOD:-2}

if [ "$METHOD" = "1" ]; then
    print_section "Step 4: Automatic Permission Granting"

    echo -e "${YELLOW}Enter MySQL root password:${NC}"
    read -sp "Password: " MYSQL_ROOT_PASSWORD
    echo ""

    # Test MySQL root access
    if ! mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" &>/dev/null; then
        echo -e "${RED}✗ MySQL root authentication failed!${NC}"
        echo -e "${YELLOW}Try option 2 (cPanel) or option 3 (manual SQL) instead${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ MySQL root access confirmed${NC}\n"

    echo -e "${YELLOW}Granting permissions for all school databases...${NC}\n"

    for DB_NAME in "${SCHOOL_DBS[@]}"; do
        echo -e "${BLUE}Processing: ${CYAN}${DB_NAME}${NC}"

        # Check if database exists
        DB_EXISTS=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES LIKE '${DB_NAME}';" 2>&1)

        if [[ ! $DB_EXISTS == *"${DB_NAME}"* ]]; then
            echo -e "  ${YELLOW}⚠ Database doesn't exist, creating...${NC}"

            mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<SQLEOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
SQLEOF
            echo -e "  ${GREEN}✓ Database created${NC}"
        else
            echo -e "  ${GREEN}✓ Database exists${NC}"
        fi

        # Grant permissions
        mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<SQLEOF
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
SQLEOF

        if [ $? -eq 0 ]; then
            echo -e "  ${GREEN}✓ Permissions granted${NC}"
        else
            echo -e "  ${RED}✗ Failed to grant permissions${NC}"
        fi
        echo ""
    done

    # Also grant wildcard permissions for future school databases
    echo -e "${YELLOW}Granting wildcard permissions for future schools...${NC}"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<SQLEOF
GRANT ALL PRIVILEGES ON \`olam_school_%\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${CURRENT_USER}_olam_school_%\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Wildcard permissions granted${NC}"
        echo -e "${BLUE}Future school databases will automatically be accessible${NC}\n"
    fi

    print_section "Step 5: Test School Database Access"

    echo -e "${YELLOW}Testing access to school databases...${NC}\n"

    ALL_GOOD=true
    for DB_NAME in "${SCHOOL_DBS[@]}"; do
        echo -n "Testing ${DB_NAME}... "
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE \`${DB_NAME}\`; SELECT 1;" &>/dev/null; then
            echo -e "${GREEN}✓ OK${NC}"
        else
            echo -e "${RED}✗ FAILED${NC}"
            ALL_GOOD=false
        fi
    done
    echo ""

    if [ "$ALL_GOOD" = true ]; then
        echo -e "${GREEN}✓ All school databases are accessible!${NC}"
    else
        echo -e "${YELLOW}⚠ Some databases still have issues${NC}"
    fi

elif [ "$METHOD" = "2" ]; then
    print_section "Step 4: cPanel Instructions"

    echo -e "${YELLOW}Follow these steps in cPanel:${NC}\n"

    echo -e "${BLUE}1. Login to your cPanel${NC}\n"

    echo -e "${BLUE}2. Click on 'MySQL Databases'${NC}\n"

    echo -e "${BLUE}3. Scroll down to 'Add User To Database' section${NC}\n"

    echo -e "${BLUE}4. For each school database, do the following:${NC}\n"

    for DB_NAME in "${SCHOOL_DBS[@]}"; do
        echo -e "   ${CYAN}→ User:${NC} ${DB_USER}"
        echo -e "   ${CYAN}→ Database:${NC} ${DB_NAME}"
        echo -e "   ${CYAN}→ Click 'Add'${NC}"
        echo -e "   ${CYAN}→ Check 'ALL PRIVILEGES'${NC}"
        echo -e "   ${CYAN}→ Click 'Make Changes'${NC}"
        echo ""
    done

    echo -e "${YELLOW}5. After granting all permissions, run:${NC}"
    echo -e "   ${CYAN}bash test-permissions.sh${NC}\n"

elif [ "$METHOD" = "3" ]; then
    print_section "Step 4: Manual SQL Commands"

    echo -e "${YELLOW}Copy and run these SQL commands:${NC}\n"

    echo -e "${BLUE}mysql -u root -p${NC}\n"

    echo -e "${YELLOW}-- Grant permissions for existing school databases${NC}"
    for DB_NAME in "${SCHOOL_DBS[@]}"; do
        echo -e "-- Database: ${DB_NAME}"
        echo -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        echo -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
        echo ""
    done

    echo -e "${YELLOW}-- Grant wildcard permissions for future schools${NC}"
    echo -e "GRANT ALL PRIVILEGES ON \`olam_school_%\`.* TO '${DB_USER}'@'localhost';"
    echo -e "GRANT ALL PRIVILEGES ON \`${CURRENT_USER}_olam_school_%\`.* TO '${DB_USER}'@'localhost';"
    echo ""
    echo -e "FLUSH PRIVILEGES;"
    echo -e "EXIT;\n"

    # Save to file
    SQL_FILE="grant-permissions.sql"
    cat > "$SQL_FILE" << SQLFILE
-- Grant permissions for DARASA 360 school databases
-- Run with: mysql -u root -p < grant-permissions.sql

SQLFILE

    for DB_NAME in "${SCHOOL_DBS[@]}"; do
        cat >> "$SQL_FILE" << SQLFILE
-- Database: ${DB_NAME}
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

SQLFILE
    done

    cat >> "$SQL_FILE" << SQLFILE
-- Wildcard permissions for future schools
GRANT ALL PRIVILEGES ON \`olam_school_%\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${CURRENT_USER}_olam_school_%\`.* TO '${DB_USER}'@'localhost';

FLUSH PRIVILEGES;
SQLFILE

    echo -e "${GREEN}✓ SQL commands saved to: ${CYAN}${SQL_FILE}${NC}"
    echo -e "${BLUE}Run with: ${CYAN}mysql -u root -p < ${SQL_FILE}${NC}\n"

fi

print_section "Step 6: Create Test Script"

cat > test-permissions.sh << 'TESTSCRIPT'
#!/bin/bash

echo "Testing school database permissions..."
echo ""

DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)

# Get school databases
php artisan tinker --execute="
    \$schools = DB::table('schools')->pluck('database_name');
    foreach (\$schools as \$db_name) {
        echo \$db_name . \"\n\";
    }
" 2>/dev/null | while read db_name; do
    if [ -n "$db_name" ]; then
        echo -n "Testing $db_name... "
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE \`$db_name\`; SELECT 1;" &>/dev/null 2>&1; then
            echo "✓ OK"
        else
            echo "✗ FAILED"
        fi
    fi
done

echo ""
echo "If all tests show ✓ OK, you can now:"
echo "  1. View school details"
echo "  2. Login as school owner"
echo "  3. Access all school features"
TESTSCRIPT

chmod +x test-permissions.sh

echo -e "${GREEN}✓ Created test script: ${CYAN}test-permissions.sh${NC}"

print_section "Step 7: Clear Caches"

echo -e "${YELLOW}Clearing application caches...${NC}"

php artisan config:clear 2>/dev/null
php artisan cache:clear 2>/dev/null
php artisan optimize 2>/dev/null

echo -e "${GREEN}✓ Caches cleared${NC}"

print_section "Complete!"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Next Steps${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

if [ "$METHOD" = "1" ]; then
    echo -e "${BLUE}✅ Permissions granted automatically!${NC}\n"
    echo -e "${YELLOW}Test it:${NC}"
    echo -e "  ${CYAN}bash test-permissions.sh${NC}\n"
    echo -e "${YELLOW}Then try:${NC}"
    echo -e "  1. View school details in admin dashboard"
    echo -e "  2. Login as school owner"
    echo -e "  3. Access school features\n"
else
    echo -e "${YELLOW}1. Grant permissions using method you chose${NC}\n"
    echo -e "${YELLOW}2. Test permissions:${NC}"
    echo -e "   ${CYAN}bash test-permissions.sh${NC}\n"
    echo -e "${YELLOW}3. Try accessing school features${NC}\n"
fi

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
