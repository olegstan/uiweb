'use strict';
function Scroll()
{
    this.options = {};
    this.options.margin = 60;
    this.init();
}

Scroll.prototype.init = function ()
{
    var self = this;

    //кроссстраницчное перенаправление
    //проверяем передан ли id на страницу
    if(location.hash !== ''){
        //отрезаем #
        var elementId = location.hash.slice(1);
        var element = document.getElementById(elementId);

        if(element !== null){
            var to = document.getElementById(elementId).offsetTop;

            setTimeout(function() {
                window.scrollTo( 0, (to - self.options.margin) );
            }, 1);
        }
    }


    //вешаем событие при клике на все элементы с атрибутами
    //атрибуты ведут на элементы с нужным id
    var elements = document.querySelectorAll('[data-href]');
    Array.prototype.forEach.call(elements, function(v, k){
        v.addEventListener('click', function (e) {

            var element = document.getElementById(v.getAttribute('data-href'));

            //если элемента на странице нет, то переходим по href
            if(element !== null){
                //кроссбраузерная отмена действия
                e.preventDefault();

                var to = document.getElementById(v.getAttribute('data-href')).offsetTop;
                self.animate(to);
            }else{
                //console.log(v.href);
                //window.location.assign(v.href);
                //window.location.replace(document.getElementById(v.getAttribute('-href')));
            }
        });
    });
};

Scroll.prototype.animate = function (to)
{

    var self = this;
    var from = window.scrollY;
    var step = 0;
    function animateDown(){
        var from = window.scrollY;

        if(from < to - self.options.margin){
            step += 10;
            var newFrom = from + step;
            window.scrollTo(0, newFrom);

            //проверка двигается ли экран
            if(from !== window.scrollY) {
                requestAnimationFrame(animateDown);
            }
        }else{
            window.scrollTo( 0, (to - self.options.margin) );
        }
    }

    function animateUp(){
        var from = window.scrollY;

        if(from > to - self.options.margin){
            step += 10;
            var newFrom = from - step;
            window.scrollTo(0, newFrom);
            if(from !== window.scrollY) {
                requestAnimationFrame(animateUp);
            }
        }else{
            window.scrollTo( 0, ( to - self.options.margin ) );
        }
    }

    //проверяем где сейчас находится экран относительно
    //объекта к которому будем скролить
    if(from < to-this.options.margin){
        requestAnimationFrame(animateDown);
    }else{
        requestAnimationFrame(animateUp);
    }
};
