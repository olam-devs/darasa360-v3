#!/bin/bash

###############################################################################
# Evolution Server - Fix Directory Structure Script
# This script fixes the 403 error by restructuring the deployment
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
echo -e "${GREEN}     Evolution Server - Directory Structure Fix${NC}"
echo -e "${GREEN}     Domain: darasa360.olamtec.co.tz${NC}"
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

print_section "Step 1: Verify Current Location"

# Check if we're in the right location
if [ ! -d "$DOMAIN_ROOT" ]; then
    echo -e "${RED}✗ Domain directory not found: $DOMAIN_ROOT${NC}"
    echo -e "${YELLOW}Please make sure you're on the Evolution server${NC}"
    exit 1
fi

cd "$DOMAIN_ROOT"
echo -e "${GREEN}✓ Found domain directory: $DOMAIN_ROOT${NC}"

# Check if public_html exists and contains Laravel files
if [ ! -d "public_html/app" ]; then
    echo -e "${RED}✗ Laravel files not found in public_html${NC}"
    echo -e "${YELLOW}This script should be run when Laravel files are inside public_html${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Confirmed Laravel files are in public_html${NC}"

print_section "Step 2: Create Backup"

BACKUP_DIR="${DOMAIN_ROOT}/backup-$(date +%Y%m%d_%H%M%S)"
echo -e "${YELLOW}Creating backup at: $BACKUP_DIR${NC}"

mkdir -p "$BACKUP_DIR"
cp -r public_html "$BACKUP_DIR/"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Backup created successfully${NC}"
    echo -e "${BLUE}   Backup location: $BACKUP_DIR${NC}"
else
    echo -e "${RED}✗ Backup failed${NC}"
    exit 1
fi

print_section "Step 3: Restructure Directories"

echo -e "${YELLOW}Moving Laravel application files to domain root...${NC}"

# Move Laravel directories
echo -e "${BLUE}Moving core directories...${NC}"
for dir in app bootstrap config database public resources routes storage vendor; do
    if [ -d "public_html/$dir" ]; then
        mv "public_html/$dir" ./
        echo -e "${GREEN}  ✓ Moved $dir/${NC}"
    fi
done

# Move important files
echo -e "${BLUE}Moving configuration files...${NC}"
for file in artisan composer.json composer.lock package.json package-lock.json phpunit.xml vite.config.js yarn.lock; do
    if [ -f "public_html/$file" ]; then
        mv "public_html/$file" ./
        echo -e "${GREEN}  ✓ Moved $file${NC}"
    fi
done

# Move .env files
echo -e "${BLUE}Moving environment files...${NC}"
for envfile in .env .env.example .env.production .env.production.example; do
    if [ -f "public_html/$envfile" ]; then
        mv "public_html/$envfile" ./
        echo -e "${GREEN}  ✓ Moved $envfile${NC}"
    fi
done

