<?php

return [
    'pixxio_files' => [
        'path' => '/pixxio/files',
        'target' => \Pixxio\PixxioExtension\Controller\FilesController::class . '::selectedFilesAction'
    ],
];