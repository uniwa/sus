<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\PhpCasClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Auth route endpoints, preserving the old paths (security.md §11):
 *
 *  /login       — the old FOS login form was vestigial (no form_login listener existed);
 *                 per the inventory it now redirects straight to CAS.
 *  /login_check — the CAS service/callback URL. With ?ticket the CasAuthenticator intercepts
 *                 the request before this controller runs; without a ticket the old app fell
 *                 through to a dead FOS action — here we bounce to /login (i.e. to CAS).
 *  /logout      — handled entirely by the firewall's logout listener + CasLogoutListener;
 *                 the route only needs to exist so `path('logout')` works in templates
 *                 (old UI link `fos_user_security_logout`, label «Αποσύνδεση»).
 *  /debug       — the old CAS failure_path (var_dump + die) is deliberately NOT ported;
 *                 failures render templates/security/login_error.html.twig instead.
 */
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(PhpCasClient $casClient): RedirectResponse
    {
        return new RedirectResponse($casClient->getLoginUrl());
    }

    #[Route('/login_check', name: 'login_check')]
    public function loginCheck(): RedirectResponse
    {
        // only reached WITHOUT a ?ticket (the authenticator handles the ticketed case)
        return $this->redirectToRoute('login');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        throw new \LogicException('This code should never be reached — the firewall logout listener intercepts /logout.');
    }
}
