<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\Bridge\Container;

use LightSaml\Binding\BindingFactoryInterface;
use LightSaml\Build\Container\ServiceContainerInterface;
use LightSaml\Resolver\Credential\CredentialResolverInterface;
use LightSaml\Resolver\Endpoint\EndpointResolverInterface;
use LightSaml\Resolver\Session\SessionProcessorInterface;
use LightSaml\Resolver\Signature\SignatureResolverInterface;
use LightSaml\Validator\Model\Assertion\AssertionTimeValidator;
use LightSaml\Validator\Model\Assertion\AssertionValidatorInterface;
use LightSaml\Validator\Model\NameId\NameIdValidatorInterface;
use LightSaml\Validator\Model\Signature\SignatureValidatorInterface;

class ServiceContainer extends AbstractContainer implements ServiceContainerInterface
{
    /**
     * @return AssertionValidatorInterface
     */
    public function getAssertionValidator()
    {
        return $this->container->get('lightsaml.service.assertion_validator');
    }

    /**
     * @return AssertionTimeValidator
     */
    public function getAssertionTimeValidator()
    {
        return $this->container->get('lightsaml.service.assertion_time_validator');
    }

    /**
     * @return SignatureResolverInterface
     */
    public function getSignatureResolver()
    {
        return $this->container->get('lightsaml.service.signature_resolver');
    }

    /**
     * @return EndpointResolverInterface
     */
    public function getEndpointResolver()
    {
        return $this->container->get('lightsaml.service.endpoint_resolver');
    }

    /**
     * @return NameIdValidatorInterface
     */
    public function getNameIdValidator()
    {
        return $this->container->get('lightsaml.service.name_id_validator');
    }

    /**
     * @return BindingFactoryInterface
     */
    public function getBindingFactory()
    {
        return $this->container->get('lightsaml.service.binding_factory');
    }

    /**
     * @return SignatureValidatorInterface
     */
    public function getSignatureValidator()
    {
        return $this->container->get('lightsaml.service.signature_validator');
    }

    /**
     * @return CredentialResolverInterface
     */
    public function getCredentialResolver()
    {
        return $this->container->get('lightsaml.service.credential_resolver');
    }

    /**
     * @return \LightSaml\Resolver\Logout\LogoutSessionResolverInterface
     */
    public function getLogoutSessionResolver()
    {
        return $this->container->get('lightsaml.service.logout_resolver');
    }

    /**
     * @return SessionProcessorInterface
     */
    public function getSessionProcessor()
    {
        return $this->container->get('lightsaml.service.session_processor');
    }
}
