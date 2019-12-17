<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class UploadSubscriber.
 */
class UploadSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AssetModel
     */
    protected $assetModel;

    /**
     * @param TranslatorInterface $translator
     */
    protected $translator;

    /**
     * UploadSubscriber constructor.
     *
     * @param TranslatorInterface  $translator
     * @param CoreParametersHelper $coreParametersHelper
     * @param AssetModel           $assetModel
     */
    public function __construct(TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper, AssetModel $assetModel)
    {
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->assetModel           = $assetModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            UploadEvents::POST_UPLOAD => ['onPostUpload', 0],
            UploadEvents::VALIDATION  => ['onUploadValidation', 0],
        ];
    }

    /**
     * Moves upladed file to temporary directory where it can be found later
     * and all uploaded files in there cleared. Also sets file name to the response.
     *
     * @param PostUploadEvent $event
     */
    public function onPostUpload(PostUploadEvent $event)
    {
        $request   = $event->getRequest()->request;
        $response  = $event->getResponse();
        $tempId    = $request->get('tempId');
        $file      = $event->getFile();
        $config    = $event->getConfig();
        $uploadDir = $config['storage']['directory'];
        $tmpDir    = $uploadDir.'/tmp/'.$tempId;

        // Move uploaded file to temporary folder
        $file->move($tmpDir);

        // Set resposnse data
        $response['state']       = 1;
        $response['tmpFileName'] = $file->getBasename();
    }

    /**
     * Validates file before upload.
     *
     * @param ValidationEvent $event
     */
    public function onUploadValidation(ValidationEvent $event)
    {
        $file       = $event->getFile();
        $extensions = $this->coreParametersHelper->getParameter('allowed_extensions');
        $maxSize    = $this->assetModel->getMaxUploadSize('B');

        if ($file !== null) {
            if ($file->getSize() > $maxSize) {
                $message = $this->translator->trans('mautic.asset.asset.error.file.size', [
                    '%fileSize%' => round($file->getSize() / 1048576, 2),
                    '%maxSize%'  => round($maxSize / 1048576, 2),
                ], 'validators');
                throw new ValidationException($message);
            }

            if (!in_array(strtolower($file->getExtension()), array_map('strtolower', $extensions))) {
                $message = $this->translator->trans('mautic.asset.asset.error.file.extension', [
                    '%fileExtension%' => $file->getExtension(),
                    '%extensions%'    => implode(', ', $extensions),
                ], 'validators');
                throw new ValidationException($message);
            }
        }
    }
}
