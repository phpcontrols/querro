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
        return $_SESSION[$session];
    }
}


class C_SessionMaker{
    public static function getSession($framework='')
    {
        if(!session_id()){ session_start();}
        return new C_GridSession();
    }
}