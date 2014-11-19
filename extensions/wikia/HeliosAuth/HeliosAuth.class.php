<?php
class HeliosAuth extends ExternalUser {
    
    /**
     * The method is protected in the abstract class and has been made public
     * to smooth unit testing.
     * 
     * @param string $sName User's name
     * @return boolean True on success, false otherwise
     * @see includes/ExternalUser.php
     */
    public function initFromName( $sName ) {
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
        return false;
    }

    /**
     * @return integer User's ID
     * @see includes/ExternalUser.php
     */
    public function getId() {
        return 1;
    }
    
    /**
     * @return string User's name
     * @see includes/ExternalUser.php
     */
    public function getName() {
        return 'John';
    }
    
    /**
     * @param string $sPassword User's password
     * @return boolean True on success, false otherwise
     */
    public function authenticate( $sPassword ) {
        return false;
    }
}