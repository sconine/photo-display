// Early Experimentation, got this from a site that was building for RasberryPi
// will credit if kept


var express = require('express')
  , app = express()  
  , server = require('http').createServer(app)
  , path = require('path')
  , io = require('socket.io').listen(server)
  , spawn = require('child_process').spawn

//Socket.io Config
io.set('log level', 1);

// all environments
app.set('port', process.env.TEST_PORT || 8080);
app.use(express.favicon());
app.use(express.logger('dev'));
app.use(express.bodyParser());
app.use(express.methodOverride());
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










