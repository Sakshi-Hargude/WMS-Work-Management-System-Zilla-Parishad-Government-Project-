<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Dism;

use App\Http\Controllers\Controller;
use App\Models\Disms;
use Illuminate\Http\Request;

class DismController extends Controller
{
      //Add Record Screen Call
      public function createform(){
        return view('dism/add');
    }

    // Retrive All Records
    function allrecords(){
        $data= Dism::paginate(10);
        return view('dism/list',['dism'=>$data]);
    }

    //Delete table Rows
    function delete($id){
        $SQL= DB::table('disms')->where('Dist_id', '=', $id)->delete();
        if($SQL){
          return redirect('admin/dism/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/dism/list')->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
    {
        $data = $request->input();
        try{
            $SQLNewPKID = DB::table('disms')
                ->selectRaw('Dist_id + 1 as Dist_id')
                ->orderBy('Dist_id', 'desc')
                ->limit(1)
                ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Dist_id) && !empty($RSNewPKID[0]->Dist_id)){
                $PrimaryNumber=$RSNewPKID[0]->Dist_id;
            }else{
                $PrimaryNumber=1;
            }
            $objDistrict = new Dism();
            $RSNewPKID = json_decode($SQLNewPKID);
            $objDistrict->Dist_id  = $PrimaryNumber;
            $objDistrict->Dist  = $data['Dist'];
            $objDistrict->Dist_M  = $data['Dist_M'];
            $objDistrict->save();
            return redirect('admin/permission/add')->with('status',"Insert successfully");
        }catch(Exception $e){
            return redirect('admin/permission/add')->with('failed',"operation failed");
        }

    }

    //edit records
    function vieweditrecords($id){
        $data= DB::table('disms')
        ->selectRaw('`Dist_id`,`Dist`,`Dist_M`')
        ->where('Dist_id', '=', $id)
        ->get();
         $result = json_decode($data, true);
         return view('dism/edit',['singlerecord'=>$result]);

    }

    function editsubmitrecord(Request $req){
        $SQL = DB::table('disms')
        ->where('Dist_id', $req->Dist_id)
        ->update(['Dist' => $req->Dist,'Dist_M' => $req->Dist_M]);
        if($SQL){
            return redirect('admin/dism/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/dism/edit/'.$req->Dist_id)->with('success','Error in update record.');
        }

     }

}
