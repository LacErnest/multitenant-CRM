#!/bin/bash

# Database connection details (replace with your actual credentials)
DB_HOST="127.0.0.1"
DB_USER="root"
DB_PASSWORD="secret"
DB_NAME="oz-finance"
DB_PORT="3308"

# Get the parent directory of the script
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )

# Get the parent directory of the parent directory
GRANDPARENT_DIR=$( cd "$( dirname "$SCRIPT_DIR" )" >/dev/null 2>&1 && pwd )

# Get the great-grandparent directory
GREAT_GRANDPARENT_DIR=$( cd "$( dirname "$GRANDPARENT_DIR" )" >/dev/null 2>&1 && pwd )

DUMP_DIR="$GREAT_GRANDPARENT_DIR/dumping"
OUTPUT_DIR="$DUMP_DIR/output"
SCRIPT_OUTPUT_DIR="$SCRIPT_DIR/output"
PRIVILEGE_DIR="$SCRIPT_DIR/tenant_users.sql"
# Docker volume paths (adjust according to your directory structure)
CONF_FILE="$SCRIPT_DIR/oz-finance-anon.conf"
INPUT_SQL_FILE="$DUMP_DIR/oz-finance-anon.sql"
OUTPUT_SQL_FILE="$OUTPUT_DIR/oz-finance-output.sql"

DOCKER_VOLUME_CONF="$CONF_FILE"
DOCKER_VOLUME_SQL="$INPUT_SQL_FILE"
DOCKER_VOLUME_OUTPUT="$OUTPUT_DIR"

# Create output directory structure if it doesn't exist
mkdir -p "$OUTPUT_DIR"

# Safety checks
if [[ -z "$DB_HOST" || -z "$DB_USER" || -z "$DB_PASSWORD" || -z "$DB_NAME" ]]; then
  echo "Error: Please set environment variables for DB_HOST, DB_USER, DB_PASSWORD, and DB_NAME."
  exit 1
fi

# Display message before import
echo "** Starting import process from '$OUTPUT_SQL_FILE'..."
read -r -p "Import the database. Are you sure? (y/N) " import_response
if [[ $import_response =~ ^([Yy]$) ]]; then
  # Import the database using mysql
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -P"$DB_PORT" "$DB_NAME" < "$OUTPUT_SQL_FILE"

  if [[ $? -eq 0 ]]; then
    echo "** Database import completed successfully! **"
    # Execute the privilege SQL file
    echo "** Applying privileges from '$PRIVILEGE_DIR'..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -P"$DB_PORT" "$DB_NAME" < "$PRIVILEGE_DIR"
    
    if [[ $? -eq 0 ]]; then
      echo "** Privileges applied successfully! **"
    else
      echo "** Error: Failed to apply privileges. **"
    fi
  else
    echo "** Error: Failed to import database. **"
  fi
else
  echo "Import cancelled*"
fi
