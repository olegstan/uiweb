"use strict";
Autoload.prototype.loadClasses([], function() {

    String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.replace(new RegExp(search, 'g'), replacement);
    };

    Autoload.prototype.registerClass("Framework/String");
});

