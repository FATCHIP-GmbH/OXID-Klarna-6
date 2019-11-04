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

function klButtonManager (buttonConfig) {
    var buttons = document.querySelectorAll('klarna-instant-shopping');
    for(var i=0; i < buttons.length; i++) {
        buttons[i].setAttribute('data-instance-id', i);
        buttonConfig.setup.instance_id = i;
        Klarna.InstantShopping.load(buttonConfig);
    }
}
if (!window.klarnaAsyncCallback) {
    window.klarnaAsyncCallback = function () {
        new klButtonManager(klButtonManagerConfig);
    };
}
