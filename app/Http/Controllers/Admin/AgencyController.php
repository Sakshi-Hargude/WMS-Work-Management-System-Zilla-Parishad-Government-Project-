<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
      //Add Record Screen Call
      public function createform(){
        return view('agencies/add');
    }

    // Retrive All Records
    function allrecords(){
        $data= Agency::paginate(10);
        return view('agencies/list',['agencylist'=>$data]);
    }

    //Delete table Rows
    function delete($id){
        $SQL= DB::table('agencies')->where('id', '=', $id)->delete();
        if($SQL){
          return redirect('admin/agencies/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/agencies/list')->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
    {
        $data = $request->input();
        try{
            $objAgency = new Agency();
            $objAgency->agency_nm  = $data['agency_nm'];
            $objAgency->agency_nm_m  = $data['agency_nm_m'];
            $objAgency->place  = $data['place'];
            $objAgency->save();
            return redirect('admin/agencies/add')->with('status',"Insert successfully");
        }catch(Exception $e){
            return redirect('admin/agencies/add')->with('failed',"operation failed");
        }

    }

    //edit records
    function vieweditrecords($id){
        $data= DB::table('agencies')
        ->selectRaw('`id`,`agency_nm`,`agency_nm_m`,`place`')
        ->where('id', '=', $id)
        ->get();
         $result = json_decode($data, true);
         return view('agencies/edit',['singlerecord'=>$result]);

    }

    function editsubmitrecord(Request $req){
        $SQL = DB::table('agencies')
        ->where('id', $req->id)
        ->update(['agency_nm' => $req->agency_nm,'agency_nm_m' => $req->agency_nm_m,'place' => $req->place]);
        if($SQL){
            return redirect('admin/agencies/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/agencies/edit/'.$req->Dist_id)->with('success','Error in update record.');
        }

     }

}
