/**
 * wDragAndDrop object provides a simple and binding-oriented
 * interface to handle Drag'n drop functionalities.
 *
 * The binding-side of drag'n drop functionnalities is implemented
 * by the wBox binding.
 *
 * An element is draggable since its "candDrag" attribute is set to "true".
 *
 * An element is droppable (ie somethig can be dropped on it) since
 * its "candDrop" attribute is set to "any" or to a space-separated list
 * of supported flavours (ex: "text/html text/unicode application/x-moz-file").
 *
 * A *draggable* element MUST declare the set of data it conveys,
 * as an array of objects defining flavours and their related data,
 * for example :
 *    [
 *          {
 *              flavour: "text/html",
 *              data: "<div>simple <em>html<em/> text</div>"
 *          },
 *          {
 *              flavour: "text/xml",
 *              data: "<node>simple xml text</node>"
 *          },
 *          {
 *              flavour: "some/object",
 *              data: someObject
 *          }
 *    ]
 *
 *     2 - By overriding the "dragData" binding's property's getter and setter.
 *
 *     3 - By overriding the "initDragData()" binding's method.
 *
 * Other optionnal binding's methods are available (in order of call
 * in the drag'n drop process) :
 *     - canDrag()
 *     - onDragStart()
 *     - canDrop()
 *     - getSupportedFlavours()
 *     - onDragOver()
 *     - onDragOut()
 *     - onDragEnd()
 */

var wDragAndDrop = {};

// Dragged object relative position :
const POSITION_INVALID = -1;
const POSITION_CENTER = 0;
const POSITION_TOP = 1;
const POSITION_RIGHT = 2;
const POSITION_BOTTOM = 3;
const POSITION_LEFT = 4;

// Dragged element for the current dragging session :
wDragAndDrop.draggedElement = null;

// Dragged dataset for the current dragging session :
wDragAndDrop.draggedData = null;

// Latest event throwed by the current dragging session :
wDragAndDrop.currentEvent = null;

// Flag to tell us if the current dragging session is done :
wDragAndDrop.done = false;

// Flag to tell us if a dragging session is in process :
wDragAndDrop.inProcess = false;

// Flag to tell us if the dragging session has just ended :
wDragAndDrop.hasJustEnded = false;

// Flag to tell us if external data have been integrated :
wDragAndDrop.preventDoubleImport = false;

// Listeners :
wDragAndDrop.listeners = [];

// dragSessionEnded Listeners
wDragAndDrop.dragSessionEndedListeners = [];

