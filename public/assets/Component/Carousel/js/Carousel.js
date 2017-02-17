"use strict";
function Carousel (settings)
{

    this.carousel = settings.carousel;


    //проверка на существование карусельки
    if(this.carousel !== null) {

        //ширина карусели от количества вложенных элементов
        this.inner = document.querySelector('#' + this.carousel.id + ' .slider-inner');
        this.navigation = document.querySelector('#' + this.carousel.id + ' .slider-navigation');

        this.items = [];
        this.navItems = [];


        this.options = {};

        //начальный слайдер
        this.options.place = (settings.startAt === undefined ? 1 : settings.startAt);
        this.currentItem = this.options.place - 1;


        //запускать при инициализации
        this.options.autoPlay = (settings.autoPlay === undefined ? false : settings.autoPlay);

        //интервал в секундах
        this.options.duration = (settings.duration === undefined ? 5 : settings.duration);

        //таймер, можно юзать для остановки
        this.options.timer = null;

        this.options.aniDuration = 500;

        //анимация
        this.step = null;
        this.fps = 20;
        this.interval = 1000 / this.fps;
        this.totalStep = this.options.aniDuration / this.interval;
        //this.totalStep = 50;


        //инифиализируем запуск
        this.generateItems();
        this.prepareItems();
        this.navigate();


        if (!this.options.autoPlay) {
            //this.play();
        }
    }
}

//запись элеметнтов в массив
Carousel.prototype.generateItems = function ()
{
    var self = this;

    this.items = [];
    var nodes = this.inner.children;

    this.navigation.style.width = (30 * nodes.length) + "px";

    for (var i = 0; i < nodes.length; i++) {
        //проверка по тегу
        if (nodes[i].tagName === "DIV") {
            nodes[i].setAttribute('data-carousel-item', i);
            this.items.push(nodes[i]);

            nodes[i].addEventListener("mousedown", function(e){
                //e.target.style.cursor = "-webkit-grabbing";
            });

            nodes[i].addEventListener("mouseup", function(e){
                //e.target.style.cursor = "pointer";
            });

            nodes[i].addEventListener("dragstart", function(e){
                //e.preventDefault();
            });

            nodes[i].addEventListener("touchmove", function(e){
                self.pause();
            });

            nodes[i].addEventListener("mouseover", function(e){
                self.pause();
            });

            nodes[i].addEventListener("mouseout", function(e){
                self.play();
            });




        }

        //создаём навигацию
        var node = document.createElement("LI");
        node.setAttribute('data-carousel-nav-item', i);
        this.navigation.appendChild(node);
        this.navItems.push(node);
    }
};

Carousel.prototype.navigate = function()
{
    var self = this;

    Array.prototype.forEach.call(self.navItems, function(v, k){

        v.addEventListener('click', function(){

            var item = parseInt(this.getAttribute('data-carousel-nav-item'));

            //проверяем какой сейчас показывается элемент
            //и какой будет вызван
            if(self.currentItem !== item) {
                self.changeItems(self.currentItem, item);

                self.currentItem = item;
                self.options.place = ++item;

                self.pause();
                self.play();
            }
        });
    });
};

//простоановка позиций
Carousel.prototype.prepareItems = function ()
{
    //активная кнопочка навигации
    this.navItems[0].classList.add('active');

    for (var i = 0; i < this.items.length; i++) {
        this.items[i].style.left = (100 * i) + '%';
        this.items[i].style.width = '100%';
    }
};

//запуск с задержкой
Carousel.prototype.play = function ()
{
    var self = this;
    this.options.timer = setTimeout(function () {
        self.periodicallyUpdate();
    }, self.options.duration * 1000);
};

Carousel.prototype.pause = function ()
{
    clearTimeout(this.options.timer);
};

Carousel.prototype.periodicallyUpdate = function ()
{
    this.next();
};

Carousel.prototype.getNextItem = function()
{
    if(this.options.place == this.items.length){
        return 0;
    }else{
        return this.currentItem;
    }
};

Carousel.prototype.animate = function(item, from, to)
{

    var self = this;

    var step = 0;
    var change = to - from;

    function animate () {

        //шаги до завершения
        //меняем шаг, пока шаги не закончены
        if (step < self.totalStep) {
            step++;
        }

        var percentComplete = (step/self.totalStep),
            newValue = change * self.ease(percentComplete);

        self.items[item].style.left = (from + newValue) + '%';

        //повторяем итерации пока не выполним шаги
        if (step < self.totalStep) {
            requestAnimationFrame(animate);
        } else {
            self.items[item].style.left = to + '%';
        }
    }

    requestAnimationFrame(animate);

};

Carousel.prototype.next = function ()
{

    if(this.options.place == this.items.length){
        this.changeItems(this.currentItem, 0);

        this.currentItem = 0;
        this.options.place = 1;
        this.play();
    }else{
        //устанавливаем позиции при листании
        this.changeItems(this.currentItem, this.currentItem+1);

        this.currentItem++;
        this.options.place++;
        this.play();
    }
};

Carousel.prototype.changeItems = function(fromItem, toItem)
{
    var self = this;

    this.animate(fromItem, parseFloat(self.items[fromItem].style.left), -100);
    this.navItems[fromItem].classList.remove('active');

    this.items[toItem].style.left = '100%';
    this.animate(toItem, parseFloat(self.items[toItem].style.left), 0);
    this.navItems[toItem].classList.add('active');
};

Carousel.prototype.ease = function(pos)
{
    if ((pos/=0.5) < 1){
        return 0.5 * Math.pow(pos,2);
    }else {
        return -0.5 * ((pos -= 2) * pos - 2);
    }
};