<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Port of old `SUS\AdminBundle\Controller\AuthController::debugAction` (route `debug`,
 * path /debug) — the old firewall's `failure_path`, i.e. where a failed CAS authentication
 * landed the user.
 *
 * Deliberate behavior change (flagged in the port inventory): the old action did
 * `var_dump($error->getMessage()); die();` — and fatally crashed when no error was present
 * (method call on null). This port renders the last authentication error message as a plain
 * text response instead, and degrades gracefully when there is none.
 *
 * The other two old AuthController actions (loginAction/logoutAction, BeSimple SSO glue with
 * no routes of their own) are superseded by the phpCAS authenticator in src/Security/.
 */
class AuthDebugController extends AbstractController
{
    #[Route('/debug', name: 'debug')]
    public function debug(Request $request): Response
    {
        $error = null;

        if ($request->attributes->has(SecurityRequestAttributes::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        } elseif ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->has(SecurityRequestAttributes::AUTHENTICATION_ERROR)) {
                $error = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
                $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);
            }
        }

        $message = $error instanceof \Throwable ? $error->getMessage() : 'No authentication error.';

        return new Response($message, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
