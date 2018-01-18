<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use D2EM, Exception;

use Entities\{
    PatchPanelPort  as PatchPanelPortEntity,
    Customer        as CustomerEntity
};

/**
 * CustomerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Customer extends EntityRepository
{
    /**
     * DQL for selecting customers that are current in terms of `datejoin` and `dateleave`
     *
     * @var string DQL for selecting customers that are current in terms of `datejoin` and `dateleave`
     */
    const DQL_CUST_CURRENT = "c.datejoin <= CURRENT_DATE() AND ( c.dateleave IS NULL OR c.dateleave = '0000-00-00' OR c.dateleave >= CURRENT_DATE() )";
    
    /**
     * DQL for selecting customers that are active (i.e. not suspended)
     *
     * @var string DQL for selecting customers that are active (i.e. not suspended)
     */
    const DQL_CUST_ACTIVE = "c.status IN ( 1, 2 )";
    
    /**
     * DQL for selecting all customers except for internal / dummy customers
     *
     * @var string DQL for selecting all customers except for internal / dummy customers
     */
    const DQL_CUST_EXTERNAL = "c.type != 3";
    
    /**
     * DQL for selecting all trafficing customers
     *
     * @var string DQL for selecting all trafficing customers
     */
    const DQL_CUST_TRAFFICING = "c.type != 2";
    
    /**
     * DQL for selecting all "connected" customers
     *
     * @var string DQL for selecting all "connected" customers
     */
    const DQL_CUST_CONNECTED = "c.status = 1";
    
    
    /**
     * Utility function to provide a count of different customer types as `type => count`
     * where type is as defined in Entities\Customer::$CUST_TYPES_TEXT
     *
     * @return array Number of customers of each customer type as `[type] => count`
     */
    public function getTypeCounts()
    {
        $atypes = $this->getEntityManager()->createQuery(
            "SELECT c.type AS ctype, COUNT( c.type ) AS cnt FROM Entities\\Customer c
                WHERE " . self::DQL_CUST_CURRENT . " AND " . self::DQL_CUST_ACTIVE . "
                GROUP BY c.type"
        )->getArrayResult();
        
        $types = [];
        foreach( $atypes as $t )
            $types[ $t['ctype'] ] = $t['cnt'];
    
        return $types;
    }
    
    
    /**
     * Utility function to provide a array of all active and current customers.
     *
     * @param bool $asArray If `true`, return an associative array, else an array of Customer objects
     * @param bool $trafficing If `true`, only include trafficing customers (i.e. no associates)
     * @param bool $externalOnly If `true`, only include external customers (i.e. no internal types)
     * @param \Entities\IXP $ixp Limit to a specific IXP
     * @return array
     */
    public function getCurrentActive( $asArray = false, $trafficing = false, $externalOnly = false, $ixp = false )
    {
        $dql = "SELECT c FROM \\Entities\\Customer c
                WHERE " . self::DQL_CUST_CURRENT . " AND " . self::DQL_CUST_ACTIVE;

        if( $trafficing )
            $dql .= " AND " . self::DQL_CUST_TRAFFICING . " AND " . self::DQL_CUST_CONNECTED;
        
        if( $externalOnly )
            $dql .= " AND " . self::DQL_CUST_EXTERNAL;

        if( $ixp !== false )
            $dql .= " AND :ixp MEMBER OF c.IXPs";

        $dql .= " ORDER BY c.name ASC";
        
        $custs = $this->getEntityManager()->createQuery( $dql );

        if( $ixp !== false )
            $custs->setParameter( 'ixp', $ixp );
        
        return $asArray ? $custs->getArrayResult() : $custs->getResult();
    }
    
    /**
     * Utility function to provide a array of all members connected to the exchange (including at 
     * least one physical interface with status 'CONNECTED').
     *
     * @param bool $asArray If `true`, return an associative array, else an array of Customer objects
     * @param bool $externalOnly If `true`, only include external customers (i.e. no internal types)
     * @param \Entities\IXP $ixp Limit to a specific IXP
     * @return array
     */
    public function getConnected( $asArray = false, $externalOnly = false, $ixp = false )
    {
        $dql = "SELECT c FROM \\Entities\\Customer c
                    LEFT JOIN c.VirtualInterfaces vi
                    LEFT JOIN vi.PhysicalInterfaces pi
                WHERE " . self::DQL_CUST_CURRENT . " AND " . self::DQL_CUST_TRAFFICING . " 
                    AND pi.status = " . \Entities\PhysicalInterface::STATUS_CONNECTED;
        
        if( $externalOnly )
            $dql .= " AND " . self::DQL_CUST_EXTERNAL;

        if( $ixp !== false )
            $dql .= " AND :ixp MEMBER OF c.IXPs";

        $dql .= " ORDER BY c.name ASC";
        
        $custs = $this->getEntityManager()->createQuery( $dql );

        if( $ixp !== false )
            $custs->setParameter( 'ixp', $ixp );
        
        return $asArray ? $custs->getArrayResult() : $custs->getResult();
    }
    
    /**
     * Takes an array of \Entities\Customer and filters them for a given infrastructure.
     *
     * Often used by passing the return fo `getCurrentActive()`
     *
     * @param \Entities\Customer[] $customers
     * @param \Entities\Infrastructure $infra
     * @return \Entities\Customer[]
     */
    public function filterForInfrastructure( $customers, $infra )
    {
        $filtered = [];
        
        foreach( $customers as $c )
        {
            foreach( $c->getVirtualInterfaces() as $vi )
            {
                foreach( $vi->getPhysicalInterfaces() as $pi )
                {
                    if( $pi->getSwitchport()->getSwitcher()->getInfrastructure() == $infra )
                    {
                        $filtered[] = $c;
                        continue 3;
                    }
                }
            }
        }
        
        return $filtered;
    }
    
    
    /**
     * Return an array of all customer names where the array key is the customer id.
     *
     * @param bool $activeOnly If true, only return active / current customers
     * @return array An array of all customer names with the customer id as the key.
     */
    public function getNames( $activeOnly = false )
    {
        $acusts = $this->getEntityManager()->createQuery(
            "SELECT c.id AS id, c.name AS name "
                . "FROM Entities\\Customer c "
                . ( $activeOnly ? "WHERE " . self::DQL_CUST_CURRENT : '' ) 
                . "ORDER BY name ASC"
        )->getResult();
        
        $customers = [];
        foreach( $acusts as $c )
            $customers[ $c['id'] ] = $c['name'];
        
        return $customers;
    }

    /**
     * Return an array of all customers who are not related with a given IXP.
     *
     * @param \Entities\IXP $ixp IXP for filtering results
     * @return \Entities\Customerp[ An array of customers
     */
    public function getNamesNotAssignedToIXP( $ixp )
    {
        return $this->getEntityManager()->createQuery(
                "SELECT c
                    FROM Entities\\Customer c
                    WHERE ?1 NOT MEMBER OF c.IXPs
                    ORDER BY c.name ASC" )
            ->setParameter( 1, $ixp )
            ->getResult();
    }

    /**
     * Return an array of all reseller names where the array key is the customer id.
     *
     * @return array An array of all reseller names with the customer id as the key.
     */
    public function getResellerNames()
    {
        $acusts = $this->getEntityManager()->createQuery(
            "SELECT c.id AS id, c.name AS name FROM Entities\\Customer c WHERE c.isReseller = 1 ORDER BY c.name ASC"
        )->getResult();
        
        $customers = [];
        foreach( $acusts as $c )
            $customers[ $c['id'] ] = $c['name'];
        
        return $customers;
    }

    /**
     * Return an array of all resold customers names where the array key is the customer id.
     *
     * @param int $id Customers id to get resold customer names list
     * @return array An array of all reseller names with the customer id as the key.
     */
    public function getResoldCustomerNames( $custid = false )
    {
        $query = "SELECT c.id AS id, c.name AS name FROM Entities\\Customer c WHERE c.Reseller IS NOT NULL";
        if( $custid )
            $query .= " AND c.Reseller = " . $custid;
        $query .= " ORDER BY c.name ASC";
        
        $acusts = $this->getEntityManager()
                    ->createQuery( $query )
                    ->getResult();
        
        $customers = [];
        foreach( $acusts as $c )
            $customers[ $c['id'] ] = $c['name'];
        
        return $customers;
    }
    
    /**
     * Return an array of the must recent customers (who are current,
     * external, active and trafficing).
     *
     * @return array An array of all customer names with the customer id as the key.
     */
    public function getRecent()
    {
        return $this->getEntityManager()->createQuery(
                "SELECT c
                 FROM \\Entities\\Customer c
                     LEFT JOIN c.VirtualInterfaces vi
                     LEFT JOIN vi.PhysicalInterfaces pi
                 WHERE " . self::DQL_CUST_CURRENT . " AND " . self::DQL_CUST_ACTIVE . "
                     AND " . self::DQL_CUST_EXTERNAL . " AND " . self::DQL_CUST_TRAFFICING . "
                     AND pi.status = " . \Entities\PhysicalInterface::STATUS_CONNECTED . "
                ORDER BY c.datejoin DESC"
            )
            ->useResultCache( true, 3600 )
            ->getResult();
    }
    
    /**
     * Return an array of the customer's peers as listed in the `PeeringManager`
     * table.
     *
     * @param $cid int The customer ID
     * @return array An array of all the customer's PeeringManager entries
     */
    public function getPeers( $cid )
    {
        $tmpPeers = $this->getEntityManager()->createQuery(
            "SELECT pm.id AS id, c.id AS custid, p.id AS peerid,
                pm.email_last_sent AS email_last_sent, pm.emails_sent AS emails_sent,
                pm.peered AS peered, pm.rejected AS rejected, pm.notes AS notes,
                pm.created AS created, pm.updated AS updated
        
             FROM \\Entities\\PeeringManager pm
                 LEFT JOIN pm.Customer c
                 LEFT JOIN pm.Peer p
        
             WHERE c.id = ?1"
        )
        ->setParameter( 1, $cid )
        ->getArrayResult();

        $peers = [];
        foreach( $tmpPeers as $p )
            $peers[ $p['peerid'] ] = $p;
        
        return $peers;
    }
    
    
    /**
     * Utility function to load all customers suitable for inclusion in the peering manager
     *
     */
    public function getForPeeringManager()
    {
        $customers = $this->getEntityManager()->createQuery(
                "SELECT c
        
                 FROM \\Entities\\Customer c

                 WHERE " . self::DQL_CUST_ACTIVE . " AND " . self::DQL_CUST_CURRENT . "
                     AND " . self::DQL_CUST_EXTERNAL . " AND " . self::DQL_CUST_TRAFFICING . "

                ORDER BY c.name ASC"
        
            )->getResult();
        
    
        $custs = array();
    
        foreach( $customers as $c )
        {
            $custs[ $c->getAutsys() ] = [];
    
            $custs[ $c->getAutsys() ]['id']            = $c->getId();
            $custs[ $c->getAutsys() ]['name']          = $c->getName();
            $custs[ $c->getAutsys() ]['shortname']     = $c->getShortname();
            $custs[ $c->getAutsys() ]['autsys']        = $c->getAutsys();
            $custs[ $c->getAutsys() ]['maxprefixes']   = $c->getMaxprefixes();
            $custs[ $c->getAutsys() ]['peeringemail']  = $c->getPeeringemail();
            $custs[ $c->getAutsys() ]['peeringpolicy'] = $c->getPeeringpolicy();
    
            $custs[ $c->getAutsys() ]['vlaninterfaces'] = array();
    
            foreach( $c->getVirtualInterfaces() as $vi )
            {
                foreach( $vi->getVlanInterfaces() as $vli )
                {
                    if( !isset( $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ] ) )
                    {
                        $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ] = [];
                        $cnt = 0;
                    }
                    else
                        $cnt = count( $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ] );
                        
                    $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ][ $cnt ]['ipv4enabled'] = $vli->getIpv4enabled();
                    $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ][ $cnt ]['ipv6enabled'] = $vli->getIpv6enabled();
                    $custs[ $c->getAutsys() ]['vlaninterfaces'][ $vli->getVlan()->getNumber() ][ $cnt ]['rsclient']    = $vli->getRsclient();
                }
            }
                         
        }
                        
        return $custs;
    }
                        
    /**
     * Find customers by ASN
     * 
     * @param  string $asn The ASN number to search for
     * @return \Entities\Customer[] Matching customers
     */
    public function findByASN( $asn )
    {
        return $this->getEntityManager()->createQuery(
                "SELECT c
        
                 FROM \\Entities\\Customer c

                 WHERE c.autsys = :asn

                 ORDER BY c.name ASC"
        
            )
            ->setParameter( 'asn', $asn )
            ->getResult();
    }
    
    /**
     * Find customers by AS Macro
     * 
     * @param  string $asm The AS macro to search for
     * @return \Entities\Customer[] Matching customers
     */
    public function findByASMacro( $asm )
    {
        return $this->getEntityManager()->createQuery(
                "SELECT c
        
                 FROM \\Entities\\Customer c

                 WHERE c.peeringmacro = :asm OR c.peeringmacrov6 = :asm

                 ORDER BY c.name ASC"
        
            )
            ->setParameter( 'asm', strtoupper( $asm ) )
            ->getResult();
    }
    
    /**
     * Find customers by 'wildcard'
     * 
     * @param  string $wildcard The test string to search for
     * @return \Entities\Customer[] Matching customers
     */
    public function findWild( $wildcard )
    {
        return $this->getEntityManager()->createQuery(
                "SELECT c
        
                 FROM \\Entities\\Customer c
                 LEFT JOIN c.RegistrationDetails r

                 WHERE 
                    c.name LIKE :wildcard
                    OR c.shortname LIKE :wildcard
                    OR c.abbreviatedName LIKE :wildcard
                    OR r.registeredName LIKE :wildcard

                 ORDER BY c.name ASC"
        
            )
            ->setParameter( 'wildcard', "%{$wildcard}%" )
            ->getResult();
    }


    /**
     * Return an array of the patch panel port cross connected
     *
     * @param $cid int The customer ID
     * @return array An array of all the  patch panel port entries
     */
    public function getCrossConnects( $cid )
    {
        $crossConnected = $this->getEntityManager()->createQuery(
            "SELECT ppp
             FROM \\Entities\\PatchPanelPort ppp
             WHERE ppp.customer = ?1
             AND ppp.duplexMasterPort IS NULL"
        )
            ->setParameter( 1, $cid )
            ->getResult();

        return $crossConnected;
    }

    /**
     * Return an array of one or all customer names where the array key is the customer id.
     *
     * @param $types array the types needed
     * @param $cid int The customer ID
     *
     * @return array An array of all customers names with the customers id as the key.
     */
    public function getAsArray( int $cid = null, array $types = [] ): array {
        $request = "SELECT c
                    FROM \\Entities\\customer c
                    WHERE 1 = 1";

        if( $cid ){
            $request .= " AND c.id = {$cid} ";
        }

        if( count( $types) > 0 ){
            $request .= " AND c.type IN (" . implode( ',', $types ) . ")";
        }

        $request .= " ORDER BY c.name ASC ";

        $listCustomers = $this->getEntityManager()->createQuery( $request )->getResult();

        $customers = [];
        foreach( $listCustomers as $cust ) {
            $customers[ $cust->getId() ] = $cust->getName() ;
        }

        return $customers;
    }

    /**
     * Return an array of one or all customer names where the array key is the customer id.
     *
     * @param $types array the types needed
     * @param $cid int The customer ID
     *
     * @return array An array of all customers names with the customers id as the key.
     */
    public function getAllForFeList( bool $currentCust = null,  int $state = null, int $type = null ): array {
        $request = "SELECT c
                    FROM \\Entities\\customer c
                    WHERE 1 = 1";

        if( $state ){
            $request .= " AND c.status = {$state} " ;
        }

        if( $type ){
            $request .= " AND c.type = {$type} " ;
        }

        if( $currentCust ){
            $request .= " AND " . Customer::DQL_CUST_CURRENT ;
        }

        $request .= " ORDER BY c.name ASC ";

        $listCustomers = $this->getEntityManager()->createQuery( $request )->getResult();

        $customers = [];
        foreach( $listCustomers as $cust ) {
            $customers[ ] = $cust ;
        }

        return $customers;
    }


    /**
     * Delete the customer object and everything releated
     *
     *     MACAddresses
     *     PhysicalInterfaces
     *     Layer2Addresses
     *     VlanInterfaces
     *     SflowReceivers
     *     VirtualInterfaces
     *     Contacts
     *     Preferences
     *     Logins
     *     Users
     *     ConsoleServerConnections
     *     TrafficDaily
     *     Traffic95thMonthly
     *     Traffic95th
     *     RSPrefix
     *     Peering Manager
     *     PatchPanelPorts
     *     Peering Matrix
     *     Logos
     *     Notes
     *     CustomerEquipment
     *     Irrdb Prefix
     *     Irrdb ASN
     *
     * @param CustomerEntity $c The customer Object
     *
     * @return boolean
     * @throws
     */
    public function delete( CustomerEntity $c ){
        $isOK = true;
        $this->getEntityManager()->getConnection()->beginTransaction();

        try {
            // Delete Virtual Interfaces
            foreach( $c->getVirtualInterfaces() as $vi ){

                // Delete Mac Addresses
                foreach( $vi->getMACAddresses() as $mac ){
                    $vi->removeMACAddresses( $mac );
                    $this->getEntityManager()->remove( $mac );
                }

                // Delete Physical Interface
                foreach( $vi->getPhysicalInterfaces() as $pi ){
                    $vi->removePhysicalInterface( $pi );
                    $this->getEntityManager()->remove( $pi );
                }

                // Delete Vlan Interfaces
                foreach( $vi->getVlanInterfaces() as $vli ){
                    // Delete Layer2Addresses
                    foreach( $vli->getLayer2Addresses() as $l2a ){
                        $vli->removeLayer2Address( $l2a );
                        $this->getEntityManager()->remove( $l2a );
                    }
                    $vi->removeVlanInterface( $vli );
                    $this->getEntityManager()->remove( $vli );
                }

                // Delete SflowReceivers
                foreach( $vi->getSflowReceivers() as $sflow ){
                    $vi->removeSflowReceiver( $sflow );
                    $this->getEntityManager()->remove( $sflow );
                }

                $c->removeVirtualInterface( $vi );
                $this->getEntityManager()->remove( $vi );
            }

            // Delete Contacts
            foreach( $c->getContacts() as $contact ){
                $c->removeContact( $contact );
                $this->getEntityManager()->remove( $contact );
            }

            // Delete Users
            foreach( $c->getUsers() as $user ){
                // Delete User Preferences
                /** @var \Entities\User $user */
                foreach( $user->getPreferences() as $pref ){
                    $user->removePreference( $pref );
                    $this->getEntityManager()->remove( $pref );
                }

                foreach( $user->getLastLogins() as $login ){
                    $user->removeLastLogin( $login );
                    $this->getEntityManager()->remove( $login );
                }

                $c->removeUser( $user );
                $this->getEntityManager()->remove( $user );
            }

            // Delete Console Server Connections
            foreach( $c->getConsoleServerConnections() as $csc ){
                $c->removeConsoleServerConnection( $csc );
                $this->getEntityManager()->remove( $csc );
            }

            // Delete Traffic Daily
            foreach( $c->getTrafficDailies() as $trafficDaily ){
                $c->removeTrafficDaily( $trafficDaily );
                $this->getEntityManager()->remove( $trafficDaily );
            }

            // Delete Traffic 95 monthly
            foreach( $c->getTraffic95thMonthlys() as $traffic95thMonthly ){
                $c->removeTraffic95thMonthly( $traffic95thMonthly );
                $this->getEntityManager()->remove( $traffic95thMonthly );
            }

            // Delete Traffic 95 monthly
            foreach( $c->getTraffic95ths() as $traffic95th ){
                $c->removeTraffic95th( $traffic95th );
                $this->getEntityManager()->remove( $traffic95th );
            }

            // Delete Rs Prefixes
            foreach( $c->getRSPrefixes() as $RSPrefix ){
                $c->removeRSPrefix( $RSPrefix );
                $this->getEntityManager()->remove( $RSPrefix );
            }

            // Delete Peering manager
            foreach( $c->getPeersWith() as $peerWith ){
                $c->removePeersWith( $peerWith );
                $this->getEntityManager()->remove( $peerWith );
            }

            foreach( $c->getPeers() as $peer ){
                $c->removePeer( $peer );
                $this->getEntityManager()->remove( $peer );
            }

            // Delete Patch Panel Port
            foreach( $c->getPatchPanelPorts() as $ppp ){
                D2EM::getRepository( PatchPanelPortEntity::class )->delete( $ppp );
            }

            foreach( $c->getYCusts() as $YCust){
                $c->removeYCust( $YCust );
                $this->getEntityManager()->remove( $YCust );
            }

            foreach( $c->getXCusts() as $XCust){
                $c->removeXCust( $XCust );
                $this->getEntityManager()->remove( $XCust );
            }

            // Delete Customer Logo
            foreach( $c->getLogos() as $logo){
                //unlink( $logo->getFullPath() );
                $c->removeLogo( $logo );
                $this->getEntityManager()->remove( $logo );
            }

            // Delete Notes
            foreach( $c->getNotes() as $note ){
                $c->removeNote( $note );
                $this->getEntityManager()->remove( $note );
            }

            // Delete Customer Equipment
            foreach( $c->getCustomerEquipment() as $custKit ){
                $c->removeCustomerEquipment( $custKit );
                $this->getEntityManager()->remove( $custKit );
            }

            // Delete Irrdb Prefixes
            foreach( $c->getIrrdbPrefixes() as $irrdbPrefixes ){
                $c->removeIrrdbPrefix( $irrdbPrefixes );
                $this->getEntityManager()->remove( $irrdbPrefixes );
            }

            // Delete Irrdb ASN
            foreach( $c->getIrrdbASNs() as $irrdbASN ){
                $c->removeIrrdbASN( $irrdbASN );
                $this->getEntityManager()->remove( $irrdbASN );
            }




            $this->getEntityManager()->remove( $c );


            $this->getEntityManager()->flush();
            $this->getEntityManager()->getConnection()->commit();


        } catch (Exception $e) {
            dd( $e );
            $this->getEntityManager()->getConnection()->rollBack();
            $isOK = false;
        }

        return $isOK;
    }
}
