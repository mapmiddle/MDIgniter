<?php


class Email {
    static $CI = NULL;

    public function __construct() {
        $this->getCI()->load->library('email', mdi::config('mail'));
    }

    public function send($name, $from, $to, $subject, $content) {
        $ci = $this->getCI();

        $ci->email->set_newline("\r\n");
        $ci->email->clear();
        $ci->email->from($from, $name);
        $ci->email->to($to);
        $ci->email->subject($subject);
        $ci->email->message($content);

        if ($ci->email->send()) {
            return TRUE;
        } else {
            MDI_Log::write($ci->email->print_debugger());
            return FALSE;
        }
    }

    public static function &getCI() {
        if (is_null(self::$CI)) {
            self::$CI =& get_instance();
        }

        return self::$CI;
    }
}