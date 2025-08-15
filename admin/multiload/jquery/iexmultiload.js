/**
 * SWFUpload: http://www.swfupload.org, http://swfupload.googlecode.com
 *
 * mmSWFUpload 1.0: Flash upload dialog - http://profandesign.se/swfupload/,  http://www.vinterwebb.se/
 *
 * SWFUpload is (c) 2006-2007 Lars Huring, Olov Nilzйn and Mammon Media and is released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * SWFUpload 2 is (c) 2007-2008 Jake Roberts and is released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 */


/* ******************* */
/* Constructor & Init  */
/* ******************* */
var SWFUpload;

if (SWFUpload == undefined) {
	SWFUpload = function (settings) {
		this.initSWFUpload(settings);
	};
}

SWFUpload.prototype.initSWFUpload = function (settings) {
	try {
		this.customSettings = {};	// A container where developers can place their own settings associated with this instance.
		this.settings = settings;
		this.eventQueue = [];
		this.movieName = "SWFUpload_" + SWFUpload.movieCount++;
		this.movieElement = null;


		// Setup global control tracking
		SWFUpload.instances[this.movieName] = this;

		// Load the settings.  Load the Flash movie.
		this.initSettings();
		this.loadFlash();
		this.displayDebugInfo();
	} catch (ex) {
		delete SWFUpload.instances[this.movieName];
		throw ex;
	}
};

/* *************** */
/* Static Members  */
/* *************** */
SWFUpload.instances = {};
SWFUpload.movieCount = 0;
SWFUpload.version = "2.2.0 2009-03-25";
SWFUpload.QUEUE_ERROR = {
	QUEUE_LIMIT_EXCEEDED	  		: -100,
	FILE_EXCEEDS_SIZE_LIMIT  		: -110,
	ZERO_BYTE_FILE			  		: -120,
	INVALID_FILETYPE		  		: -130
};
SWFUpload.UPLOAD_ERROR = {
	HTTP_ERROR				  		: -200,
	MISSING_UPLOAD_URL	      		: -210,
	IO_ERROR				  		: -220,
	SECURITY_ERROR			  		: -230,
	UPLOAD_LIMIT_EXCEEDED	  		: -240,
	UPLOAD_FAILED			  		: -250,
	SPECIFIED_FILE_ID_NOT_FOUND		: -260,
	FILE_VALIDATION_FAILED	  		: -270,
	FILE_CANCELLED			  		: -280,
	UPLOAD_STOPPED					: -290
};
SWFUpload.FILE_STATUS = {
	QUEUED		 : -1,
	IN_PROGRESS	 : -2,
	ERROR		 : -3,
	COMPLETE	 : -4,
	CANCELLED	 : -5
};
SWFUpload.BUTTON_ACTION = {
	SELECT_FILE  : -100,
	SELECT_FILES : -110,
	START_UPLOAD : -120
};
SWFUpload.CURSOR = {
	ARROW : -1,
	HAND : -2
};
SWFUpload.WINDOW_MODE = {
	WINDOW : "window",
	TRANSPARENT : "transparent",
	OPAQUE : "opaque"
};

// Private: takes a URL, determines if it is relative and converts to an absolute URL
// using the current site. Only processes the URL if it can, otherwise returns the URL untouched
SWFUpload.completeURL = function(url) {
	if (typeof(url) !== "string" || url.match(/^https?:\/\//i) || url.match(/^\//)) {
		return url;
	}
	
	var currentURL = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ":" + window.location.port : "");
	
	var indexSlash = window.location.pathname.lastIndexOf("/");
	if (indexSlash <= 0) {
		path = "/";
	} else {
		path = window.location.pathname.substr(0, indexSlash) + "/";
	}
	
	return /*currentURL +*/ path + url;
	
};


/* ******************** */
/* Instance Members  */
/* ******************** */

// Private: initSettings ensures that all the
// settings are set, getting a default value if one was not assigned.
SWFUpload.prototype.initSettings = function () {
	this.ensureDefault = function (settingName, defaultValue) {
		this.settings[settingName] = (this.settings[settingName] == undefined) ? defaultValue : this.settings[settingName];
	};
	
	// Upload backend settings
	this.ensureDefault("upload_url", "");
	this.ensureDefault("preserve_relative_urls", false);
	this.ensureDefault("file_post_name", "Filedata");
	this.ensureDefault("post_params", {});
	this.ensureDefault("use_query_string", false);
	this.ensureDefault("requeue_on_error", false);
	this.ensureDefault("http_success", []);
	this.ensureDefault("assume_success_timeout", 0);
	
	// File Settings
	this.ensureDefault("file_types", "*.*");
	this.ensureDefault("file_types_description", "All Files");
	this.ensureDefault("file_size_limit", 0);	// Default zero means "unlimited"
	this.ensureDefault("file_upload_limit", 0);
	this.ensureDefault("file_queue_limit", 0);

	// Flash Settings
	this.ensureDefault("flash_url", "swfupload.swf");
	this.ensureDefault("prevent_swf_caching", true);
	
	// Button Settings
	this.ensureDefault("button_image_url", "");
	this.ensureDefault("button_width", 1);
	this.ensureDefault("button_height", 1);
	this.ensureDefault("button_text", "");
	this.ensureDefault("button_text_style", "color: #000000; font-size: 16pt;");
	this.ensureDefault("button_text_top_padding", 0);
	this.ensureDefault("button_text_left_padding", 0);
	this.ensureDefault("button_action", SWFUpload.BUTTON_ACTION.SELECT_FILES);
	this.ensureDefault("button_disabled", false);
	this.ensureDefault("button_placeholder_id", "");
	this.ensureDefault("button_placeholder", null);
	this.ensureDefault("button_cursor", SWFUpload.CURSOR.ARROW);
	this.ensureDefault("button_window_mode", SWFUpload.WINDOW_MODE.WINDOW);
	
	// Debug Settings
	this.ensureDefault("debug", false);
	this.settings.debug_enabled = this.settings.debug;	// Here to maintain v2 API
	
	// Event Handlers
	this.settings.return_upload_start_handler = this.returnUploadStart;
	this.ensureDefault("swfupload_loaded_handler", null);
	this.ensureDefault("file_dialog_start_handler", null);
	this.ensureDefault("file_queued_handler", null);
	this.ensureDefault("file_queue_error_handler", null);
	this.ensureDefault("file_dialog_complete_handler", null);
	
	this.ensureDefault("upload_start_handler", null);
	this.ensureDefault("upload_progress_handler", null);
	this.ensureDefault("upload_error_handler", null);
	this.ensureDefault("upload_success_handler", null);
	this.ensureDefault("upload_complete_handler", null);
	
	this.ensureDefault("debug_handler", this.debugMessage);

	this.ensureDefault("custom_settings", {});

	// Other settings
	this.customSettings = this.settings.custom_settings;
	
	// Update the flash url if needed
	if (!!this.settings.prevent_swf_caching) {
		this.settings.flash_url = this.settings.flash_url + (this.settings.flash_url.indexOf("?") < 0 ? "?" : "&") + "preventswfcaching=" + new Date().getTime();
	}
	
	if (!this.settings.preserve_relative_urls) {
		//this.settings.flash_url = SWFUpload.completeURL(this.settings.flash_url);	// Don't need to do this one since flash doesn't look at it
		this.settings.upload_url = SWFUpload.completeURL(this.settings.upload_url);
		this.settings.button_image_url = SWFUpload.completeURL(this.settings.button_image_url);
	}
	
	delete this.ensureDefault;
};

// Private: loadFlash replaces the button_placeholder element with the flash movie.
SWFUpload.prototype.loadFlash = function () {
	var targetElement, tempParent;

	// Make sure an element with the ID we are going to use doesn't already exist
	if (document.getElementById(this.movieName) !== null) {
		throw "ID " + this.movieName + " is already in use. The Flash Object could not be added";
	}

	// Get the element where we will be placing the flash movie
	targetElement = document.getElementById(this.settings.button_placeholder_id) || this.settings.button_placeholder;

	if (targetElement == undefined) {
		throw "Could not find the placeholder element: " + this.settings.button_placeholder_id;
	}

	// Append the container and load the flash
	tempParent = document.createElement("div");
	tempParent.innerHTML = this.getFlashHTML();	// Using innerHTML is non-standard but the only sensible way to dynamically add Flash in IE (and maybe other browsers)
	targetElement.parentNode.replaceChild(tempParent.firstChild, targetElement);

	// Fix IE Flash/Form bug
	if (window[this.movieName] == undefined) {
		window[this.movieName] = this.getMovieElement();
	}
	
};

