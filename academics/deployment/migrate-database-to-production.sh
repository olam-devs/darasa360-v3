#!/bin/bash

###############################################################################
# Database Migration Script - Development to Production
# This script migrates local databases to production Evolution server
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   OLAM Database Migration - Local to Production${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

# Configuration - UPDATE THESE VALUES
LOCAL_DB_USER="root"
LOCAL_DB_PASSWORD=""
LOCAL_DB_HOST="127.0.0.1"

# Production credentials (you'll be prompted)
PROD_DB_USER="darasa360_user"
PROD_DB_HOST="localhost"

# Databases to migrate
MAIN_DB="laravel"
TENANT_DBS=("FezaSchools" "Imani" "olam_school_greenvalley" "olam_school_local")

# Migration directory
MIGRATION_DIR="/tmp/olam_migration_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$MIGRATION_DIR"

echo -e "${BLUE}Migration directory: $MIGRATION_DIR${NC}\n"

# Prompt for production database password
echo -e "${YELLOW}Enter production database password for user '$PROD_DB_USER':${NC}"
read -s PROD_DB_PASSWORD
echo ""

# Function to export database
export_database() {
    local db_name=$1
    local output_file="$MIGRATION_DIR/${db_name}.sql"

    echo -e "${YELLOW}Exporting $db_name...${NC}"

    mysqldump \
        --host="$LOCAL_DB_HOST" \
        --user="$LOCAL_DB_USER" \
        --password="$LOCAL_DB_PASSWORD" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        --routines \
        --triggers \
        --events \
        --set-gtid-purged=OFF \
        "$db_name" > "$output_file"

    if [ $? -eq 0 ]; then
        local size=$(du -h "$output_file" | cut -f1)
        echo -e "${GREEN}✓ Exported $db_name ($size)${NC}"

        # Compress the SQL file
        gzip "$output_file"
        echo -e "${GREEN}✓ Compressed to ${db_name}.sql.gz${NC}"
    else
        echo -e "${RED}✗ Failed to export $db_name${NC}"
        return 1
    fi
}

# Function to sanitize database for production
sanitize_database() {
    local sql_file=$1
    local sanitized_file="${sql_file}.sanitized"

    echo -e "${YELLOW}Sanitizing database dump...${NC}"

    # Remove DEFINER clauses (causes issues on shared hosting)
    sed 's/DEFINER=[^ ]* / /g' "$sql_file" > "$sanitized_file"

    # Replace file
    mv "$sanitized_file" "$sql_file"

    echo -e "${GREEN}✓ Database dump sanitized${NC}"
}

# Step 1: Export all databases
echo -e "\n${BLUE}Step 1: Exporting Local Databases${NC}\n"

# Export main database
export_database "$MAIN_DB"

# Export tenant databases
for tenant_db in "${TENANT_DBS[@]}"; do
    # Check if database exists
    if mysql -h "$LOCAL_DB_HOST" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASSWORD" -e "USE $tenant_db" 2>/dev/null; then
        export_database "$tenant_db"
    else
        echo -e "${YELLOW}⚠ Database $tenant_db not found, skipping${NC}"
    fi
done

# Step 2: Create migration package
echo -e "\n${BLUE}Step 2: Creating Migration Package${NC}\n"

PACKAGE_NAME="database-migration-$(date +%Y%m%d_%H%M%S).tar.gz"
cd "$MIGRATION_DIR"
tar -czf "../$PACKAGE_NAME" *.sql.gz

echo -e "${GREEN}✓ Migration package created: /tmp/$PACKAGE_NAME${NC}"

# Step 3: Generate import script
echo -e "\n${BLUE}Step 3: Generating Import Script${NC}\n"

IMPORT_SCRIPT="$MIGRATION_DIR/import-on-production.sh"

cat > "$IMPORT_SCRIPT" << 'EOFSCRIPT'
#!/bin/bash

###############################################################################
# Production Database Import Script
# Run this script ON THE PRODUCTION SERVER after uploading migration files
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   Production Database Import${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}\n"

# Production database credentials
PROD_DB_USER="darasa360_user"
PROD_DB_HOST="localhost"
PROD_MAIN_DB="darasa360_main"

# Prompt for password
echo -e "${YELLOW}Enter production database password:${NC}"
read -s PROD_DB_PASSWORD
echo ""

# Function to import database
import_database() {
    local sql_gz_file=$1
    local db_name=$2

    echo -e "${YELLOW}Importing $db_name...${NC}"

    # Create database if it doesn't exist
    mysql -h "$PROD_DB_HOST" -u "$PROD_DB_USER" -p"$PROD_DB_PASSWORD" \
        -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

    # Import data
    gunzip -c "$sql_gz_file" | mysql -h "$PROD_DB_HOST" -u "$PROD_DB_USER" -p"$PROD_DB_PASSWORD" "$db_name"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Imported $db_name successfully${NC}"
    else
        echo -e "${RED}✗ Failed to import $db_name${NC}"
        return 1
    fi
}

# Import main database
echo -e "\n${YELLOW}Importing main database...${NC}"
if [ -f "laravel.sql.gz" ]; then
    import_database "laravel.sql.gz" "$PROD_MAIN_DB"
fi

# Import tenant databases
echo -e "\n${YELLOW}Importing tenant databases...${NC}"
for sql_file in *.sql.gz; do
    if [ "$sql_file" != "laravel.sql.gz" ]; then
        db_name=$(basename "$sql_file" .sql.gz)
        import_database "$sql_file" "$db_name"
    fi
done

echo -e "\n${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   Database Import Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Update .env with database credentials"
echo -e "2. Run: php artisan migrate --force"
echo -e "3. Run: php artisan config:cache"
echo -e "4. Test the application\n"

EOFSCRIPT

chmod +x "$IMPORT_SCRIPT"
mv "$IMPORT_SCRIPT" /tmp/
echo -e "${GREEN}✓ Import script created: /tmp/import-on-production.sh${NC}"

# Step 4: Display instructions
echo -e "\n${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   Migration Files Ready!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}Files created:${NC}"
echo -e "  📦 Migration package: ${BLUE}/tmp/$PACKAGE_NAME${NC}"
echo -e "  📜 Import script: ${BLUE}/tmp/import-on-production.sh${NC}"
echo -e ""

echo -e "${YELLOW}Next Steps:${NC}"
echo -e ""
echo -e "${BLUE}1. Upload migration files to production server:${NC}"
echo -e "   ${GREEN}scp /tmp/$PACKAGE_NAME user@server:/home/username/darasa360.olamtec.co.tz/${NC}"
echo -e "   ${GREEN}scp /tmp/import-on-production.sh user@server:/home/username/darasa360.olamtec.co.tz/${NC}"
echo -e ""
echo -e "${BLUE}2. SSH into production server:${NC}"
echo -e "   ${GREEN}ssh user@server${NC}"
echo -e ""
echo -e "${BLUE}3. Navigate to application directory:${NC}"
echo -e "   ${GREEN}cd /home/username/darasa360.olamtec.co.tz${NC}"
echo -e ""
echo -e "${BLUE}4. Extract migration package:${NC}"
echo -e "   ${GREEN}tar -xzf $PACKAGE_NAME${NC}"
echo -e ""
echo -e "${BLUE}5. Run import script:${NC}"
echo -e "   ${GREEN}bash import-on-production.sh${NC}"
echo -e ""
echo -e "${BLUE}6. Update .env file on server:${NC}"
echo -e "   ${GREEN}nano .env${NC}"
echo -e "   Update DB_DATABASE to: darasa360_main"
echo -e "   Update DB_USERNAME to: darasa360_user"
echo -e "   Update DB_PASSWORD to: [your password]"
echo -e ""
echo -e "${BLUE}7. Run migrations on production:${NC}"
echo -e "   ${GREEN}php artisan migrate --force${NC}"
echo -e ""
echo -e "${BLUE}8. Optimize application:${NC}"
echo -e "   ${GREEN}php artisan config:cache${NC}"
echo -e "   ${GREEN}php artisan route:cache${NC}"
echo -e "   ${GREEN}php artisan view:cache${NC}"
echo -e ""

echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}\n"

# Cleanup option
echo -e "${YELLOW}Keep migration files in $MIGRATION_DIR? (y/n)${NC}"
read -r keep_files

if [ "$keep_files" != "y" ]; then
    rm -rf "$MIGRATION_DIR"
    echo -e "${GREEN}✓ Cleaned up temporary files${NC}"
fi

echo -e "\n${GREEN}Database migration preparation complete!${NC}\n"

exit 0
