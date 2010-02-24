/**
 * wToolkit object provides a set of generic
 * functionalities, such as :
 *     - Simple event handling.
 *     - Simple boxObject manipulation.
 *     - Overriding methods for many JavaScript global objects.
 */

var wToolkit = {};

// Basic XML namespaces :
const XUL_XMLNS = "http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul";
const HTML_XMLNS = "http://www.w3.org/1999/xhtml";

// Event modifiers :
const MODIFIER_NONE = 0;
const MODIFIER_CTRL = 1;
const MODIFIER_ALT = 2;
const MODIFIER_SHIFT = 4;
const MODIFIER_META = 8;

// Boolean operator used for Array and Object conversion :
const BOOLEAN_AND = 1;
const BOOLEAN_OR = 2;

// Misc Mozilla stuff :
const BOUNDARY = "111222111";

const MULTI = "@mozilla.org/io/multiplex-input-stream;1";
const FINPUT = "@mozilla.org/network/file-input-stream;1";
const STRINGIS = "@mozilla.org/io/string-input-stream;1";
const BUFFERED = "@mozilla.org/network/buffered-input-stream;1";

const nsIMultiplexInputStream = Components.interfaces.nsIMultiplexInputStream;
const nsIFileInputStream = Components.interfaces.nsIFileInputStream;
const nsIStringInputStream = Components.interfaces.nsIStringInputStream;
const nsIBufferedInputStream = Components.interfaces.nsIBufferedInputStream;

try
{
	wToolkit._storage = globalStorage[Context.UIHOST];
}
catch (e)
{
	wToolkit._storage = null;
}

