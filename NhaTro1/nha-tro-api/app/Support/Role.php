<?php

namespace App\Support;

class Role
{
    public const ADMIN = 'quan_tri';
    public const ADMIN_ALT = 'admin';
    public const OWNER = 'chu_tro';
    public const TENANT = 'khach_thue';

    public static function name($user): string
    {
        if (!$user) return '';
        if (is_array($user)) return $user['vai_tro'] ?? '';
        if (is_object($user)) return $user->vai_tro ?? '';
        return '';
    }

    public static function isAdmin($user): bool
    {
        $n = self::name($user);
        return $n === self::ADMIN || $n === self::ADMIN_ALT;
    }

    public static function isOwner($user): bool
    {
        return self::name($user) === self::OWNER;
    }

    public static function isTenant($user): bool
    {
        return self::name($user) === self::TENANT;
    }
}
