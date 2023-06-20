/**
 * Module: Pixxio/PixxioExtension/Script
 *
 *
 */

import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import {MessageUtility} from "@typo3/backend/utility/message-utility.js";


function init() {
    var containers = document.querySelectorAll(".pixxio-sdk-btn");

    containers.forEach((container) => {
        document
            .querySelector(
                ".btn-default.pixxio[data-uid='" +
                container.getAttribute("data-uid") +
                "']"
            )
            .addEventListener("click", function (event) {
                event.preventDefault();
                var pixxioLightbox = document.getElementById('pixxio-lightbox');
                pixxioLightbox.style.display = 'block';

                var pixxioIframe = document.getElementById('pixxio_sdk');
                var pixxioIframeSrc = pixxioIframe.dataset.src;
                if (pixxioIframeSrc != "") {
                    pixxioIframe.src = pixxioIframeSrc;
                }
            });
    });

    document
        .querySelector(
            ".pixxio-close"
        )
        .addEventListener("click", function (event) {
            var pixxioLightbox = document.getElementById('pixxio-lightbox');
            pixxioLightbox.style.display = 'none';
        });
}

if (document.readyState === "complete") {
    init();
} else {
    document.addEventListener("readystatechange", (event) => {
        if (event.target.readyState === "complete") {
            init();
        }
    });
}

window.addEventListener('message', (messageEvent) => {
    if (
        messageEvent?.origin !== 'https://plugin.pixx.io' ||
        messageEvent?.data?.sender !== 'pixxio-plugin-sdk'
    )
        return;

    if (messageEvent?.data?.method === 'downloadFiles') {
        downloadFiles(messageEvent?.data?.parameters[0]);
    }
});


function downloadFiles(files) {
    if (!files || !files.length) {
        console.warn('No files to download'); // eslint-disable-line no-console
    }

    var containers = document.querySelectorAll(".pixxio-sdk-btn");
    var container = containers[0];

    new AjaxRequest(TYPO3.settings.ajaxUrls.pixxio_files)
        .post(
            {files: files},
            {
                headers: {
                    "Content-Type": "application/json; charset=utf-8",
                },
            }
        )
        .then(async function (response) {
            const data = await response.resolve();
            if (data.files.length) {
                data.files.forEach(function (uid) {
                    const message = {
                        actionName: "typo3:foreignRelation:insert",
                        objectGroup: container.getAttribute("data-dom"),
                        table: "sys_file",
                        uid: uid,
                    };
                    MessageUtility.send(message);
                    var pixxioLightbox = document.getElementById('pixxio-lightbox');
                    pixxioLightbox.style.display = 'none';
                });
            } else {
                var $confirm = Modal.confirm(
                    "ERROR",
                    data.error,
                    Severity.error,
                    [
                        {
                            text: TYPO3.lang["button.ok"] || "OK",
                            btnClass:
                                "btn-" + Severity.getCssClass(Severity.error),
                            name: "ok",
                            active: true,
                        },
                    ]
                ).on("confirm.button.ok", function () {
                    $confirm.modal("hide");
                });
            }
        })
}