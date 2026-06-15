#!/bin/bash

cd /var/www/html/philgeps || exit


echo "Pulling latest code..."

git pull origin main

echo "Deployment finished."
