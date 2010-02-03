/**
 * Define a clone method for Date Object
 */
Date.prototype.clone = function()
{
	return new Date(this.getTime());
};

Date.prototype.toDebugString = function()
{
	return this.getHours() + ':' + this.getMinutes() + ':' + this.getSeconds() + '.' + this.getMilliseconds() + ' ';
};

function in_array(needle, array)
{
	return ! array.every(function(element) { return element != needle; });
}

/**
 * Add Standard String.trim functionality
 */
if (typeof(String.prototype.trim) !== 'function')
{
	String.prototype.trim = function()
	{
		return this.replace(/^\s+/g, "").replace(/\s+$/g, "");
	};
}

function trim(string)
{
    return String(string).trim();
}

/**
 * wCore object provides basic functionalities
 * required by wCore bindings, such as :
 *     - Ordered initialization.
 *     - Logging.
 */

var wCore = {};

// Event listeners stack :
wCore._eventListeners = [];

wCore.addEventListener = function (element, event, listener, useCapture)
{
    try
    {
        if (useCapture != true) {
            useCapture = false;
        }
        var listenerData =
        {
            element: element,
            event: event,
            listener: listener,
            useCapture: useCapture
        };
        wCore._eventListeners.push(listenerData);
        element.addEventListener(event, listener, useCapture);
    }
    catch (e)
    {
        wCore.error("addEventListener", [element, event, listener, useCapture], e);
    }
};

wCore.removeEventListener = function (element, event)
{
    try
    {
        var listener = undefined;
        var useCapture = undefined;
        for (var i = 0; i < wCore._eventListeners.length; i++) {
            if ((wCore._eventListeners[i].element == element)
            && (wCore._eventListeners[i].event == event)) {
                listener = wCore._eventListeners[i].listener;
                useCapture = wCore._eventListeners[i].useCapture;
                wCore._eventListeners[i].element = undefined;
                wCore._eventListeners[i].event = undefined;
                break;
            }
        }
        if (listener) {
            element.removeEventListener(event, listener, useCapture);
        }
    }
    catch (e)
    {
        wCore.error("removeEventListener", [element, event], e);
    }
};

/**
 * wCore object provides a set of methods to
 * display and log messages.
 */

// Logging levels :
const DEBUG = 1000;
const INFO = 2000;
const WARN = 3000;
const ERROR = 4000;
const FATAL = 5000;

// Default logging level :
wCore._logLevel = DEBUG;

// Get and Set logging level :
wCore.getLogLevel = function ()
{
    return this._logLevel;
};
wCore.setLogLevel = function (logLevel)
{
    this._logLevel = logLevel;
};

/**
 * error() is used to log all error messages
 * throwed by functions and binding methods.
 *
 * @param  func   string function name.
 * @param  args   array function parameters.
 * @param  error  string error message.
 * @returns
 */
wCore.error = function (func, args, error)
{
	if (args != null && error != null)
	{
		var msg = "*Error* in " + func + "(" + args.join(", ") + "):";
		if (typeof(error) == "object")
		{
			msg += error.toString() + ":" + error.stack;
		}
	}
	else
	{
		msg = func;
	}
	this.log(msg, ERROR);
};

wCore.warn = function (message)
{
    this.log(message, WARN);
};

wCore.info = function (message)
{
    this.log(message, INFO);
};
/**
 * debug() is used to simply log a
 * debug-level message.
 *
 * @param  message  string message to log.
 */
wCore.debug = function (message)
{
	if (Context.DEV_MODE) {this.log(message, DEBUG);}
};

/**
 * log() displays given message in JS Console
 * according to its logging level.
 *
 * @param  message   string message to log.
 * @param  logLevel  integer message's logging level.
 */
wCore.log = function (message, logLevel)
{
    if (logLevel >= this.getLogLevel())
    {
        try
        {
            var consoleService=Components.classes["@mozilla.org/consoleservice;1"]
                .getService(Components.interfaces.nsIConsoleService);
            var errorObject = Components.classes['@mozilla.org/scripterror;1']
                .createInstance(Components.interfaces.nsIScriptError);
            message = trim(message);
            switch (logLevel)
            {
                case DEBUG:
                case INFO:
                    consoleService.logStringMessage(new Date().toDebugString() +  message);
                    break;

                case WARN:
                    errorObject.init(message, null, null, 0, 0, 1, null);
                    consoleService.logMessage(errorObject);
                    break;

                case ERROR:
                case FATAL:
                    errorObject.init(message, null, null, 0, 0, 0, null);
                    consoleService.logMessage(errorObject);
                    break;
            }
        }
        catch (e)
        {
            dump(message + "\n");
            dump(e);
        }

    }
};

