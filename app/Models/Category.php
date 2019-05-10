<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for categories which contains basic threads and sub-categories.
 *
 * @property timestamp deleted_at
 * @property int id
 * @property int parent_id (nullable)
 * @property string name
 * @property string description
 * @property string fa_icon FontAwesome icon code
 */
class Category extends Model
{

    use SoftDeletes;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['parent_id', 'name', 'description', 'fa_icon'];

    /**
     * Gets the sub-categories, maybe none.
     */
    public function subs()
    {
        return $this->hasMany('App\Models\Category', 'parent_id');
    }

    /**
     * Gets the parent category, maybe null.
     */
    public function parent()
    {
        return $this->belongsTo('App\Models\Category', 'parent_id');
    }

    /**
     * Gets the basic threads in this category. The threads of the sub-categories
     * aren't included.
     */
    public function basicThreads()
    {
        return $this->hasMany('App\Models\BasicThread', 'category_id');
    }

    /**
     * Gets the users who subscribed to receive notifications about new threads.
     */
    public function subscribers()
    {
        return $this->belongsToMany('App\Models\User', 'categories_subscriptions', 'category_id', 'user_id');
    }
}
