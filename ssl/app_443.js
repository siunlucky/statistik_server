var fs = require('fs');
var https = require('https');

var privateKey  = fs.readFileSync(__dirname+'/ssl_files/key.pem');
var certificate = fs.readFileSync(__dirname+'/ssl_files/csr.pem');

var credentials = {key: privateKey, cert: certificate};
const express = require('express');
const path = require('path');
const app = express();

app.use(express.static(path.join(__dirname, 'build')));

app.get('/*', function (req, res) {
  res.sendFile(path.join(__dirname, 'build', 'index.html'));
});

var httpsServer = https.createServer(credentials, app);
httpsServer.listen(443);

