/**
 * Module: Pixxio/PixxioExtension/Script
 *
 *
 */

import AjaxRequest from "@typo3/core/ajax/ajax-request.js";import Modal from "@typo3/backend/modal.js";import{MessageUtility}from "@typo3/backend/utility/message-utility.js";


  function init() {
    var containers = document.querySelectorAll(".pixxio-jsdk");

    /*
    var p = new PIXXIO({
      appKey: containers[0].getAttribute("data-key"),
      modal: true,
      compact: false,
      language: "de",
      element: containers[0],
      appUrl: containers[0].getAttribute("data-url"),
      refreshToken: containers[0].getAttribute("data-token"),
    });
     */

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

          /*
          p.getMedia({
            allowTypes: ["jpg", "png", "gif", "svg", "tif", "tiff"],
            max: -1,
            showSelection: false,
            showFileName: false,
            showFileType: true,
            showFileSize: false,
            additionalResponseFields: ["metadataFields", "description"],
          })
            .then((result) => {
              /*
              NProgress.start();
              new AjaxRequest(TYPO3.settings.ajaxUrls.pixxio_files)
                .post(
                  { files: result },
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
                  NProgress.done();
                });
            })
            .catch((error) => {
              console.trace(error);
            });

           */
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
