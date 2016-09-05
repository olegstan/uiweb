"use strict";
function Mask(options) {

    var self = this;

    self.mask = options.mask;
    self.elem = options.elem;
    self.type = options.type;
    self.pattern = '';
    self.resultPattern = '';
    self.symbols = [];
    self.input = '';

    self.key_map = new KeyMap();

    self.init();
};

Mask.prototype.init = function(){
    var self = this;

    self.getPattern();
    self.elem.value = self.pattern;
    self.setEventListeners();
};

Mask.prototype.reinit = function(){
    var self = this;

    self.getPattern();
    self.symbols = [];
    self.elem.value = self.pattern;
};

Mask.prototype.setEventListeners = function(){
    var self = this;

    self.elem.addEventListener('keydown', function(e){
        self.currentKey = e.keyCode;

        var letter = self.key_map.detectLetter(self.currentKey);
        var key = self.key_map.detectDelete(self.currentKey);
        var digit = self.key_map.detectDigit(self.currentKey);

        //проверяем нажатую кнопку
        //delete или backspace
        if(key === false){
            //проверяем если это буква
            //буквы мы не печатаем
            if(letter){
                e.preventDefault();
            }
        }else if(digit === false){
            self.symbols = self.elem.value.match(/\d/g);
            self.symbols.pop();
        }
    });

    self.elem.addEventListener('keypress', function(e){
        e.preventDefault();
    });

    self.elem.addEventListener('keyup', function(e){
        var digit = self.key_map.detectDigit(self.currentKey);

        if(digit !== false){
            self.getPattern();

            self.symbols.push(digit);
            self.count = self.symbols.length - 1;

            for(var i = 0; i <= self.count; i++){
                self.pattern = self.pattern.replace(/_/, self.symbols[i]);
                self.elem.value = self.pattern;
            }

            var position = self.pattern.indexOf('_')


            if(position !== -1){
                self.elem.setSelectionRange(position, position);
            }
        }
    });
};

Mask.prototype.getMask = function () {
    var self = this;

    if(self.type === 'static'){
        self.pattern = self.mask;
    }else if(self.type === 'dynamic'){
        self.pattern = self.mask();
    }

    return self;
};

Mask.prototype.getPattern = function () {
    var self = this;
    self.maskPattern = self.getMask().pattern;
    self.maskPattern = self.maskPattern.replace(/\\/g, '');
    self.maskPattern = self.maskPattern.replace(/d/g, '_');
    self.pattern = self.maskPattern;

    return self;
};