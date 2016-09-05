"use strict";
function Cookie(){
    var self = this;

    self.cookie = [];
    self.parts = document.cookie.split(';');

    if(self.parts[1] !== undefined){
        self.cookei = [];
        Array.prototype.forEach.call(self.parts, function(v, k){
            var parts = v.split("=");
            self.cookei[parts[0]] = parts[1];
        });
    }
}