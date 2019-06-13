/*
  @source https://www.git.taler.net/?p=web-common.git;a=blob_plain;f=taler-wallet-lib.ts;hb=HEAD
  @license magnet:?xt=urn:btih:5de60da917303dbfad4f93fb1b985ced5a89eac2&dn=lgpl-2.1.txt LGPL v21

  @licstart  The following is the entire license notice for the
  JavaScript code in this page.

  Copyright (C) 2015, 2016 INRIA

  The JavaScript code in this page is free software: you can
  redistribute it and/or modify it under the terms of the GNU
  Lesser General Public License (GNU LGPL) as published by the Free Software
  Foundation, either version 2.1 of the License, or (at your option)
  any later version.  The code is distributed WITHOUT ANY WARRANTY;
  without even the implied warranty of MERCHANTABILITY or FITNESS
  FOR A PARTICULAR PURPOSE.  See the GNU LGPL for more details.

  As additional permission under GNU LGPL version 2.1 section 7, you
  may distribute non-source (e.g., minimized or compacted) forms of
  that code without the copy of the GNU LGPL normally required by
  section 4, provided you include this license notice and a URL
  through which recipients can access the Corresponding Source.

  @licend  The above is the entire license notice
  for the JavaScript code in this page.

  @author Marcello Stanisci
  @author Florian Dold
*/

