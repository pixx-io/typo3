/**
 * Module: Pixxio/PixxioExtension/Script
 */

import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";

function init() {
  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("pixxio-sdk-btn")) {
      event.preventDefault();
      var pixxioIframe =
        event.target.parentElement.querySelector("iframe.pixxio_sdk");
      var pixxioIframeSrc = pixxioIframe.dataset.src;
      if (pixxioIframeSrc != "") {
        pixxioIframe.src = pixxioIframeSrc;
      }
      var pixxioLightbox =
        event.target.parentElement.querySelector(".pixxio-lightbox");
      pixxioLightbox.style.display = "block";

      var closeButton =
        event.target.parentElement.querySelector(".pixxio-close");
      if (closeButton) {
        closeButton.addEventListener("click", (event) => {
          var pixxioLightbox = event.target.closest(".pixxio-lightbox");
          pixxioLightbox.style.display = "none";
          pixxioIframe.src = "";
        });
      }

      window.pixxioLastLightboxOpenerButton = event.target;
    }
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
    container = document.querySelector(".pixxio-sdk-btn");
  }

  new AjaxRequest(TYPO3.settings.ajaxUrls.pixxio_files)
    .post(
      { files: files },
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
}
