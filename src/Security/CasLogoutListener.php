<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * After the firewall's logout listener kills the local session, send the user to the bare CAS
 * logout URL (global CAS logout, no service redirect back) — exactly what the old
 * `SsoLogoutSuccessHandler` → `AuthController::logoutAction` combo did with
 * `strtok($manager->getServer()->getLogoutUrl(), '?')` (security.md §11).
 */
#[AsEventListener(event: LogoutEvent::class)]
class CasLogoutListener
{
    public function __construct(private readonly PhpCasClient $casClient)
    {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $event->setResponse(new RedirectResponse($this->casClient->getLogoutUrl()));
    }
}
