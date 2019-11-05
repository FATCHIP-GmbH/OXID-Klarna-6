"use strict";
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

function KlButtonManager (buttonConfig) {
    var instanceIndex = 0;
    var buttons = document.querySelectorAll('klarna-instant-shopping');
    for(instanceIndex; instanceIndex < buttons.length; instanceIndex++) {
        buttons[instanceIndex].setAttribute('data-instance-id', instanceIndex);
        buttonConfig.setup.instance_id = instanceIndex;
        Klarna.InstantShopping.load(buttonConfig);
        console.log(instanceIndex, buttonConfig);
    }

    this.updateInstances = function(buttonConfig) {
        buttons = document.querySelectorAll('klarna-instant-shopping');
        instanceIndex++;
        for(var i=0; i < buttons.length; i++) {
            instanceIndex += i;
            buttonConfig.setup.instance_id = instanceIndex;
            Klarna.InstantShopping.load(buttonConfig);
            console.log(instanceIndex, buttonConfig);
        }
    };
}
var klButtonManager = null;
if (!window.klarnaAsyncCallback) {
    window.klarnaAsyncCallback = function () {
        klButtonManager = new KlButtonManager(klButtonManagerConfig);
    };
}