var taler;
(function (taler) {
    "use strict";
    var logVerbose = false;
    try {
        logVerbose = !!localStorage.getItem("taler-log-verbose");
    }
    catch (e) {
        // can't read from local storage
    }
    var presentHandlers = [];
    var absentHandlers = [];
    // Are we running as the content script of an
    // extension (and not just from a normal page)?
    var runningInExtension = false;
    var callSeqId = 1;
    var installed = false;
    var probeExecuted = false;
    var pageLoaded = false;
    var errorHandler = undefined;
    function onError(handler) {
        if (errorHandler) {
            console.warn("Overriding error handler");
        }
        errorHandler = handler;
    }
    taler.onError = onError;
    /**
     * Error handler for things that go wrong in the merchant
     * frontend browser code.
     */
    function raise_error(reason, detail) {
        if (errorHandler) {
            errorHandler(reason, detail);
            return;
        }
        alert("Failure: " + reason + ".  No error handler installed.  Open the developer console for more information.");
        console.error(reason, detail);
        console.warn("No custom error handler set.");
    }
    function callWallet(funcName, args, onResult) {
        var detail = JSON.parse(JSON.stringify(args || {}));
        var callId = callSeqId++;
        detail.callId = callId;
        var onTimeout = function () {
            console.warn("timeout for invocation of " + funcName);
        };
        var timeoutHandle = setTimeout(onTimeout, 1000);
        var handler = function (evt) {
            if (evt.detail.callId !== callId) {
                return;
            }
            if (onResult) {
                onResult(evt.detail);
            }
            clearTimeout(timeoutHandle);
            document.removeEventListener(funcName + "-result", handler);
        };
        document.addEventListener(funcName + "-result", handler);
        var evt = new CustomEvent(funcName, { detail: detail });
        document.dispatchEvent(evt);
    }
    /**
     * Confirm that a reserve was created.
     *
     * Used by tightly integrated bank portals.
     */
    function confirmReserve(reservePub) {
        if (!installed) {
            logVerbose && console.log("delaying confirmReserve");
            taler.onPresent(function () {
                confirmReserve(reservePub);
            });
            return;
        }
        callWallet("taler-confirm-reserve", { reserve_pub: reservePub });
    }
    taler.confirmReserve = confirmReserve;
    function createReserve(callbackUrl, amount, wtTypes, suggestedExchangeUrl) {
        if (!installed) {
            logVerbose && console.log("delaying createReserve");
            taler.onPresent(function () {
                createReserve(callbackUrl, amount, wtTypes, suggestedExchangeUrl);
            });
            return;
        }
        var args = {
            callback_url: callbackUrl,
            amount: amount,
            wt_types: wtTypes,
            suggested_exchange_url: suggestedExchangeUrl
        };
        callWallet("taler-create-reserve", args);
    }
    taler.createReserve = createReserve;
    function onPresent(f) {
        presentHandlers.push(f);
    }
    taler.onPresent = onPresent;
    function onAbsent(f) {
        absentHandlers.push(f);
    }
    taler.onAbsent = onAbsent;
    function pay(p) {
        if (!installed) {
            logVerbose && console.log("delaying call to 'pay' until GNU Taler wallet is present");
            taler.onPresent(function () {
                pay(p);
            });
            return;
        }
        callWallet("taler-pay", p);
    }
    taler.pay = pay;
    function refund(refundUrl) {
        if (!installed) {
            logVerbose && console.log("delaying call to 'refund' until GNU Taler wallet is present");
            taler.onPresent(function () {
                refund(refundUrl);
            });
            return;
        }
        callWallet("taler-refund", refundUrl);
    }
    taler.refund = refund;
    function addAuditor(d) {
        if (!installed) {
            logVerbose && console.log("delaying call to 'addAuditor' until GNU Taler wallet is present");
            taler.onPresent(function () {
                addAuditor(d);
            });
            return;
        }
        callWallet("taler-add-auditor", d);
    }
    taler.addAuditor = addAuditor;
    /**
     * Check if an auditor is already added to the wallet.
     *
     * Same-origin restrictions apply.
     */
    function checkAuditor(url) {
        if (!installed) {
            logVerbose && console.log("delaying call to 'checkAuditor' until GNU Taler wallet is present");
            return new Promise(function (resolve, reject) {
                taler.onPresent(function () {
                    resolve(checkAuditor(url));
                });
            });
        }
        return new Promise(function (resolve, reject) {
            taler.onPresent(function () {
                callWallet("taler-check-auditor", url, function (x) { return resolve(x); });
            });
        });
    }
    taler.checkAuditor = checkAuditor;
    function initTaler() {
        function handleUninstall() {
            installed = false;
            // not really true, but we want "uninstalled" to be shown
            firstTimeoutCalled = true;
            announce();
        }
        function handleProbe() {
            probeExecuted = true;
            if (!installed) {
                logVerbose && console.log("taler install detected");
                installed = true;
                announce();
            }
        }
        function probeTaler() {
            probeExecuted = false;
            var eve = new Event("taler-probe");
            document.dispatchEvent(eve);
        }
        var firstTimeoutCalled = false;
        function onProbeTimeout() {
            if (!probeExecuted) {
                if (installed || !firstTimeoutCalled) {
                    installed = false;
                    firstTimeoutCalled = true;
                    logVerbose && console.log("taler uninstall detected");
                    announce();
                }
            }
            // try again, maybe it'll be installed ...
            probeTaler();
        }
        /**
         * Announce presence/absence
         *
         * Only called after document.readyState is at least "interactive".
         */
        function announce() {
            if (!pageLoaded) {
                logVerbose && console.log("page not loaded yet, announcing later");
                return;
            }
            if (installed) {
                logVerbose && console.log("announcing installed");
                for (var i = 0; i < presentHandlers.length; i++) {
                    presentHandlers[i]();
                }
            }
            else {
                if (firstTimeoutCalled) {
                    logVerbose && console.log("announcing uninstalled");
                    for (var i = 0; i < absentHandlers.length; i++) {
                        absentHandlers[i]();
                    }
                }
                else {
                    logVerbose && console.log("announcing nothing");
                }
            }
        }
        function onPageLoad() {
            pageLoaded = true;
            // We only start the timeout after the page is interactive.
            window.setInterval(onProbeTimeout, 300);
            announce();
        }
        probeTaler();
        document.addEventListener("taler-probe-result", handleProbe, false);
        document.addEventListener("taler-uninstall", handleUninstall, false);
        // Handle the case where the JavaScript is loaded after the page
        // has been loaded for the first time.
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", onPageLoad, false);
        }
        else {
            onPageLoad();
        }
    }
    logVerbose && console.log("running taler-wallet-lib from page");
    initTaler();
})(taler || (taler = {}));
// @license-end