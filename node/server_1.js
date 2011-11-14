var net = require('net');
var inSockets = [];
var inWebSockets = [];
var clientInfos = {};
var connectionCounter = 0;
var http = require('http');
var settings = {
	socketAddress : '127.0.0.1',
	flashPort : 8081,
	webSocketPort : 8082
};

// command line parameters

if(process.argv.length > 2) {
	if(['help', '-help', '--help', '-h'].indexOf(process.argv[2]) > -1) {
		console.error('usage : ' + process.argv[0] + ' ' + process.argv[1] + ' serverAddress flashPort webSocketPort');
		process.exit(0);
	}
	settings.socketAddress = process.argv[2];
}

if(process.argv.length > 3) {
	settings.flashPort = process.argv[3];
}

if(process.argv.length > 4) {
	settings.webSocketPort = process.argv[4];
}

console.log('trying to start with settings: ' + JSON.stringify(settings));

// this one manages clients
var utils = {
    getClientInfoFromInSocket: function(inSocket) {
        if(!inSocket.hasOwnProperty('_myClientId')) {
            connectionCounter ++;
            inSocket._myClientId = 'client_' + connectionCounter; 
            var timestamp = new Date().getTime();
            clientInfos[inSocket._myClientId] = {id: inSocket._myClientId, initialized:false, firstConnect: timestamp, lastConnect: timestamp, counter:0};
        }
        return clientInfos[inSocket._myClientId];
    }
};


var io = require('/Users/jan/node_modules/socket.io').listen(settings.webSocketPort);

io.sockets.on('connection', function (socket) {
    
    inWebSockets.push(socket);
	console.log('new connection : ' + socket);
    var clientInfo = utils.getClientInfoFromInSocket(socket);
    clientInfo.initialized = true;
    
    socket.emit('welcome', {clientId: socket._myClientId});
});

io.sockets.on('close', function (socket) {
	console.log('websocket client ' + socket._myClientId + ' waves good bye');
    if(socket._myClientId) {
        delete clientInfos[socket._myClientId];
    }
	inWebSockets = inWebSockets.filter(function(el) { return el !== socket; });
});

var inServer = net.createServer(function(socket) {
    inSockets.push(socket);
    socket.setNoDelay(true);
    // initializing connection
    socket.on('data', function(data) {
        var clientInfo = utils.getClientInfoFromInSocket(socket);
        try {
            clientInfo.clientData = JSON.parse(data);
            clientInfo.initialized = true;
            console.log(' data parsed');
        } catch(parseError) {
            // hack for web sockets very ugly
            clientInfo.clientData = {client:{type:'browser', dataFormat: 'json'}};
            clientInfo.initialized = true;

            console.log('could not parse ' + data + '(' + data + ')');
        }
        // console.log('incoming data', clientInfo.clientData);
    });
    // clean up active sockets
    socket.on('close', function() {
        // delete from the clientInfos
        if(socket._myClientId) {
            delete clientInfos[socket._myClientId];
        }
        // clean up inSockets
		inSockets = inSockets.filter(function(el) { return el !== socket; });
    });
});



