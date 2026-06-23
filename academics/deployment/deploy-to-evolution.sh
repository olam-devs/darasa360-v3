#!/bin/bash

###############################################################################
# OLAM - Evolution Server Deployment Script
# Domain: https://darasa360.olamtec.co.tz
# This script prepares and deploys the application to Evolution server
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
echo -e "${GREEN}     OLAM - Evolution Server Deployment Wizard${NC}"
echo -e "${GREEN}     Domain: darasa360.olamtec.co.tz${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

# Configuration
APP_DIR="$(pwd)"
DEPLOY_DIR="${APP_DIR}/deployment-temp"
PACKAGE_DATE=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="darasa360-production-${PACKAGE_DATE}.tar.gz"

# Function to print section headers
print_section() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Step 1: Pre-deployment checks
print_section "Step 1: Pre-Deployment Checks"

echo -e "${YELLOW}Checking required tools...${NC}"

command -v php >/dev/null 2>&1 || { echo -e "${RED}✗ PHP not found${NC}"; exit 1; }
echo -e "${GREEN}✓ PHP installed${NC}"

command -v composer >/dev/null 2>&1 || { echo -e "${RED}✗ Composer not found${NC}"; exit 1; }
echo -e "${GREEN}✓ Composer installed${NC}"

command -v npm >/dev/null 2>&1 || { echo -e "${RED}✗ NPM not found${NC}"; exit 1; }
echo -e "${GREEN}✓ NPM installed${NC}"

command -v git >/dev/null 2>&1 || { echo -e "${RED}✗ Git not found${NC}"; exit 1; }
echo -e "${GREEN}✓ Git installed${NC}"

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}✗ .env file not found${NC}"
    exit 1
fi
echo -e "${GREEN}✓ .env file exists${NC}"

# Step 2: Clean previous deployment
print_section "Step 2: Cleaning Previous Deployment Files"

if [ -d "$DEPLOY_DIR" ]; then
    echo -e "${YELLOW}Removing old deployment directory...${NC}"
    rm -rf "$DEPLOY_DIR"
fi

mkdir -p "$DEPLOY_DIR"
echo -e "${GREEN}✓ Clean deployment directory created${NC}"

# Step 3: Install/Update dependencies
print_section "Step 3: Installing Dependencies"

echo -e "${YELLOW}Installing Composer dependencies (production)...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

echo -e "${YELLOW}Installing NPM dependencies (all for build)...${NC}"
npm install
echo -e "${GREEN}✓ NPM dependencies installed${NC}"

echo -e "${YELLOW}Building frontend assets...${NC}"
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"

echo -e "${YELLOW}Removing dev node_modules for production...${NC}"
rm -rf node_modules
npm install --omit=dev
echo -e "${GREEN}✓ Production node_modules ready${NC}"

# Step 4: Run tests (skipped for non-interactive mode)
print_section "Step 4: Skipping Tests"
echo -e "${YELLOW}⚠ Tests skipped (non-interactive mode)${NC}"
echo -e "${YELLOW}Run 'php artisan test' manually if needed${NC}"

# Step 5: Create deployment package
print_section "Step 5: Creating Deployment Package"

echo -e "${YELLOW}Copying application files...${NC}"

# Copy essential files and directories
rsync -av --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='deployment-temp' \
    --exclude='*.tar.gz' \
    --exclude='.env.local' \
    --exclude='tests' \
    --exclude='.phpunit.result.cache' \
    ./ "$DEPLOY_DIR/"

echo -e "${GREEN}✓ Files copied${NC}"

# Create production .env template
echo -e "${YELLOW}Creating production .env template...${NC}"

cat > "$DEPLOY_DIR/.env.production" << 'EOF'
APP_NAME="DARASA 360"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_ON_SERVER
APP_DEBUG=false
APP_TIMEZONE=Africa/Dar_es_Salaam
APP_URL=https://darasa360.olamtec.co.tz

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=darasa360_main
DB_USERNAME=darasa360_user
DB_PASSWORD=CHANGE_THIS_PASSWORD

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=file
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@olamtec.co.tz
MAIL_FROM_NAME="${APP_NAME}"

