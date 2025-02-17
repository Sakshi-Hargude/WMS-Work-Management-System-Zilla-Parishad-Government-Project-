<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Progressreport;
use App\Models\Rabill;
use Illuminate\Http\Request;


class ProgressreportController extends Controller
{

    public $fileNamephoto11='', $fileNamephoto21='',$fileNamephoto31='',$fileNamedocument11='',$fileNamedocument21='',$fileNamedocument31='' , $fileNamevideo='',$fileNamevideo55='',$filepathvideo='';

    //Add Record Screen Call
    public function createform(){
        $rsProgressreportList= Progressreport::get();
        return view('progressreport/add',['rsProgressreportList'=>$rsProgressreportList]);
    }

    // Retrive All Records
    function allrecords(){
        $data= Progressreport::paginate(10);
        return view('progressreport/list',['rabill'=>$data]);
    }

    //Delete table Rows
    function deleteProgressReport($progressreport_Id,$eid){
        $res= DB::table('progressreports')->where('per_rep_id', '=', $progressreport_Id)->delete();
        if ($res){
          return redirect('ProgressReportEdit/'.$eid)->with('success','Record deleted successfully.');
              }else{
          return redirect('ProgressReportEdit/'.$eid)->with('success','Error in record.');
          }
    }

    //Insert Database
    function InsertTB(Request $request)
        {
                $data = $request->input();
                try{
                    $SQLNewPKID = DB::table('progressreports')
                        ->selectRaw('per_rep_id + 1 as per_rep_id')
                        ->orderBy('per_rep_id', 'desc')
                        ->limit(1)
                        ->get();
                    $RSNewPKID = json_decode($SQLNewPKID);
                    if(isset($RSNewPKID[0]->per_rep_id) && !empty($RSNewPKID[0]->per_rep_id)){
                        $PrimaryNumber=$RSNewPKID[0]->per_rep_id;
                    }else{
                        $PrimaryNumber=1;
                    }

                    $filephoto1_Path = $request->file('photo1');
                    $filephoto2_Path = $request->file('photo2');
                    $filephoto3_Path = $request->file('photo3');

                    if($filephoto1_Path){
                        $filenamephoto1 = time().$filephoto1_Path->getClientOriginalName();
                        $extensionphoto1 = $filephoto1_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathphoto1 = $filephoto1_Path->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationphoto1 = 'uploads/photo1'; //Created an "uploads" folder for that
                        // Upload file
                        $filephoto1_Path->move($locationphoto1, $filenamephoto1);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamephoto11 = $filenamephoto1;
                        $this->filepathphoto1 = public_path($locationphoto1 . "/" . $this->fileNamephoto11);
                    }

                    if($filephoto2_Path){
                        $filenamephoto2 = time().$filephoto2_Path->getClientOriginalName();
                        $extensionphoto2 = $filephoto2_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathphoto2 = $filephoto2_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationphoto2 = 'uploads/photo2'; //Created an "uploads" folder for that
                            // Upload file
                            $filephoto2_Path->move($locationphoto2, $filenamephoto2);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamephoto21 = $filenamephoto2;
                            $this->filepathphoto2 = public_path($locationphoto2 . "/" . $this->fileNamephoto21);
                    }

                    if($filephoto3_Path){
                        $filenamephoto3 = time().$filephoto3_Path->getClientOriginalName();
                        $extensionphoto3 = $filephoto3_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathphoto3 = $filephoto3_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationphoto3 = 'uploads/photo3'; //Created an "uploads" folder for that
                            // Upload file
                            $filephoto3_Path->move($locationphoto3, $filenamephoto3);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamephoto31 = $filenamephoto3;
                            $this->filepathphoto3 = public_path($locationphoto3 . "/" . $this->fileNamephoto31);
                    }



                    $filedocument1_Path = $request->file('document1');
                    $filedocument2_Path = $request->file('document2');
                    $filedocument3_Path = $request->file('document3');

                    if($filedocument1_Path){
                        $filenamedocument1 = time().$filedocument1_Path->getClientOriginalName();
                        $extensiondocument1 = $filedocument1_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathdocument1 = $filedocument1_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationdocument1 = 'uploads/document1'; //Created an "uploads" folder for that
                            // Upload file
                            $filedocument1_Path->move($locationdocument1, $filenamedocument1);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamedocument11 = $filenamedocument1;
                            $this->filepathdocument1 = public_path($locationdocument1 . "/" . $this->fileNamedocument11);
                    }


                    if($filedocument2_Path){
                        $filenamedocument2 = time().$filedocument2_Path->getClientOriginalName();
                        $extensiondocument2 = $filedocument2_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathdocument2 = $filedocument2_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationdocument2 = 'uploads/document2'; //Created an "uploads" folder for that
                            // Upload file
                            $filedocument2_Path->move($locationdocument2, $filenamedocument2);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamedocument21 = $filenamedocument2;
                            $this->filepathdocument2 = public_path($locationdocument2 . "/" . $this->fileNamedocument21);
                    }

                    if($filedocument2_Path){
                        $filenamedocument2 = time().$filedocument2_Path->getClientOriginalName();
                        $extensiondocument2 = $filedocument2_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathdocument2 = $filedocument2_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationdocument2 = 'uploads/document2'; //Created an "uploads" folder for that
                            // Upload file
                            $filedocument2_Path->move($locationdocument2, $filenamedocument2);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamedocument21 = $filenamedocument2;
                            $this->filepathdocument2 = public_path($locationdocument2 . "/" . $this->fileNamedocument21);
                    }


                    if($filedocument3_Path){
                        $filenamedocument3 = time().$filedocument3_Path->getClientOriginalName();
                        $extensiondocument3 = $filedocument3_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathdocument3 = $filedocument3_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationdocument3 = 'uploads/document3'; //Created an "uploads" folder for that
                            // Upload file
                            $filedocument3_Path->move($locationdocument3, $filenamedocument3);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamedocument31 = $filenamedocument3;
                            $this->filepathdocument3 = public_path($locationdocument3 . "/" . $this->fileNamedocument31);
                    }

                    $objProgressreport = new Progressreport();
                    $objProgressreport->per_rep_id  = $PrimaryNumber;
                    $objProgressreport->work_id  = $data['Work_Id']?$data['Work_Id']:'';
                    $objProgressreport->report_id  = $data['report_id']?$data['report_id']:'';
                    $objProgressreport->pr_rep_no  = $data['pr_rep_no']?$data['pr_rep_no']:'';
                    $objProgressreport->per_rep_by  = $data['per_rep_by']?$data['per_rep_by']:'';
                    $objProgressreport->pre_report_dt  = $data['pre_report_dt']?$data['pre_report_dt']:'';
                    $objProgressreport->status  = $data['status']?$data['status']:'';
                    $objProgressreport->photo1  = $this->fileNamephoto11?$this->fileNamephoto11:'';
                    $objProgressreport->photo2  =  $this->fileNamephoto21? $this->fileNamephoto21:'';
                    $objProgressreport->photo3  = $this->fileNamephoto31?$this->fileNamephoto31:'';
                    $objProgressreport->document1  = $this->fileNamedocument11?$this->fileNamedocument11:'';
                    $objProgressreport->document2  = $this->fileNamedocument21?$this->fileNamedocument21:'';
                    $objProgressreport->document3  = $this->fileNamedocument31?$this->fileNamedocument31:'';
                    $objProgressreport->is_final_report  = $data['is_final_report']??'OFF';
                    $objProgressreport->save();



                    $is_work_complete = 0;
                    if($request->is_work_complete){
                        $is_work_complete = 1;
                    }

                    $WorkMasterUpdated = DB::table('workmasters')
                    ->where('Work_Id', $request->Work_Id)
                    ->update(['is_work_complete' => $is_work_complete??0,'actual_complete_date' => $request->actual_complete_date??null]);


                    return redirect('ProgressReportEdit/'.$request->Eid)->with('status',"Insert successfully");

                }catch(Exception $e){
                    return redirect('ProgressReportEdit/'.$request->Eid)->with('failed',"operation failed");
                }
        }

