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

$('.row-label').click(function () {
    var $parent = $(this).parent();
    $parent.find('.rows-wrapper:first').toggle(400);
    $parent.find('.sign').toggleClass('minus');
    $parent.toggleClass('bg-grey');
});

$('input.radio_type').click(function(){

    var $choicesPlanes =  $(this).closest('.config-options').find('.rows-wrapper');
    /** radio style toggle switch */
    $(this)
        .closest('table')
        .find('input.radio_type')
        .each(
            (function(i, e){
                var $plane = $($choicesPlanes[i]);
                if(e === this && e.checked) {
                    $plane.show(400)
                        .find('input[type=radio]')[0]
                        .checked = e.checked ? true : false;
                } else {
                    e.checked = false;
                    $plane.hide(400);
                }
            }).bind(this));
});


/**
 * { langSelectorId: element id,
 *   formCssSelector: form element css selector,
 *   toggleCssSelector: toggle switch css selector,
 *   inputsCssSelector: inputs collection css selector
 *   dataPath: path to ajax endpoint
 *  }
 */
var bannerMultiLang = Object.create(MultiLangWidget);

bannerMultiLang.onInit({
    langSelectorId: 'langSelector',
    formCssSelector: 'form#myedit',
    toggleCssSelector: '#DisplayBanner',
    inputsCssSelector: '.source.m-lang',
    dataPath: 'cl=KlarnaDesign'
});


