<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\HttpFoundation\EventListener;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Pass the Request instance to {@link \Symfony\Component\Form\Form::handleRequest()} instead.
 */
class BindRequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // High priority in order to supersede other listeners
        return array(FormEvents::PRE_SUBMIT => array('preBind', 128));
    }

    public function preBind(FormEvent $event)
    {
        $form = $event->getForm();

        /* @var Request $request */
        $request = $event->getData();

        // Only proceed if we actually deal with a Request
        if (!$request instanceof Request) {
            return;
        }

        @trigger_error('The '.__CLASS__.' class is deprecated since Symfony 2.3 and will be removed in 3.0. Pass the Request instance to the \Symfony\Component\Form\Form::handleRequest() method instead.', E_USER_DEPRECATED);

        $name = $form->getConfig()->getName();
        $default = $form->getConfig()->getCompound() ? array() : null;

        // For request methods that must not have a request body we fetch data
        // from the query string. Otherwise we look for data in the request body.
        switch ($request->getMethod()) {
            case 'GET':
            case 'HEAD':
            case 'TRACE':
                $data = '' === $name
                    ? $request->query->all()
                    : $request->query->get($name, $default);

                break;

            default:
                if ('' === $name) {
                    // Form bound without name
                    $params = $request->request->all();
                    $files = $request->files->all();
                } else {
                    $params = $request->request->get($name, $default);
                    $files = $request->files->get($name, $default);
                }

                if (is_array($params) && is_array($files)) {
                    $data = array_replace_recursive($params, $files);
                } else {
                    $data = $params ?: $files;
                }

                break;
        }

        $event->setData($data);
    }
}
