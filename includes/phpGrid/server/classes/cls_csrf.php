<?php
namespace phpCtrl;

// Desc: per-session CSRF token for state-changing grid requests (edit.php,
// ajaxfileupload.php, ajaxfiledelete.php, ws_edit.php). The grid page exposes it as
// window.PHPGRID_CSRF_TOKEN and sends it with its own same-origin jQuery requests in
// the X-PHPGRID-CSRF header, or as the _phpgrid_csrf POST field for the file upload
// iframe form. $session is the object from C_SessionMaker::getSession() (get/set).
class C_Csrf{

    const HEADER = 'HTTP_X_PHPGRID_CSRF';
    const FIELD  = '_phpgrid_csrf';

    public static function token($session){
        $token = $session->get(self::session_key());
        if(!is_string($token) || strlen($token) !== 64){
            $token = bin2hex(random_bytes(32));
            $session->set(self::session_key(), $token);
        }

        return $token;
    }

    public static function is_valid($session, $submitted){
        $token = $session->get(self::session_key());

        return is_string($token) && $token !== '' && is_string($submitted) && hash_equals($token, $submitted);
    }

    public static function from_request(array $server, array $post){
        if(isset($server[self::HEADER]) && is_string($server[self::HEADER])){
            return $server[self::HEADER];
        }
        if(isset($post[self::FIELD]) && is_string($post[self::FIELD])){
            return $post[self::FIELD];
        }

        return '';
    }

    // Stops the request unless it carries this session's token.
    public static function require_valid($session){
        if(!self::is_valid($session, self::from_request($_SERVER, $_POST))){
            C_Utility::fail('PHPGRID_ERROR: Invalid or missing CSRF token. Reload the page and try again.', 403);
        }
    }

    private static function session_key(){
        return GRID_SESSION_KEY .'_csrf';
    }
}
