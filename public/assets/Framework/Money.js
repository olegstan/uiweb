"use strict";
Autoload.prototype.loadClasses(["Framework/String"], function() {


    //Number.prototype.forEach = Array.prototype.forEach;
    window["Money"] = function Money(string)
    {
        /**
         *
         * @type {String}
         */
        this.value = new String(string);
        /**
         *
         * @type {string}
         */
        this.sign = '$';
        /**
         *
         */
        this.setSign(this.value);
        /**
         *
         */
        this.setNumber(this.value);
    };

    /**
     *
     * @returns {string|*}
     */
    Money.prototype.getNumber = function()
    {
        return this.number;
    };

    /**
     *
     * @param value
     */
    Money.prototype.setNumber = function(value)
    {
        this.number = parseFloat(value.replaceAll(',', '')).toFixed(2);
        return this;
    };

    Money.prototype.setSign = function(value)
    {
        var self = this;
        var symbols = [
            '$', '₽', '€', 'руб.'
        ];

        symbols.forEach(function(v, k){
            if(value.search(v) > -1){
                self.sign = v;
            }
        });
    };

    Money.prototype.getSign = function()
    {
        return this.sign;
    };

    /**
     *
     * @param count
     * @returns {Money}
     */
    Money.prototype.multiply = function(count)
    {
        this.number = this.number * count;
        return this;
    };

    /**
     *
     * @returns {string|*}
     */
    Money.prototype.toString = function() {
        return parseFloat(this.number);
    };

    /**
     *
     * @param decimals
     * @param dec_point
     * @param thousands_sep
     * @returns {*}
     * TODO rewrite
     */
    Money.prototype.getMoney = function(decimals, dec_point, thousands_sep)
    {
        var number = this.getNumber();
        var i, j, kw, kd, km;

        // input sanitation & defaults
        if( isNaN(decimals = Math.abs(decimals)) ){
            decimals = 2;
        }
        if( dec_point == undefined ){
            dec_point = ".";
        }
        if( thousands_sep == undefined ){
            thousands_sep = ",";
        }

        i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
        if( (j = i.length) > 3 ){
            j = j % 3;
        } else{
            j = 0;
        }
        km = (j ? i.substr(0, j) + thousands_sep : "");
        kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
        //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
        kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

        return km + kw + kd + ' ' + this.getSign();
    };

    Autoload.prototype.registerClass("Framework/Money");
});