wCore.dump = function(obj, name, indent, depth)
{
	if (typeof name == "undefined") {name = "anonymous";}
	if (typeof indent == "undefined") {indent = "";}
	if (typeof depth == "undefined") {depth = 1;}
	
	if (depth > 10) {
		return indent + name + ": <Maximum Depth Reached>\n";
	}
	if (typeof obj == "object") 
	{	
		if (obj === null) {return indent + name + ": null\n";}
		var child = null;
		var output = indent + name + "\n";
		indent += "\t";
		for (var item in obj)
		{
			try {
				child = obj[item];
			} catch (e) {
				child = "<Unable to Evaluate>";
			}
			if (typeof child == "object") {
				output += wCore.dump(child, item, indent, depth + 1);
			} else {
				output += indent + item + ": " + child + "\n";
			}
		}
		return output;
	} 
	else 
	{
		return obj;
	}
}

wCore.getStackText = function()
{
    stackText = "Stack Trace: \n";
    var count = 0;
    var caller = arguments.callee.caller; 
    while (caller) {
      stackText += count++ + ":" + caller.name + "(";
      for (var i = 0; i < caller.arguments.length; ++i) {
        var arg = caller.arguments[i];
        stackText += arg;
        if (i < caller.arguments.length - 1)
          stackText += ",";
      }
      stackText += ")\n";
      caller = caller.arguments.callee.caller;
    }
    return stackText;
}

wCore.cleanHiddenChars = function(content)
{
	if (typeof content == 'string')
	{
		return content.replace(/\x19/g, "");
	}
	return content;
}

wCore.getSelectionAttribute = function(selection, attributeName, unique)
{
	var values = [ ], uniqueValues = [ ];
	if (selection && selection.document)
	{
    	for (var i=0; i<selection.document.length(); i++)
    	{
    		var value = selection.document[i].component.(@name==attributeName).toString();
    		if (unique === true)
    		{
    			if ( ! (value in uniqueValues) )
    			{
    				uniqueValues[value] = 1;
    				values.push(value);
    			}
    		}
    		else
    		{
    			values.push(value);
    		}
    	}
	}
	return values;
}

function wLocale(value)
{
    this.value = value;
    this.attributes = [];
    this.setAttribute = function (name, value)
    {
        this.attributes[name] = value;
    };
    this.toString = function ()
    {
        var finalValue = this.value;
        for (var i in this.attributes)
        {
            finalValue = finalValue.replace("{" + i + "}", this.attributes[i]);
        }

        // Fix new lines.
        finalValue = finalValue.replace(new RegExp("\\\\n", "g"), "\n");
        
        return finalValue;
    };
}

function wServerLocale(value)
{
	this.value = value;
    this.attributes = [];
    this.setAttribute = function (name, value)
    {
        this.attributes[name] = value;
    };
    this.toString = function ()
    {
    	if (this.value in wServerLocaleCache)
    	{
    		var finalValue = wServerLocaleCache[this.value];
    	}
    	else
    	{
   			var serverRequest = $.ajax({
  								url: "{UIHOST}/xul_controller.php?module=uixul&action=Translate&key=" + this.value,
  								type: "GET",
  								async: false,
  								dataType: 'text'});
 			var finalValue = serverRequest.responseText;
 			wServerLocaleCache[this.value] = finalValue;
        }
        for (var i in this.attributes)
        {
            finalValue = finalValue.replace("{" + i + "}", this.attributes[i]);
        }
        return finalValue;
    };
}

var wServerLocaleCache = {};


function wControllerExecuteParameters()
{
	this.actionLabel = null;
	this.senderObject = null;
	this.module = null;
	this.action = null;
	this.requestParameters = null;
	this.httpMethod = null;
	this.callBack = null;
	this.callBackParameters = null;
}

wCore._delayedExecutions = [];
wCore._queueIsRunning = false;

wCore.executeOnPredicate = function (predicate, func, context)
{
	if (predicate())
	{
		func(context);
		return;
	}
	wCore._delayedExecutions.push({
			predicate: predicate,
			func: func,
			context: context,
			retryCount:0
		});
	if (!wCore._queueIsRunning)
	{
		wCore._queueIsRunning = true;
		wCore.timer();
		wCore.debug("queue started");
	}
} 

