<?php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function phoneNumber($phone) {
        $clean = preg_replace('/\D/', '', $phone);
        return strlen($clean) >= 10 && strlen($clean) <= 11;
    }

    public static function cep($cep) {
        $clean = preg_replace('/\D/', '', $cep);
        return preg_match('/^\d{8}$/', $clean) === 1;
    }
}
?>
