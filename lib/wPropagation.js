/**
 * wPropagation object allows the bidirectional
 * or unidirectional synchronization of DOM elements
 * attributes with bindings properties.
 *
 * It extends the basic functionality of observers through
 * the use of global notifications with custom topics (see
 * http://www.xulplanet.com/tutorials/mozsdk/observerserv.php).
 *
 * Basically, each binding's property becomes a topic that
 * any DOM element's attribute can listen and/or modify
 * synchronously.
 *
 * An attribute can listen and/or modify multiple topics at once,
 * each topic being related (or not) to a specified binding.
 *
 * Ex:
 *      [Binding #1 :]
 *
 *      <binding id="mainBinding">
 *
 *          <!-- ... -->
 *
 *          <property name="lock">
 *               <getter><![CDATA[
 *                   return this._lock;
 *               ]]></getter>
 *               <setter><![CDATA[
 *                  this._lock = parseBoolean(val);
 *                  // WARNING : Property change must be explicitly propagated :
 *                  this.propagate("lock");
 *               ]]></setter>
 *          </property>
 *
 *          <property name="closed">
 *               <getter><![CDATA[
 *                   return this.getAttribute("closed");
 *               ]]></getter>
 *               <setter><![CDATA[
 *                  this.setAttribute("closed", parseBoolean(val));
 *                  // WARNING : Property change must be explicitly propagated :
 *                  this.propagate("closed");
 *               ]]></setter>
 *          </property>
 *
 *          <!-- ... -->
 *
 *      </binding>
 *
 *      [Binding #2 :]
 *
 *      <binding id="anotherBinding">
 *
 *          <!-- ... -->
 *
 *          <property name="disabled">
 *               <getter><![CDATA[
 *                   return this.getAttribute("disabled");
 *               ]]></getter>
 *               <setter><![CDATA[
 *                  this.setAttribute("disabled", parseBoolean(val));
 *                  // WARNING : Property change must be explicitly propagated :
 *                  this.propagate("disabled");
 *               ]]></setter>
 *          </property>
 *
 *          <!-- ... -->
 *
 *      </binding>
 *
 *      [Document :]
 *
 *      <mainBinding id="main" />
 *      <anotherBinding id="another" />
 *      <checkbox id="test"
 *                label="lock"
 *                connect="checked: main/lock;"
 *                listen="disabled: main/closed another/disabled;"
 *      />
 *
 *      [Initialization inside some binding :]
 *
 *      this.registerPropagation(this.getElementById("test"));
 *
 *      [OR on "global" level (no use of the specific attributes "connect" and "listen") :]
 *
 *      wPropagation.registerElement(
 *          document.getElementById("test"),
 *          "lock",
 *          "checked",
 *          document.getElementById("main"),
 *          false
 *      );
 *      wPropagation.registerElement(
 *          document.getElementById("test"),
 *          "disabled",
 *          "disabled",
 *          document.getElementById("main"),
 *          true
 *      );
 *      wPropagation.registerElement(
 *          document.getElementById("test"),
 *          "disabled",
 *          "disabled",
 *          document.getElementById("another"),
 *          true
 *      );
 *
 *      ==> The "checked" attribute of the checkbox element is now fully synchronized
 *          with the "lock" property of the "main" binding : checking the box will
 *          change the property, and reciprocally.
 *
 *      ==> The "disabled" attribute of the checkbox element is now passively synchronized
 *          with the "closed" property of the "main" binding AND the "disabled" property of
 *          the "another" binding : closing the "main" binding OR disabling the "another" binding
 *          will disable the checkbox (but NOT reciprocally).
 */

var wPropagation = {};

// Array of registred topics :
wPropagation.topics = [];

// Array of registred elements :
wPropagation.elements = [];

// Reference to nsIObserverService :
wPropagation.service = undefined;

/**
 * wPropagation.observer object defines the main
 * handler for propagation.
 */
wPropagation.observer = {
    observe : function (subject, topic, data)
    {
        try
        {
            var elements = wPropagation.getElementsByTopic(topic);
            for (var i = 0; i < elements.length; i++)
            {
                if (elements[i])
                {
                    if (elements[i].element != subject)
                    {
                        elements[i].element.setAttribute(elements[i].attribute, data);
                        if ((elements[i].element.tagName == "textbox")
                        && (elements[i].attribute == "value"))
                        {
                            elements[i].element[elements[i].attribute] = data;
                        }
                    }
                    else if (elements[i].target)
                    {
                        elements[i].target[topic] = data;
                    }
                }
            }
        }
        catch (e)
        {
            wCore.error("wPropagation.observe", [subject, topic, data], e);
        }
    }
};

/**
 * getService() returns an nsIObserverService instance.
 *
 * @param
 *
 * @returns nsIObserverService
 */
wPropagation.getService = function ()
{
    try
    {
        if (!this.service)
        {
            this.service = Components.classes["@mozilla.org/observer-service;1"].
                getService(Components.interfaces.nsIObserverService);
        }
        return this.service;
    }
    catch (e)
    {
        wCore.error("wPropagation.getService", [], e);
    }
};

/**
 * propagateElement() is called by the onchange or oncommand
 * handler of a registred element in order to propagate
 * its modification appropriately.
 *
 * @param  DOM element
 *
 * @returns
 */
wPropagation.propagateElement = function (element)
{
    try
    {
         var topics = this.getTopicsByElement(element, true);
        for (var i = 0; i < topics.length; i++)
        {
            var attribute = this.getAttributeByElementAndTopic(element, topics[i]);
            this.getService().notifyObservers(element, topics[i], element[attribute]);
        }
    }
    catch (e)
    {
        wCore.error("wPropagation.propagateElement", [element], e);
    }
};

