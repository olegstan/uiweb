"use strict";
Autoload.prototype.loadClasses(["Framework/Array"], function() {

    NodeList.prototype.forEach = Array.prototype.forEach;

    NodeList.prototype.first = Array.prototype.first;

    NodeList.prototype.last = Array.prototype.last;

    Autoload.prototype.registerClass("Framework/NodeList");
});

