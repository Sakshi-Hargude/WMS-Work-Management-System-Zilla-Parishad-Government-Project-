<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estrecap extends Model
{
use HasFactory;
protected $fillable = [
    'Est_Id',
    'Est_Rcp_Id',
    'Sr_No',
    'Se_No',
    'Descrip',
    'Rcp_Pc',
    'Pc_On',
    'Rcp_Amt',
    'Rcp'
];
}
