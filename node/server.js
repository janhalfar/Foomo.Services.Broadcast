var net = require('net');
var inSockets = [];
var clientInfos = {};
var connectionCounter = 0;
var settings = {
	broadcastServer: {address: '127.0.0.1', port:8080},
	controlServer: {address: '127.0.0.1', port:8765}
};

// read incoming cli args
(function(settings) {
	var serverPortSplitter = function(serverPort) {
		parts = serverPort.split(':');
		if(parts.length != 2) {
			throw new Error('invalid serverPort setting :' + serverPort);
		}
		return {address: parts[0], port: parts[1]};
	};
	// is it a call for help
	if(process.argv.length == 4) {
		// we are good to go
		settings.controlServer = serverPortSplitter(process.argv[2]);
		settings.broadcastServer = serverPortSplitter(process.argv[3]);
	} else {
		//if(['help', '-help', '--help', '-h'].indexOf(process.argv[2]) > -1) {
		console.error('usage : ' + process.argv[0] + ' ' + process.argv[1] + ' controlserver:port broadcastserver:port');
		process.exit(1);
	}

	console.log('settings: ' + JSON.stringify(settings));
})(settings);


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


var broadcastServer = net.createServer(function(socket) {
    inSockets.push(socket);
    socket.setNoDelay(true);
    // initializing connection
    socket.on('data', function(data) {
        var clientInfo = utils.getClientInfoFromInSocket(socket);
        try {
            clientInfo.clientData = JSON.parse(data);
            clientInfo.initialized = true;
            console.log('a new client as connected : ' + JSON.stringify(clientInfo.clientData));
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
        // delete inSockets[inSockets.indexOf(socket)]; <-- you have got to be kidding me !!!
        var activeSockets = [];
        for(var i=0;i<inSockets.length;i++) {
            if(socket !== inSockets[i]) {
                activeSockets.push(inSockets[i]);
            }
        }
        inSockets = activeSockets;
    });
});



// this is where the php foomo server connects
ControlServer = function() {
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
            }
    };
    var methods = {
        broadcast: function(data) {
            var stats = {
                AMF0: 0,
                json: 0,
                error: 0
            };
            for (var i = 0; i < inSockets.length; i++) {
                var socket = inSockets[i];
                var clientInfo = utils.getClientInfoFromInSocket(socket);
                if(clientInfo.initialized && data.hasOwnProperty(clientInfo.clientData.client.dataFormat)) {
					console.log('broadcasting to: ' + JSON.stringify(clientInfo.clientData.sessionData));
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
									//console.log('amf length: ' + broadcastBuffer.length);
									break;
								case 'base64':
									broadcastBuffer = new Buffer(data[clientInfo.clientData.client.dataFormat], 'binary');
									//console.log('base64 length: ' + broadcastBuffer.length);
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
                    console.log('ignoring i	nvalid command');
                }
            }
        };
    });
};
var ControlServer = new ControlServer();

ControlServer.socketServer.listen(
	settings.controlServer.port, 
	settings.controlServer.address
);
broadcastServer.listen(
	settings.broadcastServer.port,
	settings.broadcastServer.address
);
