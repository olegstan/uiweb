"use strict";
function Core(){
    var core = this;

    core.html = document.querySelector("html");
    core.body = document.querySelector("body");
    core.wrapper = document.getElementById("wrapper");

    core.carousel = [];
    core.dropdown = [];
    core.smart_form = [];
    core.custom_select = [];
    core.ajax_form = [];
    core.mask = [];

    core.popup = new Popup();
    core.modal = new Modal();
    core.preloader = new Preloader();

    core.route = new Route();
    core.cookie = new Cookie();

    core.system = new System();
}

function Popup()
{

}

Core.prototype.ajax = function(obj, type, method)
{
    obj.onprogress = (typeof obj.onprogress !== "undefined") ? obj.onprogress : function(e, request){};
    obj.onloadstart = (typeof obj.onloadstart !== "undefined") ? obj.onloadstart : function(e, request){};
    obj.onloadend = (typeof obj.onloadend !== "undefined") ? obj.onloadend : function(e, request){};
    obj.onerror = (typeof obj.onerror !== "undefined") ? obj.onerror : function(e, request){};
    obj.onabort = (typeof obj.onabort !== "undefined") ? obj.onabort : function(e, request){};
    obj.onreadystatechange = (typeof obj.onreadystatechange !== "undefined") ? obj.onreadystatechange : function(e, request){};

    //const unsigned short UNSENT = 0; // РЅР°С‡Р°Р»СЊРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ
    //const unsigned short OPENED = 1; // РІС‹Р·РІР°РЅ open
    //const unsigned short HEADERS_RECEIVED = 2; // РїРѕР»СѓС‡РµРЅС‹ Р·Р°РіРѕР»РѕРІРєРё
    //const unsigned short LOADING = 3; // Р·Р°РіСЂСѓР¶Р°РµС‚СЃСЏ С‚РµР»Рѕ (РїРѕР»СѓС‡РµРЅ РѕС‡РµСЂРµРґРЅРѕР№ РїР°РєРµС‚ РґР°РЅРЅС‹С…)
    //const unsigned short DONE = 4; // Р·Р°РїСЂРѕСЃ Р·Р°РІРµСЂС€С‘РЅ

    var self = this;

    var request = new XMLHttpRequest();
    request.open(method, obj.url, true);
    //XMLHttpRequestProgressEvent
    request.onprogress = function(e)
    {
        obj.onprogress(e, request);
    };
    request.onloadstart = function(e)
    {
        obj.onloadstart(e, request);
    };
    request.onload = function (e) {
        if (request.status == 200) {
            switch (type){
                case "JSON":
                    //РЅР°РїРёСЃР°С‚СЊ РїСЂРѕРІРµСЂРєСѓ РЅР° РІРµСЂРЅСѓРІС€РёРµСЃСЏ Р·РЅР°С‡РµРЅРёСЏ
                    var getDataJson = JSON.parse(request.responseText)

                    //obj.beforeSend();
                    if (getDataJson.result === "success") {
                        console.log(obj);
                        obj.success(getDataJson, e);
                    }
                    if (getDataJson.result === "error") {
                        obj.error(getDataJson, e);
                    }
                    break;
                case "HTML":
                    obj.success(request.responseText, e);
                    break;
            }
        }
    };
    request.onloadend = function (e)
    {
        obj.onloadend(e, request);
    };
    request.onerror = function (e)
    {
        obj.onerror(e, request);
    };
    request.onabort = function (e)
    {
        obj.onabort(e, request);
    };

    request.onreadystatechange = function(e)
    {
        obj.onreadystatechange(e, request);
    };

    switch (type){
        case "JSON":
            //request.responseType = "json";
            break;
        case "HTML":

            break;
    }

    switch (method){
        case "GET":

            break;
        case "POST":
            //request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            //request.setRequestHeader("Content-type", "multipart/form-data");
            break;
    }
    //http.setRequestHeader("Content-length", params.length);
    //http.setRequestHeader("Connection", "close");
    request.setRequestHeader('Detect-Ajax', true);
    request.send(obj.data);
};

Core.prototype.prepareData = function(type)
{
    switch (type){
        case "JSON":

            break;
        case "HTML":
            break;
    }
};

Popup.prototype.open = function(e, url, title, w, h, reload)
{
    reload = reload === true ? true : false;

    var left = (screen.width/2)-(w/2);
    var top = (screen.height/2)-(h/2);

    var popup = window.open(url, title,'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h);
    popup.moveTo(left, top);

    if(reload) {
        //РїСЂРѕРІРµСЂРєР° Р·Р°РєСЂС‹С‚ Р»Рё РїРѕРїР°Рї
        var check_connect = setInterval(function () {
            if (!popup || !popup.closed) {
                return;
            }
            clearInterval(check_connect);
            window.location.reload();
        }, 100);
    }
};