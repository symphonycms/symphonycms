Symphony = {
	VERSION: "2.0.0b",
	WEBSITE: location.href.match(/.{7,}(?=\/symphony\/)|/)[0]
};

// Language Localisation

Symphony.Language = {
	UNTITLED        : "Untitled",
	CREATE_ITEM     : "Add item",
	REMOVE_ITEM     : "Remove selected item",
	REMOVE_ITEMS    : "Remove selected items",
	CONFIRM_SINGLE  : "Are you sure you want to {$action} {$name}?",
	CONFIRM_MANY    : "Are you sure you want to {$action} {$count} items?",
	CONFIRM_ABSTRACT: "Are you sure you want to {$action}?",
	SEARCH_ENTRIES  : "Search {$section}...",
	SHOW_CONFIG     : "Configure page settings",
	HIDE_CONFIG     : "Hide page settings",
	REORDER_ERROR   : "Reordering was unsuccessful.",
	MONTH           : "Month",
	TIME            : "Time"
};

Symphony.Language.MONTHS = [
	"January",
	"February",
	"March",
	"April",
	"May",
	"June",
	"July",
	"August",
	"September",
	"October",
	"November",
	"December"
];

Symphony.Language.DAYS = [
	"Sun",
	"Mon",
	"Tue",
	"Wed",
	"Thu",
	"Fri",
	"Sat"
];

// Abstract Utilities

function Abstract(source) {
	for (var name in source) {
		if (!this.hasOwnProperty(name)) this[name] = source[name];
	}
}

Abstract.defineFallbackMethods = function(target, name, source) {
	target[name] = function() {
		var method = target[name] = source.shift();

		try {
			return method.apply(this, arguments);
		} catch (error) {
			if (!source.length) throw error;

			return arguments.callee.apply(this, arguments);
		}
	};
};

Abstract.defineGenericMethods = function(target, names) {
	names.match(/[\w$]+/g).forEach(Abstract.DEFINE_GENERIC_METHOD, target);
};

Abstract.DEFINE_GENERIC_METHOD = function(name) {
	if (!this.hasOwnProperty(name)) this[name] = Abstract.GENERIC.bind(this.prototype[name]);
};

Abstract.GENERIC = function() {
	return this.call.apply(this, arguments);
};

// Function Extensions

Function.prototype.bind = function(context) {
	var self = this;

	return function() {
		return self.apply(context, arguments);
	};
};

// Array Normalisation

Abstract.call(Array.prototype, {
	forEach: function(callback, context) {
		var i = 0,
		    n = this.length;

		do {
			if (i in this) callback.call(context, this[i], i, this);
		} while (++i < n);
	},
	map: function(callback, context) {
		var n = this.length,
		    i = 0,
		    r = new Array(n);

		do {
			if (i in this) r[i] = callback.call(context, this[i], i, this);
		} while (++i < n);

		return r;
	},
	filter: function(callback, context) {
		var i = 0,
		    r = [],
		    n = this.length,
		    o;

		do {
			if (i in this && callback.call(context, o = this[i], i, this)) r.push(o);
		} while (++i < n);

		return r;
	},
	every: function(callback, context) {
		var i = 0,
		    n = this.length;

		do {
			if (i in this && !callback.call(context, this[i], i, this)) return false;
		} while (++i < n);

		return true;
	},
	some: function(callback, context) {
		var i = 0,
		    n = this.length;

		do {
			if (i in this && callback.call(context, this[i], i, this)) return true;
		} while (++i < n);

		return false;
	},
	indexOf: function(item, startIndex) {
		var n = this.length,
		    i = (startIndex < 0) ? Math.max(n + startIndex, 0) : startIndex >>> 0;

		do {
			if (this[i] === item) return i;
		} while (++i < n);

		return -1;
	},
	reduce: function(callback, value) {
		var i = 0,
		    n = this.length;

		while (value === undefined) {
			value = this[i];

			if (++i > n) throw new TypeError();
		}

		do {
			if (i in this) value = callback(value, this[i], i, this);
		} while (++i < n);

		return value;
	},
	remove: function(item) {
		var index = this.indexOf(item);

		if (index != -1) this.splice(index, 1);

		return this.length;
	},
	invoke: function(name) {
		var args = Array.slice(arguments, 1);

		return this.map(function(item) {
			return item[name].apply(item, args);
		});
	}
}, true);

Abstract.defineGenericMethods(Array, "slice forEach map filter");