SMS_API_TOKEN=874c60a1b825ef400518ce36e3fcc917
SMS_SENDER_NAME="DARASA 360"
SMS_ENDPOINT=https://messaging-service.co.tz/api/sms/v2/text/single
SMS_ENDPOINT_MULTI=https://messaging-service.co.tz/api/sms/v2/text/multi

VITE_APP_NAME="${APP_NAME}"
EOF

echo -e "${GREEN}✓ Production .env template created${NC}"

# Create deployment instructions
echo -e "${YELLOW}Creating deployment instructions...${NC}"

cat > "$DEPLOY_DIR/DEPLOY_ON_SERVER.txt" << 'EOF'
═══════════════════════════════════════════════════════════
  DEPLOYMENT INSTRUCTIONS FOR EVOLUTION SERVER
  https://darasa360.olamtec.co.tz
═══════════════════════════════════════════════════════════

STEP 1: UPLOAD FILES
────────────────────────────────────────────────────────────
Upload this entire directory to:
  /home/username/darasa360.olamtec.co.tz/

STEP 2: SET FILE PERMISSIONS
────────────────────────────────────────────────────────────
SSH into server and run:

cd /home/username/darasa360.olamtec.co.tz

# Set ownership
chown -R username:username .

# Set permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env.production
chmod +x artisan

STEP 3: CONFIGURE ENVIRONMENT
────────────────────────────────────────────────────────────
# Copy and edit .env file
cp .env.production .env
nano .env

Update these values:
  - APP_KEY (generate with: php artisan key:generate)
  - DB_DATABASE=darasa360_main
  - DB_USERNAME=darasa360_user
  - DB_PASSWORD=[your database password]
  - MAIL_USERNAME=[your email]
  - MAIL_PASSWORD=[your email password]

Save: Ctrl+X, Y, Enter

STEP 4: CREATE DATABASE
────────────────────────────────────────────────────────────
Via cPanel MySQL:
  1. Create database: darasa360_main
  2. Create user: darasa360_user (with strong password)
  3. Grant ALL PRIVILEGES to user on database

Or via command line:
  mysql -u root -p

  CREATE DATABASE darasa360_main
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

  CREATE USER 'darasa360_user'@'localhost'
    IDENTIFIED BY 'STRONG_PASSWORD';

  GRANT ALL PRIVILEGES ON darasa360_main.*
    TO 'darasa360_user'@'localhost';

  GRANT ALL PRIVILEGES ON `olam_school_%`.*
    TO 'darasa360_user'@'localhost';

  FLUSH PRIVILEGES;
  EXIT;

STEP 5: RUN MIGRATIONS
────────────────────────────────────────────────────────────
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SuperAdminSeeder --force

STEP 6: OPTIMIZE FOR PRODUCTION
────────────────────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

STEP 7: TEST APPLICATION
────────────────────────────────────────────────────────────
Access: https://darasa360.olamtec.co.tz

Default login:
  Username: admin
  Password: admin222

⚠️ CHANGE PASSWORD IMMEDIATELY!

═══════════════════════════════════════════════════════════
  DEPLOYMENT COMPLETE
═══════════════════════════════════════════════════════════
EOF

echo -e "${GREEN}✓ Deployment instructions created${NC}"

# Create post-deployment checklist
cat > "$DEPLOY_DIR/POST_DEPLOYMENT_CHECKLIST.txt" << 'EOF'
═══════════════════════════════════════════════════════════
  POST-DEPLOYMENT CHECKLIST
═══════════════════════════════════════════════════════════

IMMEDIATE (After Deployment):
  [ ] Site is accessible via HTTPS
  [ ] Login works
  [ ] Change default admin password
  [ ] Create test school
  [ ] Test student registration
  [ ] Test exam entry
  [ ] Test SMS sending
  [ ] Verify file uploads work

