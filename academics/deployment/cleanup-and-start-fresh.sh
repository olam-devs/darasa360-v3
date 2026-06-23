#!/bin/bash

#
# Cleanup and Start Fresh Script
# This script removes all existing schools and prepares the system for new multi-tenancy setup
#

echo "========================================"
echo "Cleanup and Start Fresh"
echo "========================================"
echo ""
echo "This script will:"
echo "1. Remove all existing schools from the database"
echo "2. Drop all school databases"
echo "3. Drop all school MySQL users"
echo ""
echo "WARNING: This is DESTRUCTIVE and cannot be undone!"
echo ""
read -p "Are you sure you want to continue? (type YES): " confirmation

if [ "$confirmation" != "YES" ]; then
    echo "Cleanup cancelled."
    exit 0
fi

echo ""
echo "Running cleanup script..."
echo ""

# Change to the Laravel root directory
cd "$(dirname "$0")/.."

# Run the PHP cleanup script
php deployment/cleanup-school-databases.php

echo ""
echo "========================================"
echo "Cleanup Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Login as SuperAdmin"
echo "2. Create new schools with proper database credentials"
echo "3. Each school will have its own MySQL user for proper permission isolation"
echo ""
