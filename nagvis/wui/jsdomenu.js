/*

  jsDOMenu Version 1.3
  Copyright (C) 2003 - 2005 Toh Zhiqiang
  Released on 16 January 2005
  jsDOMenu is distributed under the terms of the GNU GPL license
  Refer to license.txt for more informatiom

*/

/*
Determine whether the browser is IE5.0.
*/
function isIE50() { // Private method
  return isIE5() && !isIE55();
}

/*
Determine whether the browser is IE5.5.
*/
function isIE55() { // Private method
  return navigator.userAgent.indexOf("MSIE 5.5") > -1;
}

/*
Determine whether the browser is IE5.0 or IE5.5.
*/
function isIE5() { // Private method
  return navigator.userAgent.indexOf("MSIE 5") > -1;
}

/*
Determine whether the browser is IE6.
*/
function isIE6() { // Private method
  return navigator.userAgent.indexOf("MSIE 6") > -1 && navigator.userAgent.indexOf("Opera") == -1;
}

/*
Determine whether the browser is IE.
*/
function isIE() { // Private method
  return isIE5() || isIE6();
}

/*
Determine whether the browser is Opera.
*/
function isOpera() { // Private method
  return navigator.userAgent.indexOf("Opera") > -1;
}

/* 
Determine whether the browser is Safari.
*/
function isSafari() { // Private method
  return navigator.userAgent.indexOf("Safari") > -1;
}

/*
Determine the page render mode.

0: Quirks mode.
1: Strict mode.
*/
function getPageMode() { // Private method
  if (document.compatMode) {
    switch (document.compatMode) {
      case "BackCompat":
        return 0;
      case "CSS1Compat":
        return 1;
      case "QuirksMode":
        return 0;
    }
  }
  else {
    if (ie5) {
      return 0;
    }
    if (safari) {
      return 1;
    }
  }
  return 0;
}

/*
Alias for document.getElementById().
*/
function getElmId(id) { // Private method
  return document.getElementById(id);
}

/*
Alias for document.createElement().
*/
function createElm(tagName) { // Private method
  return document.createElement(tagName);
}

/*
Get the x-coordinate of the cursor position relative to the window.
*/
function getX(e) { // Private method
  if (!e) {
    var e = window.event;
  }
  if (safari) {
    return e.clientX - getScrollLeft();
  }
  else {
    return e.clientX;
  }
}

/*
Get the y-coordinate of the cursor position relative to the window.
*/
function getY(e) { // Private method
  if (!e) {
    var e = window.event;
  }
  if (safari) {
    return e.clientY - getScrollTop();
  }
  else {
    return e.clientY;
  }
}

/*
Get the scrollLeft property.
*/
function getScrollLeft() { // Private method
  switch (pageMode) {
    case 0:
      return document.body.scrollLeft;
    case 1:
      if (document.documentElement && document.documentElement.scrollLeft > 0) {
        return document.documentElement.scrollLeft;
      }
      else {
        return document.body.scrollLeft;
      }
  }
}

/*
Get the scrollTop property.
*/
function getScrollTop() { // Private method
  switch (pageMode) {
    case 0:
      return document.body.scrollTop;
    case 1:
      if (document.documentElement && document.documentElement.scrollTop > 0) {
        return document.documentElement.scrollTop;
      }
      else {
        return document.body.scrollTop;
      }
  }
}

/*
Get the clientHeight property.
*/
function getClientHeight() { // Private method
  switch (pageMode) {
    case 0:
      return document.body.clientHeight;
    case 1:
      if (safari) {
        return self.innerHeight;
      }
      else {
        if (!opera && document.documentElement && document.documentElement.clientHeight > 0) {
          return document.documentElement.clientHeight;
        }
        else {
          return document.body.clientHeight;
        }
      }
  }
}

/*
Get the clientWidth property.
*/
function getClientWidth() { // Private method
  switch (pageMode) {
    case 0:
      return document.body.clientWidth;
    case 1:
      if (safari) {
        return self.innerWidth;
      }
      else {
        if (!opera && document.documentElement && document.documentElement.clientWidth > 0) {
          return document.documentElement.clientWidth;
        }
        else {
          return document.body.clientWidth;
        }
      }
  }
}

/*
Convert the string into lower camel case.
*/
function toCamelCase(input) { // Private method
  var inputArray = input.split("-");
  if (inputArray.length == 1) {
    return inputArray[0];
  }
  else {
    var camelCase = inputArray[0];
    for (var i = 1, len = inputArray.length; i < len; i++) {
      camelCase += inputArray[i].charAt(0).toUpperCase() + inputArray[i].substring(1);
    }
    return camelCase;
  }
}

