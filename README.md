<h1>photo-display</h1>
A system which will display photos and videos (media) on remote screens via a browser.  

Will work like this (see README_2 for install specifics):<br>
1) Put all your personal servers on a cloud storage solution like S3<br>
2) Provision a Dynamo database on AWS database<br>
3) Provision in mini server in the cloud: <b>EC2 public instance</b><br> 
4) Install nginx and mysql on this server<br>
5) Put scripts in <b>php</b> folder on this server<br>
6) Buy a chrome box, RasberryPi, Intel NUC (or some similar cheep, open source small computer with some local drive space): <b>localhost</b><br>
7) Install node.js and chrome on it <br>
8) Put the script from this project on this small computer<br>
9) Configure it so that when it starts:<br>
&#160;&#160;&#160;a) It boots chrome up in Kiosk mode to: http://localhost:8080/slideshow.html<br>
&#160;&#160;&#160;b) Also boots up a node.js server like: >node media-display.js<br>



<h1>Files on <b>localhost</b></h1>
<b>node/public/slideshow.html</b> A basic HTML page with Javascript in it that:<br>
a) Makes a call to a URL like http://localhost:8080/slides which will return JSON data that indicates what image/video to show next as well as how long to show that media before making a new call to the same URL<br>
b) With the JSON data it will know the URL of the media to show next and display the media on the browser screen in a full-screen kiosk mode.  URLs for the media it references will also be to the localhost, and might look something like this: http://localhost:8080/public/images/bucket/folder/image_name.jpg (though might be the IP of a host that is a peer on the same network)<br><br>
  
<b>node/media_display.js</b> A node.js script that creates a node.js server on localhost:8080<br>
  a) For anything in the "/public/" folder it should just hand back static content (node.js is beautiful because it handles 302s automatically!)<br>
  b) Requests for "/slides" will return JSON data that specifies the next media to display.  This data will come from a queue table in MySQL - See below for how this will get populated.  The media that is specified in the JSON data should be confirmed by get_media.php as "on local network" of the localhost before it is sent to the client.  If there are no new files set for viewing this script will show the oldest viewed files first and start looping back through the images that have been displayed.<br>
  c) Requests for "/find_media" look for media on the local hard drive.  This should return true/false, the IP to use in the URL and the space remaining on the local drive (in theory we could later use this information to better stripe data across all locally available peers).
  <br><br>
  
<B>get_media.php</b> A php script that will talk to the main EC2 server, retreive media to the local network and enqueue media for display.  As follows:<br>
  a) Every 5 minutes a cron job kicks this script off, script makes sure it is not already running<br>
  b) Script makes a curl call to a public URL like http://MyEC2instance.com/find_peers.php?screen_id=1&region=MainStreet that returns the IP addresses of it's peers, this list is stored in a MySQL Table.<br>
  c) Script makes a curl call to a public URL like http://MyEC2instance.com/send_media_queue.php?screen_id=1&region=MainStreet<br>
  d) Script will check it's local storage space.  If there is less than 100MB remaining, it will do a little clean-up by removing the most recently accessed files (thinking being these will not be called again soon). So that storage gets to 200MB.<br>
  e) Script looks at JSON response and does 1 of 3 things:  <br>
    1) It already has the file that is to be dispalyed on it's local storage.  All Set!  Enqueue localhost URL in MYSQL Database.<br>
    2) If it does not have the file it will make a call to the "/find_media?media_id=someID" script to all the peers<br> that are local to it's network (so peer to peer) to see if any local peers have the file.  If they do, it will use their IP in the URL for enqueueing the media.  Doing this so that we cut down on internet/EC2 transfer volumes.<br>
    3) If the file cannot be found locally, the script will call http://MyEC2instance.com/send_media.php?media_id=someID which will send the requested file down to be stored locally.<br>
  f) Script puts information about the media in the local MySQL database so that local get_media script can read it and serve it to the screen<br>
  g) Script makes a curl call to a public URL like http://MyEC2instance.com/confirm_media_queue.php?screen_id=1&region=MainStreet and POSTS json data that confirms which media it successfully registered.  This way if something goes haywire we'll get a resend<br>
  ** If this script cannot make any network calls, local or over the internet it should revert to showing what it already has stored on it's local drive.<br><br>
    

  
<h1>Files on <b>EC2 public instance</b></h1>
<b>send_media_queue.php</b> A scripts that reads a MySQL table that keeps track of what images have been sent to which screen and what is being stored on S3.  Based on requested configuration this script will return the next X hours (or X files: configrable in get_media.php) to display as a JSON document.<br><br>

<b>send_media.php</b> A script that pulls a single resource off S3 and sends it down to a server.  Called like: http://MyEC2instance.com/send_media.php?media_id=someID<br><br>

<b>confirm_media_queue.php</b> A script that receives JSON data and updates the status of media queue items such that the host knows they were received and logged correctly.<br><br>


<b>sync_media.php</b> A script that pulls the full media list off S3 and makes sure that the list in the MySQL (media.media_files) is current.  Does adds and deletes.  Once the sync is complete this job should looks to see if we need to re-randomize the list of media.  Randomization will happen by updating a column with a random number and then sorting on that column.  Only after all media has been displayed once will re-randomization happen (I hate it when randomizers "pick favorites" and this is a good way to avoid that, media_display.js also can reset the media randomizer).  This script will be scheduled to run once per day.<br><br>

<b>find_peers.php</b> A script that registers new regions and computers (if it has not seen what is being passed in) and then returns JSON data that is the information for all the peers in the same "region".  Regions and computers will be stored in a SimpleDB table or some other cloud persistent storage.
<br><br>
<h1>Other Thoughts</h1>
Could add functionality so that there was a key screens used to verify they were legit before media was send.<br>
Need to figure out a way to synchronize video files across several screens 

