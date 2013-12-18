photo-display
=============

Building a system which will display photos and videos on remote screens via a browser.  

Prototype model idea is this:

1) Buy a chrome box (or some similar cheep, open source computer)
2) install node.js and chrome on it 
3) Configure it so that when it starts:
  a) it boots chrome up in Kiosk mode to a specific URL like: http://localhost:8080/slideshow.html
  b) also boots up a node.js server like: >node media-display.js


Behind the scene are these parts
---------------------------------

Files on <b>localhost</b>
<b>slideshow.html</b> with be a basic HTML script with Javascript in it that:
  a) makes a call to a URL like http://localhost:8080/get_media which will return JSON data that indicates what image/video to show next as well as how long to show that media before making a new call to the same URL
  b) with the JSON data it will figure out what media to show next and display the media on the browser screen in a full-screen type mode.  URLs for the media it references will also be to the localhost, and might look something like this: http://localhost:8080/public/images/bucket/folder/image_name.jpg (though might be the IP of a host that is local)
  
<b>media-display.js</b> is the logic for the node.js server that is handling localhost:8080 requests.
  a) For anything in the "public" folder it should just hand back static content
  b) Requests for 'get_media" will return JSON data that specifies the next media to display.  I think this data will likely come from a queue of some sort (probably a table in MySQL - See below for how this might get populated).  The media that is specified in the JSON data should be confirmed as "on disk" of the localhost before it is sent to the client.
  
<B>get_media.php</b> this is a php script that will talk to the main EC2 server which returns which media a screen should enqueue.  This might work as follows:
  a) Every 10 minutes a cron job kicks this script off, script makes sure it is not already running
  b) Script makes a curl call to a public URL like http://MyEC2instance.com/send_media_queue.php?screen_id=1
  c) Script will check it's local storage space.  If there is less than say 100MB remaining, it will do a little clean-up be removing the most recently accessed files (thinking being these will not be called again soon). So that storage gets to 200MB.
  d) Script looks JSON and do 1 of 3 things:  
    1) It already has the file that is to be dispalyed on it's local storage.  All Set!  Send localhost URL.
    2) If it does not have the file it will make a call to the "find_media?media_id=someID" script to all the servers that are local to it's network (so peer to peer essentially).  To see if any local peers have the file.  If they do it will use their IP in the URL for the media. Doing this so that we cut down on internet/EC2 transfer volumes.
    3) If the file cannot be found locally, the script will call http://MyEC2instance.com/send_media.php?media_id=someID which will send the requested file down to be stored locally.
  e) Script puts information about the media in the local MySQL database so that local get_media script can read it and serve it to the screen
    
<b>find_media</b> - a node handled js script that looks for media on the local hard drive.
  a) This should return true/false, the IP to use in the URL and the space remaining on the local drive (in theory we could later use this information to better stripe data across all locally available peers).
  
  
  
Files on <b>EC2 public instance</b>
<b>send_media_queue.php</b> on the EC2 instance this will read a SimpleDB (or Dynamo or something persistent in the cloud) table that keeps track of what images have been sent to which screen.  Based on requested configuration (need to flush that out) this script will return the next X hours (or X files) to display as a JSON document.



<b>sync_media.php</b> a script on the server that pulls the full media list off S3 and makes sure that the list in the SimpleDB is current.  Does adds and deletes only.  Once the sync is complete this job should also look to see if we need to re-randomize the list of media.  Randomization will happen by updating a column with a random number and then sorting on that column.  Only after all media has been displayed once will re-randomization happen (I hate it when randomizers "pick favorites" and this is a good way to avoid that).

  
  
  
  
  
  need to keep flushing this out...
