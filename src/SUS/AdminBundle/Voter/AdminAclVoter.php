<?php
namespace SUS\AdminBundle\Voter;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use SUS\UserBundle\Loader\YamlUserLoader;

class AdminAclVoter extends AclVoter
{
    private $objectIdentityRetrievalStrategy;
    private $container,$kernel;
      
    public function __construct(AclProviderInterface $aclProvider, ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy, SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy, PermissionMapInterface $permissionMap, LoggerInterface $logger = null, $allowIfObjectIdentityUnavailable = true)
    {
        $this->objectIdentityRetrievalStrategy = $oidRetrievalStrategy;
        parent::__construct($aclProvider, $oidRetrievalStrategy, $sidRetrievalStrategy, $permissionMap, $logger, $allowIfObjectIdentityUnavailable);
    }
    
    public function setContainer($container) {
        $this->container = $container;
    }
    
    public function setKernel($kernel) {
        $this->kernel = $kernel;
    }
        
    public function supportsClass($class) {
        return parent::supportsClass($class);
    }
           
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        
        // Login
        if (null === $object) {
            return self::ACCESS_GRANTED;
        } elseif ($object instanceof FieldVote) {
            $field = $object->getField();
            $object = $object->getDomainObject();
        } else {
            $field = null;
        }

        if ($object instanceof ObjectIdentityInterface) {
            $oid = $object;
        } elseif (null === $oid = $this->objectIdentityRetrievalStrategy->getObjectIdentity($object)) {
            return self::ACCESS_GRANTED;
        }
    
        
 //CHECK username(uid) for authorization at SUS
 //WRITE in USER table, role of user
        $sus_user = $token->getUser();       
        $sso_user = $token->getAttributes();
        $username = $sso_user["sso:validation"]["uid"]; 
    
        //check user uid with roles.yml file
        //check for USER1,USER2,USER4
        $path = $this->kernel->locateResource('@SUSUserBundle').'/Resources/config/roles.yml';
        $userRoles = new YamlUserLoader();   
        $roles = $userRoles->load($path);
        if (array_key_exists($username, $roles)){
            $role_by_system = $roles[$username]['role'];
            $sus_user->setRoles(array($role_by_system));
            $this->em = $this->container->get('doctrine')->getManager();
            $this->em->persist($sus_user);
            $this->em->flush($sus_user);
        }

        //check for USER3
        if (array_key_exists($username, $roles)!=true){
            $mm_id = $this->container->get('security.user.permissions')->checkPrincipal($username);
            if ($mm_id == null) {
                //$mm_id = '1019474';
                return self::ACCESS_DENIED;
            } else {
                $sus_user->setRoles(array('ROLE_USER3'));
                $this->em = $this->container->get('doctrine')->getManager();
                $this->em->persist($sus_user);
                $this->em->flush($sus_user);
            }
        }

        
//CHECK if user has role
//CHECK user attributes for permissions
        if(($user = $token->getUser()) instanceof UserInterface) {

                if ($user->hasRole('ROLE_USER1')) {
                   foreach ($attributes as $attribute) {
                        if (in_array( $attribute, $roles[$username]['permissions'])){
                            return self::ACCESS_GRANTED;
                        }
                    } 
                } elseif ($user->hasRole('ROLE_USER2')) {
                    foreach ($attributes as $attribute) {
                        if (in_array( $attribute, $roles[$username]['permissions'])){
                            return self::ACCESS_GRANTED;
                        }
                    } 
                } elseif ($user->hasRole('ROLE_USER3')) {
                   foreach ($attributes as $attribute) {
                        if (in_array( $attribute, array('VIEW','LIST','EDIT') )){
                            return self::ACCESS_GRANTED;
                        }
                    } 
                } elseif ($user->hasRole('ROLE_USER4')) {
                   foreach ($attributes as $attribute) {
                        if (in_array( $attribute, $roles[$username]['permissions'])){
                            return self::ACCESS_GRANTED;
                        }
                    } 
                } else {
                    return self::ACCESS_DENIED;
                }            
            }
        
        return self::ACCESS_DENIED;
    }
}
