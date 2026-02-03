<?php
namespace App\EventListener;

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    /**
     * @param LogoutEvent $logoutEvent
     * @return void
     */
    #[NoReturn]
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        if(session_id()) session_destroy();

        //$logoutEvent->setResponse(new RedirectResponse('/login', Response::HTTP_TEMPORARY_REDIRECT));
    }
}