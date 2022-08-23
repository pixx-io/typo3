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
  window.addEventListener("load", function () {
    var containers = document.querySelectorAll(".pixxio-jsdk");
    
    var p = new PIXXIO({
      appKey: containers[0].getAttribute("data-key"),
      modal: true,
      compact: false,
      language: "de",
      element: containers[0],
      appUrl: containers[0].getAttribute("data-url"),
      refreshToken: containers[0].getAttribute("data-token"),
    });
    
    containers.forEach((container) => {
      document
        .querySelector(".btn-default.pixxio[data-uid='"+ container.getAttribute('data-uid')+"']")
        .addEventListener("click", function (event) {
          event.preventDefault();
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
        });
    });
  });
});
