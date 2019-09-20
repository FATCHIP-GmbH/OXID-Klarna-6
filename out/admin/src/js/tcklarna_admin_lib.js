/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*****  UTILITIES  *****/
window.getElementA = function (baseElement, clone) {
    if (clone)
        return baseElement.querySelector('a').cloneNode(true);
    else
        return baseElement.querySelector('a');
};

if (!Array.prototype.last){
    Object.defineProperty(Array.prototype, 'last', {
        get: function() {
            return this[this.length - 1];
        }
    });
}

if(!Array.prototype.remove){
    Array.prototype.remove = function(item){
        var index = this.indexOf(item);
        if(index !== -1)
            this.splice(index, 1);

        return this;
    };
}

if (!Array.prototype.find) {
    Array.prototype.find = function(predicate) {
        if (this == null) {
            throw new TypeError('Array.prototype.find called on null or undefined');
        }
        if (typeof predicate !== 'function') {
            throw new TypeError('predicate must be a function');
        }
        var list = Object(this);
        var length = list.length >>> 0;
        var thisArg = arguments[1];
        var value;

        for (var i = 0; i < length; i++) {
            value = list[i];
            if (predicate.call(thisArg, value, i, list)) {
                return value;
            }
        }
        return undefined;
    };
}

function byNameContains(names, element){
    for(var i=0; names[i]; i++){
        if(!(element.name.indexOf(names[i]) > -1))
            return false;
    }
    return true
}

function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

(function($) {
    $.extendObjectArray = function(destArray, srcArray, key) {
        for (var index = 0; index < srcArray.length; index++) {
            var srcObject = srcArray[index];
            var existObject = destArray.filter(function(destObj) {
                return destObj[key] === srcObject[key];
            });
            if (existObject.length > 0) {
                var existingIndex = destArray.indexOf(existObject[0]);
                $.extend(true, destArray[existingIndex], srcObject);
            } else {
                destArray.push(srcObject);
            }
        }
        return destArray;
    };
})(jQuery);


/***** SELECTOR *****/
function Selector2(objParams) {

    if (typeof objParams.node === 'undefined') {
        this.menu = document.getElementById(objParams.id);
    } else {
        this.menu = objParams.node;
    }
    this.emptyOption = objParams.emptyOption;
    this.hiddenInput = this.menu.querySelector('input[type=hidden]');
    this.choices = this.menu.querySelector('.selector__choices');
    this.options = objParams.options;
    if (objParams.fromOptions) {
        this.buildOptionsFromObject(objParams.options);
    }
    this.cleanChoices();
    this.init();

    //events
    this.beforeSelect = objParams.beforeSelect ? objParams.beforeSelect : function(){return;};
    this.onSelect = objParams.onSelect ? objParams.onSelect : function(){return;};
}

Selector2.prototype.init = function () {

    var selected = this.choices.querySelector('.selector__item--selected');
    if (selected) {
        selected.index = Array.prototype.indexOf.call(this.choices.childNodes, selected);
        this.choices.insertBefore(this.choices.childNodes[selected.index], this.choices.childNodes[0]); // move selected to the top
        this.emptyOption = false;
    } else if(this.emptyOption){
        this.setEmptyOption();
    } else {
        this.selectFirst();
    }

    // register events
    this.choices.addEventListener('click', this.selectItem.bind(this));

    return selected ? getElementA(selected) : null;
};

Selector2.prototype.buildOptionsFromObject = function (options) {
    var key;
    for (key in options) {
        if (options.hasOwnProperty(key)) {
            this.addItem(key);
        }
    }
};

Selector2.prototype.setEmptyOption = function(){
    var selected = this.choices.querySelector(".selector__item--selected");
    if(selected){
        selected.className = 'selector__item';
    }
    this.choices.insertBefore($('<li class="selector__item--selected"></li>')[0], this.choices.childNodes[0]);
    this.hiddenInput.value = null;
    this.emptyOption = true;
};

