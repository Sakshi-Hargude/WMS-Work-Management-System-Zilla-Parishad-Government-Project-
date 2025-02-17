<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Eemaster;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Disms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Providers\RouteServiceProvider;

class EeController extends Controller
{
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
        return view('eemaster/add',['rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);
        
     }

   // Retrive All Records
   function allrecords(){
        $dsjelist= DB::table('eemasters')
       ->leftJoin('divisions', 'divisions.div_id', '=', 'eemasters.divid')
       ->select("eemasters.divid","eemasters.eeid","eemasters.name","eemasters.name_m","eemasters.period_from","eemasters.period_upto","eemasters.pf_no","eemasters.phone_no","eemasters.email","divisions.div_m")
       ->paginate(10);
       //dd($data);
       //$data= eemaster::paginate(10);
       return view('eemaster/list',compact('dsjelist'));
   }

   //Delete table Rows
   function delete($id){
       $SQL= DB::table('eemasters')->where('eeid', '=', $id)->delete();
       if($SQL){
         return redirect('admin/eemaster/list')->with('success','Record deleted successfully.');
             }else{
         return redirect('admin/eemaster/list')->with('success','Error in record.');
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
            $SQLNewPKID = DB::table('eemasters')
                ->selectRaw('eeid + 1 as eeid')
                ->orderBy('eeid', 'desc')
                ->limit(1)
                ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
           if(isset($RSNewPKID[0]->eeid) && !empty($RSNewPKID[0]->eeid)){
               $PrimaryNumber=$RSNewPKID[0]->eeid;
           }else{
               $PrimaryNumber=1;
           }
           $objeeMaster = new eemaster();
           $RSNewPKID = json_decode($SQLNewPKID);
           $objeeMaster->eeid  = $PrimaryNumber;
           $objeeMaster->divid  = $request->divid;
           $objeeMaster->name  = $request->name?$request->name:'';
           $objeeMaster->name_m  = $request->name_m?$request->name_m:'';
           $objeeMaster->period_from  = $request->period_from;
           $objeeMaster->period_upto  = $request->period_upto;
           $objeeMaster->pf_no  = $request->pf_no?$request->pf_no:'';
           $objeeMaster->phone_no  = $request->phone_no?$request->phone_no:'';
           $objeeMaster->email  = $request->email?$request->email:'';
           $objeeMaster->save();

           //User master Insert.
           $objuser = new User();
           $objuser->name = $request->name?$request->name:'';
           $objuser->email = $request->email?$request->email:'';
           $objuser->mobileno= $request->phone_no?$request->phone_no:'';
           $objuser->Usernm= $request->Usernm?$request->Usernm:'';
           $objuser->usertypes= 'EE';
           $objuser->password = Hash::make($request->password);
           $objuser->save();

           return redirect('admin/eemaster/list')->with('status',"Insert successfully");
       }catch(Exception $e){
           return redirect('admin/eemaster/add')->with('failed',"operation failed");
       }

   }

   //edit records
   function vieweditrecords($id){
       $data= DB::table('eemasters')
       ->leftJoin('divisions', 'divisions.div_id', '=', 'eemasters.divid')
       ->select("eemasters.divid","eemasters.eeid","eemasters.name","eemasters.name_m","eemasters.period_from","eemasters.period_upto","eemasters.pf_no","eemasters.phone_no","eemasters.email","divisions.div_m")
       ->where('eemasters.eeid', '=', $id)
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

       return view('eemaster/edit',['singlerecord'=>$data,'rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);

   }

   function editsubmitrecord(Request $req){
       $data = $req->input();

       $objeeMaster = new eemaster();
       $eeid  = $data['eeid'];
       $divid  = $data['divid'];
       $name  = $data['name'];
       $name_m  = $data['name_m'];
       $period_from  = $data['period_from'];
       $period_upto  = $data['period_upto'];
       $pf_no  = $data['pf_no'];
       $phone_no  = $data['phone_no'];
       $email  = $data['email'];


       $sQLUpdate = DB::table( 'eemasters' )
       ->where( 'eeid', '=',  $eeid)
       ->update( [ 'divid' => $divid ,'name' => $name,'name_m' => $name,'period_from' => $period_from,'period_upto' => $period_upto,'pf_no' => $pf_no,'phone_no' => $phone_no,'email' => $email] );

       if($sQLUpdate){
           return redirect('admin/eemaster/list')->with('success','Record Updated successfully.');
       }else{
           return redirect('admin/eemaster/edit/'.$req->eeid)->with('success','Error in update record.');
       }

    }
}