// Private: getFlashHTML generates the object tag needed to embed the flash in to the document
SWFUpload.prototype.getFlashHTML = function () {
	// Flash Satay object syntax: http://www.alistapart.com/articles/flashsatay
	return ['<object id="', this.movieName, '" type="application/x-shockwave-flash" data="', this.settings.flash_url, '" width="', this.settings.button_width, '" height="', this.settings.button_height, '" class="swfupload">',
				'<param name="wmode" value="', this.settings.button_window_mode, '" />',
				'<param name="movie" value="', this.settings.flash_url, '" />',
				'<param name="quality" value="high" />',
				'<param name="menu" value="false" />',
				'<param name="allowScriptAccess" value="always" />',
				'<param name="flashvars" value="' + this.getFlashVars() + '" />',
				'</object>'].join("");
};

// Private: getFlashVars builds the parameter string that will be passed
// to flash in the flashvars param.
SWFUpload.prototype.getFlashVars = function () {
	// Build a string from the post param object
	var paramString = this.buildParamString();
	var httpSuccessString = this.settings.http_success.join(",");
	
	// Build the parameter string
	return ["movieName=", encodeURIComponent(this.movieName),
			"&amp;uploadURL=", encodeURIComponent(this.settings.upload_url),
			"&amp;useQueryString=", encodeURIComponent(this.settings.use_query_string),
			"&amp;requeueOnError=", encodeURIComponent(this.settings.requeue_on_error),
			"&amp;httpSuccess=", encodeURIComponent(httpSuccessString),
			"&amp;assumeSuccessTimeout=", encodeURIComponent(this.settings.assume_success_timeout),
			"&amp;params=", encodeURIComponent(paramString),
			"&amp;filePostName=", encodeURIComponent(this.settings.file_post_name),
			"&amp;fileTypes=", encodeURIComponent(this.settings.file_types),
			"&amp;fileTypesDescription=", encodeURIComponent(this.settings.file_types_description),
			"&amp;fileSizeLimit=", encodeURIComponent(this.settings.file_size_limit),
			"&amp;fileUploadLimit=", encodeURIComponent(this.settings.file_upload_limit),
			"&amp;fileQueueLimit=", encodeURIComponent(this.settings.file_queue_limit),
			"&amp;debugEnabled=", encodeURIComponent(this.settings.debug_enabled),
			"&amp;buttonImageURL=", encodeURIComponent(this.settings.button_image_url),
			"&amp;buttonWidth=", encodeURIComponent(this.settings.button_width),
			"&amp;buttonHeight=", encodeURIComponent(this.settings.button_height),
			"&amp;buttonText=", encodeURIComponent(this.settings.button_text),
			"&amp;buttonTextTopPadding=", encodeURIComponent(this.settings.button_text_top_padding),
			"&amp;buttonTextLeftPadding=", encodeURIComponent(this.settings.button_text_left_padding),
			"&amp;buttonTextStyle=", encodeURIComponent(this.settings.button_text_style),
			"&amp;buttonAction=", encodeURIComponent(this.settings.button_action),
			"&amp;buttonDisabled=", encodeURIComponent(this.settings.button_disabled),
			"&amp;buttonCursor=", encodeURIComponent(this.settings.button_cursor)
		].join("");
};

// Public: getMovieElement retrieves the DOM reference to the Flash element added by SWFUpload
// The element is cached after the first lookup
SWFUpload.prototype.getMovieElement = function () {
	if (this.movieElement == undefined) {
		this.movieElement = document.getElementById(this.movieName);
	}

	if (this.movieElement === null) {
		throw "Could not find Flash element";
	}
	
	return this.movieElement;
};

// Private: buildParamString takes the name/value pairs in the post_params setting object
// and joins them up in to a string formatted "name=value&amp;name=value"
SWFUpload.prototype.buildParamString = function () {
	var postParams = this.settings.post_params; 
	var paramStringPairs = [];

	if (typeof(postParams) === "object") {
		for (var name in postParams) {
			if (postParams.hasOwnProperty(name)) {
				paramStringPairs.push(encodeURIComponent(name.toString()) + "=" + encodeURIComponent(postParams[name].toString()));
			}
		}
	}

	return paramStringPairs.join("&amp;");
};

// Public: Used to remove a SWFUpload instance from the page. This method strives to remove
// all references to the SWF, and other objects so memory is properly freed.
// Returns true if everything was destroyed. Returns a false if a failure occurs leaving SWFUpload in an inconsistant state.
// Credits: Major improvements provided by steffen
SWFUpload.prototype.destroy = function () {
	try {
		// Make sure Flash is done before we try to remove it
		this.cancelUpload(null, false);
		

		// Remove the SWFUpload DOM nodes
		var movieElement = null;
		movieElement = this.getMovieElement();
		
		if (movieElement && typeof(movieElement.CallFunction) === "unknown") { // We only want to do this in IE
			// Loop through all the movie's properties and remove all function references (DOM/JS IE 6/7 memory leak workaround)
			for (var i in movieElement) {
				try {
					if (typeof(movieElement[i]) === "function") {
						movieElement[i] = null;
					}
				} catch (ex1) {}
			}

			// Remove the Movie Element from the page
			try {
				movieElement.parentNode.removeChild(movieElement);
			} catch (ex) {}
		}
		
		// Remove IE form fix reference
		window[this.movieName] = null;

		// Destroy other references
		SWFUpload.instances[this.movieName] = null;
		delete SWFUpload.instances[this.movieName];

		this.movieElement = null;
		this.settings = null;
		this.customSettings = null;
		this.eventQueue = null;
		this.movieName = null;
		
		
		return true;
	} catch (ex2) {
		return false;
	}
};


// Public: displayDebugInfo prints out settings and configuration
// information about this SWFUpload instance.
// This function (and any references to it) can be deleted when placing
// SWFUpload in production.
SWFUpload.prototype.displayDebugInfo = function () {
	this.debug(
		[
			"---SWFUpload Instance Info---\n",
			"Version: ", SWFUpload.version, "\n",
			"Movie Name: ", this.movieName, "\n",
			"Settings:\n",
			"\t", "upload_url:               ", this.settings.upload_url, "\n",
			"\t", "flash_url:                ", this.settings.flash_url, "\n",
			"\t", "use_query_string:         ", this.settings.use_query_string.toString(), "\n",
			"\t", "requeue_on_error:         ", this.settings.requeue_on_error.toString(), "\n",
			"\t", "http_success:             ", this.settings.http_success.join(", "), "\n",
			"\t", "assume_success_timeout:   ", this.settings.assume_success_timeout, "\n",
			"\t", "file_post_name:           ", this.settings.file_post_name, "\n",
			"\t", "post_params:              ", this.settings.post_params.toString(), "\n",
			"\t", "file_types:               ", this.settings.file_types, "\n",
			"\t", "file_types_description:   ", this.settings.file_types_description, "\n",
			"\t", "file_size_limit:          ", this.settings.file_size_limit, "\n",
			"\t", "file_upload_limit:        ", this.settings.file_upload_limit, "\n",
			"\t", "file_queue_limit:         ", this.settings.file_queue_limit, "\n",
			"\t", "debug:                    ", this.settings.debug.toString(), "\n",

			"\t", "prevent_swf_caching:      ", this.settings.prevent_swf_caching.toString(), "\n",

			"\t", "button_placeholder_id:    ", this.settings.button_placeholder_id.toString(), "\n",
			"\t", "button_placeholder:       ", (this.settings.button_placeholder ? "Set" : "Not Set"), "\n",
			"\t", "button_image_url:         ", this.settings.button_image_url.toString(), "\n",
			"\t", "button_width:             ", this.settings.button_width.toString(), "\n",
			"\t", "button_height:            ", this.settings.button_height.toString(), "\n",
			"\t", "button_text:              ", this.settings.button_text.toString(), "\n",
			"\t", "button_text_style:        ", this.settings.button_text_style.toString(), "\n",
			"\t", "button_text_top_padding:  ", this.settings.button_text_top_padding.toString(), "\n",
			"\t", "button_text_left_padding: ", this.settings.button_text_left_padding.toString(), "\n",
			"\t", "button_action:            ", this.settings.button_action.toString(), "\n",
			"\t", "button_disabled:          ", this.settings.button_disabled.toString(), "\n",

			"\t", "custom_settings:          ", this.settings.custom_settings.toString(), "\n",
			"Event Handlers:\n",
			"\t", "swfupload_loaded_handler assigned:  ", (typeof this.settings.swfupload_loaded_handler === "function").toString(), "\n",
			"\t", "file_dialog_start_handler assigned: ", (typeof this.settings.file_dialog_start_handler === "function").toString(), "\n",
			"\t", "file_queued_handler assigned:       ", (typeof this.settings.file_queued_handler === "function").toString(), "\n",
			"\t", "file_queue_error_handler assigned:  ", (typeof this.settings.file_queue_error_handler === "function").toString(), "\n",
			"\t", "upload_start_handler assigned:      ", (typeof this.settings.upload_start_handler === "function").toString(), "\n",
			"\t", "upload_progress_handler assigned:   ", (typeof this.settings.upload_progress_handler === "function").toString(), "\n",
			"\t", "upload_error_handler assigned:      ", (typeof this.settings.upload_error_handler === "function").toString(), "\n",
			"\t", "upload_success_handler assigned:    ", (typeof this.settings.upload_success_handler === "function").toString(), "\n",
			"\t", "upload_complete_handler assigned:   ", (typeof this.settings.upload_complete_handler === "function").toString(), "\n",
			"\t", "debug_handler assigned:             ", (typeof this.settings.debug_handler === "function").toString(), "\n"
		].join("")
	);
};

