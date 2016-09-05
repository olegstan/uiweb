<?php
namespace core\helper;

/*
Code    Meaning
200    (nonstandard success response, see rfc876)
211    System status, or system help reply
214    Help message
220    <domain> Service ready
221    <domain> Service closing transmission channel
250    Requested mail action okay, completed
251    User not local; will forward to <forward-path>
252    Cannot VRFY user, but will accept message and attempt delivery
354    Start mail input; end with <CRLF>.<CRLF>
421    <domain> Service not available, closing transmission channel
450    Requested mail action not taken: mailbox unavailable
451    Requested action aborted: local error in processing
452    Requested action not taken: insufficient system storage
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
503    Bad sequence of commands
504    Command parameter not implemented
521    <domain> does not accept mail (see rfc1846)
530    Access denied (???a Sendmailism)
550    Requested action not taken: mailbox unavailable
551    User not local; please try <forward-path>
552    Requested mail action aborted: exceeded storage allocation
553    Requested action not taken: mailbox name not allowed
554    Transaction failed
*/

/*
Command    Code    Description
HELO
250    Requested mail action okay, completed
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
504    Command parameter not implemented
521    <domain> does not accept mail [rfc1846]
421    <domain> Service not available, closing transmission channel
RSET
200    (nonstandard success response, see rfc876)
250    Requested mail action okay, completed
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
504    Command parameter not implemented
421    <domain> Service not available, closing transmission channel
SOML
250    Requested mail action okay, completed
552    Requested mail action aborted: exceeded storage allocation
451    Requested action aborted: local error in processing
452    Requested action not taken: insufficient system storage
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
421    <domain> Service not available, closing transmission channel
SAML
250    Requested mail action okay, completed
552    Requested mail action aborted: exceeded storage allocation
451    Requested action aborted: local error in processing
452    Requested action not taken: insufficient system storage
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
421    <domain> Service not available, closing transmission channel
VRFY
250    Requested mail action okay, completed
251    User not local; will forward to <forward-path>
252    Cannot VRFY user, but will accept message and attempt delivery
550    Requested action not taken: mailbox unavailable
551    User not local; please try <forward-path>
553    Requested action not taken: mailbox name not allowed
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
504    Command parameter not implemented
421    <domain> Service not available, closing transmission channel
EXPN
250    Requested mail action okay, completed
550    Requested action not taken: mailbox unavailable
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
504    Command parameter not implemented
421    <domain> Service not available, closing transmission channel
HELP
211    System status, or system help reply
214    Help message
500    Syntax error, command unrecognised
501    Syntax error in parameters or arguments
502    Command not implemented
504    Command parameter not implemented
421    <domain> Service not available, closing transmission channel
NOOP
200    (nonstandard success response, see rfc876)
250    Requested mail action okay, completed
500    Syntax error, command unrecognised
421    <domain> Service not available, closing transmission channel
QUIT
221    <domain> Service closing transmission channel
500    Syntax error, command unrecognised
TURN
250    Requested mail action okay, completed
502    Command not implemented
500    Syntax error, command unrecognised
503    Bad sequence of commands


235    Authentication Succeeded    yes    yes
334    Text part containing the [BASE64] encoded string    yes    yes
432    A password transition is needed    no    >= 0.75
454    Temporary authentication failure    yes    n/a
500    Authentication Exchange line is too long    no    n/a
501    Malformed auth input/Syntax error    yes    n/a
503    AUTH command is not permitted during a mail transaction    yes    n/a
504    Unrecognized authentication type    yes    n/a
530    Authentication required    Submission mode    n/a
534    Authentication mechanism is to weak    no    no
535    Authentication credentials invalid    yes    yes
538    Encryption required for requested authentication mechanism    no    no



*/
/*class MailException extends \Exception
{

}*/

class Mail
{
    public $connected;
    public $auth;
    public $connect_timeout = 30;
    public $response_timeout = 8;

    const separator = "\r\n";

    public $headers = [];

    public $last_status_code;
    public $last_status_text;

    function __construct($host = SMTP_HOST, $port = SMTP_PORT, $username = SMTP_USERNAME, $password = SMTP_PASSWORD, $localhost = SMTP_LOCALHOST)
    {
        $this->mail = new Render('mail');

        $this->host = $host;
        $this->post = $port;
        $this->username = $username;
        $this->password = $password;
        $this->localhost = $localhost;

        $this->socket = fsockopen($host, $port, $errno, $errstr, $this->connect_timeout);

        if($this->socket){
            $this->connected = true;

            $this->protectConnection();

            $this->getResponse('OPEN');

            $this->ehlo();

            $this->auth();
        }else{
            $this->connected = false;
        }

        return $this;
    }

    /**
     * @return string
     *
     * получаем ответ сервера
     */