    //edit records
    function editProgressReport($id){


        // login user session Data----------------------------
        $divid = auth()->user()->Div_id;
        $usercode = auth()->user()->usercode;
        $subdivid = auth()->user()->Sub_Div_id;
        // login user session Data----------------------------

        $rsReportedByUserList= DB::table('users')
            ->whereIn('usertypes',['EE','JE','DYE'])
            ->when($divid, function ($query) use ($divid) {
        return $query->where('Div_id','=',$divid);
        })
            ->when($subdivid, function ($query) use ($subdivid) {
        return $query->where('Sub_Div_id','=',$subdivid);
        })
            ->get();

        $rsprogressReportDtls= DB::table('progressreports')
        ->selectRaw('`progressreports`.`work_id`,
        `progressreports`.`per_rep_id`,
        `progressreports`.`pr_rep_no`,
        `progressreports`.`per_rep_by`,
        `progressreports`.`report_id`,
        `progressreports`.`pre_report_dt`,
        `progressreports`.`status`,
        `progressreports`.`photo1`,
        `progressreports`.`photo2`,
        `progressreports`.`photo3`,
        `progressreports`.`lat1`,
         `progressreports`.`long1`,
          `progressreports`.`lat2`,
           `progressreports`.`long2`,
           `progressreports`.`lat3`,
                      `progressreports`.`long3`,


        `progressreports`.`document1`,
        `progressreports`.`document2`,
        `progressreports`.`document3`,
         `progressreports`.`vid`,
        `progressreports`.`is_final_report`')
        ->where('per_rep_id', '=', $id)
        ->first();
        return view('progressreports/editProgressReport',compact('rsprogressReportDtls','rsReportedByUserList'));
    }

    function editProgressReport1($id){


        // login user session Data----------------------------
        $divid = auth()->user()->Div_id;
        $usercode = auth()->user()->usercode;
        $subdivid = auth()->user()->Sub_Div_id;
        // login user session Data----------------------------

        $rsReportedByUserList= DB::table('users')
            ->whereIn('usertypes',['EE','JE','DYE'])
            ->when($divid, function ($query) use ($divid) {
        return $query->where('Div_id','=',$divid);
        })
            ->when($subdivid, function ($query) use ($subdivid) {
        return $query->where('Sub_Div_id','=',$subdivid);
        })
            ->get();

        $rsprogressReportDtls= DB::table('progressreports')
        ->selectRaw('`progressreports`.`work_id`,
        `progressreports`.`per_rep_id`,
        `progressreports`.`pr_rep_no`,
        `progressreports`.`per_rep_by`,
        `progressreports`.`report_id`,
        `progressreports`.`pre_report_dt`,
        `progressreports`.`status`,
        `progressreports`.`photo1`,
        `progressreports`.`photo2`,
        `progressreports`.`photo3`,
        `progressreports`.`lat1`,
         `progressreports`.`long1`,
          `progressreports`.`lat2`,
           `progressreports`.`long2`,
           `progressreports`.`lat3`,
                      `progressreports`.`long3`,


        `progressreports`.`document1`,
        `progressreports`.`document2`,
        `progressreports`.`document3`,
         `progressreports`.`vid`,
        `progressreports`.`is_final_report`')
        ->where('per_rep_id', '=', $id)
        ->first();
        return view('progressreports/edit1',compact('rsprogressReportDtls','rsReportedByUserList'));
    }

    function editsubmitrecord(Request $request){
        try {
        // dd($request);
        $filephoto1_Path = $request->file('photo1');
        $filephoto2_Path = $request->file('photo2');
        $filephoto3_Path = $request->file('photo3');

        // Photo 1 Handling
        if ($filephoto1_Path) {
            // Delete old photo if it exists
            if (!empty($request->oldphoto1)) {
                $oldPhoto1Path = public_path('uploads/photo1/' . $request->oldphoto1);
                if (file_exists($oldPhoto1Path)) {
                    unlink($oldPhoto1Path); // Remove old photo
                }
            }

            $filenamephoto1 = time() . $filephoto1_Path->getClientOriginalName();
            $locationphoto1 = 'uploads/photo1'; // Upload location
            $filephoto1_Path->move($locationphoto1, $filenamephoto1); // Move new file
            $this->fileNamephoto11 = $filenamephoto1;
            $this->filepathphoto1 = public_path($locationphoto1 . "/" . $this->fileNamephoto11);
        } else {
            $this->fileNamephoto11 = $request->oldphoto1 ? $request->oldphoto1 : '';
        }

        // Photo 2 Handling
        if ($filephoto2_Path) {
            // Delete old photo if it exists
            if (!empty($request->oldphoto2)) {
                $oldPhoto2Path = public_path('uploads/photo2/' . $request->oldphoto2);
                if (file_exists($oldPhoto2Path)) {
                    unlink($oldPhoto2Path); // Remove old photo
                }
            }

            $filenamephoto2 = time() . $filephoto2_Path->getClientOriginalName();
            $locationphoto2 = 'uploads/photo2'; // Upload location
            $filephoto2_Path->move($locationphoto2, $filenamephoto2); // Move new file
            $this->fileNamephoto21 = $filenamephoto2;
            $this->filepathphoto2 = public_path($locationphoto2 . "/" . $this->fileNamephoto21);
        } else {
            $this->fileNamephoto21 = $request->oldphoto2 ? $request->oldphoto2 : '';
        }

        $filedocument1_Path = $request->file('document1');
        $filedocument2_Path = $request->file('document2');
        $filedocument3_Path = $request->file('document3');

        if ($filedocument1_Path) {
            // Remove old file if exists
            if ($request->olddocument1 && file_exists(public_path('uploads/document1/' . $request->olddocument1))) {
                unlink(public_path('uploads/document1/' . $request->olddocument1));
            }

            $filenamedocument1 = time() . $filedocument1_Path->getClientOriginalName();
            $locationdocument1 = 'uploads/document1';
            $filedocument1_Path->move($locationdocument1, $filenamedocument1);
            $this->fileNamedocument11 = $filenamedocument1;
        } else {
            $this->fileNamedocument11 = $request->olddocument1 ? $request->olddocument1 : '';
        }

        if ($filedocument2_Path) {
            // Remove old file if exists
            if ($request->olddocument2 && file_exists(public_path('uploads/document2/' . $request->olddocument2))) {
                unlink(public_path('uploads/document2/' . $request->olddocument2));
            }

            $filenamedocument2 = time() . $filedocument2_Path->getClientOriginalName();
            $locationdocument2 = 'uploads/document2';
            $filedocument2_Path->move($locationdocument2, $filenamedocument2);
            $this->fileNamedocument21 = $filenamedocument2;
        } else {
            $this->fileNamedocument21 = $request->olddocument2 ? $request->olddocument2 : '';
        }

        if ($filedocument3_Path) {
            // Remove old file if exists
            if ($request->olddocument3 && file_exists(public_path('uploads/document3/' . $request->olddocument3))) {
                unlink(public_path('uploads/document3/' . $request->olddocument3));
            }

            $filenamedocument3 = time() . $filedocument3_Path->getClientOriginalName();
            $locationdocument3 = 'uploads/document3';
            $filedocument3_Path->move($locationdocument3, $filenamedocument3);
            $this->fileNamedocument31 = $filenamedocument3;
        } else {
            $this->fileNamedocument31 = $request->olddocument3 ? $request->olddocument3 : '';
        }


         // Video handling - add this block
         $filevideo_Path = $request->file('video'); // Get the video file from the request
         if ($request->hasFile('video')) {
             // Check if old video exists and delete it
             if (!empty($request->oldvideo)) {
                 $oldVideoPath = public_path('uploads/videos/' . $request->oldvideo);
                 if (file_exists($oldVideoPath)) {
                     unlink($oldVideoPath); // Delete the old video file
                 }
             }

             // Get the new video file
             $videoFile = $request->file('video');

             // Generate a unique filename for the video
             $this->fileNamevideo = time() . '_' . $videoFile->getClientOriginalName();

             // Move the video file to the desired location
             $videoFile->move(public_path('uploads/videos'), $this->fileNamevideo);

             // Store the new video filename for database update
             $this->filepathvideo = public_path('uploads/videos/' . $this->fileNamevideo);
         } else {
             // If no new video is uploaded, use the old video filename
             $this->fileNamevideo = $request->oldvideo ? $request->oldvideo : '';
         }



        $SQL = DB::table('progressreports')
        ->where('per_rep_id','=',$request->per_rep_id)
        ->update(['pr_rep_no'=>$request->pr_rep_no?$request->pr_rep_no:''
        ,'per_rep_by'=>$request->per_rep_by?$request->per_rep_by:''
        ,'report_id'=>$request->report_id?$request->report_id:''
        ,'pre_report_dt'=>$request->pre_report_dt?$request->pre_report_dt:''
        ,'status'=>$request->status?$request->status:''
        ,'photo1'=>$this->fileNamephoto11?$this->fileNamephoto11:''
        ,'photo2'=>$this->fileNamephoto21?$this->fileNamephoto21:''
        ,'photo3'=>$this->fileNamephoto31?$this->fileNamephoto31:''
        ,'document1'=>$this->fileNamedocument11?$this->fileNamedocument11:''
        ,'document2'=>$this->fileNamedocument21?$this->fileNamedocument21:''
         ,'document3'=>$this->fileNamedocument31?$this->fileNamedocument31:'','is_final_report'=>$request->is_final_report??'OFF']);

          if (!empty($this->fileNamevideo)) {
            $updateData['vid'] = $this->fileNamevideo;
        }
        $SQL = DB::table('progressreports')
    ->where('per_rep_id', '=', $request->per_rep_id)
    ->update($updateData);
        if($SQL){
        return redirect('ProgressReportEdit/'.$request->eid)->with('success','Record Updated successfully.');
        }else{
        return redirect('ProgressReportEdit/'.$request->eid)->with('success','Error in update record.');
        }

        } catch (\Throwable $th) {

        }


    }



}
