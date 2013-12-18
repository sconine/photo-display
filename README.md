photo-display
=============

Building a system which will display photos and videos on remote screens via a browser.  

Prototype model idea is this:

1) Buy a chrome box (or some similar cheep, open source computer)
2) install node.js and chrome on it 
3) Configure it so that when it starts:
  a) it boots chrome up in Kiosk mode to a specific URL like: http://localhost:8080/slideshow.html
  b) also boots up a node.js server like: >node media-display.js
  
4) slideshow.html with be a basic HTML script with Javascript in it that
  a) makes a call to a URL like http://localhost:8080/get_media which will return JSON data that indicates what image/video to show next as well as how long to show that media befor emaking a new call to the same URL
  b) with the JSON data it will figure out what media to show next and display the media on the browser screen in a full-screen type mode.  URLs for the media it references will also be to the localhost, and might look something like this: http://localhost:8080/public/images/bucket/folder/image_name.jpg
  
5) media-display.js is the logic for the node.js server that is handling localhost:8080 requests.
  a) For anything in the "public" folder it should just hand back static content
  b) Requests for 'get_media" will return JSON data that specifies the next media to display
  
  need to keep flushing this out...
