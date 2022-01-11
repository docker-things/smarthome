#!/bin/bash

echo ' > app/web/UI/Controller/Dashboard.php'
rm -f app/web/UI/Controller/Dashboard.php
cp app/web/UI/Controller/DashboardDev.php app/web/UI/Controller/Dashboard.php
sed -i 's/Dev\./\./g' app/web/UI/Controller/Dashboard.php
sed -i 's/Dev\_/\_/g' app/web/UI/Controller/Dashboard.php
sed -i 's/Dev extends/ extends/g' app/web/UI/Controller/Dashboard.php

echo ' > app/web/UI/Controller/Screen/'
rm -rf app/web/UI/Controller/Screen
cp -r app/web/UI/Controller/ScreenDev app/web/UI/Controller/Screen
sed -i 's/Dev\_/\_/g' app/web/UI/Controller/Screen/*

echo ' > app/web/res/css/dashboardDev.css'
rm -f app/web/res/css/dashboard.css
cp app/web/res/css/dashboardDev.css app/web/res/css/dashboard.css

echo ' > app/web/res/css/screen/'
rm -rf app/web/res/css/screen
cp -r app/web/res/css/screenDev app/web/res/css/screen

echo ' > app/web/res/js/dashboardDev.js'
rm -f app/web/res/js/dashboard.js
cp app/web/res/js/dashboardDev.js app/web/res/js/dashboard.js

echo ' > app/web/res/js/screen/'
rm -rf app/web/res/js/screen
cp -r app/web/res/js/screenDev app/web/res/js/screen
