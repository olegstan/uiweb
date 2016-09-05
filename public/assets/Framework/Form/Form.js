"use strict";
function Form(id){
    var self = this;

    //this.options = options;
    //this.count_param = 0;
    //
    //self.additional = [];
    //self.additionalFunctions = [];
    //functions
    //this.errors.errorText
    //this.errors.errorTextarea
    //this.errors.errorPassword
    //this.errors.errorCheckbox

    //this.options.buttons = {view: "РџСЂРѕСЃРјРѕС‚СЂ", update: "Р?Р·РјРµРЅРёС‚СЊ", delete: "РЈРґР°Р»РёС‚СЊ"};

    //this.options.place = (settings.startAt === undefined ? 1 : settings.startAt);

    //this.init();
    //
    //if(typeof(options.additional) !== 'undefined'){
    //    self.additional = options.additional;
    //}
    //
    //if(typeof(options.additionalFunctions) !== 'undefined'){
    //    self.additionalFunctions = options.additionalFunctions;
    //}

}

AjaxFrom.prototype.init = function () {
    var self = this;
    var form = document.getElementById(this.options.id);
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        var input_error;
        var input_success;

        var submit_button = this.querySelector('input[type="submit"]');
        if (!submit_button.classList.contains('disabled')) {

            var data = self.prepareData(form.elements);
            core.ajax({
                data: data,
                url: form.action,
                onloadstart: function(){

                },
                success: function(getDataJson){
                    self.options.result.success(getDataJson.success, submit_button);
                    Array.prototype.forEach.call(self.options.fields, function (name, k) {
                        var element = form.querySelector('[name="' + name + '"]');

                        if (element.type === 'text') {
                            self.options.success.successText(element, form, getDataJson);
                            return;
                        }
                        if (element.type === 'password') {
                            self.options.success.successPassword(element, form, getDataJson);
                            return;
                        }
                        if (element.type === 'textarea') {
                            self.options.success.successTextarea(element, form, getDataJson);
                            return;
                        }
                        if (element.type === 'checkbox') {
                            self.options.success.successCheckbox(element, form, getDataJson);
                            return;
                        }
                    });

                    self.options.result.empty(submit_button);
                    Array.prototype.forEach.call(self.options.fields, function (name, k) {
                        //var elements = form.querySelectorAll('[name="' + name + '"]');

                        var element = form.querySelector('[name="' + name + '"]');
                        if (element.type === 'text') {
                            self.options.empty.emptyText(element, form);
                            return;
                        }
                        if (element.type === 'password') {
                            self.options.empty.emptyPassword(element, form);
                            return;
                        }
                        if (element.type === 'textarea') {
                            self.options.empty.emptyTextarea(element, form);
                            return;
                        }
                        if (element.type === 'checkbox') {
                            var checkboxes = form.querySelectorAll('[name="' + name + '"]');
                            Array.prototype.forEach.call(checkboxes, function(checkbox, i){
                                self.options.empty.emptyCheckbox(checkbox, form);
                            });
                            return;
                        }
                    });
                },
                error: function(getDataJson){
                    self.options.result.error(getDataJson.error, submit_button);
                    for (var name in getDataJson.errors) {
                        input_error = form.querySelector('input[name="' + name + '"], textarea[name="' + name + '"]');
                        if(input_error !== null) {
                            if (input_error.type === 'text') {
                                self.options.errors_notice.errorText(input_error, form, getDataJson);
                                continue;
                            }
                            if (input_error.type === 'password') {
                                self.options.errors_notice.errorPassword(input_error, form, getDataJson);
                                continue;
                            }
                            if (input_error.type === 'textarea') {
                                self.options.errors_notice.errorTextarea(input_error, form, getDataJson);
                                continue;
                            }
                            if (input_error.type === 'checkbox') {
                                self.options.errors_notice.errorCheckbox(input_error, form, getDataJson);
                                continue;
                            }
                        }
                    }
                    for (var name in getDataJson.success) {
                        input_success = form.querySelector('input[name="' + name + '"], textarea[name="' + name + '"]');
                        if(input_success !== null) {
                            if (input_success.type === 'text') {
                                self.options.success_notice.successText(input_success, form, getDataJson);
                                continue;
                            }
                            if (input_success.type === 'password') {
                                self.options.success_notice.successPassword(input_success, form, getDataJson);
                                continue;
                            }
                            if (input_success.type === 'textarea') {
                                self.options.success_notice.successTextarea(input_success, form, getDataJson);
                                continue;
                            }
                            if (input_success.type === 'checkbox') {
                                self.options.success_notice.successCheckbox(input_success, form, getDataJson);
                                continue;
                            }
                        }
                    }
                }
            }, "JSON", "POST");
        }
    });

};



AjaxFrom.prototype.prepareData = function(elements){
    var self = this;
    var form = document.getElementById(this.options.id);

    self.count_param = 0;

    var form_data = new FormData();

    Array.prototype.forEach.call(self.options.fields, function (name, k) {
        var element = form.querySelector('[name="' + name + '"]');

        if (element.type === 'text' || element.type === 'password' || element.type === 'textarea') {
            form_data.append(element.name, element.value);
            self.count_param++;
            return;
        }

        if (element.type === 'checkbox') {
            var checkboxes = form.querySelectorAll('[name="' + name + '"]');
            Array.prototype.forEach.call(checkboxes, function(checkbox, i){
                if (checkbox.checked) {
                    form_data.append(element.name, element.value);
                }
                self.count_param++;
            });
            return;
        }
    });



    for(var k in self.additionalFunctions) {
        form_data.append(k, self.additionalFunctions[k]());

        self.count_param++;
    }

    return form_data;
};