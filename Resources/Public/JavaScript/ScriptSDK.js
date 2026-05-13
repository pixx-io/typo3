/**
 * Module: Pixxio/PixxioExtension/Script
 */

import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";

// Guard to ensure init is only called once
let isInitialized = false;

function handleClick(event) {
  var buttonElement = null;

  // Only proceed if the click target is related to pixx.io
  if (event.target.classList.contains("pixxio-sdk-btn")) {
    buttonElement = event.target;
  } else if (event.target.closest(".pixxio-sdk-btn")) {
    buttonElement = event.target.closest(".pixxio-sdk-btn");
  }

  // Early return if not a pixx.io button - no impact on other elements
  if (!buttonElement) {
    return;
  }

  event.preventDefault();
  var pixxioIframe =
    buttonElement.parentElement.querySelector("iframe.pixxio_sdk");
  var pixxioIframeSrc = pixxioIframe.dataset.src;
  if (pixxioIframeSrc) {
    pixxioIframe.src = pixxioIframeSrc;
  }
  var pixxioLightbox =
    buttonElement.parentElement.querySelector(".pixxio-lightbox");
  pixxioLightbox.style.display = "block";

  var closeButton = buttonElement.parentElement.querySelector(".pixxio-close");

  if (closeButton && !closeButton.dataset.pixxioListenerAttached) {
    closeButton.dataset.pixxioListenerAttached = "true";
    closeButton.addEventListener("click", (event) => {
      event.preventDefault();

      pixxioLightbox.style.display = "none";
      pixxioIframe.src = "";
    });
  }

  // Store in namespaced global variable to avoid conflicts
  if (!window.pixxio) {
    window.pixxio = {};
  }
  window.pixxio.lastLightboxOpenerButton = buttonElement;
}

function init() {
  // Prevent multiple initializations
  if (isInitialized) {
    return;
  }
  isInitialized = true;

  document.addEventListener("click", handleClick);
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

function handlePostMessage(messageEvent) {
  // Early return for non-pixx.io messages - no impact on other functionality
  if (
    messageEvent?.origin !== "https://plugin.pixx.io" ||
    messageEvent?.data?.sender !== "pixxio-plugin-sdk"
  ) {
    return;
  }

  if (messageEvent?.data?.method === "downloadFiles") {
    downloadFiles(messageEvent?.data?.parameters[0]);
  } else if (messageEvent?.data?.method === "directLinksCreated") {
    downloadFiles(messageEvent?.data?.parameters[0]);
  } else if (messageEvent?.data?.method === "onSdkReady") {
    handleSdkReady(messageEvent);
  }
}

// Register global message listener (with strict origin and sender checks)
window.addEventListener("message", handlePostMessage);

function downloadFiles(files) {
  if (!files || !files.length) {
    console.warn("[pixx.io] No files to download"); // eslint-disable-line no-console
    return;
  }

  // Only access pixx.io-specific elements
  let container = null;
  if (window.pixxio?.lastLightboxOpenerButton) {
    container = window.pixxio.lastLightboxOpenerButton;
  } else {
    // Select the first pixx.io button
    container = document.querySelector(".pixxio-sdk-btn");
  }

  // Safety check: abort if no pixx.io container found
  if (!container) {
    console.warn("[pixx.io] No pixx.io button container found"); // eslint-disable-line no-console
    return;
  }

  new AjaxRequest(TYPO3.settings.ajaxUrls.pixxio_files)
    .post(
      {
        files: files.map((file) => {
          const metadata = file.metadata || {};
          return {
            // Spread the metadata fields to the root level for easier access in PHP
            // Map the metadata fields to an array of objects with name and value to emulate metadataFields data structure for PHP
            ...metadata,
            metadataFields: Object.keys(metadata).map((key) => ({
              name: key,
              value: metadata[key],
            })),
            ...file,
          };
        }),
      },
      {
        headers: {
          "Content-Type": "application/json; charset=utf-8",
        },
      },
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
        });

        // Only close pixx.io lightboxes (identified by specific class)
        const lightboxes = document.querySelectorAll(".pixxio-lightbox");

        if (lightboxes && lightboxes.length) {
          lightboxes.forEach((lightbox) => {
            lightbox.style.display = "none";
          });
        }
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

function handleSdkReady(messageEvent) {
  // Find the corresponding button and iframe for this SDK ready event
  let targetButton = null;
  let targetIframe = null;

  // If we have a last opened button, use that
  if (window.pixxio?.lastLightboxOpenerButton) {
    targetButton = window.pixxio.lastLightboxOpenerButton;
    targetIframe =
      targetButton.parentElement.querySelector("iframe.pixxio_sdk");
  } else {
    // Otherwise find the first visible iframe
    const visibleLightboxes = document.querySelectorAll(
      '.pixxio-lightbox[style*="block"]',
    );
    if (visibleLightboxes.length > 0) {
      targetIframe = visibleLightboxes[0].querySelector("iframe.pixxio_sdk");
      targetButton =
        visibleLightboxes[0].parentElement.querySelector(".pixxio-sdk-btn");
    }
  }

  // Check if auto-login is enabled and we have the required data
  if (
    targetButton &&
    targetIframe &&
    targetButton.getAttribute("data-auto-login") === "1"
  ) {
    const refreshToken = targetButton.getAttribute("data-refresh-token");
    const mediaspaceUrl = targetButton.getAttribute("data-mediaspace-url");

    if (refreshToken && mediaspaceUrl) {
      // Decode the base64 encoded values
      const decodedRefreshToken = atob(refreshToken);
      const decodedMediaspaceUrl = atob(mediaspaceUrl).replace("https://", "");

      // Send the login success message to the iframe
      const loginMessage = {
        receiver: "pixxio-plugin-sdk",
        method: "login",
        parameters: [
          {
            refreshToken: decodedRefreshToken,
            mediaspaceDomain: decodedMediaspaceUrl,
          },
        ],
      };

      targetIframe.contentWindow.postMessage(
        loginMessage,
        "https://plugin.pixx.io",
      );
    }
  }
}