/*
Get the value of the property of the object.
*/
function getPropVal(obj, propertyName) { // Private method
  var propertyValue = obj.style[toCamelCase(propertyName)];
  if (propertyValue) {
    return propertyValue;
  }
  else {
    if (document.defaultView && document.defaultView.getComputedStyle) {
      return document.defaultView.getComputedStyle(obj, null).getPropertyValue(propertyName);
    }
    else {
      if (obj.currentStyle) {
        return obj.currentStyle[toCamelCase(propertyName)];
      }
      else {
        return null;
      }
    }
  }
}

/*
Get the integer value of the property of the object.
*/
function getPropIntVal(obj, propertyName) { // Private method
  return parseInt(getPropVal(obj, propertyName));
}

/*
Get the left position of the pop-up menu.
*/
function getMainMenuLeftPos(menuObj, x) { // Private method
  if (x + menuObj.offsetWidth <= getClientWidth()) {
    return x;
  }
  else {
    return x - menuObj.offsetWidth;
  }
}

/*
Get the top position of the pop-up menu.
*/
function getMainMenuTopPos(menuObj, y) { // Private method
  if (y + menuObj.offsetHeight <= getClientHeight()) {
    return y;
  }
  else {
    return y - menuObj.offsetHeight;
  }
}

/*
Get the left position of the submenu.
*/
function getSubMenuLeftPos(menuObj, x, offset) { // Private method
  if (x + menuObj.offsetWidth - 2 <= getClientWidth()) {
    return x - 2;
  }
  else {
    return x - menuObj.offsetWidth - offset;
  }
}

/*
Get the top position of the submenu.
*/
function getSubMenuTopPos(menuObj, y, offset) { // Private method
  var top = getPropIntVal(menuObj, btw);
  var bottom = getPropIntVal(menuObj, bbw);
  if (y + menuObj.offsetHeight <= getClientHeight()) {
    if (safari) {
      return y - top;
    }
    else {
      return y;
    }
  }
  else {
    if (safari) {
      return y - menuObj.offsetHeight + offset + bottom;
    }
    else {
      return y - menuObj.offsetHeight + offset + top + bottom;
    }
  }
}

/*
Pop up the submenu.
*/
function popUpSubMenu(menuItemObj) { // Private method
  var parentMenuObj = menuItemObj.parent.menuObj;
  var menuObj = menuItemObj.subMenu.menuObj;
  var x;
  var y;
  if (parentMenuObj.style.position == "fixed") {
    x = parentMenuObj.offsetLeft + parentMenuObj.offsetWidth - getPropIntVal(parentMenuObj, brw);
    y = parentMenuObj.offsetTop + menuItemObj.offsetTop + getPropIntVal(parentMenuObj, btw) - getPropIntVal(menuObj, btw);
    menuObj.style.position = "absolute";
    menuObj.style.left = getSubMenuLeftPos(menuObj, x, menuItemObj.offsetWidth) + px;
    menuObj.style.top = getSubMenuTopPos(menuObj, y, menuItemObj.offsetHeight) + px;
    menuObj.style.position = "fixed";
  }
  else {
    if (parentMenuObj.mode == "static" && !ie50) {
      x = menuItemObj.offsetLeft + parentMenuObj.offsetWidth - getPropIntVal(parentMenuObj, blw) - getPropIntVal(parentMenuObj, brw) - getScrollLeft();
      y = menuItemObj.offsetTop - getPropIntVal(menuObj, btw) - getScrollTop();
      if (ie55 || ie6) {
          x += getPropIntVal(parentMenuObj, blw);
          y += getPropIntVal(parentMenuObj, btw);
      }
      if (safari) {
        x += 8;
        y += getPropIntVal(menuObj, btw) + 13;
      }
      menuObj.style.left = (getSubMenuLeftPos(menuObj, x, menuItemObj.offsetWidth) + getScrollLeft()) + px;
      menuObj.style.top = (getSubMenuTopPos(menuObj, y, menuItemObj.offsetHeight) + getScrollTop()) + px;
    }
    else {
      x = parentMenuObj.offsetLeft + parentMenuObj.offsetWidth - getPropIntVal(parentMenuObj, brw) - getScrollLeft();
      y = parentMenuObj.offsetTop + menuItemObj.offsetTop + getPropIntVal(parentMenuObj, btw) - getPropIntVal(menuObj, btw) - getScrollTop();
      menuObj.style.left = (getSubMenuLeftPos(menuObj, x, menuItemObj.offsetWidth) + getScrollLeft()) + px;
      menuObj.style.top = (getSubMenuTopPos(menuObj, y, menuItemObj.offsetHeight) + getScrollTop()) + px;
    }
  }
  if (ie && menuObj.mode == "fixed") {
    menuObj.initialLeft = parseInt(menuObj.style.left) - getScrollLeft();
    menuObj.initialTop = parseInt(menuObj.style.top) - getScrollTop();
  }
  menuObj.style.visibility = "visible";
}

