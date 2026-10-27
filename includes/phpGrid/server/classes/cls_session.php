<?php
namespace phpCtrl;

class C_GridSession{

    public static function sessionStarted() {
        if(session_id() == '') {
            return false;
        } else {
            return true;
        }
    }
    public static function sessionExists($session) {
        if(self::sessionStarted() == false) {
            session_start();
        }
        if(isset($_SESSION[$session])) {
            return true;
        } else {
            return false;
        }
    }

    public function set($session, $value) {
        $_SESSION[$session] = $value;
    }
    public function get($session) {
        return isset($_SESSION[$session]) ? $_SESSION[$session] : null;
    }
}


class C_SessionMaker{
    public static function getSession($framework='')
    {
        if($framework=='JOOMLA'){

            if (!defined('_JEXEC') )
                define( '_JEXEC', 1 );

            if (!defined('JPATH_BASE') )
                define('JPATH_BASE', '/var/www/mysite'); #UPDATE this value to the actual Joomla site file path

            require_once( JPATH_BASE. '/includes/defines.php' );
            require_once( JPATH_BASE. '/includes/framework.php');

            $mainframe = \JFactory::getApplication('site');
            $mainframe -> initialise();

            $session = \JFactory::getSession();

            return $session;
        }else{
            if(!session_id()){ session_start();}
            return new C_GridSession();
        }
    }

}