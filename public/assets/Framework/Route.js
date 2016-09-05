"use strict";
function Route(){
    var self = this;
    //object location
    self.url_parts = window.location.href.split('?');

    if(self.url_parts[1] !== undefined){
        self.get_param_parts = self.url_parts[1].split('&');

        self.get_param = [];
        Array.prototype.forEach.call(self.get_param_parts, function(v, k){
            var parts = v.split('=');
            self.get_param[parts[0]] = parts[1];
        });
    }
}