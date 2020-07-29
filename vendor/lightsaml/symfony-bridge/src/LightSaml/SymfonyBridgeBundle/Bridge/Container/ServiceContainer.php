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
use LightSaml\Validator\Model\Assertion\AssertionTimeValidatorInterface;
use LightSaml\Validator\Model\Assertion\AssertionValidatorInterface;
use LightSaml\Validator\Model\NameId\NameIdValidatorInterface;
use LightSaml\Validator\Model\Signature\SignatureValidatorInterface;

class ServiceContainer implements ServiceContainerInterface
{
    /** @var AssertionValidatorInterface */
    private $assertionValidator;

    /** @var AssertionTimeValidatorInterface */
    private $assertionTimeValidator;

    /** @var SignatureResolverInterface */
    private $signatureResolver;

    /** @var EndpointResolverInterface */
    private $endpointResolver;

    /** @var NameIdValidatorInterface */
    private $nameIdValidator;

    /** @var BindingFactoryInterface */
    private $bindingFactory;

    /** @var SignatureValidatorInterface */
    private $signatureValidator;

    /** @var CredentialResolverInterface */
    private $credentialResolver;

    /** @var SessionProcessorInterface */
    private $sessionProcessor;

    /**
     * @param AssertionValidatorInterface     $assertionValidator
     * @param AssertionTimeValidatorInterface $assertionTimeValidator
     * @param SignatureResolverInterface      $signatureResolver
     * @param EndpointResolverInterface       $endpointResolver
     * @param NameIdValidatorInterface        $nameIdValidator
     * @param BindingFactoryInterface         $bindingFactory
     * @param SignatureValidatorInterface     $signatureValidator
     * @param CredentialResolverInterface     $credentialResolver
     * @param SessionProcessorInterface       $sessionProcessor
     */
    public function __construct(
        AssertionValidatorInterface $assertionValidator,
        AssertionTimeValidatorInterface $assertionTimeValidator,
        SignatureResolverInterface $signatureResolver,
        EndpointResolverInterface $endpointResolver,
        NameIdValidatorInterface $nameIdValidator,
        BindingFactoryInterface $bindingFactory,
        SignatureValidatorInterface $signatureValidator,
        CredentialResolverInterface $credentialResolver,
        SessionProcessorInterface $sessionProcessor
    ) {
        $this->assertionValidator = $assertionValidator;
        $this->assertionTimeValidator = $assertionTimeValidator;
        $this->signatureResolver = $signatureResolver;
        $this->endpointResolver = $endpointResolver;
        $this->nameIdValidator = $nameIdValidator;
        $this->bindingFactory = $bindingFactory;
        $this->signatureValidator = $signatureValidator;
        $this->credentialResolver = $credentialResolver;
        $this->sessionProcessor = $sessionProcessor;
    }

    /**
     * @return AssertionValidatorInterface
     */
    public function getAssertionValidator()
    {
        return $this->assertionValidator;
    }

    /**
     * @return AssertionTimeValidatorInterface
     */
    public function getAssertionTimeValidator()
    {
        return $this->assertionTimeValidator;
    }

    /**
     * @return SignatureResolverInterface
     */
    public function getSignatureResolver()
    {
        return $this->signatureResolver;
    }

    /**
     * @return EndpointResolverInterface
     */
    public function getEndpointResolver()
    {
        return $this->endpointResolver;
    }

    /**
     * @return NameIdValidatorInterface
     */
    public function getNameIdValidator()
    {
        return $this->nameIdValidator;
    }

    /**
     * @return BindingFactoryInterface
     */
    public function getBindingFactory()
    {
        return $this->bindingFactory;
    }

    /**
     * @return SignatureValidatorInterface
     */
    public function getSignatureValidator()
    {
        return $this->signatureValidator;
    }

    /**
     * @return CredentialResolverInterface
     */
    public function getCredentialResolver()
    {
        return $this->credentialResolver;
    }

    /**
     * @return \LightSaml\Resolver\Logout\LogoutSessionResolverInterface
     */
    public function getLogoutSessionResolver()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @return SessionProcessorInterface
     */
    public function getSessionProcessor()
    {
        return $this->sessionProcessor;
    }
}
