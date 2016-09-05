"use strict";
function ShowPassword(){
    var self = this;

    self.elems = document.querySelectorAll('[data-show-input]');
    self.init();
}

ShowPassword.prototype.init = function () {
    var self = this;

    Array.prototype.forEach.call(self.elems, function (el, i) {
        var input = document.getElementById(el.getAttribute('data-show-input'));

        input.addEventListener('keyup', function (e) {
            if (input.value.length > 0) {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        });
        el.addEventListener('click', function(e){
            if(input.type == 'text'){
                input.type = 'password';
                el.src = el.getAttribute('data-show-input-closed');
            }else{
                el.src = el.getAttribute('data-show-input-opened');
                input.type = 'text';
            }
            input.focus(e);
        });

    });

};