    private function getResponse($command)
    {
        stream_set_timeout($this->socket, $this->response_timeout);
        $response = '';

        $i = 0;
        while (($line = fgets($this->socket, 515)) != false)
        {
            $response .= trim($line) . "\n";
            if($i == 0){
                $status = substr($line, 0, 3);

                //echo $status . '<br>';
                //echo $command . '<br>';
                $this->last_status_code = $status;

                switch($command){
                    case 'OPEN':
                        switch($status){
                            case 220:
                                $this->connected = true;
                                break;
                            case 421:
                            default:
                                $this->connected = false;
                                break;
                        }
                        break;
                    case 'EHLO':
                        switch($status){
                            case 250:
                                $this->connected = true;
                                break;
                            case 550:
                            case 500:
                            case 501:
                            case 504:
                            case 421:
                            default:
                                $this->connected = false;
                                break;
                        }
                        break;
                    case 'AUTH LOGIN':
                        switch($status){
                            case 235:
                            case 334:
                                $this->auth = true;
                                break;
                            case 432:
                            case 454:
                            case 500:
                            case 501:
                            case 503:
                            case 504:
                            case 530:
                            case 534:
                            case 535:
                            case 538:
                            default:
                                $this->auth = false;
                                break;
                        }
                        break;
                    case 'QUIT':
                        switch($status){
                            case 221:

                                break;
                            case 441:
                            default:

                                break;

                        }
                        break;
                    case 'DATA':
                        switch($status){
                            case 250:
                            case 354:

                                break;
                            case 451:
                            case 452:
                            case 500:
                            case 501:
                            case 503:
                            case 421:
                            case 552:
                            case 554:
                            default:

                                break;
                        }
                        break;
                    case 'RCPT':
                        switch($status){
                            case 250:

                                break;
                            case 251:
                            case 550:
                            case 551:
                            case 552:
                            case 553:
                            case 450:
                            case 451:
                            case 452:
                            case 500:
                            case 501:
                            case 503:
                            case 521:
                            case 421:
                            default:

                                break;
                        }
                        break;
                    case 'MAIL':
                        switch($status){
                            case 250:

                                break;
                            case 552:
                            case 451:
                            case 452:
                            case 500:
                            case 501:
                            case 421:
                            default:

                                break;
                        }
                }

            }
            $i++;

            if (substr($line,3,1)==' '){
                break;
            }
        }

        $this->last_status_text = $response;
    }

    public function protectConnection()
    {
        /**
         * специальная штука для защищенной передачи данных
         * без неё работать не будет yandex
         *
         * STREAM_CRYPTO_METHOD_SSLv2_CLIENT
         * STREAM_CRYPTO_METHOD_SSLv3_CLIENT
         * STREAM_CRYPTO_METHOD_SSLv23_CLIENT
         * STREAM_CRYPTO_METHOD_TLS_CLIENT
         * STREAM_CRYPTO_METHOD_SSLv2_SERVER
         * STREAM_CRYPTO_METHOD_SSLv3_SERVER
         * STREAM_CRYPTO_METHOD_SSLv23_SERVER
         * STREAM_CRYPTO_METHOD_TLS_SERVER
         */

        stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);

    }

    private function sendCommand($command)
    {
        if($this->isConnected()){
            fputs($this->socket, $command . self::separator);
            //return $this->getResponse($command);
        }else{
            return false;
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function isAuth()
    {
        return $this->auth;
    }

    public function ehlo()
    {
        $this->sendCommand('EHLO ' . $this->localhost);
        $this->getResponse('EHLO');
    }

    /**
     * smtp принимает пароль тоьлко base64_decode
     */

    public function auth()
    {
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
        $this->getResponse('AUTH LOGIN');
        $this->getResponse('AUTH LOGIN');
        $this->getResponse('AUTH LOGIN');
    }

    public function send($to, $subject, $name, $data = null)
    {
        if($this->isConnected() && $this->isAuth()){

            $this->sendCommand('MAIL FROM:<' . $this->username . '>');
            $this->getResponse('MAIL');
            $this->sendCommand('RCPT TO:<' . $to . '>');
            $this->getResponse('RCPT');

            $this->sendCommand('DATA');

            $this->headers['Date'] = date('D, d M Y H:i:s');
            $this->headers['From'] = 'no-replay@uiweb.ru';
            $this->headers['To'] = $to;
            $this->headers['Subject'] = $subject;
            $this->headers['Reply-To'] = 'no-replay@uiweb.ru';
            $this->headers['MIME-Version'] .= '1.0';
            $this->headers['X-Priority'] = '1';
            $this->headers['X-MSMail-Priority'] = 'High';
            $this->headers['X-Mailer'] = 'php';
            $this->headers['Content-type'] = 'text/html; charset=utf-8';
            $this->headers['Content-Transfer-Encoding'] .= '8bit';

            $headers = '';
            foreach ($this->headers as $key => $val) {
                $headers .= $key . ': ' . $val . self::separator;
            }
            $message = $this->mail->render($name, $data);
            $this->sendCommand($headers . self::separator . $message . self::separator . '.');
            $this->getResponse('DATA');
            $this->sendCommand('QUIT');
            $this->getResponse('QUIT');

            fclose($this->socket);
        }
        return $this;
    }
}