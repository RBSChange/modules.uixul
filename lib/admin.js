//Script global de l'interface d'administration

function onChangeLoaded(event)
{
	wCore.debug('onChangeLoaded');
	window.setTimeout(enableReloadInterface, 1000);
	window.setTimeout(sessionKeepAlive, 300000);
	onFragmentChange(event);
}

function sessionKeepAlive()
{
	try
	{
		// wCore.debug("sessionKeepAlive...");
		if (!checkUrl(Context.UIBASEURL + '/sessionKeepAlive.php'))
		{
			wCore.log('sessionKeepAlive not responding', INFO);
		}
	}
	catch (e)
	{
	}
	window.setTimeout(sessionKeepAlive, 300000);
}
function enableReloadInterface()
{
	// wCore.debug("enableReloadInterface...");
	try
	{
		var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
				.getService(Components.interfaces.nsIWindowMediator);
		var mainWindow = wm.getMostRecentWindow("navigator:browser");
		mainWindow.ChangeManager.test('[enableReloadInterface]' + Context.CHROME_BASEURL);
	}
	catch (e)
	{
		wCore.error('enableReloadInterface', [], e);
	}

	var clientVersion = getCookie("cacheversion");
	var serverVersion = getServerCacheVersion();

	if (clientVersion == null)
	{
		// assume that the first time, all is OK
		setCookie("cacheversion", serverVersion, 365);
		clientVersion = serverVersion;
	}

	var infobox = document.getElementById('reloadInterfaceNotification');
	if (infobox != null)
	{
		if (serverVersion != "" && serverVersion != clientVersion)
		{
			infobox.removeAttribute('collapsed');
		}
		else
		{
			infobox.setAttribute('collapsed', 'true');
		}
		window.setTimeout(enableReloadInterface, 30000);
	}
}

var confirmBeforeClosing = true;

function acceptCloseGlobal()
{
	confirmBeforeClosing = false;
}

function preventCloseGlobalWarning(e)
{
	if (confirmBeforeClosing)
	{
		var elt = document.getElementById('disconnectbutton');
		e.returnValue = elt.getAttribute('tmplabel');
	}
}

function disconnect(forceDisconnect)
{
	try
	{
		var elt = document.getElementById('disconnectbutton');
		var confirmLabel = elt.getAttribute("tmplabel");
		if (forceDisconnect || window.confirm(confirmLabel))
		{
			if (Context.CHROME_BASEURL)
			{
				acceptCloseGlobal();
				wCore.executeJSON('users', 'Logout', { access: 'back' }, null, true);
				window.close();
				return;
			}
		}
	}
	catch (e)
	{
		wCore.error('disconnect', [ forceDisconnect ], e);
	}
}

function getServerCacheVersion()
{
	var result = wCore.executeJSON('uixul', 'GetCacheVersion', {}, null, true);
	if (result.status === 'OK') { return result.contents.cacheVersion; }
	return "";
}

function clearCache()
{
	wCore.debug('clearCache');
	var serverVersion = getServerCacheVersion();
	setCookie("cacheversion", serverVersion, 365);
	var cacheClass = Components.classes["@mozilla.org/network/cache-service;1"];
	var service = cacheClass.getService(Components.interfaces.nsICacheService);
	try
	{
		service.evictEntries(Components.interfaces.nsICache.STORE_ON_DISK);
		service.evictEntries(Components.interfaces.nsICache.STORE_IN_MEMORY);
	}
	catch (exception)
	{
		wCore.error('clearCache', [], exception);
	}

	try
	{
		acceptCloseGlobal();
		confirmBeforeClosing = false;
		window.location.assign(window.location.toString());
		return;
	}
	catch (e)
	{
		wCore.error('clearCache', [], e);
	}
}

function getController()
{
	return document.getElementById('wcontroller');
}

function about()
{
	getController().openModalDialog(getController(), "aboutchange", {});
}

function openWebsite(elt)
{
	width = Math.max(800, Math.min(1024, Math.floor(screen.width * 0.8)));
	height = Math.floor(screen.height * 0.8);
	var left = Math.floor((screen.width - width) / 2);
	var top = Math.floor((screen.height - height) / 2);
	var popupWindow = window.open(elt.getAttribute('href'), "popup", "top=" + top + ", left="
			+ left + ", width=" + width + ", height=" + height
			+ ", menubar=yes, toolbar=yes, resizable=yes, scrollbars=yes");
	popupWindow.focus();
}