# Move documentation files
echo -e "${BLUE}Moving documentation and scripts...${NC}"
mv public_html/*.md ./ 2>/dev/null || true
mv public_html/*.txt ./ 2>/dev/null || true
mv public_html/*.sh ./ 2>/dev/null || true

# Move hidden directories and files
echo -e "${BLUE}Moving hidden files and directories...${NC}"
for hidden in .gitignore .gitattributes .editorconfig .eslintrc.json .eslintignore .prettierrc.json .prettierignore .vscode .github .idea .claude; do
    if [ -e "public_html/$hidden" ]; then
        mv "public_html/$hidden" ./ 2>/dev/null || true
        echo -e "${GREEN}  ✓ Moved $hidden${NC}"
    fi
done

# Move deployment and dev-scripts directories
for dir in deployment dev-scripts documentation stubs; do
    if [ -d "public_html/$dir" ]; then
        mv "public_html/$dir" ./
        echo -e "${GREEN}  ✓ Moved $dir/${NC}"
    fi
done

echo -e "${GREEN}✓ All files moved to domain root${NC}"

print_section "Step 4: Configure public_html Symlink"

# Remove old public_html directory if it's now empty or only has a few files left
echo -e "${YELLOW}Checking public_html directory...${NC}"
REMAINING_FILES=$(ls -A public_html 2>/dev/null | wc -l)

if [ "$REMAINING_FILES" -eq 0 ]; then
    echo -e "${BLUE}public_html is empty, removing it...${NC}"
    rmdir public_html
elif [ "$REMAINING_FILES" -le 3 ]; then
    echo -e "${YELLOW}public_html has $REMAINING_FILES files remaining${NC}"
    ls -la public_html
    echo -e "${YELLOW}Removing public_html anyway...${NC}"
    rm -rf public_html
else
    echo -e "${RED}✗ public_html still has $REMAINING_FILES files!${NC}"
    echo -e "${YELLOW}Contents:${NC}"
    ls -la public_html
    echo -e "${YELLOW}Please review and remove manually if needed${NC}"
    exit 1
fi

# Create symlink
echo -e "${YELLOW}Creating symlink: public_html -> public${NC}"
ln -s public public_html

if [ -L "public_html" ]; then
    echo -e "${GREEN}✓ Symlink created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create symlink${NC}"
    exit 1
fi

print_section "Step 5: Set File Permissions"

echo -e "${YELLOW}Setting ownership to ${CURRENT_USER}:${CURRENT_USER}...${NC}"
chown -R "${CURRENT_USER}:${CURRENT_USER}" .
echo -e "${GREEN}✓ Ownership set${NC}"

echo -e "${YELLOW}Setting directory permissions (755)...${NC}"
find . -type d -exec chmod 755 {} \;
echo -e "${GREEN}✓ Directory permissions set${NC}"

echo -e "${YELLOW}Setting file permissions (644)...${NC}"
find . -type f -exec chmod 644 {} \;
echo -e "${GREEN}✓ File permissions set${NC}"

echo -e "${YELLOW}Setting writable permissions for storage and cache...${NC}"
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Storage and cache are writable${NC}"

echo -e "${YELLOW}Making artisan executable...${NC}"
chmod +x artisan
echo -e "${GREEN}✓ Artisan is executable${NC}"

if [ -f ".env" ]; then
    echo -e "${YELLOW}Securing .env file...${NC}"
    chmod 600 .env
    echo -e "${GREEN}✓ .env file secured${NC}"
fi

print_section "Step 6: Configure Environment"

if [ ! -f ".env" ] && [ -f ".env.production" ]; then
    echo -e "${YELLOW}Creating .env from .env.production template...${NC}"
    cp .env.production .env
    echo -e "${GREEN}✓ .env file created${NC}"
    echo -e "${YELLOW}⚠ You MUST edit .env file with your database credentials!${NC}"
elif [ -f ".env" ]; then
    echo -e "${GREEN}✓ .env file already exists${NC}"
else
    echo -e "${RED}✗ No .env or .env.production file found!${NC}"
    echo -e "${YELLOW}You will need to create .env manually${NC}"
fi

print_section "Step 7: Verify Structure"

echo -e "${YELLOW}Verifying directory structure...${NC}\n"

echo -e "${BLUE}Current directory structure:${NC}"
ls -lah | grep -E "^d|^l" | awk '{print "  " $9 " -> " $11}'

echo ""
echo -e "${BLUE}Checking critical files:${NC}"

# Check critical files and directories
CHECKS=(
    "app/:directory"
    "public/:directory"
    "public_html:symlink"
    ".env:file"
    "artisan:file"
    "composer.json:file"
)

ALL_GOOD=true
for check in "${CHECKS[@]}"; do
    IFS=':' read -r path type <<< "$check"

    if [ "$type" = "directory" ]; then
        if [ -d "$path" ]; then
            echo -e "  ${GREEN}✓${NC} $path (directory)"
        else
            echo -e "  ${RED}✗${NC} $path (directory missing)"
            ALL_GOOD=false
        fi
    elif [ "$type" = "symlink" ]; then
        if [ -L "$path" ]; then
            target=$(readlink "$path")
            echo -e "  ${GREEN}✓${NC} $path -> $target (symlink)"
        else
            echo -e "  ${RED}✗${NC} $path (symlink missing)"
            ALL_GOOD=false
        fi
    elif [ "$type" = "file" ]; then
        if [ -f "$path" ]; then
            echo -e "  ${GREEN}✓${NC} $path (file)"
        else
            echo -e "  ${YELLOW}⚠${NC} $path (file missing)"
        fi
    fi
done

print_section "Step 8: Final Configuration Steps"

echo -e "${BLUE}Next steps to complete deployment:${NC}\n"

echo -e "${YELLOW}1. Configure .env file:${NC}"
echo -e "   ${CYAN}nano .env${NC}"
echo -e "   Update these values:"
echo -e "     - DB_DATABASE=darasa360_main"
echo -e "     - DB_USERNAME=darasa360_user"
echo -e "     - DB_PASSWORD=[your_database_password]"
echo -e "     - MAIL_USERNAME=[your_email]"
echo -e "     - MAIL_PASSWORD=[your_email_password]\n"

echo -e "${YELLOW}2. Generate application key:${NC}"
echo -e "   ${CYAN}php artisan key:generate${NC}\n"

echo -e "${YELLOW}3. Run migrations (if fresh database):${NC}"
echo -e "   ${CYAN}php artisan migrate --force${NC}"
echo -e "   ${CYAN}php artisan db:seed --class=SuperAdminSeeder --force${NC}\n"

echo -e "${YELLOW}4. Optimize for production:${NC}"
echo -e "   ${CYAN}php artisan config:cache${NC}"
echo -e "   ${CYAN}php artisan route:cache${NC}"
echo -e "   ${CYAN}php artisan view:cache${NC}"
echo -e "   ${CYAN}php artisan optimize${NC}\n"

echo -e "${YELLOW}5. Test the site:${NC}"
echo -e "   ${CYAN}https://darasa360.olamtec.co.tz${NC}\n"

if [ "$ALL_GOOD" = true ]; then
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}     ✓ Directory Structure Fixed Successfully!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"
else
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}     ⚠ Structure Fixed with Warnings${NC}"
    echo -e "${YELLOW}     Please review the messages above${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}\n"
fi

echo -e "${BLUE}Backup Location:${NC} $BACKUP_DIR"
echo -e "${BLUE}Domain Root:${NC} $DOMAIN_ROOT"
echo -e "${BLUE}Web Root:${NC} $DOMAIN_ROOT/public (via public_html symlink)\n"

exit 0