wCore.executeOnMethodExists = function (object, methodName, func)
{
	var bindingObject = object;
	var bindingMethodName = methodName;
	var targetFunction = func;
	wCore.executeOnPredicate(function(){ return  (bindingMethodName in bindingObject); }, targetFunction, bindingObject);
}

wCore.executeLater = function(func, timer)
{
	try
	{
		setTimeout(func, timer);
	}
	catch (e)
	{
		wCore.error("executeLater", [func, timer], e);
	}
}

wCore.timer = function ()
{
	var queueLength = wCore._delayedExecutions.length;
	var leftToExecute = [];
	for (var i = 0 ; i < wCore._delayedExecutions.length; i++)
	{
		var exec = wCore._delayedExecutions[i];
		try
		{
			if (exec.predicate() == true)
			{
				exec.func(exec.context);
			}
			else
			{
				if ((++exec.retryCount) > 1000)
				{
					wCore.debug("dequeuing Predicate: " + exec.predicate);
					wCore.debug("dequeuing Func: " + exec.func);
					wCore.debug("dequeuing Context TagName: " + exec.context.localName);
					wCore.debug("dequeuing Context Id: " + exec.context.id);
				}
				else
				{
					leftToExecute.push(exec);
				}
			}
		} 
		catch (e)
		{
			wCore.error("wCore.timer", [], e);
		}
	}
	if (leftToExecute.length > 0)
	{
		//wCore.debug("re-queuing : " + leftToExecute.length + " functions");
		wCore._delayedExecutions = leftToExecute;
		setTimeout(function(){ wCore.timer() }, 80);
	}
	else
	{
		wCore._delayedExecutions = [];
		wCore._queueIsRunning = false;
		wCore.debug("stoping queue");
	}
}

wCore.checkPermission = function(permission, nodeId)
{
		var cacheKey = permission.replace(/\./, '_')+'_'+nodeId;
		if (cacheKey in wPermissionCache)
		{
			return wPermissionCache[cacheKey];
		}
		var requestUrl = "{UIHOST}/xul_controller.php?module=uixul&action=CheckRole&role=" + permission +"&node=" + nodeId;
		var serverRequest = $.ajax({
  						url: requestUrl,
  						type: "GET",
  						async: false,
  						dataType: 'text'
 		});
		var xmlParser = new DOMParser();
		var xmlResponse = xmlParser.parseFromString(serverRequest.responseText, 'text/xml');
 		if (xmlResponse && $("status", xmlResponse).length == 1)
 		{
			if ($("status", xmlResponse).get(0).firstChild.nodeValue == "OK")
			{
				return (wPermissionCache[cacheKey] = true);
			}
 		}
 		return (wPermissionCache[cacheKey] = false);
 }
 
var wPermissionCache = {};

wCore.getDocumentInfo = function(documentId)
{
	try
	{
		var result = wCore.executeJSON('generic', 'Info', {cmpref: documentId, lang: Context.W_LANG}, null, false);
		if (result != null && result.status != null && result.status == 'OK')
		{
			if (result.contents.length == 1)
		 	{
		 		return result.contents[0];
		 	} 
		 	else if (result.contents.length > 1)
		 	{
		 		return result.contents;
		 	}			
		}
	} 
	catch (e)
	{
		wCore.error("wCore.getDocumentInfo", [documentId], e);
	}
 	return null;
};

wCore.getSubDocumentParentId = function(parentModule, parentId, newModelName)
{
	try
	{
		var result = wCore.executeJSON(parentModule, 'GetSubDocumentParentId', {cmpref: parentId, newmodelname: newModelName}, null, true);
		if (result != null && result.status != null && result.status == 'OK')
		{
			return result.contents;
		}
	} 
	catch (e)
	{
		wCore.error("wCore.getSubDocumentParentId", [parentModule, parentId, newModelName], e);
	}
 	return null;
};