WITHIN 24 HOURS:
  [ ] Set up database backups
  [ ] Configure cron jobs
  [ ] Test all major features
  [ ] Monitor error logs
  [ ] Test on mobile devices

WITHIN 1 WEEK:
  [ ] Train administrators
  [ ] Import production data
  [ ] Set up monitoring
  [ ] Configure email settings
  [ ] Test payment integration (if applicable)

SECURITY:
  [ ] Verify HTTPS is enforced
  [ ] Test rate limiting
  [ ] Check file permissions
  [ ] Review user permissions
  [ ] Enable firewall rules

ONGOING:
  [ ] Monitor logs daily
  [ ] Check backups weekly
  [ ] Update dependencies monthly
  [ ] Security audit quarterly

═══════════════════════════════════════════════════════════
EOF

echo -e "${GREEN}✓ Post-deployment checklist created${NC}"

# Step 6: Package everything
print_section "Step 6: Creating Archive"

cd "$DEPLOY_DIR/.."
echo -e "${YELLOW}Creating compressed archive...${NC}"

tar -czf "$PACKAGE_NAME" -C "$DEPLOY_DIR" .

if [ $? -eq 0 ]; then
    PACKAGE_SIZE=$(du -h "$PACKAGE_NAME" | cut -f1)
    echo -e "${GREEN}✓ Package created: $PACKAGE_NAME ($PACKAGE_SIZE)${NC}"
else
    echo -e "${RED}✗ Failed to create package${NC}"
    exit 1
fi

# Step 7: Final summary
print_section "Step 7: Deployment Package Ready!"

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     DEPLOYMENT PACKAGE CREATED SUCCESSFULLY!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📦 Package Information:${NC}"
echo -e "   Name: ${CYAN}$PACKAGE_NAME${NC}"
echo -e "   Size: ${CYAN}$PACKAGE_SIZE${NC}"
echo -e "   Location: ${CYAN}$(pwd)/$PACKAGE_NAME${NC}\n"

echo -e "${BLUE}📋 Next Steps:${NC}\n"

echo -e "${YELLOW}1. Upload to Evolution Server:${NC}"
echo -e "   ${GREEN}Via FTP/SFTP to: /home/username/darasa360.olamtec.co.tz/${NC}"
echo -e "   ${GREEN}Or via SCP: scp $PACKAGE_NAME user@server:/home/username/darasa360.olamtec.co.tz/${NC}\n"

echo -e "${YELLOW}2. Extract on server:${NC}"
echo -e "   ${GREEN}ssh user@server${NC}"
echo -e "   ${GREEN}cd /home/username/darasa360.olamtec.co.tz${NC}"
echo -e "   ${GREEN}tar -xzf $PACKAGE_NAME${NC}\n"

echo -e "${YELLOW}3. Follow deployment instructions:${NC}"
echo -e "   ${GREEN}Read DEPLOY_ON_SERVER.txt in the uploaded directory${NC}\n"

echo -e "${YELLOW}4. After deployment:${NC}"
echo -e "   ${GREEN}Follow POST_DEPLOYMENT_CHECKLIST.txt${NC}\n"

echo -e "${BLUE}📚 Documentation:${NC}"
echo -e "   ${CYAN}DEPLOY_ON_SERVER.txt${NC} - Step-by-step deployment guide"
echo -e "   ${CYAN}POST_DEPLOYMENT_CHECKLIST.txt${NC} - Post-deployment tasks"
echo -e "   ${CYAN}.env.production${NC} - Production environment template\n"

# Cleanup option
echo -e "${YELLOW}Clean up temporary files? (y/n)${NC}"
read -r cleanup

if [ "$cleanup" = "y" ]; then
    rm -rf "$DEPLOY_DIR"
    echo -e "${GREEN}✓ Temporary files cleaned${NC}"
fi

echo -e "\n${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}     Ready for Evolution Server Deployment!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

exit 0
