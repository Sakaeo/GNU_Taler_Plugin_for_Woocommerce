/**
 * Wallet detection script
 * Detects which browser the user is using and if the GNU Taler wallet is installed
 * If the wallet isn't detected or the customer uses a browser which isn't supported, then it removes the GNU Taler payment method from the possibilities.
 *
 * Currently only the Browser detection is working and the wallet detection isn't
 * The reason is, that we couldn't figure out how to detect the wallet with the given functions of the taler-wallet-lib.js
 */

function detectWallet() {

    sUsrAg = navigator.userAgent;

    if ((sUsrAg.indexOf("Firefox") > -1) || (sUsrAg.indexOf("Opera") > -1 || sUsrAg.indexOf("OPR") > -1) || (sUsrAg.indexOf("Chrome") > -1)) {
        //Mozilla Firefox, Opera or Google Chrome
        taler.onAbsent(() => {
               //Does nothing
        });
    } else if ((sUsrAg.indexOf("Trident") > -1) || (sUsrAg.indexOf("Edge") > -1) || (sUsrAg.indexOf("Safari") > -1)) {
        //Microsoft Internet Explorer, Microsoft edge or Apple Safari
        removePaymentMethod();
    } else {
        //Unknown Browser
        removePaymentMethod();
    }
}

function removePaymentMethod() {
    var observer = new MutationObserver(function (mutations, observer) {
        mutations.forEach(() => {
            document.getElementsByClassName("wc_payment_method payment_method_gnutaler").item(0).style.display = 'none';
        });
    });

    // define what element should be observed by the observer
    // and what types of mutations trigger the callback
    observer.observe(document, {
        subtree: true,
        attributes: true
    });
}

detectWallet();