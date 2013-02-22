//Copyright (C) 2008 Apple Inc. All Rights Reserved.

function Transitions () {
  // callback for the first batch of operation, where we set the default properties
  // for the transition (transition-property and transition-duration) as well as
  // the "from" property value if explicitely passed as a param to .add()
  this.instantOperations = new Function ();
  // callback for the second batch of operation, where we set the "to" property value
  this.deferredOperations = new Function ();
};

// Core defaults for the transitions, you can update these members so that all
// calls to .add() from that point on use this duration and set of properties
Transitions.DEFAULTS = {
  duration : 1,	// default to 1 second
  properties : []
};


/*
 Adds a CSS transition, parameters are :

 element:	 target element for transition
 duration:	duration for all transitions in seconds
 properties:  the properties that are transitioned (will be fed to '-webkit-transition-property')
 from:		optional list of initial property values to match properties passed as .properties
 to:		  list of final property values to match properties passed as .properties

 The .duration and .properties parameters are optional and can be defined once for
 all upcoming transitions by over-riding the Transition.DEFAULTS properties

 Some operations need to be deferred so that the styles are currently set for the from state
 of from / to operations

 */

Transitions.prototype.add = function (params) {
  var style = params.element.style;
  // set up properties
  var properties = (params.properties) ? params.properties : Transitions.DEFAULTS.properties;
  // set up durations
  var duration = ((params.duration) ? params.duration : Transitions.DEFAULTS.duration) + 's';
  var durations = [];
  for (var i = 0; i < properties.length; i++) {
	durations.push(duration);
  }
  // from/to animation
  if (params.from) {
	this.addInstantOperation(function () {
	  style.webkitTransitionProperty = 'none';
	  for (var i = 0; i < properties.length; i++) {
		style.setProperty(properties[i], params.from[i], '');
	  }
	});
	this.addDeferredOperation(function () {
	  style.webkitTransitionProperty = properties.join(', ');
	  style.webkitTransitionDuration = durations.join(', ');
	  for (var i = 0; i < properties.length; i++) {
		style.setProperty(properties[i], params.to[i], '');
	  }
	});
  }
  // to-only animation
  else {
	this.addDeferredOperation(function () {
	  style.webkitTransitionProperty = properties.join(', ');
	  style.webkitTransitionDuration = durations.join(', ');
	  for (var i = 0; i < properties.length; i++) {
		style.setProperty(properties[i], params.to[i], '');
	  }
	});
  }
};

// adds a new operation to the set of instant operations
Transitions.prototype.addInstantOperation = function (new_operation) {
  var previousInstantOperations = this.instantOperations;
  this.instantOperations = function () {
	previousInstantOperations();
	new_operation();
  };
};

// adds a new operation to the set of deferred operations
Transitions.prototype.addDeferredOperation = function (new_operation) {
  var previousDeferredOperations = this.deferredOperations;
  this.deferredOperations = function () {
	previousDeferredOperations();
	new_operation();
  };
};

// called in order to launch the current group of transitions
Transitions.prototype.apply = function () {
  this.instantOperations();
  var _this = this;
  setTimeout(_this.deferredOperations, 0);
};