wDragAndDrop.observer =
{
  onDragStart: function (event, transferData, action)
  {
    try
    {
        wDragAndDrop.currentEvent = event;
        if (event.originalTarget && event.originalTarget.tagName != undefined && (event.originalTarget.tagName.toLowerCase() == 'xul:thumb'))
        {
            wDragAndDrop.draggedElement = null;
            wDragAndDrop.draggedData = null;
            wDragAndDrop.done = false;
            event.preventDefault();
            return false;
        }
        var dragStarted = false;
        if (("canDrag" in event.currentTarget)
        && (event.currentTarget.canDrag()))
        {
            if ("initDragData" in event.currentTarget)
            {
                var dragData = event.currentTarget.initDragData();
                if (dragData)
                {
                    dragStarted = true;
                    wDragAndDrop.inProcess = true;
                    wDragAndDrop.draggedElement = event.currentTarget;
                    wDragAndDrop.draggedData = dragData;
                    wDragAndDrop.done = false;
                    transferData.data = new TransferData();
                    for (var i = 0; i < dragData.length; i++)
                    {
                        transferData.data.addDataForFlavour(dragData[i].flavour, dragData[i].data);
                    }
                    if ("onDragStart" in event.currentTarget)
                    {
                        event.currentTarget.onDragStart();
                    }
                    /**
                     * This "mousedown" handler is used to properly cancel an aborted drag'n drop session.
                     * Since we can't tell the difference between a dragExit event and the release
                     * of the mouse button over an element that don't support dropping,
                     * and since no "mouseup" event is fired during the drag'n drop session, this is
                     * the only way to know that the current drag'n drop session has ended.
                     */
                    wCore.addEventListener(window, "mousedown", function(event) {
                        wDragAndDrop.endDragSession(event);
                    }, true);
                }
            }
        }
        if (!dragStarted)
        {
            wDragAndDrop.draggedElement = null;
            wDragAndDrop.draggedData = null;
            wDragAndDrop.done = false;
            event.preventDefault();
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.onDragStart", [event, transferData, action], e);
    }
  },

  getSupportedFlavours : function (event)
  {
    try
    {
        wDragAndDrop.currentEvent = event;
        var flavours = new FlavourSet();        
        if ("getSupportedFlavours" in event.currentTarget)
        {
            var supportedFlavours = event.currentTarget.getSupportedFlavours(wDragAndDrop.draggedElement, wDragAndDrop.draggedData);
            
            if (supportedFlavours)
            {
                if ((supportedFlavours.length == 1)
                && (supportedFlavours[0] == "any")
                && (wDragAndDrop.draggedData))
                {
                    supportedFlavours = [];
                    for (var i = 0; i < wDragAndDrop.draggedData.length; i++) {
                        supportedFlavours.push(wDragAndDrop.draggedData[i].flavour);
                    }
                }
                for (var i = 0; i < supportedFlavours.length; i++)
                {
                    switch (supportedFlavours[i]) {
                        case "application/x-moz-file":
                            var flavourInterface = "nsIFile";
                            break;

                        default:
                            var flavourInterface = undefined;
                            break;
                    }
                    flavours.appendFlavour(supportedFlavours[i], flavourInterface);
                }
            }
        }
        return flavours;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.getSupportedFlavours", [event], e);
    }
  },

  onDragOver: function(event, flavour, session)
  {
    try
    {
        wDragAndDrop.currentEvent = event;
        if ("onDragOver" in event.currentTarget)
        {
            event.currentTarget.onDragOver(wDragAndDrop.draggedElement, wDragAndDrop.draggedData);
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.onDragOver", [event, flavour, session], e);
    }
  },

  onDragExit: function(event, flavour, session)
  {
    try
    {
        wDragAndDrop.currentEvent = event;
        if (("onDragOut" in event.currentTarget)
        && (wDragAndDrop.done == false))
        {
            event.currentTarget.onDragOut(wDragAndDrop.draggedElement, wDragAndDrop.draggedData);
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.onDragExit", [event, flavour, session], e);
    }
  },

  onDrop: function(event, dropdata, session)
  {
	try
    {
       wDragAndDrop.currentEvent = event;
        wDragAndDrop.inProcess = false;
        if (("onDrop" in event.currentTarget)
        && (wDragAndDrop.done == false))
        {
        	wDragAndDrop.done = true;
            var dropResult = event.currentTarget.onDrop(wDragAndDrop.draggedElement, wDragAndDrop.draggedData);
            try
            {
                if ("onDragEnd" in wDragAndDrop.draggedElement)
                {
                	wDragAndDrop.draggedElement.onDragEnd(event.currentTarget, dropResult);
                }
            }
            catch (e)
            {
            	wCore.error("wDragAndDrop.onDrop", [event, dropdata, session], e);
            }
            wDragAndDrop.endDragSession(event);
        }
        else
        {
        	event.preventDefault();
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.onDrop", [event, dropdata, session], e);
    }
  },

  canDrop: function (event, session)
  {
    try
    {
        if (wDragAndDrop.preventDoubleImport == false)
        {
            wDragAndDrop.importExternalIfNeeded(event, session);
        }
        wDragAndDrop.currentEvent = event;
        var canDrop = false;
        if ("canDrop" in event.currentTarget)
        {
           	try
           	{
           		canDrop = event.currentTarget.canDrop(wDragAndDrop.draggedElement, wDragAndDrop.draggedData);
        	}
           	catch (e)
           	{
           		wCore.error("wDragAndDrop.canDrop", [event, session], e);
           	   	canDrop = false;
           	}
        }
        return canDrop;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.canDrop", [event, session], e);
    }
  }
};

wDragAndDrop.importExternalIfNeeded = function(event, session)
{
    if (!wDragAndDrop.draggedElement && !wDragAndDrop.draggedData)
    {
        if (wDragAndDrop.hasJustEnded == true)
        {
            wDragAndDrop.hasJustEnded = false;
        }
        else
        {        	
            // Here we add data from the outside (data not set through an onDragStart event) :
            wDragAndDrop.draggedData = [];
            var hasExternaldata = false;
            try
            {
	            var flavourSet = wDragAndDrop.observer.getSupportedFlavours(event);
	            var transferData = nsTransferable.get(flavourSet, nsDragAndDrop.getDragData, true);
	            var xfer = Components.classes["@mozilla.org/widget/transferable;1"]
	                .createInstance(Components.interfaces.nsITransferable);
	                
	            for (var i in flavourSet.flavours)
	            {
	            	wDragAndDrop.draggedData.push({
	                    flavour: flavourSet.flavours[i].contentType,
	                    data: null
	                });
	                xfer.addDataFlavor(flavourSet.flavours[i].contentType);
	            }
	            
	            var dropItemCount = session.numDropItems;
	            for (var i = 0; i < dropItemCount; ++i)
	    	    {
	                session.getData(xfer, i);
	                for (var j = 0; j < wDragAndDrop.draggedData.length; j++)
	                {
	                    var data = { };
	                    var length = { };
	                    try
	                    {
	                        xfer.getTransferData(wDragAndDrop.draggedData[j].flavour, data, length);
	        	      	    if (data)
	        	      	    {
	        	      	        if (!wDragAndDrop.draggedData[j].data)
	            	      	    {
	            	      	        wDragAndDrop.draggedData[j].data = [];
	            	      	    }
	            	      	    if (wDragAndDrop.draggedData[j].flavour == "application/x-moz-file")
	                            {
	                                hasExternaldata = true;
	                                wDragAndDrop.draggedData[j].data.push(data);
	                            }
	                            else
	                            {
	            	      	        var data = data.value.QueryInterface(Components.interfaces.nsISupportsString).toString();
	            	      	        var assocObject = assocStringToObject(data);
	                                if (data && assocObject)
	                                {
	                                    hasExternaldata = true;
	                                    wDragAndDrop.draggedData[j].data.push(assocObject);
	                                }
	                            }
	        	      	    }
	                    }
	                    catch (e)
	                    {
	                    	wCore.warn("wDragAndDrop.importExternalIfNeeded");
	                    }
	                }
	    	    }
            }
            catch (e)
            {
				wCore.error("wDragAndDrop.importExternalIfNeeded", [event, session], e);
            }
    	    if (!hasExternaldata)
    	    {
    	        wDragAndDrop.draggedData = null;
    	    }
    	    else
    	    {
    	        wDragAndDrop.preventDoubleImport = true;
    	    }
        }
    }
}

wDragAndDrop.endDragSession = function (event)
{
    try
    {

        wDragAndDrop.inProcess = false;
        wDragAndDrop.draggedElement = null;
        wDragAndDrop.draggedData = null;
        wDragAndDrop.currentEvent = null;
        wDragAndDrop.done = false;
        wDragAndDrop.hasJustEnded = true;
        window.setTimeout('wDragAndDrop.preventDoubleImport = false', 500);
        for (var i = 0 ; i < wDragAndDrop.dragSessionEndedListeners.length ; i++)
        {
        	if ("onDragSessionEnded" in wDragAndDrop.dragSessionEndedListeners[i])
        	{
        		wDragAndDrop.dragSessionEndedListeners[i].onDragSessionEnded(event);
        	}
        }
        wCore.removeEventListener(window, "mousedown");
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.endDragSession", [event], e);
    }
};

wDragAndDrop.getDragPosition = function (UIContainer)
{
    try
    {

        var scrollPosition = wToolkit.getScrollPosition(UIContainer);
        var position =
        {
           x: wDragAndDrop.currentEvent.clientX + scrollPosition.x,
           y: wDragAndDrop.currentEvent.clientY + scrollPosition.y
        };
        return position;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.getDragPosition", [UIContainer], e);
    }
};

wDragAndDrop.getDragRelativePosition = function (elementBoxObjectData, UIContainer, marginData)
{
    try
    {
        var scrollPosition = wToolkit.getScrollPosition(UIContainer);
        var x = (wDragAndDrop.currentEvent.clientX + scrollPosition.x - elementBoxObjectData.x);
        var y = (wDragAndDrop.currentEvent.clientY + scrollPosition.y - elementBoxObjectData.y);
        var width = elementBoxObjectData.width;
        var height = elementBoxObjectData.height;
        
       	if (!marginData)
       	{
            marginData =
            {
                top: Math.min(height/2, 15),
                right: Math.min(width/3., 30),
                bottom: Math.min(height/2, 15),
                left: Math.min(width/3., 30)
            }
        }
        var relativePosition = POSITION_CENTER;
        if (x <= marginData.left)
        {
            relativePosition = POSITION_LEFT;
        }
        else if ((y <= marginData.top)
        && (x < (width - marginData.right)))
        {
            relativePosition = POSITION_TOP;
        }
        else if ((y >= (height - marginData.bottom))
        && (x < (width - marginData.right)))
        {
            relativePosition = POSITION_BOTTOM;
        }
        else if (x >= (width - marginData.right))
        {
            relativePosition = POSITION_RIGHT;
        }
        return relativePosition;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.getDragRelativePosition", [elementBoxObjectData, UIContainer, marginData], e);
    }
};

wDragAndDrop.getDragModifier = function ()
{
    try
    {
        return wToolkit.getEventModifier(wDragAndDrop.currentEvent);
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.getDragModifier", [], e);
    }
};

wDragAndDrop.getDragDataByFlavour = function (flavour, data)
{
    try
    {

        var dragData = wDragAndDrop.draggedData;
        if (data)
        {
            dragData = data;
        }
        if (dragData)
        {
	        var dataByFlavour = null;
	        for (var i = 0; i < dragData.length; i++)
	        {
	            if (dragData[i].flavour == flavour)
	            {
	                dataByFlavour = dragData[i].data;
	                break;
	            }
	        }
	        return dataByFlavour;
	    }
	    return null;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.getDragDataByFlavour", [flavour, data], e);
    }
};

wDragAndDrop.hasDragDataForFlavour = function (flavour, data)
{
    try
    {

        var dragData = wDragAndDrop.draggedData;
        if (data)
        {
            dragData = data;
        }
        var hasDataForFlavour = false;
        if (dragData)
        {
	        for (var i = 0; i < dragData.length; i++)
	        {
	        	if (dragData[i].flavour == flavour && dragData[i].data)
	            {
	                hasDataForFlavour = true;
	                break;
                }
            }
        }
        return hasDataForFlavour;
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.hasDragDataForFlavour", [flavour, data], e);
    }
};

wDragAndDrop.ensureDragAndDropUsability = function(UIContainer, boxObjectOffsetData)
{
    try
    {

        if (wDragAndDrop.currentEvent)
        {
            var offsetData = {
                top: UIContainer.boxObject.y + boxObjectOffsetData.top,
                right: 32 + boxObjectOffsetData.right,
                bottom: 32 + boxObjectOffsetData.bottom,
                left: UIContainer.boxObject.x + boxObjectOffsetData.left,
            };
            var mouseX = wDragAndDrop.currentEvent.clientX;
            var mouseY = wDragAndDrop.currentEvent.clientY;
            var width = UIContainer.boxObject.width;
            var height = UIContainer.boxObject.height;
            var scrollPosition = wToolkit.getScrollPosition(UIContainer);
            var newScrollX = scrollPosition.x;
            var newScrollY = scrollPosition.y;
            if ((mouseY <= (15 + offsetData.top))
            && (scrollPosition.y >= 15))
            {
                newScrollY -= 15;
            }
            else if (mouseY >= (height - (15 + offsetData.bottom)))
            {
                newScrollY += 15;
            }
            if ((mouseX <= (15 + offsetData.left))
            && (scrollPosition.x >= 15))
            {
                newScrollX -= 15;
            }
            else if (mouseX >= (width - (15 + offsetData.right)))
            {
                newScrollX += 15;
            }
            if ((newScrollX != scrollPosition.x)
            || (newScrollY != scrollPosition.y))
            {
                wToolkit.getScrollBoxObject(UIContainer).scrollTo(newScrollX, newScrollY);
            }
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.ensureDragAndDropUsability", [UIContainer, boxObjectOffsetData], e);
    }
};

wDragAndDrop.registerElement = function (element)
{
    try
    {
    	if (element.hasAttribute("candrag"))
    	{
	        wCore.addEventListener(element, "draggesture", function(event) {
	            try {
	            	wCore.debug('startDrag : ' + event.target.nodeName);
	            	nsDragAndDrop.startDrag(event, wDragAndDrop.observer);
	            } catch (e) {
	            	wCore.error('nsDragAndDrop.startDrag', [event, wDragAndDrop.observer], e)
	            }
	        });
    	}
    	
    	if (element.hasAttribute("candrop"))
    	{
	        wCore.addEventListener(element, "dragover", function(event) {
	            try {
	            	nsDragAndDrop.dragOver(event, wDragAndDrop.observer);
	            } catch (e) {
	            	wCore.error('nsDragAndDrop.dragOver', [event, wDragAndDrop.observer], e)
	            }
	        });
	        wCore.addEventListener(element, "dragexit", function(event) {
	            try {
	            	wCore.debug('dragexit : ' + event.target.nodeName);
	            	nsDragAndDrop.dragExit(event, wDragAndDrop.observer);
	            } catch (e) {
	            	wCore.error('nsDragAndDrop.dragexit', [event, wDragAndDrop.observer], e)
	            }
	        });
	        wCore.addEventListener(element, "dragdrop", function(event) {
	            try {
	            	wCore.debug('dragdrop : ' + event.target.nodeName);        	
	            	nsDragAndDrop.drop(event, wDragAndDrop.observer);
	            } catch (e) {
	            	wCore.error('nsDragAndDrop.dragdrop', [event, wDragAndDrop.observer], e)
	            }
	        });
	        wCore.addEventListener(element, "drop", function(event) {
	            try {
	            	wCore.debug('drop : ' + event.target.nodeName);        	
	            	nsDragAndDrop.drop(event, wDragAndDrop.observer);
	            } catch (e) {
	            	wCore.error('nsDragAndDrop.drop', [event, wDragAndDrop.observer], e)
	            }
	        });
	    	if ( "onDragSessionEnded" in element)
	        {
	        	wDragAndDrop.dragSessionEndedListeners.push(element);
	        }
    	}
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.registerElement", [element], e);
    }
};

wDragAndDrop.unregisterElement = function (element)
{
    try
    {
        wCore.removeEventListener(element, "draggesture");
        wCore.removeEventListener(element, "dragover");
        wCore.removeEventListener(element, "dragexit");
        wCore.removeEventListener(element, "dragdrop");
        if ( "onDragSessionEnded" in element)
        {
     		 for (i = 0 ; i < wDragAndDrop.dragSessionEndedListeners.length ; i++)
     		{
     			if (wDragAndDrop.dragSessionEndedListeners[i] == element)
     			{
     				wDragAndDrop.dragSessionEndedListeners.splice(i,1);
     			}
     		}
        }
    }
    catch (e)
    {
        wCore.error("wDragAndDrop.unregisterElement", [element], e);
    }
};

// nsTransferable requirement :
var nsTransferable =
{
    set: function (aTransferDataSet)
    {
      var trans = this.createTransferable();
      for (var i = 0; i < aTransferDataSet.dataList.length; ++i)
      {
          var currData = aTransferDataSet.dataList[i];
          var currFlavour = currData.flavour.contentType;
          trans.addDataFlavor(currFlavour);
          var supports = null;
          var length = 0;
          if (currData.flavour.dataIIDKey == "nsISupportsString")
          {
              supports = Components.classes["@mozilla.org/supports-string;1"]
                       .createInstance(Components.interfaces.nsISupportsString);
              supports.data = currData.supports;
              length = supports.data.length;
          }
          else
          {
              supports = currData.supports;
              length = 0;
          }
          trans.setTransferData(currFlavour, supports, length * 2);
      }
      return trans;
    },

    get: function (aFlavourSet, aRetrievalFunc, aAnyFlag)
    {
      if (!aRetrievalFunc)
      {
        throw "No data retrieval handler provided!";
      }
      var supportsArray = aRetrievalFunc(aFlavourSet);
      var dataArray = [];
      try
      {
      	var count = supportsArray.Count();
        for (var i = 0; i < count; i++)
        {
          var trans = supportsArray.GetElementAt(i);
          if (!trans)
          {
              continue;
          }
          trans = trans.QueryInterface(Components.interfaces.nsITransferable);
          var data = { };
          var length = { };
          var currData = null;
          if (aAnyFlag)
          {
              var flavour = { };
              trans.getAnyTransferData(flavour, data, length);
              if (data && flavour)
              {
                  var selectedFlavour = aFlavourSet.flavourTable[flavour.value];
                  if (selectedFlavour)
                  {
                    dataArray[i] = FlavourToXfer(data.value, length.value, selectedFlavour);
                  }
              }
          }
          else
          {
              var firstFlavour = aFlavourSet.flavours[0];
              trans.getTransferData(firstFlavour, data, length);
              if (data && firstFlavour)
              {
                dataArray[i] = FlavourToXfer(data.value, length.value, firstFlavour);
              }
          }
        }
      }
      catch (e)
      {
      	wCore.warn("nsTransferable.get");
      }
      return new TransferDataSet(dataArray);
    },

    createTransferable: function ()
    {
      const kXferableContractID = "@mozilla.org/widget/transferable;1";
      const kXferableIID = Components.interfaces.nsITransferable;
      return Components.classes[kXferableContractID].createInstance(kXferableIID);
    }
};

function FlavourSet(aFlavourList)
{
  this.flavours = aFlavourList || [];
  this.flavourTable = { };
  this._XferID = "FlavourSet";
  for (var i = 0; i < this.flavours.length; ++i)
  {
    this.flavourTable[this.flavours[i].contentType] = this.flavours[i];
  }
}

FlavourSet.prototype =
{
  appendFlavour: function (aFlavour, aFlavourIIDKey)
  {
    var flavour = new Flavour (aFlavour, aFlavourIIDKey);
    this.flavours.push(flavour);
    this.flavourTable[flavour.contentType] = flavour;
  }
};

function Flavour(aContentType, aDataIIDKey)
{
  this.contentType = aContentType;
  this.dataIIDKey = aDataIIDKey || "nsISupportsString";
  this._XferID = "Flavour";
}

function TransferDataBase() {}

TransferDataBase.prototype =
{
  push: function (aItems)
  {
    this.dataList.push(aItems);
  },
  get first ()
  {
    return "dataList" in this && this.dataList.length ? this.dataList[0] : null;
  }
};

function TransferDataSet(aTransferDataList)
{
  this.dataList = aTransferDataList || [];
  this._XferID = "TransferDataSet";
}

TransferDataSet.prototype = TransferDataBase.prototype;

function TransferData(aFlavourDataList)
{
  this.dataList = aFlavourDataList || [];
  this._XferID = "TransferData";
}

TransferData.prototype =
{
  __proto__: TransferDataBase.prototype,
  addDataForFlavour: function (aFlavourString, aData, aLength, aDataIIDKey)
  {
    this.dataList.push(new FlavourData(aData, aLength, new Flavour(aFlavourString, aDataIIDKey)));
  }
};

function FlavourData(aData, aLength, aFlavour)
{
  this.supports = aData;
  this.contentLength = aLength;
  this.flavour = aFlavour || null;
  this._XferID = "FlavourData";
}

FlavourData.prototype = {
  get data ()
  {
    if (this.flavour && this.flavour.dataIIDKey != "nsISupportsString")
    {
      return this.supports.QueryInterface(Components.interfaces[this.flavour.dataIIDKey]);
    }
    else
    {
      var unicode = this.supports.QueryInterface(Components.interfaces.nsISupportsString);
      if (unicode)
      {
        return unicode.data.substring(0, this.contentLength/2);
      }
      return this.supports;
    }
    return "";
  }
}

function FlavourToXfer(aData, aLength, aFlavour)
{
  return new TransferData([new FlavourData(aData, aLength, aFlavour)]);
}

var transferUtils =
{
  retrieveURLFromData: function (aData, flavour)
  {
    switch (flavour)
    {
      case "text/unicode":
        return aData;
      case "text/x-moz-url":
        return aData.toString().split("\n")[0];
      case "application/x-moz-file":
        var ioService = Components.classes["@mozilla.org/network/io-service;1"].getService(Components.interfaces.nsIIOService);
        var fileHandler = ioService.getProtocolHandler("file").QueryInterface(Components.interfaces.nsIFileProtocolHandler);
        return fileHandler.getURLSpecFromFile(aData);
    }
    return null;
  }
}

// nsDragAndDrop requirement :
var nsDragAndDrop =
{
    _mDS: null,
    get mDragService()
    {
      if (!this._mDS)
      {
          const kDSContractID = "@mozilla.org/widget/dragservice;1";
          const kDSIID = Components.interfaces.nsIDragService;
          this._mDS = Components.classes[kDSContractID].getService(kDSIID);
      }
      return this._mDS;
    },

    startDrag: function (aEvent, aDragDropObserver)
    {
      if (!("onDragStart" in aDragDropObserver))
      {
        return;
      }
      const kDSIID = Components.interfaces.nsIDragService;
      var dragAction =
      {
          action: kDSIID.DRAGDROP_ACTION_COPY + kDSIID.DRAGDROP_ACTION_MOVE + kDSIID.DRAGDROP_ACTION_LINK
      };
      var transferData =
      {
          data: null
      };
      try
      {
          aDragDropObserver.onDragStart(aEvent, transferData, dragAction);
      }
      catch (e)
      {
      	  wCore.error("nsDragAndDrop.startDrag", [aEvent, aDragDropObserver], e);
          return;
      }
      if (!transferData.data)
      {
          return;
      }
      transferData = transferData.data;
      var transArray = Components.classes["@mozilla.org/supports-array;1"]
          .createInstance(Components.interfaces.nsISupportsArray);
      var region = null;
      if (aEvent.originalTarget.localName == "treechildren")
      {
        var tree = aEvent.originalTarget.parentNode;
        try
        {
          region = Components.classes["@mozilla.org/gfx/region;1"]
                 .createInstance(Components.interfaces.nsIScriptableRegion);
          region.init();
          var obo = tree.treeBoxObject;
          var bo = obo.treeBody.boxObject;
          var sel= tree.view.selection; // SebC ??? : var sel= obo.view.selection;
          var rowX = bo.x;
          var rowY = bo.y;
          var rowHeight = obo.rowHeight;
          var rowWidth = bo.width;
          for (var i = obo.getFirstVisibleRow(); i <= obo.getLastVisibleRow(); i++)
          {
            if (sel.isSelected(i))
            {
              region.unionRect(rowX, rowY, rowWidth, rowHeight);
            }
            rowY = rowY + rowHeight;
          }
          region.intersectRect(bo.x, bo.y, bo.width, bo.height);
        }
        catch(ex)
        {
          wCore.error("nsDragAndDrop.startDrag", [aEvent, aDragDropObserver], ex);
          region = null;
        }
      }
      var count = 0;
      do
      {
          var trans = nsTransferable.set(transferData._XferID == "TransferData" ? transferData : transferData.dataList[count++]);
          transArray.AppendElement(trans.QueryInterface(Components.interfaces.nsISupports));
      }
      while (transferData._XferID == "TransferDataSet" && count < transferData.dataList.length);
      try
      {
        this.mDragService.invokeDragSession(aEvent.target, transArray, region, dragAction.action);
      }
      catch(ex)
      {
      	 wCore.error("nsDragAndDrop.startDrag", [aEvent, aDragDropObserver], ex);
      }
      aEvent.stopPropagation();
    },

    dragOver: function (aEvent, aDragDropObserver)
    {
      if (!("onDragOver" in aDragDropObserver))
      {
        return;
      }
      if (!this.checkCanDrop(aEvent, aDragDropObserver))
      {
        return;
      }
      var flavourSet = aDragDropObserver.getSupportedFlavours(aEvent);
      for (var flavour in flavourSet.flavourTable)
      {
          if (this.mDragSession.isDataFlavorSupported(flavour))
          {
              aDragDropObserver.onDragOver(aEvent, flavourSet.flavourTable[flavour], this.mDragSession);
              aEvent.stopPropagation();
              break;
          }
      }
    },

    mDragSession: null,

    drop: function (aEvent, aDragDropObserver)
    {
      try
      {
		  if (!("onDrop" in aDragDropObserver))
	      {	      
	        return;
	      }
	      if (!this.checkCanDrop(aEvent, aDragDropObserver))
	      {	      	      
	        return;
	      }
	      if (this.mDragSession.canDrop)
	      {
	        var flavourSet = aDragDropObserver.getSupportedFlavours(aEvent);
	        var transferData = nsTransferable.get(flavourSet, this.getDragData, true);
	        var multiple = "canHandleMultipleItems" in aDragDropObserver && aDragDropObserver.canHandleMultipleItems;
	        if (multiple)
	        {
	            var dropData = transferData;
	        }
	        else if (transferData.first && transferData.first.first)
	        {
	            var dropData = transferData.first.first;
	        }
	        else
	        {
	            var dropData = null;
	        }
	        aDragDropObserver.onDrop(aEvent, dropData, this.mDragSession);
	      }
	      aEvent.stopPropagation();
	    }
	    catch (e)
	    {
	    	wCore.error("nsDragAndDrop.drop", [aEvent, aDragDropObserver], e);
	    	return;
	    }
    },

    dragExit: function (aEvent, aDragDropObserver)
    {
      if (!this.checkCanDrop(aEvent, aDragDropObserver))
      {
        return;
      }
      if ("onDragExit" in aDragDropObserver)
      {
        aDragDropObserver.onDragExit(aEvent, this.mDragSession);
      }
    },

    dragEnter: function (aEvent, aDragDropObserver)
    {
      if (!this.checkCanDrop(aEvent, aDragDropObserver))
      {
        return;
      }
      if ("onDragEnter" in aDragDropObserver)
      {
        aDragDropObserver.onDragEnter(aEvent, this.mDragSession);
      }
    },

    getDragData: function (aFlavourSet)
    {
      try
      {
      var supportsArray = Components.classes["@mozilla.org/supports-array;1"]
          .createInstance(Components.interfaces.nsISupportsArray);
      for (var i = 0; i < nsDragAndDrop.mDragSession.numDropItems; ++i)
      {
          var trans = nsTransferable.createTransferable();
          for (var j = 0; j < aFlavourSet.flavours.length; ++j)
          {
            trans.addDataFlavor(aFlavourSet.flavours[j].contentType);
          }
          nsDragAndDrop.mDragSession.getData(trans, i);
          supportsArray.AppendElement(trans);
      }
      return supportsArray;
      }
      catch (e)
      {
      	wCore.error("nsDragAndDrop.getDragData", [aFlavourSet], e);
      	return null;
      }
    },

    checkCanDrop: function (aEvent, aDragDropObserver)
    {
      if (!this.mDragSession)
      {
        this.mDragSession = this.mDragService.getCurrentSession();
      }
      if (!this.mDragSession)
      {
        return false;
      }
      this.mDragSession.canDrop = this.mDragSession.sourceNode != aEvent.target;
      if ("canDrop" in aDragDropObserver)
      {
	    try
	    {
        	this.mDragSession.canDrop = aDragDropObserver.canDrop(aEvent, this.mDragSession);
	    }
	    catch (e)
	    {
	    	wCore.error("nsDragAndDrop.checkCanDrop", [aEvent, aDragDropObserver], e);
	    	this.mDragSession.canDrop = false;
	    	return false;
	    }
      }
      return true;
    },

    dragDropSecurityCheck: function (aEvent, aDragSession, aUri)
    {
      var sourceDoc = aDragSession.sourceDocument;
      if (sourceDoc)
      {
        var uriStr = aUri.replace(/^\s*|\s*$/g, '');
        var uri = null;
        try
        {
          uri = Components.classes["@mozilla.org/network/io-service;1"]
              .getService(Components.interfaces.nsIIOService).newURI(uriStr, null, null);
        }
        catch (e)
        {
        	wCore.error("nsDragAndDrop.dragDropSecurityCheck", [aEvent, aDragSession, aUri], e);
        }
        if (uri)
        {
          var sourceURI = sourceDoc.documentURI;
          const nsIScriptSecurityManager = Components.interfaces.nsIScriptSecurityManager;
          var secMan = Components.classes["@mozilla.org/scriptsecuritymanager;1"]
              .getService(nsIScriptSecurityManager);
          try
          {
            secMan.checkLoadURIStr(sourceURI, uriStr, nsIScriptSecurityManager.STANDARD);
          }
          catch (e)
          {
          	wCore.error("nsDragAndDrop.dragDropSecurityCheck", [aEvent, aDragSession, aUri], e);
            aEvent.stopPropagation();
            throw "Drop of " + aUri + " denied.";
          }
        }
      }
    }
};