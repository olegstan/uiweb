"use strict";
Autoload.prototype.loadClasses(["Framework/Ajax", "Framework/Money", "Framework/NodeList"], function(){
    /**
     *
     * @constructor
     */
    window["Cart"] = function Cart()
    {


        /**
         *
         * @param {MouseEvent} [e]
         */
        this.open = function (e)
        {
            //this.instance.style.top = e.target.offsetTop + 'px';
            //this.instance.style.left = (e.target.offsetLeft - this.width) + 'px';
        };
        /**
         *
         * @param {MouseEvent} [e]
         */
        this.close = function (e)
        {

        };
        /**
         * @param {Number} [id]
         * @param {MouseEvent} [e]
         */
        this.get = function (id, e)
        {
            this.ajax().get("/ajax/cart/get/" + id);
        };
        /**
         *
         * @param {MouseEvent} [e]
         */
        this.getAll = function (e)
        {
            return this.ajax().get();
        };
        /**
         * @param {Number} [id]
         * @param {MouseEvent} [e]
         */
        this.add = function (id, e, elem)
        {
            this.ajax().get("/ajax/cart/add/" + id);
        };
        /**
         *
         * @param {Number} [id]
         * @param {MouseEvent} [e]
         */
        this.delete = function (id, e, elem)
        {
            var self = this;
            this.ajax().get("/ajax/cart/delete/" + id,
                function(){
                    elem.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode);
                    self.calculate();
                },
                function(){

                }
            );
        };
        /**
         *
         * @param {Number} [id]
         * @param {Number} [count]
         * @param {MouseEvent} [e]
         */
        this.change = function (id, e, elem)
        {
            var self = this;
            var count = isNaN(parseInt(elem.value)) ? 0 : parseInt(elem.value);
            var price = new Money(elem.parentNode.parentNode.querySelector("[data-price]").textContent);
            this.ajax().get("/ajax/cart/change/" + id + "/" + count,
                function(){
                    elem.parentNode.parentNode.querySelector("[data-sum]").textContent = price.multiply(count).getMoney();
                    self.calculate();
                },
                function(){

                }
            );
        };
        /**
         *
         * @param {MouseEvent} [e]
         */
        this.clear = function (e)
        {
            this.ajax().get("/ajax/cart/clear");
        };

        this.calculate = function()
        {
            /**
             *
             * @type {NodeList}
             */
            var elements = document.querySelectorAll("[data-product]");
            var sum = 0;

            elements.forEach(function(v, k){
                /**
                 * @@type {Node}
                 */
                sum += (new Money(v.querySelector("[data-sum]").textContent));
            });

            document.querySelectorAll("[data-sum]").last().textContent = (new Money(sum)).getMoney();
        };

        /**
         *
         * @returns {Ajax}
         */
        this.ajax = function()
        {
            return (new Ajax()).getRequestObject().setType('json');
        };

    };

    Autoload.prototype.registerClass("Component/Cart/Cart");
});
