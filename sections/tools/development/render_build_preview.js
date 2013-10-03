var page = require('webpage').create();
var system = require('system');
var fs = require('fs');
var returnStatus = {};
var rootPath = system.args[1];
var staticPath = system.args[2];
var toolsMiscPath = system.args[4];

// Check if all paths are accessible
// We assume at least read rights on all paths and files
if (!fs.isDirectory(rootPath) || !fs.isDirectory(rootPath + '/' + staticPath) || !fs.isDirectory(rootPath + '/' + staticPath + 'styles/' + system.args[3] + '/') || !fs.isDirectory(toolsMiscPath)) {
	// Incorrect paths, are they passed correctly?
	returnStatus.status = -1;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
}
fs.changeWorkingDirectory(toolsMiscPath);
if (!fs.exists('render_base.html')) {
	// Rendering base doesn't exist, who broke things?
	returnStatus.status = -2;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
}

page.open('render_base.html', function () {
	// Fixed view size
	page.viewportSize = {
		width: 1200,
		height: 1000
	};
	// Switch to specific stylesheet subdirectory
	fs.changeWorkingDirectory(rootPath + '/' + staticPath + 'styles/' + system.args[3] + '/');
	if (!fs.isWritable(fs.workingDirectory)) {
		// Don't have write access.
		returnStatus.status = -3;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	fs.write('preview.html', page.content, 'w');
	if (!fs.isFile('preview.html')) {
		// Failed to store specific preview file.
		returnStatus.status = -4;
		console.log(JSON.stringify(returnStatus));
		phantom.exit();
	}
	page.close();
	returnStatus.status = 0;
	console.log(JSON.stringify(returnStatus));
	phantom.exit();
});
