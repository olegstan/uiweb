"use strict";
function Preloader(options)
{
    this.popup = document.getElementById('popup-preloader');
    //this.timer;
    this.timer_seconds = 0;
}

Preloader.prototype.show = function (success) {
    var self = this;

    success = typeof(success) !== 'undefined' ? success : function(){};

    self.popup.style.display = 'block';

    success();

    self.timer_seconds = 0;
    self.timer_show = setInterval(function () {
        self.timer_seconds++;
    }, 1000);
};

Preloader.prototype.hide = function (success) {
    var self = this;

    success = typeof(success) !== 'undefined' ? success : function(){};

    self.timer_hide = setInterval(function () {
        if(self.timer_seconds > 1){
            self.popup.style.display = 'none';
            success();
            clearInterval(self.timer_show);
            clearInterval(self.timer_hide);
        }
    }, 10);


};