/*
Pop up the main menu.
*/
function popUpMainMenu(menuObj, e) { // Private method
  menuObj.style.left = (getMainMenuLeftPos(menuObj, getX(e)) + getScrollLeft()) + px;
  menuObj.style.top = (getMainMenuTopPos(menuObj, getY(e)) + getScrollTop()) + px;
  var display = popUpMenuObj.menuObj.style.display;
  popUpMenuObj.menuObj.style.display = "none";
  popUpMenuObj.menuObj.style.visibility = "visible";
  popUpMenuObj.menuObj.style.display = display;
}

/*
Refresh the menu items.
*/
function refreshMenuItems(menuObj) { // Private method
  for (var i = 0, len = menuObj.childNodes.length; i < len; i++) {
    if (menuObj.childNodes[i].enabled) {
      menuObj.childNodes[i].className = menuObj.childNodes[i].itemClassName;
      if (menuObj.childNodes[i].subMenu) {
        menuObj.childNodes[i].arrowObj.className = menuObj.childNodes[i].arrowClassName;
      }
      if (menuObj.childNodes[i].iconObj) {
        menuObj.childNodes[i].iconObj.className = menuObj.childNodes[i].iconClassName;
      }
    }
  }
}

/*
Event handler that handles onmouseover event of the menu item.
*/
function menuItemOver(e) { // Private method
  var previousItem = this.parent.previousItem;
  if (previousItem) {
    if (previousItem.className == previousItem.itemClassNameOver) {
      previousItem.className = previousItem.itemClassName;
    }
    if (previousItem.subMenu) {
      previousItem.className = previousItem.itemClassName;
      previousItem.arrowObj.className = previousItem.arrowClassName;
      if (previousItem.iconObj) {
        previousItem.iconObj.className = previousItem.iconClassName;
      }
    }
    var menuObj = getElmId(this.parent.menuObj.id);
    for (var i = 0, len = menuObj.childNodes.length; i < len; i++) {
      if (menuObj.childNodes[i].enabled && menuObj.childNodes[i].subMenu) {
        hideMenus(menuObj.childNodes[i].subMenu.menuObj);
      }
    }
  }
  if (this.enabled) {
    this.className = this.itemClassNameOver;
    if (this.subMenu) {
      this.arrowObj.className = this.arrowClassNameOver;
      popUpSubMenu(this);
    }
    if (this.iconObj && this.iconClassNameOver) {
      this.iconObj.className = this.iconClassNameOver;
    }
  }
  this.parent.previousItem = this;
}

/*
Event handler that handles onclick event of the menu item.
*/
function menuItemClick(e) { // Private method
  if (this.enabled && this.actionOnClick) {
    var action = this.actionOnClick;
    if (action.indexOf("link:") == 0) {
      location.href = action.substr(5);
    }
    else {
      if (action.indexOf("code:") == 0) {
        eval(action.substr(5));
      }
      else {
        location.href = action;
      }
    }
  }
  if (!e) {
    var e = window.event;
    e.cancelBubble = true;
  }
  if (e.stopPropagation) {
    e.stopPropagation();
  }
  if (this.parent.menuObj.mode == "cursor") {
    hideCursorMenus();
  }
  if (this.parent.menuObj.mode == "absolute" || this.parent.menuObj.mode == "fixed") {
    hideVisibleMenus();
    if (typeof(hideMenuBarMenus) == "function") {
      hideMenuBarMenus();
    }
  }
}

/*
Event handler that handles onmouseout event of the menu item.
*/
function menuItemOut() { // Private method
  if (this.enabled) {
    if (!(this.subMenu && this.subMenu.menuObj.style.visibility == "visible")) {
      this.className = this.itemClassName;
    }
    if (this.subMenu) {
      if (this.subMenu.menuObj.style.visibility == "visible") {
        this.arrowObj.className = this.arrowClassNameOver;
        if (this.iconObj) {
          this.iconObj.className = this.iconClassNameOver;
        }
      }
    }
    else {
      if (this.iconObj) {
        this.iconObj.className = this.iconClassName;
      }
    }
  }
}

/*
Determine whether any of the tag name/tag id pair in the filter matches the tagName/tagId pair.
*/
function findMatch(tagName, tagId, filter) { // Private method
  for (var i = 0, len = filter.length; i < len; i++) {
    var filterArray = filter[i].toLowerCase().split(".");
    if ((filterArray[0] == "*" && filterArray[1] == "*") || 
        (filterArray[0] == "*" && filterArray[1] == tagId) || 
        (filterArray[0] == tagName && filterArray[1] == "*") || 
        (filterArray[0] == tagName && filterArray[1] == tagId)) {
      return true;
    }
  }
  return false;
}

