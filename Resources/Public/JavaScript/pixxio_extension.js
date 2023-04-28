/**
 * Module: Pixxio/PixxioExtension/Script
 *
 *
 */

//import Modal from "@typo3/backend/modal.js";

var pixxioIframe = document.getElementById('pixxio_sdk');

if (top && typeof top.TYPO3 !== "undefined" && typeof top.TYPO3.Modal !== "undefined") {
    top.TYPO3.Modal.show(
        'Debug: ',
        '<iframe style="display:none" id="pixxio_sdk" data-src="https://plugin.pixx.io/static/v1/de/media?applicationId=eS9Pb3S5bsEa2Z6527lUwUBp8" width="100%" height="100%"></iframe>',
        top.TYPO3.Severity.notice
    );
} else {
    browserWindow(debugMessage, header, group);
}
//TYPO3.Modal.confirm("The title of the modal", "This the the body of the modal");