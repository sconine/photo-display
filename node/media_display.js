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
app.get('/slides', function (req, res) {
	res.sendfile(__dirname + '/public/slideshow.html');
});



// get_media function
app.get('/get_media', function (req, res) {
	res.set('Content-Type', 'application/json');
	// Check Local Screen settings
	var sql = "SELECT setting_name, setting_value FROM my_settings";
	connection.query(sql, function(err, rows, fields) {
		var media_id = 0;
		var msettings = {};
		if (err) {
			res.json({ media_type: 'get_settings_error', media_url: err});
		} else {
			if (rows.length > 0) {
				// Get settings to sent back
				for (var i=0;i<rows.length;i++) {
					msettings[rows[i]['setting_name']] = rows[i]['setting_value'];
				}	
			}
	
			// Get the next item to display
			// you might need to run get_media.php first to build this table
			// order by media_id if you want the order sent by the server, media_order if you want random from client
			// TODO: remove movie/quicktime clause after debugging media_type IN ('movie/quicktime', 'movie/mp4') AND
			var sql = "SELECT media_path, media_type, media_host, media_id FROM my_media WHERE media_type IN (' image/jpeg', 'image/gif', 'image/png') AND media_displayed is NULL ORDER BY media_id LIMIT 1";	
			get_media(req, res, sql, 0, msettings);
		}
	});

});


// check if a media file exists locally
app.get('/find_media', function (req, res) {
	res.set('Content-Type', 'application/json');

	var fs = require('fs');
	// TODO: check if config.media_folder ends with a slash or not
	var file_path = config.media_folder + req.query.media_path;
	console.log('find_media: ' + file_path);
	fs.exists(file_path, function(exists) {
		// disk remaining might do something with in the future 
		if (exists) {
			fs.stat(file_path, function (err, stats) {
				if (err) {
					res.json({ found: false, disk_remaining: 0, file_path: file_path, file_size: 0});
				} else {
					res.json({ found: true, disk_remaining: 0, file_path: file_path, file_size: stats.size});
				}
			});
		} else {
			res.json({ found: false, disk_remaining: 0, file_path: file_path, file_size: 0});
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

// Function to get media to display from the database
function get_media(req, res, sql, cnt, settings) {
	connection.query(sql, function(err, rows, fields) {
		var media_id = 0;	
		if (err) {
			res.json({ media_type: 'text', media_url: err});
		} else {
			if (rows.length > 0) {
			  	res.json({ media_path: rows[0].media_path, media_type: rows[0].media_type, media_host: rows[0].media_host, media_settings: settings });
				media_id = rows[0].media_id;
				connection.query('UPDATE my_media SET media_displayed=NOW() WHERE media_id=' + media_id, function(err, rows, fields) {
				  	if (err) {
						console.log({ media_type: 'text', media_url: err});
				  	} else {
						console.log('media_id ' + media_id + ' marked as displayed cnt: ' + cnt);
				  	}
				});
			} else {
				if (cnt == 0) {
					// Try calling again and just getting the oldest image
					get_media(req, res, "SELECT media_path, media_type, media_host, media_id FROM my_media ORDER BY media_displayed LIMIT 1", 1, settings);
				} else {
					res.json({ media_type: 'text', media_url: 'could not redisplay oldest'});
				}
			}
		}	
	});
}