/*
Determine whether to show or hide the menu.
*/
function canShowMenu(tagName, tagId, allExcept, noneExcept) { // Private method
  if (allExcept.length > 0) {
    return (!findMatch(tagName.toLowerCase(), tagId.toLowerCase(), allExcept));
  }
  else {
    if (noneExcept.length > 0) {
      return findMatch(tagName.toLowerCase(), tagId.toLowerCase(), noneExcept);
    }
    else {
      return true;
    }
  }
}

/*
Shows/Hides the pop-up menu.
*/
function activatePopUpMenu(e) { // Private method
  if (!popUpMenuObj) {
    return;
  }
  var state = popUpMenuObj.menuObj.style.visibility;
  if (state == "visible") {
    for (var i = 1; i <= menuCount; i++) {
      var menuObj = getElmId("DOMenu" + i);
      if (menuObj.mode == "cursor") {
        menuObj.style.visibility = "hidden";
        menuObj.style.left = "0px";
        menuObj.style.top = "0px";
        menuObj.initialLeft = 0;
        menuObj.initialTop = 0;
        refreshMenuItems(menuObj);
      }
    }
  }
  else {
    if (!e) {
      var e = window.event;
    }
    var targetElm = (e.target) ? e.target : e.srcElement;
    if (targetElm.nodeType == 3) {
      targetElm = targetElm.parentNode;
    }
    if (canShowMenu(targetElm.tagName, targetElm.id, popUpMenuObj.menuObj.allExceptFilter, popUpMenuObj.menuObj.noneExceptFilter)) {
      popUpMainMenu(popUpMenuObj.menuObj, e);
    }
  }
}

/*
Event handler that handles left click event.
*/
function leftClickHandler(e) { // Private method
  if (getX(e) > getClientWidth() || getY(e) > getClientHeight()) {
    return;
  }
  if (!e) {
    var e = window.event;
  }
  if (e.button && e.button == 2) {
    return;
  }
  hideVisibleMenus();
  if (typeof(hideMenuBarMenus) == "function") {
    hideMenuBarMenus();
  }
  if (popUpMenuObj) {
    var state = popUpMenuObj.menuObj.style.visibility;
    if (state == "visible" && (hideValue == 0 || hideValue == 2)) {
      activatePopUpMenu(e);
    }
    if ((state == "hidden" || state == "") && (showValue == 0 || showValue == 2)) {
      activatePopUpMenu(e);
    }
  }
}

/*
Event handler that handles right click event.
*/
function rightClickHandler(e) { // Private method
  if (getX(e) > getClientWidth() || getY(e) > getClientHeight()) {
    return;
  }
  hideVisibleMenus();
  if (typeof(hideMenuBarMenus) == "function") {
    hideMenuBarMenus();
  }
  if (popUpMenuObj) {
    var state = popUpMenuObj.menuObj.style.visibility;
    if (state == "visible" && (hideValue == 1 || hideValue == 2)) {
      activatePopUpMenu(e);
      return false;
    }
    if ((state == "hidden" || state == "") && (showValue == 1 || showValue == 2)) {
      activatePopUpMenu(e);
      return false;
    }
  }
}

/*
Event handler that handles scroll event.
*/
function scrollHandler() { // Private method
  for (var i = 1; i <= menuCount; i++) {
      var menuObj = getElmId("DOMenu" + i);
      if (ie && menuObj.mode == "fixed") {
        menuObj.style.left = (menuObj.initialLeft + getScrollLeft()) + px;
        menuObj.style.top = (menuObj.initialTop + getScrollTop()) + px;
      }
  }
  if (typeof(menuBarScrollHandler) == "function") {
    menuBarScrollHandler();
  }
}

/*
Show the icon before the display text.
Arguments:
className          : Required. String that specifies the CSS class selector for the icon.
classNameOver      : Optional. String that specifies the CSS class selector for the icon when 
                     the cursor is over the menu item.
*/
function showMenuItemIcon() { // Public method
  var iconElm = createElm("span");
  iconElm.id = this.id + "Icon";
  iconElm.className = arguments[0];
  this.insertBefore(iconElm, this.firstChild);
  var height;
  if (ie) {
    height = getPropIntVal(iconElm, "height");
  }
  else {
    height = iconElm.offsetHeight;
  }
  iconElm.style.top = Math.floor((this.offsetHeight - height) / 2) + px;
  if (ie) {
    var left = getPropIntVal(iconElm, "left");
    if (ie55 || ie6) {
      iconElm.style.left = (left - getPropIntVal(this, "padding-left")) + px;
    }
    else {
      iconElm.style.left = left + px;
    }
  }
  this.iconClassName = iconElm.className;
  if (arguments.length > 1 && arguments[1].length > 0) {
    this.iconClassNameOver = arguments[1];
  }
  this.iconObj = iconElm;
  this.setIconClassName = function(className) { // Public method
    this.iconClassName = className;
    this.iconObj.className = this.iconClassName;
  };
  this.setIconClassNameOver = function(classNameOver) { // Public method
    this.iconClassNameOver = classNameOver;
  };
}

