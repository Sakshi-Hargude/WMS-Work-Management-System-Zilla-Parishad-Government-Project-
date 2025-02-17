<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserscatController extends Controller
{
   // Retrive All Records
    function allrecords(){
       $data= User::whereNotIn('usertypes', ['EE','DA'])->paginate(10);
    //   dd($data);
       return view('users/list',['users'=>$data]);
    }

          //delete records
    function deleteUsers($id){
    $res=User::find($id)->delete();
    if ($res){
      return redirect('admin/users/list')->with('success','Record deleted successfully.');
          }else{
      return redirect('admin/users/list')->with('success','Error in record.');
      }

      }
}
