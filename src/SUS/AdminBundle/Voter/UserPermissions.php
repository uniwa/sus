<?php
namespace SUS\AdminBundle\Voter;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UserPermissions {
    
    private $container;
    private $security_context;
       
    public function __construct(Container $container,SecurityContextInterface $security_context) {
        $this->container = $container;
        $this->security_context = $security_context;
    }

    public function userRole(){
        
//        $attributes = $this->security_context->getToken()->getAttributes();
        $username = $this->security_context->getToken()->getUsername();
        
        $mm_id = self::checkPrincipal($username);
        
        return $mm_id;
    }
    
    public function checkPrincipal($username){
      
        //DEPRECATED
        //check if user has Principal Role
        //
        //       $school_principal = $found = null;
        //       $attributes = $this->security_context->getToken()->getAttributes();
        //       $attribute = $attributes["sso:validation"] ;
        //       
        //        if (array_key_exists('gsnuserroleon;school-principal',$attribute)) {
        //            $school_principal = $attribute['gsnuserroleon;school-principal'];
        //        }  
        //   
        //        if ($school_principal!=null && array_key_exists('edupersonorgunitdn:gsnregistrycode:extended',$attribute) ) {
        //            foreach ($attribute['edupersonorgunitdn:gsnregistrycode:extended'] as $data) {
        //                $url_string = explode(';', $data);
        //                if (in_array($school_principal,$url_string)){
        //                       $found = $url_string[1];
        //                }
        //            }
        //        }

        //ldap connection    
        $syncLdapOptions = $this->container->getParameter('syncLdapOptions');
        
        //ldap search with uid
        $ldap = new \Zend\Ldap\Ldap($syncLdapOptions);
        $uidResult = $ldap->search('(uid='.$username.')', 'ou=people,dc=sch,dc=gr', \Zend\Ldap\Ldap::SEARCH_SCOPE_SUB);
        $uidRows = iterator_to_array($uidResult);

        if ($uidResult->count() == 1) {
            
            if (array_key_exists('physicaldeliveryofficename',$uidRows[0])!= true ) return null;
            $results = array (  "uid" => $uidRows[0]["uid"][0],
                                "physicaldeliveryofficename" => $uidRows[0]["physicaldeliveryofficename"][0],
                                "dn" => $uidRows[0]["dn"],
                                "l" => $uidRows[0]["l"][0]
                                //"labeledUri" => $uidRows[0]["labeleduri"][0]
                            );
            
        } else if ($uidResult->count() == 0) 
            throw new \Exception("Δεν βρέθηκαν δεδομένα για το συγκεκριμένο λογαριασμό στον LDAP με βάση το Uid του λογαριασμού.");
        else 
            throw new \Exception("Βρέθηκαν πολλαπλοί λογαριασμοί στον LDAP με το ίδιο Uid.");

        //check if physicaldeliveryofficename = EPISHMOS LOGARIASMOS
        if ($uidRows[0]["physicaldeliveryofficename"][0] !== 'ΕΠΙΣΗΜΟΣ ΛΟΓΑΡΙΑΣΜΟΣ'){
           return null;
        }
      
        //ldap query found unit
        //$l = explode(',','ou=10gym-perist,ou=att-g,ou=units,dc=sch,dc=gr');
        $l = explode(',',$uidRows[0]["l"][0]);
        $ldap = new \Zend\Ldap\Ldap($syncLdapOptions);
        $baseDn =  $l[1].',ou=units,dc=sch,dc=gr';

        $lResult = $ldap->search('('.$l[0].')',$baseDn, \Zend\Ldap\Ldap::SEARCH_SCOPE_SUB);
        $lRows = iterator_to_array($lResult);
        $mm_id = ( array_key_exists('gsnregistrycode',$lRows[0]) ) ? $lRows[0]["gsnregistrycode"][0] : null;   
        return $mm_id;
        
    }
    
}
?>
