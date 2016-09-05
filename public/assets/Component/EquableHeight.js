"use strict";
Autoload.prototype.loadClasses([], function(){


    window['EquableHeight'] = function EquableHeight(classSelector)
    {
        /**
         *
         * @param {String} [classSelector]
         */
        this.handle = function(classSelector)
        {
            /**
             *
             * @type {Array}
             */
            this.nodes = Array.prototype.slice.call(document.getElementsByClassName(classSelector), 0);
            /**
             *
             * @type {number}
             */
            this.maxHeight = 0;

            this.setMaxHeight();
            this.equalAll();
        };

        /**
         *
         */
        this.setMaxHeight = function()
        {
            var self = this;
            this.nodes.forEach(function (v, k) {
                if(v.style.innerHeight > self.maxHeight){
                    self.maxHeight = v.style.height;
                }
            });
        };

        /**
         *
         * @returns {string}
         */
        this.getMaxHeight = function()
        {
            return 350 + "px";
            //return this.maxHeight + "px";
        };

        /**
         *
         */
        this.equalAll = function()
        {
            var self = this;
            this.nodes.forEach(function (v, k) {
                v.style.height = self.getMaxHeight();
            });
        };
    }

    Autoload.prototype.registerClass("Component/EquableHeight", new EquableHeight());
});
