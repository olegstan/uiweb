AjaxFrom.prototype.initDynamic = function () {
    var action = document.querySelectorAll("input[type='submit']"),
        self = this;
    Array.prototype.forEach.call(action, function(v, k) {
        v.addEventListener("click", function(e){
            e.preventDefault();

            var actionType = v.getAttribute("data-type");

            self.logic(actionType, v);
        }, true);
    });
};
AjaxFrom.prototype.logic = function(actionType, v){
    var self = this;

    switch (actionType){
        case "insert":
            self.insertNode(v);
            break;
        case "update":
            self.updateNode(v);
            break;
        case "delete":
            self.deleteNode(v);
            break;
        case "view":
            self.viewNode(v);
            break;
    }
};

AjaxFrom.prototype.insertNode = function(element){

    var self = this;
    var form = element.parentNode;

    var data = "";
    var prefix;

    self.countParam = 0;

    Array.prototype.forEach.call(form.elements, function (v, k) {
        //префикс
        prefix = self.countParam == 0 ? "" : "&";

        if (v.type == "text" || v.type == "textarea") {
            data += prefix + v.name + "=" + encodeURIComponent(v.value);
        }
        self.countParam++;
    });

    data += "&action=insert";
    data += "&project_id=" + form.parentNode.getAttribute('data-project-id');

    this.sendAjax(
        data,
        form,
        function(){},
        function(getDataJson){
            var body = document.querySelector(".body");
            var newForm = document.createElement("form");
            var newBody = document.querySelector(".new-body form");

            newForm.setAttribute("method", newBody.method);
            newForm.setAttribute("action", newBody.action);
            newForm.setAttribute("data-form-id", getDataJson.fields.id);



            for(var k in self.options.fields) {
                var input = document.createElement("input");
                if(k === 'id'){
                    input.setAttribute("disabled", "disabled");
                }
                input.setAttribute("type", self.options.fields[k]);
                input.setAttribute("name", k);
                input.value = getDataJson.fields[k];
                newForm.appendChild(input);
            }


            for(var k in self.options.buttons) {
                var submit = document.createElement("input");
                submit.setAttribute("type", "submit");
                submit.setAttribute("data-type", k);
                submit.value = self.options.buttons[k];

                if (k === 'view') {
                    submit.setAttribute("data-view", "/project/view/" + getDataJson.fields["id"]);
                }

                newForm.appendChild(submit);
            }

            var div = document.createElement("div");
            div.className = "clearfix";

            newForm.appendChild(div);

            body.appendChild(newForm);

            var form = document.querySelector("input[data-type='insert']").parentNode;

            Array.prototype.forEach.call(form.elements, function (v, k) {
                if (v.type === "text" || v.type === "textarea") {
                    v.value = '';
                }
            });

            var newActions = newForm.querySelectorAll("input[type='submit']");

            Array.prototype.forEach.call(newActions, function(v, k) {
                v.addEventListener("click", function(e){
                    e.preventDefault();

                    var actionType = v.getAttribute("data-type");

                    self.logic(actionType, v);
                }, true);
            });

        },
        function(){}
    );
};

AjaxFrom.prototype.updateNode = function(element){
    var form = element.parentNode;

    var data = "";
    var prefix;

    self.countParam = 0;

    Array.prototype.forEach.call(form.elements, function (v, k) {
        //префикс
        prefix = self.countParam === 0 ? "" : "&";

        if (v.type === "text" || v.type === "textarea") {
            data += prefix + v.name + "=" + encodeURIComponent(v.value);
        }
        self.countParam++;
    });

    data += "&action=update";

    this.sendAjax(
        data,
        form,
        function(){},
        function(){},
        function(){}
    );
};



AjaxFrom.prototype.deleteNode = function(element){
    var form = element.parentNode;

    var data = "";
    var prefix;

    self.countParam = 0;

    Array.prototype.forEach.call(form.elements, function (v, k) {
        //префикс
        prefix = self.countParam === 0 ? "" : "&";

        if (v.type === "text" || v.type === "textarea") {
            data += prefix + v.name + "=" + encodeURIComponent(v.value);
        }
        self.countParam++;
    });

    data += "&action=delete";

    this.sendAjax
    (
        data,
        form,
        function(){},
        function(getDataJson)
        {
            var element = document.querySelector("form[data-form-id='" + getDataJson.fields.id + "']");
            element.parentNode.removeChild(element);
        },
        function(){}
    );
};

AjaxFrom.prototype.viewNode = function(element){
    window.location.assign(element.getAttribute("data-view"));
};

/*AjaxFrom.prototype.sendAjax = function(data, form, beforeSend, success, error){
 var self = this;
 var request = new XMLHttpRequest();
 request.open(form.method, form.action, true);
 request.onload = function (response) {
 //написать проверку на вернувшиеся значения
 var getDataJson = JSON.parse(response.target.responseText)

 beforeSend();
 if (getDataJson.result === "success") {
 success(getDataJson);
 }
 if (getDataJson.result === "error") {
 error(getDataJson);
 }
 };
 request.onerror = function (get_data) {
 //console.log(get_data);
 };
 request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
 request.send(data);
 };
