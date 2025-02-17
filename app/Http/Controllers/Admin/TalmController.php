<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Talm;
use App\Models\Dism;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TalmController extends Controller
{
    //Add Record Screen Call
    public function createform(){
        $rsDistList= Dism::get();
        return view('talm/add',['rsDistrict'=>$rsDistList]);
    }

    // Retrive All Records
    function allrecords(){
        $data= Talm::paginate(10);
        return view('talm/list',['talms'=>$data]);
    }

    //Delete table Rows
    function delete($Tal_Id){
        $res= DB::table('talms')->where('Tal_Id', '=', $Tal_Id)->delete();
        if ($res){
          return redirect('admin/talm/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/talm/list')->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
        {
            $data = $request->input();
                try{
                    $SQLNewPKID = DB::table('talms')
                        ->selectRaw('Tal_Id + 1 as Tal_Id')
                        ->orderBy('Tal_Id', 'desc')
                        ->limit(1)
                        ->get();
                    $RSNewPKID = json_decode($SQLNewPKID);
                    if(isset($RSNewPKID[0]->Tal_Id) && !empty($RSNewPKID[0]->Tal_Id)){
                      $PrimaryNumber=$RSNewPKID[0]->Tal_Id;
                    }else{
                      $PrimaryNumber=1;
                    }
                    $objtalukha = new Talm();
                    $objtalukha->Tal_Id  = $PrimaryNumber;
                    $objtalukha->Dist_Id  = $data['Dist_Id'];
                    $objtalukha->Tal  = $data['Tal'];
                    $objtalukha->Tal_M  = $data['Tal_M'];
                    $objtalukha->save();
                    return redirect('admin/talm/add')->with('status',"Insert successfully");
                }catch(Exception $e){
                    return redirect('admin/talm/add')->with('failed',"operation failed");
                }
        }

    //edit records
    function vieweditrecords($id){
        $rsDistList= Dism::get();
        $data= DB::table('talms')
        ->selectRaw('`Tal_Id`,`Dist_Id`,`Tal`,`Tal_M`')
        ->where('Tal_Id', '=', $id)
        ->get();
        $result = json_decode($data, true);
        return view('talm/edit',['singlerecord'=>$result,'rsDistrict'=>$rsDistList]);
    }

    function editsubmitrecord(Request $req){
        $SQL = DB::table('talms')
        ->where('Tal_Id', $req->Tal_Id)
        ->update(['Dist_Id' => $req->Dist_Id,'Tal' => $req->Tal,'Tal_M' => $req->Tal_M]);
        if($SQL){
            return redirect('admin/talm/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/talm/edit/'.$req->Tal_Id)->with('success','Error in update record.');
        }

     }



}