/*
Set the menu object that will show up when the cursor is over the menu item object.
Argument:
menuObj            : Required. Menu object that will show up when the cursor is over the 
                     menu item object.
*/
function setSubMenu(menuObj) { // Public method
  var arrowElm = createElm("div");
  arrowElm.id = this.id + "Arrow";
  arrowElm.className = this.arrowClassName;
  this.appendChild(arrowElm);
  var height;
  if (ie) {
    height = getPropIntVal(arrowElm, "height");
  }
  else {
    height = arrowElm.offsetHeight;
  }
  arrowElm.style.top = Math.floor((this.offsetHeight - height) / 2) + px;
  this.subMenu = menuObj;
  this.arrowObj = arrowElm;
  this.setArrowClassName = function(className) { // Public method
    this.arrowClassName = className;
    this.arrowObj.className = this.arrowClassName;
  };
  this.setArrowClassNameOver = function(classNameOver) { // Public method
    this.arrowClassNameOver = classNameOver;
  };
  menuObj.menuObj.style.zIndex = this.parent.menuObj.level + 1;
  menuObj.menuObj.level = this.parent.menuObj.level + 1;
}

/*
Add a new menu item to the menu.
Argument:
menuItemObj        : Required. Menu item object that is going to be added to the menu object.
*/
function addMenuItem(menuItemObj) { // Public method
  if (menuItemObj.displayText == "-") {
    var hrElm = createElm("hr");
    var itemElm = createElm("div");
    itemElm.appendChild(hrElm);
    itemElm.id = menuItemObj.id;
    if (menuItemObj.className.length > 0) {
      itemElm.sepClassName = menuItemObj.className;
    }
    else {
      itemElm.sepClassName = menuItemObj.sepClassName;
    }
    itemElm.className = itemElm.sepClassName;
    this.menuObj.appendChild(itemElm);
    itemElm.parent = this;
    itemElm.setClassName = function(className) { // Public method
      this.sepClassName = className;
      this.className = this.sepClassName;
    };
    itemElm.onclick = function(e) { // Private method
      if (!e) {
        var e = window.event;
        e.cancelBubble = true;
      }
      if (e.stopPropagation) {
        e.stopPropagation();
      }
    };
    itemElm.onmouseover = menuItemOver;
    if (menuItemObj.itemName.length > 0) {
      this.items[menuItemObj.itemName] = itemElm;
    }
    else {
      this.items[this.items.length] = itemElm;
    }
  }
  else {
    var itemElm = createElm("div");
    itemElm.id = menuItemObj.id;
    itemElm.actionOnClick = menuItemObj.actionOnClick;
    itemElm.enabled = menuItemObj.enabled;
    itemElm.itemClassName = menuItemObj.className;
    itemElm.itemClassNameOver = menuItemObj.classNameOver;
    itemElm.className = itemElm.itemClassName;
    itemElm.subMenu = null;
    itemElm.arrowClassName = arrowClassName;
    itemElm.arrowClassNameOver = arrowClassNameOver;
    var textNode = document.createTextNode(menuItemObj.displayText);
    itemElm.appendChild(textNode);
    this.menuObj.appendChild(itemElm);
    itemElm.parent = this;
    itemElm.setClassName = function(className) { // Public method
      this.itemClassName = className;
      this.className = this.itemClassName;
    };
    itemElm.setClassNameOver = function(classNameOver) { // Public method
      this.itemClassNameOver = classNameOver;
    };
    itemElm.setDisplayText = function(text) { // Public method
      if (this.childNodes[0].nodeType == 3) {
        this.childNodes[0].nodeValue = text;
      }
      else {
        this.childNodes[1].nodeValue = text;
      }
    };
    itemElm.setSubMenu = setSubMenu;
    itemElm.showIcon = showMenuItemIcon;
    itemElm.onmouseover = menuItemOver;
    itemElm.onclick = menuItemClick;
    itemElm.onmouseout = menuItemOut;
    if (menuItemObj.itemName.length > 0) {
      this.items[menuItemObj.itemName] = itemElm;
    }
    else {
      this.items[this.items.length] = itemElm;
    }
  }
}

