<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoUser extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'your_key_name';

    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'email',
        'status',
        'group_name',
        'passwd',
        'creation_date',
        'tm',
        'mstr',
        'rclcrew',
        'ctrac',
        'ctrac_app',
        'passwd',
        'is_migrated',
        'login_attempts',
        'user_role',
        'passcode',
        'passcode_flag',
        'passwd_update',
    ];

    protected $hidden = [
      // 'passwd',
      'creation_date',
    ];

    protected $dates = [
        'creation_date',
    ];

    public function scopeOfUserAccess($query, $username)
    {
        return $query->where('email', $username)
            ->orWhere('tm', $username)
            ->orWhere('mstr', $username)
            ->orWhere('rclcrew', $username)
            ->orWhere('ctrac', $username)
            ->orWhere('ctrac_app', $username);
    }

    static function encryptPassword($word)
    {
        $ldap_api_enc_method = 'aes-128-cbc';
        $ldap_api_enc_iv = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/0123455@'));
        $ldap_api_enc_pass = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/9876543$'));
        $userPassword = base64_encode(openssl_encrypt(
          base64_encode($word),
          $ldap_api_enc_method,
          $ldap_api_enc_pass,
          false,
          mb_substr($ldap_api_enc_iv, 0, 16)
        ));
        return $userPassword;
    }

    static function decryptPassword($word)
    {
        $ldap_api_enc_method = 'aes-128-cbc';
        $ldap_api_enc_iv = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/0123455@'));
        $ldap_api_enc_pass = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/9876543$'));
        $userPassword = rtrim( base64_decode( openssl_decrypt(
            base64_decode($word),
            $ldap_api_enc_method,
            $ldap_api_enc_pass,
            false,
            mb_substr($ldap_api_enc_iv, 0, 16)
        ) ), "\0" );
        return $userPassword;
    }
}
