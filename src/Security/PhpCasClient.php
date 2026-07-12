<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Thin, lazily-initialized wrapper around the phpCAS global singleton.
 *
 * Reproduces the client setup of the old `SUS\UserBundle\Sso\PhpCasValidation` (security.md §5)
 * on apereo/phpcas 1.6:
 *  - SAML 1.1 ticket validation (POST to https://<CAS_HOST>/samlValidate),
 *  - $changeSessionID = false (Symfony owns the session),
 *  - setNoCasServerValidation() behind the CAS_INSECURE_SKIP_VERIFY env toggle,
 *  - setNoClearTicketsFromUrl() (the authenticator's success handler does the redirect),
 *  - the phpCAS 1.6 $service_base_url comes from CAS_SERVICE_BASE_URL (dev:
 *    https://mmsch.uniwa.gr) — this replaces the old vendor's http→https hack.
 *
 * `handleLogoutRequests()` is intentionally NOT called: CAS single-logout never actually worked
 * in the old app (security.md §5 line 50) — status quo preserved.
 *
 * phpCAS may only be initialized once per PHP process and MUST NOT be initialized during
 * CLI/console runs; initialize() is only ever called from the CasAuthenticator and guards both.
 */
class PhpCasClient
{
    private bool $initialized = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $casHost,
        private readonly int $casPort,
        private readonly string $casUri,
        private readonly string $serviceBaseUrl,
        private readonly bool $insecureSkipVerify,
    ) {
    }

    public function initialize(): void
    {
        if ($this->initialized || \phpCAS::isInitialized()) {
            $this->initialized = true;

            return;
        }

        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            throw new \LogicException('phpCAS must not be initialized during CLI/console runs.');
        }

        // phpCAS stores its validation state in $_SESSION ($changeSessionID = false);
        // make sure the Symfony session is started first so both share the same session.
        $this->requestStack->getCurrentRequest()?->getSession()->start();

        \phpCAS::client(
            SAML_VERSION_1_1,
            $this->casHost,
            $this->casPort,
            $this->casUri,
            rtrim($this->serviceBaseUrl, '/'),
            false // do NOT let phpCAS rename the PHP session — Symfony owns it
        );

        if ($this->insecureSkipVerify) {
            // parity with the old app (curl peer verification off for /samlValidate)
            \phpCAS::setNoCasServerValidation();
        }

        // Symfony's success handler redirects after login; phpCAS must not strip ?ticket itself.
        \phpCAS::setNoClearTicketsFromUrl();

        $this->initialized = true;
    }

    /**
     * Validates the ticket on the current request (reads ?ticket from the query string itself).
     */
    public function validateTicket(): bool
    {
        $this->initialize();

        return \phpCAS::checkAuthentication();
    }

    public function getUser(): string
    {
        return \phpCAS::getUser();
    }

    /** @return array<string, mixed> SAML 1.1 attribute map from the CAS server */
    public function getAttributes(): array
    {
        return \phpCAS::getAttributes();
    }

    /**
     * CAS login URL with service = <base>/login_check — same shape as the old
     * `https://sso-01.sch.gr/login?service=https%3A%2F%2F<host>%2Flogin_check`.
     * Built without touching the phpCAS singleton so it is safe from entry points/controllers.
     */
    public function getLoginUrl(): string
    {
        return $this->getCasBaseUrl().'/login?service='.urlencode(rtrim($this->serviceBaseUrl, '/').'/login_check');
    }

    /**
     * Bare CAS logout URL — the old AuthController::logoutAction stripped the query string
     * (strtok($logoutUrl, '?')), i.e. global CAS logout with no service redirect back.
     */
    public function getLogoutUrl(): string
    {
        return $this->getCasBaseUrl().'/logout';
    }

    private function getCasBaseUrl(): string
    {
        $url = 'https://'.$this->casHost;
        if (443 !== $this->casPort) {
            $url .= ':'.$this->casPort;
        }
        if ('' !== trim($this->casUri, '/')) {
            $url .= '/'.trim($this->casUri, '/');
        }

        return $url;
    }
}
