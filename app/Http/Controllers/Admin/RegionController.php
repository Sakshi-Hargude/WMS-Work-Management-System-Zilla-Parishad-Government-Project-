<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Talm;
use App\Models\Dism;
use App\Models\Region;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegionController extends Controller
{


    //Add Record Screen Call
    public function createform(){
        $rsDistrictList= Dism::get();
        return view('region/add',['rsDistrict'=>$rsDistrictList]);
    }

    // Retrive All Records
    function allrecords(){
        $data= Region::paginate(10);
        return view('region/list',['region'=>$data]);
    }

    //Delete table Rows
    function delete($Reg_Id){
        $res= DB::table('regions')->where('Reg_Id', '=', $Reg_Id)->delete();
        if ($res){
          return redirect('admin/region/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/region/list')->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
        {
            $data = $request->input();
                try{
                    $SQLNewPKID = DB::table('regions')
                        ->selectRaw('Reg_Id + 1 as Reg_Id')
                        ->orderBy('Reg_Id', 'desc')
                        ->limit(1)
                        ->get();
                    $RSNewPKID = json_decode($SQLNewPKID);
                    if(isset($RSNewPKID[0]->Reg_Id) && !empty($RSNewPKID[0]->Reg_Id)){
                        $PrimaryNumber=$RSNewPKID[0]->Reg_Id;
                    }else{
                        $PrimaryNumber=1;
                    }
                    $objRegion = new Region();
                    $objRegion->Reg_Id  = $PrimaryNumber;
                    $objRegion->Region  = $data['Region'];
                    $objRegion->Region_M  = $data['Region_M'];
                    $objRegion->Dist_Id  = $data['Dist_Id'];
                    $objRegion->save();

                    return redirect('admin/region/add')->with('status',"Insert successfully");
                }catch(Exception $e){
                    return redirect('admin/region/add')->with('failed',"operation failed");
                }
        }

    //edit records
    function vieweditrecords($id){
        $rsDistrictList= Dism::get();
        $data= DB::table('regions')
        ->selectRaw('`Reg_Id`,`Region`,`Region_M`,`Dist_Id`')
        ->where('Reg_Id', '=', $id)
        ->get();
        $result = json_decode($data, true);
        return view('region/edit',['singlerecord'=>$result,'rsDistrict'=>$rsDistrictList]);
    }

    function editsubmitrecord(Request $req){
        $SQL = DB::table('regions')
        ->where('Reg_Id', $req->Reg_Id)
        ->update(['Dist_Id'=>$req->Dist_Id,'Region'=>$req->Region,'Region_M'=>$req->Region_M]);
        if($SQL){
            return redirect('admin/region/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/region/edit/'.$req->Reg_Id)->with('success','Error in update record.');
        }

     }



}
