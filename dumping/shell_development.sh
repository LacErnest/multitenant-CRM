#!/bin/bash

# Database connection details (replace with your actual credentials)
DB_HOST="127.0.0.1"
DB_USER="root"
DB_PASSWORD="secret"
DB_NAME="oz-finance"

# Get the parent directory of the script
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )

# Get the parent directory of the parent directory
GRANDPARENT_DIR=$( cd "$( dirname "$SCRIPT_DIR" )" >/dev/null 2>&1 && pwd )

# Get the great-grandparent directory
GREAT_GRANDPARENT_DIR=$( cd "$( dirname "$GRANDPARENT_DIR" )" >/dev/null 2>&1 && pwd )

DUMP_DIR="$GREAT_GRANDPARENT_DIR/dumping"
OUTPUT_DIR="$DUMP_DIR/output"
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


# Display message before export
echo "** Starting export process from '$SQL_FILE'..."
read -r -p "Dump companies and oz-finance databases. Are you sure? (y/N) " export_response
if [[ $export_response =~ ^([Yy]$) ]]; then
  # Get the list of database IDs from the companies table
  databases=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" -N -e "SELECT DISTINCT(id) FROM \`${DB_NAME}\`.companies")

  # Initialize a variable to hold the database names
  databases_to_dump=""

  # Loop through the IDs and construct the list of databases
  first=true
  for id in $databases; do
      databases_to_dump+="${id//-/} "
  done

  databases_to_dump+="$DB_NAME"

  echo "$databases_to_dump"

  mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" --databases $databases_to_dump > "$INPUT_SQL_FILE"
  if [[ $? -eq 0 ]]; then
    echo "** Database export completed successfully! **"
  else
    echo "** Error: Failed to export database. **"
    exit 1
  fi
else
  echo "** Export cencelled **"
fi

read -r -p "Anonymize companies and oz-finance databases. Are you sure? (y/N) " anonymization_response

if [[ $anonymization_response =~ ^([Yy]$) ]]; then
  # Display message before anonymization
  echo "** Starting database anonymization process..."
  # Run Docker container for anonymization
  docker run --rm \
    -v "$DOCKER_VOLUME_CONF":/dumping/oz-finance-anon.conf \
    -v "$DOCKER_VOLUME_SQL":/dumping/oz-finance-anon.sql \
    -v "$DOCKER_VOLUME_OUTPUT":/dumping/output \
    oz-finance/db_dumping sh -c '/bin/myanon -f /dumping/oz-finance-anon.conf < /dumping/oz-finance-anon.sql > "/dumping/output/oz-finance-output.sql"'


  if [[ $? -eq 0 ]]; then
    echo "** Database anonymization completed successfully. **"

  else
    echo "** Error: Failed to anonymize database. **"
    exit 1
  fi

else
  echo "** Database anonymization canceled. **"
fi

# Confirmation prompt
read -r -p "This script will DELETE databases from oz-finance based on company_name. Are you sure? (y/N) " response
if [[ $response =~ ^([Yy]$) ]]; then

  # Connect to MySQL and iterate through companies
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT id FROM companies;" | while read -r company_name; do

    database_name="${company_name//-/}"

    # Skip database named "id"
    if [[ "$database_name" == "id" ]]; then
      continue
    fi

    # Construct and execute DROP DATABASE command with proper quoting
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE \`$database_name\`"

    if [[ $? -eq 0 ]]; then
      echo "Database '$database_name' deleted successfully."
    else
      echo "Error: Failed to delete database '$database_name'."
    fi
  done

  database_name="oz-finance"

  # Construct and execute DROP DATABASE command with proper quoting
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE \`$database_name\`"

  if [[ $? -eq 0 ]]; then
    echo "Database '$database_name' deleted successfully."
  else
    echo "Error: Failed to delete database '$database_name'."
  fi
else 
 echo "Database deletion cancelled."
fi

# Display message before import
echo "** Starting import process from '$OUTPUT_SQL_FILE'..."
read -r -p "Import many database. Are you sure? (y/N) " import_response
if [[ $import_response =~ ^([Yy]$) ]]; then
  # Import the database using mysqldump
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" < "$OUTPUT_SQL_FILE"

  if [[ $? -eq 0 ]]; then
    echo "** Database import completed successfully! **"
  else
    echo "** Error: Failed to import database. **"
  fi
else
  echo "Import cancelled*"
fi
