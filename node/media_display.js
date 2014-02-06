// Using this as a resource: http://blog.donaldderek.com/2013/06/build-your-own-google-tv-using-raspberrypi-nodejs-and-socket-io/
//
// This starts the server side node.js server that screens and remote 
// communicate with 
//
// uses express node.js npm checkout API docs here: http://expressjs.com/api.html
// uses express node-mysql npm checkout here: https://npmjs.org/package/mysql

// load the config file for this local slideshow server
// I copy config.json to master_config.json so that I can modify on local server w/out 
// checking back into git by accident...
var config = require('../master_config.json');

// Setup node server 
var express = require('express')
  , app = express()  
  , server = require('http').createServer(app)
  , path = require('path')
  , io = require('socket.io').listen(server)
  , spawn = require('child_process').spawn

//Socket.io Config
io.set('log level', 1);

// all environments
app.set('port', process.env.PHOTO_DISPLAY_PORT || 8080);
app.use(express.favicon());
app.use(express.logger('dev'));
app.use(express.bodyParser());
app.use(express.methodOverride());
// Create the public folder for static content (thie is where Media will be served from)
app.use(express.static(path.join(__dirname, 'public')));

// get a connection to the local MySQL instance
var mysql      = require('mysql');
var connection = mysql.createConnection({
  host     : config.mysql.host,
  user     : config.mysql.user,
  password : config.mysql.password, 
  database : config.mysql.database
});
connection.connect();

//Routes
// default page is the slideshow screen driver
app.get('/', function (req, res) {
	res.sendfile(__dirname + '/public/slideshow.html');
});



// get_media function
app.get('/get_media', function (req, res) {
	res.set('Content-Type', 'application/json');
	
	// Get the next item to display
	// you might need to run get_media.php first to build this table
	connection.query('SELECT media_path, media_type, media_host FROM my_media WHERE media_displayed is NULL ORDER BY media_order LIMIT 1', function(err, rows, fields) {
	  if (err) { res.json({ media_type: 'text', media_url: err});}
	  else {
		if (rows.length > 0) {
		  	res.json({ media_path: rows[0].media_path, media_type: rows[0].media_type, media_host: rows[0].media_host });
		} else {
			res.json({ media_type: 'text', media_url: 'no rows returned'});
		}
	  }	
	});	
});


// check if a media file exists locally
app.get('/find_media', function (req, res) {
	res.set('Content-Type', 'application/json');
	
	var fs = require('fs');
	// TODO: check if config.media_folder ends with a slash or not
	var file_path = config.media_folder + req.query.media_path;
	fs.exists(file_path, function(exists) {
	  // might do something with in the future 
	  if (exists) {
	    res.json({ found: true, disk_remaining: 0, file_path: file_path});
	  } else {
	    res.json({ found: false, disk_remaining: 0, file_path: file_path});
	  }
	});
});


// Stub for future remote control interface
app.get('/remote', function (req, res) {
	res.sendfile(__dirname + '/public/remote.html');
});

// start this thing up
server.listen(app.get('port'), function(){
	console.log('Express server listening on port ' + app.get('port'));
});

// The stuff below really only matters if we want to implement
// status monitoring of screens and the abiliy to fairly real-time
// remote control them
var ss = {};
io.sockets.on('connection', function (socket) {

 socket.on("screen", function(data){
   socket.type = "screen";
   //Save the screen socket: Want this to support
   // many screens.  data should send the Screen ID that is registering
   // we'll need to do a little work on this, basica concept is to 
   // maintain an array of screens identified by screen ID
   ss[data] = socket;
   console.log("Screen ready...");
 });

 socket.on("remote", function(data){
   socket.type = "remote";
   console.log("Remote ready...");
   
   // This isn't really relevant to me as a remote can manage many screens
   //if(ss[data] != undefined){
   //   console.log("Synced...");
   //}
 });

 // My version of a hello world to make sure thsi thing is working
 // like I think it does 
 socket.on("blah", function(data){
	console.log("Got Blah");
 });



});










