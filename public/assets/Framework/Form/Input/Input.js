"use strict";
Autoload.prototype.loadClasses([], function(){
    /**
     *
     * @constructor
     */
    window["Ajax"] = function Ajax()
    {

        /**
         *
         * @type {XMLHttpRequest|null}
         */
        this.request = null;
        /**
         *
         * @type {string}
         */
        this.type = 'html';//json
        /**
         *
         * @type {Array}
         */
        this.headers = [];
        /**
         *
         * @type {String|ArrayBuffer|Blob|Document|FormData|null}
         */
        this.data = null;
        /**
         *
         * @type {string}
         */
        this.method = 'get';


        //const unsigned short UNSENT = 0; //
        //const unsigned short OPENED = 1; //
        //const unsigned short HEADERS_RECEIVED = 2; //
        //const unsigned short LOADING = 3; //
        //const unsigned short DONE = 4; //

        /**
         *
         */
        this.default = function()
        {
            this.request = null;
            this.type = 'html';
            this.headers = [];
            this.data = null;
        };
        /**
         * @param {Object} [obj]
         * @returns {Ajax}
         */
        this.getRequestObject = function(obj)
        {
            var self = this;
            this.default();

            this.request = new XMLHttpRequest();
            this.request.onprogress = function(e)
            {
                self.onprogress(e);
            };
            this.request.onloadstart = function(e)
            {
                self.onloadstart(e);
            };
            this.request.onload = function (e) {
                self.onload(e);
            };
            this.request.onloadend = function (e)
            {
                self.onloadend(e);
            };
            this.request.onerror = function (e)
            {
                self.onerror(e);
            };
            this.request.onabort = function (e)
            {
                self.onabort(e);
            };
            this.request.onreadystatechange = function(e)
            {
                self.onreadystatechange(e);
            };

            this.request.ontimeout = function(e)
            {
                self.ontimeout(e);
            };

            return this;
        };
        /**
         *
         * @param key
         * @param func
         * @returns {Ajax}
         */
        this.ajaxCallbackFunction = function(key, func)
        {
            switch(key)
            {
                case 'success':
                case 'error':
                    self[key] = func;
                    break;
            }
            return this;
        };

        this.requestCallbackFunction = function(key, func)
        {
            switch(key)
            {
                case 'onprogress':
                case 'onloadstart':
                case 'onload':
                case 'onloadend':
                case 'onerror':
                case 'onabort':
                case 'ontimeout':
                case 'onreadystatechange':
                    self[key] = func;
                    break;
            }
            return this;
        };
        /**
         *
         * @param {String} [method]
         * @returns {Ajax}
         */
        this.setMethod = function(method)
        {
            this.method = method;
            return this;
        };
        /**
         *
         * @param {String} [type]
         * @returns {Ajax}
         */
        this.setType = function (type)
        {
            this.type = type;//html json
            return this;
        };
        /**
         *
         * @param {Array} [headers]
         * @returns {Ajax}
         */
        this.setHeaders = function(headers)
        {
            var self = this;
            headers.forEach(function(item, k){
                self.headers.push(item);
            });
            return this;
        };
        /**
         *
         * @param {String|ArrayBuffer|Blob|Document|FormData} [data]
         * @returns {Ajax}
         */
        this.setData = function (data) {
            this.data = data;
            return this;
        };
        /**
         @param {String} [url]
         @param {Function} [success]
         @param {Function} [error]
         */
        this.get = function(url, success, error)
        {
            this.requestUrl(url, success, error);
        };
        /**
         @param {String} [url]
         @param {Function} [success]
         @param {Function} [error]
         */
        this.post = function(url, success, error)
        {
            this.setHeaders([
                {"header": "Content-type", "value": "application/x-www-form-urlencoded"},
                {"header": "Content-type", "value": "multipart/form-data"}
            ])
                .setMethod('post')
                .requestUrl(url, success, error);
        };

        this.requestUrl = function(url, success, error)
        {
            var self = this;
            self.request.open(self.method, url, true);

            self.success = success;

            self.error = error;

            self.headers.forEach(function(item, k){
                self.request.setRequestHeader(item["header"], item["value"]);
            });

            self.request.send(self.data);
        };
        /**
         *
         * @param data
         * @param {Event} [e]
         */
        this.beforeSend = function(data, e)
        {

        };
        /**
         *
         * @param data
         * @param {Event} [e]
         */
        this.success = function(data, e)
        {

        };
        /**
         *
         * @param data
         * @param {Event} [e]
         */
        this.error = function(data, e)
        {

        };
        /**
         *
         * @param {ProgressEvent} [e]
         */
        this.onprogress = function(e)
        {

        };
        /**
         *
         * @param {ProgressEvent} [e]
         */
        this.onloadstart = function(e)
        {

        };
        /**
         *
         * @param {ProgressEvent} [e]
         */
        this.onload = function(e)
        {
            var self = this;

            if (self.request.status == 200) {
                switch (self.type){
                    case "json":
                        var json = JSON.parse(self.request.responseText);

                        //self.beforeSend();
                        if (json.result === "success") {
                            self.success(json, e);
                        }
                        if (json.result === "error") {
                            self.error(json, e);
                        }
                        break;
                    case "html":
                        self.success(self.request.responseText, e);
                        break;
                }
            }else{
                throw new Error('Response stattus ' + self.request.status);
            }
        };
    };

    Autoload.prototype.registerClass("Framework/Form/Input/Input");
});