/* Note: addSetting and getSetting are no longer used by SWFUpload but are included
	the maintain v2 API compatibility
*/
// Public: (Deprecated) addSetting adds a setting value. If the value given is undefined or null then the default_value is used.
SWFUpload.prototype.addSetting = function (name, value, default_value) {
    if (value == undefined) {
        return (this.settings[name] = default_value);
    } else {
        return (this.settings[name] = value);
	}
};

// Public: (Deprecated) getSetting gets a setting. Returns an empty string if the setting was not found.
SWFUpload.prototype.getSetting = function (name) {
    if (this.settings[name] != undefined) {
        return this.settings[name];
	}

    return "";
};



// Private: callFlash handles function calls made to the Flash element.
// Calls are made with a setTimeout for some functions to work around
// bugs in the ExternalInterface library.
SWFUpload.prototype.callFlash = function (functionName, argumentArray) {
	argumentArray = argumentArray || [];
	
	var movieElement = this.getMovieElement();
	var returnValue, returnString;

	// Flash's method if calling ExternalInterface methods (code adapted from MooTools).
	try {
		returnString = movieElement.CallFunction('<invoke name="' + functionName + '" returntype="javascript">' + __flash__argumentsToXML(argumentArray, 0) + '</invoke>');
		returnValue = eval(returnString);
	} catch (ex) {
		throw "Call to " + functionName + " failed";
	}
	
	// Unescape file post param values
	if (returnValue != undefined && typeof returnValue.post === "object") {
		returnValue = this.unescapeFilePostParams(returnValue);
	}

	return returnValue;
};

/* *****************************
	-- Flash control methods --
	Your UI should use these
	to operate SWFUpload
   ***************************** */

// WARNING: this function does not work in Flash Player 10
// Public: selectFile causes a File Selection Dialog window to appear.  This
// dialog only allows 1 file to be selected.
SWFUpload.prototype.selectFile = function () {
	this.callFlash("SelectFile");
};

// WARNING: this function does not work in Flash Player 10
// Public: selectFiles causes a File Selection Dialog window to appear/ This
// dialog allows the user to select any number of files
// Flash Bug Warning: Flash limits the number of selectable files based on the combined length of the file names.
// If the selection name length is too long the dialog will fail in an unpredictable manner.  There is no work-around
// for this bug.
SWFUpload.prototype.selectFiles = function () {
	this.callFlash("SelectFiles");
};


// Public: startUpload starts uploading the first file in the queue unless
// the optional parameter 'fileID' specifies the ID 
SWFUpload.prototype.startUpload = function (fileID) {
	this.callFlash("StartUpload", [fileID]);
};

// Public: cancelUpload cancels any queued file.  The fileID parameter may be the file ID or index.
// If you do not specify a fileID the current uploading file or first file in the queue is cancelled.
// If you do not want the uploadError event to trigger you can specify false for the triggerErrorEvent parameter.
SWFUpload.prototype.cancelUpload = function (fileID, triggerErrorEvent) {
	if (triggerErrorEvent !== false) {
		triggerErrorEvent = true;
	}
	this.callFlash("CancelUpload", [fileID, triggerErrorEvent]);
};

// Public: stopUpload stops the current upload and requeues the file at the beginning of the queue.
// If nothing is currently uploading then nothing happens.
SWFUpload.prototype.stopUpload = function () {
	this.callFlash("StopUpload");
};

/* ************************
 * Settings methods
 *   These methods change the SWFUpload settings.
 *   SWFUpload settings should not be changed directly on the settings object
 *   since many of the settings need to be passed to Flash in order to take
 *   effect.
 * *********************** */

// Public: getStats gets the file statistics object.
SWFUpload.prototype.getStats = function () {
	return this.callFlash("GetStats");
};

// Public: setStats changes the SWFUpload statistics.  You shouldn't need to 
// change the statistics but you can.  Changing the statistics does not
// affect SWFUpload accept for the successful_uploads count which is used
// by the upload_limit setting to determine how many files the user may upload.
SWFUpload.prototype.setStats = function (statsObject) {
	this.callFlash("SetStats", [statsObject]);
};

// Public: getFile retrieves a File object by ID or Index.  If the file is
// not found then 'null' is returned.
SWFUpload.prototype.getFile = function (fileID) {
	if (typeof(fileID) === "number") {
		return this.callFlash("GetFileByIndex", [fileID]);
	} else {
		return this.callFlash("GetFile", [fileID]);
	}
};

// Public: addFileParam sets a name/value pair that will be posted with the
// file specified by the Files ID.  If the name already exists then the
// exiting value will be overwritten.
SWFUpload.prototype.addFileParam = function (fileID, name, value) {
	return this.callFlash("AddFileParam", [fileID, name, value]);
};

// Public: removeFileParam removes a previously set (by addFileParam) name/value
// pair from the specified file.
SWFUpload.prototype.removeFileParam = function (fileID, name) {
	this.callFlash("RemoveFileParam", [fileID, name]);
};

// Public: setUploadUrl changes the upload_url setting.
SWFUpload.prototype.setUploadURL = function (url) {
	this.settings.upload_url = url.toString();
	this.callFlash("SetUploadURL", [url]);
};

// Public: setPostParams changes the post_params setting
SWFUpload.prototype.setPostParams = function (paramsObject) {
	this.settings.post_params = paramsObject;
	this.callFlash("SetPostParams", [paramsObject]);
};

// Public: addPostParam adds post name/value pair.  Each name can have only one value.
SWFUpload.prototype.addPostParam = function (name, value) {
	this.settings.post_params[name] = value;
	this.callFlash("SetPostParams", [this.settings.post_params]);
};

// Public: removePostParam deletes post name/value pair.
SWFUpload.prototype.removePostParam = function (name) {
	delete this.settings.post_params[name];
	this.callFlash("SetPostParams", [this.settings.post_params]);
};

// Public: setFileTypes changes the file_types setting and the file_types_description setting
SWFUpload.prototype.setFileTypes = function (types, description) {
	this.settings.file_types = types;
	this.settings.file_types_description = description;
	this.callFlash("SetFileTypes", [types, description]);
};

// Public: setFileSizeLimit changes the file_size_limit setting
SWFUpload.prototype.setFileSizeLimit = function (fileSizeLimit) {
	this.settings.file_size_limit = fileSizeLimit;
	this.callFlash("SetFileSizeLimit", [fileSizeLimit]);
};

// Public: setFileUploadLimit changes the file_upload_limit setting
SWFUpload.prototype.setFileUploadLimit = function (fileUploadLimit) {
	this.settings.file_upload_limit = fileUploadLimit;
	this.callFlash("SetFileUploadLimit", [fileUploadLimit]);
};

// Public: setFileQueueLimit changes the file_queue_limit setting
SWFUpload.prototype.setFileQueueLimit = function (fileQueueLimit) {
	this.settings.file_queue_limit = fileQueueLimit;
	this.callFlash("SetFileQueueLimit", [fileQueueLimit]);
};

// Public: setFilePostName changes the file_post_name setting
SWFUpload.prototype.setFilePostName = function (filePostName) {
	this.settings.file_post_name = filePostName;
	this.callFlash("SetFilePostName", [filePostName]);
};

// Public: setUseQueryString changes the use_query_string setting
SWFUpload.prototype.setUseQueryString = function (useQueryString) {
	this.settings.use_query_string = useQueryString;
	this.callFlash("SetUseQueryString", [useQueryString]);
};

// Public: setRequeueOnError changes the requeue_on_error setting
SWFUpload.prototype.setRequeueOnError = function (requeueOnError) {
	this.settings.requeue_on_error = requeueOnError;
	this.callFlash("SetRequeueOnError", [requeueOnError]);
};

// Public: setHTTPSuccess changes the http_success setting
SWFUpload.prototype.setHTTPSuccess = function (http_status_codes) {
	if (typeof http_status_codes === "string") {
		http_status_codes = http_status_codes.replace(" ", "").split(",");
	}
	
	this.settings.http_success = http_status_codes;
	this.callFlash("SetHTTPSuccess", [http_status_codes]);
};