function setCookie(c_name,value,expiredays)
{
 	var uri = Components.classes["@mozilla.org/network/standard-url;1"].createInstance(Components.interfaces.nsIURI);
    var cservice = Components.classes["@mozilla.org/cookieService;1"].getService().QueryInterface(Components.interfaces.nsICookieService);
	uri.spec = Context.UIBASEURL;
	
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	var cookieString = c_name+ "=" +escape(value)+
	((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
	cservice.setCookieString(uri, null, cookieString, null);
	
}

function getCookie(c_name)
{
 	var uri = Components.classes["@mozilla.org/network/standard-url;1"].createInstance(Components.interfaces.nsIURI);
    var cservice = Components.classes["@mozilla.org/cookieService;1"].getService().QueryInterface(Components.interfaces.nsICookieService);
	uri.spec = Context.UIBASEURL;
	var result = cservice.getCookieString(uri, null);
	if (result != null && result.length > 0)
	{
		var c_start = result.indexOf(c_name + "=");
	  	if (c_start!=-1)
	    { 
	    	c_start=c_start + c_name.length+1; 
	    	var c_end = result.indexOf(";",c_start);
	    	if (c_end == -1)
	    	{
	    		c_end = result.length;
	    	}
	    	return unescape(result.substring(c_start,c_end));
	    } 
	}
	return null;
}

//JSON General Methode

wCore.parseJSON = function(string)
{
	if (typeof(JSON) != 'undefined')
	{
		return JSON.parse(string);
	}
	else 
	{
		var Ci = Components.interfaces;
		var Cc = Components.classes;
		var nativeJSON = Cc["@mozilla.org/dom/json;1"].createInstance(Ci.nsIJSON);
		return nativeJSON.decode(string);
	}
};

wCore.stringifyJSON = function(value)
{
	if (typeof(JSON) != 'undefined')
	{
		return JSON.stringify(value);
	}
	else
	{
		var Ci = Components.interfaces;
		var Cc = Components.classes;
		var nativeJSON = Cc["@mozilla.org/dom/json;1"].createInstance(Ci.nsIJSON);
		return nativeJSON.encode(value);
	}
}

wCore.jsonCachedResult = [];

wCore.getCachedResult = function(url)
{
	var testTime = new Date().getTime() - 180000;
	var chachedItem = null;
	for (var i = 0; i< this.jsonCachedResult.length; i++)
	{
		chachedItem = this.jsonCachedResult[i];
		if (chachedItem.url == url)
		{
			if (chachedItem.created < testTime)
			{
				this.refreshCachedResult();
			}
			else
			{
				return chachedItem;
			}
		}
	}
	var chachedItem = {url : url, result: null, created : new Date().getTime()}
	this.jsonCachedResult.push(chachedItem);
	return chachedItem;
}

wCore.refreshCachedResult = function()
{
	var timelimite = new Date().getTime() - 180000;
	var newJsonCachedResult = [];
						
	for (var i = 0; i < this.jsonCachedResult.length; i++)
	{
		var chachedItem = this.jsonCachedResult[i];
		if (chachedItem.created > timelimite)
		{
			newJsonCachedResult.push(chachedItem);
		}
	}
	this.jsonCachedResult = newJsonCachedResult;
}

wCore.buildServerUrl = function(module, action, parameters)
{
	var url = Context.UIBASEURL + '/xul_controller.php?module=' + encodeURIComponent(module) + '&action=' + encodeURIComponent(action);
	var encParams = this.encodeParameters(parameters);
	if (encParams !== '') {url += '&' + encParams;}
	return url;
}

// TODO: remove
wCore.buildeServerUrl = wCore.buildServerUrl;

wCore.encodeParameters = function(parameters)
{
	if (parameters == null) {return '';}
	var encParams = [];
	for (var name in parameters) 
	{
		var value = parameters[name];
		if (value != null)
		{
			if (typeof(value) == 'object' && 'length' in value) 
			{
				for (var i=0; i<value.length; i++) {
					encParams.push(name + '[]=' + encodeURIComponent(value[i]));
				}
			} 
			else if (typeof(value) == 'object') 
			{
				for (var key in value)
				{
					encParams.push(name + '['+encodeURIComponent(key)+ ']=' + encodeURIComponent(value[key]));
				}
			}
			else if (typeof(value) != 'function') 
			{
				encParams.push(name + '=' + encodeURIComponent(value));
			}
	    }
	}
	return encParams.join('&');
}


wCore.executeJSON = function(module, action, parameters, callBack, noCache)
{
	noCache = (noCache == true);
	var requestUrl = this.buildServerUrl(module, action, noCache ? {} : parameters);
	var postData = null;
	var chachedItem = null;
	var useCallBack = (typeof(callBack) == "function");
	if (noCache)
	{
		postData = this.encodeParameters(parameters);
		if (postData == '') {postData = null;}
	}
	else
	{
		var chachedItem = this.getCachedResult(requestUrl);
		if (useCallBack) 
		{
			if (chachedItem.result != null)
			{
				callBack(chachedItem.result);
				return null;
			}
			if (chachedItem.callback)
			{
				chachedItem.callback.push(callBack);
				return null;
			}
			chachedItem.callback = [];
			chachedItem.callback.push(callBack);
		}
		else if (chachedItem.result != null)
		{
			return chachedItem.result;		
		}
	}
	wCore.debug('wCore.executeJSON(' + requestUrl + ')');
	var req = new XMLHttpRequest();
	req.open('POST', requestUrl, useCallBack);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.setRequestHeader('Content-Length', ((postData == null) ? 0 : postData.length));
	if (useCallBack)
	{
		req.onreadystatechange = function (aEvt) {  
			if (req.readyState == 4) {wCore.executeJSONComplet(req, chachedItem, callBack);}  
		};  
		req.send(postData);
		return req;
	}
	req.send(postData);
	return wCore.executeJSONComplet(req, chachedItem, callBack);
}

wCore.executeJSONComplet = function(req, chachedItem, callBack)
{
	var result = null;
	try 
	{
		//Abort
		if (req.status == 0) 
		{
			wCore.debug("Request Abort");
			return null;
		}
		
		if (req.responseText == "")
		{
			result = {status: 'ERROR', module: 'uixul', 	action: 'XulError', 
					contents: {popupAlert: false, errorMessage: "Empty content"}};
		}
		else
		{
			result = wCore.parseJSON(req.responseText);
		}				
	}
	catch (e)
	{
		wCore.error('executeJSONComplet', [req, chachedItem, callBack], e);
		result = {status: 'ERROR', module: 'uixul', 	action: 'XulError', 
			contents: {popupAlert: false, errorMessage: req.responseText}};
	}
	
	if (chachedItem !== null)
	{
		chachedItem.result = result;
		if (chachedItem.callback)
		{
			for(var i = 0; i < chachedItem.callback.length; i++)
			{
				try
				{
					chachedItem.callback[i](result);
				}
				catch (e)
				{
					wCore.error('executeJSONComplet CALLBACK', [req, i, chachedItem.callback[i]], e);
				}				
			}		
			chachedItem.callback = null;
		}
	}
	else if (callBack !== null)
	{
		callBack(result);
	}
	return result;	
}

wCore.uploadFile = function(nsIFile, parameters, callbackComplete)
{
	var tmp = null;
	var fileInput = Components.classes["@mozilla.org/network/file-input-stream;1"]
	         .createInstance(Components.interfaces.nsIFileInputStream);
	fileInput.init(nsIFile, 0x01, 0444, tmp);
	
	var fileContent = Components.classes["@mozilla.org/network/buffered-input-stream;1"]
	         .createInstance(Components.interfaces.nsIBufferedInputStream);
	fileContent.init(fileInput, 4096);
	
	var headerInput = Components.classes["@mozilla.org/io/string-input-stream;1"]
	         .createInstance(Components.interfaces.nsIStringInputStream);
	
	var headerContent = new String();
	headerContent += "\r\n";
	headerContent += "--111222111\r\nContent-disposition: form-data;name=\"addfile\"\r\n\r\n1";
	headerContent += "\r\n" + "--111222111\r\n";
	headerContent += "Content-disposition: form-data;name=\"filename\";filename=\"" + nsIFile.leafName + "\"\r\n";
	headerContent += "Content-Type: application/octet-stream\r\n";
	headerContent += "Content-Length: " + nsIFile.fileSize + "\r\n\r\n";
	headerInput.setData(headerContent, headerContent.length);
	
	var footerInput = Components.classes["@mozilla.org/io/string-input-stream;1"]
	        .createInstance(Components.interfaces.nsIStringInputStream);
	var footerContent = new String("\r\n--111222111--\r\n");
	footerInput.setData(footerContent, footerContent.length);
	
	var muxInput = Components.classes["@mozilla.org/io/multiplex-input-stream;1"]
	                .createInstance(Components.interfaces.nsIMultiplexInputStream);
	
	muxInput.appendStream(headerInput);
	muxInput.appendStream(fileContent);
	muxInput.appendStream(footerInput);
	
	var requestUrl = wCore.buildeServerUrl('uixul', 'UploadFile', parameters)
	//wCore.debug('startUploadFile.sendTo : ' + requestUrl);
	var req = new XMLHttpRequest();
	req.open('POST', requestUrl, true);
	var me = this;
	req.onreadystatechange = function (aEvt) 
	{  
		if (req.readyState == 4) 
		{
			return wCore.executeJSONComplet(req, null, callbackComplete);
		}
		return null;
	}
	req.setRequestHeader("Content-Length", (muxInput.available()-2));
	req.setRequestHeader("Content-Type", "multipart/form-data; boundary=111222111");
    req.send(muxInput);
    return req;
}