wToolkit.getStorageItem = function (key)
{
	var item = null
	if (wToolkit._storage)
	{
		storageItem = wToolkit._storage.getItem(key);
		if (storageItem)
		{
			item = storageItem.toString();
			if (bool = item.match(/^#BOOL#(.*)/i))
			{
				switch (bool[1])
				{
					case "true":
						item = true;
						break;
						
					default:
						item = false;
						break;
				}
			}
			else if (object = item.match(/^#OBJ#(.*)/i))
			{
				item = assocStringToObject(object[1]);
			}
		}
	}
	return item;
}

wToolkit.setStorageItem = function (key, value)
{
	if (wToolkit._storage)
	{
		switch (typeof value)
        {
            case "object":        
            	value = "#OBJ#" + objectToAssocString(value);    
                break;

            case "boolean":
            	if (value)
            	{	
            		value = "#BOOL#true";
            	}  
            	else
            	{
            		value = "#BOOL#false";
            	}              
                break;
        }
        
		wToolkit._storage.setItem(key, value);
	}
}

wToolkit.removeStorageItem = function (key)
{
	if (wToolkit._storage)
	{
		item = wToolkit._storage.removeItem(key)
	}
}

/**
 * getEventModifier() returns an integer representing
 * a bit field of key modifiers involved in the given event.
 *
 * @param  event  DOM event.
 *
 * @returns integer
 */
wToolkit.getEventModifier = function (event)
{
    try
    {
        var modifier = MODIFIER_NONE;
        if (event.ctrlKey)
        {
           modifier += MODIFIER_CTRL;
        }
        if (event.altKey)
        {
           modifier += MODIFIER_ALT;
        }
        if (event.shiftKey)
        {
           modifier += MODIFIER_SHIFT;
        }
        if (event.metaKey)
        {
           modifier += MODIFIER_META;
        }
        return modifier;
    }
    catch (e)
    {
        wCore.error("wToolkit.getEventModifier", [event], e);
    }
};

/**
 * getScrollBoxObject() returns the nsIScrollBoxObject
 * interface for the given DOM node.
 *
 * @param  UIContainer  DOM node.
 *
 * @returns nsIScrollBoxObject interface.
 */
wToolkit.getScrollBoxObject = function (UIContainer)
{
    var scrollBoxObject = undefined;
    try
    {
        scrollBoxObject = UIContainer.boxObject.QueryInterface(
            Components.interfaces.nsIScrollBoxObject
        );
    }
    catch (e)
    {
    	wCore.error("wToolkit.getScrollBoxObject", [UIContainer], e);
    }
    return scrollBoxObject;
};

/**
 * getScrollPosition() returns the horizontal
 * and vertical positions of the scrollBoxObject
 * element for the given DOM node.
 *
 * @param  UIContainer  DOM node.
 *
 * @returns object { x: integer, y: integer }.
 */
wToolkit.getScrollPosition = function (UIContainer)
{
    var position =
    {
        x: 0,
        y: 0
    };
    try
    {
        var x = {};
        var y = {};
        wToolkit.getScrollBoxObject(UIContainer).getPosition(x, y);
        position =
        {
            x: x.value,
            y: y.value
        };
    }
    catch (e)
    {
    	wCore.error("wToolkit.getScrollPosition", [UIContainer], e);
    }
    return position;
};

wToolkit.getUserDirectory = function ()
{
    try
    {
        const DIR_SERVICE = new Components.Constructor("@mozilla.org/file/directory_service;1", "nsIProperties");
        var path = (new DIR_SERVICE()).get("ProfD", Components.interfaces.nsIFile).path;
        if (path.search(/\\/) != -1)
        {
            path = path + "\\";
        }
        else
        {
            path = path + "/";
        }
        return path;
    }
    catch (e)
    {
        wCore.error("wToolkit.getUserDirectory", [], e);
    }
};

wToolkit.uploadStack = [];

wToolkit.startUploadFile = function (file, target, sender)
{
    try
    {
        var module = sender.getModule();
        var controller = module.getController();
        // INIT FILE DATA :
        var muxInput = Components.classes[MULTI].createInstance(nsIMultiplexInputStream);
        var tmp = null;
        var fileInput = Components.classes[FINPUT].createInstance(nsIFileInputStream);
        fileInput.init(file, 0x01, 0444, tmp);
        var fileContent = Components.classes[BUFFERED].createInstance(nsIBufferedInputStream);
        fileContent.init(fileInput, 4096);
        var headerInput = Components.classes[STRINGIS].createInstance(nsIStringInputStream);
        var headerContent = new String();
        headerContent += "\r\n";
        headerContent += "--" + BOUNDARY + "\r\nContent-disposition: form-data;name=\"addfile\"\r\n\r\n1";
        headerContent += "\r\n" + "--" + BOUNDARY + "\r\n";
        headerContent += "Content-disposition: form-data;name=\"filename\";filename=\"" + file.leafName + "\"\r\n";
        headerContent += "Content-Type: application/octet-stream\r\n";
        headerContent += "Content-Length: " + file.fileSize + "\r\n\r\n";
        headerInput.setData(headerContent, headerContent.length);
        var footerInput = Components.classes[STRINGIS].createInstance(nsIStringInputStream);
        var footerContent = new String("\r\n--" + BOUNDARY + "--\r\n");
        footerInput.setData(footerContent, footerContent.length);
        muxInput.appendStream(headerInput);
        muxInput.appendStream(fileContent);
        muxInput.appendStream(footerInput);
        // INIT REQUEST :
        var request = new wServerRequest(controller.controllerUrl, 'post');
		request.label = "&modules.media.backoffice.progress;";
		request.addParameter('module', 'media');
		request.addParameter('action', 'Upload');
		request.addParameter('filename', file.leafName);
		request.addParameter('lang', target.component.(@name=="lang").toString());
		request.addParameter('parentref', target.component.(@name=="id").toString());
		
        request.addHeader("Content-Length", (muxInput.available()-2));
		request.addHeader("Content-Type", "multipart/form-data; boundary=" + BOUNDARY);
		request.setRequestData(muxInput);
		request.setObjectData({
		    fileName: file.leafName
		});
		request.senderObject = sender;
		request.setHandler(function(){ controller.executeHandler(request); });
		try
		{
			controller.enqueue(request);
			document.getElementById('uploaderBox').collapsed = false;
	      	var fileList = document.getElementById('uploaderList');
	      	//document.getElementById('uploaderClearButton').disabled = true;
	      	var row = document.createElement('listitem');
            var cell = document.createElement('listcell');
            cell.setAttribute('label', file.leafName);
            cell.setAttribute('value', file.leafName);
            row.appendChild(cell);
            cell = document.createElement('listcell');
            cell.setAttribute('label', 'Progress');
            cell.setAttribute('flex', '1');
            cell.setAttribute('value', '');
            var newHbox = document.createElement('hbox');
            newHbox.setAttribute('align', 'center');
            var newProgressChild = document.createElement('progressmeter');
            newProgressChild.setAttribute('mode', 'undetermined');
            newProgressChild.setAttribute('flex', '1');
            newHbox.appendChild(newProgressChild);
            var stateCol = document.createElement('image');
            stateCol.setAttribute('src', Context.UIBASEURL + '/icons/small/clock.png');
            newHbox.appendChild(stateCol);
            cell.appendChild(newHbox);
            row.appendChild(cell);
            fileList.appendChild(row);
            wToolkit.uploadStack.push({
                fileName: file.leafName,
                progressMeter: newProgressChild
            });
			return true;
		}
		catch (e)
		{
			wCore.error("wToolkit.startUploadFile", [file, target, sender], e);
			return false;
		}
    }
    catch (e)
    {
    	wCore.error("wToolkit.startUploadFile", [file, target, sender], e);
    }
};

wToolkit.successUploadFile = function (fileName, sender)
{
    try
    {
        var progressMeter = null;
        var nbPendingItems = 0;

        for (var i = 0; i < wToolkit.uploadStack.length; i++)
        {
            if (wToolkit.uploadStack[i])
            {
                nbPendingItems++;
            }
        }

        for (var i = 0; i < wToolkit.uploadStack.length; i++)
        {
            if (wToolkit.uploadStack[i]
            && wToolkit.uploadStack[i].fileName
            && (wToolkit.uploadStack[i].fileName == fileName))
            {
                progressMeter = wToolkit.uploadStack[i].progressMeter;
                wToolkit.uploadStack[i] = null;
                nbPendingItems--;
                break;
            }
        }

        progressMeter.setAttribute('mode', 'determined');
    	progressMeter.setAttribute('value', '100%');
    	progressMeter.nextSibling.setAttribute('src', Context.UIBASEURL + '/icons/small/check.png');
    }
    catch (e)
    {
        wCore.error("wToolkit.successUploadFile", [fileName, sender], e);
    }
};

wToolkit.errorUploadFile = function (fileName, message, sender)
{
    try
    {
    	if (fileName == null || fileName == '')
    	{
    		wToolkit.clearUploader();
    		return;
    	} 
        var progressMeter = null;
        var nbPendingItems = 0;

        for (var i = 0; i < wToolkit.uploadStack.length; i++)
        {
            if (wToolkit.uploadStack[i])
            {
                nbPendingItems++;
            }
        }

        for (var i = 0; i < wToolkit.uploadStack.length; i++)
        {
            if (wToolkit.uploadStack[i]
            && wToolkit.uploadStack[i].fileName
            && (wToolkit.uploadStack[i].fileName == fileName))
            {
                progressMeter = wToolkit.uploadStack[i].progressMeter;
                wToolkit.uploadStack[i] = null;
                nbPendingItems--;
                break;
            }
        }

        progressMeter.setAttribute('mode', 'determined');
    	progressMeter.setAttribute('value', '0%');
    	progressMeter.nextSibling.setAttribute('collapsed', 'true');
        var newErrorLabel = document.createElement('label');
		newErrorLabel.setAttribute('value', message);
		newErrorLabel.setAttribute('class', 'warning');
		var progressParent = progressMeter.parentNode;
		progressParent.replaceChild(newErrorLabel, progressParent.firstChild);
    }
    catch (e)
    {
    	wCore.error("wToolkit.errorUploadFile", [fileName, message, sender], e);
    }
};

wToolkit.clearUploader = function ()
{
	try
	{
		var fileList = document.getElementById('uploaderList');
		var rowCount = fileList.getRowCount();
		for (var i = 0; i < rowCount; i++ ) {
			fileList.removeItemAt(0);
		}
		document.getElementById('uploaderBox').collapsed = true;
	}
    catch (e)
    {
    	wCore.error("wToolkit.clearUploader", [], e);
    }
};

wToolkit.writeFile = function (filepath, data)
{
    try
    {
        var file = Components.classes["@mozilla.org/file/local;1"]
            .createInstance(Components.interfaces.nsILocalFile);
        file.initWithPath(filepath);
        if (file.exists() == false)
        {
            file.create(Components.interfaces.nsIFile.NORMAL_FILE_TYPE, 420);
        }
        var outputStream = Components.classes["@mozilla.org/network/file-output-stream;1"]
            .createInstance(Components.interfaces.nsIFileOutputStream);
        outputStream.init(file, 0x04 | 0x08 | 0x20, 420, 0);
        outputStream.write(data, data.length );
        outputStream.close();
        return true;
    }
    catch (e)
    {
        wCore.error("wToolkit.writeFile", [filepath, data], e);
    }
};

wToolkit.readFile = function (filepath)
{
    try
    {
        var file = Components.classes["@mozilla.org/file/local;1"]
            .createInstance(Components.interfaces.nsILocalFile);
        file.initWithPath(filepath);
        if (file.exists() == true)
        {
            var inputStream = Components.classes["@mozilla.org/network/file-input-stream;1"]
                .createInstance(Components.interfaces.nsIFileInputStream);
            inputStream.init(file, 0x01, 00004, null);
            var scriptableInputStream = Components.classes["@mozilla.org/scriptableinputstream;1"]
                .createInstance(Components.interfaces.nsIScriptableInputStream);
            scriptableInputStream.init(inputStream);
            return scriptableInputStream.read(scriptableInputStream.available());
            }
        return false;
    }
    catch (e)
    {
        wCore.error("wToolkit.readFile", [filepath], e);
    }
};

/***/

wToolkit.fitToContent = function (element, offset)
{
    if (element.boxObject)
    {
        var height = element.boxObject.height;
    }
    else
    {
        var height = element.offsetHeight;
    }
    window.resizeTo(window.innerWidth, height + offset);
};

wToolkit.dialogParam = null;

wToolkit.dialogReturnValue = false;

wToolkit.setDialogParam = function (value)
{
	if (window.opener && window.opener.window && window.opener.window.wToolkit)
    {
        window.opener.window.wToolkit.dialogParam = value;
    }
    else
    {
        wToolkit.dialogParam = value;
    }
};

wToolkit.setDialogReturnValue = function (value)
{
	if (window.opener && window.opener.window && window.opener.window.wToolkit)
    {
        window.opener.window.wToolkit.dialogReturnValue = value;
    }
    else
    {
        wToolkit.dialogReturnValue = value;
    }
};

wToolkit.getDialogParam = function ()
{
    if (window.opener && window.opener.window && window.opener.window.wToolkit)
    {
        return window.opener.window.wToolkit.dialogParam;
    }
    return wToolkit.dialogParam;
};

wToolkit.getDialogReturnValue = function ()
{
	if (window.opener && window.opener.window && window.opener.window.wToolkit)
    {
        return window.opener.window.wToolkit.dialogReturnValue;
    }
    return wToolkit.dialogReturnValue;
};

//wToolkit.buildXulURL(module, action, urlParams, signedType([xul], html))
wToolkit.buildXulURL = function(module, action, urlParams, signedType)
{
	var url = Context.CHROME_BASEURL + "/module=" + module + "&action=" + action;
	
	if (typeof(urlParams) == 'object')
	{
		for (var i in urlParams)
		{
		    try
		    {
	   			if (typeof(urlParams[i]) == 'object' && 'push' in urlParams[i])
	   			{
	   				var paramAppears = false;
	
	   				for (var j in urlParams[i])
	   				{
	   				    url = url + "&" + i + '[' + trim(j) + ']' + "=" + encodeURIComponent(urlParams[i][j]);
	   					paramAppears = true;
	   				}
	
	   				if (!paramAppears)
	   				{
	   				    url = url + "&" + i + '[]' + "=";
	   				}
	   			}
	   			else
	   			{
	   			    url = url + "&" + trim(i) + "=" + encodeURIComponent(urlParams[i]);
	   			}
		    }
		    catch (e)
	   	    {
	               url = url + "&" + trim(i) + "=" + encodeURIComponent(urlParams[i]);
	   	    }
		}
	}
	return url;		
};

wToolkit.dialog = function (module, action, urlParams, windowParams, signed, nomodal)
{
	try
	{
	    var hasPrivileges = true;

		var url = this.buildXulURL(module, action, urlParams, signed);

		var finalWindowParams = [];

		finalWindowParams["name"] = "DialogWindow" + new Date().getTime();

		if (hasPrivileges)
		{
		    finalWindowParams["dialog"] = "yes";
		}
		else
		{
		    finalWindowParams["dialog"] = "no";
		}

		finalWindowParams["alwaysRaised"] = "yes";
		finalWindowParams["resizable"] = "yes";
		finalWindowParams["scrollbars"] = "yes";
		finalWindowParams["width"] = "450";
		finalWindowParams["height"] = "500";
		finalWindowParams["status"] = "no";

		for (var i in windowParams)
		{
		    if ((i == "width") && (windowParams[i] == "auto"))
		    {
		        windowParams[i] = Math.floor(screen.width / 2);
		    }

		    if ((i == "height") && (windowParams[i] == "auto"))
		    {
		        windowParams[i] = Math.floor(screen.height / 2);
		    }

			finalWindowParams[i] = windowParams[i];
		}

		if (!finalWindowParams['left'])
		{
		    finalWindowParams['left'] = Math.floor( (screen.width - parseInt(finalWindowParams['width'])) / 2);
		}

		if (!finalWindowParams['top'])
		{
            finalWindowParams['top'] = Math.floor( (screen.height - parseInt(finalWindowParams['height'])) / 2);
		}

		var openerwindow = window;
		wToolkit.dialogReturnValue = true;
		
		if (nomodal || !hasPrivileges)
		{
    		var windowParamsString = "modal=no";
		}
		else if (hasPrivileges)
		{
		    var windowParamsString = "modal=yes";
		    if (openerwindow.constructor != ChromeWindow)
		    {
			    var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
				                            .getService(Components.interfaces.nsIWindowMediator);
				openerwindow = wm.getMostRecentWindow("navigator:browser");
				openerwindow.wToolkit = {};
		    }
		}
		
		if (wToolkit !== openerwindow.wToolkit)
		{
			openerwindow.wToolkit.dialogReturnValue = wToolkit.dialogReturnValue;
			openerwindow.wToolkit.dialogParam = wToolkit.dialogParam;
			openerwindow.wToolkit.opener = window;
		}
		
		finalWindowParams["close"] = "yes";
		finalWindowParams["titlebar"] = "yes";

		for (var i in finalWindowParams)
		{
		    if (i != "name")
		    {
			     windowParamsString = windowParamsString + ", " + i + "=" + finalWindowParams[i];
		    }
		}

		wCore.debug('url:' + url);
		wCore.debug('windowParamsString:' + windowParamsString);
		var promptWindow = openerwindow.open(url, finalWindowParams["name"], windowParamsString);
		return wToolkit.getDialogReturnValue();
	}
	catch (e)
	{
		wCore.error('wToolkit.dialog', [module, action, urlParams, windowParams, signed, nomodal], e);
	    return false;
	}
};

wToolkit.openlink = function (module, action, urlParams)
{
	try 
	{
	    url = wCore.buildeServerUrl(module, action, urlParams);
	    this.downloadurl(url);
	}
	catch (e)
	{
		wCore.error('wToolkit.openlink', [module, action, urlParams], e);
	    return false;
	}
};

wToolkit.downloadurl = function (url)
{
	try 
	{
	 	var iFrame = document.getElementById('open-link-iframe');
		iFrame.setAttribute('src', ''); // This is required to be able to download the same URL two times consecutively.
		iFrame.setAttribute('src', url);
	}
	catch (e)
	{
		wCore.error('wToolkit.downloadurl', [url], e);
	    return false;
	}
};

wToolkit.openurl = function (url)
{
	window.open(url);
};

wToolkit.printFrame = function (frame)
{
    try
    {
	var wincontent = new XPCNativeWrapper(frame.contentWindow);
       	wincontent.print();
    }
    catch (e)
    {
        wCore.error("wToolkit.printFrame", [frame], e);
    }
}

wToolkit.OK    = "ok";
wToolkit.ERROR = "error";
wToolkit.INFO  = "info";

wToolkit.SMALL   = "small";
wToolkit.BIG     = "big";
wToolkit.COMMAND = "command";
wToolkit.NORMAL  = "normal";

wToolkit.notificationTimeout = null;

wToolkit.setNotificationMessage = function(message, type, timeout)
{
	if (type == wToolkit.ERROR)
	{
		try
		{
			var controller = getController();
			controller.openErrorPanel(message, '');
			return;
		}
		catch (e)
		{
			wCore.error("wToolkit.setNotificationMessage", [message, type, timeout], e);
		}
	}

	var mainWindow = window.QueryInterface(Components.interfaces.nsIInterfaceRequestor)
    		.getInterface(Components.interfaces.nsIWebNavigation)
    		.QueryInterface(Components.interfaces.nsIDocShellTreeItem)
    		.rootTreeItem
    		.QueryInterface(Components.interfaces.nsIInterfaceRequestor)
    		.getInterface(Components.interfaces.nsIDOMWindow); 

	var bar = mainWindow.document.getElementById('statusbar-change-status');
	if (bar == null)
	{
		bar = mainWindow.document.createElementNS('http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul', 'statusbarpanel');
		bar.setAttribute('id', 'statusbar-change-status');
		bar.setAttribute('class', 'statusbarpanel-iconic-text');
		var toolbar = mainWindow.document.getElementById('status-bar');
		var text1 = mainWindow.document.getElementById('statusbar-display');
		toolbar.insertBefore(bar, text1);
	}
	bar.label = message;
	switch (type)
	{
		case  wToolkit.OK :	bar.image = wToolkit.getIcon('check', 'small'); break;
		case  wToolkit.ERROR :	bar.image = wToolkit.getIcon('error', 'small'); break;
		default : bar.image = wToolkit.getIcon('information', 'small'); break;
	}	
	if (wToolkit.notificationTimeout != null)
	{
		window.clearTimeout(wToolkit.notificationTimeout);
	}
	if (timeout)
	{
		wToolkit.notificationTimeout = window.setTimeout("wToolkit.clearNotificationMessage()", timeout);
	}
}


wToolkit.clearNotificationMessage = function()
{
	window.clearTimeout(wToolkit.notificationTimeout);
	wToolkit.notificationTimeout = null;
	
	var mainWindow = window.QueryInterface(Components.interfaces.nsIInterfaceRequestor)
	.getInterface(Components.interfaces.nsIWebNavigation)
	.QueryInterface(Components.interfaces.nsIDocShellTreeItem)
	.rootTreeItem
	.QueryInterface(Components.interfaces.nsIInterfaceRequestor)
	.getInterface(Components.interfaces.nsIDOMWindow); 

	var bar = mainWindow.document.getElementById('statusbar-change-status');
	bar.label = "";
	bar.image = "";
}


wToolkit.getIcon = function(icon, size)
{
	if (size != wToolkit.SMALL && size != wToolkit.NORMAL && size != wToolkit.BIG && size != wToolkit.COMMAND)
	{
		size = wToolkit.SMALL;
	}
	return Context.UIBASEURL + "/icons/"+size+"/"+icon+".png";
}

/***/

function array_unique(array)
{
    try
    {
        var newArray = [];
        var existingItems = {};
        var prefix = String(Math.random() * 9e9);
        for (var i = 0; i < array.length; ++i)
        {
            if (!existingItems[prefix + array[i]])
            {
                newArray.push(array[i]);
                existingItems[prefix + array[i]] = true;
            }
        }
        return newArray;
    }
    catch (e)
    {
        wCore.error("wToolkit.array_unique", [array], e);
    }
}

function parseBoolean(mixed, operator)
{
    try
    {
        var bool = true;
        switch (typeof mixed)
        {
            case "object":
                for (var i in mixed)
        {
            switch (operator) {
                case BOOLEAN_OR:
                            bool = (bool || parseBoolean(mixed[i]));
                    break;

                case BOOLEAN_AND:
                default:
                            bool = (bool && parseBoolean(mixed[i]));
                    break;
            }
        }
                break;

            case "boolean":
                bool = mixed;
                break;

            case "number":
                bool = (mixed != 0);
                break;

            case "string":
                mixed = mixed.toLowerCase();
                switch (mixed)
                {
                    case "true":
                    case "on":
                    case "1":
                        bool = true;
                        break;

                    case "false":
                    case "off":
                    case "0":
                    case "":
                        bool = false;
                        break;
                }
                break;
        }
        return bool;
    }
    catch (e)
    {
        wCore.error("wToolkit.parseBoolean", [mixed, operator], e);
    }
}

function objectToAssocString (object)
{
    try
    {
        var assocString = "";
        for (var i in object)
        {
            var property = trim(i);
            var value = trim(object[i]);
            if ((property.length > 0)
            && (value.length > 0))
            {
                assocString += property + ": " + value + ";";
            }
        }
        return assocString;
    }
    catch (e)
    {
        wCore.error("wToolkit.objectToAssocString", [object], e);
    }
}

function assocStringToObject (string)
{
    try
    {
        var val = string;
        var object = {};
        var asArray = val.split(";");
        var hasContent = false;
        for (var i = 0; i < asArray.length; i++)
        {
            if (asArray[i])
            {
                var declaration = asArray[i].split(":");
                var property = trim(declaration.shift());
                var value = trim(declaration.join(":"));
                    if ((property.length > 0)
                    && (value.length > 0))
                    {
                        object[property] = value;
                        hasContent = true;
                }
            }
        }
        if (hasContent)
        {
            return object;
        }
        return null;
    }
    catch (e)
    {
        wCore.error("wToolkit.assocStringToObject", [string], e);
    }
}

function dec2hex (n)
{
    n = parseInt(n);
    var c = 'ABCDEF';
    var b = n / 16;
    var r = n % 16;
    b = b - (r / 16);
    b = ((b>=0) && (b<=9)) ? b : c.charAt(b-10);
    return ((r>=0) && (r<=9)) ? b+''+r : b+''+c.charAt(r-10);
}

function hex2dec (s)
{
    return parseInt(s, 16);
}
