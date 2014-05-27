<?php
namespace SUS\UserBundle\Model;

use FOS\UserBundle\Security\UserProvider as BaseUserProvider;
use BeSimple\SsoAuthBundle\Security\Core\User\UserFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider extends BaseUserProvider implements UserFactoryInterface
{
    public function createUser($username, array $roles, array $attributes)
    {
        try {
            $user = $this->userManager->createUser();
            $user->setEmail($username.'@'.$username.'.com');
            $user->setPlainPassword(md5(rand(0, 10000)));
            $user->setPassword(md5(rand(0, 10000)));
            $user->setUsername($username);
            $user->setRoles($roles);
            $user->setEnabled(true);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must create an UserInterface object.');
            }
        } catch (\Exception $repositoryProblem) {
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
        }

        return $user;
    }
}