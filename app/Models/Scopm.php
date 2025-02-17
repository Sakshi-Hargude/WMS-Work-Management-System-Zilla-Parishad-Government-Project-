<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scopm extends Model
{
    use HasFactory;
    //public $timestamps=true;
    protected $fillable = [
        'Work_Type_Id',
        'Work_Type',
        'Scope_Id',
        'Scope',
        'Scope_M',
        'Unit'
    ];
}
