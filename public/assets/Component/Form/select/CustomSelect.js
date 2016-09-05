//custom select
function CustomSelect(options)
{
    this.is_opened = 0;
    this.search = options.search;
    this.search_text = "";
    this.elem = options.elem;
    this.container = options.container;
    this.items = options.items;
    this.success_select = options.success_select;

    this.init();
}

CustomSelect.prototype.init=function()
{
    var self=this;

    self.search.addEventListener("click", function(e){
        e.stopPropagation();
    });

    self.search.addEventListener("keyup", function(e){
        if(self.search.value === ""){
            self.search_text = ".*";
        }else{
            self.search_text = self.search.value;
            self.shield();
        }

        Array.prototype.forEach.call(self.items, function(el, i){
            el.style.display = "block";
        });

        self.searchText();
    });

    self.elem.addEventListener("click", function(e){
        if(!self.is_opened) {
            self.container.style.display="block";
            self.is_opened = !self.is_opened;
            self.search.focus(e);
        }
        e.stopPropagation();
    });

    Array.prototype.forEach.call(self.items, function(el, i){
        el.addEventListener("click", function(e){
            self.container.style.display="none";

            self.elem.setAttribute("data-country-id", el.getAttribute("data-country-id"));
            self.elem.setAttribute("data-country-pattern", el.getAttribute("data-country-pattern"));
            self.elem.querySelector(".country-flag").style.backgroundPosition="0 " + el.getAttribute("data-country-flag") + "px";
            self.elem.querySelector(".country-code").textContent=el.getAttribute("data-country-code");
            self.success_select();

            self.is_opened=0;
        });
    });

    document.addEventListener("click", function (e) {
        if(self.is_opened){
            self.container.style.display="none";
            self.is_opened = !self.is_opened;
        }
    });

};

/**
 * написать
 */

CustomSelect.prototype.searchText = function(){
    var self = this;
    var regex = new RegExp(self.search_text, "i");

    var count = self.items.length;
    Array.prototype.forEach.call(self.items, function(el, i){
        var country_name = el.querySelector(".country-name").textContent;

        if(!regex.test(country_name)){
            el.style.display = "none";
        }else{
            el.style.display = "block";
        }
    });
};

CustomSelect.prototype.shield = function () {
    var self = this;
    var spec_chars = [
        "^", ".", "*", "+", "?", "=", "!", ":", "|", "\"", "\\", "/", "(", ")", "[", "]", "{", "}"
    ];
    var regex;
    var pattern;
    var pre = "\\";

    Array.prototype.forEach.call(spec_chars, function(v, k){
        pattern = pre + v;
        regex = new RegExp(pattern, "g");
        self.search_text = self.search_text.replace(regex, pattern);
    });

    /*var dollar_char = "$";
    var dollar_pattern = "$" + dollar_char;
    regex = new RegExp(dollar_pattern, "g");
    self.search_text = self.search_text.replace(regex, dollar_pattern);
*/
};