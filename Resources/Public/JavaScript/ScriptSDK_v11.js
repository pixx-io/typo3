/**
 * Module: Pixxio/PixxioExtension/Script
 *
 *
 */

define([
    "nprogress",
    "TYPO3/CMS/Core/Ajax/AjaxRequest",
    "TYPO3/CMS/Backend/Modal",
    "TYPO3/CMS/Backend/Severity",
    "TYPO3/CMS/Backend/Utility/MessageUtility",
], function (NProgress, AjaxRequest, Modal, Severity, MessageUtility) {
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
                    var pixxioIframe = document.getElementById('pixxio_sdk');
                    var pixxioIframeSrc = pixxioIframe.dataset.src;
                    if (pixxioIframeSrc != "") {
                        pixxioIframe.src = pixxioIframeSrc;
                    }
                    var pixxioLightbox = document.getElementById('pixxio-lightbox');
                    pixxioLightbox.style.display = 'block';
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
        if (messageEvent?.data?.method === 'onSdkReady') {
            var pixxioIframe = document.getElementById('pixxio_sdk');
            var pixxio_token_refresh = atob(document.getElementById('pixxio_token_refresh').dataset.value);
            var pixxio_user_id = atob(document.getElementById('pixxio_user_id').dataset.value);
            var pixxio_mediaspace = atob(document.getElementById('pixxio_mediaspace').dataset.value);

            if (pixxio_token_refresh != '' && pixxio_user_id != '' && pixxio_mediaspace != '') {
                pixxioIframe.contentWindow.postMessage(
                    {
                        receiver: 'pixxio-plugin-sdk',
                        method: 'login',
                        parameters: [pixxio_token_refresh, pixxio_user_id, pixxio_mediaspace]
                    },
                    'https://plugin.pixx.io'
                );
            }
        }
    });

    function downloadFiles(files) {
        if (!files || !files.length) {
            console.warn('No files to download'); // eslint-disable-line no-console
        }

        var containers = document.querySelectorAll(".pixxio-sdk-btn");
        var container = containers[0];

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
        NProgress.done();
    }
});