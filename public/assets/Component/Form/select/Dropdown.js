"use strict";
function Dropdown(options){

    this.elem = options.elem;
    this.directions = [];
    this.dimenshins = [];
    this.statuses = [];

    this.init();
}

Dropdown.prototype.init = function(){
    var self = this;
    Array.prototype.forEach.call(self.elem, function(v, k){
        var event = v.getAttribute('data-dropdown-event');
        var element = document.getElementById(v.getAttribute('data-dropdown-selector'));
        self.directions[k] = v.getAttribute('data-dropdown-direction');
        self.dimenshins[k] = self.detectDimenshion(v.getAttribute('data-dropdown-direction'));
        // 0 - not showing
        self.statuses[k] = 0;

        element.addEventListener("mousemove", function(e){
            self.fadeIn(k);
        });
        element.addEventListener("mouseover", function(e){
            self.fadeIn(k);
        });

        //хак для вложенных объектов
        element.addEventListener("mouseout", function(e){
            var e = e.relatedTarget;
            if (e.parentNode.parentNode == this || e.parentNode == this || e == this) {
                return;
            }
            self.fadeOut(k);
        });


    });
};
Dropdown.prototype.fadeIn = function(k){
    var self = this;
    var to = self.elem[k][self.dimenshins[k]];
    var from = parseInt(self.elem[k].style.bottom) || 0;
    var step = 0;

    function animateUp(){
        if(from < to){
            step += 5;
            var newFrom = from + step;
            self.elem[k].style.bottom = newFrom + 'px';
            if(newFrom < to) {
                requestAnimationFrame(animateUp);
            }else{
                self.statuses[k] = 1;
            }
        }
    }
    requestAnimationFrame(animateUp);
};
Dropdown.prototype.fadeOut = function(k){
    var self = this;
    var from = parseInt(self.elem[k].style.bottom);
    var to = 0;
    var step = 0;

    function animateDown(){

        if(from > to){
            step += 10;
            var newFrom = from - step;
            self.elem[k].style.bottom = newFrom + 'px';
            if(newFrom > to) {
                requestAnimationFrame(animateDown);
            }else{
                self.statuses[k] = 0;
            }
        }
    }
    requestAnimationFrame(animateDown);


};

Dropdown.prototype.detectDimenshion = function(direction){
    switch (direction){
        case 'bottom':
        case 'top':
            return 'offsetHeight';
        case 'right':
        case 'left':
            return 'offsetWidth';
    }
};