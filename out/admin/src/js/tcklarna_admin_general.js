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

/****************     FormRows      ***************/
function FormRows(accSelector) {

    this.accAddButton = document.getElementById('add-country-creds');
    this.rowTemplate = tcklarna_countryCredsTemplate;
    this.newItem = {};
    this.newItem.wrapper = this.accAddButton.parentNode.parentNode.nextElementSibling.querySelector('.rows-wrapper');
    this.newItem.countrySelector = accSelector;
    this.newItem.$inputs = $('input', this.newItem.wrapper);
    this.newItem.$saveButton = $('#acc-save', this.newItem.wrapper);

    this.init();
}

FormRows.prototype.init = function () {
    // register events
    $(this.accAddButton).click(this.toggleAddForm.bind(this));
    this.newItem.$saveButton.click(this.validateNewItem.bind(this));
    $('.klarna-conf-table').delegate('.acc-remove', 'click', this.removeItem.bind(this));
};

FormRows.prototype.validateInput = function (input) {
    return (input.value !== '') ? true : false;
};
FormRows.prototype.getCountryISO = function () {
    return getElementA(this.newItem.countrySelector.choices.childNodes[0]).getAttribute('data-value');
};


FormRows.prototype.toggleAddForm = function (event) {
    if (event.currentTarget.style.color === '') {                                    // show
        event.currentTarget.style.color = '#000';
        event.currentTarget.parentNode.parentNode.style.borderBottom = 'none';
        event.currentTarget.firstChild.className = '';


    } else {                                                                            // hide
        event.currentTarget.style.color = '';
        event.currentTarget.firstChild.className = 'fa fa-plus fa-lg';
        event.currentTarget.parentNode.parentNode.style.borderBottom = '1px solid #ddd';

    }
    $(this.newItem.wrapper).toggle(400);
};

FormRows.prototype.validateNewItem = function (event) {
    event.preventDefault();
    var count = this.newItem.$inputs.length;
    this.newItem.isValid = true;
    this.newItem.values = [];
    this.newItem.$inputs.each(
        (function (i, input) {
            var inputIsValid = this.validateInput(input);
            if (!inputIsValid) {
                this.displayValidationMessage(input, 'Field can not be empty.');
            } else {
                this.newItem.values.push(input.value);
                this.displayValidationMessage(input, '');
            }

            this.newItem.isValid = this.newItem.isValid && inputIsValid;

            if (!--count && this.newItem.isValid) {         // do we have all inputs checked and valid
                this.accAddButton.click();          // hide newItem form
                this.newItem.$inputs.val('');               // clear inputs
                this.createItem();
            }

        }).bind(this)
    );
};

FormRows.prototype.displayValidationMessage = function (input, message) {
    if (message != '') {
        $(input).after('<br><span class="error"> ' + message + '</span>')
    } else {
        $(input).next('br').remove();
        $(input).next('span.error').remove();
    }
};

FormRows.prototype.setAttrs = function (element, countryISO) {

    switch(element.nodeName) {

        case 'INPUT':
            element.name = element.name.replace( 'aKlarnaCreds_',  'aKlarnaCreds_' + countryISO);
            element.setAttribute('value', this.newItem.values.shift());
            break;

        case 'DIV':
            if(element.classList.contains('klarna-flag'))
                element.classList.add(countryISO.toLowerCase());

            if(element.classList.contains('rows-wrapper'))
                $(element).removeAttr('style');      // remove for css animation on add
            break;

        case 'A':
            element.setAttribute('data-country', countryISO);
            break;
    }
};

FormRows.prototype.createItem = function () {

    var $template = $(this.rowTemplate);
    $template.get(0).className = 'klarna-creds_' + this.getCountryISO() + ' csc_first';
    $template.find('div.klarna-flag, div.rows-wrapper, input, a.acc-remove').each(
        (function (i, element) {
            this.setAttrs(element, this.getCountryISO());
        }).bind(this)
    );
    $('#acc-separator').before($template);
    this.newItem.countrySelector.removeSelected();
    if ($('a.acc-remove').length > 0) {
        $('#ycsc').removeClass('hidden');
    }
    setTimeout(function(){$template.find('.rows-wrapper').toggle(400);}, 10);
                          // adds css animation
};

FormRows.prototype.removeItem = function (event) {
    var countryISO = event.target.parentNode.getAttribute('data-country');
    var $item = $(event.target)
        .closest('.rows-wrapper')
            .toggle(400)
        .closest('tr');
    setTimeout(
        function(){
            $item.remove();
            if ($('a.acc-remove').length === 0) {
                $('#ycsc').addClass('hidden');
            }
        },
        400
    );

    this.newItem.countrySelector.addItem(countryISO);
    multiLangForm.removeFormData($item);
};

function CountrySpecificCredentialsSelector(initObject) {

    this.accAddButton = document.getElementById('add-country-creds');
    Selector2.call(this, initObject);
}

CountrySpecificCredentialsSelector.prototype = Object.create(Selector2.prototype);
CountrySpecificCredentialsSelector.prototype.constructor = Selector2;

CountrySpecificCredentialsSelector.prototype.init = function () {
    Selector2.prototype.init.call(this);  // calling parent method with new context
    this.itemsQuantityChangedHandler();
};
CountrySpecificCredentialsSelector.prototype.itemsQuantityChangedHandler = function () {
    if (this.choices.childNodes.length == 0) {
        this.accAddButton.className = 'hidden';
    } else {
        this.accAddButton.className = '';
    }
};


var multiLangForm = Object.create(MultiLangWidget);
multiLangForm.onInit({
    langSelectorId: 'langSelector',
    formCssSelector: 'form#myedit',
    toggleCssSelector: '#anonymized',
    inputsCssSelector: '#anonymized-value',
    dataPath: 'cl=KlarnaGeneral'
});

var modeSelector = new Selector2({
        id: 'modeSelector',
        fromOptions: false
    }),
    accSelector = new CountrySpecificCredentialsSelector({
        id: 'accSelector',
        fromOptions: false,
        options: tcklarna_countriesList                   // needed to add new choice on row remove
    }),
    accComponent = new FormRows(accSelector);