function createXmlHttpRequest()
{
	if (typeof XMLHttpRequest != 'undefined')
	{
		return new XMLHttpRequest();
	}
	
	var xmlHttp = false;
	try
	{
		xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
	}
	catch (e)
	{
		try
		{
			xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch (E)
		{
			xmlHttp = false;
		}
	}

	return xmlHttp;
}

var wServerRequest_uid = 0;


/*
A class to easily handle server requests.
@author FredB
@date 2005-11-28
*/
function wServerRequest(url, httpMethod)
{

	// URL of the service to call
	this.url = url;
	
	this.uid = wServerRequest_uid++;

	// parameters
	this.urlArgs = [];

	// specific headers
	this.headers = {};

	// specific request data
	this.requestData = null;

	// specific xmlHttp object data
	this.objectData = null;

	// HTTP method to use
	if (httpMethod && httpMethod.toLowerCase() == 'get')
	{
		this.httpMethod = 'get';
	}
	else
	{
		this.httpMethod = 'post';
	}
	
	wCore.debug("wServerRequest.construct " + this.uid);
	
	// Progress handler
	this.progressHandler = null;

	// XmlHttpRequest object
	this.xmlHttp = null;

	/**
	 * Sets the request data.
	 */
	this.setRequestData = function(requestData)
	{
		this.requestData = requestData;
	}

	/**
	 * Sets the object data.
	 */
	this.setObjectData = function(objectData)
	{
		this.objectData = objectData;
	}

	/**
	 * Adds a parameter to the request.
	 */
	this.addParameter = function(name, value) {
		if (value != null)
		{
			// is Array ?
			if (typeof(value) != 'function')
			{
				if (typeof(value) == 'object' && 'push' in value)
				{
					var paramAppears = false;
					for (var i in value)
					{
						this.addParameter(name+'['+trim(i)+']', value[i]);
						paramAppears = true;
					}
					if (!paramAppears)
					{
						this.addParameter(name+'[]', '');
					}
				}
				else
				{
					this.urlArgs.push(trim(name) + '=' + encodeURIComponent(value));
				}
			}
		}
	}

	/**
	 * Sets the progress handler.
	 */
	this.setHandler = function(progressHandler)
	{
		this.progressHandler = progressHandler;
		if (this.xmlHttp)
		{
			this.xmlHttp.onreadystatechange = progressHandler;
		}
	}

	/**
	 * Adds a specific request header.
	 */
	this.addHeader = function(name, value)
	{
		this.headers[name] = value;
	}

	/**
	 * Gets the XmlHttpRequest object.
	 */
	this.getXmlHttpRequest = function()
	{
		return this.xmlHttp;
	}

	/**
	 * Sends the request to the server with the appropriate method.
	 */
	this.send = function(blocking)
	{
		if (this.xmlHttp == null || this.xmlHttp.readyState == 4)
		{
			var url = this.url;

			if (this.httpMethod == 'get' || this.requestData)
			{
				url = this.url + "?" + this.urlArgs.join('&');
			}

			if (arguments.length > 0 && blocking != false)
			{
				blocking = true;
			}
			this.xmlHttp = createXmlHttpRequest();

			if (this.objectData)
			{
			    this.xmlHttp.objectData = this.objectData;
			}

			this.xmlHttp.open(this.httpMethod, url, !blocking);

			if (!blocking)
			{
				if (this.progressHandler != null)
				{
					this.xmlHttp.onreadystatechange = this.progressHandler;
				}
			}

			var headerSet = false;
			for (var i in this.headers)
			{
			    headerSet = true;
			    this.xmlHttp.setRequestHeader(i, this.headers[i]);
			}

			if (this.httpMethod == 'get')
			{
				wCore.debug("wServerRequest.send " + this.uid + " GET:URL(" + url + ")");
				this.xmlHttp.send(null);
			}
			else
			{
				// POST
				if (!headerSet)
				{
				    this.xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				}
				if (!this.requestData)
				{
				    this.requestData = this.urlArgs.join('&');
				}
				wCore.debug("wServerRequest.send " + this.uid + " POST:URL(" + url + ")");
				wCore.debug("wServerRequest.send " + this.uid + " POST:DATA(" + this.requestData + ")");
			    this.xmlHttp.send(this.requestData);
			}

			if (blocking)
			{
				if (this.progressHandler != null)
				{
					this.progressHandler(this);
				}
			}
		}
	}
	
	this.getTextData = function ()
	{
		var req = this.getXmlHttpRequest();
		
		if (req)
		{		
			return req.responseText;
	    }
	    
	    return null;
	}

	this.getXmlData = function ()
	{
		var xmlText = this.getTextData();
	
		try
        {
			if (xmlText)
			{
		        if (xmlText.indexOf('<?xml') == 0)
		        {
		            xmlText = xmlText.substring(xmlText.indexOf('>')+1, xmlText.length);
		        }
		
		        xmlText = trim(xmlText);
		
		        return new XML(xmlText);
		    }
		}
		catch (e)
		{
			wCore.error("wServerRequest.getXmlData", [], e);
		}
	    
	    return null;
	}

	this.abort = function()
	{
		// intbonjf 2006-10-31:
		// does not throw a JS error anymore when calling abort() on a request
		// that does not really exist yet.
		if (this.xmlHttp)
		{
			this.xmlHttp.abort();
		}
	}
}
