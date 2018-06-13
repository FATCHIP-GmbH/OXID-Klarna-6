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

(function () {
    var $form = $('form#myedit');
    var token = $('input[name="stoken"]').val();
    var imageUrls = {};
    var errorMsgs;
    var formData = [];

    getData();

    $('.row-label').click(function () {
        var $parent = $(this).parent();
        $parent.find('.rows-wrapper:first').toggle(400);
        $parent.find('.sign').toggleClass('minus');
        $parent.toggleClass('bg-grey');
    });


    $('.js-external-payment').change(function () {

        var $outerContainer = $(this).closest('.klarna-conf-table'),
            $rows = $outerContainer.find('.rows-wrapper .rows-wrapper'),
            $toggleInputs = $outerContainer.find('.js-external-payment'),
            $urlsRows = $outerContainer.find('.rows-wrapper:first'),
            changedInput = this;

        $toggleInputs.each(function(i, element){

            if(element === changedInput){
                this.$row = $($rows[i]);
                this.$input = this.$row.find('input');
                this.$row.toggle(400);

                // switch on | off pattern validation
                if(this.$input.attr('pattern')){
                    this.$input.attr('data-pattern', this.$input.attr('pattern'));
                    this.$input.removeAttr('pattern');
                } else if(this.$input.attr('data-pattern')){
                    this.$input.attr('pattern', this.$input.attr('data-pattern'));
                    this.$input.removeAttr('data-pattern');
                }

                if(this.name.match(/oxpayments__tcklarna_externalcheckout/)) {

                    if (this.$input.prop('required')) {
                        this.$input.prop('required', false);
                        this.$input.get(0).setCustomValidity('');
                    } else {
                        this.$input.prop('required', true);
                    }
                }
            }
        });
        var display = $urlsRows.css('display');
        if($toggleInputs.filter(':checked').length === 0 && display === 'block'){
            $urlsRows.toggle(400);
        }

        if($toggleInputs.filter(':checked').length > 0 && display === 'none'){
            $urlsRows.toggle('slow');
        }
    });

    /********* Payment Selectors ************/
    $('.payment-selector').each(function () {
        new Selector2({
            node: this,
            fromOptions: false,
            emptyOption: true
        });
    });

    // ***  Language Selector ***
    var instances = [];
    function LangSelector(obj) {
        Selector2.call(this, obj);
        instances.push(this);
    }

    LangSelector.prototype = Object.create(Selector2.prototype);
    LangSelector.prototype.constructor = Selector2;
    LangSelector.prototype.selectItem = function (event) {

        var selectedIndex = Selector2.prototype.selectItem.call(this, event);  // calling parent method with new context
        for (var i = 0; i < instances.length; i++) {
            if (this !== instances[i]) {
                instances[i].selectItemIndex(selectedIndex);
            }
        }

        var langId = this.choices.childNodes[0].getAttribute('data-value'),
            $inputs = $('.js-multilang-input');

        formData = $.extendObjectArray(formData, $form.serializeArray(), 'name');
        $inputs.each(function () {
            var name = $(this).data('field-name'),
                paymentId = $(this).data('payment-id');

            if (langId !== '0') {
                name = name + '_' + langId;
            }
            var newName = 'payments' + '[' + paymentId + ']' + '[' + name + ']';
            $(this).attr('name', 'payments' + '\[' + paymentId + '\]' + '\[' + name + '\]');                            // change input name

            // change input value
            this.value = $.grep(formData, function(obj){
                if(obj.name === newName) {
                    return obj;
                }
            })[0].value;

            loadImg($(this).closest('td').find('img'), this.value);             // reload img
        });
    };

    $('.langSelector').each(function () {
        new LangSelector({
            id: 'langSelector',
            node: this,
            fromOptions: false
        });
    });

    $('.js-multilang-input').change(function(){
        loadImg($(this).closest('td').find('img'), this.value);
    });

    $form.find('input[type=submit]').click(validateFormData);

    $form.submit(submitForm);

    function validateFormData(){
        var $validateInputs = $('input.js-multilang-input');
        var langs = $('.langSelector:first .selector__choices');

        formData = $.extendObjectArray(formData, $form.serializeArray(), 'name');

        $validateInputs.each(function(){

            // find formData related to the input
            var fieldName  = $(this).data('field-name');
            var payment_id = $(this).data('payment-id');
            var pattern = this.getAttribute('pattern');

            this.setCustomValidity(''); // reset validator
            var langErrors = {invalidPatter: [], missingValue: []};
            this.formValues = formData.filter(byNameContains.bind(null, [fieldName, payment_id]));

            for(var j = 0; this.formValues[j]; j++){
                var langId = this.formValues[j].name.match(/\d+/) ? this.formValues[j].name.match(/\d+/)[0] : '0';

                // has pattern , not empty, patternMismatch
                if(pattern && (this.formValues[j].value && !this.formValues[j].value.match(pattern))){
                    langErrors.invalidPatter.push($('[data-value='+ langId +'] a ', langs).html());
                    this.setCustomValidity(errorMsgs.patternMismatch + ' [' + langErrors.invalidPatter.join(', ') +']');
                }

                // required, is empty
                if(this.required && this.formValues[j].value === ""){
                    langErrors.missingValue.push($('[data-value='+ langId +'] a ', langs).html());
                    this.setCustomValidity(errorMsgs.valueMissing + ' [' + langErrors.missingValue.join(', ') +']');
                }
            }
        });
    }

    function submitForm(e){
        e.preventDefault();
        $.post($form.attr('action'), formData);
        $('.messagebox.info').slideDown(function() {
            setTimeout(function() {
                $('.messagebox.info').slideUp();
            }, 2000);
        });
    }

    function getData() {
        $.ajax({
            url: '?cl=KlarnaExternalPayments&fnc=getMultilangUrls&stoken=' + token,
            type: 'GET',
            dataType: 'json',
        }).success(function(oData){
            imageUrls = oData.imageUrls;
            formData = $.extendObjectArray(formData, oData.imageUrls, 'name');
            errorMsgs = oData.errorMsg;
        });
    }

    function loadImg($target, url){
        console.log(url);
        if(url)
            $target.attr('src', url);
        else
            $target.removeAttr('src');
    }

})();
