<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Jemaster;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Disms;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class JeController extends Controller
{
    
     //Division List

     //Sub Division List

     //Add Record Screen Call
      public function createform(){
         $divid = 141;

         // Get Division ID To Division Name
         $rsDivisionDtls = DB::table('divisions')
         ->selectRaw('`div_m`,`div`,`div_id`')
         ->where('div_id','=',$divid)->get();


        //Get Selected Divisions All Subdivisions
         $rsSubDivisionDtls = DB::table('subdivms')
         ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
         ->where('Div_Id','=',$divid)->get();
         $rsWorkType = DB::table('worktypes')
         ->selectRaw('`id`,`worktype`')
         ->get();

        return view('jemaster/add',['rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);
      }

    // Retrive All Records
    function allrecords(){
        $dsjelist = DB::table('jemasters')
        ->leftJoin('divisions', 'divisions.div_id', '=', 'jemasters.div_id')
        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'jemasters.subdiv_id')
        ->select("jemasters.div_id","jemasters.subdiv_id","jemasters.jeid","jemasters.name","jemasters.name_m","jemasters.period_from","jemasters.period_upto","jemasters.pf_no","jemasters.phone_no","jemasters.email","divisions.div_m","subdivms.Sub_Div_M")
        ->paginate(10);
        return view('jemaster/list',compact('dsjelist'));
    }

    //Delete table Rows
    function delete($id){
        $SQL= DB::table('jemasters')->where('jeid', '=', $id)->delete();
        if($SQL){
          return redirect('admin/jemaster/list')->with('success','Record deleted successfully.');
              }else{
          return redirect('admin/jemaster/list')->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
    {
        try{
            $request->validate([
                'Usernm' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_no'=> 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                'password' => 'required|string|confirmed|min:8',
            ]);
            $SQLNewPKID = DB::table('jemasters')
                ->selectRaw('jeid + 1 as jeid')
                ->orderBy('jeid', 'desc')
                ->limit(1)
                ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->jeid) && !empty($RSNewPKID[0]->jeid)){
                $PrimaryNumber=$RSNewPKID[0]->jeid;
            }else{
                $PrimaryNumber=1;
            }
            $objJeMaster = new Jemaster();
            $RSNewPKID = json_decode($SQLNewPKID);
            $objJeMaster->jeid  = $PrimaryNumber;
            $objJeMaster->div_id  = $request->div_id;
            $objJeMaster->subdiv_id  = $request->subdiv_id;
            $objJeMaster->name  = $request->name?$request->name:'';
            $objJeMaster->name_m  = $request->name?$request->name:'';
            $objJeMaster->period_from  = $request->period_from;
            $objJeMaster->period_upto  = $request->period_upto;
            $objJeMaster->pf_no  = $request->pf_no?$request->pf_no:'';
            $objJeMaster->phone_no  = $request->phone_no?$request->phone_no:'';
            $objJeMaster->email  = $request->email?$request->email:'';
            $objJeMaster->save();
            //User master Insert.
            $objuser = new User();
            $objuser->name = $request->name?$request->name:'';
            $objuser->email = $request->email?$request->email:'';
            $objuser->mobileno= $request->phone_no?$request->phone_no:'';
            $objuser->Usernm= $request->Usernm?$request->Usernm:'';
            $objuser->usertypes= 'JE';
            $objuser->password = Hash::make($request->password);
            $objuser->save();

            return redirect('admin/jemaster/list')->with('status',"Insert successfully");
        }catch(Exception $e){
            return redirect('admin/jemaster/add')->with('failed',"operation failed");
        }

    }

    //edit records
    function vieweditrecords($id){
        $data= DB::table('jemasters')
        ->leftJoin('divisions', 'divisions.div_id', '=', 'jemasters.div_id')
        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'jemasters.subdiv_id')
        ->select("jemasters.div_id","jemasters.subdiv_id","jemasters.jeid","jemasters.name","jemasters.name_m","jemasters.period_from","jemasters.period_upto","jemasters.pf_no","jemasters.phone_no","jemasters.email","divisions.div_m","subdivms.Sub_Div_M")
        ->where('jeid', '=', $id)
        ->first();

        $divid = 141;

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();


       //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();

        return view('jemaster/edit',['singlerecord'=>$data,'rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);

    }

    function editsubmitrecord(Request $req){
        $data = $req->input();

        $objJeMaster = new Jemaster();
        $jeid  = $req->jeid;
        $div_id  = $req->div_id;
        $subdiv_id  = $req->subdiv_id;
        $name  = $req->name?$req->name:'';
        $name_m  = $req->name_m?$req->name_m:'';
        $period_from  = $req->period_from;
        $period_upto  = $req->period_upto;
        $pf_no  = $req->pf_no?$req->pf_no:'';
        $phone_no  = $req->phone_no?$req->phone_no:'';
        $email  = $req->email?$req->email:'';


        $sQLUpdate = DB::table( 'jemasters' )
        ->where( 'jeid', '=',  $jeid)
        ->update( [ 'div_id' => $div_id ,'subdiv_id' => $subdiv_id,'name' => $name,'name_m' => $name,'period_from' => $period_from,'period_upto' => $period_upto,'pf_no' => $pf_no,'phone_no' => $phone_no,'email' => $email] );

        if($sQLUpdate){
            return redirect('admin/jemaster/list')->with('success','Record Updated successfully.');
        }else{
            return redirect('admin/jemaster/edit/'.$req->eeid)->with('success','Error in update record.');
        }

     }

}
