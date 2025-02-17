<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserscatController extends Controller
{
   // Retrive All Records
    function allrecords(){
        // login user session Data----------------------------

        $usercode = auth()->user()->usercode;
        $divid = auth()->user()->Div_id;
        $subdivid = auth()->user()->Sub_Div_id;

        // login user session Data----------------------------
        $data= User::select('*')
        ->whereNotIn('usertypes', ['EE','DA'])
        ->where('Div_id', '=', $divid)
        ->orWhere('Sub_Div_id','=', $subdivid)
        ->get();

        return view('userslist',['users'=>$data]);
    }

    //delete records
    function deleteUsers($id){
        $res=User::find($id)->delete();
        if($res){
            return redirect('userslist')->with('success','Record deleted successfully.');
        }else{
            return redirect('userslist')->with('success','Error in record.');
        }
    }

}
