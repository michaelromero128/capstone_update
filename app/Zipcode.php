<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Zipcode extends Model
{
    // asserts the actual table used
    protected $table = 'zipcodes';
    
    // asserts the primary key
    protected $primarykey = 'zipcode';
    
    // asserts the table doesn't autoincrement but no insertions are performed so not an issue
    public $incrementing = false;
    
    // table doesn't have time stamp
    public $timestamps = false;
}

