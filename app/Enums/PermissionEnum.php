<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case UPDATE = 'update';
    case CREATE = 'create';
    case DELETE = 'delete';


}
