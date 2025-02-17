<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Rabill;
use Illuminate\Http\Request;

class RabillController extends Controller
{

    //Add Record Screen Call
    public function createform(){
        $rsRabillList= Rabill::get();
        
        return view('rabills/add',['rsRabill'=>$rsRabillList]);
    }

    // Retrive All Records
    function allrecords(){
        $data= Rabill::paginate(10);
        return view('rabills/list',['rabill'=>$data]);
    }

    //Delete table Rows
    function deleteRaBill($id,$eid){
        //dd($request->('id'));
        $res= DB::table('rabills')->where('bill_id', '=', $id)->delete();
        if ($res){
          return redirect('RABillEdit/'.$eid)->with('success','Record deleted successfully.');
              }else{
          return redirect('RABillEdit/'.$eid)->with('success','Error in record.');
          }
    }
    //Insert Database
    function InsertTB(Request $request)
        {  //dd($request);
         try{
            //Check Duplication in RA 
            request()->validate([
                'pr_rep_no' => 'required|unique:progressreports'
            ]);

            if($request->final_bill && $request->final_bill == 'on'){
                $this->validate($request,[
                    'is_work_complete'=>'required',
                    'actual_complete_date'=>'required'
                ]);
            }

            $SQLNewPKID = DB::table('rabills')
                    ->selectRaw('bill_id + 1 as bill_id')
                    ->orderBy('bill_id', 'desc')
                    ->limit(1)
                    ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->bill_id) && !empty($RSNewPKID[0]->bill_id)){
                $PrimaryNumber=$RSNewPKID[0]->bill_id;
            }else{
                $PrimaryNumber=1;
            }
            $objRabill = new Rabill();
            $objRabill->bill_id  = $PrimaryNumber;
            $objRabill->work_id  = $request->Work_Id;
            $objRabill->bill_no  = $request->bill_no??'';
            $objRabill->final_bill  = $request->final_bill??'OFF';
            $objRabill->bill_date  = $request->bill_date??null;
            $objRabill->bill_amt  = $request->bill_amt??0;
            $objRabill->other_exp  = $request->other_exp??0;
            $objRabill->tot_exp  = $request->tot_exp??0;
            $objRabill->tot_ded  = $request->tot_ded??0;
            $objRabill->pay_amt  = $request->pay_amt??0;
            $objRabill->prev_amt  = $request->prev_amt??0;
            $objRabill->chq_amt = 0;
            $objRabill->voucher_no  = $request->voucher_no??'';
            $objRabill->voucher_dt  = $request->voucher_dt??null; 
            $objRabill->save(); 
            //Get RA Bill List
            $rsWorkidRabillList= Rabill::where('work_id','=',$request->Work_Id)->get();
            //Update RA Bill Details
            
            $is_work_complete = 0;
            if($request->is_work_complete){
                $is_work_complete = 1;
            }