// Public: setHTTPSuccess changes the http_success setting
SWFUpload.prototype.setAssumeSuccessTimeout = function (timeout_seconds) {
	this.settings.assume_success_timeout = timeout_seconds;
	this.callFlash("SetAssumeSuccessTimeout", [timeout_seconds]);
};

// Public: setDebugEnabled changes the debug_enabled setting
SWFUpload.prototype.setDebugEnabled = function (debugEnabled) {
	this.settings.debug_enabled = debugEnabled;
	this.callFlash("SetDebugEnabled", [debugEnabled]);
};

// Public: setButtonImageURL loads a button image sprite
SWFUpload.prototype.setButtonImageURL = function (buttonImageURL) {
	if (buttonImageURL == undefined) {
		buttonImageURL = "";
	}
	
	this.settings.button_image_url = buttonImageURL;
	this.callFlash("SetButtonImageURL", [buttonImageURL]);
};

// Public: setButtonDimensions resizes the Flash Movie and button
SWFUpload.prototype.setButtonDimensions = function (width, height) {
	this.settings.button_width = width;
	this.settings.button_height = height;
	
	var movie = this.getMovieElement();
	if (movie != undefined) {
		movie.style.width = width + "px";
		movie.style.height = height + "px";
	}
	
	this.callFlash("SetButtonDimensions", [width, height]);
};
// Public: setButtonText Changes the text overlaid on the button
SWFUpload.prototype.setButtonText = function (html) {
	this.settings.button_text = html;
	this.callFlash("SetButtonText", [html]);
};
// Public: setButtonTextPadding changes the top and left padding of the text overlay
SWFUpload.prototype.setButtonTextPadding = function (left, top) {
	this.settings.button_text_top_padding = top;
	this.settings.button_text_left_padding = left;
	this.callFlash("SetButtonTextPadding", [left, top]);
};

// Public: setButtonTextStyle changes the CSS used to style the HTML/Text overlaid on the button
SWFUpload.prototype.setButtonTextStyle = function (css) {
	this.settings.button_text_style = css;
	this.callFlash("SetButtonTextStyle", [css]);
};
// Public: setButtonDisabled disables/enables the button
SWFUpload.prototype.setButtonDisabled = function (isDisabled) {
	this.settings.button_disabled = isDisabled;
	this.callFlash("SetButtonDisabled", [isDisabled]);
};
// Public: setButtonAction sets the action that occurs when the button is clicked
SWFUpload.prototype.setButtonAction = function (buttonAction) {
	this.settings.button_action = buttonAction;
	this.callFlash("SetButtonAction", [buttonAction]);
};

// Public: setButtonCursor changes the mouse cursor displayed when hovering over the button
SWFUpload.prototype.setButtonCursor = function (cursor) {
	this.settings.button_cursor = cursor;
	this.callFlash("SetButtonCursor", [cursor]);
};

/* *******************************
	Flash Event Interfaces
	These functions are used by Flash to trigger the various
	events.
	
	All these functions a Private.
	
	Because the ExternalInterface library is buggy the event calls
	are added to a queue and the queue then executed by a setTimeout.
	This ensures that events are executed in a determinate order and that
	the ExternalInterface bugs are avoided.
******************************* */

SWFUpload.prototype.queueEvent = function (handlerName, argumentArray) {
	// Warning: Don't call this.debug inside here or you'll create an infinite loop
	
	if (argumentArray == undefined) {
		argumentArray = [];
	} else if (!(argumentArray instanceof Array)) {
		argumentArray = [argumentArray];
	}
	
	var self = this;
	if (typeof this.settings[handlerName] === "function") {
		// Queue the event
		this.eventQueue.push(function () {
			this.settings[handlerName].apply(this, argumentArray);
		});
		
		// Execute the next queued event
		setTimeout(function () {
			self.executeNextEvent();
		}, 0);
		
	} else if (this.settings[handlerName] !== null) {
		throw "Event handler " + handlerName + " is unknown or is not a function";
	}
};

// Private: Causes the next event in the queue to be executed.  Since events are queued using a setTimeout
// we must queue them in order to garentee that they are executed in order.
SWFUpload.prototype.executeNextEvent = function () {
	// Warning: Don't call this.debug inside here or you'll create an infinite loop

	var  f = this.eventQueue ? this.eventQueue.shift() : null;
	if (typeof(f) === "function") {
		f.apply(this);
	}
};

// Private: unescapeFileParams is part of a workaround for a flash bug where objects passed through ExternalInterface cannot have
// properties that contain characters that are not valid for JavaScript identifiers. To work around this
// the Flash Component escapes the parameter names and we must unescape again before passing them along.
SWFUpload.prototype.unescapeFilePostParams = function (file) {
	var reg = /[$]([0-9a-f]{4})/i;
	var unescapedPost = {};
	var uk;

	if (file != undefined) {
		for (var k in file.post) {
			if (file.post.hasOwnProperty(k)) {
				uk = k;
				var match;
				while ((match = reg.exec(uk)) !== null) {
					uk = uk.replace(match[0], String.fromCharCode(parseInt("0x" + match[1], 16)));
				}
				unescapedPost[uk] = file.post[k];
			}
		}

		file.post = unescapedPost;
	}

	return file;
};

// Private: Called by Flash to see if JS can call in to Flash (test if External Interface is working)
SWFUpload.prototype.testExternalInterface = function () {
	try {
		return this.callFlash("TestExternalInterface");
	} catch (ex) {
		return false;
	}
};

// Private: This event is called by Flash when it has finished loading. Don't modify this.
// Use the swfupload_loaded_handler event setting to execute custom code when SWFUpload has loaded.
SWFUpload.prototype.flashReady = function () {
	// Check that the movie element is loaded correctly with its ExternalInterface methods defined
	var movieElement = this.getMovieElement();

	if (!movieElement) {
		this.debug("Flash called back ready but the flash movie can't be found.");
		return;
	}

	this.cleanUp(movieElement);
	
	this.queueEvent("swfupload_loaded_handler");
};

// Private: removes Flash added fuctions to the DOM node to prevent memory leaks in IE.
// This function is called by Flash each time the ExternalInterface functions are created.
SWFUpload.prototype.cleanUp = function (movieElement) {
	// Pro-actively unhook all the Flash functions
	try {
		if (this.movieElement && typeof(movieElement.CallFunction) === "unknown") { // We only want to do this in IE
			this.debug("Removing Flash functions hooks (this should only run in IE and should prevent memory leaks)");
			for (var key in movieElement) {
				try {
					if (typeof(movieElement[key]) === "function") {
						movieElement[key] = null;
					}
				} catch (ex) {
				}
			}
		}
	} catch (ex1) {
	
	}

	// Fix Flashes own cleanup code so if the SWFMovie was removed from the page
	// it doesn't display errors.
	window["__flash__removeCallback"] = function (instance, name) {
		try {
			if (instance) {
				instance[name] = null;
			}
		} catch (flashEx) {
		
		}
	};

};


/* This is a chance to do something before the browse window opens */
SWFUpload.prototype.fileDialogStart = function () {
	this.queueEvent("file_dialog_start_handler");
};


/* Called when a file is successfully added to the queue. */
SWFUpload.prototype.fileQueued = function (file) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("file_queued_handler", file);
};


/* Handle errors that occur when an attempt to queue a file fails. */
SWFUpload.prototype.fileQueueError = function (file, errorCode, message) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("file_queue_error_handler", [file, errorCode, message]);
};

/* Called after the file dialog has closed and the selected files have been queued.
	You could call startUpload here if you want the queued files to begin uploading immediately. */
SWFUpload.prototype.fileDialogComplete = function (numFilesSelected, numFilesQueued, numFilesInQueue) {
	this.queueEvent("file_dialog_complete_handler", [numFilesSelected, numFilesQueued, numFilesInQueue]);
};

SWFUpload.prototype.uploadStart = function (file) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("return_upload_start_handler", file);
};

SWFUpload.prototype.returnUploadStart = function (file) {
	var returnValue;
	if (typeof this.settings.upload_start_handler === "function") {
		file = this.unescapeFilePostParams(file);
		returnValue = this.settings.upload_start_handler.call(this, file);
	} else if (this.settings.upload_start_handler != undefined) {
		throw "upload_start_handler must be a function";
	}

	// Convert undefined to true so if nothing is returned from the upload_start_handler it is
	// interpretted as 'true'.
	if (returnValue === undefined) {
		returnValue = true;
	}
	
	returnValue = !!returnValue;
	
	this.callFlash("ReturnUploadStart", [returnValue]);
};



SWFUpload.prototype.uploadProgress = function (file, bytesComplete, bytesTotal) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("upload_progress_handler", [file, bytesComplete, bytesTotal]);
};

SWFUpload.prototype.uploadError = function (file, errorCode, message) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("upload_error_handler", [file, errorCode, message]);
};

