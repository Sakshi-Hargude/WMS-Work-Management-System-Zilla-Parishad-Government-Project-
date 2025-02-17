<?php

    namespace App\Http\Controllers\Admin;
    use Illuminate\Support\Facades\DB;
use App\Models\Talm;
use App\Models\Dism;
use App\Models\Region;
use App\Models\Circle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CircleController extends Controller
{

    //Add Record Screen Call
    public function createform(){
        $rsRegionList= Region::get();
        $rsDistrictList= Dism::get();
        return view('circle/add',['rsRegion'=>$rsRegionList,'rsDistrict'=>$rsDistrictList]);
    }

    // Retrive All Records
    function allrecords(){
        $data= Circle::paginate(10);
        return view('circle/list',['circle'=>$data]);
    }

    //Delete table Rows
    function delete($Cir_Id){
        $res= DB::table('circles')->where('Cir_Id', '=', $Cir_Id)->delete();
        if ($res){
          return redirect('admin/circle/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/circle/list')->with('success','Error in record.');
          }
    }

    //Create New Records
    function InsertTB(Request $request)
        {
            $data = $request->input();
                try{
                    $SQLNewPKID = DB::table('circles')
                        ->selectRaw('Cir_Id + 1 as Cir_Id')
                        ->orderBy('Cir_Id', 'desc')
                        ->limit(1)
                        ->get();
                    $RSNewPKID = json_decode($SQLNewPKID);
                    if(isset($RSNewPKID[0]->Cir_Id) && !empty($RSNewPKID[0]->Cir_Id)){
                        $PrimaryNumber=$RSNewPKID[0]->Cir_Id;
                    }else{
                        $PrimaryNumber=1;
                    }
                    $objCircle = new Circle();
                    $objCircle->Cir_Id  = $PrimaryNumber;
                    $objCircle->Reg_Id  = $data['Reg_Id'];
                    $objCircle->Circle  = $data['Circle'];
                    $objCircle->Circle_M  = $data['Circle_M'];
                    $objCircle->Dist_Id  = $data['Dist_Id'];
                    $objCircle->save();

                    return redirect('admin/circle/add')->with('status',"Insert successfully");
                }catch(Exception $e){
                    return redirect('admin/circle/add')->with('failed',"operation failed");
                }
        }

    //View Edit Screen
    function vieweditrecords($id){
        $rsRegionList= Region::get();
        $rsDistrictList= Dism::get();
        $data= DB::table('circles')
        ->selectRaw('`Reg_Id`,`Cir_Id`,`Circle`,`Circle_M`,`Dist_Id`')
        ->where('Cir_Id', '=', $id)
        ->get();
        $result = json_decode($data, true);
        return view('circle/edit',['singlerecord'=>$result,'rsRegion'=>$rsRegionList,'rsDistrict'=>$rsDistrictList]);
    }

    //Edit Submit Records
    function editsubmitrecord(Request $req){
        $SQL = DB::table('circles')
        ->where('Cir_Id', $req->Cir_Id)
        ->update(['Reg_Id'=>$req->Reg_Id,'Circle'=>$req->Circle,'Circle_M'=>$req->Circle_M,'Dist_Id'=>$req->Dist_Id]);
        if($SQL){
            return redirect('admin/circle/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/circle/edit/'.$req->Cir_Id)->with('success','Error in update record.');
        }

     }

}
