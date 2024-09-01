#!/bin/bash

cd /app

export_all_databases() {
    # Get the list of database IDs from the companies table
    databases=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -N -e "SELECT DISTINCT(id) FROM \`${DB_NAME}\`.companies")

    # Initialize a variable to hold the database names
    databases_to_dump=""

    # Loop through the IDs and construct the list of databases
    first=true
    for id in $databases; do
        databases_to_dump+="${id//-/} "
    done

    databases_to_dump+="$DB_NAME"

    echo "$databases_to_dump"

    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" --databases $databases_to_dump >dump.sql

    if [[ $? -eq 0 ]]; then
        echo "** Database export completed successfully! **"
    else
        echo "** Error: Failed to export database. **"
        exit 1
    fi
}

run_anonymization() {
    myanon -f anon.conf <dump.sql >anon.sql
    if [[ $? -eq 0 ]]; then
        echo "** Data anonymization completed successfully! **"
    else
        echo "** Error: Failed to anonymize data. **"
        exit 1
    fi
}

export_all_databases
run_anonymization
mv dump.sql /app/result
mv anon.sql /app/result
