Some Step by Steps in case helpful<br><br>

Buy a small PC<br>
----------------------------------<br>
These work great for like $500: Intel-D34010WYK (hit CTRL F10 for boot screen)<br>
Install ubuntu<br>
Install chrome browser<br>
Install g++<br>
Install git, configure and pull code<br>
Configure master_ config file (cp config.json master_config.json) <br>
Install node<br>
Install mySQL<br>
Install PHP-curl and PHP-CLI and PHP-mysql<br>
In Startup Applications add:<br>
google-chrome --kiosk http://localhost:8080/slides<br>
node /usr/www/html/photo-display/node/media_display.js<br>
NOTE: To exit Google Kiosk Mode press ALT-F4<br>
<br>
Provision an Amazon Linux AMI 2013.09.1 Instance<br>
----------------------------------<br>
<br>
Configure Amazon Firewall settings (EC2 Services interface)<br>
----------------------------------<br>
Go into Network & Security -> Security Groups<br>
Pick the security group the instance is in<br>
Allow inbound HTTP <br>
<br>
Configure You Amazon Instance with Programs you’ll need<br>
----------------------------------<br>
sudo yum update   <br>
sudo yum install git  <br>
sudo yum install php  <br>
sudo yum install nginx  <br>
sudo yum install spawn-fcgi  <br>
sudo yum install make automake gcc gcc-c++ wget <br>
sudo wget -O php-fastcgi-rpm.sh http://library.linode.com/assets/696-php-fastcgi-rpm.sh<br>
sudo mv php-fastcgi-rpm.sh /usr/bin/php-fastcgi<br>
sudo chmod +x /usr/bin/php-fastcgi<br>
sudo wget -O php-fastcgi-init-rpm.sh http://library.linode.com/assets/697-php-fastcgi-init-rpm.sh<br>
sudo mv php-fastcgi-init-rpm.sh /etc/rc.d/init.d/php-fastcgi<br>
sudo chmod +x /etc/rc.d/init.d/php-fastcgi<br>
sudo mv php-fastcgi-init-rpm.sh /etc/rc.d/init.d/php-fastcgi<br>
sudo chmod +x /etc/rc.d/init.d/php-fastcgi<br>
sudo chkconfig --add php-fastcgi<br>
sudo chkconfig php-fastcgi on<br>
sudo /etc/init.d/php-fastcgi start<br>
Folders where my files are going to live<br>
sudo mkdir /usr/www<br>
sudo mkdir html<br>
sudo chmod 777 html/<br>
sudo mkdir common<br>
<br>
Configure Mini Computer with Programs you’ll need<br>
----------------------------------<br>
sudo apt-get install git<br>
sudo apt-get install php5-cli<br>
sudo apt-get install php5-curl<br>
sudo apt-get install g++<br>
Folders where my files are going to live<br>
sudo mkdir /usr/www<br>
sudo mkdir html<br>
sudo chmod 777 html/<br>
sudo mkdir common<br>
<br>
Install AWS PHP libraries so you can use PHP to talk with AWS stuff<br>
----------------------------------<br>
I setup AWS php using composer on my Mac as follows (similar on AWS)<br>
----------------------------------<br>
Enable php.ini config file: cp /etc/php.ini.default /etc/php.ini<br>
Go to directory my scripts are going to be in: cd /usr/www/html/photo-display/php<br>
curl -s http://getcomposer.org/installer | php<br>
php composer.phar install<br>
mv vendor/ ../<br>
I setup AWS using composer on Amazon EC2 instance as follows<br>
----------------------------------<br>
Get composer for php<br>
in home directory<br>
curl -sS https://getcomposer.org/installer | php<br>
sudo mv composer.phar /usr/local/bin/composer<br>
in project folder (make sure composer.json file is in there) run: composer install <br>
<br>
Setup GIT<br>
----------------------------------<br>
Create a folder for a project and cd into it then<br>
(/usr/www/html/photo-display is where my project is)<br>
git config --global user.name "yourname"<br>
git config --global user.email "your@email"<br>
git init<br>
git remote add origin https://github.com/yourrepo.git<br>
git pull origin master<br>
<br>
Other Useful GIT info<br>
----------------------------------<br>
touch README<br>
git add README<br>
vi README<br>
git commit -m 'first commit'<br>
git remote add origin https://github.com/syourrepo.git<br>
git push origin master<br>
git diff<br>
git status<br>
git branch -avv<br>
git pull origin master<br>
git push -u origin master<br>
<br>
<br>
NGINX: important folders & Info<br>
----------------------------------<br>
/usr/sbin/nginx<br>
/etc/rc.d/init.d/nginx<br>
/etc/nginx<br>
sudo vi /etc/nginx/nginx.conf -> you will want to uncomment the fast-cgi section and set the script path correctly<br>
/etc/php.ini -> You will want to set the error reporting so that it outputs errors to the screen<br>
sudo /etc/rc.d/init.d/nginx start<br>
sudo /etc/init.d/php-fastcgi start<br>
<br>
<br>
<br>
<br>
Install Node.js and configure <br>
----------------------------------<br>
in /usr/src<br>
sudo wget http://nodejs.org/dist/v0.10.23/node-v0.10.23.tar.gz<br>
sudo tar zxf node-v0.10.23.tar.gz<br>
cd node-v0.10.23<br>
sudo ./configure <br>
sudo make<br>
sudo make install<br>
with a package.json file in the directory you want the node project in run: npm install (should be /use/www/html/photo-display/node )<br>
run program by typing node {path to .js file} or npm start in /use/www/html/photo-display/node<br>
for example page: http://192.168.2.142:8080/get_media<br>
<br>
<br>
Install MySQL on Amazon instance<br>
----------------------------------<br>
sudo yum install mysql-server<br>
sudo yum install php-mysql<br>
sudo /etc/init.d/mysqld start<br>
/usr/bin/mysqladmin -u root password 'yourpass'<br>
sudo mysql_secure_installation<br>
sudo chkconfig mysqld on<br>
CREATE DATABASE media;<br>
USE media;<br>
check storage used by:<br>
cd /var/lib/mysql<br>
sudo du -h<br>
df -h gives number for entire drive<br>
<br>
<br>
Install MySQL on ubuntu (https://help.ubuntu.com/12.04/serverguide/mysql.html)<br>
----------------------------------<br>
sudo apt-get install mysql-server<br>
sudo apt-get install php5-mysql (make sure php-cli is installed first)<br>
sudo service mysql restart<br>
mysql -u root -p<br>
CREATE DATABASE media;<br>
USE media;<br>
<br>
No need to create this the php script does for you but for reference<br>
----------------------------------<br>
CREATE TABLE my_media (<br>
media_id INT AUTO_INCREMENT PRIMARY KEY,<br>
media_path VARCHAR(2000) NOT NULL,<br>
media_type VARCHAR(128) NOT NULL,<br>
media_host VARCHAR(256) NULL,<br>
media_displayed DATE NULL,<br>
media_order INT);<br>
<br>
INSERT INTO my_media (media_path, media_type, media_host, media_order) VALUES ('/user/media/file.gif', 'image', '192.168.1.142', 1);<br>
<br>
<br>
More Resources:<br>
----------------------------------<br>
http://www.youtube.com/watch?v=_zaW2VZB1ok&feature=youtu.be<br>
http://docs.aws.amazon.com/aws-sdk-php/guide/latest/index.html<br>
http://blog.donaldderek.com/2013/06/build-your-own-google-tv-using-raspberrypi-nodejs-and-socket-io/<br>
<br>

