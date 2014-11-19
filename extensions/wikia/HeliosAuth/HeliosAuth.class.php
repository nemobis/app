<?php
class HeliosAuth extends ExternalUser {
    
    public function __construct() {
        trigger_error( __METHOD__, E_USER_NOTICE );
        parent::__construct();
    }
    
    /**
     * The method is protected in the abstract class and has been made public
     * to smooth unit testing.
     * 
     * @param string $sName User's name
     * @return boolean True on success, false otherwise
     * @see includes/ExternalUser.php
     */
    public function initFromName( $sName ) {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return false;
    }
    
    /**
     * This method is protected in the abstract class and has been made public
     * to smooth unit testing.
     * 
     * @param integer $iId User's ID
     * @return boolean True on success, false otherwise
     * @see includes/ExternalUser.php
     */
    public function initFromId( $iId ) {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return false;
    }
    
    /**
     * This method is protected in the abstract class and has been made public
     * to smooth unit testing.
     * 
     * @param object $oUser A user object
     * @return boolean True on success, false otherwise
     * @see includes/ExternalUser.php
     */
    public function initFromUser( $oUser ) {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return false;
    }
    
    /**
     * This method is protected in the abstract class and has been made public
     * to smooth unit testing.
     * 
     * @param object $oUser A user object
     * @param string $sPassword User's password
     * @param string $sEmail User's email
     * @param string $sRealname User's real name
     * @return boolean True on success, false otherwise
     * @see includes/ExternalUser.php
     */
    public function addToDatabase( $oUser, $sPassword, $sEmail, $sRealname ) {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return false;
    }

    /**
     * @return integer User's ID
     * @see includes/ExternalUser.php
     */
    public function getId() {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return 1;
    }
    
    /**
     * @return string User's name
     * @see includes/ExternalUser.php
     */
    public function getName() {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return 'John';
    }
    
    /**
     * @param string $sPassword User's password
     * @return boolean True on success, false otherwise
     */
    public function authenticate( $sPassword ) {
        trigger_error( __METHOD__, E_USER_NOTICE );
        return false;
    }
}