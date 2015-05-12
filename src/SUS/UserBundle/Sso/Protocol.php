<?php

namespace SUS\UserBundle\Sso;

use BeSimple\SsoAuthBundle\Sso\Cas\Protocol as BaseProtocol;

use BeSimple\SsoAuthBundle\Exception\InvalidConfigurationException;
use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;
use Buzz\Client\ClientInterface;

class Protocol extends BaseProtocol
{
    protected $kernel;

    public function __construct($kernel) {
        $this->kernel = $kernel;
    }

    public function executeValidation(ClientInterface $client, BuzzRequest $request, $credentials)
    {
        $phpCas = new PhpCasValidation(new BuzzResponse(), $credentials);
        $phpCas->setKernel($this->kernel);
        return $phpCas;
    }

}
