<?php

namespace App\Utils;

/**
 * Encrypt/Decrypt data from Javascript's CryptoJS
 * PHP 7.x and later supported
 * If you need PHP 5.x support, goto the legacy branch https://github.com/brainfoolong/cryptojs-aes-php/tree/legacy
 * @link https://github.com/brainfoolong/cryptojs-aes-php
 * @version 2.1.1
 */
class CryptoJsAes
{
    /**
     * Encrypt any value
     * @param mixed $value Any value
     * @param string $passphrase Your password
     * @return string
     */
    public static function encrypt($value, string $passphrase)
    {
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = ["ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt)];
        return json_encode($data);
    }

    /**
     * Decrypt a previously encrypted value
     * @param string $jsonStr Json stringified value
     * @param string $passphrase Your password
     * @return mixed
     */
    public static function decrypt(string $jsonStr, string $passphrase)
    {
        $json = json_decode($jsonStr, true);
        $salt = hex2bin($json["s"]);
        $iv = hex2bin($json["iv"]);
        $ct = base64_decode($json["ct"]);
        $concatedPassphrase = $passphrase . $salt;
        $md5 = [];
        $md5[0] = md5($concatedPassphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1] . $concatedPassphrase, true);
            $result .= $md5[$i];
        }
        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        return json_decode($data, true);
    }

    public static function decode($data, $password)
    {
//        $password = "AS@CIE@ttes#GPSB";
        $password = utf8_decode($password);

// IV
        $iv = "BBBBBBBBBBBBBBBB";
        $iv = utf8_decode($iv);

// Données chiffrées
//        $data = "votre_donnee_chiffree_en_base64";

// Convertir la clé et l'IV en bytes
        $password = substr($password, 0, 16); // Assurez-vous que la clé a la bonne longueur
        $iv = substr($iv, 0, 16); // Assurez-vous que l'IV a la bonne longueur

// Déchiffrer les données
        $decryptedData = openssl_decrypt(base64_decode($data), 'aes-128-cbc', $password, OPENSSL_RAW_DATA, $iv);
        return $decryptedData;
    }

}