SWFUpload.prototype.uploadSuccess = function (file, serverData, responseReceived) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("upload_success_handler", [file, serverData, responseReceived]);
};

SWFUpload.prototype.uploadComplete = function (file) {
	file = this.unescapeFilePostParams(file);
	this.queueEvent("upload_complete_handler", file);
};

/* Called by SWFUpload JavaScript and Flash functions when debug is enabled. By default it writes messages to the
   internal debug console.  You can override this event and have messages written where you want. */
SWFUpload.prototype.debug = function (message) {
	this.queueEvent("debug_handler", message);
};


/* **********************************
	Debug Console
	The debug console is a self contained, in page location
	for debug message to be sent.  The Debug Console adds
	itself to the body if necessary.

	The console is automatically scrolled as messages appear.
	
	If you are using your own debug handler or when you deploy to production and
	have debug disabled you can remove these functions to reduce the file size
	and complexity.
********************************** */
   
// Private: debugMessage is the default debug_handler.  If you want to print debug messages
// call the debug() function.  When overriding the function your own function should
// check to see if the debug setting is true before outputting debug information.
SWFUpload.prototype.debugMessage = function (message) {
	if (this.settings.debug) {
		var exceptionMessage, exceptionValues = [];

		// Check for an exception object and print it nicely
		if (typeof message === "object" && typeof message.name === "string" && typeof message.message === "string") {
			for (var key in message) {
				if (message.hasOwnProperty(key)) {
					exceptionValues.push(key + ": " + message[key]);
				}
			}
			exceptionMessage = exceptionValues.join("\n") || "";
			exceptionValues = exceptionMessage.split("\n");
			exceptionMessage = "EXCEPTION: " + exceptionValues.join("\nEXCEPTION: ");
			SWFUpload.Console.writeLine(exceptionMessage);
		} else {
			SWFUpload.Console.writeLine(message);
		}
	}
};

SWFUpload.Console = {};
SWFUpload.Console.writeLine = function (message) {
	var console, documentForm;

	try {
		console = document.getElementById("SWFUpload_Console");

		if (!console) {
			documentForm = document.createElement("form");
			document.getElementsByTagName("body")[0].appendChild(documentForm);

			console = document.createElement("textarea");
			console.id = "SWFUpload_Console";
			console.style.fontFamily = "monospace";
			console.setAttribute("wrap", "off");
			console.wrap = "off";
			console.style.overflow = "auto";
			console.style.width = "700px";
			console.style.height = "350px";
			console.style.margin = "5px";
			documentForm.appendChild(console);
		}

		console.value += message + "\n";

		console.scrollTop = console.scrollHeight - console.clientHeight;
	} catch (ex) {
		alert("Exception: " + ex.name + " Message: " + ex.message);
	}
};

