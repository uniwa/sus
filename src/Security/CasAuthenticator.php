<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Twig\Environment;

/**
 * CAS SSO authenticator — replaces the whole old `trusted_sso` stack (BeSimple SsoAuthBundle
 * listener/provider/entry-point + `SUS\UserBundle\Sso\PhpCasValidation`/`Protocol`), see
 * docs/port-inventory/security.md §3, §5, §15.
 *
 * Flow (identical to the old app):
 *  1. Anonymous request to /admin/… → start() redirects to
 *     https://<CAS_HOST>/login?service=<CAS_SERVICE_BASE_URL>/login_check
 *  2. CAS redirects back to /login_check?ticket=ST-… → supports()/authenticate()
 *  3. phpCAS SAML-1.1-validates the ticket against /samlValidate
 *  4. Acceptance gates (old effective rule preserved, including the "leaky" third case):
 *     uid in sus_roles.yaml (→ ROLE_USER1/2/4), or LDAP official school account
 *     (→ ROLE_USER3), or merely present in LDAP (no role — every admin action is then denied
 *     by the voter, exactly as before). Unknown/ambiguous LDAP uid → auth failure with the
 *     original Greek message.
 *  5. User row loaded-or-created + resolved role persisted (CasUserProvisioner).
 *  6. Redirect ALWAYS to the Unit admin list (old `default_target_path:
 *     admin_sus_site_unit_list` + `always_use_default_target_path: true`).
 *
 * CAS attributes are kept on the token under `sso:validation` — same key the old SsoToken used —
 * although the only attribute any live code ever read is `uid` (== user identifier).
 */
class CasAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    /** The CAS "service" callback path — MUST stay /login_check (security.md §11). */
    public const CHECK_PATH = '/login_check';

    /** Old post-login target: route `admin_sus_site_unit_list` = this path. */
    private const TARGET_PATH = '/admin/sus/site/unit/list';
    private const TARGET_ROUTES = ['admin_sus_site_unit_list', 'admin_app_unit_list'];

    public function __construct(
        private readonly PhpCasClient $casClient,
        private readonly RolesRegistry $rolesRegistry,
        private readonly UserPermissions $userPermissions,
        private readonly CasUserProvisioner $userProvisioner,
        private readonly RouterInterface $router,
        private readonly Environment $twig,
    ) {
    }

    /**
     * Entry point: any access-denied-for-anonymous request → redirect to the CAS login
     * (old TrustedSsoAuthenticationEntryPoint + AuthController::loginAction, forceAuthentication
     * semantics — the user cannot proceed without authenticating at sso.sch.gr).
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->casClient->getLoginUrl());
    }

    /**
     * Same trigger as the old TrustedSsoAuthenticationListener: only the check path, and only
     * when CAS appended a ticket (without one the request falls through to the normal router).
     */
    public function supports(Request $request): ?bool
    {
        return self::CHECK_PATH === $request->getPathInfo() && $request->query->has('ticket');
    }

    public function authenticate(Request $request): Passport
    {
        // phpCAS re-reads the ticket from the query string itself (as in the old app)
        if (!$this->casClient->validateTicket()) {
            // old BeSimple SsoAuthenticationProvider message, verbatim
            throw new CustomUserMessageAuthenticationException('Authentication has not been validated by SSO provider.');
        }

        $username = $this->casClient->getUser();
        $attributes = $this->casClient->getAttributes();

        // acceptance gates + role resolution (security.md §5 lines 62-73, §15.3/§15.4)
        $resolvedRole = $this->rolesRegistry->getRole($username);
        if (null === $resolvedRole) {
            try {
                $mmId = $this->userPermissions->checkPrincipal($username);
            } catch (\Exception $e) {
                // uid missing/ambiguous in LDAP — old app failed authentication here too
                // (uncaught exception); Greek message preserved for the failure page
                throw new CustomUserMessageAuthenticationException($e->getMessage(), [], 0, $e);
            }
            if (null !== $mmId) {
                $resolvedRole = 'ROLE_USER3';
            }
            // else: "leaky" pass-through preserved — valid CAS + non-official LDAP account gets
            // a session with no role and is denied by AdminAclVoter on every admin action.
        }

        $passport = new SelfValidatingPassport(
            new UserBadge($username, fn (string $identifier) => $this->userProvisioner->provision($identifier, $resolvedRole))
        );
        $passport->setAttribute('sso:validation', $attributes + ['uid' => $username]);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $token = parent::createToken($passport, $firewallName);
        // same token attribute key the old SsoToken used
        $token->setAttribute('sso:validation', $passport->getAttribute('sso:validation') ?? []);

        return $token;
    }

    /**
     * Old behavior: ALWAYS redirect to the Sonata Unit list after login
     * (`default_target_path: admin_sus_site_unit_list`, `always_use_default_target_path: true`).
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        foreach (self::TARGET_ROUTES as $route) {
            try {
                return new RedirectResponse($this->router->generate($route));
            } catch (RouteNotFoundException) {
                // admin area may still be under construction / use a different route name
            }
        }

        return new RedirectResponse(self::TARGET_PATH);
    }

    /**
     * Replaces the old `failure_path: /debug` (a var_dump+die leftover — intentionally not
     * ported, security.md §11) with a proper error page carrying the message.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception instanceof CustomUserMessageAuthenticationException
            ? $exception->getMessage()
            : $exception->getMessageKey();

        return new Response(
            $this->twig->render('security/login_error.html.twig', ['error' => $message]),
            Response::HTTP_FORBIDDEN
        );
    }
}
