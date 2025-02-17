<?php

namespace App\Http\Controllers\Admin;
use App\Models\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EnquirycatController extends Controller
{
    // Retrive All Records
    function allrecords(){
        $data= Member::paginate(10);
        return view('enquiries/list',['members'=>$data]);
    }

    // Delete records
    function deleteEnquery($id){
        $res=Member::find($id)->delete();
        if ($res){
            return redirect('admin/enquiries/list')->with('success','Record deleted successfully.');
                }else{
            return redirect('admin/enquiries/list')->with('success','Error in record.');
        }
    }
}