/*
Create a new menu item object.
Arguments:
displayText        : Required. String that specifies the text to be displayed on the menu item. If 
                     displayText = "-", a menu separator will be created instead.
itemName           : Optional. String that specifies the name of the menu item. Defaults to "" (no 
                     name).
actionOnClick      : Optional. String that specifies the action to be done when the menu item is 
                     being clicked. Defaults to "" (no action).
enabled            : Optional. Boolean that specifies whether the menu item is enabled/disabled. 
                     Defaults to true.
className          : Optional. String that specifies the CSS class selector for the menu item. 
                     Defaults to "jsdomenuitem".
classNameOver      : Optional. String that specifies the CSS class selector for the menu item when 
                     the cursor is over it. Defaults to "jsdomenuitemover".
*/
function menuItem() { // Public method
  this.displayText = arguments[0];
  if (this.displayText == "-") {
    this.id = "menuSep" + (++sepCount);
    this.className = sepClassName;
  }
  else {
    this.id = "menuItem" + (++menuItemCount);
    this.className = menuItemClassName;
  }
  this.itemName = "";
  this.actionOnClick = "";
  this.enabled = true;
  this.classNameOver = menuItemClassNameOver;
  this.sepClassName = sepClassName;
  var len = arguments.length;
  if (len > 1 && arguments[1].length > 0) {
    this.itemName = arguments[1];
  }
  if (len > 2 && arguments[2].length > 0) {
    this.actionOnClick = arguments[2];
  }
  if (len > 3 && typeof(arguments[3]) == "boolean") {
    this.enabled = arguments[3];
  }
  if (len > 4 && arguments[4].length > 0) {
    if (arguments[4] == "-") {
      this.className = arguments[4];
      this.sepClassName = arguments[4];
    }
    else {
      this.className = arguments[4];
    }
  }
  if (len > 5 && arguments[5].length > 0) {
    this.classNameOver = arguments[5];
  }
}

/*
Create a new menu object.
Arguments:
width              : Required. Integer that specifies the width of the menu.
mode               : Optional. String that specifies the mode of the menu. Defaults to "cursor".
id                 : Optional, except when mode = "static". String that specifies the id of 
                     the element that will contain the menu. This argument is required when 
                     mode = "static".
alwaysVisible      : Optional. Boolean that specifies whether the menu is always visible. Defaults 
                     to false.
className          : Optional. String that specifies the CSS class selector for the menu. Defaults 
                     to "jsdomenudiv".
*/
function jsDOMenu() { // Public method
  this.items = new Array();
  var menuElm;
  var len = arguments.length;
  if (len > 2 && arguments[2].length > 0 && arguments[1] == "static") {
    menuElm = getElmId(arguments[2]);
    if (!menuElm) {
      return;
    }
    staticMenuId[staticMenuId.length] = arguments[2];
  }
  else {
    menuElm = createElm("div");
    menuElm.id = "DOMenu" + (++menuCount);
  }
  menuElm.level = 10;
  menuElm.previousItem = null;
  menuElm.allExceptFilter = allExceptFilter;
  menuElm.noneExceptFilter = noneExceptFilter;
  menuElm.className = menuClassName;
  menuElm.mode = menuMode;
  menuElm.alwaysVisible = false;
  menuElm.initialLeft = 0;
  menuElm.initialTop = 0;
  if (len > 1 && arguments[1].length > 0) {
    switch (arguments[1]) {
      case "cursor":
        menuElm.style.position = "absolute";
        menuMode.mode = "cursor";
        break;
      case "absolute":
        menuElm.style.position = "absolute";
        menuElm.mode = "absolute";
        break;
      case "fixed":
        if (ie) {
          menuElm.style.position = "absolute";
        }
        else {
          menuElm.style.position = "fixed";
        }
        menuElm.mode = "fixed";
        break;
      case "static":
        menuElm.style.position = "static";
        menuElm.mode = "static";
        break;
    }
  }
  if (len > 3 && typeof(arguments[3]) == "boolean") {
    menuElm.alwaysVisible = arguments[3];
  }
  if (len > 4 && arguments[4].length > 0) {
    menuElm.className = arguments[4];
  }
  menuElm.style.width = arguments[0] + px;
  menuElm.style.left = "0px";
  menuElm.style.top = "0px";
  if (menuElm.mode != "static") {
    document.body.appendChild(menuElm);
  }
  if (!getPropVal(menuElm, blw)) {
    menuElm.style.borderWidth = menuBorderWidth + px;
  }
  this.menuObj = menuElm;
  this.addMenuItem = addMenuItem;
  this.setClassName = function(className) { // Public method
    this.menuObj.className = className;
  };
  this.setMode = function(mode) { // Public method
    switch (mode) {
      case "cursor":
        this.menuObj.style.position = "absolute";
        this.menuObj.mode = "cursor";
        break;
      case "absolute":
        this.menuObj.style.position = "absolute";
        this.menuObj.mode = "absolute";
        this.menuObj.initialLeft = parseInt(this.menuObj.style.left);
        this.menuObj.initialTop = parseInt(this.menuObj.style.top);
        break;
      case "fixed":
        if (ie) {
          this.menuObj.style.position = "absolute";
          this.menuObj.initialLeft = parseInt(this.menuObj.style.left);
          this.menuObj.initialTop = parseInt(this.menuObj.style.top);
        }
        else {
          this.menuObj.style.position = "fixed";
        }
        this.menuObj.mode = "fixed";
        break;
    }
  };
  this.setAlwaysVisible = function(alwaysVisible) { // Public method
    if (typeof(alwaysVisible) == "boolean") {
      this.menuObj.alwaysVisible = alwaysVisible;
    }
  };
  this.show = function() { // Public method
    this.menuObj.style.visibility = "visible";
  };
  this.hide = function() { // Public method
    this.menuObj.style.visibility = "hidden";
    if (this.menuObj.mode == "cursor") {
      this.menuObj.style.left = "0px";
      this.menuObj.style.top = "0px";
      this.menuObj.initialLeft = 0;
      this.menuObj.initialTop = 0;
    }
  };
  this.setX = function(x) { // Public method
    this.menuObj.initialLeft = x;
    this.menuObj.style.left = x + px;
  };
  this.setY = function(y) { // Public method
    this.menuObj.initialTop = y;
    this.menuObj.style.top = y + px;
  };
  this.moveTo = function(x, y) { // Public method
    this.menuObj.initialLeft = x;
    this.menuObj.initialTop = y;
    this.menuObj.style.left = x + px;
    this.menuObj.style.top = y + px;
  };
  this.moveBy = function(x, y) { // Public method
    var left = parseInt(this.menuObj.style.left);
    var top = parseInt(this.menuObj.style.top);
    this.menuObj.initialLeft = left + x;
    this.menuObj.initialTop = top + y;
    this.menuObj.style.left = (left + x) + px;
    this.menuObj.style.top = (top + y) + px;
  };
  this.setAllExceptFilter = function(filter) { // Public method
    this.menuObj.allExceptFilter = filter;
    this.menuObj.noneExceptFilter = new Array();
  };
  this.setNoneExceptFilter = function(filter) { // Public method
    this.menuObj.noneExceptFilter = filter;
    this.menuObj.allExceptFilter = new Array();
  };
  this.setBorderWidth = function(width) { // Public method
    this.menuObj.style.borderWidth = width + px;
  };
}

