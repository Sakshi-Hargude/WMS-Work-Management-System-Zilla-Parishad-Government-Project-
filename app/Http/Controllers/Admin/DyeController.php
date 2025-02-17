<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Dyemaster;
use App\Http\Controllers\Controller;
use App\Models\Disms;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DyeController extends Controller
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

       return view('dyemaster/add',['rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);
     }

   // Retrive All Records
   function allrecords(){
        $dsdyelist =  DB::table('dyemasters')
       ->leftJoin('divisions', 'divisions.div_id', '=', 'dyemasters.div_id')
       ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'dyemasters.subdiv_id')
       ->select("dyemasters.div_id","dyemasters.subdiv_id","dyemasters.dye_id","dyemasters.name","dyemasters.name_m","dyemasters.period_from","dyemasters.period_upto","dyemasters.pf_no","dyemasters.phone_no","dyemasters.email","dyemasters.email","divisions.div_m","subdivms.Sub_Div_M")
       ->paginate(10);

       return view('dyemaster/list',compact('dsdyelist'));
   }

   //Delete table Rows
   function delete($id){
       $SQL= DB::table('dyemasters')->where('dye_id', '=', $id)->delete();
       if($SQL){
         return redirect('admin/dyemaster/list')->with('success','Record deleted successfully.');
             }else{
         return redirect('admin/dyemaster/list')->with('success','Error in record.');
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
           $SQLNewPKID = DB::table('dyemasters')
               ->selectRaw('dye_id + 1 as dye_id')
               ->orderBy('dye_id', 'desc')
               ->limit(1)
               ->get();
           $RSNewPKID = json_decode($SQLNewPKID);
           if(isset($RSNewPKID[0]->dye_id) && !empty($RSNewPKID[0]->dye_id)){
               $PrimaryNumber=$RSNewPKID[0]->dye_id;
           }else{
               $PrimaryNumber=1;
           }
           $objdyeMaster = new dyemaster();
           $RSNewPKID = json_decode($SQLNewPKID);
           $objdyeMaster->dye_id  = $PrimaryNumber;
           $objdyeMaster->div_id  = $request->div_id;
           $objdyeMaster->subdiv_id  = $request->subdiv_id;
           $objdyeMaster->name  = $request->name?$request->name:'';
           $objdyeMaster->name_m  = $request->name_m?$request->name_m:'';
           $objdyeMaster->period_from  = $request->period_from;
           $objdyeMaster->period_upto  = $request->period_upto;
           $objdyeMaster->pf_no  = $request->pf_no?$request->pf_no:'';
           $objdyeMaster->phone_no  = $request->phone_no?$request->phone_no:'';
           $objdyeMaster->email  = $request->email?$request->email:'';
           $objdyeMaster->save();

             //User master Insert.
             $objuser = new User();
             $objuser->name = $request->name?$request->name:'';
             $objuser->email = $request->email?$request->email:'';
             $objuser->mobileno= $request->phone_no?$request->phone_no:'';
             $objuser->Usernm= $request->Usernm?$request->Usernm:'';
             $objuser->usertypes= 'DYE';
             $objuser->refid = $PrimaryNumber;
             $objuser->password = Hash::make($request->password);
             $objuser->save();

           return redirect('admin/dyemaster/list')->with('status',"Insert successfully");
       }catch(Exception $e){
           return redirect('admin/dyemaster/add')->with('failed',"operation failed");
       }

   }

   //edit records
   function vieweditrecords($id){
       $data= DB::table('dyemasters')
       ->leftJoin('divisions', 'divisions.div_id', '=', 'dyemasters.div_id')
       ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'dyemasters.subdiv_id')
       ->select("dyemasters.div_id","dyemasters.subdiv_id","dyemasters.dye_id","dyemasters.name","dyemasters.name_m","dyemasters.period_from","dyemasters.period_upto","dyemasters.pf_no","dyemasters.phone_no","dyemasters.email","dyemasters.email")
       ->where('dye_id', '=', $id)
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

       return view('dyemaster/edit',['singlerecord'=>$data,'rsDivision'=>$rsDivisionDtls,'rssubdivisions'=>$rsSubDivisionDtls]);

   }

   function editsubmitrecord(Request $req){
       $objdyeMaster = new dyemaster();
       $dye_id  = $req->dye_id;
       $div_id  = $req->div_id;
       $subdiv_id  = $req->subdiv_id;
       $name  = $req->name;
       $name_m  = $req->name_m;
       $period_from  = $req->period_from;
       $period_upto  = $req->period_upto;
       $pf_no  = $req->pf_no;
       $phone_no  = $req->phone_no;
       $email  = $req->email;


       $sQLUpdate = DB::table( 'dyemasters' )
       ->where( 'dye_id', '=',  $dye_id)
       ->update( [ 'div_id' => $div_id ,'subdiv_id' => $subdiv_id,'name' => $name,'name_m' => $name,'period_from' => $period_from,'period_upto' => $period_upto,'pf_no' => $pf_no,'phone_no' => $phone_no,'email' => $email] );

       if($sQLUpdate){
           return redirect('admin/dyemaster/list')->with('success','Record Updated successfully.');
       }else{
           return redirect('admin/dyemaster/edit/'.$req->eeid)->with('success','Error in update record.');
       }

    }
}
