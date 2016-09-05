"use strict";
function System(){
    var self = this;

    self.browser = {};
    self.detectBrowser();

    self.os = {};
    self.detectOs();
};

System.prototype.detectBrowser = function () {
    var self = this;
    var n_ver = navigator.appVersion;
    var n_agt = navigator.userAgent;
    var browser_name  = navigator.appName;
    self.browser.full_version  = ''+parseFloat(navigator.appVersion);
    self.browser.major_version = parseInt(navigator.appVersion,10);
    var name_offset,ver_offset,ix;

    if ((ver_offset=n_agt.indexOf("Opera"))!=-1) {
        self.browser.name = "Opera";
        self.browser.full_version = n_agt.substring(ver_offset+6);
        if ((ver_offset=n_agt.indexOf("Version"))!=-1)
            self.browser.full_version = n_agt.substring(ver_offset+8);
    }else if ((ver_offset=n_agt.indexOf("MSIE"))!=-1) {
        self.browser.name = "Microsoft Internet Explorer";
        self.browser.full_version = n_agt.substring(ver_offset+5);
    }else if ((ver_offset=n_agt.indexOf("Chrome"))!=-1) {
        self.browser.name = "Chrome";
        self.browser.full_version = n_agt.substring(ver_offset+7);
    }else if ((ver_offset=n_agt.indexOf("Safari"))!=-1) {
        self.browser.name = "Safari";
        self.browser.full_version = n_agt.substring(ver_offset+7);
        if ((ver_offset=n_agt.indexOf("Version"))!=-1)
            self.browser.full_version = n_agt.substring(ver_offset+8);
    }else if ((ver_offset=n_agt.indexOf("Firefox"))!=-1) {
        self.browser.name = "Firefox";
        self.browser.full_version = n_agt.substring(ver_offset+8);
    }else if ( (name_offset=n_agt.lastIndexOf(' ')+1) <
        (ver_offset=n_agt.lastIndexOf('/')) )
    {
        browser_name = n_agt.substring(name_offset,ver_offset);
        self.browser.full_version = n_agt.substring(ver_offset+1);
        if (browser_name.toLowerCase()==browser_name.toUpperCase()) {
            self.browser.name = navigator.appName;
        }
    }

    if ((ix=self.browser.full_version.indexOf(";"))!=-1) {
        self.browser.full_version = self.browser.full_version.substring(0, ix);
    }
    if ((ix=self.browser.full_version.indexOf(" "))!=-1) {
        self.browser.full_version = self.browser.full_version.substring(0, ix);
    }

    self.browser.major_version = parseInt(''+self.browser.full_version,10);
    if (isNaN(self.browser.major_version)) {
        self.browser.full_version  = ''+parseFloat(navigator.appVersion);
        self.browser.major_version = parseInt(navigator.appVersion,10);
    }
    
};

System.prototype.detectOs = function()
{
    var self = this;

    if (navigator.appVersion.indexOf("Win")!=-1){
        self.os.name="Windows"
    }else if (navigator.appVersion.indexOf("Mac")!=-1) {
        self.os.name = "MacOS";
    }else if (navigator.appVersion.indexOf("X11")!=-1) {
        self.os.name = "UNIX";
    }else if (navigator.appVersion.indexOf("Linux")!=-1) {
        self.os.name = "Linux";
    }
};