            $WorkMasterUpdated = DB::table('workmasters')
            ->where('Work_Id', $request->Work_Id)
            ->update(['is_work_complete' => $is_work_complete??0,'actual_complete_date' => $request->actual_complete_date??null,'percentage_complete' => $request->percentagecomplete??0,'Tot_Exp'=>$request->tot_exp??0]);
            return redirect('RABillEdit/'.$request->Eid)->with('status',"Insert successfully");

            }catch(Exception $e){
               return redirect('RABillEdit/'.$request->Eid,compact('rsWorkidRabillList'))->with('failed',"operation failed");
            }
        }

    //edit records
    function vieweditrecords($id){
        try {
            //code...
      
        $rsRabillDetails= Rabill::get();
        $data= DB::table('rabills')
        ->selectRaw('`rabills`.`work_id`,
        `rabills`.`bill_id`,
        `rabills`.`bill_no`,
        `rabills`.`final_bill`,
        `rabills`.`bill_date`,
        `rabills`.`created_at`,
        `rabills`.`updated_at`,
        `rabills`.`bill_amt`,
        `rabills`.`other_exp`,
        `rabills`.`tot_exp`,
        `rabills`.`pay_amt`,
        `rabills`.`prev_amt`,
        `rabills`.`voucher_no`,
        `rabills`.`tot_ded`,
        `rabills`.`voucher_dt`,
        `rabills`.`chq_amt`')
        ->where('bill_id', '=', $id)
        ->get();
        $result = json_decode($data, true);
        return view('region/edit',['singlerecord'=>$result,'rsRabills'=>$rsRabillDetails]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    function editsubmitrecord(Request $req){ 
       try { 
            // Ra Bill Work Completed Date ---------------------------------------------
            $is_work_complete = 0;
            if($req->is_work_complete){
                $is_work_complete = 1;
            }

            $WorkMasterUpdated = DB::table('workmasters')
            ->where('Work_Id', $req->Work_Id)
            ->update(['is_work_complete' => $is_work_complete??0,'actual_complete_date' => $req->actual_complete_date??null,'percentage_complete' => $req->percentagecomplete??0,'Tot_Exp'=>$req->tot_exp??0]);

            // Ra Bill Work Completed Date ---------------------------------------------


            $SQL = DB::table('rabills')
            ->where('bill_id', $req->id)
            ->update(['bill_no'=>$req->bill_no??0,'final_bill'=>$req->final_bill??'OFF','bill_date'=>$req->bill_date??null,'bill_amt'=>$req->bill_amt??0,'other_exp'=>$req->other_exp??0,'tot_exp'=>$req->tot_exp??0,'pay_amt'=>$req->pay_amt??0,'prev_amt'=>$req->prev_amt??0,'voucher_no'=>$req->voucher_no??'','voucher_dt'=>$req->voucher_dt??null,'tot_ded'=>$req->tot_ded??0 ]);

            if($SQL){
                    return redirect('RABillEdit/'.$req->eid)->with('success','Record Updated successfully.');
            }else{
                    return redirect('RABillEdit/'.$req->eid)->with('success','Error in update record.');
            }

         } catch (\Throwable $th) {
            return redirect('RABillEdit/'.$req->eid)->with('success',$th);
         }

    }

    function editRaBill($id){
        if($id){
            $dataRaBillDetails = DB::table('rabills')
            ->selectRaw('`rabills`.`work_id`,
            `rabills`.`bill_id`,
            `rabills`.`bill_no`,
            `rabills`.`final_bill`,
            `rabills`.`bill_date`,
            `rabills`.`created_at`,
            `rabills`.`updated_at`,
            `rabills`.`bill_amt`,
            `rabills`.`other_exp`,
            `rabills`.`tot_exp`,
            `rabills`.`pay_amt`,
            `rabills`.`tot_ded`,
            `rabills`.`voucher_no`,
            `rabills`.`voucher_dt`,
            `rabills`.`chq_amt`,
            `rabills`.`prev_amt`')
            ->where('bill_id', '=', $id)
            ->first();



        //Last Workid Transaction
        $rsWorkidLastBillGenrated =   DB::table('rabills')
        ->selectRaw('`rabills`.`work_id`,
        `rabills`.`bill_id`,
        `rabills`.`bill_no`,
        `rabills`.`final_bill`,
        `rabills`.`bill_date`,
        `rabills`.`created_at`,
        `rabills`.`updated_at`,
        `rabills`.`bill_amt`,
        `rabills`.`other_exp`,
        `rabills`.`tot_exp`,
        `rabills`.`pay_amt`,
        `rabills`.`tot_ded`,
        `rabills`.`voucher_no`,
        `rabills`.`voucher_dt`,
        `rabills`.`prev_amt`')
        ->where('bill_id', '<=', $id)
        ->orderBy('bill_id','desc')
        ->skip(1)->take(1)->first();
        
        }else{
            $dataRaBillDetails = null;
            $rsWorkidLastBillGenrated =   null;
        }

        return view('rabills/editRaBill',compact('dataRaBillDetails','rsWorkidLastBillGenrated'));
    }


}
