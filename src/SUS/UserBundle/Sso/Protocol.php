<?php

namespace SUS\UserBundle\Sso;

use BeSimple\SsoAuthBundle\Sso\Cas\Protocol as BaseProtocol;

use BeSimple\SsoAuthBundle\Exception\InvalidConfigurationException;
use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;
use Buzz\Client\ClientInterface;

class Protocol extends BaseProtocol
{
    protected $kernel ,$container;

public function __construct($kernel ,$container) {
        $this->kernel = $kernel;
       $this->container = $container;
        parent::__construct();
    }
    
    public function executeValidation(ClientInterface $client, BuzzRequest $request, $credentials)
    {
        $phpCas = new PhpCasValidation(new BuzzResponse(), $credentials);
        $phpCas->setKernel($this->kernel);
        $phpCas->setContainer($this->container);
        return $phpCas;
    }

}