// DOM Utilities

DOM = {
	createElementWithText: function(tagName, text) {
		return DOM.insertTextNode(text, document.createElement(tagName)).parentNode;
	},
	createElementWithAttributes: function(tagName, attributes) {
		return DOM.setAttributes(attributes, document.createElement(tagName));
	},
	createElementWithClass: function(tagName, className) {
		var element = document.createElement(tagName);

		element.className = className;

		return element;
	},
	insertNode: function(node, parent, sibling) {
		return sibling ? parent.insertBefore(node, sibling) : parent.appendChild(node);
	},
	insertElement: function(tagName, parent, sibling) {
		return DOM.insertNode(document.createElement(tagName), parent, sibling);
	},
	insertTextNode: function(text, parent, sibling) {
		return DOM.insertNode(document.createTextNode(text), parent, sibling);
	},
	insertElementWithText: function(tagName, text, parent, sibling) {
		var element = DOM.createElementWithText(tagName, text);

		return DOM.insertNode(element, parent, sibling);
	},
	insertElementWithAttributes: function(tagName, attributes, parent, sibling) {
		var element = DOM.createElementWithAttributes(tagName, attributes);

		return DOM.insertNode(element, parent, sibling);
	},
	insertElementWithClass: function(tagName, className, parent, sibling) {
		var element = DOM.createElementWithClass(tagName, className);

		return DOM.insertNode(element, parent, sibling);
	},
	setAttributes: function(attributes, element) {
		for (var name in attributes) element.setAttribute(name, attributes[name]);

		return element;
	},
	removeNode: function(node) {
		return node.parentNode.removeChild(node);
	},
	replaceNode: function(previous, next) {
		return previous.parentNode.replaceChild(next, previous);
	},
	getFirstElement: function(tagName, context) {
		return (context || document).getElementsByTagName(tagName)[0];
	},
	getLastElement: function(tagName, context) {
		var elements = (context || document).getElementsByTagName(tagName);

		return elements[elements.length - 1] || null;
	},
	getChildren: function(context) {
		return context.children
			? Array.slice(context.children, 0)
			: Array.filter(context.childNodes, DOM.isElement);
	},
	isElement: function(node) {
		return node.nodeType == 1;
	},
	getPreviousElement: function(element) {
		while (element = element.previousSibling) {
			if (element.nodeType == 1) return element;
		}

		return null;
	},
	getNextElement: function(element) {
		while (element = element.nextSibling) {
			if (element.nodeType == 1) return element;
		}

		return null;
	},
	hasClass: function(className, element) {
		return new RegExp("(?:\\s|^)" + className + "(?:\\s|$)").test(element.className);
	},
	addClass: function(className, element) {
		return element.className = element.className.replace(/^\s*/, className + " ");
	},
	removeClass: function(className, element) {
		var pattern = new RegExp("(\\s(?!\\s*\\S+$)|\\b)\\s*" + className + "(?:$|\\s+)", "g");

		return element.className = element.className.replace(pattern, "$1");
	},
	toggleClass: function(className, element) {
		return DOM.hasClass(className, element)
			? DOM.removeClass(className, element)
			: DOM.addClass(className, element);
	},
	setClass: function(className, element, pass) {
		return (pass ? DOM.addClass : DOM.removeClass)(className, element);
	}
};

// DOM Selectors

