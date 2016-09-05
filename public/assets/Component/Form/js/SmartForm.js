"use strict";
function SmartForm(options) {
    var self = this;

    self.elem = options.elem;
    console.log(self.elem);
    if(self.elem.tagName === 'TEXTAREA'){
        self.autoResize();
    }
}

SmartForm.prototype.autoResize = function(){
    var self = this;

    self.elem.addEventListener('keypress', function(e){
        self.lines = self.countLines();
        console.log(self.lines);
        if(self.elem.rows < self.lines){
            self.elem.rows++;
        }
    });
};

SmartForm.prototype.countLines = function(){
    var self = this;
    return self.elem.value.split(/\n/).length;
};