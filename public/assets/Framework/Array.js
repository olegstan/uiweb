"use strict";
Autoload.prototype.loadClasses([], function() {

    Array.prototype.first = function()
    {
        if(this.length > 0){
            return this[0];
        }else{
            return null;
        }
    };

    Array.prototype.last = function()
    {
        if(this.length > 0){
            return this[this.length - 1];
        }else{
            return null;
        }
    };

    Autoload.prototype.registerClass("Framework/Array", []);
});

