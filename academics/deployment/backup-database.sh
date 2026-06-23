#!/bin/bash

###############################################################################
# OLAM Database Backup Script
# This script backs up all databases (main + tenant databases)
###############################################################################

set -e

# Configuration
BACKUP_DIR="/backups/olam"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30
DB_USER="olam_user"
DB_PASSWORD="YOUR_PASSWORD"
DB_HOST="127.0.0.1"

# Main database
MAIN_DB="olam_main"

# Tenant databases (add your school databases here)
TENANT_DBS=(
    "FezaSchools"
    "Imani"
    "olam_school_greenvalley"
    "olam_school_local"
)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}OLAM Database Backup - $DATE${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Function to backup a database
backup_database() {
    local db_name=$1
    local backup_file="$BACKUP_DIR/${db_name}_${DATE}.sql.gz"

    echo -e "${YELLOW}Backing up $db_name...${NC}"

    mysqldump \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        --routines \
        --triggers \
        --events \
        "$db_name" | gzip > "$backup_file"

    if [ $? -eq 0 ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        echo -e "${GREEN}✓ Backed up $db_name ($size)${NC}"
    else
        echo -e "${RED}✗ Failed to backup $db_name${NC}"
        return 1
    fi
}

# Backup main database
backup_database "$MAIN_DB"

# Backup tenant databases
for tenant_db in "${TENANT_DBS[@]}"; do
    backup_database "$tenant_db"
done

# Create a backup of uploaded files
echo -e "\n${YELLOW}Backing up uploaded files...${NC}"
FILE_BACKUP="$BACKUP_DIR/files_${DATE}.tar.gz"
tar -czf "$FILE_BACKUP" -C /var/www/olam_portal/public uploads/ 2>/dev/null || true

if [ -f "$FILE_BACKUP" ]; then
    local size=$(du -h "$FILE_BACKUP" | cut -f1)
    echo -e "${GREEN}✓ Backed up files ($size)${NC}"
fi

# Remove old backups
echo -e "\n${YELLOW}Removing backups older than $RETENTION_DAYS days...${NC}"
find "$BACKUP_DIR" -name "*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.tar.gz" -type f -mtime +$RETENTION_DAYS -delete

# Count remaining backups
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR" | wc -l)
echo -e "${GREEN}$BACKUP_COUNT backup files in $BACKUP_DIR${NC}"

# Calculate disk usage
DISK_USAGE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo -e "${GREEN}Total backup size: $DISK_USAGE${NC}"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Backup Completed Successfully${NC}"
echo -e "${GREEN}========================================${NC}\n"

exit 0
