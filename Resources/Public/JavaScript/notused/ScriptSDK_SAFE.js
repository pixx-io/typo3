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


    console.log(TYPO3.settings.ajaxUrls.pixxio_files);

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
          console.log(data.files);
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
        });




    containers.forEach((container) => {
      document.querySelector(
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

}

window.addEventListener( 'message', ( messageEvent ) => {
  if (
      messageEvent?.origin !== 'https://plugin.pixx.io' ||
      messageEvent?.data?.sender !== 'pixxio-plugin-sdk'
  )
    return;

  if ( messageEvent?.data?.method === 'downloadFiles' ) {
    downloadFiles( messageEvent?.data?.parameters[ 0 ] );
  }
} );


function downloadFiles( files ) {
  alert("1");
  console.log(files);
  if ( ! files || ! files.length ) {
    console.warn( 'No files to download' ); // eslint-disable-line no-console
  }

  //var downloadErrors = [];

  const progressData = {
    totalFiles: files.length,
    processedFiles: 0,
    fileProgress: new Map(),
    fakeInterval: null,
    fakeProgress: 0,
  };

  files.forEach( ( file ) => {
    progressData.fileProgress.set( file, 0 );
    downloadSingleFile( file, progressData );
  } );
}

function downloadSingleFile( file, progressData ) {
  console.log(file);
  console.log(progressData);
  alert(progressData);
}

  /*
  $.ajax( {
    xhr() {
      const xhr = new window.XMLHttpRequest();
      const jAjax = this;

      xhr.addEventListener(
          'progress',
          function ( event ) {
            const responseLines = event.currentTarget.responseText
                .split( '\n' )
                .filter( Boolean );
            if ( ! responseLines.length ) {
              return;
            }

            const lastLine =
                responseLines[ responseLines.length - 1 ].trim();
            let lastResponse = {};
            try {
              lastResponse = JSON.parse( lastLine );
            } catch ( e ) {
              return;
            }

            if ( lastResponse?.success === undefined ) {
              if ( lastResponse?.progress !== undefined ) {
                chunkResponseEnabled = true;
                progressData.fileProgress.set(
                    file,
                    lastResponse.progress
                );

                pxSend( 'setDownloadProgress', [
                  calculateProgress( progressData ),
                ] );
                if (
                    lastResponse.progress === 100 &&
                    progressData.fakeInterval === null
                ) {
                  progressData.fakeInterval = setInterval(
                      function () {
                        const progressSoFar =
                            calculateProgress(
                                progressData
                            );

                        progressData.fakeProgress +=
                            ( 100 - progressSoFar ) /
                            ( progressData.totalFiles -
                                progressData.processedFiles ) /
                            5;

                        pxSend( 'setDownloadProgress', [
                          calculateProgress(
                              progressData
                          ),
                        ] );
                      },
                      500
                  );
                }
              } else {
                // eslint-disable-next-line no-console
                console.error( `Unexpected response: ${ lastResponse }` ); // prettier-ignore
              }
            } else {
              progressData.fileProgress.set( file, 100 );
              jAjax.fileProcessed( lastResponse, ...arguments );
            }
          },
          false
      );

      return xhr;
    },
    type: 'POST',
    url: ajaxurl,
    data: {
      action: 'download_pixxio_image',
      file,
      returnMediaItem: !! mediaItems,
      nonce: pxSDK().dataset.nonce,
    },
    // we don't use the default success()
    // because otherwise it might be fired twice
    fileProcessed( data ) {
      progressData.processedFiles++;
      const allFilesFinished =
          progressData.processedFiles >= progressData.totalFiles;
      const attachmentData = data?.data;

      if ( allFilesFinished ) {
        clearInterval( progressData.fakeInterval );
        pxSend( 'setDownloadComplete' );
      } else {
        pxSend( 'setDownloadProgress', [
          calculateProgress( progressData ),
        ] );
      }

      if ( data.success ) {
        if ( ! attachmentData._existed ) {
          wp.Uploader.queue.add( attachmentData );
        }

        if (
            mediaItems &&
            attachmentData._returnMediaItemUrl &&
            ! mediaItems.querySelector(
                '#media-item-' + attachmentData.id
            )
        ) {
          const fileObj = {
            id: attachmentData.id,
            name: attachmentData.filename,
          };
          fileQueued( fileObj );
          uploadSuccess( fileObj, attachmentData.id );
        }
      } else {
        // eslint-disable-next-line no-console
        console.error( data );
        const fileError = `${ file.fileName }: ${ attachmentData }`;
        downloadErrors.push( fileError );
        // @TODO: downloadErrors.join("\n")
        // once multiple messages are supported
        pxSend( 'showError', [ fileError ] );
      }

      if ( pxCurrentFrame ) {
        const library = pxCurrentFrame.state().get( 'library' );
        let attachment = library.get( attachmentData.id );
        if ( ! attachment ) {
          attachment = library.add( attachmentData, {
            merge: true,
            at: 0,
          } );
        }

        if ( allFilesFinished && ! downloadErrors.length ) {
          if ( pxCurrentFrame?._pixxioExclusive ) {
            pxCurrentFrame.close();
          } else {
            pxCurrentFrame?.content.mode( 'browse' );
          }
          const attElSelector = `.attachment[data-id="${ parseInt(
              attachmentData.id
          ) }"]`;
          window.setTimeout( () => {
            pxCurrentFrame.content
                .get()
                ?.$el.get( 0 )
                ?.querySelector( attElSelector )
                ?.scrollIntoView();
          }, 1 );
        } else if ( downloadErrors.length ) {
          pxCurrentFrame?.state()?.get( 'selection' )?.reset();
        }

        pxCurrentFrame
            ?.state()
            ?.get( 'selection' )
            .add( attachment, {
              merge: true,
            } );
      }
    },
  } );
   */
