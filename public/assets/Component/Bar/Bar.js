"use strict";
Autoload.prototype.loadClasses(["Framework/Ajax"], function(){
    /**
     *
     * @constructor
     */
    window["Bar"] = function Bar(){
        /**
         *
         * @param {String} [id]
         */
        this.handle = function(id)
        {
            //if(arguments.length !== 1)
            //    throw ("error1");
            //
            //if(typeof id !== 'string')
            //    throw("error2");

            this.instance = document.getElementById(id);
            this.isOpened = true;

            this.openedStyle = '0px';
            this.closedStyle = '-300px';
            this.detectIsOpened();
            return this;
        };
        /**
         *
         */
        this.open = function()
        {
            this.ajax().getRequestObject().get('/ajax/bar/open');
            this.instance.style.left = this.openedStyle;
        };
        /**
         *
         */
        this.close = function ()
        {
            this.ajax().getRequestObject().get('/ajax/bar/close');
            this.instance.style.left = this.closedStyle;
        };
        /**
         *
         */
        this.close = function ()
        {
            this.ajax().getRequestObject().get('/ajax/bar/close');
            this.instance.style.left = this.closedStyle;
        };
        /**
         *
         */
        this.toggle = function ()
        {
            if(this.isOpened){
                this.isOpened = false;
                this.close();
            }else{
                this.isOpened = true;
                this.open();
            }
        };
        /**
         *
         */
        this.detectIsOpened = function()
        {
            if(this.instance.style.left === this.openedStyle){
                this.isOpened = true;
            }else{
                this.isOpened = false;
            }
        };
        /**
         *
         * @returns {Ajax}
         */
        this.ajax = function()
        {
            return new Ajax();
        };
    }

    Autoload.prototype.registerClass("Component/Bar/Bar");
});