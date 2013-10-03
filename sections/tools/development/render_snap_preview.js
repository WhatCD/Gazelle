var page = require('webpage').create();
var system = require('system');
var fs = require('fs');
var returnStatus = {};
var rootPath = system.args[1];
var staticPath = system.args[2];

// Check if all paths are accessible
// We assume at least read rights on all paths and files
if (!fs.isDirectory(rootPath) || !fs.isDirectory(rootPath + '/' + staticPath) || !fs.isDirectory(rootPath + '/' + staticPath + 'styles/' + system.args[3] + '/') || !fs.isDirectory(rootPath + '/' + staticPath + '/stylespreview')) {
	//Incorrect paths, are they passed correctly?
	returnStatus.status = -1;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
}
// Switch to given stylesheet directory
fs.changeWorkingDirectory(rootPath + '/' + staticPath + 'styles/' + system.args[3] + '/');
if (!fs.exists('preview.html')) {
	// Preview file doesn't exist. Running things in the wrong order perhaps?
	returnStatus.status = -2;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
}
// Open the file, start working.
page.open('preview.html', function () {
	if (page.framePlainText == "") {
		// Preview is empty. Did it get created properly?
		returnStatus.status = -3;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	page.viewportSize = {
		width: 1200,
		height: 800
	};
	// Save files to static
	fs.changeWorkingDirectory(rootPath + '/' + staticPath + '/stylespreview');
	if (!fs.isWritable(fs.workingDirectory)) {
		// Don't have write access.
		returnStatus.status = -4;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	page.render('full_' + system.args[3] + '.png');
	if (!fs.isFile('full_' + system.args[3] + '.png')) {
		// Failed to store full image.
		returnStatus.status = -5;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	// Remove temp files
	fs.changeWorkingDirectory(rootPath + '/' + staticPath + 'styles/' + system.args[3] + '/');
	if (!fs.isFile('preview.html') || !fs.isWritable('preview.html')) {
		// Can't find temp file to remove. Are the paths correct?
		returnStatus.status = -6;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	fs.remove('preview.html');
	// All good and done
	page.close();
	returnStatus.status = 0;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
});