/*
Uploadify v3.0.0
Copyright (c) 2010 Ronnie Garcia

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

if(jQuery)(
	function(jQuery){
		jQuery.extend(jQuery.fn,{
			uploadify:function(options,swfUploadOptions) {
				jQuery(this).each(function() {
					var clone    = jQuery(this).clone();
					var settings = jQuery.extend({
						// Required Settings
						id       : jQuery(this).attr('id'),
						swf      : 'uploadify.swf',
						uploader : 'uploadify.php',
						
						// Options
						auto : false,
						buttonClass     : '',
						buttonCursor    : 'hand',
						buttonImage     : false,
						buttonText      : 'SELECT FILES',
						cancelImage     : 'uploadify-cancel.png',
						checkExisting   : 'uploadify-check-existing.php',
						debug           : false,
						fileObjName     : 'Filedata',
						fileSizeLimit   : 0,
						fileTypeDesc    : 'All Files (*.*)',
						fileTypeExts    : '*.*',
						height          : 30,
						method          : 'post',
						multi           : false,
						queueID         : false,
						queueSizeLimit  : 999,
						removeCompleted : true,
						removeTimeout   : 3,
						requeueErrors   : true,
						postData        : {},
						preventCaching  : true,
						progressData    : 'percentage',
						// simUploadLimit  : 1, // Not possible with swfUpload
						successTimeout  : 30,
						transparent     : true,
						uploadLimit     : 999,
						uploaderType    : 'html5', // the other option is 'flash'
						width           : 120,
						
						// Events
						skipDefault      : [],
						onClearQueue     : function() {},
						onDialogOpen     : function() {},
						onDialogClose    : function() {},
						onInit           : function() {},
						onQueueComplete  : function() {},
						onSelectError    : function() {},
						onSelect         : function() {},
						onSWFReady       : function() {},
						onUploadCancel   : function() {},
						onUploadComplete : function() {},
						onUploadError    : function() {},
						onUploadProgress : function() {},
						onUploadStart    : function() {}
					}, options);
					
					var swfUploadSettings = {
						assume_success_timeout   : settings.successTimeout,
						button_placeholder_id    : settings.id,
						button_image_url         : settings.buttonImage,
						button_width             : settings.width,
						button_height            : settings.height,
						button_text              : null,
						button_text_style        : null,
						button_text_top_padding  : 0,
						button_text_left_padding : 0,
						button_action            : (settings.multi ? SWFUpload.BUTTON_ACTION.SELECT_FILES : SWFUpload.BUTTON_ACTION.SELECT_FILE),
						button_disabled          : false,
						button_cursor            : (settings.buttonCursor == 'arrow' ? SWFUpload.CURSOR.ARROW : SWFUpload.CURSOR.HAND),
						button_window_mode       : (settings.transparent && !settings.buttonImage ? SWFUpload.WINDOW_MODE.TRANSPARENT : SWFUpload.WINDOW_MODE.OPAQUE),
						debug                    : settings.debug,						
						requeue_on_error         : settings.requeueErrors,
						file_post_name           : settings.fileObjName,
						file_size_limit          : settings.fileSizeLimit,
						file_types               : settings.fileTypeExts,
						file_types_description   : settings.fileTypeDesc,
						file_queue_limit         : settings.queueSizeLimit,
						file_upload_limit        : settings.uploadLimit,
						flash_url                : settings.swf,					
						prevent_swf_caching      : settings.preventCaching,
						post_params              : settings.postData,
						upload_url               : settings.uploader,
						use_query_string         : (settings.method == 'get'),
						
						// Event Handlers 
						file_dialog_complete_handler : onDialogClose,
						file_dialog_start_handler    : onDialogOpen,
						file_queued_handler          : onSelect,
						file_queue_error_handler     : onSelectError,
						flash_ready_handler          : settings.onSWFReady,
						upload_complete_handler      : onUploadComplete,
						upload_error_handler         : onUploadError,
						upload_progress_handler      : onUploadProgress,
						upload_start_handler         : onUploadStart,
						upload_success_handler       : onUploadSuccess
					}
					if (swfUploadOptions) {
						swfUploadSettings = jQuery.extend(swfUploadSettings,swfUploadOptions);
					}
					swfUploadSettings = jQuery.extend(swfUploadSettings,settings);
					
					// Create the swfUpload instance
					window['uploadify_' + settings.id] = new SWFUpload(swfUploadSettings);
					var swfuploadify = window['uploadify_' + settings.id];
					swfuploadify.original = clone;
					
					// Wrap the uploadify instance
					var wrapper = jQuery('<div />',{
						id      : settings.id,
						'class' : 'uploadify',
						css     : {
												'height'   : settings.height + 'px',
												'position' : 'relative',
												'width'    : settings.width + 'px'
											}
					});
					jQuery('#' + swfuploadify.movieName).wrap(wrapper);
					
					// Create the file queue
					if (!settings.queueID) {
						var queue = jQuery('<div />', {
							id      : settings.id + '_queue',
							'class' : 'uploadifyQueue'
						});
						jQuery('#' + settings.id).after(queue);
						swfuploadify.settings.queueID = settings.queueID = settings.id + '_queue';
					}
					
					// Create some queue related objects and variables
					swfuploadify.queue = {
						files              : {}, // The files in the queue
						filesSelected      : 0, // The number of files selected in the last select operation
						filesQueued        : 0, // The number of files added to the queue in the last select operation
						filesReplaced      : 0, // The number of files replaced in the last select operation
						filesCancelled     : 0, // The number of files that were cancelled instead of replaced
						filesErrored       : 0, // The number of files that caused error in the last select operation
						averageSpeed       : 0, // The average speed of the uploads in KB
						queueLength        : 0, // The number of files in the queue
						queueSize          : 0, // The size in bytes of the entire queue
						uploadSize         : 0, // The size in bytes of the upload queue
						queueBytesUploaded : 0, // The size in bytes that have been uploaded for the current upload queue
						uploadQueue        : [], // The files currently to be uploaded
						errorMsg           : 'Some files were not added to the queue:'
					};
					
					// Create the button
					if (!settings.buttonImage) {
						var button = jQuery('<div />', {
							id      : settings.id + '_button',
							'class' : 'uploadifyButton ' + settings.buttonClass,
							html    : '<span class="uploadifyButtonText">' + settings.buttonText + '</span>'
						});
						jQuery('#' + settings.id).append(button);
						jQuery('#' + swfuploadify.movieName).css({position: 'absolute', 'z-index': 1});
					} else {
						jQuery('#' + swfuploadify.movieName).addClass(settings.buttonClass);
					}
					
					// -----------------------------
					// Begin Event Handler Functions
					// -----------------------------
					
					// Triggered once when file dialog is closed
					function onDialogClose(filesSelected,filesQueued,queueLength) {
						var stats                     = swfuploadify.getStats();
						swfuploadify.queue.filesErrored  = filesSelected - filesQueued;
						swfuploadify.queue.filesSelected = filesSelected;
						swfuploadify.queue.filesQueued   = filesQueued - swfuploadify.queue.filesCancelled;
						swfuploadify.queue.queueLength   = queueLength;
						if (jQuery.inArray('onDialogClose',swfuploadify.settings.skipDefault) < 0) {
							if (swfuploadify.queue.filesErrored > 0) {
								alert(swfuploadify.queue.errorMsg);
							}
						}
						if (swfuploadify.settings.onDialogClose) swfuploadify.settings.onDialogClose(swfuploadify.queue);
						if (swfuploadify.settings.auto) jQuery('#' + swfuploadify.settings.id).uploadifyUpload('*');
					}
					
					function onDialogOpen() {
						// Reset some queue info
						swfuploadify.queue.errorMsg       = 'Some files were not added to the queue:';
						swfuploadify.queue.filesReplaced  = 0;
						swfuploadify.queue.filesCancelled = 0;
						if (swfuploadify.settings.onDialogOpen) swfuploadify.settings.onDialogOpen();
					}
					
					// Triggered once for each file added to the queue
					function onSelect(file) {
						if (jQuery.inArray('onSelect',swfuploadify.settings.skipDefault) < 0) {
							// Check if a file with the same name exists in the queue
							var queuedFile = {};
							for (var n in swfuploadify.queue.files) {
								queuedFile = swfuploadify.queue.files[n];
								if (queuedFile.name == file.name) {
									var replaceQueueItem = confirm('The file named "' + file.name + '" is already in the queue.\nDo you want to replace the existing item in the queue?');
									if (!replaceQueueItem) {
										swfuploadify.cancelUpload(file.id);
										swfuploadify.queue.filesCancelled++;
										return false;
									} else {
										jQuery('#' + queuedFile.id).remove();
										swfuploadify.cancelUpload(queuedFile.id);
										swfuploadify.queue.filesReplaced++;
									}
								}
							}
							
							// Get the size of the file
							var fileSize = Math.round(file.size / 1024);
							var suffix   = 'KB';
							if (fileSize > 1000) {
								fileSize = Math.round(fileSize / 1000);
								suffix   = 'MB';
							}
							var fileSizeParts = fileSize.toString().split('.');
							fileSize = fileSizeParts[0];
							if (fileSizeParts.length > 1) {
								fileSize += '.' + fileSizeParts[1].substr(0,2);
							}
							fileSize += suffix;
							
							// Truncate the filename if it's too long
							var fileName = file.name;
							if (fileName.length > 25) {
								fileName = fileName.substr(0,25) + '...';
							}
							
							// Add the file item to the queue
							jQuery('#' + swfuploadify.settings.queueID).append('<div id="' + file.id + '" class="uploadifyQueueItem">\
								<div class="cancel">\
									<a href="javascript:jQuery(\'#' + swfuploadify.settings.id + '\').uploadifyCancel(\'' + file.id + '\')"><img src="' + swfuploadify.settings.cancelImage + '" border="0" /></a>\
								</div>\
								<span class="fileName">' + fileName + ' (' + fileSize + ')</span><span class="data"></span>\
								<div class="uploadifyProgress">\
									<div class="uploadifyProgressBar"><!--Progress Bar--></div>\
								</div>\
							</div>');
							swfuploadify.queue.queueSize += file.size;
						}
						swfuploadify.queue.files[file.id] = file;
						if (swfuploadify.settings.onSelect) swfuploadify.settings.onSelect(file);
					}
					
					// Triggered when a file is not added to the queue
					function onSelectError(file,errorCode,errorMsg) {
						if (jQuery.inArray('onSelectError',swfuploadify.settings.skipDefault) < 0) {
							switch(errorCode) {
								case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
									if (swfuploadify.settings.queueSizeLimit > errorMsg) {
										swfuploadify.queue.errorMsg += '\nThe number of files selected exceeds the remaining upload limit (' + errorMsg + ').';
									} else {
										swfuploadify.queue.errorMsg += '\nThe number of files selected exceeds the queue size limit (' + swfuploadify.settings.queueSizeLimit + ').';
									}
									break;
								case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
									swfuploadify.queue.errorMsg += '\nThe file "' + file.name + '" exceeds the size limit (' + swfuploadify.settings.fileSizeLimit + ').';
									break;
								case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
									swfuploadify.queue.errorMsg += '\nThe file "' + file.name + '" is empty.';
									break;
								case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
									swfuploadify.queue.errorMsg += '\nThe file "' + file.name + '" is not an accepted file type (' + swfuploadify.settings.fileTypeDesc + ').';
									break;
							}
						}
						if (errorCode != SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
							delete swfuploadify.queue.files[file.id];
						}
						if (swfuploadify.settings.onSelectError) swfuploadify.settings.onSelectError(file,errorCode,errorMsg);
					}
					
					// Triggered when all the files in the queue have been processed
					function onQueueComplete() {
						var stats = swfuploadify.getStats();
						if (swfuploadify.settings.onQueueComplete) swfuploadify.settings.onQueueComplete(stats);
					}
					
					// Triggered when a file upload successfully completes
					function onUploadComplete(file) {
						var stats = swfuploadify.getStats();
						swfuploadify.queue.queueLength = stats.files_queued;
						if (swfuploadify.queue.uploadQueue[0] == '*') {
							if (swfuploadify.queue.queueLength > 0) {
								swfuploadify.startUpload();
							} else {
								swfuploadify.queue.uploadQueue = [];
								if (swfuploadify.settings.onQueueComplete) swfuploadify.settings.onQueueComplete(stats);
							}
						} else {
							if (swfuploadify.queue.uploadQueue.length > 0) {
								swfuploadify.startUpload(swfuploadify.queue.uploadQueue.shift());
							} else {
								swfuploadify.queue.uploadQueue = [];
								if (swfuploadify.settings.onQueueComplete) swfuploadify.settings.onQueueComplete(stats);
							}
						}
						if (jQuery.inArray('onUploadComplete',swfuploadify.settings.skipDefault) < 0) {
							if (swfuploadify.settings.removeCompleted) {
								switch (file.filestatus) {
									case SWFUpload.FILE_STATUS.COMPLETE:
										setTimeout(function() { 
											if (jQuery('#' + file.id)) {
												swfuploadify.queue.queueSize -= file.size;
												delete swfuploadify.queue.files[file.id]
												jQuery('#' + file.id).fadeOut(500,function() {
													jQuery(this).remove();
												});
											}
										},swfuploadify.settings.removeTimeout * 1000);
										break;
									case SWFUpload.FILE_STATUS.ERROR:
										if (!swfuploadify.settings.requeueErrors) {
											setTimeout(function() {
												if (jQuery('#' + file.id)) {
													swfuploadify.queue.queueSize -= file.size;
													delete swfuploadify.queue.files[file.id];
													jQuery('#' + file.id).fadeOut(500,function() {
														jQuery(this).remove();
													});
												}
											},swfuploadify.settings.removeTimeout * 1000);
										}
										break;
								}
							}
						}
						if (swfuploadify.settings.onUploadComplete) swfuploadify.settings.onUploadComplete(file,swfuploadify.queue);
					}
					
					// Triggered when a file upload returns an error
					function onUploadError(file,errorCode,errorMsg) {
						var errorString = 'Error';
						if (errorCode != SWFUpload.UPLOAD_ERROR.FILE_CANCELLED && errorCode != SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED) {
							jQuery('#' + file.id).addClass('uploadifyError');
						}
						jQuery('#' + file.id).find('.uploadifyProgressBar').css('width','1px');
						switch(errorCode) {
							case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
								errorString = 'HTTP Error (' + errorMsg + ')';
								break;
							case SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL:
								errorString = 'Missing Upload URL';
								break;
							case SWFUpload.UPLOAD_ERROR.IO_ERROR:
								errorString = 'IO Error';
								break;
							case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
								errorString = 'Security Error';
								break;
							case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
								alert('The upload limit has been reached (' + errorMsg + ').');
								errorString = 'Exceeds Upload Limit';
								break;
							case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
								errorString = 'Failed';
								break;
							case SWFUpload.UPLOAD_ERROR.SPECIFIED_FILE_ID_NOT_FOUND:
								break;
							case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
								errorString = 'Validation Error';
								break;
							case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
								errorString = 'Cancelled';
								swfuploadify.queue.queueSize -= file.size;
								if (file.status == SWFUpload.FILE_STATUS.IN_PROGRESS || jQuery.inArray(file.id,swfuploadify.queue.uploadQueue) >= 0) {
									swfuploadify.queue.uploadSize -= file.size;
								}
								delete swfuploadify.queue.files[file.id];
								break;
							case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
								errorString = 'Stopped';
								break;
						}
						if (errorCode != SWFUpload.UPLOAD_ERROR.SPECIFIED_FILE_ID_NOT_FOUND && file.status != SWFUpload.FILE_STATUS.COMPLETE) {
							jQuery('#' + file.id).find('.data').html(' - ' + errorString);
						}
						if (swfuploadify.settings.onUploadError) swfuploadify.settings.onUploadError(file,errorCode,errorMsg,errorString,swfuploadify.queue);
					}
					
					// Triggered periodically during a file upload
					function onUploadProgress(file,fileBytesLoaded,fileTotalBytes) {
						var timer                = new Date();
						var newTime              = timer.getTime();
						var lapsedTime           = newTime - swfuploadify.timer;
						swfuploadify.timer       = newTime;
						var lapsedBytes          = fileBytesLoaded - swfuploadify.bytesLoaded;
						swfuploadify.bytesLoaded = fileBytesLoaded;
						var queueBytesLoaded     = swfuploadify.queue.queueBytesUploaded + fileBytesLoaded;
						var percentage           = Math.round(fileBytesLoaded / fileTotalBytes * 100);
						
						// Calculate the average speed
						var mbs = 0;
						var kbs = (lapsedBytes / 1024) / (lapsedTime / 1000);
						kbs     = Math.floor(kbs * 10) / 10;
						if (swfuploadify.queue.averageSpeed > 0) {
							swfuploadify.queue.averageSpeed = (swfuploadify.queue.averageSpeed + kbs) / 2;
						} else {
							swfuploadify.queue.averageSpeed = kbs;
						}
						if (kbs > 1000) {
							mbs = (kbs * .001);
							swfuploadify.queue.averageSpeed = mbs;
						}
						var suffix = 'KB/s';
						if (mbs > 0) {
							suffix = 'MB/s';
						}
						
						if (jQuery.inArray('onUploadProgress',swfuploadify.settings.skipDefault) < 0) {
							if (swfuploadify.settings.progressData == 'percentage') {
								jQuery('#' + file.id).find('.data').html(' - ' + percentage + '%');
							} else if (swfuploadify.settings.progressData == 'speed') {
								jQuery('#' + file.id).find('.data').html(' - ' + percentage + suffix);
							}
							jQuery('#' + file.id).find('.uploadifyProgressBar').css('width',percentage + '%');
						}
						if (swfuploadify.settings.onUploadProgress) swfuploadify.settings.onUploadProgress(file,fileBytesLoaded,fileTotalBytes,queueBytesLoaded,swfuploadify.queue.uploadSize);
					}
					
					// Triggered right before a file is uploaded
					function onUploadStart(file) {

						var timer                = new Date();
						swfuploadify.timer       = timer.getTime();
						swfuploadify.bytesLoaded = 0;
						if (swfuploadify.queue.uploadQueue.length == 0) {
							swfuploadify.queue.uploadSize = file.size;
						}
						if (swfuploadify.settings.checkExisting !== false) {
							jQuery.ajax({
								type    : 'POST',
								async  : false,
								url     : swfuploadify.settings.checkExisting,
								data    : {filename: file.name},
								success : function(data) {
								alert(data);
									if (data == 1) {

										var overwrite = confirm('A file with the name "' + file.name + '" already exists on the server.\nWould you like to replace the existing file?');
										if (!overwrite) {
											swfuploadify.cancelUpload(file.id);
											jQuery('#' + file.id).remove();
											if (swfuploadify.queue.uploadQueue.length > 0 && swfuploadify.queue.queueLength > 0) {
												if (swfuploadify.queue.uploadQueue[0] == '*') {
													swfuploadify.startUpload();
												} else {
													swfuploadify.startUpload(swfuploadify.queue.uploadQueue.shift());
												}
											}
										}
									}
								}
							});
						}
						if (swfuploadify.settings.onUploadStart) swfuploadify.settings.onUploadStart(file); 
					}
					
					// Triggered when a file upload returns a successful code
					function onUploadSuccess(file,data,response) {
						swfuploadify.queue.queueBytesUploaded += file.size;
						jQuery('#' + file.id).find('.data').html(' - Complete');
						if (swfuploadify.settings.onUploadSuccess) swfuploadify.settings.onUploadSuccess(file,data,response); 
					}
					
					// ---------------------------
					// End Event Handler Functions
					// ---------------------------
				});
			},
			
			// Cancel a file upload and remove it from the queue
			uploadifyCancel:function(fileID) {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				var delay        = -1;
				if (arguments[0]) {
					if (arguments[0] == '*') {
						jQuery('#' + swfuploadify.settings.queueID).find('.uploadifyQueueItem').each(function() {
							delay++;
							swfuploadify.cancelUpload(jQuery(this).attr('id'));
							jQuery(this).delay(100 * delay).fadeOut(500,function() {
								jQuery(this).remove();
								
							});
						});
						swfuploadify.queue.queueSize = 0;
					} else {
						for (var n = 0; n < arguments.length; n++) {
							swfuploadify.cancelUpload(arguments[n]);
							jQuery('#' + arguments[n]).delay(100 * n).fadeOut(500,function() {
								jQuery(this).remove();
							});
						}
					}
				} else {
					jQuery('#' + swfuploadify.settings.queueID).find('.uploadifyQueueItem').get(0).fadeOut(500,function() {
						jQuery(this).remove();
						swfuploadify.cancelUpload(jQuery(this).attr('id'));
					});
				}
			},
			
			// Get rid of the instance of Uploadify
			uploadifyDestroy:function() {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				swfuploadify.destroy();
				jQuery('#' + id + '_queue').remove();
				jQuery('#' + id).replaceWith(swfuploadify.original);
				delete window['uploadify_' + id];
			},
			
			// Disable the select button
			uploadifyDisable:function(isDisabled) {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				swfuploadify.setButtonDisabled(isDisabled);
			},
			
			// Update or retrieve a setting
			uploadifySettings:function(name,value,resetObjects) {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				if (typeof(arguments[0]) == 'object') {
					for (var n in value) {
						setData(n,value[n]);
					}
				}
				if (arguments.length == 1) {
					return swfuploadify.settings[name];
				} else {
					setData(name,value,resetObjects);
				}
				
				function setData(settingName,settingValue,resetObjects) {
					switch (settingName) {
						case 'uploader':
							swfuploadify.setUploadURL(settingValue);
							break;
						case 'postData':
							if (!resetObjects) {
								value = jQuery.extend(swfuploadify.settings.postData,settingValue);
							}
							swfuploadify.setPostParams(settingValue);
							break;
						case 'method':
							if (settingValue == 'get') {
								swfuploadify.setUseQueryString(true);
							} else {
								swfuploadify.setUseQueryString(false);
							}
							break;
						case 'fileObjName':
							swfuploadify.setFilePostName(settingValue);
							break;
						case 'fileTypeExts':
							swfuploadify.setFileTypes(settingValue,swfuploadify.settings.fileTypeDesc);
							break;
						case 'fileTypeDesc':
							swfuploadify.setFileTypes(swfuploadify.settings.fileTypeExts,settingValue);
							break;
						case 'fileSizeLimit':
							swfuploadify.setFileSizeLimit(settingValue);
							break;
						case 'uploadLimit':
							swfuploadify.setFileUploadLimit(settingValue);
							break;
						case 'queueSizeLimit':
							swfuploadify.setFileQueueLimit(settingValue);
							break;
						case 'buttonImage':
							jQuery('#' + swfuploadify.settings.id + '_button').remove();
							swfuploadify.setButtonImageURL(settingValue);
							break;
						case 'buttonCursor':
							if (settingValue == 'arrow') {
								swfuploadify.setButtonCursor(SWFUpload.CURSOR.ARROW);
							} else {
								swfuploadify.setButtonCursor(SWFUpload.CURSOR.HAND);
							}
							break;
						case 'buttonText':
							jQuery('#' + swfuploadify.settings.id + '_button').find('.uploadifyButtonText').html(settingValue);
							break;
						case 'width':
							swfuploadify.setButtonDimensions(settingValue,swfuploadify.settings.height);
							break;
						case 'height':
							swfuploadify.setButtonDimensions(swfuploadify.settings.width,settingValue);
							break;
						case 'multi':
							if (settingValue) {
								swfuploadify.setButtonAction(SWFUpload.BUTTON_ACTION.SELECT_FILES);
							} else {
								swfuploadify.setButtonAction(SWFUpload.BUTTON_ACTION.SELECT_FILE);
							}
							break;
					}
					swfuploadify.settings[settingName] = value;
				}
			},
			
			// Stop the current upload and requeue what is in progress
			uploadifyStop:function() {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				swfuploadify.stopUpload();
			},
			
			// Upload the first file, a select number of files, or all the files in the queue
			uploadifyUpload:function() {
				var id           = jQuery(this).selector.replace('#','');
				var swfuploadify = window['uploadify_' + id];
				
				// Reset the queue information
				swfuploadify.queue.averageSpeed  = 0;
				swfuploadify.queue.uploadSize    = 0;
				swfuploadify.queue.bytesUploaded = 0;
				swfuploadify.queue.uploadQueue   = [];
				
				if (arguments[0]) {
					if (arguments[0] == '*') {
						swfuploadify.queue.uploadSize = swfuploadify.queue.queueSize;
						swfuploadify.queue.uploadQueue.push('*');
						swfuploadify.startUpload();
					} else {
						for (var n = 0; n < arguments.length; n++) {
							swfuploadify.queue.uploadSize += swfuploadify.queue.files[arguments[n]].size;
							swfuploadify.queue.uploadQueue.push(arguments[n]);
						}
						swfuploadify.startUpload(swfuploadify.queue.uploadQueue.shift());
					}
				} else {
					swfuploadify.startUpload();
				}
			}
		})
	}
)(jQuery);

function InitUploadifyQueue() {
	$("#uploadify").uploadify({
		'swf'            : '/admin/multiload/jquery/uploadify.swf',
		'buttonImage'    : '/admin/multiload/jquery/browse.png',
		'width'          : 72,
		'height'         : 22,
		'uploader'       : '/admin/multiload/action/uploadpics.php',
		'checkExisting'  : false,
		'cancelImage'      : '/admin/multiload/jquery/cancel.png',
		'multi'          : true,
		'postData'     : {'groups_id': 0, 'information_systems_id': 0, 'sc': sc, 'name': '', 'description':'','tags':'','smallprefix':'','save_proportions_big':'','save_proportions_small':''},
		'onQueueComplete' :  function(stats) {
			$("#incount").val(stats.successful_uploads);
			$("#picsform").submit()
		},
		'onUploadStart': function(file){
			var realname = $('#'+file.id+'_ename').val();
			if (realname != '') {
				var swfuploadify = window['uploadify_uploadify'];
				swfuploadify.addFileParam(file.id,'realname',realname);
			}
		},
		'onSelect' : function(file) {
			var div = $('#'+file.id);
			var fileName = file.name;
			if ($('#removeext:checked').val() == 'on') {
				var nameArray = /(.+)\.(.+)$/i.exec(file.name);
				if (nameArray != null && typeof nameArray[1] != 'undefined') {
					fileName = nameArray[1];
				}
			}
			if ($('#itemname').val() != '') 
			{			
				fileName2 = $('#itemname').val().replace(/\$1/g,fileName);
				fileName2 = fileName2.replace(/\$2/g, fileName.substr(0, fileName.indexOf(".")));				
				fileName = fileName2;
			}
			div.append('<br/>Название инф. элемента:<br/><input type="text" value="'+fileName+'" name="'+file.id+'_ename" id="'+file.id+'_ename" style="width: 100%;" />');
		}
		
	});
}
        jQuery(function($) {
			InitUploadifyQueue();
			
			// Инфогруппа
			$("#infsysgroupselect").change(function(){
				$.get("/admin/multiload/action/get_items.php", {'infsysgroup': $("#infsysgroupselect option:selected").val(), 'infsysid': $("#infsysselect option:selected").val()},
                function(data){
                    $("#infsysitemselect").html(data);
                });
			});
			
			// Инфосистема
            $("#infsysselect").change(function(){
                $.get("/admin/multiload/action/get_groups.php", {'infsysid': $("#infsysselect option:selected").val()},
                function(data){
                    $("#infsysgroupselect").html(data);
					$("#infsysgroupselect").change();
                });
                $.get("/admin/multiload/action/get_properties.php", {'infsysid': $("#infsysselect option:selected").val()},
                function(data){
                    $("#infsyspropselect").html(data);
                });		
	        });
			
			// Магазин
            $("#shopselect").change(function(){
                $.get("/admin/multiload/action/get_groups.php", {'shopid': $("#shopselect option:selected").val()},
                function(data){
                    $("#shopgroupselect").html(data);
					$("#shopgroupselect").change();
                });
			});
			
			// Раздел магазина
			$("#shopgroupselect").change(function(){
				$.get("/admin/multiload/action/get_items.php", {'shopgroup': $("#shopgroupselect option:selected").val(), 'shopid': $("#shopselect option:selected").val()},
					function(data){
						$("#shopitemselect").html(data);
                });
				
				$.get("/admin/multiload/action/get_properties.php", {'shopid': $("#shopselect option:selected").val(), 'shopgroup': $("#shopgroupselect option:selected").val()},
					function(data){
						$("#shoppropselect").html(data);
                });
				
			});
			
			$("#id_tab_span_0, #id_tab_span_1").click(function(){
                    $('#uploadify').uploadifyDestroy();
					InitUploadifyQueue();
                }
            );
			
			// Загрузка в элемент инофсистемы
			$("#type_selector0").change(function(){
				$("#infsysitemselect_div").css("display", "none");
				$("#infsyspropselect_div").css("display", "none");
				
				$("#infsysitemselect").val(0);
				$("#infsyspropselect").val(0);
			});
			
			// Загрузка в доп. свойство элемента инофсистемы
			$("#type_selector1").change(function(){
				$("#infsysitemselect_div").css("display", "block");
				$("#infsyspropselect_div").css("display", "block");
			});
			
			// Вкладка Информационная система
			$("#li_tab_page_0").click(function(){
				$("#loadtype").val(1);
			});		
			
			// Вкладка Интернет-магазин
			$("#li_tab_page_1").click(function(){
				$("#loadtype").val(2);
			});				
        });

		function getUploadifyFileName(file) {
			var fileName = file.name;
			if (fileName.length > 25) {
				fileName = fileName.substr(0,25) + '...';
			}
			return fileName;
		}
		
		function getUploadifyFileSize(file) {
			var fileSize = Math.round(file.size / 1024);
			var suffix   = 'KB';
			if (fileSize > 1000) {
				fileSize = Math.round(fileSize / 1000);
				suffix   = 'MB';
			}
			var fileSizeParts = fileSize.toString().split('.');
			fileSize = fileSizeParts[0];
			if (fileSizeParts.length > 1) {
				fileSize += '.' + fileSizeParts[1].substr(0,2);
			}
			fileSize += suffix;
			return fileSize;
		}

        function UploadButton() {
		
			var loadFlag = false;
			
			$type = $("#loadtype").val();	
			
			if ($type == 1)
			{
				if ($("#infsysselect option:selected").val() != 0)
				{
					loadFlag = true;
				}
			} else
			{
				if ($("#shopselect option:selected").val() != 0 && $("#shoppropselect option:selected").val() != 0 && $("#shopitemselect option:selected").val() != 0)
				{
					loadFlag = true;
				}			
			}
			
            if (loadFlag) {
				var propids = '';
				$('#iexmultiloadispropertys .isprop:checked').each(function() {
					propids += $(this).attr('id')+',';
				});
				$("#uploadify").uploadifySettings('postData', {
				
				'information_system_group_id': $("#infsysgroupselect option:selected").val(),
				'information_system_id':$("#infsysselect option:selected").val(),
				'information_system_item_id':$("#infsysitemselect option:selected").val(),
				'information_system_prop_id':$("#infsyspropselect option:selected").val(),
				
				'sc': sc,
				'loadtype': $type,
				
				'shop_group_id': $("#shopgroupselect option:selected").val(),
				'shop_id': $("#shopselect option:selected").val(),
				'shop_item_id':$("#shopitemselect option:selected").val(),
				'shop_prop_id':$("#shoppropselect option:selected").val()
				
				/*, 'name': $("#itemname").val(), 'description':tinyMCE.activeEditor.getContent(),'tags':$("#itemtags").val(),'save_proportions_big':$("#save_proportions_big").attr('checked'),'save_proportions_small':$("#save_proportions_small").attr('checked'),'additional_properties':propids*/});
                $('#uploadify').uploadifyUpload('*');
            }
        }
		