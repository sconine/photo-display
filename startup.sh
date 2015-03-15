cd /usr/www/html/photo-display/node/ && supervisor /usr/www/html/photo-display/node/media_display.js&
unclutter -idle 2 -root&
sed -i 's/exit_type\"\:\ \"Crashed/exit_type\"\:\ \"normal/g' /home/conine/.config/google-chrome/Default/Preferences
sleep 5
google-chrome --kiosk http://localhost:8080/slides



