#!/bin/bash

TIMESTAMP=$(date +'%Y-%m-%d_%H-%M-%S')
mysqldump -h db -u nahuel.resala -p56843589 cooperativa > /backups/backup_$TIMESTAMP.sql
echo "Backup realizado: backup_$TIMESTAMP.sql"
