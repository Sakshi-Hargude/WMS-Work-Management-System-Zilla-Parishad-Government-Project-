<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\DB;


class CategoryController extends Controller
{

    //Add Record Screen Call
    public function createform(){
       return view('eventcategory/add');
    }

    //Insert To Tb
    function InsertTB(Request $request)
    {
        $data = $request->input();
        try{
            $ev = new Event;
            $ev->eventtitle  = $data['eventtitle'];
            $ev->save();
            return redirect('admin/eventcategory/add')->with('status',"Insert successfully");
        }catch(Exception $e){
            return redirect('admin/eventcategory/add')->with('failed',"operation failed");
        }

    }

    // Retrive All Records
    function allrecords(){
        $data= Event::all();
        return view('eventcategory/list',['events'=>$data]);
    }

//delete records
    function deleteEvent($id){


  $res=Event::find($id)->delete();
  if ($res){
    return redirect('admin/eventcategory/list')->with('success','Record deleted successfully.');
        }else{
    return redirect('admin/eventcategory/list')->with('success','Error in record.');
    }

    }

//edit records
    function vieweditrecords($id){
        // echo $id; exit;
        $data=Event::find($id);
         return view('eventcategory/edit',['singlerecord'=>$data]);

    }
    function editsubmitrecord(Request $req){
        $data=Event::find($req->id);
        $data->eventtitle=$req->eventtitle;
        $data->save();
        return redirect('admin/eventcategory/list')->with('success','Record Updated successfully.');

    }

}
