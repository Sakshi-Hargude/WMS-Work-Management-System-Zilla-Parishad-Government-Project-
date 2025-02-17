<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estmaster extends Model
{
    use HasFactory;
    //public $timestamps=true;

    protected $fillable = [
        'Est_Id',
        'Est_No',
        'Sub_Div_Id',
        'Sub_Div',
        'Tal',
        'Work_Nm',
        'Work_Type',
        'Tot_Amt',
        'E_Prep_By',
        'E_Chk_By',
        'Date_Prep',
        'F_H_Code',
        'Tal_Id',
        'Work_Nm_M',
        'Est_PDF_Path',
        'Est_XLS_Path',
        'AA_TS',
        'roundofvalue',
        'roundOfCalculateAmount',
        'je_id',
        'po_id'
    ];

    // Esthd.php (Model)
public function jemaster()
{
    return $this->belongsTo(Jemaster::class, 'je_id', 'jeid');
}

// Esthd.php (Model)
public function jemaster1()
{
    return $this->belongsTo(Jemaster::class, 'po_id', 'jeid');
}


}
