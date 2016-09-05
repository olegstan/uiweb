"use strict";
function ClearInput(){
    var self = this;

    self.elems = document.querySelectorAll('[data-clear-input]');
    self.init();
}

ClearInput.prototype.init = function () {
    var self = this;

    Array.prototype.forEach.call(self.elems, function (el, i) {
        var input = document.getElementById(el.getAttribute('data-clear-input'));
        input.addEventListener('keyup', function(e){
            if(input.value.length > 0){
                el.style.display = 'block';
            }else {
                el.style.display = 'none';
            }
        });
        el.addEventListener('click', function(e){
            input.value = '';
            input.focus(e);
            el.style.display = 'none';
        })
    });
};