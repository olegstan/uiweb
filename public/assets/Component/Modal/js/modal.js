"use strict";
function Modal(options){
    var self = this;

    this.is_opened = 0;
    //this.elem = options.elem;
    this.popup = document.getElementById('popup');
    this.background = document.getElementById('modal-background');
    this.content = document.getElementById('popup-content');
    this.title = document.getElementById('popup-title');
    this.inner = document.getElementById('popup-inner');

    this.content.addEventListener('click', function(e){
        e.stopPropagation();
    });

    this.popup.addEventListener('click', function(e){
        self.close(e);
    });
}

Modal.prototype.open = function(e, title){
    e.preventDefault();
    //typeof(e.preventDefault) == 'function' ? e.preventDefault() : '';

    var self = this;

    if(!self.is_opened) {
        self.title.innerHTML = title;
        self.popup.style.display = 'block';
        self.is_opened = !self.is_opened;
    }

};

Modal.prototype.close = function(e){
    e.preventDefault();

    var self = this;
    if(self.is_opened) {
        self.popup.style.display = 'none';
        self.is_opened = !self.is_opened;
    }
};

//Modal.prototype.get = function(e, url, title, type, data){
Modal.prototype.get = function(e, obj){
    e.preventDefault();

    obj.data = typeof(obj.data) !== 'undefined' ? obj.data : null;
    obj.onloadstart = typeof(obj.onloadstart) !== 'undefined' ? obj.onloadstart : function(){};
    obj.success = typeof(obj.success) !== 'undefined' ? obj.success : function(){};
    obj.error = typeof(obj.error) !== 'undefined' ? obj.error : function(){};

    core.ajax({
        url: obj.url,
        data: obj.data,
        onloadstart: function (e, request) {
            obj.onloadstart(e, request);
        },
        success: function(response, e){
            console.log(response);
            obj.success(response, e);
        },
        error: function(response, e){
            obj.error(response, e);
        }
    }, obj.type, "GET");
};