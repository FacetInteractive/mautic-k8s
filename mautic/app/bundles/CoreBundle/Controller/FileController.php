<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileController.
 */
class FileController extends AjaxController
{
    protected $imageMimes = [
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
    ];

    protected $response = [];

    protected $statusCode = Response::HTTP_OK;

    /**
     * Uploads a file.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uploadAction()
    {
        $mediaDir = $this->getMediaAbsolutePath();
        if (!isset($this->response['error'])) {
            foreach ($this->request->files as $file) {
                if (in_array($file->getMimeType(), $this->imageMimes)) {
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move($mediaDir, $fileName);
                    $this->response['link'] = $this->getMediaUrl().'/'.$fileName;
                } else {
                    $this->response['error'] = 'The uploaded image does not have an allowed mime type';
                }
            }
        }

        return $this->sendJsonResponse($this->response, $this->statusCode);
    }

    /**
     * List the files in /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction()
    {
        $fnames = scandir($this->getMediaAbsolutePath());

        if ($fnames) {
            foreach ($fnames as $name) {
                $imagePath = $this->getMediaAbsolutePath().'/'.$name;
                $imageUrl  = $this->getMediaUrl().'/'.$name;
                if (!is_dir($name) && in_array(mime_content_type($imagePath), $this->imageMimes)) {
                    $this->response[] = [
                        'url'   => $imageUrl,
                        'thumb' => $imageUrl,
                        'name'  => $name,
                    ];
                }
            }
        } else {
            $this->response['error'] = 'Images folder does not exist!';
        }

        return $this->sendJsonResponse($this->response, $this->statusCode, false);
    }

    /**
     * Delete a file from /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction()
    {
        $src       = InputHelper::clean($this->request->request->get('src'));
        $response  = ['deleted' => false];
        $imagePath = $this->getMediaAbsolutePath().'/'.basename($src);

        if (!file_exists($imagePath)) {
            $this->response['error'] = 'File does not exist';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        } elseif (!is_writable($imagePath)) {
            $this->response['error'] = 'File is not writable';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        } else {
            unlink($imagePath);
            $this->response['deleted'] = true;
        }

        return $this->sendJsonResponse($this->response, $this->statusCode);
    }

    /**
     * Get the Media directory full file system path.
     *
     * @return string
     */
    public function getMediaAbsolutePath()
    {
        $mediaDir = realpath($this->get('mautic.helper.paths')->getSystemPath('images', true));

        if ($mediaDir === false) {
            $this->response['error'] = 'Media dir does not exist';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if (is_writable($mediaDir) === false) {
            $this->response['error'] = 'Media dir is not writable';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $mediaDir;
    }

    /**
     * Get the Media directory full file system path.
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->request->getScheme().'://'
            .$this->request->getHttpHost()
            .$this->request->getBasePath().'/'
            .$this->coreParametersHelper->getParameter('image_path');
    }
}
