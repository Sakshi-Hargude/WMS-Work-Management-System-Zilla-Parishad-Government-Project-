<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estscope extends Model
{
    use HasFactory;
    protected $fillable = [
        'Est_Id',
        'Est_Sc_Id',
        'Scope_Id',
        'Scope',
        'Scope_M',
        'Qty',
        'Unit'
    ];
}