function portalredirect(url, login, password)
{
	try
	{
		acceptCloseGlobal();
		if (Context.CHROME_BASEURL)
		{

			try
			{
				var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"].getService(Components.interfaces.nsIWindowMediator);
				var mainWindow = wm.getMostRecentWindow("navigator:browser");

				var portalUrl = mainWindow.ChangeManager.portalRedirect(url, login, password);
				if (portalUrl != null)
				{
					window.location.replace(portalUrl);
				}
			}
			catch (e)
			{
				wCore.error('enableReloadInterface', [], e);
			}
		}
	}
	catch (e)
	{
		wCore.error('portalredirect', [ url ], e);
	}
}

function goToDashboard()
{
	loadModule('dashboard', null, null, true);
}

function changePassword()
{
	var controller = getController();
	controller.openModalDialog(controller, "changepassword", controller.getUserInfos());
}

function openControllerErrorPanel()
{
	getController().openErrorPanel();
}

// Global Administration function

function loadModule(moduleName, perspectiveName, locateDocumentId, onLoadAction)
{
	var module = showModule(moduleName);

	if (!module.getAttribute('name'))
	{
		if (locateDocumentId)
		{
			module.setAttribute('onload-locate-document', locateDocumentId);
		}

		if (onLoadAction)
		{
			module.setAttribute('onlocate-perform-action', onLoadAction);
		}

		module.setAttribute('name', moduleName);
	}
	else
	{
		if (locateDocumentId)
		{
			if (onLoadAction)
			{
				module.setAttribute('onlocate-perform-action', onLoadAction);
			}
			module.locateDocument(locateDocumentId);
		}
		else if (onLoadAction)
		{
			if (module.onLoadAction && (typeof (module.onLoadAction) == "function"))
			{
				try
				{
					module.onLoadAction();
				}
				catch (e)
				{
					wCore.error("loadModule", [ moduleName, perspectiveName, locateDocumentId, onLoadAction ], e);
				}
			}
			else if (typeof (onLoadAction) == "string"
					&& typeof (module[onLoadAction]) == "function")
			{
				try
				{
					module[onLoadAction]();
				}
				catch (e)
				{
					wCore.error("loadModule", [ moduleName, perspectiveName, locateDocumentId, onLoadAction ], e);
				}
			}
		}
	}
}

function locateDocumentInModule(documentId, moduleName)
{
	loadModule(moduleName, null, documentId);
}

function performActionOnDocumentInModule(actionName, documentId, moduleName)
{
	loadModule(moduleName, null, documentId, actionName);
}

function performActionOnModule(actionName, moduleName)
{
	loadModule(moduleName, null, null, actionName);
}

function openActionUri(uri, from)
{
	wCore.debug('openActionUri uri(' + uri + '), from(' + from + ')');
	var parts = uri.split(',');
	var moduleName = parts[0];
	var module = showModule(moduleName);
	module.setAttribute('name', moduleName);
	if (from)
	{
		module.setAttribute('fromURI', from);
	}
	getController().setAttribute('execute', uri);
}

function onFragmentChange(event)
{
	var hash = window.location.hash ;
	wCore.debug('onFragmentChange(' + hash + ')');
	if (hash.length > 2)
	{
		var suburi = hash.substr(1).split(',');
		if (suburi.length > 0)
		{
			var module = getModuleByName(suburi[0]);
			if (module !== null)
			{
				if (suburi.length == 1)
				{
					openActionUri(suburi[0] +',refresh');
				}
				else
				{
					openActionUri(hash.substr(1));
				}
			}
		}
	}
}

function actionUriOpened()
{
	getController().removeAttribute('execute');
}

function getModuleByName(moduleName)
{
	var elementId = 'wmodule_' + moduleName;
	return document.getElementById(elementId);
}

function showModule(moduleName)
{
	var module = getModuleByName(moduleName);
	getController().showModule(module);
	return module;
}

function getCurrentModule()
{
	var deck = document.getElementById("deck_wmodules");
	return deck.childNodes[deck.selectedIndex];
}

function getCurrentModuleName()
{
	var module = getCurrentModule();
	return module.id.split('_')[1];
}

function checkUrl(url)
{
	var req = new XMLHttpRequest();
	req.open('GET', url, false);
	req.send(null);
	if (req.status == 200) { return true; }
	return false;
}

window.addEventListener('load', onChangeLoaded, true);
window.addEventListener('beforeunload', preventCloseGlobalWarning, true);
window.addEventListener('hashchange', onFragmentChange, false);