Selector2.prototype.cleanChoices = function () {
    // filter child nodes - remove comments and text(whitespaces)
    for(var i = 0; this.choices.childNodes[i]; i++){
        if (this.choices.childNodes[i].nodeType === Node.COMMENT_NODE || this.choices.childNodes[i].nodeType === Node.TEXT_NODE) {
            this.choices.removeChild(this.choices.childNodes[i]);
        }
    }
};

Selector2.prototype.selectItem = function (event) {

    this.beforeSelect.call(this, this.choices.childNodes[0]);

    //remove empty option
    if(this.choices.childNodes[0].innerHTML == ''){
        this.emptyOption = false;
        this.choices.childNodes[0].remove();
    }

    if (event.target && event.target.nodeName == "A") {
        var index = $(event.target.parentNode).index();
        event.target.parentNode.className = 'selector__item--selected';
        this.choices.childNodes[0].className = 'selector__item';
        this.choices.insertBefore(event.target.parentNode, this.choices.childNodes[0]);                                 // move selected to the top

        if (this.hiddenInput) {
            this.hiddenInput.value = event.target.getAttribute('data-value');
        }
        // hide element for 100ms to force blur event on the element
        this.choices.style.display = 'none';
        setTimeout((function () {
            this.choices.style.display = '';
        }).bind(this), 1);

        this.onSelect.call(this, this.choices.childNodes[0]);
        return index;
    }
};

Selector2.prototype.selectItemIndex = function (index) {
    this.choices.childNodes[0].className = 'selector__item';
    this.choices.childNodes[index].className = 'selector__item--selected';
    this.choices.insertBefore(this.choices.childNodes[index], this.choices.childNodes[0]);                          // move selected to the top

    if (this.hiddenInput) {
        this.hiddenInput.value = event.target.getAttribute('data-value');
    }
    // hide element for 100ms to force blur event on the element
    this.choices.style.display = 'none';
    setTimeout((function () {
        this.choices.style.display = '';
    }).bind(this), 1);
};


Selector2.prototype.addItem = function (key) {
    this.choices.appendChild(
        $('<li>')
            .addClass('selector__item')
            .append('<a href="#" data-value="' + key + '">' + this.options[key] + '</a>')
            .get(0)
    );

    if (this.choices.childNodes.length === 1)
        this.selectFirst();

    this.itemsQuantityChangedHandler();
};

Selector2.prototype.removeItem = function (index) {
    this.choices.childNodes[index].remove();
    this.itemsQuantityChangedHandler();
};

Selector2.prototype.removeSelected = function () {
    this.removeItem(0);
    this.selectFirst();
};

Selector2.prototype.selectFirst = function () {
    if (this.choices.childNodes.length == 0)
        return;
    this.choices.childNodes[0].className = 'selector__item--selected';
};

Selector2.prototype.getSelection = function () {
    if(this.choices.childNodes.length > 0)
        return this.choices.childNodes[0];
};

Selector2.prototype.itemsQuantityChangedHandler = function () {
    return;
};


/**
 *
 */
