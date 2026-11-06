<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class GiftOption extends Model
{
    use HasVisibility;

    protected $guarded = ['id'];
}
