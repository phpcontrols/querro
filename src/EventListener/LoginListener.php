<?php
namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use App\Entity\User;

class LoginListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        // Get the User entity.
        $user = $event->getAuthenticationToken()->getUser();

        $_SESSION['AccountId'] = $user->getAccountId();
        $_SESSION['UserId'] = $user->getId();
        $_SESSION['Username'] = $user->getUsername();

        // // Update your field here.
        // $user->setLastLogin(new \DateTime());

        // // Persist the data to database.
        // $this->em->persist($user);
        // $this->em->flush();
    }
}