var MultiLangWidget = {

    serializeForm: function(data){
        if(!this.$form.serialized){
            this.$form.serialized = $.extendObjectArray([], this.$form.serializeArray(), 'name');
        }
        if(data) {
            this.$form.serialized = $.extendObjectArray(this.$form.serialized, data, 'name');
        } else {
            this.$form.serialized = $.extendObjectArray(this.$form.serialized, this.$form.serializeArray(), 'name');
        }
        //console.log(this.$form.serialized);
    },

    /**
     * Gets complete, language specific form data and updates serialized form data
     * @path string  'cl=klarna_general', 'cl=klarna_general&fcn=someMethod'
     */
    getFullLangData: function(dataPath){

        $.getJSON(this.$form[0].action + dataPath)
            .done(this.mergeIntoFormData.bind(this));

        // retrieve array of system languages
        this.langs = JSON.parse(this.$form.attr('data-langs'));
    },

    mergeIntoFormData: function(response){

        var configSerialized = Object.keys(response).map(
            (function(fieldName){
                return { name: fieldName, value: decodeHtml(response[fieldName]) };
            }).bind(this)
        );
        //console.log(configSerialized);
        this.serializeForm(configSerialized);
    },

    retrieveDefaultValue: function(input){
        var sValue = input.getAttribute("data-default-value");
        if(!sValue) {
            input.value = "";
            return;
        }
        var defaultValues = JSON.parse(sValue);
        var currentLang = this.getCurrentLanguage();
        //console.log(currentLang, defaultValues);
        input.value = '';
        if(defaultValues[currentLang]) {
            input.value = defaultValues[currentLang];
        }
    },

    /**
     * Modifies language specific inputs (retrieve input name and value)
     * @param langId
     */
    changeForm:   function(langId){

        var langName = this.$inputs[0].name.match(/.*_(.*)]/)[1];
        this.$inputs.each(
            (function(i, input){
                input.name = input.name.replace(langName, this.langs[langId].abbr.toUpperCase());
                var inputData = this.$form.serialized.filter(byNameContains.bind(null, [input.name]));
                // console.log(inputData);
                if(inputData.length > 0 && inputData[0].value !== ""){
                    input.value = inputData[0].value;
                } else {
                    this.retrieveDefaultValue(input);
                }
            }).bind(this)
        );
    },

    removeFormData: function($object){
        var $inputs = $object.find('input');
        for(var i=0; $inputs[i]; i++){
            var toRemove = this.$form.serialized.find( function(element, index, serializedForm ){
                return element ? element.name === this.name : null;
            }, $inputs[i]); // thisArg
            this.$form.serialized.remove(toRemove);
        }
    },

    /**
     * Updates form data and submits it, shows message
     * @param event
     */
    submitFormData: function(event) {

        event.preventDefault();
        var $msgBox = $('.messagebox.info');
        this.serializeForm();
        $.post(this.$form.attr('action'), this.$form.serialized);
        $msgBox.slideDown(function () {
            setTimeout(function () {
                $msgBox.slideUp();
            }, 2000);
        });
    },

    beforeSelection: function(selected){

        this.serializeForm();
    },
    afterSelection: function(selected){

        this.changeForm($(selected).find('a').attr('data-value'));
    },

    getCurrentLanguage: function(){
        var selectedNode = this.langSelector.getSelection();

        return this.langs[selectedNode.firstElementChild.getAttribute('data-value')].abbr;
    },

    /**
     * {langSelectorId: 'langSelector', formCssSelector: 'form#myedit', toggleCssSelector: '#anonymized', inputsCssSelector: '#anonymized-value', dataPath: 'cl=klarna_general'}
     * @param settings
     */
    onInit: function(settings){
        // overwrite prototype methods
        for(var prop in settings){
            if(typeof settings[prop] === 'function'){
                this[prop] = settings[prop];
            }
        }

        this.$form = $(settings.formCssSelector);
        this.$toggle = $(settings.toggleCssSelector);
        this.$inputs =  $(settings.inputsCssSelector);

        this.langSelector = new Selector2({
            id: settings.langSelectorId,
            fromOptions: false,
            beforeSelect: this.beforeSelection.bind(this),
            onSelect: this.afterSelection.bind(this)
        });

        this.getFullLangData(settings.dataPath);

        // handle toggle switch
        if(this.$toggle.length > 0){
            this.$toggle.click( function(){
                $(this).closest("tr").next()
                    .find(".rows-wrapper")
                    .toggle(400);
            });
        }

        // set to default if empty
        this.$inputs.each(
            (function(i, input){
                if(!input.value){
                    this.retrieveDefaultValue(input);
                }
            }).bind(this)
        );

        this.errors = {};
        if(typeof this.validateFormData === 'function'){
            this.$smbButton = this.$form.find('input[type=submit]');
            this.$smbButton.click(this.validateFormData.bind(this));
        }
        this.$form.submit(this.submitFormData.bind(this));
    }
};

$(document).ready(function () {
    $('.kl-tooltip').tooltipster({
        theme: 'tooltipster-noir',
        trigger: 'click',
        animation: 'fade',
        maxWidth: 600,
        side: 'left'
    });
});