// Using this as a resource: http://blog.donaldderek.com/2013/06/build-your-own-google-tv-using-raspberrypi-nodejs-and-socket-io/
//
// This starts the server side node.js server that screens and remote 
// communicate with 

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

//Routes
app.get('/', function (req, res) {
	res.sendfile(__dirname + '/public/index.html');
});

app.get('/remote', function (req, res) {
	res.sendfile(__dirname + '/public/remote.html');
});

server.listen(app.get('port'), function(){
	console.log('Express server listening on port ' + app.get('port'));
});

io.sockets.on('connection', function (socket) {
    socket.emit('message', { message: 'welcome to the chat' });
    socket.on('send', function (data) {
        	//Emit to all
		io.sockets.emit('message', data);
	});
});


var ss;
io.sockets.on('connection', function (socket) {

 socket.on("screen", function(data){
   socket.type = "screen";
   //Save the screen socket
   ss = socket;
   console.log("Screen ready...");
 });

 socket.on("remote", function(data){
   socket.type = "remote";
   console.log("Remote ready...");
   if(ss != undefined){
      console.log("Synced...");
   }
 });

 socket.on("blah", function(data){
	console.log("Got Blah");
 });



});










