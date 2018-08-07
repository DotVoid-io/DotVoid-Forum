<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public $timestamps = false;

    /**
     * Gets the roles that have this permission.
     */
    public function roles()
    {
      return $this->belongsToMany('App\Models\Role', 'roles_permissions', 'perm_id', 'role_id');
    }
}