function Autoload()
{

}
/**
 *
 * @type {String}
 */
Autoload.prototype.root = 'assets';
/**
 *
 * @type {String[]}
 */
Autoload.prototype.loaded = [];
/**
 *
 * @type {Function[]}
 */
Autoload.prototype.registered = [];
/**
 *
 * @param {String} [namespace]
 * @returns {String}
 */
Autoload.prototype.getPath = function(namespace)
{
    if(typeof namespace === "undefined"){
        throw Error("Required namespace " + namespace);
    }

    return "/" + this.root + "/" + namespace + '.js';
};
/**
 *
 * @param {String} [namespace]
 * @param {Function} [callback]
 */
Autoload.prototype.registerClass = function(namespace, callback)
{
    if(typeof namespace === "undefined"){
        throw Error("No class name " + namespace);
    }

    Autoload.prototype.registered[namespace] = new Class(namespace, Autoload.prototype.getPath(namespace), callback);
    Autoload.prototype.onautoload[namespace]();
};

/**
 *
 * @param {String[]} [namespaces]
 * @param {Function} [func]
 */
Autoload.prototype.loadClasses = function(namespaces, func)
{
    namespaces.forEach(function(namespace, k){
        if(typeof namespace === "undefined"){
            throw Error("No namespace [" + namespace + "]");
        }
    });

    namespaces.forEach(function(namespace, k){
        if(Autoload.prototype.isLoaded(namespace)){
            namespaces.splice(namespace, 1);
        }
    });

    if(namespaces.length > 0){
        namespaces.forEach(function(namespace, k){
            Autoload.prototype.loaded[namespace] = namespace;
        });
        Autoload.prototype.loadFiles(namespaces, func);
    }else{
        func();
    }
};
/**
 *
 * @param {String[]} [namespaces]
 * @param {Function} [func]
 */
Autoload.prototype.loadFiles = function(namespaces, func)
{
    var count = 0;

    namespaces.forEach(function(namespace, k){
        var fileref = document.createElement('script');
        fileref.setAttribute("type","text/javascript");
        fileref.setAttribute("src", Autoload.prototype.getPath(namespace));

        fileref.onload = function(e)
        {
            console.log(namespace + " loaded");
        };

        fileref.onerror = function(e)
        {
            throw Error("[" + Autoload.prototype.getPath() + "] not loaded");
        };

        Autoload.prototype.onautoload[namespace] = function(e)
        {
            count++;
            if(count === namespaces.length){
                func();
            }
        };


        document.getElementsByTagName("head")[0].appendChild(fileref);
    });
};

Autoload.prototype.onautoload = function(e)
{

};
/**
 *
 * @param {String} [namespace]
 * @returns {boolean}
 */
Autoload.prototype.isLoaded = function(namespace)
{
    if(typeof Autoload.prototype.loaded[namespace] !== "undefined"){
        return true;
    }else{
        return false;
    }
};
/**
 *
 * @param {String} [namespace]
 * @returns {boolean}
 */
Autoload.prototype.isRegistered = function(namespace)
{
    if(typeof Autoload.prototype.registered[namespace] !== "undefined"){
        return true;
    }else{
        return false;
    }
};
/**
 *
 * @param {String} [namespace]
 * @param {String} [path]
 * @param {Object} [callback]
 * @constructor
 */
function Class(namespace, path, callback)
{
    this.namespace = namespace;
    this.path = path;
    this.callback = callback;
}
/**
 *
 * @returns {String}
 */
Class.prototype.getNamecspace = function () {
    return this.namespace;
};
/**
 *
 * @returns {String}
 */
Class.prototype.getPath = function () {
    return this.path;
};
/**
 *
 * @returns {Function}
 */
Class.prototype.getCallback = function()
{
    return this.callback;
};