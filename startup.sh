cd /usr/www/html/photo-display/node/ && supervisor /usr/www/html/photo-display/node/media_display.js&
unclutter -idle 2 -root&
sleep 5
google-chrome --kiosk http://localhost:8080/slides