/*
Specifies how the pop-up menu shows/hide.
Arguments:
showValue          : Required. Integer that specifies how the menu shows.
hideValue          : Optional. Integer that specifies how the menu hides. If not specified, the 
                     menu shows/hides in the same manner.

0: Shows/Hides the menu by left click only.
1: Shows/Hides the menu by right click only.
2: Shows/Hides the menu by left or right click.
*/
function activatePopUpMenuBy() { // Public method
  showValue = typeof(arguments[0]) == "number" && arguments[0] > -1 ? arguments[0] : 0;
  if (arguments.length > 1) {
    hideValue = typeof(arguments[1]) == "number" && arguments[1] > -1 ? arguments[1] : 0;
  }
  else {
    hideValue = showValue;
  }
  if (showValue == 1 || showValue == 2 || hideValue == 1 || hideValue == 2) {
    document.oncontextmenu = rightClickHandler;
  }
}

/*
Hide all menus, except those with alwaysVisible = true.
*/
function hideAllMenus() { // Public method
  for (var i = 1; i <= menuCount; i++) {
    var menuObj = getElmId("DOMenu" + i);
    if (!menuObj.alwaysVisible) {
      if (menuObj.style.position == "fixed") {
        menuObj.style.position == "absolute";
        menuObj.style.visibility = "hidden";
        menuObj.style.position == "fixed";
      }
      else {
        menuObj.style.visibility = "hidden";
        if (menuObj.mode == "cursor") {
          menuObj.style.left = "0px";
          menuObj.style.top = "0px";
          menuObj.initialLeft = 0;
          menuObj.initialTop = 0;
        }
      }
    }
    refreshMenuItems(menuObj);
  }
  for (var i = 0, len = staticMenuId.length; i < len; i++) {
    refreshMenuItems(getElmId(staticMenuId[i]));
  }
}

/*
Hide all menus with mode = "cursor", except those with alwaysVisible = true.
*/
function hideCursorMenus() { // Public method
  for (var i = 1; i <= menuCount; i++) {
    var menuObj = getElmId("DOMenu" + i);
    if (menuObj.mode == "cursor" && !menuObj.alwaysVisible) {
      menuObj.style.visibility = "hidden";
      menuObj.style.left = "0px";
      menuObj.style.top = "0px";
      menuObj.initialLeft = 0;
      menuObj.initialTop = 0;
    }
    if (menuObj.mode == "cursor") {
      refreshMenuItems(menuObj);
    }
  }
}

