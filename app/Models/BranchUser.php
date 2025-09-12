<?php

namespace App\Models;


class BranchUser extends BaseModel
{
    protected $table = 'branch_user';
        protected $fillable = ['branch_user_id','user_id', 'branch_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