// this is where the php foomo server connects
OutServer = function() {
    var respond = function(socket, response, raw) {
            if(socket.hasOwnProperty('_myClientId')) {
                var clientInfo = utils.getClientInfoFromInSocket(socket);
                clientInfo.counter ++;
                clientInfo.lastConnect = new Date().getTime();
            }
            if(raw) {
                socket.write(response);
            } else {
                socket.write(JSON.stringify(response));
                // hack
                //socket.emit('event', response);
            }
    };
    var methods = {
        broadcast: function(data) {
            var stats = {
                AMF0: 0,
                json: 0,
                error: 0
            };
            var i;
            var clientInfo;
            for(i = 0; i < inWebSockets.length; i++) {
                var webSocket = inWebSockets[i];
                clientInfo = utils.getClientInfoFromInSocket(webSocket);
                //webSocket.emit('event', data[clientInfo.clientData.client.dataFormat]);
            }
            for (i = 0; i < inSockets.length; i++) {
                var socket = inSockets[i];
                clientInfo = utils.getClientInfoFromInSocket(socket);
                if(clientInfo.initialized && data.hasOwnProperty(clientInfo.clientData.client.dataFormat)) {
                    // console.log('sending: ' + clientInfo.clientData.client.dataFormat,  data[clientInfo.clientData.client.dataFormat]);
                    var broadcastData;
                    switch(clientInfo.clientData.client.dataFormat) {
                        case 'AMF3':
                        case 'AMF0':
							// extract the base64 encoded payloaded and base64 decode it
							var broadcastBuffer;
							var headerBuffer = new Buffer(4);
							switch('binary') {
								case 'binary':
									broadcastBuffer = new Buffer(data[clientInfo.clientData.client.dataFormat], 'base64');
									// i guess we should use an endianess from the client
									console.log('amf length: ' + broadcastBuffer.length);
									break;
								case 'base64':
									broadcastBuffer = new Buffer(data[clientInfo.clientData.client.dataFormat], 'binary');
									console.log('base64 length: ' + broadcastBuffer.length);
									break;
							}
							headerBuffer.writeUInt32BE(broadcastBuffer.length, 0);
							socket.write(headerBuffer);
							socket.write(broadcastBuffer);
							console.log(headerBuffer);
                            break;
                        default:
                            broadcastData = data[clientInfo.clientData.client.dataFormat];
							respond(socket, broadcastData);
                    }
                    //respond(socket, broadcastData, true);
                    stats[clientInfo.clientData.client.dataFormat] ++;
                } else {
                    console.log('can not serve ' + clientInfo.clientData.client.dataFormat + ' to ' + clientInfo.id);
                    stats.error ++;
                }
                
            }
            return stats;
        },
        status: function() {},
        getClients: function() {
            return clientInfos;
        }
        
    };
    this.socketServer = net.createServer(function(socket) {
        var buffer;
        socket.on('data', function(data) {
            // console.log('-- data ' + data.toString().length + ' -------------------------------');
            var newBuffer;
            if(!buffer) {
                // create a freh buffer
                buffer = new Buffer(data, 'utf8');
            } else {
                // append to existing
                newBuffer = new Buffer(buffer.length + data.length);
                buffer.copy(newBuffer, 0, 0);
                data.copy(newBuffer, buffer.length, 0);
                buffer = newBuffer;
            }
            // scan for delimiter String.fromCharCode(0)
            for(var i=0;i<buffer.length;i++) {
                var chr = buffer[i];
                // console.log(String.fromCharCode(char) + ' : ' + char);// + ' ' + char.charCodeAt(0));
                if(chr === 0) {
                    var command;
                    // extract the command
                    var commandBuffer = new Buffer(buffer.toString('utf8', 0, i));//, 'base64');
                    // save the rest
                    buffer = new Buffer(buffer.toString('utf8', i+1));
                    try {
                        var commandJSON = commandBuffer.toString('utf8');
                        command = JSON.parse(commandJSON);
                        // console.log('parsed command:', command);
                        handleCommand(command);
                    } catch (parseErr) {
                        respond(socket, 'wtf: ' + parseErr);
                        console.log('that was no JSON dude err:' + parseErr);
                        console.log(buffer);
                        console.log('start : ' + buffer.toString().substr(0,10));
                        console.log('end   : ' + buffer.toString().substr(-10,10));
                    }
                    return;
                }
            }
        });
        var handleCommand = function(command) {
            if (command && 'object' == typeof command) {
                if (command.hasOwnProperty('method') && command.hasOwnProperty('args')) {
                    if (methods.hasOwnProperty(command.method)) {
                        // console.log('gonna execute method ' + command.method);
                        var response;
                        try {
                            response = methods[command.method].apply(this, command.args);
                            // console.log('got a response ', response);
                        } catch(execErr) {
                            console.log('got an execution error: ' + execErr, command);
                        }
                        if (response) {
                            respond(socket, response, false);
                        }
                    } else {
                        console.log('unknown method: ' + command.method);
                    }
                } else {
                    console.log('ignoring invalid command');
                }
            }
        };
    });
};

var outServer = new OutServer();

// server socket
outServer.socketServer.listen(8765, "127.0.0.1");

// flash sockets
inServer.listen(8081, settings.socketAddress);

// web sockets
//inWebSocketServer.listen(settings.webSocketPort, settings.socketAddress);