/*
Hide all menus with mode = "absolute" or mode = "fixed" or mode = "static", except those with 
alwaysVisible = true.
*/
function hideVisibleMenus() { // Public method
  for (var i = 1; i <= menuCount; i++) {
    var menuObj = getElmId("DOMenu" + i);
    if ((menuObj.mode == "absolute" || menuObj.mode == "fixed") && !menuObj.alwaysVisible) {
      if (menuObj.style.position == "fixed") {
        menuObj.style.position = "absolute";
        menuObj.style.visibility = "hidden";
        menuObj.style.position = "fixed";
      }
      else {
        menuObj.style.visibility = "hidden";
        menuObj.style.left = "0px";
        menuObj.style.top = "0px";
        menuObj.initialLeft = 0;
        menuObj.initialTop = 0;
      }
    }
    if (menuObj.mode == "absolute" || menuObj.mode == "fixed") {
      refreshMenuItems(menuObj);
    }
  }
  for (var i = 0, len = staticMenuId.length; i < len; i++) {
    refreshMenuItems(getElmId(staticMenuId[i]));
  }
  if (typeof(staticMenuBarId) == "object") {
    for (var i = 0, len = staticMenuBarId.length; i < len; i++) {
      refreshMenuBarItems(getElmId(staticMenuBarId[i]));
    }
  }
}

/*
Hide the menu and all its submenus.
Argument:
menuObj            : Required. Menu object that specifies the menu and all its submenus to 
                     be hidden.
*/
function hideMenus(menuObj) { // Public method
  refreshMenuItems(menuObj);
  for (var i = 0, len = menuObj.childNodes.length; i < len; i++) {
    if (menuObj.childNodes[i].enabled && menuObj.childNodes[i].subMenu) {
      hideMenus(menuObj.childNodes[i].subMenu.menuObj);
    }
  }
  if (menuObj.style.position == "fixed") {
    menuObj.style.position = "absolute";
    menuObj.style.visibility = "hidden";
    menuObj.style.position = "fixed";
  }
  else {
    menuObj.style.visibility = "hidden";
    menuObj.style.left = "0px";
    menuObj.style.top = "0px";
    menuObj.initialLeft = 0;
    menuObj.initialTop = 0;
  }
}

/*
Set the menu object to be the pop-up menu.
Argument:
menuObj            : Required. Menu object that specifies the pop-up menu.
*/
function setPopUpMenu(menuObj) { // Public method
  popUpMenuObj = menuObj;
}

/*
Check browser compatibility and create the menus.
*/
function initjsDOMenu() { // Public method
  if (document.createElement && document.getElementById) {
    createjsDOMenu();
  }
}

if (typeof(allExceptFilter) == "undefined") {
  var allExceptFilter = new Array("A.*", 
                                  "BUTTON.*", 
                                  "IMG.*", 
                                  "INPUT.*", 
                                  "OBJECT.*", 
                                  "OPTION.*", 
                                  "SELECT.*", 
                                  "TEXTAREA.*"); // Public field
}

if (typeof(noneExceptFilter) == "undefined") {
  var noneExceptFilter = new Array(); // Public field
}

if (typeof(menuClassName) == "undefined") {
  var menuClassName = "jsdomenudiv"; // Public field
}

if (typeof(menuItemClassName) == "undefined") {
  var menuItemClassName = "jsdomenuitem"; // Public field
}

if (typeof(menuItemClassNameOver) == "undefined") {
  var menuItemClassNameOver = "jsdomenuitemover"; // Public field
}

if (typeof(sepClassName) == "undefined") {
  var sepClassName = "jsdomenusep"; // Public field
}

if (typeof(arrowClassName) == "undefined") {
  var arrowClassName = "jsdomenuarrow"; // Public field
}

if (typeof(arrowClassNameOver) == "undefined") {
  var arrowClassNameOver = "jsdomenuarrowover"; // Public field
}

if (typeof(menuMode == "undefined")) {
  var menuMode = "cursor"; // Public field
}

if (typeof(menuBorderWidth) == "undefined") {
  var menuBorderWidth = 2; // Public field
}

var ie50 = isIE50(); // Private field
var ie55 = isIE55(); // Private field
var ie5 = isIE5(); // Private field
var ie6 = isIE6(); // Private field
var ie = isIE(); // Private field
var opera = isOpera(); // Private field
var safari = isSafari(); // Private field
var pageMode = getPageMode(); // Private field
var px = "px"; // Private field
var btw = "border-top-width"; // Private field
var bbw = "border-bottom-width"; // Private field
var blw = "border-left-width"; // Private field
var brw = "border-right-width"; // Private field
var menuCount = 0; // Private field
var menuItemCount = 0; // Private field
var sepCount = 0; // Private field
var popUpMenuObj = null; // Private field
var showValue = 0; // Private field
var hideValue = 0; // Private field
var staticMenuId = new Array(); // Private field
document.onclick = leftClickHandler;
window.onscroll = scrollHandler;
