<?php

namespace App\Models;

class UserRole
{
    const ADMIN = 'admin';
    const EMPLOYEE = 'employee';
    const CLIENT = 'client';
    const ROLES = [
        self::ADMIN,
        self::EMPLOYEE,
        self::CLIENT,
    ];
}
