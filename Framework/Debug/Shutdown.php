<?php
namespace Framework\Debug;
/**
 * Class Shutdown
 * @package Framework\Debug
 */
class Shutdown
{
    /**
     *
     */
    public function __construct()
    {
        register_shutdown_function(function(){
            //echo $message = ob_get_contents(); // Capture 'Doh'
            echo ob_end_clean(); // Cleans output buffer
        });
    }
}
///**
// *
// */
//new Shutdown();

//public function __construct()
//{
//    register_shutdown_function(array($this, 'shutdownHandler'));
//    set_error_handler(array($this, 'errorHandler'));
//}
//
//private function errorHandler($error_level, $error_message, $error_file, $error_line, $error_context)
//{
//    $error = "lvl: " . $error_level . " | msg:" . $error_message . " | file:" . $error_file . " | ln:" . $error_line;
//
//    switch ($error_level) {
//        case E_ERROR:
//        case E_CORE_ERROR:
//        case E_COMPILE_ERROR:
//        case E_PARSE:
//        case E_USER_ERROR:
//            $this->logMe($error, "fatal");
//            break;
//        case E_USER_ERROR:
//        case E_RECOVERABLE_ERROR:
//            $this->logMe($error, "error");
//            break;
//        case E_WARNING:
//        case E_CORE_WARNING:
//        case E_COMPILE_WARNING:
//        case E_USER_WARNING:
//            $this->logMe($error, "warn");
//            break;
//        case E_NOTICE:
//        case E_USER_NOTICE:
//            $this->logMe($error, "info");
//            break;
//        case E_STRICT:
//            $this->logMe($error, "debug");
//            break;
//        default:
//            $this->logMe($error, "warn");
//    }
//}
//
//private function shutdownHandler() //will be called when php script ends.
//{
//    $lasterror = error_get_last();
//
//    echo "Level: " . $lasterror;
//    switch ($lasterror['type'])
//    {
//        case E_ERROR:
//        case E_CORE_ERROR:
//        case E_COMPILE_ERROR:
//        case E_USER_ERROR:
//        case E_RECOVERABLE_ERROR:
//        case E_CORE_WARNING:
//        case E_COMPILE_WARNING:
//        case E_PARSE:
//            $error = "[SHUTDOWN] lvl:" . $lasterror['type'] . " | msg:" . $lasterror['message'] . " | file:" . $lasterror['file'] . " | ln:" . $lasterror['line'];
//            $this->logMe($error, "fatal");
//    }
//}
//
//private function logMe($error, $errlvl)
//{
//    echo 'Error No: ' . $error . ' <BR> Error Level: ' .$errlvl;
//}