DOM.Selector = function(selector) {
	this.components = selector.replace(/\.([^\s.[]+)/g, "[class~=$1]").split(/\s+|(?=\[)/g);
};

DOM.Selector.prototype.evaluate = function(context) {
	return (this.components.length != 1)
		? this.components.reduce(DOM.Selector.evaluateComponent, [context || document])
		: Array.slice((context || document).getElementsByTagName(this.components[0]), 0);
};

DOM.Selector.evaluateComponent = function(elements, component) {
	if (elements.length == 0) return elements;

	var predicate = /^\[([^=~^$*\]]+)([~^$*]?=|)([^\]]*)/.exec(component);

	if (!predicate) return DOM.Selector.getUniqueElements(component, elements);

	var attribute = predicate[1],
	    operator  = predicate[2],
	    pattern   = predicate[3];

	return elements.filter(function(element) {
		var value = element.getAttribute(attribute);

		return (value !== null) && DOM.Selector.PREDICATE_OPERATORS[operator](value, pattern);
	});
};

DOM.Selector.getUniqueElements = function(tagName, ancestors) {
	return (ancestors.length != 1)
		? ancestors.map(getElements).reduce(DOM.Selector.concatUniquely)
		: Array.slice(ancestors[0].getElementsByTagName(tagName), 0);

	function getElements(context) {
		return Array.slice(context.getElementsByTagName(tagName), 0);
	}
};

DOM.Selector.concatUniquely = function(result, elements) {
	return (result.indexOf(elements[0]) != -1) ? result : result.concat(elements);
};

DOM.Selector.PREDICATE_OPERATORS = {
	"": function() {
		return true;
	},
	"=": function(value, pattern) {
		return value == pattern;
	},
	"~=": function(value, pattern) {
		return new RegExp("(?:\\s|^)" + pattern + "(?:\\s|$)").test(value);
	},
	"^=": function(value, pattern) {
		return value.indexOf(pattern) == 0;
	},
	"$=": function(value, pattern) {
		return value.lastIndexOf(pattern) == value.length;
	},
	"*=": function(value, pattern) {
		return value.indexOf(pattern) != -1;
	}
};

Abstract.defineFallbackMethods(DOM, "select", [
	function(selector, context) {
		return Array.slice((context || document).querySelectorAll(selector), 0);
	},
	function(selector, context) {
		return (selector.indexOf(",") != -1)
			? Array.prototype.concat.apply([], selector.split(/,\s*/g).map(select))
			: new DOM.Selector(selector).evaluate(context);

		function select(selector) {
			return new DOM.Selector(selector).evaluate(context);
		}
	}
]);

// Event Utilities

DOM.Event = {
	preventDefault: function(event) {
		event.preventDefault();
	}
};

Abstract.defineFallbackMethods(DOM.Event, "addListener", [
	function(target, type, listener) {
		target.addEventListener(type, listener, false);

		return listener;
	},
	function(target, type, listener) {
		var current = target["on" + type], listeners = current && current.LISTENERS;

		if (!listeners) {
			var handler = target["on" + type] = function() {
				var listeners = arguments.callee.LISTENERS, allowDefaultResponse = true;

				Abstract.call(event, {
					target        : event.srcElement,
					currentTarget : this,
					preventDefault: function() {
						allowDefaultResponse = false;
					}
				});

				listeners.invoke("call", this, event);

				return allowDefaultResponse;
			};

			handler.LISTENERS = current ? [current, listener] : [listener];

		} else if (listeners.indexOf(listener) == -1) listeners.push(listener);

		return listener;
	}
]);

Abstract.defineFallbackMethods(DOM.Event, "removeListener", [
	function(target, type, listener) {
		target.removeEventListener(type, listener, false);
	},
	function(target, type, listener) {
		var handler = target["on" + type], listeners = handler && handler.LISTENERS;

		if (listeners) listeners.remove(listener);
	}
]);

DOM.onready = (function() {
	var listeners = [],
	    available = false;

	DOM.Event.addListener(document, "DOMContentLoaded", respond);

	if ("readyState" in document) {
		var handler = setInterval(function() {
			if (available || !/complete|loaded/.test(document.readyState)) return;
			
			available = listeners.invoke("call", clearInterval(handler));
		}, 100)
	} else DOM.Event.addListener(window, "load", respond);

	return listeners.push.bind(listeners);

	function respond() {
		if (!available) available = listeners.invoke("call");
	}
})();

// Layout Utilities

DOM.Coordinates = function(element) {
	this.X = this.Y = 0;

	do {
		this.X += element.offsetLeft;
		this.Y += element.offsetTop;
	} while (element = element.offsetParent);
};

DOM.CursorPosition = function(event) {
	this.X = this.Y = 0;

	if (isNaN(event.pageX)) {
		var offset = new DOM.Layout.Coordinates(document.body);

		this.X += offset.X + event.clientX;
		this.Y += offset.Y + event.clientY;

	} else {
		this.X += event.pageX;
		this.Y += event.pageY;
	}
};

// Date Utilities

Date.prototype.offset = function(time) {
	this.setTime(this.getTime() + time);

	return this;
};

// HTTP Requests

function Request(resource, callback) {
	this.resource = resource;
	this.callback = callback;
}

Request.DEFAULT_CONTENT_TYPE = "application/x-www-form-urlencoded";

Request.prototype.get = function(data) {
	var request = new XMLHttpRequest();

	request.open("GET", this.resource + data, true);
	request.send(null);

	Request.setReadyStateHandler(request, this.callback);

	return request;
};

Request.prototype.post = function(data, contentType) {
	var request = new XMLHttpRequest();

	request.open("POST", this.resource, true);
	request.setRequestHeader("Content-Type", contentType || Request.DEFAULT_CONTENT_TYPE);
	request.setRequestHeader("Cookie", document.cookie);
	request.send(data);

	Request.setReadyStateHandler(request, this.callback);

	return request;
};

Request.setReadyStateHandler = function(request, callback) {
	request.onreadystatechange = function() {
		if (request.readyState == 4) callback(request);
	};
};

// Cookies

function Cookie(name) {
	var cookie = decodeURIComponent(document.cookie),
	    match  = new RegExp("(?:^|;\s*)" + name + "=([^;]+)").exec(cookie);

	this.name  = name;
	this.value = match && match[1];
}

Cookie.DURATION_SCALE = 24 * 3600 * 1000; // days

Cookie.prototype.set = function(value, duration, path) {
	var expiry = new Date().offset(duration * Cookie.DURATION_SCALE).toUTCString(),
	    cookie = this.name + "=" + value + ";expires=" + expiry + ";path=" + path || ".";

	document.cookie = cookie;
	this.value      = value;
};

// Animations

function Animation(origin, target, update) {
	this.current    = origin;
	this.target     = target;
	this.update     = update;
	this.responders = [];

	Animation.run(this, origin);
}

Animation.prototype.stop = function() {
	if (this.timeout) clearTimeout(this.timeout);

	this.timeout = null;
};

Animation.prototype.addResponder = function(responder) {
	return this.responders.indexOf(responder) == -1 && this.responders.push(responder);
};

Animation.run = function(state, previous) {
	var delay = 0, current;

	do {
		state.current += (state.target - state.current) / 2.4;
		delay         += 40;
		current        = Math.round(state.current);
	} while (current == previous && current != state.target);

	state.update(current, state);

	return (current == state.target)
		? state.responders.invoke("call", state.timeout = null, state)
		: state.timeout = setTimeout(arguments.callee, delay, state, current);
};

Animation.updateHeight = function(element, keep) {
	var style = element.style,
	    guide = document.body.style, // Force Gecko reflow
	    items = DOM.select("label *", element);

	return function(height, state) {
		switch (height) {
			case 0:                    style.display = "none";
			                           keep ? items.invoke("blur") : DOM.removeNode(element);

			case element.offsetHeight: return;

			case state.target:         items[0].focus();

			default:                   style.height  = guide.minHeight = height + "px";
			                           style.display = "block";
		}
	};
};

// Selectable Lists

function Selectable(elements, callback, targets) {
	this.callback = callback;
	this.targets  = targets || /^(?:h4|td)$/i;
	this.select   = this.select.bind(this);
	this.items    = [];

	elements.forEach(function(element) {
		DOM.Event.addListener(element, "click", this.select);
	}, this);
}

Selectable.prototype.select = function(event) {
	var element = event.currentTarget,
	    movable = Orderable.CURRENT_ITEM,
	    shifted = movable && movable.element === element && movable.movement,
	    allowed = this.targets.test(event.target.nodeName);

	if (!allowed || shifted) return;

	DOM.toggleClass("selected", element);

	return (this.items.indexOf(element) != -1)
		? this.callback(element, this.items.remove(element) & 0)
		: this.callback(element, this.items.push(element));
};

// Reorderable Lists

function Orderable(element, callback) {
	DOM.addClass("movable", element);

	this.element  = element;
	this.callback = callback;
	this.previous = DOM.getPreviousElement(element);
	this.next     = DOM.getNextElement(element);
	this.movement = 0;

	this.setBoundaries();
}

Orderable.prototype.setBoundaries = function() {
	var position  = new DOM.Coordinates(this.element).Y,
	    height    = this.element.offsetHeight,
	    roomAbove = (this.previous || this).offsetHeight - height,
	    roomBelow = (this.next || this).offsetHeight;

	this.minimum = Math.min(position, position - roomAbove);
	this.maximum = Math.max(position + height, position + roomBelow);
};

Orderable.prototype.respond = function(event) {
	DOM.removeClass("movable", this.element);

	if (this.callback && this.movement) this.callback(this.element, this.movement, event);
};

Orderable.implement = function(elements, callback, targets) {
	elements.forEach(implementSingle);

	return implementSingle;

	function implementSingle(element) {
		DOM.Event.addListener(element, "mousedown", grab);
	}

	function grab(event) {
		Orderable.grab.call(this, callback, targets || /^(?:h4|td)$/i, event);
	}
};

Orderable.grab = function(callback, targets, event) {
	if (!targets.test(event.target.nodeName)) return;

	Orderable.CURRENT_ITEM = new Orderable(this, callback);

	DOM.Event.addListener(document.body, "mousemove", Orderable.move);
	DOM.Event.addListener(document.body, "mouseup"  , Orderable.drop);

	event.preventDefault();
};

Orderable.move = function(event) {
	var position = new DOM.CursorPosition(event).Y,
	    selected = Orderable.CURRENT_ITEM,
	    element  = selected.element;

	if (position < selected.minimum && selected.previous) {
		DOM.insertNode(element, element.parentNode, selected.previous);

		DOM.toggleClass("odd", selected.previous);
		DOM.toggleClass("odd", element);

		selected.next      = selected.previous;
		selected.previous  = DOM.getPreviousElement(element);
		selected.movement -= 1;

		selected.setBoundaries();

	} else if (position > selected.maximum && selected.next) {
		DOM.insertNode(element, element.parentNode, selected.next.nextSibling);

		DOM.toggleClass("odd", selected.next);
		DOM.toggleClass("odd", element);

		selected.previous  = selected.next;
		selected.next      = DOM.getNextElement(element);
		selected.movement += 1;

		selected.setBoundaries();
	}
};

Orderable.drop = function(event) {
	DOM.Event.removeListener(document.body, "mousemove", Orderable.move);
	DOM.Event.removeListener(document.body, "mouseup"  , Orderable.drop);

	Orderable.CURRENT_ITEM.respond(event);
};

// Subsections

function Subsection(list) {
	this.list      = list;
	this.templates = DOM.select("li.template", list).map(DOM.removeNode);

	var container  = list.parentNode,
	    items      = DOM.getChildren(list),
	    actions    = DOM.createElementWithClass("div", "actions");

	this.orderable = Orderable.implement(items);
	this.selected  = new Selectable(items, this.updateActions.bind(this));
	this.wrapper   = DOM.insertElementWithClass("div", "wrapper", container);

	this.createButton = DOM.insertElementWithText("a", Symphony.Language.CREATE_ITEM, actions);
	this.removeButton = DOM.insertElementWithText("a", Symphony.Language.REMOVE_ITEM, actions);

	DOM.Event.addListener(this.createButton, "click", this.createItem.bind(this));
	DOM.Event.addListener(this.removeButton, "click", this.removeItem.bind(this));

	if (this.templates.length > 1) {
		this.chooseTemplate = DOM.insertElement("select", actions, this.createButton);

		this.templates.forEach(function(template, i) {
			this.options[i] = new Option(DOM.getFirstElement("h4", template).firstChild.data);
		}, this.chooseTemplate);

		DOM.Event.addListener(this.chooseTemplate, "change", this.selected.callback);
	}

	this.updateActions(container.appendChild(actions));

	DOM.Event.addListener(document.forms[0], "submit", function() {
		DOM.select("li", container).forEach(function(item, position) {
			DOM.select("input, textarea, select", item).forEach(setOrder, position);
		});

		function setOrder(field) {
			field.name = field.name.replace(/\[\-?\d+]/, "[" + this + "]");
		}
	});
};

Subsection.prototype.createItem = function() {
	if (DOM.hasClass("inactive", this.createButton)) return;

	var item = this.activeTemplate.cloneNode(true);

	if (DOM.hasClass("unique", item)) DOM.addClass("inactive", this.createButton);

	DOM.removeClass("template", item);
	DOM.Event.addListener(item, "click", this.selected.select);

	var limit = this.wrapper.appendChild(item).offsetHeight,
		label = DOM.getFirstElement("label", item);

	UIControl.ACTIVE_CONTROLS.invoke("deploy", item);

	new Animation(0, limit, Animation.updateHeight(item));

	this.orderable(this.list.appendChild(item));
};

Subsection.prototype.removeItem = function() {
	if (DOM.hasClass("inactive", this.removeButton)) return;

	var animators = this.selected.items.map(function(item) {
		var animate = Animation.updateHeight(item),
		    maximum = item.offsetHeight;

		return function(scale) {
			animate(Math.round(scale * maximum), this);
		};
	});

	new Animation(100, 0, function(percent, state) {
		animators.invoke("call", state, percent ? state.current / 100 : 0);
	}).addResponder(this.selected.callback);

	this.selected.items = [];
};

Subsection.prototype.updateActions = function() {
	var selected = this.selected.items.length,
	    position = this.chooseTemplate ? this.chooseTemplate.selectedIndex : 0;

	switch (selected) {
		case 0: this.removeButton.className = "inactive";
		        break;

		case 1: this.removeButton.firstChild.data = Symphony.Language.REMOVE_ITEM;
		        this.removeButton.className       = "";
		        break;

		case 2: this.removeButton.firstChild.data = Symphony.Language.REMOVE_ITEMS;
	}

	this.activeTemplate = this.templates[position];

	var isUnique = DOM.hasClass("unique", this.activeTemplate),
	    disabled = this.chooseTemplate && this.chooseTemplate.options[position].firstChild.data,
	    inactive = isUnique && (disabled ? DOM.select("h4", this.list).some(taken) : selected);

	DOM.setClass("inactive", this.createButton, inactive);

	function taken(heading) {
		return heading.firstChild.data == disabled;
	}
};

DOM.onready(function() {
	Symphony.SUBSECTIONS = DOM.select("div.subsection ol").map(function(list) {
		return new Subsection(list);
	});
});

// UI Controls

function UIControl(selector, callback, context) {
	this.selector = selector;
	this.callback = callback;

	Abstract.call(this, context);

	UIControl.ACTIVE_CONTROLS.push(this);
}

UIControl.ACTIVE_CONTROLS = [];

UIControl.prototype.deploy = function(context) {
	DOM.select(this.selector, context).forEach(this.callback, this);
};

UIControl.deploy = function(selector, callback, context) {
	var control = new UIControl(selector, callback, context);

	DOM.onready(function() {
		control.deploy(document)
	});
};

UIControl.remove = function(selector) {
	var controls = UIControl.ACTIVE_CONTROLS,
	    i        = controls.length;

	while (i--) {
		if (controls[i].selector == selector) controls.splice(i, 1);
	}
};

// Admin UI Controls

UIControl.deploy("label", function(label) {
	var control  = DOM.getLastElement("*", label),
	    overflow = control.offsetWidth - label.offsetWidth;

	if (overflow <= 0) return;

	if (DOM.hasClass("group", label.parentNode)) {
		var width = label.offsetWidth,
		    ratio = 1 - overflow / width;

		control.style.width = ratio * 100 + "%";
	} else label.style.paddingRight = overflow + "px";
});

UIControl.deploy("input[type=checkbox]", function(checkbox) {
	DOM.addClass("toggle", checkbox.parentNode);
});

UIControl.deploy("button.confirm", function(button) {
	var lang = Symphony.Language,
	    name = document.title.split(/\u2013\s*/g)[2],
	    deed = button.firstChild.data.toLowerCase().replace(/\bs(?=ymphony\b)/g, "S"),
	    data = name ? lang.CONFIRM_SINGLE.replace("{$action}", deed).replace("{$name}", name)
		            : lang.CONFIRM_ABSTRACT.replace("{$action}", deed);

	DOM.Event.addListener(button, "click", function(event) {
		if (!confirm(data)) event.preventDefault();
	});
});

UIControl.deploy("ul.tags", function(tags) {
	var input  = DOM.getFirstElement("input", DOM.getPreviousElement(tags)),
	    single = DOM.hasClass("singular", tags),
	    items  = DOM.select("li", tags),
	    update = this.updateTags;

	if (items.length > 15) { // TO-DO Localisability for "N more..."
		var excess = document.createDocumentFragment(),
		    more   = DOM.insertElementWithText("li", items.length - 15 + " more...", tags);

		more.className = "more";

		DOM.Event.addListener(more, "click", function() {
			DOM.replaceNode(this, excess);
		});
	}

	items.forEach(function(item, position) {
		DOM.Event.addListener(item, "click", addTag);
		DOM.Event.addListener(item, "mousedown", DOM.Event.preventDefault);

		if (position >= 15) excess.appendChild(item);
	});

	function addTag() {
		update(input, this.className || this.firstChild.data, single);
	}
}, {
	updateTags: function(input, tag, single) {
		input.focus();

		if (single) return input.value = tag;

		var value = input.value.replace(/(?:^\s+|\s(?=$|\s))/, ""),
		    match = value.match(new RegExp("\\b" + tag + "\\b", "i"));

		return match
			? input.setSelectionRange(match.index, match.index + tag.length)
			: input.value += (value && ", ") + tag;
	}
});

UIControl.deploy("label.file input[type=hidden]", function(input) {
	var name = input.name,
	    span = input.parentNode,
	    file = DOM.getFirstElement("a", span),
	    back = DOM.insertElementWithText("em", "Remove File", span);

	DOM.Event.addListener(back, "click", function(event) {
		event.preventDefault();

		DOM.getChildren(span).forEach(DOM.removeNode);
		DOM.insertElementWithAttributes("input", {name: input.name, type: "file"}, span);
	});
}, {
	remove: function(input) {
		var file = input.parentNode,
		    link = DOM.getFirstElement("a", file);

		file.removeChild(link);

		input.type = "file";
	}
});

UIControl.deploy("*.contextual", function(item) {
	var context = this.context || (this.context = document.getElementById("context"));

	DOM.Event.addListener(context, "change", function() {
		var parent = context.options[context.selectedIndex].parentNode,
		    option = context.value.replace(/\W+/g, "_"),
		    group  = parent.label && parent.label.replace(/\W+/g, "_"),
		    target = DOM.hasClass(option, item) || parent && DOM.hasClass(group, item);
		    active = target ^ DOM.hasClass("inverse", item);

		DOM.setClass("irrelevant", item, !active, document.body.offsetHeight); // Opera reflow
	})();
});

// System Messages

DOM.onready(function() {
	var heading = DOM.getFirstElement("h1"),
	    message = document.getElementById("notice");

	if (!message) {
		message    = DOM.createElementWithText("p", "");
		message.id = "notice";
	}

	// TO-DO Fade behaviour for new messages

	Symphony.Message = function(text, type) {
		var nodes = message.childNodes, i = nodes.length;

		while (--i) message.removeChild(nodes[i]);

		message.firstChild.data = text;
		message.className       = type;

		if (!message.parentNode) DOM.insertNode(message, heading.parentNode, heading);
	};

	Symphony.Message.clear = function(type) {
		if (DOM.hasClass(type, message)) DOM.removeNode(message);
	};
});

// Data Lists

DOM.onready(function() {
	var table = DOM.getFirstElement("table"),
	    rows  = DOM.select("tbody input").map(function(input, position) {
		          var row = input.parentNode.parentNode;

		          if (input.checked) DOM.addClass("selected", row);

		          return row;
	          });

	if (!table || rows.length == 0) return;

	DOM.addClass("selectable", table);

	var action = DOM.select("div.actions select")[0],
	    select = new Selectable(rows, function(row) {
		             with (DOM.getFirstElement("input", row)) checked = !checked;
	             });

	if (DOM.hasClass("orderable", table)) {
		var base = Symphony.WEBSITE + "/symphony",
		    href = base + "/ajax/reorder" + location.href.slice(base.length),
		    save = new Request(href, function(request) {
			           DOM.removeClass("busy", table);

			           return (request.status != 200)
				           ? Symphony.Message(Symphony.Language.REORDER_ERROR, "reorder error")
				           : Symphony.Message.clear("reorder");
		           });

		Orderable.implement(rows, function(row) {
			DOM.addClass("busy", table);

			save.post(DOM.select("input", table).map(serialise).join("&"));
		});
	}

	function serialise(input, position) {
		return input.name + "=" + position;
	}

	if (!action) return;

	var template    = Symphony.Language.SEARCH_ENTRIES,
	    section     = /[^\u2013]+.\s*(.+)|/.exec(document.title)[1],
	    placeholder = template.replace("{$section}", section),
	    search      = DOM.getPreviousElement(action);

	DOM.Event.addListener(action.form, "submit", function(event) {
		var actual = action.value && select.items.length,
		    option = action.options[action.selectedIndex],
		    denied = !actual || DOM.hasClass("destructive", option) && !permitted(option);

		if (denied) event.preventDefault();
	});

	function permitted(option) {
		var lang = Symphony.Language.CONFIRM_MANY,
		    deed = option.firstChild.data.toLowerCase(),
		    data = select.items.length;

		if (data == 1) {
			lang = Symphony.Language.CONFIRM_SINGLE;
			data = DOM.getFirstElement("td", select.items[0]).textContent; // TO-DO Fix me
		}

		return confirm(lang.replace("{$action}", deed).replace(/{\$\w+}/, data));
	}

	if (!search) return;

	DOM.Event.addListener(search, "focus", function() {
		if (search.value == placeholder || search.value == "") search.value = "";
	});

	DOM.Event.addListener(search, "blur", function() {
		if (search.value && search.value != placeholder) DOM.addClass("active", search);

		else search.value = DOM.removeClass("active", search) || placeholder;
	})();

	DOM.Event.addListener(action.form, "submit", function() {
		if (search.value != placeholder) this.submit();
	});
});

// Page Settings

DOM.onready(function() {
	var configure = document.getElementById("configure");

	if (!configure) return;

	var maximum = configure.offsetHeight,
	    initial = /blueprints?\/pages\/new/i.test(location.href) ? maximum : 0;
	    animate = new Animation(initial, initial, Animation.updateHeight(configure, true)),
	    button  = DOM.createElementWithClass("a", "configure button");

	if (initial) {
		DOM.addClass("active", button);
		button.title = Symphony.Language.HIDE_CONFIG;
	} else {
		DOM.addClass("irrelevant", configure);
		button.title = Symphony.Language.SHOW_CONFIG;
	}

	button.accessKey = "c";

	DOM.Event.addListener(button, "click", function() {
		if (!DOM.hasClass("irrelevant", configure)) animate.stop();

		DOM.removeClass("irrelevant", configure);
		DOM.toggleClass("active", this);

		animate.target = maximum - animate.target;

		Animation.run(animate);

		this.title = animate.target
			? Symphony.Language.HIDE_CONFIG
			: Symphony.Language.SHOW_CONFIG;
	});

	DOM.getFirstElement("h2").appendChild(button);
});

// Utilities

DOM.onready(function() {
	var list     = document.getElementById("utilities"),
	    textarea = DOM.getFirstElement("textarea");

	if (!list) return;

	DOM.select("a", list).forEach(function(link, index) {
		var path = new RegExp("<xsl:i(?:mport|nclude)\\s+href=\".*?" + link.firstChild.data),
		    item = link.parentNode;

		DOM.Event.addListener(textarea, "blur", function() {
			DOM.setClass("selected", item, path.test(textarea.value));
		})();

		// TO-DO Add/remove includes (Planned feature for 2.1.0)
	});
});

// Data Sources

DOM.onready(function() {
	var source = document.getElementById("context"),
	    output = document.getElementById("output-param-name");

	if (!output) return;

	DOM.Event.addListener(DOM.getFirstElement("input"), "blur", function() {
		var words = this.value.match(/\w+/g) || Symphony.Language.UNTITLED.match(/\w+/g);

		output.firstChild.data = "$ds-" + words.join("-").toLowerCase();
	});

	DOM.select("select.filtered").forEach(function(select) {
		var options = DOM.select("optgroup", select).reduce(getOptions, {}),
		    minimum = select.options.length;

		DOM.Event.addListener(source, "change", function() {
			updateOptions(select, options, minimum);
		})();
	});

	function getOptions(options, optgroup) {
		options[optgroup.label] = DOM.select("option", optgroup);

		DOM.removeNode(optgroup);

		return options;
	}

	function updateOptions(select, options, minimum) {
		var i    = select.length = minimum,
		    name = source.options[source.selectedIndex].firstChild.data;

		if (name in options) options[name].forEach(addOption, select);
	}

	function addOption(option) {
		this.options[this.options.length] = option;
	}
});

// Change Password

DOM.onready(function() {
	var fields = document.getElementById("change-password");

	if (!fields) return;

	var change = DOM.createElementWithText("div", "Password "),
	    button = DOM.createElementWithText("button", "Change Password"),
	    help   = DOM.getNextElement(fields);

	change.className = "label";

	DOM.insertElement("span", change).appendChild(button);

	DOM.Event.addListener(button, "click", function(event) {
		DOM.replaceNode(change, help);
		DOM.insertNode(fields, help.parentNode, help);
		DOM.getFirstElement("input", fields).focus();

		event.preventDefault();
	});

	DOM.replaceNode(fields, change);
	DOM.removeNode(help, document.body.offsetHeight); // Opera reflow
});

// Login

DOM.Event.addListener(window, "load", function() {
	var username = document.getElementsByName("username")[0];

	if (username && username.value.length == 0) username.focus();
});
