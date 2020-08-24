#!/bin/sh
# Backup Mautic
# What time is it? Oh Yeah
timestamp() {
  date +"%Y-%m-%d_%H-%M-%S"
}
# I'm late for a very important Date!
# Run a backup
tar -cvf ../../mautic-backups/mautic-backup-$(timestamp).tar.gz ../* ../.* --exclude="../.."
