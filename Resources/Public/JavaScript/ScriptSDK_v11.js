/**
 * Module: Pixxio/PixxioExtension/Script
 */

define([
    "nprogress",
    "TYPO3/CMS/Core/Ajax/AjaxRequest",
    "TYPO3/CMS/Backend/Modal",
    "TYPO3/CMS/Backend/Severity",
    "TYPO3/CMS/Backend/Utility/MessageUtility",
    "jquery"
], function (NProgress, AjaxRequest, Modal, Severity, MessageUtility) {
    
    $(document).on("click", ".pixxio-sdk-btn", (event => {
        const clickedElement = $(event.currentTarget);
        loadPixxio(clickedElement.data('uid'));
    }));

    $(document).on("click", ".pixxio-close", (event => {
        const clickedElement = $(event.currentTarget);
        closePixxio(clickedElement.data('uid'));
    }));

    window.addEventListener("message", (messageEvent) => {
        if (
            messageEvent?.origin !== "https://plugin.pixx.io" ||
            messageEvent?.data?.sender !== "pixxio-plugin-sdk"
        )
            return;

        if (messageEvent?.data?.method === "downloadFiles") {
            downloadFiles(messageEvent?.data?.parameters[0]);
        }
    });

    function downloadFiles(files) {
        if (!files || !files.length) {
            console.warn("No files to download"); // eslint-disable-line no-console
        }

        let container = null;
        if (window.pixxioLastLightboxOpenerButton) {
            container = window.pixxioLastLightboxOpenerButton;
        } else {
            // Select the first button
            container = document.querySelector(".pixxio-btn");
        }

        NProgress.start();
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
                        MessageUtility.MessageUtility.send(message);

                        const lightboxes = document.querySelectorAll(".pixxio-lightbox");

                        if (lightboxes && lightboxes.length) {
                            lightboxes.forEach((lightbox) => {
                                lightbox.style.display = "none";
                            });
                        }
                    });
                } else {
                    var $confirm = Modal.confirm("ERROR", data.error, Severity.error, [
                        {
                            text: TYPO3.lang["button.ok"] || "OK",
                            btnClass: "btn-" + Severity.getCssClass(Severity.error),
                            name: "ok",
                            active: true,
                        },
                    ]).on("confirm.button.ok", function () {
                        $confirm.modal("hide");
                    });
                }
            });
        NProgress.done();
    }
});

/*
Sets src for pixxio iframe
 */
function loadPixxio(pixxioIframeId) {
    window.pixxioLastLightboxOpenerButton = document.getElementById('pixxio-btn-' + pixxioIframeId);
    var pixxioIframe = document.getElementById('pixxio-iframe-' + pixxioIframeId);
    var pixxioIframeSrc = pixxioIframe.dataset.src;
    if (pixxioIframeSrc != "") {
        pixxioIframe.src = pixxioIframeSrc;
    }
    var pixxioLightbox = document.getElementById('pixxio-lightbox-' + pixxioIframeId);
    pixxioLightbox.style.display = 'block';
}

/*
Hides Lightbox
 */
function closePixxio(pixxioIframeId) {
    var pixxioLightbox = document.getElementById('pixxio-lightbox-' + pixxioIframeId);
    pixxioLightbox.style.display = 'none';
}