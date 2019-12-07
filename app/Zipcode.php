<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Zipcode extends Model
{
    protected $table = 'zipcodes';
    
    protected $primarykey = 'zipcode';
    
    public $incrementing = false;
    
    public $timestamps = false;
}