/**
 * propagateProperty() is called by the propagate() binding method
 * inside the related property setter in order to propagate
 * its modification appropriately.
 *
 * @param  DOM element (binding)
 *
 * @param  string property name (topic)
 *
 * @returns
 */
wPropagation.propagateProperty = function (element, property)
{
    try
    {
        this.getService().notifyObservers(element, property, element[property]);
    }
    catch (e)
    {
        wCore.error("wPropagation.propagateProperty", [element, property], e);
    }
};

/**
 * registerElement()
 *
 * @param
 *
 * @returns
 */
wPropagation.registerElement = function (element, topic, attribute, target, listenOnly)
{
    try
    {
        this.topics.push(topic);
        if (!this.elements[topic])
        {
            this.elements[topic] = [];
        }
        var elementData =
        {
            element: element,
            attribute: attribute,
            target: target,
            listenOnly: listenOnly
        };
        var elementFound = false;
        for (var _topic in this.elements)
        {
                for (var i = 0; i < this.elements[_topic].length; i++)
                {
                    if (this.elements[_topic][i]
                    && (this.elements[_topic][i].element == element))
                    {
                        elementFound = true;
                    }
                }
            }
        if (elementFound == false)
        {
            switch (element.tagName)
            {
                case "textbox":
                    wCore.addEventListener(element, "change", function(event) {
                        wPropagation.propagateElement(event.target);
                    });
                    break;

                default:
                    wCore.addEventListener(element, "command", function(event) {
                        wPropagation.propagateElement(event.target);
                    });
                    break;
            }
        }
        this.elements[topic].push(elementData);
        this.topics = array_unique(this.topics);
        for (var i = 0; i < this.topics.length; i++)
        {
            var observers = this.getService().enumerateObservers(this.topics[i]);
            while (observers.hasMoreElements())
            {
                var observer = observers.getNext();
                this.getService().removeObserver(observer, this.topics[i]);
            }
            this.getService().addObserver(this.observer, this.topics[i], false);
        }
    }
    catch (e)
    {
        wCore.error("wPropagation.registerElement", [element, topic, attribute, target], e);
    }
};

/**
 * unregisterElement() removes all connections
 * related to the given element.
 *
 * @param  DOM element
 *
 * @returns
 */
wPropagation.unregisterElement = function (element)
{
    try
    {
        var unregistered = false;
        for (var topic in this.elements)
        {
                for (var i = 0; i < this.elements[topic].length; i++)
                {
                    if (this.elements[topic][i]
                    && (this.elements[topic][i].element == element))
                    {
                        this.elements[topic][i] = null;
                        unregistered = true;
                    }
                }
            }
        if (unregistered == true)
        {
            switch (element.tagName)
            {
                case "textbox":
                    wCore.removeEventListener(element, "change");
                    break;

                default:
                    wCore.removeEventListener(element, "command");
                    break;
            }
        }
    }
    catch (e)
    {
        wCore.error("wPropagation.unregisterElement", [element], e);
    }
};

/**
 * getElementsByTopic() returns an array of registred
 * elements related to the given topic.
 *
 * @param  string topic
 *
 * @returns array
 */
wPropagation.getElementsByTopic = function (topic)
{
    try
    {
        return this.elements[topic];
    }
    catch (e)
    {
        wCore.error("wPropagation.getElementsByTopic", [topic], e);
    }
};

/**
 * getTopicsByElement() returns an array of topics
 * related to the given registred element.
 *
 * @param  DOM element
 *
 * @returns array
 */
wPropagation.getTopicsByElement = function (element)
{
    try
    {
        var topics = [];
        for (var topic in this.elements)
        {
                for (var i = 0; i < this.elements[topic].length; i++)
                {
                    if (this.elements[topic][i]
                    && (this.elements[topic][i].element == element)
                    && (!this.elements[topic][i].listenOnly))
                    {
                        topics.push(topic);
                    }
                }
            }
        return array_unique(topics);
    }
    catch (e)
    {
        wCore.error("wPropagation.getTopicsByElement", [element], e);
    }
};

/**
 * getAttributeByElementAndTopic() returns the
 * attribute name of the given element related
 * to the given topic.
 *
 * @param  DOM element
 *
 * @param  string topic
 *
 * @returns string
 */
wPropagation.getAttributeByElementAndTopic = function (element, topic)
{
    try
    {
        var attribute = "";
        var elements = this.getElementsByTopic(topic);
        for (var i = 0; i < elements.length; i++)
        {
            if (elements[i]
            && (elements[i].element == element))
            {
                attribute = elements[i].attribute;
                break;
            }
        }
        return attribute;
    }
    catch (e)
    {
        wCore.error("wPropagation.getAttributeByElementAndTopic", [element, topic], e);
    }
};

/**
 * getTargetByElementAndTopic() returns the
 * element (a binding) targetted by the given
 * element and related to the given topic.
 *
 * @param  DOM element
 *
 * @param  string topic
 *
 * @returns DOM element
 */
wPropagation.getTargetByElementAndTopic = function (element, topic)
{
    try
    {
        var target = undefined;
        var elements = this.getElementsByTopic(topic);
        for (var i = 0; i < elements.length; i++)
        {
            if (elements[i]
            && (elements[i].element == element))
            {
                target = elements[i].target;
                break;
            }
        }
        return target;
    }
    catch (e)
    {
        wCore.error("wPropagation.getTargetByElementAndTopic", [element, topic], e);
    }
};