<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Estmaster;
use App\Models\Scopm;
use App\Models\Estscope;
use App\Models\Estrecap;
use App\Models\Acyrm;
use App\Models\Dism;
use App\Models\Division;
use App\Models\Estimate;
use App\Models\Workmaster;
use App\Models\Rabill;
use App\Models\Progressreport;
use App\Providers\RouteServiceProvider;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use GuzzleHttp\Psr7\Message;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Stmt\Foreach_;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Support\Facades\Storage;
use App\Models\Agency;
use Ramsey\Uuid\Type\Decimal;
use App\Helpers\MarathiAmountHelper;
use PDF;
use Dompdf\Dompdf;
use Dompdf\Image;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
//use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\View;


class EstmasterController extends Controller
{
    public $fileNamephoto11='', $fileNamephoto21='',$fileNamephoto31='',$fileNamedocument11='',$fileNamedocument21='',$fileNamedocument31='',$fileNamevideo = '';

    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
    **/
    public $Est_ID='',$Est_master_id='',$filepathpdf='',$filepathxls='',$scopeid='',$fileNamepdf1='',$fileNamexls1='',$workid='',$fileNameaapdf1='',$fileNametspdf1,$fileNamedtppdf1,$fileNamedtpxls1,
    $fileNameWOpdf1='',$fileNamewophoto1='',$fileNamewophoto2='',$fileNamewophoto3='';

    public function UploadWorkMasterForm(Request $request)
        {
            return view('estmaster/upload');
        }

    public function createview(Request $request)
        {
           try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->sercode??'';
            $divid = auth()->user()->Div_id??0;
            //Get Division Name
            $rsDivisionName= Division::select('div','div_m')->where('div_id','=',$divid)->get();
            $subdivid = auth()->user()->Sub_Div_id;
            //Get User Permission

            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';
 //dd('ok');

            if($UseUserPermission){
                            //Get All Estimates
                            $query = DB::table('estmasters');
                            $initCount = 0;

                            foreach(json_decode($DSFoundhd) as $rsFound){
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;

                                //dd($request);
                                $query = '';
                                $query = DB::table('estmasters')->where(DB::raw('left(`Est_Id`,3)'),'=',$divid);
                                if($request->flg == 'AAR'){ //Red
                                    $query->where('estmasters.AA_No','=','')->where('estmasters.AA_TS','=','AA');
                                }else if($request->flg == 'AAG'){ //Green
                                    $query->where('estmasters.AA_No','<>','');
                                }else if($request->flg == 'TSR'){ //Green
                                    $query->where('estmasters.TS_No','=','')->where('estmasters.AA_TS','=','TS');
                                }else if($request->flg == 'TSG'){ //Green
                                    $query->where('estmasters.TS_No','<>','');
                                }
                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                    $query->where("estmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                    $query->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                    $query->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                    $startDate = $request->txtsearchFromPreDate;
                                    $endDate = $request->txtsearchToPreDate;
                                    $query->whereDate('estmasters.Date_Prep','<=', $startDate)
                                    ->whereDate('estmasters.Date_Prep','>=', $endDate);
                                }

                                $query->orderBy('estmasters.created_at', 'desc');
                                $project = $query->paginate(10);
                                break;

                            }else{



                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){
                                       $query->where(DB::raw('left(`Est_Id`,3)'),'=',$divid);
                                       $query->orWhere('Work_Id','=',$rsFound->Work_Id);
                                    }else{
                                        $query->where('Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){
                                          $query->where(DB::raw('left(`Est_Id`,3)'),'=',$divid);
                                          $query->where(DB::raw('left(`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{
                                          $query->orWhere(DB::raw('left(`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count
                                        $query->Where('Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }

                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("estmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('estmasters.Date_Prep','<=', $startDate)
                                ->whereDate('estmasters.Date_Prep','>=', $endDate);
                            }

                            $query->orderBy('estmasters.created_at', 'desc');
                            if($request->flg == 'AAR'){ //Red
                                $query->where('estmasters.AA_No','=','') ->where('estmasters.AA_TS','=','AA');
                             }else if($request->flg == 'AAG'){ //Green
                                 $query->where('estmasters.AA_No','<>','');
                             }else if($request->flg == 'TSR'){ //Green
                                 $query->where('estmasters.TS_No','=','')->where('estmasters.AA_TS','=','TS');
                             }else if($request->flg == 'TSG'){ //Green
                                 $query->where('estmasters.TS_No','<>','');
                             }

                            $project =$query->paginate(10);
                            $initCount++;
                        }

            //   $jeid = DB::table('jemasters')->where('userid' , $uid)->value('jeid');

            //    if($jeid)
            //    {
            //          $query = DB::table('estmasters')->where('je_id' , $jeid);
            //          $project =$query->paginate(10);
            //dd('ok');

                }else{
                    //->where('Est_Id','=',0)
                        $query = DB::table('estmasters')->orderBy('estmasters.created_at', 'desc');
                        if($request->flg == 'AAR'){ //Red
                           $query->where('estmasters.AA_No','=','')->where('estmasters.AA_TS','=','AA');
                        }else if($request->flg == 'AAG'){ //Green
                            $query->where('estmasters.AA_No','<>','');
                        }else if($request->flg == 'TSR'){ //Green
                            $query->where('estmasters.TS_No','=','')->where('estmasters.AA_TS','=','TS');
                        }else if($request->flg == 'TSG'){ //Green
                            $query->where('estmasters.TS_No','<>','');
                        }
                        $project =$query->paginate(10);
                        //dd($project);
                }
                return view('estmaster/list',['data'=>$project]);

            } catch (\Throwable $th) {
                throw $th;
            }
        }

       public function createManualEntryForm(Request $request)
        {
            // Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();

            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();

            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();

            // Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            return view('estmaster/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno]);
        }

        public function EditViewEstimateForm(Request $request)
            {
                 $currentSOId = Estmaster::where('Est_Id', '=', $request->id)->value('je_id');
                $currentSOName = DB::table('jemasters')->where('jeid', $currentSOId)->value('name');




                $rsSOList = DB::table('jemasters')
                            ->select('jeid', 'name')
                            ->get();


                // Genrate unique ID Genration
                $uniquenumber = uniqid();

                // Logged User Session
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------

                // Get Division ID To Division Name
                $rsDivisionDtls = DB::table('divisions')
                ->selectRaw('`div_m`,`div`,`div_id`')
                ->where('div_id','=',$divid)->get();
                $rsDiv = json_decode($rsDivisionDtls,true);

                //Get Selected Divisions All Subdivisions
                $rsSubDivisionDtls = DB::table('subdivms')
                ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
                ->where('Div_Id','=',$divid)->get();
                $rsWorkType = DB::table('worktypes')
                ->selectRaw('`id`,`worktype`')
                ->get();
                $rsTalukas = DB::table('talms')
                ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
                ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
                ->where('divisions.div_id','=',$divid)
                ->get();


                //Scope Master data
                $scopeMasterList = Scopm::get();

                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$uniquenumber)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $RecapSrno=$RSNewPKID[0]->Sr_No;
                }else{
                    $RecapSrno=1;
                }

                //Get Estimate Details
                $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
                //dd($rsestimate);

                //Get Estimate Scope
                $rsscope = Estscope::where('Est_Id','=',$request->id)->get();
                //Get Estimate Recape
                $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();
                $inChargePOData = DB::table('jemasters')
                ->select('div_id', 'jeid', 'subdiv_id', 'name')
                ->whereRaw('SUBSTRING(div_id, 4, 1) = "0"')
                ->orWhereRaw('SUBSTRING(jeid, 4, 1) = "0"')
                ->orWhereRaw('SUBSTRING(subdiv_id, 4, 1) = "0"')
                ->get();


                $inChargePOData = DB::table('jemasters')
    ->select('jeid as po_id', 'name')  // Correct mapping of columns
    ->whereRaw('SUBSTRING(div_id, 4, 1) = "0"') // Check if 4th character of div_id is '0'
    ->orWhereRaw('SUBSTRING(jeid, 4, 1) = "0"') // Check if 4th character of jeid is '0'
    ->orWhereRaw('SUBSTRING(subdiv_id, 4, 1) = "0"') // Check if 4th character of subdiv_id is '0'
    ->get();

$currentPOId = Estmaster::where('Est_Id', '=', $request->id)->value('po_id');
$currentPOName = DB::table('jemasters')->where('jeid', $currentPOId)->value('name');


                return view('estmaster/edit',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'editrsestimate'=>$rsestimate,'editrsscope'=>$rsscope,       'rsSOList' => $rsSOList,
        'currentSOId' => $currentSOId, 'currentSOName' => $currentSOName,'editrsrecape'=>$rsrecape,'inChargePOData' => $inChargePOData,'currentPOId' => $currentPOId,
    'currentPOName' => $currentPOName

        ]);

            }





    public function uploadCSV(Request $request)
        {
          //  dd($request);
                $file = $request->file('file');

                if ($file) {
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension(); //Get extension of uploaded file
                $tempPath = $file->getRealPath();
                $fileSize = $file->getSize(); //Get size of uploaded file in bytes//Check for file extension and size
                //$this->checkUploadedFileProperties($extension, $fileSize);
                //Where uploaded file will be stored on the server

                $location = 'uploads/estcsv'; //Created an "uploads" folder for that
                // Upload file
                $file->move($location, $filename);


                // In case the uploaded file path is to be stored in the database
                $filepath = public_path($location . "/" . $filename);

                // Reading file
                $file = fopen($filepath, "r");
                $importData_arr = array(); // Read through the file and store the contents as an array
                $importData_arrScope = array(); // Scope Table Array
                $importData_arrRecap = array(); // Recap Table Array
                $i = 0;

                //Read the contents of the uploaded file
                while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                 $num = count($filedata);
                for ($c = 0; $c < $num; $c++) {
                    $importData_arr[$i][] = $filedata[$c];  // Estimate Master Database
                }
                $i++;
                }

                //Scope Master Database
                $scope=0;
                $isscope = false;
                foreach ($importData_arr as $importDataScope) {
                    if($importDataScope[0] == 'SCOPE'){
                        $isscope = true;
                    }
                    if($importDataScope[0] == 'RECAP'){
                        $isscope = false;
                        break;
                    }
                    if($isscope == true){
                        //Only Scope Records
                        $importData_arrScope[] = $importDataScope;
                    }
                    $scope++;
                }

                //Recap Master Database
                $recap=0;
                $isrecap = false;
                foreach ($importData_arr as $importDataRecap) {
                    if($importDataRecap[0] == 'RECAP'){
                        $isrecap = true;
                    }
                    if($isrecap == true){
                         $importData_arrRecap[] = $importDataRecap;
                    }
                    $recap++;
                }


                fclose($file); //Close after reading$j = 0;
                $j = 0;

                foreach ($importData_arr as $importData) { //Row 0 Index Loop

                if((int)$j === 0){

                try {

                        //Calculate account Year

// date sakshi
                try {
                  $Date_Prep = Carbon::createFromFormat('d/m/Y', $importData[8])->format('Y-m-d');
                } catch (\Exception $e) {
                     $Date_Prep = Carbon::createFromFormat('d-m-Y', $importData[8])->format('Y-m-d');
                    }


                $FinYearID = Acyrm::select('Ac_Yr_Id as id')
                ->where('Yr_St', '<=', $Date_Prep)
                ->where('Yr_End', '>=', $Date_Prep)
                ->get();
                $json = json_decode($FinYearID, true);
                $yearid = $json[0]['id'];


                //Calculate account Year


                // Genrate Estimate ID Functionality
                $SQLNewPKID = DB::table('estmasters')
                ->selectRaw(" MAX(CAST(right(`Est_Id`,8)AS UNSIGNED)) as code")
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID,true);
                $RSNewPKID[0]['code'];

                if($RSNewPKID[0]['code'] != '' && isset($RSNewPKID[0]['code'])){
                    $PrimaryNumber=$RSNewPKID[0]['code'] + 1;
                }else{
                    $PrimaryNumber=1;
                }

                $lenght = strlen($PrimaryNumber);  //Calculate Lenght
                if((int)$lenght === 1){ //Places Zero Functionality
                    $placezero = '0000000';
                }else if((int)$lenght === 2){
                    $placezero = '000000';
                }else if((int)$lenght === 3){
                    $placezero = '00000';
                }else if((int)$lenght === 4){
                    $placezero = '0000';
                }else if((int)$lenght === 5){
                    $placezero = '000';
                }else if((int)$lenght === 6){
                    $placezero = '00';
                }else if((int)$lenght === 7){
                    $placezero = '0';
                }else{
                    $placezero = '';
                }
                $this->Est_ID = $importData[1].$yearid.$placezero.$PrimaryNumber;
                // End Genrate Estimate ID Functionality
                } catch (\Throwable $th) {
                        // Handle exception and return error response
                        return response()->json([
                            'success' => false,
                            'message' => $th->getMessage() // You can customize the error message here
                        ], 500);
                }
                 //Calculate round Off Value----------------------------------------
                 $RecapeTotalAmount= 0;
                 $RecapeTotalRoundOffAmount = 0;
                    //dd($importData_arrRecap);
                    $indRecapecount = 0;
                    foreach($importData_arrRecap as $rsRecapef){
                       if($indRecapecount != 0){
                            $RecapeTotalAmount = $RecapeTotalAmount + $rsRecapef[5];
                        }
                       $indRecapecount ++;
                    }
                    $RecapeTotalRoundOffAmount= round($RecapeTotalAmount/(int)$importData[11]) * (int)$importData[11];
                //------------------------------------------------------------------
                // echo $RecapeTotalAmount; exit;
                // echo $RecapeTotalRoundOffAmount; exit;

                //Get a subdivision from subdivision id
                $subdivision=DB::table('subdivms')->where('Sub_Div_Id' , $importData[1])->value('Sub_Div');
                //Get taluka id from taluka
                $talukaId=DB::table('talms')->where('Tal' , $importData[2])->value('Tal_Id');

                    if($this->Est_ID){
                        try {
                            DB::beginTransaction();

                            Estmaster::create([
                                'Est_Id'=> $this->Est_ID,
                                'Est_No' => $importData[0],
                                'Sub_Div_Id' => $importData[1],
                                'Sub_Div' => $subdivision,
                                'Tal' => $importData[2],
                                'Tal_Id'=>$talukaId,
                                'Work_Nm' => $importData[3],
                                'Work_Type' => $importData[4],
                                'Tot_Amt' => $importData[5],
                                'E_Prep_By' => $importData[6],
                                'E_Chk_By' => $importData[7],
                                'Date_Prep' => $Date_Prep,
                                'F_H_Code' => $importData[9],
                                'Work_Nm_M'=>'',
                                'Est_PDF_Path'=>'',
                                'Est_XLS_Path'=>'',
                                'AA_TS'=>$importData[10],
                                'roundofvalue'=>$importData[11], //round Off value
                                'roundOfCalculateAmount'=>$RecapeTotalRoundOffAmount, // round off Amount
                                'je_id' =>$request->junior_engineer_id,//Juniore engineer id
                                'po_id' =>$request->po_id//PO id
                            ]);
                            DB::commit();
                        }catch (\Exception $e) {
                        DB::rollBack();
                        }
                    }
            }
                $j++;

            }


                 //Scope Data Insert To TB
            $scopecount= 0;
            foreach ($importData_arrScope as $rsScope){
                     if($scopecount != 0){
                         try {

                            DB::beginTransaction();
                            // Insert to Scopes table
                            Estscope::create([
                                'Est_Id'=>$this->Est_ID,
                                'Est_Sc_Id' => $this->Est_ID.$rsScope[0],
                                'Scope_Id' => $rsScope[0],
                                'Scope' => $rsScope[1],
                                'Scope_M' => '',
                                'Qty' => $rsScope[2],
                                'Unit' => $rsScope[3]
                             ]);
                             DB::commit();
                        } catch (\Exception $e) {
                          DB::rollBack();
                        }
                      }

                      $scopecount++;
            }

                 //Scope Data Insert To TB
                 $Recapecount= 0;
                 foreach ($importData_arrRecap as $rsRecape){
                     if($Recapecount != 0){
                         try {
                         DB::beginTransaction();
                         // Insert to Recapes Table
                            Estrecap::create([
                                'Est_Id'=>$this->Est_ID,
                                'Est_Rcp_Id' => $this->Est_ID.$rsRecape[0],
                                'Sr_No' => $rsRecape[0],
                                'Se_No' => $rsRecape[1],
                                'Descrip' => $rsRecape[2],
                                'Rcp_Pc' => $rsRecape[3],
                                'Pc_On' => $rsRecape[4],
                                'Rcp_Amt' => $rsRecape[5],
                                'Rcp' => $rsRecape[6]
                             ]);
                             DB::commit();
                        } catch (\Exception $e) {
                          DB::rollBack();
                        }
                      }

                      $Recapecount++;
                 }

                //Update WorkID Estimate ID
                DB::table('workmasters')
                ->where('Work_Id',$request->workid)
                ->update(['Est_Id'=>$this->Est_ID]);


                // Redirect back to the previous page with a success message
                return back()->with('success', 'File Imported Successfully.');

                } else {
                    //no file was uploaded
                }
    }

    public function ajaxFunctionEstimateScope(Request $request)
        {
            // try {
            $PrimaryNumber = '';
            //Scope Estimate Entry

            // Scope Master Entry
            if(isset($request->Scope) && isset($request->Scope_M) && isset($request->Unit)){
                    //Get Worktype ID
                    $sqlWorktypeid = DB::table('worktypes')
                    ->selectRaw('id,worktype')
                    ->where('worktype','=',$request->WorkType)
                    ->get();
                    $DSWorktypeid = json_decode($sqlWorktypeid );

                    $SQLNewPKID = DB::table('scopms')
                    ->selectRaw(" MAX(CAST(right(`Scope_Id`,3)AS UNSIGNED)) as code")
                    ->limit(1)
                    ->get();
                    $RSNewPKID = json_decode($SQLNewPKID,true);
                    $RSNewPKID[0]['code'];

                    if($RSNewPKID[0]['code'] != '' && isset($RSNewPKID[0]['code'])){
                    $PrimaryNumber=$RSNewPKID[0]['code'] + 1;
                    }else{
                    $PrimaryNumber=1;
                    }

                    $lenght = strlen($PrimaryNumber);  //Calculate Lenght
                    if((int)$lenght === 1){ //Places Zero Functionality
                    $placezero = '00';
                    }else if((int)$lenght === 2){
                    $placezero = '0';
                    }else{
                    $placezero = '';
                    }
                    $this->scopeid = $DSWorktypeid[0]->id.$placezero.$PrimaryNumber;

                    // Auto Increament Scope ID
                    Scopm::create([
                        'Work_Type_Id'=>$DSWorktypeid[0]->id,
                        'Work_Type' =>$DSWorktypeid[0]->worktype,
                        'Scope_Id' =>$this->scopeid?$this->scopeid:'', //Auto Increament Scope ID
                        'Scope' =>$request->Scope?$request->Scope:'',
                        'Scope_M' =>$request->Scope_M?$request->Scope_M:'',
                        'Unit'=>$request->Unit?$request->Unit:''
                    ]);
                    return response()->json(array('success'=> "Scope added Successfully"), 200);

            }else{

                if(isset($request->s2Scopeid) && !empty($request->s2Scopeid)){
                    $this->scopeid = $request->s2Scopeid;
                }
                if(isset($this->scopeid) && !empty($this->scopeid)){
                    DB::beginTransaction();
                    Estscope::create([
                        'Est_Id'=>$request->testimateid,
                        'Est_Sc_Id' =>$request->testimateid.$this->scopeid,
                        'Scope_Id' =>$this->scopeid,
                        'Scope' =>$request->Scope,
                        'Scope_M' =>$request->Scope_M,
                        'Qty' =>$request->Qty,
                        'Unit' =>$request->Unit
                    ]);
                    DB::commit();

                }
                //echo $PrimaryNumber; exit;
                $rsEstScopeList = Estscope::select("estscopes.Est_Sc_Id", "estscopes.Scope_Id", "scopms.Scope", "scopms.Scope_M", "estscopes.Qty","estscopes.Unit")->where('estscopes.Est_Id','=',$request->testimateid)
                ->leftJoin("scopms", "scopms.Scope_Id", "=", "estscopes.Scope_Id")->orderBy('estscopes.created_at', 'asc')->get();
                return response()->json(array('msg'=> $rsEstScopeList), 200);

            }
        }


//Edit and View Estimate
        public function ajaxAutoLoadEstimateScope(Request $request)
        {
            try {
                $rsEstScopeList = Estscope::select("estscopes.Est_Sc_Id", "estscopes.Scope_Id", "scopms.Scope", "scopms.Scope_M", "estscopes.Qty","estscopes.Unit")->where('estscopes.Est_Id','=',$request->Est_Id)
                ->leftJoin("scopms", "scopms.Scope_Id", "=", "estscopes.Scope_Id")->orderBy('estscopes.created_at', 'asc')->get();
                return response()->json(array('msg'=> $rsEstScopeList), 200);
            } catch (\Throwable $th) {
            }
        }
//Edit and View Estimate
        public function ajaxAutoLoadEstimateRecape(Request $request)
        {
            try {
                //Auto Increament Serial Number
                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$request->Est_Id)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $PrimaryNumber=$RSNewPKID[0]->Sr_No;
                }else{
                $PrimaryNumber=1;
                }

                $rsEstRecapeList = Estrecap::select("estrecaps.Est_Rcp_Id","estrecaps.Sr_No","estrecaps.Se_No","estrecaps.Descrip", "estrecaps.Rcp_Pc", "estrecaps.Pc_On", "estrecaps.Rcp_Amt")->where('estrecaps.Est_Id','=',$request->Est_Id)
                ->orderBy('estrecaps.Sr_No','ASC')
                ->get();

                $rsEstRecapeTAmt = Estrecap::select(DB::raw("SUM(estrecaps.Rcp_Amt) AS rcptol"))->where('estrecaps.Est_Id','=',$request->Est_Id)
                ->get();

                return response()->json(array('msg'=> $rsEstRecapeList,'rcpamt'=>$rsEstRecapeTAmt,'rcsrno'=>$PrimaryNumber), 200);
            } catch (\Throwable $th) {
             }

        }

        public function ajaxFunctionEstimateRecape(Request $request)
        {
            try {
            //Auto Increament Serial Number
            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$request->testimateid)
            ->limit(1)
            ->get();

            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $PrimaryNumber=$RSNewPKID[0]->Sr_No;
            }else{
                $PrimaryNumber=1;
            }
            $rcp = $request->Rcp; // New Requirement

            DB::beginTransaction();
                Estrecap::create([
                    'Est_Id'=>$request->testimateid,
                    'Est_Rcp_Id' => $request->testimateid.$PrimaryNumber,
                    'Sr_No' => $request->Sr_No?$request->Sr_No:0,
                    'Se_No' => $request->Se_No?$request->Se_No:0,
                    'Descrip' => $request->Descrip?$request->Descrip:'',
                    'Rcp_Pc' => $request->Rcp_Pc?$request->Rcp_Pc:0,
                    'Pc_On' => $request->Pc_On?$request->Pc_On:0,
                    'Rcp_Amt' => $request->Rcp_Amt?$request->Rcp_Amt:0,
                    'Rcp' => $rcp  // "S"=>Subestimate , "R"=>Recape
                ]);
            DB::commit();

            $rsEstRecapeList = Estrecap::select("estrecaps.Est_Rcp_Id","estrecaps.Sr_No","estrecaps.Se_No","estrecaps.Descrip", "estrecaps.Rcp_Pc", "estrecaps.Pc_On", "estrecaps.Rcp_Amt")->where('estrecaps.Est_Id','=',$request->testimateid)->get();

            $rsEstRecapeTAmt = Estrecap::select(DB::raw("SUM(estrecaps.Rcp_Amt) AS rcptol"))->where('estrecaps.Est_Id','=',$request->testimateid)
            ->get();

            //SrNo Auto Increament Functionality
            return response()->json(array('msg'=> $rsEstRecapeList,'rcpamt'=>$rsEstRecapeTAmt,'rcsrno'=>$PrimaryNumber), 200);

            } catch (\Throwable $th) {
            }

        }


        public function ajaxFunctionEstimateHeader(Request $request)
            {
             try {
                    // login user session Data----------------------------
                    $divid = auth()->user()->Div_id??0;
                    $usercode = auth()->user()->usercode??'';
                    $subdivid = auth()->user()->Sub_Div_id??0;
                    // login user session Data----------------------------

                    //Genrate Latest Estimate ID
                    //Calculate account Year
                    //echo $Date_Prep = Carbon::createFromFormat('d/m/Y', $request->Date_Prep)->format('Y-m-d'); exit;
                    $Date_Prep = $request->Date_Prep;
                    $FinYearID = Acyrm::select('Ac_Yr_Id as id')
                    ->where('Yr_St', '<=', $Date_Prep)
                    ->where('Yr_End', '>=', $Date_Prep)
                    ->get();
                    $json = json_decode($FinYearID, true);
                    $yearid = $json[0]['id'];
                    //Calculate account Year


                    // Genrate Estimate ID Functionality
                    $SQLNewPKID = DB::table('estmasters')
                    ->selectRaw(" MAX(CAST(right(`Est_Id`,8)AS UNSIGNED)) as code")
                    ->limit(1)
                    ->get();
                    $RSNewPKID = json_decode($SQLNewPKID,true);
                    $RSNewPKID[0]['code'];

                    if($RSNewPKID[0]['code'] != '' && isset($RSNewPKID[0]['code'])){
                    $PrimaryNumber=$RSNewPKID[0]['code'] + 1;
                    }else{
                    $PrimaryNumber=1;
                    }

                    $lenght = strlen($PrimaryNumber);  //Calculate Lenght
                    if((int)$lenght === 1){ //Places Zero Functionality
                    $placezero = '0000000';
                    }else if((int)$lenght === 2){
                    $placezero = '000000';
                    }else if((int)$lenght === 3){
                    $placezero = '00000';
                    }else if((int)$lenght === 4){
                    $placezero = '0000';
                    }else if((int)$lenght === 5){
                    $placezero = '000';
                    }else if((int)$lenght === 6){
                    $placezero = '00';
                    }else if((int)$lenght === 7){
                    $placezero = '0';
                    }else{
                    $placezero = '';
                    }

                    //FirstCharacter Div or SubDiv
                    if($divid){
                        $first4char = $divid.'0';
                    }
                    if($subdivid){
                        $first4char = $subdivid;
                    }

                    $this->Est_master_id =  $first4char.$yearid.$placezero.$PrimaryNumber;


                    //Estimate file Uploading .pdf
                    $fileEst_PDF_Path = $request->file('Est_PDF_Path');
                    if($fileEst_PDF_Path){
                        if($request->oldEst_PDF_Path){
                            $image_pathEstPDF =   public_path('uploads/estpdf/' . $request->oldEst_PDF_Path);
                            if(file_exists($image_pathEstPDF)){
                             unlink($image_pathEstPDF);
                            }
                        }
                    $filenamepdf = time().$fileEst_PDF_Path->getClientOriginalName();
                    $extensionpdf = $fileEst_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf = $fileEst_PDF_Path->getRealPath();
                        //$fileSizepdf = $fileEst_PDF_Path->getSize(); //Get size of uploaded file in bytes//Check for file extension and size

                        //$this->checkUploadedFileProperties($extensionpdf, $fileSizepdf);
                        //Where uploaded file will be stored on the server

                        $locationpdf = 'uploads/estpdf'; //Created an "uploads" folder for that
                        // Upload file
                        $fileEst_PDF_Path->move($locationpdf, $filenamepdf);
                        //$size=$fileEst_PDF_Path->getSize();

                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamepdf1 = $filenamepdf;
                        $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNamepdf1);

                    }
                    //Estimate file Uploading .pdf


                    //Estimate file Uploading excel
                    $fileEst_XLS_Path = $request->file('Est_XLS_Path');
                    if($fileEst_XLS_Path){
                        if($request->oldEst_XLS_Path){
                            $image_pathEstXLS =   public_path('uploads/estexcel/' . $request->oldEst_XLS_Path);
                            if(file_exists($image_pathEstXLS)){
                             unlink($image_pathEstXLS);
                            }
                        }
                    $filenamexls = time().$fileEst_XLS_Path->getClientOriginalName();
                    $extensionxls = $fileEst_XLS_Path->getClientOriginalExtension(); //Get extension of uploaded file
                    $tempPathxls = $fileEst_XLS_Path->getRealPath();
                    //$fileSizexls = $fileEst_XLS_Path->getSize(); //Get size of uploaded file in bytes//Check for file extension and size
                    //$this->checkUploadedFileProperties($extensionxls, $fileSizexls);
                        //Where uploaded file will be stored on the server

                    $locationxls = 'uploads/estexcel'; //Created an "uploads" folder for that
                        // Upload file
                    $fileEst_XLS_Path->move($locationxls, $filenamexls);
                    //In case the uploaded file path is to be stored in the database
                    $this->fileNamexls1 =  $filenamexls;
                    $this->filepathxls = public_path($locationxls . "/" . $this->fileNamexls1);

                    }
                    //Estimate file Uploading excel

                    //Insert Estimate
                    Estmaster::create([
                    'Est_Id'=> $this->Est_master_id,
                    'Est_No' => $request->Est_No?$request->Est_No:'',
                    'Sub_Div_Id' => $request->Sub_Div_Id?$request->Sub_Div_Id:'',
                    'Tal' => $request->Tal?$request->Tal:'',
                    'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'',
                    'Work_Type' => $request->Work_Type?$request->Work_Type:'',
                    'Tot_Amt' => $request->Tot_Amt?$request->Tot_Amt:0.00,
                    'E_Prep_By' => $request->E_Prep_By?$request->E_Prep_By:'',
                    'E_Chk_By' => $request->E_Chk_By?$request->E_Chk_By:'',
                    'Date_Prep' => $Date_Prep?$Date_Prep:'',
                    'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
                    'Tal_Id'=> $request->Tal_Id?$request->Tal_Id:'',
                    'Work_Nm_M'=> $request->Work_Nm_M?$request->Work_Nm_M:'',
                    'Est_PDF_Path'=>$this->fileNamepdf1?$this->fileNamepdf1:'',
                    'Est_XLS_Path'=>$this->fileNamexls1?$this->fileNamexls1:'',
                    'AA_TS'=>$request->AA_TS?$request->AA_TS:''
                    ]);
                    //Update Scope and Recape Estimate ids

                    //get tempEstimate Scope Details
                    $rsupdateScopeIDs = DB::table('estscopes')
                    ->select('Scope_Id','Est_Sc_Id')
                    ->where('Est_Id','=',$request->temp_estid)
                    ->get();
                    foreach($rsupdateScopeIDs as $dsscopeids){
                        $dsscopeids->Scope_Id;
                        $dsscopeids->Est_Sc_Id;
                        $updateScopeEstimateID = DB::table('estscopes')
                        ->where('Est_Sc_Id','=',$dsscopeids->Est_Sc_Id)
                        ->update(['Est_Id' => $this->Est_master_id,'Est_Sc_Id'=>$this->Est_master_id.$dsscopeids->Scope_Id]);
                    }

                    $rsupdateRecapeIDs = DB::table('estrecaps')
                    ->select('Est_Rcp_Id','Sr_No')
                    ->where('Est_Id','=',$request->temp_estid)
                    ->get();

                    foreach($rsupdateRecapeIDs as $dsrecapsids){

                    $updateRecapeEstimateID = DB::table('estrecaps')
                    ->where('Est_Rcp_Id','=',$dsrecapsids->Est_Rcp_Id)
                    ->update(['Est_Id' => $this->Est_master_id,'Est_Rcp_Id'=>$this->Est_master_id.$dsrecapsids->Sr_No]);
                    }

                    return redirect('EstimatesMasterList/h')->with('success','Estimate Save Successfully.');

                } catch (\Throwable $th) {
                }

            }

        //Ajax Scope Master List Retrive To Master
       public function ajaxScopeListFunction(Request $request){
        $data = [];
        if($request->has('q')){
            $search = $request->q;
            $data =Scopm::select("Scope_Id as id","Scope as name")
                    ->where('Scope','LIKE',"$search%")
                    ->get();
            }else{
            $data =Scopm::select("Scope_Id as id","Scope as name")
                    ->get();
            }
        return response()->json($data);

       }

       public function ajaxScopeUnitFunction(Request $request){
            if($request->scopeid){
                $search = $request->scopeid;
                $data =Scopm::select("Unit")
                        ->where('Scope_Id','=',$search)
                        ->get();
                }
            return response()->json($data);
       }


       public function deleteEstimateAllDtls($id) {
        try {
            DB::beginTransaction();

            // Dependent records delete
            DB::table('estrecaps')->where('Est_Id', '=', $id)->delete();
            DB::table('estscopes')->where('Est_Id', '=', $id)->delete();

            // Main record delete
            $SQLEstimate = DB::table('estmasters')->where('Est_Id', '=', $id)->delete();

            DB::commit();

            if ($SQLEstimate) {
                return back()->with('success', 'Record deleted successfully.');
            } else {
                return back()->with('error', 'Error in record.');
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error($th->getMessage());
            return back()->with('error', 'An error occurred while deleting the record.');
        }
    }




      //Update Estimate Master details
      public function UpdateEstimateMaster(Request $request)
      {   //dd($request);
          try {

            //dd($jeiD);
          //code...
          $Est_Id = $request->Est_Id;
        //  $Jeid = $request->Jeid;
         // $Jeid = $request->Jeid;

          // login user session Data----------------------------
              $divid = auth()->user()->Div_id??0;
              $usercode = auth()->user()->usercode??'';
              $subdivid = auth()->user()->Sub_Div_id??0;
          // login user session Data----------------------------
          $talukaName = DB::table('talms')->where('Tal_Id', $request->Tal_Id)->value('Tal');

          $subDivName = DB::table('subdivms')->where('Sub_Div_Id', $request->Sub_Div_Id)->value('Sub_Div');

        //  $jeiD = DB::table('jemasters')->where('jeid', $request->jeid)->value('name');

        // $jeId = DB::table('jemasters')->where('jeid', $request->inChargeSO)->value('name'); // In charge SO नुसार ID शोधा


          //Genrate Latest Estimate ID
          //Calculate account Year
          //echo $Date_Prep = Carbon::createFromFormat('d/m/Y', $request->Date_Prep)->format('Y-m-d'); exit;
          $Date_Prep = $request->Date_Prep;
          $FinYearID = Acyrm::select('Ac_Yr_Id as id')
          ->where('Yr_St', '<=', $Date_Prep)
          ->where('Yr_End', '>=', $Date_Prep)
          ->get();
          $json = json_decode($FinYearID, true);
          $yearid = $json[0]['id'];
          //Calculate account Year


          $previouspdfEstimate = $request->oldEstimatepdffile?$request->oldEstimatepdffile:'';
          $previousxlsEstimate = $request->oldEstimateexcelfile?$request->oldEstimateexcelfile:'';

          //Estimate file Uploading .pdf
          $fileEst_PDF_Path = $request->file('Est_PDF_Path');
          if($fileEst_PDF_Path){
          $filenamepdf = time().$fileEst_PDF_Path->getClientOriginalName();
          $extensionpdf = $fileEst_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
              $tempPathpdf = $fileEst_PDF_Path->getRealPath();
              //Where uploaded file will be stored on the server
              $locationpdf = 'uploads/estpdf'; //Created an "uploads" folder for that
              // Upload file
              $fileEst_PDF_Path->move($locationpdf, $filenamepdf);
              // In case the uploaded file path is to be stored in the database
              $this->fileNamepdf1 = $filenamepdf;
              $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNamepdf1);
          }
          //Estimate file Uploading .pdf


          //Estimate file Uploading excel
          $fileEst_XLS_Path = $request->file('Est_XLS_Path');
          if($fileEst_XLS_Path){
          $filenamexls = time().$fileEst_XLS_Path->getClientOriginalName();
          $extensionxls = $fileEst_XLS_Path->getClientOriginalExtension(); //Get extension of uploaded file
          $tempPathxls = $fileEst_XLS_Path->getRealPath();
          $locationxls = 'uploads/estexcel'; //Created an "uploads" folder for that
          // Upload file
          $fileEst_XLS_Path->move($locationxls, $filenamexls);
          //In case the uploaded file path is to be stored in the database
          $this->fileNamexls1 =  $filenamexls;
          $this->filepathxls = public_path($locationxls . "/" . $this->fileNamexls1);
          }

          $SQLEdit = DB::table('estmasters')

              ->where('Est_Id', $Est_Id)
              ->update(['Est_Id'=> $Est_Id,
              'Est_No' => $request->Est_No,
              'je_id' => $request->inChargeSO??0,
              'po_id' => $request->inChargePO??0,
               //'Sub_Div_Id' => $subdivid?$subdivid:'',
              'Sub_Div_Id' => $request->Sub_Div_Id?$request->Sub_Div_Id:'',
              'Tal' => $talukaName ,
              'Sub_Div' => $subDivName,
              'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'',
              'Work_Type' => $request->Work_Type?$request->Work_Type:'',
              'Tot_Amt' => $request->Tot_Amt?$request->Tot_Amt:0.00,
              'E_Prep_By' => $request->E_Prep_By?$request->E_Prep_By:'',
              'E_Chk_By' => $request->E_Chk_By?$request->E_Chk_By:'',
              'Date_Prep' => $Date_Prep?$Date_Prep:'',
              'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
              'Tal_Id'=> $request->Tal_Id?$request->Tal_Id:'',
              'Work_Nm_M'=> $request->Work_Nm_M?$request->Work_Nm_M:'',
              'Est_PDF_Path'=>$this->fileNamepdf1?$this->fileNamepdf1:$previouspdfEstimate,
              'Est_XLS_Path'=>$this->fileNamexls1?$this->fileNamexls1:$previousxlsEstimate,
              'AA_TS'=>$request->AA_TS?$request->AA_TS:'',
              'roundofvalue'=>$request->roundofftype?$request->roundofftype:'',
              'roundOfCalculateAmount'=>$request->recapetotalamount?$request->recapetotalamount:'']);
               return  redirect()->to($request->last_url)->with('success','Record Updated Successfully');

          } catch (\Throwable $th) {
              //throw $th;
          }

      }
      //fetchInChargeSOData sakshi
      public function fetchInChargeSOData()
      {

        $currentSOId = Estmaster::where('Est_Id', '=', $request->id)->value('je_id');
        $currentSOName = DB::table('jemasters')->where('jeid', $currentSOId)->value('name'); // SO चं नाव शोधा


        $rsSOList = DB::table('jemasters')
                    ->select('jeid', 'name')
                    ->get();

          $inChargeSOData = DB::table('jemasters')
          ->select(DB::raw('DISTINCT LEFT(jeid, 3) as short_jeid'), 'jeid', 'div_id', 'name') // Add DISTINCT and 'name' column
          ->whereRaw('LEFT(jeid, 3) = div_id') // Filter condition
              ->get();

              return response()->json([
                'rsSOList' => $rsSOList,
                'currentSOId' => $currentSOId,
                'currentSOName' => $currentSOName,
                'inChargeSOData' => $inChargeSOData
            ]);

      }
//fetchInChargePOData sakshi
      public function fetchInChargePOData()
      {
          $inChargePOData = DB::table('jemasters')
              ->select('div_id', 'jeid', 'subdiv_id','name') // Select the relevant columns
              ->whereRaw('SUBSTRING(div_id, 4, 1) = "0"') // Check if 4th character of div_id is '0'
              ->orWhereRaw('SUBSTRING(jeid, 4, 1) = "0"') // Check if 4th character of jeid is '0'
              ->orWhereRaw('SUBSTRING(subdiv_id, 4, 1) = "0"') // Check if 4th character of subdiv_id is '0'
              ->get();

              $currentPOId = Estmaster::where('Est_Id', '=', $request->id)->value('po_id');
              $currentPOName = DB::table('jemasters')->where('jeid', $currentPOId)->value('name');



              return response()->json([
                'inChargePOData' => $inChargePOData,
                'currentPOId' => $currentPOId,
                'currentPOName' => $currentPOName
            ]);

            }

      //View Estimate Function
        public function ViewEstimateForm(Request $request)
        {
            // Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            //Get Estimate Details
            $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();
            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();


            return view('estmaster/view',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'editrsestimate'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape]);

        }


        //#####################Estimate Scope Delete###########################################
        function EstimateScopeDtlsDelete(Request $request){
            try {
                $request->_token;
                $request->testimateid;
                $request->pdelid;
                $res=Estscope::where('Est_Sc_Id',$request->pdelid)->delete();

                //Old Scope Id's
                $rsEstScopeList = Estscope::select("estscopes.Est_Sc_Id", "estscopes.Scope_Id", "scopms.Scope", "scopms.Scope_M", "estscopes.Qty","estscopes.Unit")->where('estscopes.Est_Id','=',$request->testimateid)
                ->leftJoin("scopms", "scopms.Scope_Id", "=", "estscopes.Scope_Id")->get();
                return response()->json(array('msg'=> $rsEstScopeList), 200);

            } catch (\Throwable $th) {}
        }


         //#####################Estimate Recape Delete###########################################
         function EstimateRecapeDtlsDelete(Request $request){
            try {
                $request->_token;
                $request->testimateid;
                $request->pdelid;

                //Auto Increament Serial Number
                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$request->testimateid)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $PrimaryNumber=$RSNewPKID[0]->Sr_No;
                }else{
                    $PrimaryNumber=1;
                }


                $res=Estrecap::where('Est_Rcp_Id',$request->pdelid)->delete();

                //Old Recape Id's
                $rsEstRecapeList = Estrecap::select("estrecaps.Est_Rcp_Id","estrecaps.Sr_No","estrecaps.Se_No","estrecaps.Descrip", "estrecaps.Rcp_Pc", "estrecaps.Pc_On", "estrecaps.Rcp_Amt")->where('estrecaps.Est_Id','=',$request->testimateid)
                ->get();

                $rsEstRecapeTAmt = Estrecap::select(DB::raw("SUM(estrecaps.Rcp_Amt) AS rcptol"))->where('estrecaps.Est_Id','=',$request->testimateid)
                ->get();

                return response()->json(array('msg'=> $rsEstRecapeList,'rcpamt'=>$rsEstRecapeTAmt,'rcsrno'=>$PrimaryNumber), 200);

            } catch (\Throwable $th) {}
        }


        //A.A.Regiter Details
          public function createviewAARegister(Request $request)
        {

           try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->usercode??'';
            $divid = auth()->user()->Div_id??0;
            $subdivid = auth()->user()->Sub_Div_id??0;
            //Get User Permission

            //Get Division Name
           // $divisionName = Division::select()

            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            //dd($UseUserPermission);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';

            if($UseUserPermission){
                    //Get All Estimates
                    $query = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Pat", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    //->where([['workmasters.AA_No','=',""],['workmasters.TS_No', '=',""]]);
                    //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']]);

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                $query = DB::table('workmasters')
                                ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                                // ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                                // ->Where('estmasters.AA_TS', '=','AA')
                                // ->Where('estmasters.AA_No','<>','');
                                ->where(DB::raw('left(`workmasters`.`Work_Id`,3)'),'=',$divid)
                                // ->Where('estmasters.AA_TS', '=','AA')
                                 ->Where('workmasters.AA_No','<>','');

                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                    $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                    $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                    $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                    $startDate = $request->txtsearchFromPreDate;
                                    $endDate = $request->txtsearchToPreDate;
                                    $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                    ->whereDate('workmasters.AA_Dt','>=', $endDate);
                                }

                                if($request->flg == 'f'){ //Red
                                    $query->where('workmasters.TS_No','=','');
                                }else if($request->flg == 't'){//Green
                                    $query->where('workmasters.TS_No','<>','');
                                }

                                $query->orderBy('workmasters.created_at', 'desc');
                                $project = $query->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                       $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                        $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                          $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }
                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $query->Where('estmasters.AA_TS', '=','AA')
                            ->Where('estmasters.AA_No','<>','');
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                ->whereDate('workmasters.AA_Dt','>=', $endDate);
                            }

                            if($request->flg == 'f'){ //Red
                                $query->where('workmasters.TS_No','=','');
                            }else if($request->flg == 't'){//Green
                                $query->where('workmasters.TS_No','<>','');
                            }

                            $query->orderBy('workmasters.created_at', 'desc');
                            $project =$query->paginate(10);
                            $initCount++;
                        }
                }else{
                            $project = DB::table('workmasters')
                            ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                            //->where([['workmasters.AA_No','=',""],['workmasters.TS_No', '=',""]])
                            //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']]);
                            ->Where('estmasters.AA_TS', '=','AA')
                            ->Where('estmasters.AA_No','<>','')
                            ->orderBy('workmasters.created_at', 'desc')
                            ->paginate(10);

                }

                // A.A.Register New Model Result----------------------------------------------------------------
                if($UseUserPermission){
                    //Get All Estimates
                    $queryAARegisterNew = DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep");

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $queryAARegisterNew = '';
                                $queryAARegisterNew = DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                                ->Where('estmasters.AA_TS', '=','AA')
                                ->Where('estmasters.AA_No','=','');
                                $queryAARegisterNew->orderBy('estmasters.created_at', 'desc');
                                $projectAARegisterNew = $queryAARegisterNew->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                       $queryAARegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                       $queryAARegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $queryAARegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $queryAARegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $queryAARegisterNew->Where('estmasters.AA_TS', '=','AA')->Where('estmasters.AA_No','=','');
                            $queryAARegisterNew->orderBy('estmasters.created_at', 'desc');
                            $projectAARegisterNew =$queryAARegisterNew->paginate(10);
                            $initCount++;
                        }
                }else{
                        $projectAARegisterNew =DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where('estmasters.Est_Id','=',0)
                            ->Where('estmasters.AA_TS', '=','AA')
                            ->Where('estmasters.AA_No','=','')
                            ->orderBy('estmasters.created_at', 'desc')
                            ->paginate(10);

                }

            // A.A.Register New Model Result----------------------------------------------------------------
            return view('aaregister/list',['data'=>$project,'dataAARegisterNew'=>$projectAARegisterNew]);

            } catch (\Throwable $th) {
                throw $th;
            }
        }
        //######################## A.A.Register ################################//
        public function AARegisterEntryForm(Request $request)
        {
          //  dd('ok');
            // Genrate unique ID Genration
            $uniquenumber = uniqid();

            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();

            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();

            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();

            // Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            return view('aaregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno]);
        }

        //#########################END A.A.Register Code############# //


        //######################## T.S.Register Details ################################//
        public function createviewTsRegister(Request $request)
        {
            try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->usercode??'';
            $divid = auth()->user()->Div_id??0;
            $subdivid = auth()->user()->Sub_Div_id??0;
            //Get User Permission

            //Get Division Name
           // $divisionName = Division::select()

            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';

            if($UseUserPermission){
                    //Get All Estimates
                    $query = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']]);
                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $query = '';
                                $query = DB::table('workmasters')
                                ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where(DB::raw('left(`workmasters`.`Work_Id`,3)'),'=',$divid)
                                ->where('workmasters.TS_No','<>',"");

                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                    $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                    $query->where("workmasters.TS_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                    $query->where([['workmasters.TS_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                    $startDate = $request->txtsearchFromPreDate;
                                    $endDate = $request->txtsearchToPreDate;
                                    $query->whereDate('workmasters.TS_Dt','<=', $startDate)
                                    ->whereDate('workmasters.TS_Dt','>=', $endDate);
                                }
                                if($request->flg == 'f'){ //Red
                                    $query->where('workmasters.DTP_App_No','=','');
                                }else if($request->flg == 't'){//Green
                                    $query->where('workmasters.DTP_App_No','<>','');
                                }



                                $usertypes = auth()->user()->usertypes;

                                $uid = auth()->user()->id ?? 0;

                                switch ($usertypes) {
                                    case 'SO':
                                    case 'JE':
                                    case 'PO':
                                        $id = DB::table('jemasters')->where('userid', $uid)->value('jeid');
                                                                         $query->where('workmasters.jeid',  '=',$id);


                                        break;




                                    case 'EE':
                                    case 'PA':
                                        $id = DB::table('eemasters')->where('userid', $uid)->value('eeid');
                                        $query->where('workmasters.EE_id',  '=',$id);

                                        break;

                                    case 'AAO':
                                        $id = DB::table('daomasters')->where('userid', $uid)->value('DAO_id');
                                        $query->where('workmasters.DAO_Id',  '=',$id);

                                        break;

                                    case 'DYE':
                                        $id = DB::table('dyemasters')->where('userid', $uid)->value('dye_id');
                                        $query->where('workmasters.DYE_id',  '=',$id);
    //dd($id);



                                        break;

                                    case 'Agency':
                                        $id = DB::table('agencies')->where('userid', $uid)->value('id');
                                        $query->where('workmasters.Agency_Id',  '=',$id);

                                        break;

                                    case 'SDC':
                                        $id = DB::table('sdcmasters')->where('userid', $uid)->value('SDC_id');
                                        $query->where('workmasters.SDC_id',  '=',$id);


                                        break;

                                    case 'audit':
                                        $id = DB::table('abmasters')->where('userid', $uid)->value('AB_ID');
                                        $query->where('workmasters.AB_Id',  '=',$id);


                                        break;

                                    }

                                $project = $query->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){
                                        $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                       $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{
                                       $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){
                                          $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                          $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{
                                         $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }
                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count
                                        $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $query->where('workmasters.TS_No','<>',"");
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("workmasters.TS_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['workmasters.TS_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.TS_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('workmasters.TS_Dt','<=', $startDate)
                                ->whereDate('workmasters.TS_Dt','>=', $endDate);
                            }
                            if($request->flg == 'f'){ //Red
                                $query->where('workmasters.DTP_App_No','=','');
                            }else if($request->flg == 't'){//Green
                                $query->where('workmasters.DTP_App_No','<>','');
                            }


                            $initCount++;
                        }

                        $usertypes = auth()->user()->usertypes;

                            $uid = auth()->user()->id ?? 0;

                            switch ($usertypes) {
                                case 'SO':
                                case 'JE':
                                case 'PO':
                                    $id = DB::table('jemasters')->where('userid', $uid)->value('jeid');
                                                                     $query->where('workmasters.jeid',  '=',$id);

                                    break;

                                case 'EE':
                                case 'PA':
                                    $id = DB::table('eemasters')->where('userid', $uid)->value('eeid');
                                    $query->where('workmasters.EE_id',  '=',$id);

                                    break;

                                case 'AAO':
                                    $id = DB::table('daomasters')->where('userid', $uid)->value('DAO_id');
                                    $query->where('workmasters.DAO_Id',  '=',$id);

                                    break;

                                case 'DYE':
                                    $id = DB::table('dyemasters')->where('userid', $uid)->value('dye_id');
                                    $query->where('workmasters.DYE_id',  '=',$id);
//dd($id);



                                    break;

                                case 'Agency':
                                    $id = DB::table('agencies')->where('userid', $uid)->value('id');
                                    $query->where('workmasters.Agency_Id',  '=',$id);

                                    break;

                                case 'SDC':
                                    $id = DB::table('sdcmasters')->where('userid', $uid)->value('SDC_id');
                                    $query->where('workmasters.SDC_id',  '=',$id);


                                    break;

                                case 'audit':
                                    $id = DB::table('abmasters')->where('userid', $uid)->value('AB_ID');
                                    $query->where('workmasters.AB_Id',  '=',$id);


                                    break;

                                }
                        $project =$query->paginate(10);
                }else{
                            $project = DB::table('workmasters')
                            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                            ->where('workmasters.TS_No','<>',"")
                            ->paginate(10);
                }
//dd($project);

                //T.S Register First Step-----------------------------------------------------------------------------
                if($UseUserPermission){
                    //Get All Estimates
                    $queryTSRegisterStepFirst = DB::table('workmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                    ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->select("workmasters.Work_Id","workmasters.Est_Id","workmasters.Work_Nm","workmasters.Work_Nm","workmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","workmasters.AA_Amt","workmasters.TS_Amt","workmasters.F_H_Code","workmasters.F_H_Code","workmasters.Work_Type","workmasters.TS_No","workmasters.TS_Dt","estmasters.Tot_Amt","estmasters.Date_Prep","workmasters.AA_No");

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $queryTSRegisterStepFirst = '';
                                $queryTSRegisterStepFirst = DB::table('workmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                                ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                ->select("workmasters.Work_Id","workmasters.Est_Id","workmasters.Work_Nm","workmasters.Work_Nm","workmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","workmasters.AA_Amt","workmasters.TS_Amt","workmasters.F_H_Code","workmasters.F_H_Code","workmasters.Work_Type","workmasters.TS_No","workmasters.TS_Dt","estmasters.Tot_Amt","estmasters.Date_Prep","workmasters.AA_No")->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                                ->Where([['workmasters.AA_No', '<>',''],['workmasters.TS_No','=','']]);
                                //Filter------------------------------------------------------------------------------
                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $queryTSRegisterStepFirst->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $queryTSRegisterStepFirst->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $queryTSRegisterStepFirst->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $queryTSRegisterStepFirst->whereDate('estmasters.Date_Prep','<=', $startDate)
                                ->whereDate('estmasters.Date_Prep','>=', $endDate);
                                }
                                //Filter ---------------------------------------------------------------------


                                $usertypes = auth()->user()->usertypes;

                                $uid = auth()->user()->id ?? 0;

                                switch ($usertypes) {
                                    case 'SO':
                                    case 'JE':
                                    case 'PO':
                                        $id = DB::table('jemasters')->where('userid', $uid)->value('jeid');
                                                                         $queryTSRegisterStepFirst->where('workmasters.jeid',  '=',$id);

                                        break;

                                    case 'EE':
                                    case 'PA':
                                        $id = DB::table('eemasters')->where('userid', $uid)->value('eeid');
                                        $queryTSRegisterStepFirst->where('workmasters.EE_id',  '=',$id);

                                        break;

                                    case 'AAO':
                                        $id = DB::table('daomasters')->where('userid', $uid)->value('DAO_id');
                                        $queryTSRegisterStepFirst->where('workmasters.DAO_Id',  '=',$id);

                                        break;

                                    case 'DYE':
                                        $id = DB::table('dyemasters')->where('userid', $uid)->value('dye_id');
                                        $queryTSRegisterStepFirst->where('workmasters.DYE_id',  '=',$id);
    //dd($id);



                                        break;

                                    case 'Agency':
                                        $id = DB::table('agencies')->where('userid', $uid)->value('id');
                                        $queryTSRegisterStepFirst->where('workmasters.Agency_Id',  '=',$id);

                                        break;

                                    case 'SDC':
                                        $id = DB::table('sdcmasters')->where('userid', $uid)->value('SDC_id');
                                        $queryTSRegisterStepFirst->where('workmasters.SDC_id',  '=',$id);


                                        break;

                                    case 'audit':
                                        $id = DB::table('abmasters')->where('userid', $uid)->value('AB_ID');
                                        $queryTSRegisterStepFirst->where('workmasters.AB_Id',  '=',$id);


                                        break;

                                    }



                                $projectTSRegisterNew = $queryTSRegisterStepFirst->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                       $queryTSRegisterStepFirst->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                       $queryTSRegisterStepFirst->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                          $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $queryTSRegisterStepFirst->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $queryTSRegisterStepFirst->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }


                                }

                            }

                             //Filter------------------------------------------------------------------------------
                             $queryTSRegisterStepFirst->Where([['workmasters.AA_No', '<>',''],['workmasters.TS_No','<>','']]);
                             if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $queryTSRegisterStepFirst->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $queryTSRegisterStepFirst->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $queryTSRegisterStepFirst->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $queryTSRegisterStepFirst->whereDate('estmasters.Date_Prep','<=', $startDate)
                                ->whereDate('estmasters.Date_Prep','>=', $endDate);
                                }
                                //Filter ---------------------------------------------------------------------


                            $initCount++;
                        }


                        $usertypes = auth()->user()->usertypes;

                        $uid = auth()->user()->id ?? 0;

                        switch ($usertypes) {
                            case 'SO':
                            case 'JE':
                            case 'PO':
                                $id = DB::table('jemasters')->where('userid', $uid)->value('jeid');
                                                                 $queryTSRegisterStepFirst->where('workmasters.jeid',  '=',$id);

                                break;

                            case 'EE':
                            case 'PA':
                                $id = DB::table('eemasters')->where('userid', $uid)->value('eeid');
                                $queryTSRegisterStepFirst->where('workmasters.EE_id',  '=',$id);

                                break;

                            case 'AAO':
                                $id = DB::table('daomasters')->where('userid', $uid)->value('DAO_id');
                                $queryTSRegisterStepFirst->where('workmasters.DAO_Id',  '=',$id);

                                break;

                            case 'DYE':
                                $id = DB::table('dyemasters')->where('userid', $uid)->value('dye_id');
                                $queryTSRegisterStepFirst->where('workmasters.DYE_id',  '=',$id);
//dd($id);



                                break;

                            case 'Agency':
                                $id = DB::table('agencies')->where('userid', $uid)->value('id');
                                $queryTSRegisterStepFirst->where('workmasters.Agency_Id',  '=',$id);

                                break;

                            case 'SDC':
                                $id = DB::table('sdcmasters')->where('userid', $uid)->value('SDC_id');
                                $queryTSRegisterStepFirst->where('workmasters.SDC_id',  '=',$id);


                                break;

                            case 'audit':
                                $id = DB::table('abmasters')->where('userid', $uid)->value('AB_ID');
                                $queryTSRegisterStepFirst->where('workmasters.AB_Id',  '=',$id);


                                break;

                            }

                        $projectTSRegisterNew = $queryTSRegisterStepFirst->paginate(10);
                        //dd('ok2');
                } else{
                    $uid = auth()->user()->id??0;
                    $jeid = DB::table('jemasters')->where('userid' , $uid)->value('jeid');

                    $usertypes = auth()->user()->usertypes;

                    $uid = auth()->user()->id ?? 0;
                    $projectTSRegisterNew =DB::table('workmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->select("workmasters.Work_Id","workmasters.Est_Id","workmasters.Work_Nm","workmasters.Work_Nm","workmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","workmasters.AA_Amt","workmasters.TS_Amt","workmasters.F_H_Code","workmasters.F_H_Code","workmasters.Work_Type","workmasters.TS_No","workmasters.TS_Dt","estmasters.Tot_Amt","estmasters.Date_Prep","workmasters.AA_No")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                            ->Where([['workmasters.AA_No', '<>',''],['workmasters.TS_No','=','']]);




                         //Filter------------------------------------------------------------------------------
                        //  if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        //     $projectTSRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        //     }
                        //     if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        //     $projectTSRegisterNew->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        //     }
                        //     if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        //     $projectTSRegisterNew->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        //     }
                        //     if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        //     $startDate = $request->txtsearchFromPreDate;
                        //     $endDate = $request->txtsearchToPreDate;
                        //     $projectTSRegisterNew->whereDate('estmasters.Date_Prep','<=', $startDate)
                        //     ->whereDate('estmasters.Date_Prep','>=', $endDate);
                        //     }
                            //Filter ---------------------------------------------------------------------



                            switch ($usertypes) {
                                case 'SO':
                                case 'JE':
                                case 'PO':
                                    $id = DB::table('jemasters')->where('userid', $uid)->value('jeid');
                                                                     $projectTSRegisterNew->where('workmasters.jeid',  '=',$id);

                                    break;

                                case 'EE':
                                case 'PA':
                                    $id = DB::table('eemasters')->where('userid', $uid)->value('eeid');
                                    $projectTSRegisterNew->where('workmasters.EE_id',  '=',$id);

                                    break;

                                case 'AAO':
                                    $id = DB::table('daomasters')->where('userid', $uid)->value('DAO_id');
                                    $projectTSRegisterNew->where('workmasters.DAO_Id',  '=',$id);

                                    break;

                                case 'DYE':
                                    $id = DB::table('dyemasters')->where('userid', $uid)->value('dye_id');
                                    $projectTSRegisterNew->where('workmasters.DYE_id',  '=',$id);
//dd($id);



                                    break;

                                case 'Agency':
                                    $id = DB::table('agencies')->where('userid', $uid)->value('id');
                                    $projectTSRegisterNew->where('workmasters.Agency_Id',  '=',$id);

                                    break;

                                case 'SDC':
                                    $id = DB::table('sdcmasters')->where('userid', $uid)->value('SDC_id');
                                    $projectTSRegisterNew->where('workmasters.SDC_id',  '=',$id);


                                    break;

                                case 'audit':
                                    $id = DB::table('abmasters')->where('userid', $uid)->value('AB_ID');
                                    $projectTSRegisterNew->where('workmasters.AB_Id',  '=',$id);


                                    break;

                                }
                            $projectTSRegisterNew = $projectTSRegisterNew->paginate(10);

//dd($projectTSRegisterNew);
                }
                //T.S Registration First stage-----------------------------------------------------------------------


                // T.S.Register Second Steps Model  Popup Result ---------------------------------------------------
                if($UseUserPermission){
                    //Get All Estimates
                    $queryAARegisterNew = DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")
                    ->Where([['estmasters.AA_TS', '=','TS'],['estmasters.AA_No','=',''],['estmasters.TS_No','=','']]);

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $queryAARegisterNew = '';
                                $queryAARegisterNew = DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                                ->Where([['estmasters.AA_TS', '=','TS'],['estmasters.AA_No','=',''],['estmasters.TS_No','=','']])
                                ->paginate(10);
                                $projectAARegisterNew = $queryAARegisterNew;
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                       $queryAARegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                       $queryAARegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $queryAARegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $queryAARegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $queryAARegisterNew->Where([['estmasters.AA_TS', '=','TS'],['estmasters.AA_No','=',''],['estmasters.TS_No','=','']]);
                            $projectAARegisterNew =$queryAARegisterNew->paginate(10);
                            $initCount++;
                            //dd('ok2');
                        }
                }else{
                    //dd('ok');
                        $projectAARegisterNew =DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where('estmasters.Est_Id','=',0)
                            ->Where([['estmasters.AA_TS', '=','TS'],['estmasters.AA_No','<>',''],['estmasters.TS_No','=','']])
                        ->paginate(10);

                        // $projectAARegisterNew = DB::table('estmasters')
                        // ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        // ->select(
                        //     "estmasters.Est_Id", "estmasters.Est_No", "estmasters.Work_Nm", "estmasters.Work_Nm_M",
                        //     "subdivms.Sub_Div", "subdivms.Sub_Div_M", "estmasters.Tot_Amt", "estmasters.F_H_Code",
                        //     "estmasters.Work_Type", "estmasters.TS_No", "estmasters.Date_Prep", "estmasters.AA_No",

                        // )
                        // ->where('estmasters.AA_TS', '=', 'TS')
                        // ->where('estmasters.AA_No', '=', '')
                        // ->where('estmasters.TS_No', '=', '')
                        // ->paginate(10);


                        //dd($projectAARegisterNew);

                }

            // T.S.Register New Model Result----------------------------------------------------------------
            return view('tsregister/list',['data'=>$project,'TSRegisterStepFirst'=>$projectTSRegisterNew,'dataAARegisterNew'=>$projectAARegisterNew]);
            //return view('tsregister/list',['data'=>$project,'TSRegisterStepFirst'=>$projectTSRegisterNew]);
            } catch (\Throwable $th) {
                throw $th;
            }
        }


        public function TSRegisterEntryForm(Request $request)
        {
            // Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();

            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();

            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();

            // Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
            $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
            $RecapSrno=1;
            }
            return view('tsregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno]);
        }
        //#########################END T.S.Register Details############# //



        public function EditViewAAEstimateForm(Request $request)
            {
                try{

                //Genrate unique ID Genration
                $uniquenumber = uniqid();

                // Logged User Session
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------

                $FinanacialYear = DB::table('acyrms')
                ->selectRaw('`Ac_Yr`')
                ->get();

                // Get Division ID To Division Name
                $rsDivisionDtls = DB::table('divisions')
                ->selectRaw('`div_m`,`div`,`div_id`')
                ->where('div_id','=',$divid)->get();
                $rsDiv = json_decode($rsDivisionDtls,true);

                //Get Selected Divisions All Subdivisions
                $rsSubDivisionDtls = DB::table('subdivms')
                ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
                ->where('Div_Id','=',$divid)->get();
                $rsWorkType = DB::table('worktypes')
                ->selectRaw('`id`,`worktype`')
                ->get();
                $rsTalukas = DB::table('talms')
                ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
                ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
                ->where('divisions.div_id','=',$divid)
                ->get();


                //Scope Master data
                $scopeMasterList = Scopm::get();

                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$uniquenumber)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $RecapSrno=$RSNewPKID[0]->Sr_No;
                }else{
                    $RecapSrno=1;
                }

                //Get Estimate Details
                // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
                $rsestimate = DB::table('estmasters')
                ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS', 'po_id', DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
                ->where('Est_Id','=',$request->id)
                ->first();

                 //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

                $FinanacialYear1 = DB::table('acyrms')
                ->selectRaw('Ac_Yr')
                ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
                ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
                ->first();

                //Get Estimate Scope
                $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

                //Get Estimate Recape
                $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

                $rspo = DB::table('jemasters')->get();

                //Get Estimate
                $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

                return view('aaregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'polist'=>$rspo],compact('rsworkmaster'));

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        public function EditViewAAEstimateForm1(Request $request)
        {
            try{

            //Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            //Get Estimate Details
            // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
            $rsestimate = DB::table('estmasters')
            ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS', 'po_id', DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
            ->where('Est_Id','=',$request->id)
            ->first();

             //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

            $FinanacialYear1 = DB::table('acyrms')
            ->selectRaw('Ac_Yr')
            ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
            ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
            ->first();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

            $rspo = DB::table('jemasters')->get();

            //Get Estimate
            $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

            return view('aaregister/add1',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'polist'=>$rspo],compact('rsworkmaster'));

        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    public function EditViewAAEstimateForm11(Request $request)
    {
        try{

        //Genrate unique ID Genration
        $uniquenumber = uniqid();

        // Logged User Session
        // login user session Data----------------------------
        $divid = auth()->user()->Div_id??0;
        $usercode = auth()->user()->usercode??'';
        $subdivid = auth()->user()->Sub_Div_id??0;
        // login user session Data----------------------------

        $FinanacialYear = DB::table('acyrms')
        ->selectRaw('`Ac_Yr`')
        ->get();

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();
        $rsDiv = json_decode($rsDivisionDtls,true);

        //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();
        $rsTalukas = DB::table('talms')
        ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
        ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
        ->where('divisions.div_id','=',$divid)
        ->get();


        //Scope Master data
        $scopeMasterList = Scopm::get();

        $SQLNewPKID = DB::table('estrecaps')
        ->selectRaw('Sr_No + 1 as Sr_No')
        ->orderBy('Sr_No', 'desc')
        ->where('Est_Id','=',$uniquenumber)
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
            $RecapSrno=$RSNewPKID[0]->Sr_No;
        }else{
            $RecapSrno=1;
        }

        //Get Estimate Details
        // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
        $rsestimate = DB::table('estmasters')
        ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS', 'po_id', DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
        ->where('Est_Id','=',$request->id)
        ->first();

         //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

        $FinanacialYear1 = DB::table('acyrms')
        ->selectRaw('Ac_Yr')
        ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
        ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
        ->first();

        //Get Estimate Scope
        $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

        //Get Estimate Recape
        $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

        $rspo = DB::table('jemasters')->get();

        //Get Estimate
        $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

        return view('aaregister/dtpR',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'polist'=>$rspo],compact('rsworkmaster'));

    } catch (\Throwable $th) {
        //throw $th;
    }

}

        public function generatePDF(Request $request)
        {
            try {
                $rsworkmaster = Workmaster::where('Est_Id', '=', $request->id)->first();
                $esthd = DB::table('estmasters')->where('Est_Id', '=', $request->id)->first();
                $imagePath = public_path('photos/zplogo5.jpeg');
                $html = '
                <!DOCTYPE html>
                <html lang="mr">
                <head>
                    <meta charset="UTF-8">
                     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

                    <title>AA Estimate PDF</title>
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap");

                        body {
                            font-family: "freeserif";
                        }
                         .header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px;
                    width: 100%;
                }
                .header img {
                    width: 30px;
                     }
                .header-text {
                    flex-grow: 1;
                    text-align: right;
                    font-size: 14px;
                }
        .doc-number {
            font-size: 0.9em;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin: 20px 0;
        }
        .main-text {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer-list {
            margin: 20px 0;
        }
        .footer-list ol {
            margin-left: 20px;
        }
        .signature-section {
            text-align: right;
            margin-top: 40px;
        }
        .copy-section {
            margin-top: 30px;
        }
        .page-number {
            text-align: right;
            margin-top: 40px;
        }
                    </style>
                </head>
                <body>

   <div class="header" style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px;">

    <!-- डावीकडील लोगो -->
    <div style="flex: 0 0 auto;">
        <img src="' . $imagePath . '" alt="Logo" width="80">
    </div>

    <!-- उजवीकडील मजकूर -->
    <div style="flex: 1; text-align: right; padding-right: 20px; margin-top: -80px;">
        <div style="font-weight: bold;">जा.क्र. बांधकाम/प्रकल्प-2//2024</div>
        <div>बांधकाम विभाग,</div>
        <div>जिल्हा परिषद, सांगली.</div>
        <div>दिनांक :- 2 /02/2024.</div>
    </div>

</div>
  <div class="title" style="font-size: 24px;">हुकुम</div>
  <div class="main-text" style="text-align: left; padding-left: 20px;">
                        महाराष्ट्र जिल्हा परिषद व पंचायत समित्या अधिनियम लेखा संहिता 1968 नियम 4 परिशिष्ट II विभाग । विभाग मधील अधिकारान्वये खाली नमूद केलेल्या कामांना प्रशासकीय मंजूरी देण्यात येत आहे.
                     </div>
                     <table>
                        <tr>
                            <th>अ.क्र.</th>
                            <th>कामाचे नांव</th>
                            <th>अंदाजपत्रकीय रक्कम रुपये</th>
                            <th>प्रशासकीय मंजूरीची रक्कम रुपये</th>
                             <th>सन 2023-24 मधील अर्थिक तरतूद</th>
                            <th>अंदाजपत्रकीय खर्चाचे सदर</th>
                        </tr>
                        <tr>
                       <td>1</td>
                        <td>' . ($rsworkmaster->Work_Nm_M ?? 'N/A') . '</td>
                        <td>' . (\App\Helpers\CommonHelper::formatIndianRupees($esthd->Tot_Amt) ?? 'N/A') . '</td>
                         <td>' . (\App\Helpers\CommonHelper::formatIndianRupees($rsworkmaster->AA_Amt) ?? 'N/A') . '</td>
                        <td>  </td>
                         <td>' . ($rsworkmaster->F_H_Code ?? 'N/A') . '</td>
                         </tr>
                          </table>

                      <div class="footer-list">
                        <ol>
                            <li>
                            <label for="item1">वर नमूद केलेल्या रक्कमांचा खर्च त्या त्याकामा पुढे नमूद करण्यात आलेल्या सन 2023-24 च्या अंदाज पत्रकीय मंजूर तरतूदीच्या सदरामधून करणेचा आहे.</label>
                            </li>
                            <li>
                                <label for="item2">वर नमूद केलेल्या कामावरील निधी पर्यंत खर्च करणेचा आहे.</label>
                            </li>
                            <li>
                                <label for="item3">काम सुरु करणे पूर्वी प्रस्तावित काम अन्य योजनेमधून पूर्ण अथवा मंजूरी नसलेची खात्री करावी.</label>
                            </li>
                        </ol>
                    </div>

                    <div class="signature-section" style="text-align: right; padding-right: 900px;">
                        कार्यकारी अभियंता (इवद)<br>
                        जिल्हा परिषद,सांगली.
                    </div>

                    <div class="copy-section" style="text-align: left; padding-left: 20px;">
                        प्रतिलिपी:<br>
                        1) मुख्य लेखा व वित्त अधिकारी,जिल्हा परिषद,सांगली.<br>
                        2)उप कार्यकारी अभियंता (इवद) जि.प.सांगली यांना माहितीसाठी व पुढील कार्यवाही साठी<br>
                        3) लेखा शाखा बांधकाम विभाग,जि.प.सांगली.
                    </div>
                </body>
                </html>';

        //         $mpdf = new \Mpdf\Mpdf([
        //             'default_font' => 'freeserif'
        //         ]);

        // $mpdf->WriteHTML($html);
        // $mpdf->Output("Estimate_Report.pdf", "D");
        $mpdf = new \Mpdf\Mpdf();

        $logo = public_path('photos/zplogo5.jpeg');

        // Set watermark image
        $mpdf->SetWatermarkImage($logo);

        // Show watermark image
        $mpdf->showWatermarkImage = true;

        // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
        $mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed

        //$mpdf->WriteHTML($html);
        $mpdf->autoScriptToLang =true;
        $mpdf->autoLangToFont =true;

        $mpdf->WriteHTML($html);
        $mpdf->Output("A.A.Report.pdf", "D");
            } catch (\Throwable $th) {
                return response()->json(['error' => 'PDF निर्माण करताना त्रुटी आली.'], 500);
            }
        }

        public function generatePDF3(Request $request)
        {
            try {
                $rsworkmaster = Workmaster::where('Est_Id', '=', $request->id)->first();
                $esthd = DB::table('estmasters')->where('Est_Id', '=', $request->id)->first();
                $imagePath = public_path('photos/zplogo5.jpeg');
                $html = '
                <!DOCTYPE html>
                <html lang="mr">
                <head>
                    <meta charset="UTF-8">
                     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

                    <title>AA Estimate PDF</title>
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap");

                        body {
                            font-family: "freeserif";
                        }
                         .header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px;
                    width: 100%;
                }
                .header img {
                    width: 30px;
                     }
                .header-text {
                    flex-grow: 1;
                    text-align: right;
                    font-size: 14px;
                }
        .doc-number {
            font-size: 0.9em;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin: 20px 0;
        }
        .main-text {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer-list {
            margin: 20px 0;
        }
        .footer-list ol {
            margin-left: 20px;
        }
        .signature-section {
            text-align: right;
            margin-top: 40px;
        }
        .copy-section {
            margin-top: 30px;
        }
        .page-number {
            text-align: right;
            margin-top: 40px;
        }
                    </style>
                </head>
                <body>

   <div class="header" style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px;">

    <!-- डावीकडील लोगो -->
    <div style="flex: 0 0 auto;">
        <img src="' . $imagePath . '" alt="Logo" width="80">
    </div>

    <!-- उजवीकडील मजकूर -->
    <div style="flex: 1; text-align: right; padding-right: 20px; margin-top: -80px;">
        <div style="font-weight: bold;">जा.क्र. बांधकाम/प्रकल्प-2//2024</div>
        <div>बांधकाम विभाग,</div>
        <div>जिल्हा परिषद, सांगली.</div>
        <div>दिनांक :- 2 /02/2024.</div>
    </div>

</div>
  <div class="title" style="font-size: 24px;">हुकुम</div>
  <div class="main-text" style="text-align: left; padding-left: 20px;">
                        महाराष्ट्र जिल्हा परिषद व पंचायत समित्या अधिनियम लेखा संहिता 1968 नियम 4 परिशिष्ट II विभाग । विभाग मधील अधिकारान्वये खाली नमूद केलेल्या कामांना प्रशासकीय मंजूरी देण्यात येत आहे.
                     </div>
                     <table>
                        <tr>
                            <th>अ.क्र.</th>
                            <th>कामाचे नांव</th>
                            <th>अंदाजपत्रकीय रक्कम रुपये</th>
                            <th>प्रशासकीय मंजूरीची रक्कम रुपये</th>
                             <th>सन 2023-24 मधील अर्थिक तरतूद</th>
                            <th>अंदाजपत्रकीय खर्चाचे सदर</th>
                        </tr>
                        <tr>
                       <td>1</td>
                        <td>' . ($rsworkmaster->Work_Nm_M ?? 'N/A') . '</td>
                        <td>' . (\App\Helpers\CommonHelper::formatIndianRupees($esthd->Tot_Amt) ?? 'N/A') . '</td>
                         <td>' . (\App\Helpers\CommonHelper::formatIndianRupees($rsworkmaster->AA_Amt) ?? 'N/A') . '</td>
                        <td>  </td>
                         <td>' . ($rsworkmaster->F_H_Code ?? 'N/A') . '</td>
                         </tr>
                          </table>

                      <div class="footer-list">
                        <ol>
                            <li>
                            <label for="item1">वर नमूद केलेल्या रक्कमांचा खर्च त्या त्याकामा पुढे नमूद करण्यात आलेल्या सन 2023-24 च्या अंदाज पत्रकीय मंजूर तरतूदीच्या सदरामधून करणेचा आहे.</label>
                            </li>
                            <li>
                                <label for="item2">वर नमूद केलेल्या कामावरील निधी पर्यंत खर्च करणेचा आहे.</label>
                            </li>
                            <li>
                                <label for="item3">काम सुरु करणे पूर्वी प्रस्तावित काम अन्य योजनेमधून पूर्ण अथवा मंजूरी नसलेची खात्री करावी.</label>
                            </li>
                        </ol>
                    </div>

                    <div class="signature-section" style="text-align: right; padding-right: 900px;">
                        कार्यकारी अभियंता (इवद)<br>
                        जिल्हा परिषद,सांगली.
                    </div>

                    <div class="copy-section" style="text-align: left; padding-left: 20px;">
                        प्रतिलिपी:<br>
                        1) मुख्य लेखा व वित्त अधिकारी,जिल्हा परिषद,सांगली.<br>
                        2)उप कार्यकारी अभियंता (इवद) जि.प.सांगली यांना माहितीसाठी व पुढील कार्यवाही साठी<br>
                        3) लेखा शाखा बांधकाम विभाग,जि.प.सांगली.
                    </div>
                </body>
                </html>';

        //         $mpdf = new \Mpdf\Mpdf([
        //             'default_font' => 'freeserif'
        //         ]);

        // $mpdf->WriteHTML($html);
        // $mpdf->Output("Estimate_Report.pdf", "D");
        $mpdf = new \Mpdf\Mpdf();

        $logo = public_path('photos/zplogo5.jpeg');

        // Set watermark image
        $mpdf->SetWatermarkImage($logo);

        // Show watermark image
        $mpdf->showWatermarkImage = true;

        // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
        $mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed

        //$mpdf->WriteHTML($html);
        $mpdf->autoScriptToLang =true;
        $mpdf->autoLangToFont =true;

        $mpdf->WriteHTML($html);
        $mpdf->Output("A.A.Report.pdf", "D");
            } catch (\Throwable $th) {
                return response()->json(['error' => 'PDF निर्माण करताना त्रुटी आली.'], 500);
            }
        }

        public function generatePDF1(Request $request)
        {
            try {
                $rsworkmaster = Workmaster::where('Est_Id', '=', $request->id)->first();
                $esthd = DB::table('estmasters')->where('Est_Id', '=', $request->id)->first();
                $imagePath = public_path('photos/zplogo5.jpeg');

                $html = '
                <!DOCTYPE html>
                <html lang="mr">
                <head>
                    <meta charset="UTF-8">
                     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

                    <title>AA Estimate PDF</title>
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap");

                      body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1290px;
            margin: 0 auto;
        }
        .header {

            margin-bottom: 30px;
        }
        .doc-number {
            font-size: 0.9em;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin: 20px 0;
        }
        .main-text {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer-list {
            margin: 20px 0;
        }
        .footer-list ol {
            margin-left: 20px;
        }
        .signature-section {
            text-align: right;
            margin-top: 40px;
        }
        .copy-section {
            margin-top: 30px;
        }
        .page-number {
            text-align: right;
            margin-top: 40px;
        }
                    </style>
                </head>
                <body>
                    <div class="header" style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px;">

    <!-- डावीकडील लोगो -->
    <div style="flex: 0 0 auto;">
        <img src="' . $imagePath . '" alt="Logo" width="80">
    </div>

    <!-- उजवीकडील मजकूर -->
    <div style="flex: 1; text-align: right; padding-right: 20px; margin-top: -80px;">
        <div style="font-weight: bold;">जा.क्र. बांधकाम/प्रकल्प-2//2024</div>
        <div>बांधकाम विभाग,</div>
        <div>जिल्हा परिषद, सांगली.</div>
        <div>दिनांक :- 2 /02/2024.</div>
    </div>

</div>

 <div class="main-text" style="text-align: left; padding-left: 20px;">
                        शासन निर्णय क्र.क्रेट पी.अ.1084 सीआर/114 (भाग II ) 33 दि.17 जुलै 1988 ने प्रदान केलेले अधिकार.
                    </div>

                    <table>
                        <tr>
                            <th>अ.क्र.</th>
                            <th>कामाचे नांव</th>
                            <th>प्रशासकीय मंजुरीची रक्कम</th>
                            <th>प्रशासकीय मंजुरीचा नंबर व तारीख</th>
                            <th>तांत्रिक मंजूरी अंकी</th>
                            <th>मंजूरी रक्कम अक्षरी</th>
                            <th>खर्चाचे सदर</th>
                            <th>तांत्रिक मंजूरी रजिस्टर क्रमांक</th>
                        </tr>
                <tr>
            <td>1</td>
            <td>' . ($rsworkmaster->Work_Nm_M ?? 'N/A') . '</td>
            <td>' . (\App\Helpers\CommonHelper::formatIndianRupees($rsworkmaster->AA_Amt) ?? 'N/A') . '</td>
            <td>' . ($rsworkmaster->AA_No ?? 'N/A') . '<br><br>' . ($rsworkmaster->AA_Dt ?? 'N/A') . '</td>
            <td>' .  (\App\Helpers\CommonHelper::formatIndianRupees($rsworkmaster->TS_Amt) ?? 'N/A') . '</td>
            <td>' . (\App\Helpers\CommonHelper::convertAmountToWords($rsworkmaster->TS_Amt) ?? 'N/A') . '</td>
            <td>' . ($rsworkmaster->F_H_Code ?? 'N/A') . '</td>
            <td>8 <br><br> 02/2024</td>
        </tr>

                                 </table>
 <div class="footer-list">
                        <ol>
                            <li>

                                <label for="item1"> सदर कामाच्या मूल्यमापनासाठी मानकप्रमाणे गुण नियंत्रण चाचण्या घेणे बंधनकारक आहे.त्याबरीचे देयक अदा केल्या संबधित उप अभियंता यांना जबाबदार धरण्यात येईल.
                                </label>
                            </li>
                            <li>

                                <label for="item2">मंजूर आराखड्या प्रमाणे कमा करणे बंधनकारक आहे.त्यातील बदलास मा.अ.अधिकारीच्या जबाबदार राहतील.
                                </label>
                            </li>

                        </ol>
                    </div>

                   <div class="signature-section" style="text-align: right; padding-right: 900px;">
                        कार्यकारी अभियंता ((इवद)) )<br>
                        जिल्हा परिषद,सांगली.
                    </div>

                    <div class="copy-section" style="text-align: left; padding-left: 20px;">
                        प्रतिलिपी: <br>
                        1) मुख्य लेखा व वित्त अधिकारी,जिल्हा परिषद,सांगली.<br>
                        2)उप कार्यकारी अभियंता (इवद)) जि.प.सांगली यांना माहितीसाठी व पुढील कार्यवाही साठी<br>
                        3) लेखा शाखा बांधकाम विभाग,जि.प.सांगली.
                    </div>
<br>
                </body>
                </html>';

        //         $mpdf = new \Mpdf\Mpdf([
        //             'default_font' => 'freeserif'
        //         ]);

        // $mpdf->WriteHTML($html);
        // $mpdf->Output("Estimate_Report.pdf", "D");
        $mpdf = new \Mpdf\Mpdf();

        $logo = public_path('photos/zplogo5.jpeg');

        // Set watermark image
        $mpdf->SetWatermarkImage($logo);

        // Show watermark image
        $mpdf->showWatermarkImage = true;

        // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
        $mpdf->watermarkImageAlpha = 0.1;

        //$mpdf->WriteHTML($html);
        $mpdf->autoScriptToLang =true;
        $mpdf->autoLangToFont =true;

        $mpdf->WriteHTML($html);
        $mpdf->Output("T.S.Report.pdf", "D");
            } catch (\Throwable $th) {
                return response()->json(['error' => 'PDF निर्माण करताना त्रुटी आली.'], 500);
            }
        }

        public function generatePDF2(Request $request)
        {
            try {
                $rsworkmaster = Workmaster::where('Est_Id', '=', $request->id)->first();
                $esthd = DB::table('estmasters')->where('Est_Id', '=', $request->id)->first();
                $imagePath = public_path('photos/zplogo5.jpeg');

                $html = '
                <!DOCTYPE html>
                <html lang="mr">
                <head>
                    <meta charset="UTF-8">
                     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

                    <title>जिल्हा परिषद, सांगली</title>
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap");

                         body {

            line-height: 1.6;
            padding: 20px;
            max-width: 1290px;
        }
        .header {

            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
        }
        .header p {
            font-size: 16px;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;

        }
        th, td {
            border: 1px solid black;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        .notes {
            margin-top: 20px;
        }
        .notes p {
            margin: 5px 0;
        }
        .amount {
            text-align: right;
            width: 100px;
        }

        .date {
            width: 120px;
        }

        .description {
            width: 300px;
        }
        .tender-table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
  }

  .tender-table td, .tender-table th {
    border: 1px solid black;
    padding: 8px;
  }

  .tender-table tr td:first-child {
    width: 25%;
  }

  .header-row {
    display: flex;
    border: 1px solid black;
    margin-bottom: -1px;
  }

  .header-left {
    flex: 1;
    padding: 8px;
    border-right: 1px solid black;
  }

  .header-right {
    width: 600px;
    padding: 8px;
  }

  .financial-table {
            border-collapse: collapse;
            width: 100%;
            max-width: 1100px;
            margin: 20px auto;
            font-family: Arial, sans-serif;
        }

        .financial-table th,
        .financial-table td {
            border: 1px solid #000;
            padding: 8px 15px;
            text-align: left;
        }

        .financial-table td:nth-child(2),
        .financial-table td:nth-child(3),
        .financial-table td:nth-child(4) {
            text-align: right;
            width: 100px;
        }

        .financial-table tr {
            height: 40px;
        }

        .total-row {
            font-weight: bold;
        }
        .section h2 {
            margin-bottom: 10px;
        }
        @media (max-width: 600px) {
            .tender-info {
                grid-template-columns: auto auto;
            }
        }
                    </style>
                </head>
                <body>
                   <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px;">


                        <div style="flex: 0 0 auto; margin-top: -50px;">
   <img src="' . $imagePath . '" alt="Logo" width="110">
     </div>


                       <div style="flex: 1; text-align: right; padding-right: 20px;margin-top: -100px;line-height: 0;">
                             <h2 style="margin: 0; padding: 0;">जिल्हा परिषद, सांगली</h2>
    <h2 style="margin: 0; padding: 0;">बांधकाम विभाग</h2>
    <h4 style="margin: 0; padding: 0;">मध्यवर्ती प्रशासकीय इमारत</h4>
    <div style="margin: 0; padding: 0;">सांगली- मिरज रोड, पुष्कराज चौक, सांगली</div>
    <div style="margin: 0; padding: 0;">फोन नं 0233-2372719</div>
    <div style="margin: 0; padding: 0;">Email ID: eeworkszpsangli@gmail.com</div>
                        </div>
                         </div>
                    <div style="text-align: center; margin-top: 10px;">
                        <h2>कार्यारंभ आदेश</h2>
                    </div>
                    <div class="header-row">
                        <div class="header-left">जा.क्र.बांध/निविदा/का.अ/  ' . ($rsworkmaster->WO_No ?? 'N\A') . ' /23 दिनांक- ' . ($rsworkmaster->Wo_Dt ?? 'N\A') . '</div>
                <div class="header-right"> निविदा कारपत्र क्र. ब.' . ($rsworkmaster->Agree_No ?? 'N\A') . ' / ' . ($rsworkmaster->Agree_Dt ?? 'N\A') . '</div>
            </div>

  <table class="tender-table">

  <tr>
                        <th > लेखाशिर्ष : -</th>
                                      <th colspan="4">' . ($rsworkmaster->F_H_Code ?? 'N/A') . '</th>

                    </tr>

                        <tr>
                          <td>ई-निविदा सूचना क्र.</td>
                          <td>2</td>
                          <td>काम क्र.</td>
                          <td>8</td>
                        </tr>
                        <tr>
                            <td>Work ID</td>
                           <td>' . ($rsworkmaster->Work_Id ?? 'N/A') . '</td>
                          <td>ई-निविदा आयडी क्र.</td>
                           <td>' . ($rsworkmaster->Tender_Id ?? 'N/A') . '</td>

                        </tr>
                      </table>
<div> प्रति,</div>
<table>
   <tr>
                                <th colspan="2">मक्तेदाराचे नांव व पत्ता :-</th>
                                <th colspan="4">' . ($agencyDetails->agency_nm_m ?? 'N/A') . ' ' . ($agencyDetails->Agency_Ad1 ?? 'N/A') . '</th>
                                </th>
                            </tr>
                            <tr>
                                <td>पेन कार्ड नं:</td>
                                <td>' . ($agencyDetails->Pan_no ?? 'N/A') . '</td>
                                <td>GST No.</td>
                                 <td>' . ($agencyDetails->Gst_no ?? 'N/A') . '</td>
                                <td>मो.क्र.</td>
                                <td>' . ($agencyDetails->Agency_Phone ?? 'N/A') . '</td>
                            </tr>
                            <tr>
                                <td colspan="2">कामाचे नांव:</td>
                               <th colspan="4">' . ($rsworkmaster->Work_Nm_M ?? 'N/A') . '</th>
                            </tr>
                            <tr>
                                <td >1. अनामत र.रु. </td>
                                <td>81160</td>
                                <td>(EMD) बयाणा डी.डी. र.रु. </td>
                                <td>0</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="1">2. चलनाने भरणा </td>
                                <td colspan="1">41000</td>
                                <td colspan="1">DCC बैंक खाते क्र. 773 </td>
                                <td colspan="1" >दिनांक </td>
                                <td colspan="2"></td>

                            </tr>
                            <tr>
                                <td>एकूण जमा रु.</td>
                                <td>41000</td>
                                <td>देयकातून वसूल करावयाची र.रु</td>
                                <td>40160</td>
                                <td>एकूण</td>
                                <td>81160</td>
                            </tr>

                            <tr>
                                <td>3) परफॉरमन्स सिक्युरिटी डिपॉझीट </td>
                                <td>272000</td>
                                <td>परफॉरमन्स सिक्युरिटी डिपॉझीट चलनाने भरणा </td>
                                <td>दिनांक</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6">या कार्यालया कडील मक्ता मंजूरी टिपणी दिनांक 04-09-2024 </td>
                            </tr>
                        </table>
                                                 अनामत रक्कम व निविदा विषयक सर्व कागदपत्रे आपण पुर्ण केलेली असलेने आपली वरील कामाची निविदा खालीलप्रमाणे मान्य करणेत येत आहे.

                         <table>

   <tr>
                                <th colspan="1" rowspan="3">1) निविदा प्रसिध्दी र.रु. </th>
                                <th colspan="1" rowspan="3">' . ( $rsworkmaster->Tnd_Amt ?? 'N/A' ).'</th>
                                <th colspan="1">2) निविदेचा दर </th>

  <th colspan="4">
                ' . ($rsworkmaster->A_B_Pc == 0 ? 'अंदाजपत्रकीय दराने' :
                     ($rsworkmaster->Above_Below == 'Above' ? 'अंदाजपत्रकीय दरापेक्षा ' . number_format($rsworkmaster->A_B_Pc, 2) . ' % जास्त दराने' :
                     'अंदाजपत्रकीय दरापेक्षा ' . number_format($rsworkmaster->A_B_Pc, 2) . ' % कमी दराने')) . '
            </th>

                                <th colspan="1"></th>

                            </tr>
                              <tr>
                                <td colspan="1"><p>सर्व्हिस </p><p> Royalty Surcharge </p></td>
                                <td colspan="1">0</td>
                                <td colspan="1">Lab</td>
                                <td colspan="1">9760</td>
                                <td colspan="1">DMF</td>
                                <td colspan="1">3421</td>
                            </tr>
                            <tr>
                                <td colspan="1">GST 18%</td>
                                <td colspan="1">244553</td>
                                <td colspan="1">विमा </td>
                                <td colspan="1">6842</td>
                                <td colspan="1">एकूण र.रु. </td>
                                <td colspan="1">1623202</td>
                            </tr>
                             <tr>
                                <td colspan="1" rowspan="2">3) निविदा स्विकृती र.रु. </td>
                                <td colspan="1" rowspan="2">' . ( $rsworkmaster->WO_Amt ?? 'N/A') . '</td>
                                <td colspan="1"><p>सर्व्हिस </p><p>/Royalty Surcharge</p></td>
                                <td colspan="1">0</td>
                                <td colspan="1">Lab</td>
                                <td colspan="1">9760</td>
                                <td colspan="1">DMF</td>
                                <td colspan="1">3421</td>
                            </tr>
                              <tr>
                                <td colspan="1">GST 18%</td>
                                <td colspan="1">244553</td>
                                <td colspan="1">विमा </td>
                                <td colspan="1">6842</td>
                                <td colspan="1">एकूण र.रु</td>
                                <td colspan="1">1324304</td>
                            </tr>
                            <tr>
                                <td colspan="1">4) कामाची मुदत व अंतिम दि</td>
                                <td colspan="1">'. ( $rsworkmaster->Period ?? 'N/A') . '</td>
                                <td colspan="1">' . ( $rsworkmaster->Stip_Comp_Dt ?? 'N/A' ) . '</td>
                                <td colspan="4">5) दोष दायित्व कालावधी (DLP) </td>
                              <td colspan="1">' . ( $rsworkmaster->DLP_Dt ?? 'N/A' ) . 'महिने' . '</td>

                            </tr>

</table>
<br>
<div>

<p>
    1) मा. आयुक्त पुणे, यांचे कडील दि. 5/9/2018 रोजीचे परिपत्रकानुसार कामाची मुदत संपण्यापूर्वी 15 दिवस आधी मुदतवाढीसाठी रितसर अर्ज सादर करणे आवश्यक आहे. विहित कालावधीत मुदतवाढ अर्ज सादर न केल्यास ज्या दिवशी मुदतवाढ अर्ज देणे अपेक्षित होते त्या तारखेपासून ते प्रत्यक्ष अर्ज प्राप्त झाल्याच्या दिनांकापर्यंत प्रतिदिन दंड रु. 50/- आकारण्यात येईल. मुदतवाढी संदर्भात अंतिम निर्णय मा. अति. मु. का. अ. यांचा राहील.
</p>
<p>
    2) सदरचे काम हे पोट कंत्राटदारामार्फत (sub contractor) करता येणार नाही. आणि सदर काम आपण स्वतः केले असलेबाबतचे आवश्यक कागदपत्रे मागणी केल्यास सादर करणे आपाणावर बंधनकारक राहील.
</p>
<p>
     3) करारनाम्यात नमूद केल्याप्रमाणे कंत्राटदाराने शासकीय विमा निधी महाराष्ट्र राज्य यांच्याकडे कंत्राटी कामाचा व त्यांच्या कडे काम करयास नियुक्त केलेल्या कामगारांचा विमा उतरविणे अनिवार्य राहील. त्याबाबतचे प्रमाणपत्र कामाच्या पहिल्या देयकासोबत देने आवश्यक आहे अन्यथा शासनाच्या प्रचलित नियमानुसार पहिल्या देयकातून विम्याची रक्कम वसूल केली जाईल.
</p>
<p>
    4) उपरोक्त काम आपण उप अभियंता (इवद) पं.स. खानापूर विटा यांचे मर्यादर्शनाखाली त्वरीत सुरु करावे.
</p>
<p>
    5) कामाच्या ठिकाणी कामाचा सविस्तर माहिती फलक लावले नंतरच प्रत्यक्ष लाईनआऊट देऊन काम चालू केले जाईल. याची नोंद घ्यावी.
</p>
<p>
     6) ठेकेदाराने कामगारांचे / मजुरांचे देयक आधार क्रमांक जोडलेल्या बँक खात्यात जमा केल्याचे प्रमाणपत्र जमा करावयाचे आहे. हे प्रमाणपत्र करार केल्यापासून 60 दिवसात जमा करावयाचे आहे. जर करारनाम्याची मुदत 60 दिवसापेक्षा कमी असल्यास 15 दिवसात जमा करावयाचे आहे.
</p>
<p>
     7) काम सुरु करणेपूर्वी सदर कामाचा विमा उतरविणे बंधनकारक आहे.
</p>
<p>
    8) मक्तेदाराने 15 दिवसामध्ये काम सुरु करणे बंधनकारक राहील. पुढील 15 दिवसामध्ये कामाचा प्रगती अहवाल व छायाचित्र सादर करावे, उक्त कालावधीत जर काम सुरु झाले नाही तर आपण विलंबाबाबत योग्य खुलासा सादर करणे बंधनकारक राहील. खुलासा योग्य अथवा समाधानकारक नसल्यास आपला कार्यारंभ आदेश विना नोटीस रद्द केला जाईल याची नोंद घ्यावी.
</p>
<p>
    9) शासनाने वेळोवेळी सुधारीत केलेले कर हे त्याप्रमाणे सदरच्या देयकातून आकारणी करण्यात येईल.
</p>

</div>


</div>

<br>
<br>
<br>

<div class="signature-section" style="text-align: right; padding-right: 900px;">
    कार्यकारी अभियंता (इ.व.द.) <br>
    जिल्हा परिषद,सांगली.
</div>

<div class="copy-section" style="text-align: left; padding-left: 20px;">
    प्रतिलिपी: <br>
    1) मुख्य लेखा व वित्त अधिकारी,जिल्हा परिषद,सांगली.<br>
    2)उप कार्यकारी अभियंता (इवद) जि.प.सांगली यांना माहितीसाठी व पुढील कार्यवाही साठी<br>
    3) लेखा शाखा बांधकाम विभाग,जि.प.सांगली.
</div>
<br>

                </body>
                </html>';

        //         $mpdf = new \Mpdf\Mpdf([
        //             'default_font' => 'freeserif'
        //         ]);

        // $mpdf->WriteHTML($html);
        // $mpdf->Output("Estimate_Report.pdf", "D");
        $mpdf = new \Mpdf\Mpdf();

 $logo = public_path('photos/zplogo5.jpeg');

        // Set watermark image
        $mpdf->SetWatermarkImage($logo);

        // Show watermark image
        $mpdf->showWatermarkImage = true;

        // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
        $mpdf->watermarkImageAlpha = 0.1;
        //$mpdf->WriteHTML($html);
        $mpdf->autoScriptToLang =true;
        $mpdf->autoLangToFont =true;

        $mpdf->WriteHTML($html);
        $mpdf->Output("Work Register Report.pdf", "D");
            } catch (\Throwable $th) {
                return response()->json(['error' => 'PDF निर्माण करताना त्रुटी आली.'], 500);
            }
        }



        public function ViewAAEstimateForm(Request $request)
        {
            try {
            // Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            //Get Estimate Details
            // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
            $rsestimate = DB::table('estmasters')
            ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
            ->where('Est_Id','=',$request->id)
            ->first();

             //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

            $FinanacialYear1 = DB::table('acyrms')
            ->selectRaw('Ac_Yr')
            ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
            ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
            ->first();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

            //Get Estimate
            $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

            return view('aaregister/view',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr],compact('rsworkmaster'));
        } catch (\Throwable $th) {
            //throw $th;
        }

    }




        //A.A.Register view details------------------------------------------------------------------------------------------------------------------------------------------------------
        //Edit A.A.Details Form
        public function UpdateAARegisterDetails(Request $request){
               // try {
                       // dd($request);
                    // login user session Data----------------------------
                        $divid = auth()->user()->Div_id??0;
                        $usercode = auth()->user()->usercode??'';
                        $subdivid = auth()->user()->Sub_Div_id??0;
                    // login user session Data----------------------------

                    if($request->Work_Id){
                        $this->workid =$request->Work_Id;
                        }else{
                        //Genrate Work ID ------------------------------------
                        $Date_Prep = $request->Date_Prep;
                        $FinYearID = Acyrm::select('Ac_Yr_Id as id')
                        ->where('Yr_St', '<=', $Date_Prep)
                        ->where('Yr_End', '>=', $Date_Prep)
                        ->get();
                        $json = json_decode($FinYearID, true);
                        $yearid = $json[0]['id'];

                       // dd($yearid);

                        $SQLNewPKID = DB::table('workmasters')
                        ->selectRaw(" MAX(CAST(right(`Work_Id`,6)AS UNSIGNED)) as code")
                        ->limit(1)
                        ->get();
                        $RSNewPKID = json_decode($SQLNewPKID,true);
                        $RSNewPKID[0]['code'];

                        if($RSNewPKID[0]['code'] != '' && isset($RSNewPKID[0]['code'])){
                        $PrimaryNumber=$RSNewPKID[0]['code'] + 1;
                        }else{
                        $PrimaryNumber=1;
                        }

                        $lenght = strlen($PrimaryNumber);  //Calculate Lenght
                        if((int)$lenght === 1){ //Places Zero Functionality
                        $placezero = '00000'.$PrimaryNumber;
                        }else if((int)$lenght === 2){
                        $placezero = '0000'.$PrimaryNumber;
                        }else if((int)$lenght === 3){
                        $placezero = '000'.$PrimaryNumber;
                        }else if((int)$lenght === 4){
                        $placezero = '00'.$PrimaryNumber;
                        }else if((int)$lenght === 5){
                        $placezero = '0'.$PrimaryNumber;
                        }else{
                        $placezero = $PrimaryNumber;
                        }

                        //FirstCharacter Div or SubDiv
                        if($divid){
                        $first4char = $divid.'0';
                        }
                        if($subdivid){
                        $first4char = $subdivid;
                        }
                        //----------------------------------------------------
                        $this->workid = $first4char.$yearid.$placezero;;
                    }

                    try {
                        //Estimate file Uploading .pdf
                        $fileAA_PDF_Path = $request->file('AA_PDF_Path');
                        if($fileAA_PDF_Path){
                            //Unlink Old File
                            if($request->oldAA_PDF_Path){
                                $image_path =   public_path('uploads/aapdf/' . $request->oldAA_PDF_Path);
                                if(file_exists($image_path)){
                                 unlink($image_path);
                                }
                            }
                        $filenamepdf = time().$fileAA_PDF_Path->getClientOriginalName();
                            $extensionpdf = $fileAA_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
                            $tempPathpdf = $fileAA_PDF_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationpdf = 'uploads/aapdf'; //Created an "uploads" folder for that
                            // Upload file
                            $fileAA_PDF_Path->move($locationpdf, $filenamepdf);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNameaapdf1 = $filenamepdf;
                            $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNameaapdf1);
                        }
                        //Estimate file Uploading .pdf
                    } catch (\Throwable $th) {
                        //throw $th;
                    }

                    //Work ID Update OrNot Check
                    //First Update Estimate Master Table Withought Work ID
                    $SQLEstimateMaster = DB::table('estmasters')
                    ->where('Est_Id', $request->Est_Id)
                    ->update(['AA_No' => $request->AA_No?$request->AA_No:'','AA_Dt' => $request->AA_Dt?$request->AA_Dt:'','Work_Id' => $this->workid?$this->workid:'','po_id' => $request->poid?$request->poid:'']);

                //Estimation data get
                $Estimatedata = DB::table('estmasters')->where('Est_Id', $request->Est_Id)->first();
//dd($Estimatedata);
               //work Master Entry Present Or Not

               $rsPresentEstimate = Workmaster::where('Est_Id', '=', $request->Est_Id)->count();
               if(isset($rsPresentEstimate) && !empty($rsPresentEstimate) && $rsPresentEstimate > 0){
                        // Update

                        $SQLUpdateWorkMaster = DB::table('workmasters')->where('Work_Id',"=",$this->workid)->update(['Budget_Yr' => $request->Budget_Yr?$request->Budget_Yr:'', 'Budget_I_No' => $request->Budget_I_No?$request->Budget_I_No:'', 'Budget_P_No' =>$request->Budget_P_No?$request->Budget_P_No:'', 'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
                        'Est_Id' => $request->Est_Id?$request->Est_Id:'', 'Sub_Div_Id' => $subdivid?$subdivid:'', 'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'', 'Work_Nm_M' => $request->Work_Nm_M?$request->Work_Nm_M:'', 'Work_Id' => $this->workid?$this->workid:'', 'AA_No' => $request->AA_No?$request->AA_No:'',
                        'AA_Dt' => $request->AA_Dt?$request->AA_Dt:'', 'AA_Amt' => $request->AA_Amt?$request->AA_Amt:0, 'AA_Authority' => $request->AA_Authority?$request->AA_Authority:'', 'AA_Remark' => $request->AA_Remark?$request->AA_Remark:'','AA_PDF_Path'=>$this->fileNameaapdf1?$this->fileNameaapdf1:'','PB_Id'=>$request->poid?$request->poid:'',
                        'Sub_Div' => $Estimatedata->Sub_Div?$Estimatedata->Sub_Div:'','Tal_Id' => $Estimatedata->Tal_Id?$Estimatedata->Tal_Id:'','Tal' => $Estimatedata->Tal?$Estimatedata->Tal:'','jeid' => $Estimatedata->je_id?$Estimatedata->je_id:'',]);
               }else{
                //dd($request->AA_Amt);
                        // New
                        $SQLInsertWorkMaster = DB::table('workmasters')->insert(['Budget_Yr' => $request->Budget_Yr?$request->Budget_Yr:'', 'Budget_I_No' => $request->Budget_I_No?$request->Budget_I_No:'', 'Budget_P_No' => $request->Budget_P_No?$request->Budget_P_No:'', 'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
                        'Est_Id' => $request->Est_Id?$request->Est_Id:'', 'Sub_Div_Id' => $subdivid?$subdivid:'', 'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'', 'Work_Nm_M' => $request->Work_Nm_M?$request->Work_Nm_M:'', 'Work_Id' => $this->workid?$this->workid:'', 'AA_No' => $request->AA_No?$request->AA_No:'',
                         'AA_Dt' => $request->AA_Dt?$request->AA_Dt:'', 'AA_Amt' => $request->AA_Amt?$request->AA_Amt:0, 'AA_Authority' => $request->AA_Authority?$request->AA_Authority:'', 'AA_Remark' => $request->AA_Remark?$request->AA_Remark:'','AA_PDF_Path'=>$this->fileNameaapdf1?$this->fileNameaapdf1:'','PB_Id'=>$request->poid?$request->poid:'','Ac_Yr_Id'=>$yearid?$yearid:'',
                        'Sub_Div' => $Estimatedata->Sub_Div?$Estimatedata->Sub_Div:'','Tal_Id' => $Estimatedata->Tal_Id?$Estimatedata->Tal_Id:'','Tal' => $Estimatedata->Tal?$Estimatedata->Tal:'','jeid' => $Estimatedata->je_id?$Estimatedata->je_id:'',]);
                        //dd($SQLInsertWorkMaster);
                }
                    //exit;
                    return  redirect()->to($request->last_url)->with('success','Record Updated Successfully');
                    // return redirect('AARegisterList')->with('success','Record added successfully.');

                    //} catch (\Throwable $th) {}
        }

        public function UpdateAARegisterDetails1(Request $request){
            // try {
                    // dd($request);
                 // login user session Data----------------------------
                     $divid = auth()->user()->Div_id??0;
                     $usercode = auth()->user()->usercode??'';
                     $subdivid = auth()->user()->Sub_Div_id??0;
                 // login user session Data----------------------------

                 if($request->Work_Id){
                     $this->workid =$request->Work_Id;
                     }else{
                     //Genrate Work ID ------------------------------------
                     $Date_Prep = $request->Date_Prep;
                     $FinYearID = Acyrm::select('Ac_Yr_Id as id')
                     ->where('Yr_St', '<=', $Date_Prep)
                     ->where('Yr_End', '>=', $Date_Prep)
                     ->get();
                     $json = json_decode($FinYearID, true);
                     $yearid = $json[0]['id'];

                    // dd($yearid);

                     $SQLNewPKID = DB::table('workmasters')
                     ->selectRaw(" MAX(CAST(right(`Work_Id`,6)AS UNSIGNED)) as code")
                     ->limit(1)
                     ->get();
                     $RSNewPKID = json_decode($SQLNewPKID,true);
                     $RSNewPKID[0]['code'];

                     if($RSNewPKID[0]['code'] != '' && isset($RSNewPKID[0]['code'])){
                     $PrimaryNumber=$RSNewPKID[0]['code'] + 1;
                     }else{
                     $PrimaryNumber=1;
                     }

                     $lenght = strlen($PrimaryNumber);  //Calculate Lenght
                     if((int)$lenght === 1){ //Places Zero Functionality
                     $placezero = '00000'.$PrimaryNumber;
                     }else if((int)$lenght === 2){
                     $placezero = '0000'.$PrimaryNumber;
                     }else if((int)$lenght === 3){
                     $placezero = '000'.$PrimaryNumber;
                     }else if((int)$lenght === 4){
                     $placezero = '00'.$PrimaryNumber;
                     }else if((int)$lenght === 5){
                     $placezero = '0'.$PrimaryNumber;
                     }else{
                     $placezero = $PrimaryNumber;
                     }

                     //FirstCharacter Div or SubDiv
                     if($divid){
                     $first4char = $divid.'0';
                     }
                     if($subdivid){
                     $first4char = $subdivid;
                     }
                     //----------------------------------------------------
                     $this->workid = $first4char.$yearid.$placezero;;
                 }

                 try {
                     //Estimate file Uploading .pdf
                     $fileAA_PDF_Path = $request->file('AA_PDF_Path');
                     if($fileAA_PDF_Path){
                         //Unlink Old File
                         if($request->oldAA_PDF_Path){
                             $image_path =   public_path('uploads/aapdf/' . $request->oldAA_PDF_Path);
                             if(file_exists($image_path)){
                              unlink($image_path);
                             }
                         }
                     $filenamepdf = time().$fileAA_PDF_Path->getClientOriginalName();
                         $extensionpdf = $fileAA_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
                         $tempPathpdf = $fileAA_PDF_Path->getRealPath();
                         //Where uploaded file will be stored on the server
                         $locationpdf = 'uploads/aapdf'; //Created an "uploads" folder for that
                         // Upload file
                         $fileAA_PDF_Path->move($locationpdf, $filenamepdf);
                         // In case the uploaded file path is to be stored in the database
                         $this->fileNameaapdf1 = $filenamepdf;
                         $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNameaapdf1);
                     }
                     //Estimate file Uploading .pdf
                 } catch (\Throwable $th) {
                     //throw $th;
                 }

                 //Work ID Update OrNot Check
                 //First Update Estimate Master Table Withought Work ID
                 $SQLEstimateMaster = DB::table('estmasters')
                 ->where('Est_Id', $request->Est_Id)
                 ->update(['AA_No' => $request->AA_No?$request->AA_No:'','AA_Dt' => $request->AA_Dt?$request->AA_Dt:'','Work_Id' => $this->workid?$this->workid:'','po_id' => $request->poid?$request->poid:'']);

             //Estimation data get
             $Estimatedata = DB::table('estmasters')->where('Est_Id', $request->Est_Id)->first();
//dd($Estimatedata);
            //work Master Entry Present Or Not

            $rsPresentEstimate = Workmaster::where('Est_Id', '=', $request->Est_Id)->count();
            if(isset($rsPresentEstimate) && !empty($rsPresentEstimate) && $rsPresentEstimate > 0){
                     // Update

                     $SQLUpdateWorkMaster = DB::table('workmasters')->where('Work_Id',"=",$this->workid)->update(['Budget_Yr' => $request->Budget_Yr?$request->Budget_Yr:'', 'Budget_I_No' => $request->Budget_I_No?$request->Budget_I_No:'', 'Budget_P_No' =>$request->Budget_P_No?$request->Budget_P_No:'', 'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
                     'Est_Id' => $request->Est_Id?$request->Est_Id:'', 'Sub_Div_Id' => $subdivid?$subdivid:'', 'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'', 'Work_Nm_M' => $request->Work_Nm_M?$request->Work_Nm_M:'', 'Work_Id' => $this->workid?$this->workid:'', 'AA_No' => $request->AA_No?$request->AA_No:'',
                     'AA_Dt' => $request->AA_Dt?$request->AA_Dt:'', 'AA_Amt' => $request->AA_Amt?$request->AA_Amt:0, 'AA_Authority' => $request->AA_Authority?$request->AA_Authority:'', 'AA_Remark' => $request->AA_Remark?$request->AA_Remark:'','AA_PDF_Path'=>$this->fileNameaapdf1?$this->fileNameaapdf1:'','PB_Id'=>$request->poid?$request->poid:'',
                     'Sub_Div' => $Estimatedata->Sub_Div?$Estimatedata->Sub_Div:'','Tal_Id' => $Estimatedata->Tal_Id?$Estimatedata->Tal_Id:'','Tal' => $Estimatedata->Tal?$Estimatedata->Tal:'','jeid' => $Estimatedata->je_id?$Estimatedata->je_id:'',]);
            }else{
             //dd($request->AA_Amt);
                     // New
                     $SQLInsertWorkMaster = DB::table('workmasters')->insert(['Budget_Yr' => $request->Budget_Yr?$request->Budget_Yr:'', 'Budget_I_No' => $request->Budget_I_No?$request->Budget_I_No:'', 'Budget_P_No' => $request->Budget_P_No?$request->Budget_P_No:'', 'F_H_Code' => $request->F_H_Code?$request->F_H_Code:'',
                     'Est_Id' => $request->Est_Id?$request->Est_Id:'', 'Sub_Div_Id' => $subdivid?$subdivid:'', 'Work_Nm' => $request->Work_Nm?$request->Work_Nm:'', 'Work_Nm_M' => $request->Work_Nm_M?$request->Work_Nm_M:'', 'Work_Id' => $this->workid?$this->workid:'', 'AA_No' => $request->AA_No?$request->AA_No:'',
                      'AA_Dt' => $request->AA_Dt?$request->AA_Dt:'', 'AA_Amt' => $request->AA_Amt?$request->AA_Amt:0, 'AA_Authority' => $request->AA_Authority?$request->AA_Authority:'', 'AA_Remark' => $request->AA_Remark?$request->AA_Remark:'','AA_PDF_Path'=>$this->fileNameaapdf1?$this->fileNameaapdf1:'','PB_Id'=>$request->poid?$request->poid:'','Ac_Yr_Id'=>$yearid?$yearid:'',
                     'Sub_Div' => $Estimatedata->Sub_Div?$Estimatedata->Sub_Div:'','Tal_Id' => $Estimatedata->Tal_Id?$Estimatedata->Tal_Id:'','Tal' => $Estimatedata->Tal?$Estimatedata->Tal:'','jeid' => $Estimatedata->je_id?$Estimatedata->je_id:'',]);
                     //dd($SQLInsertWorkMaster);
             }
                 //exit;
                 return  redirect()->to($request->last_url)->with('success','Record Updated Successfully');
                 // return redirect('AARegisterList')->with('success','Record added successfully.');

                 //} catch (\Throwable $th) {}
     }


        public function SearchAAEstimate(Request $request){
            // try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->usercode??'';
            $divid = auth()->user()->Div_id??0;
            $subdivid = auth()->user()->Sub_Div_id??0;
            //Get User Permission

            //Get Division Name
            //$divisionName = Division::select()

            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';

            //A.A.Register New Model Result----------------------------------------------------------------
            if($UseUserPermission){
                    //Get All Estimates
                    $queryAARegisterNew = DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep");

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $request->is_estimate_no;
                                $request->is_name_work;
                                $request->is_estimate_amount;

                                $queryAARegisterNew = '';
                                $queryAARegisterNew = DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)->Where([['estmasters.AA_TS', '=','AA'],['estmasters.AA_No','=','']]);
                                $projectAARegisterNew =$queryAARegisterNew->get();
                                break;
                            }else{

                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                       $queryAARegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);

                                    }else{

                                       $queryAARegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                          $queryAARegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $queryAARegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count
                                        $queryAARegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }

                          //Filter------------------------------------------------------------------------------
                          $queryAARegisterNew->Where([['estmasters.AA_TS', '=','AA'],['estmasters.AA_No','=','']]);
                          if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $queryAARegisterNew->where("estmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $queryAARegisterNew->where("estmasters.Est_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $queryAARegisterNew->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                           $startDate = $request->txtsearchFromPreDate;
                           $endDate = $request->txtsearchToPreDate;
                            $queryAARegisterNew->whereDate('estmasters.Date_Prep','<=', $startDate)
                           ->whereDate('estmasters.Date_Prep','>=', $endDate);
                         }
                        //Filter ---------------------------------------------------------------------


                            $projectAARegisterNew =$queryAARegisterNew->get();
                            $initCount++;
                        }
                }else{
                        $projectAARegisterNew =DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where('estmasters.Est_Id','=',0)
                            ->Where([['estmasters.AA_TS', '=','AA'],['estmasters.AA_No','=','']])
                            ->get();
                }
                // A.A.Register New Model -------------------------------------------------
                return response()->json(array('msg'=> $projectAARegisterNew), 200);


            // } catch (\Throwable $th) {
            // }
        }


        public function EditViewTSEstimateForm(Request $request)
            {

                try {
                //Genrate unique ID Genration
                $uniquenumber = uniqid();

                // Logged User Session
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------

                $FinanacialYear = DB::table('acyrms')
                ->selectRaw('`Ac_Yr`')
                ->get();


                // Get Division ID To Division Name
                $rsDivisionDtls = DB::table('divisions')
                ->selectRaw('`div_m`,`div`,`div_id`')
                ->where('div_id','=',$divid)->get();
                $rsDiv = json_decode($rsDivisionDtls,true);


                //Get Selected Divisions All Subdivisions
                $rsSubDivisionDtls = DB::table('subdivms')
                ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
                ->where('Div_Id','=',$divid)->get();
                $rsWorkType = DB::table('worktypes')
                ->selectRaw('`id`,`worktype`')
                ->get();
                $rsTalukas = DB::table('talms')
                ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
                ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
                ->where('divisions.div_id','=',$divid)
                ->get();


                //Scope Master data
                $scopeMasterList = Scopm::get();

                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$uniquenumber)
                ->limit(1)
                ->get();
               // dd($uniquenumber);
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $RecapSrno=$RSNewPKID[0]->Sr_No;
                }else{
                    $RecapSrno=1;
                }

                //Get Estimate Details
               // $rsestimate = Estmaster::where('Est_Id','=',$request->eid)->first();
               // Modified By: Santosh Date:2-01-2025
                //Second Estimate ID = $request->wid
                  $rsestimate = DB::table('estmasters')
                   ->join('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->where('estmasters.Est_Id', '=', $request->wid)
                    ->select('estmasters.*', 'workmasters.*', 'estmasters.Tot_Amt')
                     ->first();

                     //Second Estimate ID = $request->eid
               $editrsestimatets = DB::table('estmasters')
              ->where('Est_Id', $request->eid ?? 0)
               ->select('Tot_Amt')
               ->first();



             //Get Estimate Scope
                $rsscope = Estscope::where('Est_Id','=',$request->eid)->get();

                //Get Estimate Recape
                $rsrecape =Estrecap::where('Est_Id','=',$request->eid)->get();

//Get EstID
                //Get Estimate
                $rsworkmaster=Workmaster::where('Work_Id','=',$rsestimate->Work_Id??$request->wid)->first();


               // dd( $editrsestimate);

//dd($rsworkmaster);

                return view('tsregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'editrsestimate'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'eid'=>$request->eid,'wid'=>$request->wid, 'Tot_Amt' => $rsestimate->Tot_Amt ?? 0],compact('rsworkmaster','editrsestimatets'));
            } catch (\Throwable $th) {
                throw $th;
            }

        }


        public function EditViewTSEstimateForm1(Request $request)
        {

            try {
            //Genrate unique ID Genration
            $uniquenumber = uniqid();

            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();


            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);


            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
           // dd($uniquenumber);
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
                $RecapSrno=1;
            }

            //Get Estimate Details
           // $rsestimate = Estmaster::where('Est_Id','=',$request->eid)->first();
           // Modified By: Santosh Date:2-01-2025
            //Second Estimate ID = $request->wid
              $rsestimate = DB::table('estmasters')
               ->join('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                ->where('estmasters.Est_Id', '=', $request->wid)
                ->select('estmasters.*', 'workmasters.*', 'estmasters.Tot_Amt')
                 ->first();

                 //Second Estimate ID = $request->eid
           $editrsestimatets = DB::table('estmasters')
          ->where('Est_Id', $request->eid ?? 0)
           ->select('Tot_Amt')
           ->first();



         //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->eid)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->eid)->get();

//Get EstID
            //Get Estimate
            $rsworkmaster=Workmaster::where('Work_Id','=',$rsestimate->Work_Id??$request->wid)->first();

           // dd( $editrsestimate);

//dd($rsworkmaster);

            return view('tsregister/add1',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'editrsestimate'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'eid'=>$request->eid,'wid'=>$request->wid, 'Tot_Amt' => $rsestimate->Tot_Amt ?? 0],compact('rsworkmaster',  'editrsestimatets'));
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function numberToMarathiWords($number) {
        $words = [
            0 => 'शून्य', 1 => 'एक', 2 => 'दोन', 3 => 'तीन', 4 => 'चार', 5 => 'पाच',
            6 => 'सहा', 7 => 'सात', 8 => 'आठ', 9 => 'नऊ', 10 => 'दहा', 11 => 'अकरा', 12 => 'बारह',
            13 => 'तेरा', 14 => 'चौदा', 15 => 'पंधरा', 16 => 'सोळा', 17 => 'सतरा', 18 => 'अठरा', 19 => 'एकोणवीस',
            20 => 'वीस', 30 => 'तीस', 40 => 'चाळीस', 50 => 'पन्नास', 60 => 'साठ', 70 => 'सत्तर', 80 => 'ऐंशी', 90 => 'नव्वद',
            100 => 'शंभर', 1000 => 'हजार', 100000 => 'लाख'
        ];
        if (isset($words[$number])) {
            return $words[$number];
        } else {
            return $number;
        }
    }



        //Only view T.S.Orders Details
        public function ViewTSEstimateForm(Request $request)
            {
                try {
                //Genrate unique ID Genration
                $uniquenumber = uniqid();

                // Logged User Session
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------

                $FinanacialYear = DB::table('acyrms')
                ->selectRaw('`Ac_Yr`')
                ->get();

                // Get Division ID To Division Name
                $rsDivisionDtls = DB::table('divisions')
                ->selectRaw('`div_m`,`div`,`div_id`')
                ->where('div_id','=',$divid)->get();
                $rsDiv = json_decode($rsDivisionDtls,true);

                //Get Selected Divisions All Subdivisions
                $rsSubDivisionDtls = DB::table('subdivms')
                ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
                ->where('Div_Id','=',$divid)->get();
                $rsWorkType = DB::table('worktypes')
                ->selectRaw('`id`,`worktype`')
                ->get();
                $rsTalukas = DB::table('talms')
                ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
                ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
                ->where('divisions.div_id','=',$divid)
                ->get();


                //Scope Master data
                $scopeMasterList = Scopm::get();

                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$uniquenumber)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $RecapSrno=$RSNewPKID[0]->Sr_No;
                }else{
                    $RecapSrno=1;
                }

                //Get Estimate Details
                $rsestimate = Estmaster::where('Est_Id','=',$request->eid)->first();

                //Get Estimate Scope
                $rsscope = Estscope::where('Est_Id','=',$request->eid)->get();

                //Get Estimate Recape
                $rsrecape =Estrecap::where('Est_Id','=',$request->eid)->get();

                //Get Estimate
                $rsworkmaster=Workmaster::where('Work_Id','=',$request->wid)->first();

                return view('tsregister/view',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'editrsestimate'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'eid'=>$request->eid,'wid'=>$request->wid],compact('rsworkmaster'));
            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        //Second Step Find Estimates
        public function getTSFindEstimates(Request $request){
            try {

                //Login Session Details
                $uid = auth()->user()->id??0;
                $usercode = auth()->user()->usercode??'';
                $divid = auth()->user()->Div_id??0;
                $subdivid = auth()->user()->Sub_Div_id??0;
                //Get User Permission

                //Get Division Name
                $DSFoundhd = DB::table('userperms')
                ->select('F_H_CODE','Sub_Div_Id','Work_Id')
                ->where('User_Id', '=',$uid)
                ->where('Removed','=',1)
                ->get();

                $UseUserPermission = json_decode($DSFoundhd);
                $FinalExecuteQuery = '';
                $rsFilterResult = '';

                if($UseUserPermission){
                        //Get All Estimates
                        $query = DB::table('workmasters')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                        //->where([['workmasters.AA_No','=',""],['workmasters.TS_No', '=',""]]);
                        //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']]);

                                $initCount = 0;
                                foreach(json_decode($DSFoundhd) as $rsFound){
                                    $rsFound->F_H_CODE;
                                    $rsFound->Sub_Div_Id;
                                    $rsFound->Work_Id;
                                    $foundcount = strlen($rsFound->F_H_CODE);

                                //echo "Step0"; exit;
                                if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                    $query = '';
                                    $query = DB::table('workmasters')
                                    ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                                    //->where([['workmasters.AA_No','=',""],['workmasters.TS_No', '=',""]])
                                    //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']])
                                    ->orderBy('workmasters.created_at', 'desc')
                                    ->paginate(10);
                                    $project = $query;
                                    break;
                                }else{
                                   // echo "Step2"; exit;
                                    // If work id
                                    if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                        //Calculate Count
                                        if($initCount == 0){
                                           $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                           $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                        }else{
                                            $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                        }
                                    }else{

                                        if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                            //Calculate Count
                                            if($initCount == 0){
                                              $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                              $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }else{
                                              $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }
                                        }
                                        if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                            //Calculate Count
                                            $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                        }

                                    }

                                }
                                $query->orderBy('workmasters.created_at', 'desc');
                                $project =$query->paginate(10);
                                $initCount++;
                            }
                    }else{

                                $project = DB::table('workmasters')
                                ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                            ->orderBy('workmasters.created_at', 'desc')
                            //->where([['workmasters.AA_No','=',""],['workmasters.TS_No', '=',""]])
                            //->orWhere([['workmasters.AA_TS', '=','AA'],['workmasters.TS_No','=','']])
                            ->paginate(10);

                    }

                   // A.A.Register New Model Result----------------------------------------------------------------
                    if($UseUserPermission){
                        //Get All Estimates
                        $queryTSRegisterLast = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep");
                            $initCount = 0;


                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                    $rsFound->F_H_CODE;
                                    $rsFound->Sub_Div_Id;
                                    $rsFound->Work_Id;
                                    $foundcount = strlen($rsFound->F_H_CODE);

                                //echo "Step0"; exit;
                                if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                    $queryTSRegisterLast = '';

                                    $queryTSRegisterLast = DB::table('estmasters')
                                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                                    ->Where('estmasters.AA_TS','=','TS')
                                    ->Where('estmasters.TS_No','=','')
                                    ->Where('estmasters.AA_TS','<>','')
                                    ->orderBy('estmasters.created_at', 'desc')
                                    ->paginate(10);
                                    $projectTSRegisterLast = $queryTSRegisterLast;
                                    break;
                                }else{

                                    // If work id
                                    if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                       //Calculate Count
                                        if($initCount == 0){


                                            $queryTSRegisterLast->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                                            ->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);

                                        }else{

                                           $queryTSRegisterLast->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                        }
                                    }else{
                                        if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                            //Calculate Count
                                            if($initCount == 0){
                                                $queryTSRegisterLast->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                                $queryTSRegisterLast->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }else{
                                                $queryTSRegisterLast->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }
                                     }
                                        if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                            //Calculate Count
                                            $queryTSRegisterLast->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                        }

                                    }
                                }
                                $queryTSRegisterLast->where('estmasters.AA_TS','=','TS')->Where('estmasters.TS_No','=','')
                                ->Where('estmasters.AA_TS','<>','');
                                $queryTSRegisterLast->orderBy('estmasters.created_at', 'desc');
                                $projectTSRegisterLast =$queryTSRegisterLast->paginate(10);
                               $initCount++;
                            }


                    }else{
                                $projectTSRegisterLast =DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where('estmasters.Est_Id','=',0)
                                ->Where('estmasters.AA_TS','=','TS')
                                ->Where('estmasters.TS_No','=','')
                                ->Where('estmasters.AA_TS','<>','')
                                ->orderBy('estmasters.created_at', 'desc')
                                ->paginate(10);

                    }
                //A.A.Register New Model Result----------------------------------------------------------------
                return view('tsregister/estimatelist',['data'=>$project,'TSRegisterSteplAST'=>$projectTSRegisterLast,'pworkid'=>$request->wid]);
                } catch (\Throwable $th) {
                    throw $th;
                }
        }

        public function UpdateTSRegisterDetails(Request $request)
        {
            try {
                // User session data
                $divid = auth()->user()->Div_id ?? 0;
                $usercode = auth()->user()->usercode ?? '';
                $subdivid = auth()->user()->Sub_Div_id ?? 0;

                // Fetch estimate and work details
                $rsestimate = DB::table('estmasters')
                    ->join('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->where('estmasters.Est_Id', '=', $request->wid)
                    ->select('estmasters.*', 'workmasters.*')
                    ->first();



                // Fetch other related records
                $rstsReferEstimate = DB::table('estmasters')
                    ->leftJoin('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->where('estmasters.Est_Id', '=', $request->eid)
                    ->select('estmasters.*', 'workmasters.*')
                    ->first();

                $editrsestimate = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? 0)
                    ->select('Tot_Amt')
                    ->first();

                $workType = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? '')
                    ->value('Work_Type');

                // File Upload logic
                try {
                    $fileTS_PDF_Path = $request->file('TS_PDF_Path');
                    if ($fileTS_PDF_Path) {
                        if ($request->oldTS_PDF_Path) {
                            $image_path = public_path('uploads/tspdf/' . $request->oldTS_PDF_Path);
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                        $filenamepdf = time() . $fileTS_PDF_Path->getClientOriginalName();
                        $locationpdf = 'uploads/tspdf';
                        $fileTS_PDF_Path->move($locationpdf, $filenamepdf);
                        $this->fileNametspdf1 = $filenamepdf;
                        $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNametspdf1);
                    }
                } catch (\Throwable $th) {
                    // Handle error for file upload
                }

                // Update workmasters table
                $SQLConditionFirst = DB::table('workmasters')->where('Work_Id', $rsestimate->Work_Id)->update([
                    'TS_Est_Id' => $request->eid ?? '',
                    'TS_No' => $request->TS_No ?? '',
                    'TS_Dt' => $request->TS_Dt ?? '',
                    'TS_Authority' => $request->TS_Authority ?? '',
                    'TS_Amt' => $request->TS_Amt ?? '',
                    'TS_Remark' => $request->TS_Remark ?? '',
                    'TS_PDF_Path' => $this->fileNametspdf1 ?? '',
                    'Work_Type' => $workType ?? ''
                ]);

                // Update estmasters table
                $SQLConditionSecond = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? '')
                    ->update([
                        'Work_Id' => $rsestimate->Work_Id ?? '',
                        'TS_No' => $request->TS_No ?? '',
                        'TS_Dt' => $request->TS_Dt ?? '',
                        'AA_No' => $request->AA_No ?? '',
                        'AA_Dt' => $request->AA_Dt ?? ''
                    ]);

                // Check if Workmaster exists before accessing Est_Id
                $rsWorkDetails = Workmaster::where('Work_Id', $rsestimate->Work_Id)->first();

                if ($rsWorkDetails) {
                    // Update estmasters table using Est_Id
                    DB::table('estmasters')
                        ->where('Est_Id', $rsWorkDetails->Est_Id ?? '')
                        ->update([
                            'TS_No' => $request->TS_No ?? '',
                            'TS_Dt' => $request->TS_Dt ?? ''
                        ]);
                } else {
                    return redirect()->to(url($request->last_url))->with('error', 'Workmaster not found.');
                }

                return redirect()->route('TSRegisterList', ['flg' => 1])
                ->with('success', 'Record Updated Successfully')
                ->with(compact('editrsestimate', 'rstsReferEstimate'));

            } catch (\Throwable $th) {
                // Handle any other errors
                throw $th;
            }
        }


        public function UpdateTSRegisterDetails1(Request $request)
        {
            try {
                // User session data
                $divid = auth()->user()->Div_id ?? 0;
                $usercode = auth()->user()->usercode ?? '';
                $subdivid = auth()->user()->Sub_Div_id ?? 0;

                // Fetch estimate and work details
                $rsestimate = DB::table('estmasters')
                    ->join('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->where('estmasters.Est_Id', '=', $request->wid)
                    ->select('estmasters.*', 'workmasters.*')
                    ->first();



                // Fetch other related records
                $rstsReferEstimate = DB::table('estmasters')
                    ->leftJoin('workmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->where('estmasters.Est_Id', '=', $request->eid)
                    ->select('estmasters.*', 'workmasters.*')
                    ->first();

                $editrsestimate = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? 0)
                    ->select('Tot_Amt')
                    ->first();

                $workType = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? '')
                    ->value('Work_Type');

                // File Upload logic
                try {
                    $fileTS_PDF_Path = $request->file('TS_PDF_Path');
                    if ($fileTS_PDF_Path) {
                        if ($request->oldTS_PDF_Path) {
                            $image_path = public_path('uploads/tspdf/' . $request->oldTS_PDF_Path);
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                        $filenamepdf = time() . $fileTS_PDF_Path->getClientOriginalName();
                        $locationpdf = 'uploads/tspdf';
                        $fileTS_PDF_Path->move($locationpdf, $filenamepdf);
                        $this->fileNametspdf1 = $filenamepdf;
                        $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNametspdf1);
                    }
                } catch (\Throwable $th) {
                    // Handle error for file upload
                }

                // Update workmasters table
                $SQLConditionFirst = DB::table('workmasters')->where('Work_Id', $rsestimate->Work_Id)->update([
                    'TS_Est_Id' => $request->eid ?? '',
                    'TS_No' => $request->TS_No ?? '',
                    'TS_Dt' => $request->TS_Dt ?? '',
                    'TS_Authority' => $request->TS_Authority ?? '',
                    'TS_Amt' => $request->TS_Amt ?? '',
                    'TS_Remark' => $request->TS_Remark ?? '',
                    'TS_PDF_Path' => $this->fileNametspdf1 ?? '',
                    'Work_Type' => $workType ?? ''
                ]);

                // Update estmasters table
                $SQLConditionSecond = DB::table('estmasters')
                    ->where('Est_Id', $request->eid ?? '')
                    ->update([
                        'Work_Id' => $rsestimate->Work_Id ?? '',
                        'TS_No' => $request->TS_No ?? '',
                        'TS_Dt' => $request->TS_Dt ?? '',
                        'AA_No' => $request->AA_No ?? '',
                        'AA_Dt' => $request->AA_Dt ?? ''
                    ]);

                // Check if Workmaster exists before accessing Est_Id
                $rsWorkDetails = Workmaster::where('Work_Id', $rsestimate->Work_Id)->first();

                if ($rsWorkDetails) {
                    // Update estmasters table using Est_Id
                    DB::table('estmasters')
                        ->where('Est_Id', $rsWorkDetails->Est_Id ?? '')
                        ->update([
                            'TS_No' => $request->TS_No ?? '',
                            'TS_Dt' => $request->TS_Dt ?? ''
                        ]);
                } else {
                    return redirect()->to(url($request->last_url))->with('error', 'Workmaster not found.');
                }

                return redirect()->route('TSRegisterList', ['flg' => 1])
                ->with('success', 'Record Updated Successfully')
                ->with(compact('editrsestimate', 'rstsReferEstimate'));

            } catch (\Throwable $th) {
                // Handle any other errors
                throw $th;
            }
        }



        public function SearchTSEstimateSecondList(Request $request)
        {
            try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->usercode??'';
            $divid = auth()->user()->Div_id??0;
            $subdivid = auth()->user()->Sub_Div_id??0;
            //Get User Permission

            //Get Division Name
           // $divisionName = Division::select()

            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';

            // T.S.Register Second Steps Model  Popup Result ---------------------------------------------------
                if($UseUserPermission){
                //Get All Estimates
                $queryTSRegisterStepFirst = DB::table('workmasters')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                ->select("workmasters.Work_Id","workmasters.Est_Id","workmasters.Work_Nm","workmasters.Work_Nm","workmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","workmasters.AA_Amt","workmasters.TS_Amt","workmasters.F_H_Code","workmasters.F_H_Code","workmasters.Work_Type","workmasters.TS_No","workmasters.TS_Dt","estmasters.Tot_Amt","estmasters.Date_Prep","workmasters.AA_No");

                        $initCount = 0;
                        foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                            $rsFound->F_H_CODE;
                            $rsFound->Sub_Div_Id;
                            $rsFound->Work_Id;
                            $foundcount = strlen($rsFound->F_H_CODE);


                        if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                            //echo "Step1"; exit;
                            $queryTSRegisterStepFirst = '';
                        $queryTSRegisterStepFirst = DB::table('workmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->select(
                            "workmasters.Work_Id",
                            "workmasters.Est_Id",
                            "workmasters.Work_Nm",
                            "workmasters.Work_Nm",  // You have repeated this field twice, you might want to adjust it.
                            "workmasters.Work_Nm_M",
                            "subdivms.Sub_Div",
                            "subdivms.Sub_Div_M",
                            "workmasters.AA_Amt",
                            "workmasters.TS_Amt",
                            "workmasters.F_H_Code",  // Also repeated, consider adjusting.
                            "workmasters.Work_Type",
                            "workmasters.TS_No",
                            "workmasters.TS_Dt",
                            "estmasters.Tot_Amt",
                            "estmasters.Date_Prep",
                            "workmasters.AA_No"
                        )
                        ->where(DB::raw('LEFT(`workmasters`.`Est_Id`, 3)'), '=', $divid)
                        ->where('workmasters.AA_No', '<>', '')
                        ->where(function ($query) {
                            $query->where('workmasters.TS_No', '=', '')
                            ->orWhereNull('workmasters.TS_No');
                        });

                            //Filter------------------------------------------------------------------------------
                                if($request->is_aa_no && !empty($request->is_aa_no) && isset($request->txtsearchaano)){
                                    $queryTSRegisterStepFirst->where("workmasters.AA_No", 'like', '%'.$request->txtsearchaano.'%');
                                }
                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                    $queryTSRegisterStepFirst->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                    $queryTSRegisterStepFirst->where("workmasters.F_H_Code", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                    $queryTSRegisterStepFirst->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                    $queryTSRegisterStepFirst->whereDate('workmasters.AA_Dt','<=', $startDate)
                                ->whereDate('workmasters.AA_Dt','>=', $endDate);
                                }
                            //Filter ---------------------------------------------------------------------

                            $projectTSRegisterNew = $queryTSRegisterStepFirst->get();
                            break;
                        }else{

                            // If work id
                            if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                //Calculate Count
                                if($initCount == 0){
                                    $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                    $queryTSRegisterStepFirst->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }else{

                                    $queryTSRegisterStepFirst->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }
                            }else{
                                //dd("Step 3");
                                if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                    //Calculate Count
                                    if($initCount == 0){

                                        $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                        $queryTSRegisterStepFirst->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }else{

                                        $queryTSRegisterStepFirst->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }

                                }
                                if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                    //Calculate Count

                                    $queryTSRegisterStepFirst->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                }

                            }

                        }

                        //Filter------------------------------------------------------------------------------
                            $queryTSRegisterStepFirst->where('workmasters.AA_No', '<>', '')
                        ->where(function ($query) {
                            $query->where('workmasters.TS_No', '=', '')
                            ->orWhereNull('workmasters.TS_No');
                        });
                            if($request->is_aa_no && !empty($request->is_aa_no) && isset($request->txtsearchaano)){
                                $queryTSRegisterStepFirst->where("workmasters.AA_No", 'like', '%'.$request->txtsearchaano.'%');
                            }
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $queryTSRegisterStepFirst->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $queryTSRegisterStepFirst->where("workmasters.F_H_Code", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $queryTSRegisterStepFirst->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                                $queryTSRegisterStepFirst->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                            }
                        //Filter ---------------------------------------------------------------------

                    $projectTSRegisterNew =$queryTSRegisterStepFirst->get();
                        $initCount++;
                    }
                }else{
                $projectTSRegisterNew =DB::table('workmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->select("workmasters.Work_Id","workmasters.Est_Id","workmasters.Work_Nm","workmasters.Work_Nm","workmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","workmasters.AA_Amt","workmasters.TS_Amt","workmasters.F_H_Code","workmasters.F_H_Code","workmasters.Work_Type","workmasters.TS_No","workmasters.TS_Dt","estmasters.Tot_Amt","estmasters.Date_Prep","workmasters.AA_No")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where([['workmasters.AA_No', '<>',''],['workmasters.TS_No','=','']])
                    ->get();
                }

                // T.S.Register New Model Result----------------------------------
                return response()->json(array('msg'=> $projectTSRegisterNew), 200);

             } catch (\Throwable $th) {
                throw $th;
            }
        }



          //Second Step Find Estimates
          public function LastStepTSFindEstimates(Request $request){
            try { //print_r($request); exit;
                //Login Session Details
                $uid = auth()->user()->id??0;
                $usercode = auth()->user()->usercode??'';
                $divid = auth()->user()->Div_id??0;
                $subdivid = auth()->user()->Sub_Div_id??0;
                //Get User Permission

                //Get Division Name
                $DSFoundhd = DB::table('userperms')
                ->select('F_H_CODE','Sub_Div_Id','Work_Id')
                ->where('User_Id', '=',$uid)
                ->where('Removed','=',1)
                ->get();

                $UseUserPermission = json_decode($DSFoundhd);
                $FinalExecuteQuery = '';
                $rsFilterResult = '';

                // T.S.Register Final Step Model Result----------------------------------------------------------------
                    if($UseUserPermission){
                        //Get All Estimates
                        $queryTSFinalRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep");
                            $initCount = 0;
                                foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                                if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                    $queryTSFinalRegisterNew = '';
                                    $queryTSFinalRegisterNew = DB::table('estmasters')
                                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                    $queryTSFinalRegisterNew->Where('estmasters.AA_TS','=','TS')->where('estmasters.AA_No','<>','')->where('estmasters.TS_No','=','');
                                         //Filter ---------------------------------
                                        if($request->is_estimate_no == 'on'){
                                        $queryTSFinalRegisterNew->where("estmasters.Est_No", '=', $request->txtsearchEstimateno);
                                        }
                                        if($request->is_name_work == 'on'){
                                        $queryTSFinalRegisterNew->where("estmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                        }
                                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                            $queryTSFinalRegisterNew->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                        }
                                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                           $startDate = $request->txtsearchFromPreDate;
                                           $endDate = $request->txtsearchToPreDate;
                                            $queryTSFinalRegisterNew->whereDate('estmasters.Date_Prep','<=', $startDate)
                                           ->whereDate('estmasters.Date_Prep','>=', $endDate);
                                         }
                                        //Filter ---------------------------------
                                        $projectTSFinalRegisterNew = $queryTSFinalRegisterNew->paginate(10);
                                        break;
                                }else{
                                   // echo "Step2"; exit;
                                    // If work id
                                    if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                        //Calculate Count
                                        if($initCount == 0){

                                           $queryTSFinalRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                           $queryTSFinalRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                        }else{

                                           $queryTSFinalRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                        }
                                    }else{

                                        if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                            //Calculate Count
                                            if($initCount == 0){

                                              $queryTSFinalRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                              $queryTSFinalRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }else{

                                              $queryTSFinalRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                            }

                                        }
                                        if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){

                                            //Calculate Count
                                            $queryTSFinalRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                        }

                                    }

                                }

                                $queryTSFinalRegisterNew->Where('estmasters.AA_TS','=','TS')->where('estmasters.AA_No','<>','')->where('estmasters.TS_No','=','');
                                  //Filter ---------------------------------
                                  if($request->is_estimate_no == 'on'){
                                    $queryTSFinalRegisterNew->where("estmasters.Est_No", '=', $request->txtsearchEstimateno);
                                    }
                                    if($request->is_name_work == 'on'){
                                    $queryTSFinalRegisterNew->where("estmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                    }
                                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                        $queryTSFinalRegisterNew->where([['estmasters.Tot_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['estmasters.Tot_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                    }
                                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                       $startDate = $request->txtsearchFromPreDate;
                                       $endDate = $request->txtsearchToPreDate;
                                        $queryTSFinalRegisterNew->whereDate('estmasters.Date_Prep','<=', $startDate)
                                       ->whereDate('estmasters.Date_Prep','>=', $endDate);
                                     }
                                    //Filter ---------------------------------
                                $projectTSFinalRegisterNew =$queryTSFinalRegisterNew->paginate(10);
                                $initCount++;
                            }
                    }else{
                            $projectTSFinalRegisterNew =DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep")->where('estmasters.Est_Id','=',0)
                                ->Where('estmasters.AA_TS','=','TS')->where('estmasters.AA_No','<>','')->where('estmasters.TS_No','=','')->paginate(10);

                    }
                // T.S.Register Last Step Result----------------------------------------------------------------
                return response()->json(array('data'=> $projectTSFinalRegisterNew,'wid'=> $request->wid), 200);

                 } catch (\Throwable $th) {
                    throw $th;
                }
        }


       // DTP Register Related All Functionality----------------------------------------------------------------------
        public function createviewDTPRegister(Request $request)
        {
           try {
            //Login Session Details
            $uid = auth()->user()->id??0;
            $usercode = auth()->user()->usercode??'';
            $divid = auth()->user()->Div_id??0;
            $subdivid = auth()->user()->Sub_Div_id??0;
            //Get User Permission

            //Get Division Name
            // $divisionName = Division::select()
            $DSFoundhd = DB::table('userperms')
            ->select('F_H_CODE','Sub_Div_Id','Work_Id')
            ->where('User_Id', '=',$uid)
            ->where('Removed','=',1)
            ->get();

            $UseUserPermission = json_decode($DSFoundhd);
            $FinalExecuteQuery = '';
            $rsFilterResult = '';

            if($UseUserPermission){
                    //Get All Estimates
                    $query = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);

                            //echo "Step0"; exit;
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                $query = DB::table('workmasters')
                                ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                                ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                                ->Where('workmasters.DTP_App_No', '<>','');

                                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                    $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                                }
                                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                    $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                                }
                                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                    $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                                }
                                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                    $startDate = $request->txtsearchFromPreDate;
                                    $endDate = $request->txtsearchToPreDate;
                                    $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                    ->whereDate('workmasters.AA_Dt','>=', $endDate);
                                }

                                // if($request->flg == 'f'){ //Red
                                //     $query->Where('workmasters.DTP_App_No', '=','');
                                // }else if($request->flg == 't'){//Green
                                //     $query->Where('workmasters.DTP_App_No', '<>','');
                                // }

                                $query->orderBy('workmasters.created_at', 'desc');
                                $project = $query->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                       $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                        $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                          $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{

                                          $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }
                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $query->Where('workmasters.DTP_App_No', '<>','');
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                ->whereDate('workmasters.AA_Dt','>=', $endDate);
                            }

                            // if($request->flg == 'f'){ //Red
                            //     $query->where('workmasters.DTP_App_No','=','');
                            // }else if($request->flg == 't'){//Green
                            //     $query->where('workmasters.DTP_App_No','<>','');
                            // }

                            $query->orderBy('workmasters.created_at', 'desc');
                            $project =$query->paginate(10);
                            $initCount++;
                        }
                }else{
                            $project = DB::table('workmasters')
                            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                            ->Where('workmasters.DTP_App_No','<>','')
                            ->orderBy('workmasters.created_at', 'desc')
                            ->paginate(10);

                }

                // A.A.Register New Model Result----------------------------------------------------------------
                if($UseUserPermission){
                    //Get All Estimates
                    $queryDTPRegisterNew = DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No");

                            $initCount = 0;
                            foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                                $rsFound->F_H_CODE;
                                $rsFound->Sub_Div_Id;
                                $rsFound->Work_Id;
                                $foundcount = strlen($rsFound->F_H_CODE);
                            if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                                //echo "Step1"; exit;
                                $queryDTPRegisterNew = '';
                                $queryDTPRegisterNew = DB::table('estmasters')
                                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                                ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                               ->Where('workmasters.TS_No', '<>','')
                               ->Where('workmasters.DTP_App_No', '=','');
                                $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                                $projectDTPRegisterNew = $queryDTPRegisterNew->paginate(10);
                                break;
                            }else{
                               // echo "Step2"; exit;
                                // If work id
                                if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                    //Calculate Count
                                    if($initCount == 0){

                                       $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                       $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }else{

                                       $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                    }
                                }else{

                                    if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                        //Calculate Count
                                        if($initCount == 0){

                                          $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                          $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }else{


                                          $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                        }

                                    }
                                    if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                        //Calculate Count

                                        $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                    }

                                }

                            }
                            $queryDTPRegisterNew->Where('workmasters.TS_No', '<>','')->Where('workmasters.DTP_App_No', '=','');
                            $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                            $projectDTPRegisterNew =$queryDTPRegisterNew->paginate(10);
                            $initCount++;
                        }
                }else{
                        $projectDTPRegisterNew =DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where('estmasters.Est_Id','=',0)
                            ->Where([['workmasters.TS_No', '<>',''],['workmasters.DTP_App_No', '=','']])
                            ->orderBy('estmasters.created_at', 'desc')
                            ->paginate(10);

                }
            $dataMasterAgency= Agency::get();
            // A.A.Register New Model Result----------------------------------------------------------------
            return view('dtpregister/list',['data'=>$project,'dataDTPRegisterNew'=>$projectDTPRegisterNew]);

            } catch (\Throwable $th) {
                throw $th;
            }
        }







        public function DTPRegisterEntryForm(Request $request)
        {
            try {
               // Genrate unique ID Genration
                $uniquenumber = uniqid();

                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------

                // Get Division ID To Division Name
                $rsDivisionDtls = DB::table('divisions')
                ->selectRaw('`div_m`,`div`,`div_id`')
                ->where('div_id','=',$divid)->get();
                $rsDiv = json_decode($rsDivisionDtls,true);

                //Get Selected Divisions All Subdivisions
                $rsSubDivisionDtls = DB::table('subdivms')
                ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
                ->where('Div_Id','=',$divid)->get();

                $rsWorkType = DB::table('worktypes')
                ->selectRaw('`id`,`worktype`')
                ->get();

                $rsTalukas = DB::table('talms')
                ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
                ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
                ->where('divisions.div_id','=',$divid)
                ->get();

                // Scope Master data
                $scopeMasterList = Scopm::get();

                $SQLNewPKID = DB::table('estrecaps')
                ->selectRaw('Sr_No + 1 as Sr_No')
                ->orderBy('Sr_No', 'desc')
                ->where('Est_Id','=',$uniquenumber)
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
                    $RecapSrno=$RSNewPKID[0]->Sr_No;
                }else{
                    $RecapSrno=1;
                }

                return view('dtpregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno]);

            } catch (\Throwable $th) {
                //throw $th;
            }
        }


        //View DTP Estimation Form
        public function EditViewDTPEstimateForm(Request $request)
        {
            try{
            //Genrate unique ID Genration
            $uniquenumber = uniqid();
            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
            $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
            $RecapSrno=1;
            }

            //Get Estimate Details
            // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
            $rsestimate = DB::table('estmasters')
            ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
            ->where('Est_Id','=',$request->id)
            ->first();

            //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

            $FinanacialYear1 = DB::table('acyrms')
            ->selectRaw('Ac_Yr')
            ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
            ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
            ->first();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

            //Get Estimate
            $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

            //Get Agency Master
            $dataMasterAgency= Agency::get();
            //dd($dataMasterAgency);

            return view('dtpregister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster'),);

            } catch (\Throwable $th) {
                throw $th;
            }

        }

        //View DTP Register
        public function ViewDTPRegister(Request $request)
        {
        try{
            //Genrate unique ID Genration
            $uniquenumber = uniqid();
            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();


            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
            $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
            $RecapSrno=1;
            }

            //Get Estimate Details
            // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
            $rsestimate = DB::table('estmasters')
            ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
            ->where('Est_Id','=',$request->id)
            ->first();

            //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

            $FinanacialYear1 = DB::table('acyrms')
            ->selectRaw('Ac_Yr')
            ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
            ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
            ->first();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

            //Get Estimate
            $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

            //Get Agency Master
            $dataMasterAgency= Agency::get();
            //dd($dataMasterAgency);

            return view('dtpregister/view',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster'));

            } catch (\Throwable $th) {
                throw $th;
            }
        }

        public function UpdateDTPRegisterDetails(Request $request){
             try {
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------
                //DTP Register file Uploading .pdf

                $fileDTP_PDF_Path = $request->file('DTP_PDF_Path');
                if($fileDTP_PDF_Path){
                        //Unlink Old File
                        if($request->oldDTP_PDF_Path){
                            $image_path =   public_path('uploads/dtppdf/' . $request->oldDTP_PDF_Path);
                            if(file_exists($image_path)){
                             unlink($image_path);
                            }
                        }
                        $filenamepdf = time().$fileDTP_PDF_Path->getClientOriginalName();
                        $extensionpdf = $fileDTP_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf = $fileDTP_PDF_Path->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationpdf = 'uploads/dtppdf'; //Created an "uploads" folder for that
                        // Upload file
                        $fileDTP_PDF_Path->move($locationpdf, $filenamepdf);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamedtppdf = $filenamepdf;
                        $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNamedtppdf);
                    }else{
                        $this->fileNamedtppdf = $request->oldDTP_PDF_Path;
                    }

                    $fileDTP_pdf_path1= $request->file('DTP_pdf_path1');
                    if($fileDTP_pdf_path1){
                        //Unlink Old File
                        if($request->oldDTP_pdf_path1){
                            $image_path1 =   public_path('uploads/dtppdf1/' . $request->oldDTP_pdf_path1);
                            if(file_exists($image_path1)){
                             unlink($image_path1);
                            }
                        }
                        $filenamepdf1 = time().$fileDTP_pdf_path1->getClientOriginalName();
                        $extensionpdf1 = $fileDTP_pdf_path1->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf1 = $fileDTP_pdf_path1->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationpdf1 = 'uploads/dtppdf1'; //Created an "uploads" folder for that
                        // Upload file
                        $fileDTP_pdf_path1->move($locationpdf1, $filenamepdf1);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamedtppdf1 = $filenamepdf1;
                        $this->filepathpdf1 = public_path($locationpdf1 . "/" . $this->fileNamedtppdf1);
                    }else{
                        $this->fileNamedtppdf1 = $request->oldDTP_pdf_path1;
                    }

                    $fileDTP_pdf_path2= $request->file('DTP_pdf_path2');
                    if($fileDTP_pdf_path2){
                        //Unlink Old File
                        if($request->oldDTP_pdf_path2){
                            $image_path2 =   public_path('uploads/dtppdf2/' . $request->oldDTP_pdf_path2);
                            if(file_exists($image_path2)){
                             unlink($image_path2);
                            }
                        }
                        $filenamepdf2 = time().$fileDTP_pdf_path2->getClientOriginalName();
                        $extensionpdf2 = $fileDTP_pdf_path2->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf2 = $fileDTP_pdf_path2->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationpdf2 = 'uploads/dtppdf2'; //Created an "uploads" folder for that
                        // Upload file
                        $fileDTP_pdf_path2->move($locationpdf2, $filenamepdf2);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamedtppdf2 = $filenamepdf2;
                        $this->filepathpdf2 = public_path($locationpdf2 . "/" . $this->fileNamedtppdf2);
                    }else{
                        $this->fileNamedtppdf2 = $request->oldDTP_pdf_path2;
                    }

                    $fileDTP_pdf_path3= $request->file('DTP_pdf_path3');
                    if($fileDTP_pdf_path3){
                        //Unlink Old File
                        if($request->oldDTP_pdf_path3){
                            $image_path3 =   public_path('uploads/dtppdf3/' . $request->oldDTP_pdf_path3);
                            if(file_exists($image_path3)){
                             unlink($image_path3);
                            }
                        }
                        $filenamepdf3 = time().$fileDTP_pdf_path3->getClientOriginalName();
                        $extensionpdf3 = $fileDTP_pdf_path3->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf3 = $fileDTP_pdf_path3->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationpdf3 = 'uploads/dtppdf3'; //Created an "uploads" folder for that
                        // Upload file
                        $fileDTP_pdf_path3->move($locationpdf3, $filenamepdf3);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamedtppdf3 = $filenamepdf3;
                        $this->filepathpdf3 = public_path($locationpdf3 . "/" . $this->fileNamedtppdf3);
                    }else{
                        $this->fileNamedtppdf3 = $request->oldDTP_pdf_path3;
                    }

                    $fileDTP_pdf_path4= $request->file('DTP_pdf_path4');
                    if($fileDTP_pdf_path4){
                        //Unlink Old File
                        if($request->oldDTP_pdf_path4){
                            $image_path4 =   public_path('uploads/dtppdf4/' . $request->oldDTP_pdf_path4);
                            if(file_exists($image_path4)){
                             unlink($image_path4);
                            }
                        }
                        $filenamepdf4 = time().$fileDTP_pdf_path4->getClientOriginalName();
                        $extensionpdf4 = $fileDTP_pdf_path4->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathpdf4 = $fileDTP_pdf_path4->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationpdf4 = 'uploads/dtppdf4'; //Created an "uploads" folder for that
                        // Upload file
                        $fileDTP_pdf_path4->move($locationpdf4, $filenamepdf4);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamedtppdf4 = $filenamepdf4;
                        $this->filepathpdf4 = public_path($locationpdf4 . "/" . $this->fileNamedtppdf4);
                    }else{
                        $this->fileNamedtppdf4 = $request->oldDTP_PDF_Path3;
                    }

                    //DTP Register file Uploading .pdf

                    //DTP Register file Uploading .XLS
                    $fileDTP_XLS_Path = $request->file('DTP_XLS_Path');
                    if($fileDTP_XLS_Path){
                            //Unlink Old File
                            if($request->oldDTP_XLS_Path){
                                $image_path =   public_path('uploads/dtpxls/'. $request->oldDTP_XLS_Path);
                                if(file_exists($image_path)){
                                unlink($image_path);
                                }
                            }
                            $filenamexls = time().$fileDTP_XLS_Path->getClientOriginalName();
                            $extensionxls = $fileDTP_XLS_Path->getClientOriginalExtension(); //Get extension of uploaded file
                            $tempPathxls = $fileDTP_XLS_Path->getRealPath();
                            //Where uploaded file will be stored on the server
                            $locationxls = 'uploads/dtpxls'; //Created an "uploads" folder for that
                            // Upload file
                            $fileDTP_XLS_Path->move($locationxls, $filenamexls);
                            // In case the uploaded file path is to be stored in the database
                            $this->fileNamedtpxls1 = $filenamexls;
                            $this->filepathxls = public_path($locationxls . "/" . $this->fileNamedtpxls1);
                    }
                    //DTP Register file Uploading .XLS
                    if ($request->hasFile('dtp_path_apr')) {
                        $file = $request->file('dtp_path_apr');
                        $allowedExtensions = ['pdf', 'jpeg', 'jpg']; // परवानगी असलेले एक्सटेंशन

                        $extension = $file->getClientOriginalExtension();

                        if (in_array($extension, $allowedExtensions)) {
                            // जुनी फाईल डिलीट करणे
                            if ($request->old_dtp_path_apr) {
                                $oldFilePath = public_path('uploads/dtpA/' . $request->old_dtp_path_apr);
                                if (file_exists($oldFilePath)) {
                                    unlink($oldFilePath);
                                }
                            }

                            // नवीन फाईलचे नाव व अपलोड करणे
                            $fileName = time() . '.' . $extension;
                            $file->move(public_path('uploads/dtpA'), $fileName);
                        } else {
                            return back()->with('error', 'फक्त PDF आणि JPEG फाईल अपलोड करू शकता.');
                        }
                    } else {
                        $fileName = $request->old_dtp_path_apr ?? null;
                    }


               //work Master Entry Present Or Not
                if($request->Work_Id){
                 // Update Work Id
                    $SQLUpdateWorkMaster = DB::table('workmasters')->where('Work_Id',"=",$request->Work_Id)->update([ 'dtp_path_apr' => $fileName,'DTP_App_No' => $request->DTP_App_No?$request->DTP_App_No:'', 'DTP_App_Dt' => $request->DTP_App_Dt, 'DTP_App_Amt' =>$request->DTP_App_Amt?$request->DTP_App_Amt:'', 'DTP_App_Auth' => $request->DTP_App_Auth?$request->DTP_App_Auth:'', 'DTP_App_Rem' => $request->DTP_App_Rem?$request->DTP_App_Rem:'', 'tendor_no' => $request->tendor_no?$request->tendor_no:'', 'Tender_Id' => $request->Tender_Id?$request->Tender_Id:'', 'tendor_preparedt' => $request->tendor_preparedt?$request->tendor_preparedt:null, 'DTP_PDF_Path' => $this->fileNamedtppdf?$this->fileNamedtppdf:'','DTP_XLS_Path' => $this->fileNamedtpxls1?$this->fileNamedtpxls1:'','Tnd_No'=>$request->tendor_no?$request->tendor_no:'','Tnd_Pub_Dt'=>$request->Tnd_Pub_Dt?$request->Tnd_Pub_Dt:null,'Tnd_Pub_In'=>$request->Tnd_Pub_In?$request->Tnd_Pub_In:'','Period_Fr'=>$request->Period_Fr?$request->Period_Fr:null,'Period_To'=>$request->Period_To?$request->Period_To:null,'Tnd_Type'=>$request->Tnd_Type?$request->Tnd_Type:'','Tnd_Amt'=>$request->Tnd_Amt?$request->Tnd_Amt:0,'EMD'=>$request->EMD?$request->EMD:0,'Con_Class'=>$request->Con_Class?$request->Con_Class:'','Response'=>$request->Response?$request->Response:0,'Agency_Id'=>$request->Agency_Id?$request->Agency_Id:'','Tnd_Remark'=>$request->Tnd_Remark?$request->Tnd_Remark:'','DTP_pdf_path1'=>$this->fileNamedtppdf1?$this->fileNamedtppdf1:'','DTP_pdf_path2'=>$this->fileNamedtppdf2?$this->fileNamedtppdf2:'','DTP_pdf_path3'=>$this->fileNamedtppdf3?$this->fileNamedtppdf3:'','DTP_pdf_path4'=>$this->fileNamedtppdf4?$this->fileNamedtppdf4:'']);
                }

                return  redirect()->to($request->last_url)->with('success','Record Updated Successfully');

                } catch (\Throwable $th) {
                throw $th;
               }
        }

// DTP Register Related All Functionality--------------------------------------


//DTP Register List
public function createviewDTPRegisterHomePendingList(Request $request)
{
   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
            //Get All Estimates
            $query = DB::table('workmasters')
            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);

                    //echo "Step0"; exit;
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        $query = DB::table('workmasters')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                        ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.DTP_App_No', '<>','');

                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->Where('workmasters.DTP_App_No', '=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->Where('workmasters.DTP_App_No', '<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project = $query->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                               $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                                $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                  $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                  $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }
                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $query->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->where('workmasters.DTP_App_No','=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->where('workmasters.DTP_App_No','<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project =$query->paginate(10);
                    $initCount++;
                }
        }else{
                    $project = DB::table('workmasters')
                    ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where('workmasters.DTP_App_No','<>','')
                    ->orderBy('workmasters.created_at', 'desc')
                    ->paginate(10);

        }

        // DTP Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                       ->Where('workmasters.TS_No', '<>','')
                       ->Where('workmasters.DTP_App_No', '=','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectDTPRegisterNew = $queryDTPRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){
                                $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{
                                $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){
                                    $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                    $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{
                                    $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count
                                    $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.TS_No', '<>','')->Where('workmasters.DTP_App_No', '=','');
                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectDTPRegisterNew =$queryDTPRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectDTPRegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where('estmasters.Est_Id','=',0)
                    ->Where([['workmasters.TS_No', '<>',''],['workmasters.DTP_App_No', '=','']])
                    ->orderBy('estmasters.created_at', 'desc')
                    ->paginate(10);

        }
    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('dtpregister/dtplist',['data'=>$project,'dataDTPRegisterNew'=>$projectDTPRegisterNew]);

    } catch (\Throwable $th) {
        throw $th;
    }
}




//Work Order List
public function createviewWORegister(Request $request)
{
   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
            //Get All Estimates
            $query = DB::table('workmasters')
            ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);

                    //echo "Step0"; exit;
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        $query = DB::table('workmasters')
                        ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                        ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.WO_No', '<>','');

                       // dd($request);
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->Where('workmasters.DTP_App_No', '=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->Where('workmasters.DTP_App_No', '<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project = $query->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                               $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                                $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                  $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                  $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }
                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $query->Where('workmasters.WO_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                        ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->where('workmasters.DTP_App_No','=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->where('workmasters.DTP_App_No','<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project =$query->paginate(10);
                    $initCount++;
                }
        }else{
                    $project = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where('workmasters.WO_No', '<>','')
                    ->orderBy('workmasters.created_at', 'desc')
                    ->paginate(10);

        }

        // A.A.Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                        // ->Where('workmasters.WO_No', '=','');
                        ->Where('workmasters.DTP_App_No', '<>','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){
                                  //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count
                                // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectWORegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                    // ->Where('workmasters.WO_No', '=','')
                    ->Where('workmasters.DTP_App_No', '<>','')
                    ->orderBy('estmasters.created_at', 'desc')
                    ->paginate(10);

        }

    //dd($projectWORegisterNew);
    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('workOrderRegister/list',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

    } catch (\Throwable $th) {
        throw $th;
    }
    }


    public function createviewWORegisterR(Request $request)
{
   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
            //Get All Estimates
            $query = DB::table('workmasters')
            ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);

                    //echo "Step0"; exit;
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        $query = DB::table('workmasters')
                        ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                        ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.WO_No', '<>','');

                       // dd($request);
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->Where('workmasters.DTP_App_No', '=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->Where('workmasters.DTP_App_No', '<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project = $query->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                               $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                                $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                  $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                  $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }
                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $query->Where('workmasters.WO_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                        ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->where('workmasters.DTP_App_No','=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->where('workmasters.DTP_App_No','<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project =$query->paginate(10);
                    $initCount++;
                }
        }else{
                    $project = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where('workmasters.WO_No', '<>','')
                    ->orderBy('workmasters.created_at', 'desc')
                    ->paginate(10);

        }

        // A.A.Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                        // ->Where('workmasters.WO_No', '=','');
                        ->Where('workmasters.DTP_App_No', '<>','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){
                                  //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count
                                // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectWORegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                    // ->Where('workmasters.WO_No', '=','')
                    ->Where('workmasters.DTP_App_No', '<>','')
                    ->orderBy('estmasters.created_at', 'desc')
                    ->paginate(10);

        }

    //dd($projectWORegisterNew);
    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('workOrderRegisterR/listR',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

    } catch (\Throwable $th) {
        throw $th;
    }
    }


    public function createviewWORegister1(Request $request)
{
   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
        //Get All Estimates
        $query = DB::table('workmasters')
        ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
        ->leftJoin('progressreports', function ($join) {
            $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                ->whereRaw('progressreports.created_at = (SELECT MAX(created_at) FROM progressreports WHERE work_id = workmasters.Work_Id)');
        })
        ->select("workmasters.Work_Id", "progressreports.status", "workmasters.Stip_Comp_Dt","workmasters.actual_complete_date", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                $initCount = 0;
                foreach(json_decode($DSFoundhd) as $rsFound){
                    $rsFound->F_H_CODE;
                    $rsFound->Sub_Div_Id;
                    $rsFound->Work_Id;
                    $foundcount = strlen($rsFound->F_H_CODE);

                //echo "Step0"; exit;
                if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                    $query = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->leftJoin('progressreports', function ($join) {
                        $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                            ->whereRaw('progressreports.created_at = (SELECT MAX(created_at) FROM progressreports WHERE work_id = workmasters.Work_Id)');
                    })
                    ->select("workmasters.Work_Id",    DB::raw("
                    CASE
                        WHEN (progressreports.status IS NULL OR progressreports.status = '')
                             AND (workmasters.Tot_Exp IS NOT NULL AND workmasters.Tot_Exp > 0)
                        THEN 'Work 20% Completed'
                        ELSE IFNULL(NULLIF(progressreports.status, ''), ' Work Order Issued')
                    END as status
                "),
                DB::raw("IF(workmasters.Tot_Exp = 0.00, ' Work Order Issued ', workmasters.Tot_Exp) as Expenditure"),DB::raw("IF(workmasters.Tot_Exp = 0.00, 'Work Issued', workmasters.Tot_Exp) as Expenditure"), "workmasters.Budget_Yr",  "workmasters.Stip_Comp_Dt","workmasters.actual_complete_date", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                    ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                    ->Where('workmasters.WO_No', '<>','')
                    ->where('workmasters.is_work_complete', '=', 1);  // Ensure is_work_complete is true


                   // dd($request);
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->Where('workmasters.DTP_App_No', '=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->Where('workmasters.DTP_App_No', '<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project = $query->paginate(10);
                    break;
                }else{
                   // echo "Step2"; exit;
                    // If work id
                    if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                        //Calculate Count
                        if($initCount == 0){

                           $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                           $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                        }else{

                            $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                        }
                    }else{

                        if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                            //Calculate Count
                            if($initCount == 0){

                              $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                              $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                            }else{

                              $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                            }
                        }
                        if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                            //Calculate Count

                            $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                        }

                    }

                }
                $query->Where('workmasters.WO_No', '<>','');
                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                    $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                }
                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                    $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                }
                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                    $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                }
                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                    $startDate = $request->txtsearchFromPreDate;
                    $endDate = $request->txtsearchToPreDate;
                    $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                    ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                }

                // if($request->flg == 'f'){ //Red
                //     $query->where('workmasters.DTP_App_No','=','');
                // }else if($request->flg == 't'){//Green
                //     $query->where('workmasters.DTP_App_No','<>','');
                // }

                $query->orderBy('workmasters.created_at', 'desc');
                $project =$query->paginate(10);
                $initCount++;
            }
    }else{
                $project = DB::table('workmasters')
                ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                ->leftJoin('progressreports', function($join) {
                    $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                         ->whereRaw('progressreports.work_id = (SELECT MAX(work_id) FROM progressreports WHERE work_id = workmasters.Work_Id)');
                })
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->select("workmasters.Work_Id",   DB::raw("
                CASE
                    WHEN (progressreports.status IS NULL OR progressreports.status = '')
                         AND (workmasters.Tot_Exp IS NOT NULL AND workmasters.Tot_Exp > 0)
                    THEN 'Work 20% Completed'
                    ELSE IFNULL(NULLIF(progressreports.status, ''), ' Work Order Issued ')
                END as status
            "),
            DB::raw("IF(workmasters.Tot_Exp = 0.00, ' Work Order Issued ', workmasters.Tot_Exp) as Expenditure"), // If Tot_Exp is 0, display 'Work Issued'
                "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code",  "workmasters.Stip_Comp_Dt","workmasters.actual_complete_date","workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                ->Where('workmasters.WO_No', '<>','')
                ->where('workmasters.is_work_complete', '=', 1)  // Ensure is_work_complete is true

                ->orderBy('workmasters.created_at', 'desc')
                ->paginate(10);

    }

        // A.A.Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                        // ->Where('workmasters.WO_No', '=','');
                        ->Where('workmasters.DTP_App_No', '<>','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){
                                  //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count
                                // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectWORegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                    // ->Where('workmasters.WO_No', '=','')
                    ->Where('workmasters.DTP_App_No', '<>','')
                    ->orderBy('estmasters.created_at', 'desc')
                    ->paginate(10);

        }

    //dd($projectWORegisterNew);
    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('workOrderRegister/list1',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

    } catch (\Throwable $th) {
        throw $th;
    }
    }


    public function EditViewWOEstimateForm(Request $request)
    {
        try{
        //Genrate unique ID Genration
        $uniquenumber = uniqid();
        // Logged User Session
        // login user session Data----------------------------
        $divid = auth()->user()->Div_id;
        $usercode = auth()->user()->usercode;
        $subdivid = auth()->user()->Sub_Div_id;
        // login user session Data----------------------------

        $FinanacialYear = DB::table('acyrms')
        ->selectRaw('`Ac_Yr`')
        ->get();

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();
        $rsDiv = json_decode($rsDivisionDtls,true);

        //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();
        $rsTalukas = DB::table('talms')
        ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
        ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
        ->where('divisions.div_id','=',$divid)
        ->get();


        //Scope Master data
        $scopeMasterList = Scopm::get();

        $SQLNewPKID = DB::table('estrecaps')
        ->selectRaw('Sr_No + 1 as Sr_No')
        ->orderBy('Sr_No', 'desc')
        ->where('Est_Id','=',$uniquenumber)
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
        $RecapSrno=$RSNewPKID[0]->Sr_No;
        }else{
        $RecapSrno=1;
        }

        //Get Estimate Details
        // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
        $rsestimate = DB::table('estmasters')
        ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
        ->where('Est_Id','=',$request->id)
        ->first();

        //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

        $FinanacialYear1 = DB::table('acyrms')
        ->selectRaw('Ac_Yr')
        ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
        ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
        ->first();

        //Get Estimate Scope
        $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

        //Get Estimate Recape
        $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

        //Get Estimate
        $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

        //Get Agency Master
        $dataMasterAgency= Agency::get();

        return view('workOrderRegister/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency ,  'rsworkmaster' => $rsworkmaster],compact('rsworkmaster'));

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function EditViewWOEstimateFormR(Request $request)
    {
        try{
        //Genrate unique ID Genration
        $uniquenumber = uniqid();
        // Logged User Session
        // login user session Data----------------------------
        $divid = auth()->user()->Div_id;
        $usercode = auth()->user()->usercode;
        $subdivid = auth()->user()->Sub_Div_id;
        // login user session Data----------------------------

        $FinanacialYear = DB::table('acyrms')
        ->selectRaw('`Ac_Yr`')
        ->get();

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();
        $rsDiv = json_decode($rsDivisionDtls,true);

        //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();
        $rsTalukas = DB::table('talms')
        ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
        ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
        ->where('divisions.div_id','=',$divid)
        ->get();


        //Scope Master data
        $scopeMasterList = Scopm::get();

        $SQLNewPKID = DB::table('estrecaps')
        ->selectRaw('Sr_No + 1 as Sr_No')
        ->orderBy('Sr_No', 'desc')
        ->where('Est_Id','=',$uniquenumber)
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
        $RecapSrno=$RSNewPKID[0]->Sr_No;
        }else{
        $RecapSrno=1;
        }

        //Get Estimate Details
        // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
        $rsestimate = DB::table('estmasters')
        ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
        ->where('Est_Id','=',$request->id)
        ->first();

        //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

        $FinanacialYear1 = DB::table('acyrms')
        ->selectRaw('Ac_Yr')
        ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
        ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
        ->first();

        //Get Estimate Scope
        $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

        //Get Estimate Recape
        $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

        //Get Estimate
        $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

        //Get Agency Master
        $dataMasterAgency= Agency::get();

        return view('workOrderRegister/listR',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency ,  'rsworkmaster' => $rsworkmaster],compact('rsworkmaster'));

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function UpdateWORegisterDetails(Request $request){
        try {
                // login user session Data----------------------------
                $divid = auth()->user()->Div_id??0;
                $usercode = auth()->user()->usercode??'';
                $subdivid = auth()->user()->Sub_Div_id??0;
                // login user session Data----------------------------


               //WO Register file Uploading .pdf

               if( $request->file('WO_PDF_Path')){
                    $fileWO_PDF_Path = $request->file('WO_PDF_Path');
                   //Unlink Old File
                    if($request->oldWO_PDF_Path){
                        $image_path =   public_path('uploads/wopdf/' . $request->oldWO_PDF_Path);
                        if(file_exists($image_path)){
                            unlink($image_path);
                        }
                    }
                    $filenameWOpdf = time().$fileWO_PDF_Path->getClientOriginalName();
                    $extensionpdf = $fileWO_PDF_Path->getClientOriginalExtension(); //Get extension of uploaded file
                    $tempPathpdf = $fileWO_PDF_Path->getRealPath();
                    //Where uploaded file will be stored on the server
                    $locationpdf = 'uploads/wopdf'; //Created an "uploads" folder for that
                    // Upload file
                    $fileWO_PDF_Path->move($locationpdf, $filenameWOpdf);
                    // In case the uploaded file path is to be stored in the database
                    $this->fileNameWOpdf1 = $filenameWOpdf;
                    $this->filepathpdf = public_path($locationpdf . "/" . $this->fileNamedtppdf1);
               }else{
                   $this->fileNameWOpdf1 =$request->oldWO_PDF_Path?$request->oldWO_PDF_Path:'';
               }
               //WO Register file Uploading .pdf



               //WO Photo 1 file Upload-------------------------------------------------

               if($request->file('WO_Site_Photo1_Path')){
                    $fileWO_Site_Photo1_Path = $request->file('WO_Site_Photo1_Path');
                    //Unlink Old File
                    if($request->oldWO_Site_Photo1_Path){
                        $image_path =   public_path('uploads/wophoto1/'. $request->oldWO_Site_Photo1_Path);
                        if(file_exists($image_path)){
                            unlink($image_path);
                        }
                    }
                    $filenamephoto1 = time().$fileWO_Site_Photo1_Path->getClientOriginalName();
                    $extensionxls = $fileWO_Site_Photo1_Path->getClientOriginalExtension(); //Get extension of uploaded file
                    $tempPathxls = $fileWO_Site_Photo1_Path->getRealPath();
                    //Where uploaded file will be stored on the server
                    $locationwophoto1 = 'uploads/wophoto1'; //Created an "uploads" folder for that
                    // Upload file
                    $fileWO_Site_Photo1_Path->move($locationwophoto1, $filenamephoto1);
                    // In case the uploaded file path is to be stored in the database
                    $this->fileNamewophoto1 = $filenamephoto1;
                    $this->filepathxls = public_path($locationwophoto1 . "/" . $this->fileNamewophoto1);
               }else{
                    $this->fileNamewophoto1 = $request->oldWO_Site_Photo1_Path?$request->oldWO_Site_Photo1_Path:'';
               }
              //WO Photo 1 file Upload-------------------------------------------------


               //WO Photo 2 file Upload-------------------------------------------------

               if($request->file('WO_Site_Photo2_Path')){
                $fileWO_Site_Photo2_Path = $request->file('WO_Site_Photo2_Path');
                   //Unlink Old File
                    if($request->oldWO_Site_Photo2_Path){
                        $image_path =   public_path('uploads/wophoto2/'. $request->oldWO_Site_Photo2_Path);
                        if(file_exists($image_path)){
                            unlink($image_path);
                        }
                    }
                    $filenamephoto2 = time().$fileWO_Site_Photo2_Path->getClientOriginalName();
                    $extensionxls = $fileWO_Site_Photo2_Path->getClientOriginalExtension(); //Get extension of uploaded file
                    $tempPathxls = $fileWO_Site_Photo2_Path->getRealPath();
                    //Where uploaded file will be stored on the server
                    $locationwophoto2 = 'uploads/wophoto2'; //Created an "uploads" folder for that
                    // Upload file
                    $fileWO_Site_Photo2_Path->move($locationwophoto2, $filenamephoto2);
                    // In case the uploaded file path is to be stored in the database
                    $this->fileNamewophoto2 = $filenamephoto2;
                    $this->filepathxls = public_path($locationwophoto2 . "/" . $this->fileNamewophoto2);
               }else{
                    $this->fileNamewophoto2 = $request->oldWO_Site_Photo2_Path?$request->oldWO_Site_Photo2_Path:'';
               }
              //WO Photo 2 file Upload-------------------------------------------------


                  //WO Photo 2 file Upload-------------------------------------------------

                  if($request->file('WO_Site_Photo3_Path')){
                    $fileWO_Site_Photo3_Path = $request->file('WO_Site_Photo3_Path');
                      //Unlink Old File
                        if($request->oldWO_Site_Photo3_Path){
                            $image_path =   public_path('uploads/wophoto3/'. $request->oldWO_Site_Photo3_Path);
                            if(file_exists($image_path)){
                            unlink($image_path);
                            }
                        }
                        $filenamephoto3 = time().$fileWO_Site_Photo3_Path->getClientOriginalName();
                        $extensionxls = $fileWO_Site_Photo3_Path->getClientOriginalExtension(); //Get extension of uploaded file
                        $tempPathxls = $fileWO_Site_Photo3_Path->getRealPath();
                        //Where uploaded file will be stored on the server
                        $locationwophoto3 = 'uploads/wophoto3'; //Created an "uploads" folder for that
                        // Upload file
                        $fileWO_Site_Photo3_Path->move($locationwophoto3, $filenamephoto3);
                        // In case the uploaded file path is to be stored in the database
                        $this->fileNamewophoto3 = $filenamephoto3;
                        $this->filepathxls = public_path($locationwophoto3 . "/" . $this->fileNamewophoto3);
                  }else{
                      $this->fileNamewophoto3 = $request->oldWO_Site_Photo3_Path?$request->oldWO_Site_Photo3_Path:'';
                  }
                 //WO Photo 2 file Upload-------------------------------------------------

          //work Master Entry Present Or Not
           if($request->Work_Id){
            // Update Work Id
            $SQLUpdateWorkOrderDetails = DB::table('workmasters')->where('Work_Id',"=",$request->Work_Id)->update([ 'Agree_No' => $request->Agreement_No ?? '',  // **Agreement Number Update**
            'Agree_Dt' => $request->Agreement_Dt ?? '' ,'Tnd_Amt' => is_numeric($request->Tnd_Amt) ? $request->Tnd_Amt : 0.00,'Tender_Id' =>$request->Tender_Id,
'WO_No' => $request->WO_No?$request->WO_No:'', 'Wo_Dt' => $request->Wo_Dt, 'WO_Authority' =>$request->WO_Authority?$request->WO_Authority:'', 'Above_Below' => $request->Above_Below?$request->Above_Below:'', 'A_B_Pc' => $request->A_B_Pc?$request->A_B_Pc:0, 'WO_Amt' => $request->WO_Amt?$request->WO_Amt:0, 'Period' => $request->Period?$request->Period:0,'Perd_Unit'=>$request->Perd_Unit?$request->Perd_Unit:'', 'DLP' => $request->DLP?$request->DLP:0,'DLP_Unit' => $request->DLP_Unit?$request->DLP_Unit:'','Stip_Comp_Dt'=>$request->Stip_Comp_Dt,'DLP_Dt'=>$request->DLP_Dt,'SD_Cash_Pc'=>$request->SD_Cash_Pc?$request->SD_Cash_Pc:0,'SD_Cash'=>$request->SD_Cash?$request->SD_Cash:0,'SD_Bill_Pc'=>$request->SD_Bill_Pc?$request->SD_Bill_Pc:0,'SD_Bill'=>$request->SD_Bill?$request->SD_Bill:0,'Spl_SD_Pc'=>$request->Spl_SD_Pc?$request->Spl_SD_Pc:0,'Spl_SD'=>$request->Spl_SD?$request->Spl_SD:0,'Spl_Conditions'=>$request->Spl_Conditions?$request->Spl_Conditions:'','WO_Form_Cd'=>$request->WO_Form_Cd?$request->WO_Form_Cd:'','WO_Remark'=>$request->WO_Remark?$request->WO_Remark:'','WO_PDF_Path'=>$this->fileNameWOpdf1,'WO_Site_Photo1_Path'=>$this->fileNamewophoto1,'WO_Site_Photo2_Path'=>$this->fileNamewophoto2,'WO_Site_Photo3_Path'=>$this->fileNamewophoto3]);
           }
           return  redirect()->to($request->last_url)->with('success','Record Updated Successfully');

          } catch (\Throwable $th) {
           throw $th;
          }
   }

   public function createviewWORegister0(Request $request)
{
   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
            //Get All Estimates
            $query = DB::table('workmasters')
            ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->leftJoin('progressreports', function ($join) {
                $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                    ->whereRaw('progressreports.created_at = (SELECT MAX(created_at) FROM progressreports WHERE work_id = workmasters.Work_Id)');
            })
            ->select("workmasters.Work_Id", "progressreports.status", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);

                    //echo "Step0"; exit;
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        $query = DB::table('workmasters')
                        ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->leftJoin('progressreports', function ($join) {
                            $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                                ->whereRaw('progressreports.created_at = (SELECT MAX(created_at) FROM progressreports WHERE work_id = workmasters.Work_Id)');
                        })
                        ->select("workmasters.Work_Id",    DB::raw("
                        CASE
                            WHEN (progressreports.status IS NULL OR progressreports.status = '')
                                 AND (workmasters.Tot_Exp IS NOT NULL AND workmasters.Tot_Exp > 0)
                            THEN 'Work 20% Completed'
                            ELSE IFNULL(NULLIF(progressreports.status, ''), ' Work Order Issued')
                        END as status
                    "),
                    DB::raw("IF(workmasters.Tot_Exp = 0.00, ' Work Order Issued ', workmasters.Tot_Exp) as Expenditure"),DB::raw("IF(workmasters.Tot_Exp = 0.00, 'Work Issued', workmasters.Tot_Exp) as Expenditure"), "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                        ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.WO_No', '<>','')
                        ->where('workmasters.is_work_complete','<>','1');


                       // dd($request);
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->Where('workmasters.DTP_App_No', '=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->Where('workmasters.DTP_App_No', '<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project = $query->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                               $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                                $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                  $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                  $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }
                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $query->Where('workmasters.WO_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                        ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->where('workmasters.DTP_App_No','=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->where('workmasters.DTP_App_No','<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project =$query->paginate(10);
                    $initCount++;
                }
        }else{
                    $project = DB::table('workmasters')
                    ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('progressreports', function($join) {
                        $join->on('progressreports.work_id', '=', 'workmasters.Work_Id')
                             ->whereRaw('progressreports.work_id = (SELECT MAX(work_id) FROM progressreports WHERE work_id = workmasters.Work_Id)');
                    })
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id",   DB::raw("
                    CASE
                        WHEN (progressreports.status IS NULL OR progressreports.status = '')
                             AND (workmasters.Tot_Exp IS NOT NULL AND workmasters.Tot_Exp > 0)
                        THEN 'Work 20% Completed'
                        ELSE IFNULL(NULLIF(progressreports.status, ''), ' Work Order Issued ')
                    END as status
                "),
                DB::raw("IF(workmasters.Tot_Exp = 0.00, ' Work Order Issued ', workmasters.Tot_Exp) as Expenditure"), // If Tot_Exp is 0, display 'Work Issued'
                    "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where('workmasters.WO_No', '<>','')
                    ->where('workmasters.is_work_complete','<>','1')

                    ->orderBy('workmasters.created_at', 'desc')
                    ->paginate(10);

        }

        // A.A.Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                        // ->Where('workmasters.WO_No', '=','');
                        ->Where('workmasters.DTP_App_No', '<>','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{
                            //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                               $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){
                                  //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                  $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count
                                // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectWORegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                    // ->Where('workmasters.WO_No', '=','')
                    ->Where('workmasters.DTP_App_No', '<>','')
                    ->orderBy('estmasters.created_at', 'desc')
                    ->paginate(10);

        }

    //dd($projectWORegisterNew);
    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('workOrderRegister/list0',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

    } catch (\Throwable $th) {
        throw $th;
    }
    }



   //Wo Pending List For Dashboard List

   public function createviewWORegisterHomePendingList(Request $request)
{

   try {
    //Login Session Details
    $uid = auth()->user()->id??0;
    $usercode = auth()->user()->usercode??'';
    $divid = auth()->user()->Div_id??0;
    $subdivid = auth()->user()->Sub_Div_id??0;
    //Get User Permission

    //Get Division Name
   // $divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';

    if($UseUserPermission){
            //Get All Estimates
            $query = DB::table('workmasters')
            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);

                    //echo "Step0"; exit;
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        $query = DB::table('workmasters')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                        ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.DTP_App_No', '<>','');

                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->Where('workmasters.DTP_App_No', '=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->Where('workmasters.DTP_App_No', '<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project = $query->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                               $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                                $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                  $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{

                                  $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }
                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $query->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                        ->whereDate('workmasters.AA_Dt','>=', $endDate);
                    }

                    // if($request->flg == 'f'){ //Red
                    //     $query->where('workmasters.DTP_App_No','=','');
                    // }else if($request->flg == 't'){//Green
                    //     $query->where('workmasters.DTP_App_No','<>','');
                    // }

                    $query->orderBy('workmasters.created_at', 'desc');
                    $project =$query->paginate(10);
                    $initCount++;
                }
        }else{
                    $project = DB::table('workmasters')
                    ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                    ->Where('workmasters.DTP_App_No','<>','')
                    ->orderBy('workmasters.created_at', 'desc')
                    ->paginate(10);

        }

        // A.A.Register New Model Result----------------------------------------------------------------
        if($UseUserPermission){
            //Get All Estimates
            $querywoRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.WO_Amt","workmasters.Wo_Dt","workmasters.DTP_App_No","workmasters.DTP_App_Amt","workmasters.Work_Id");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $querywoRegisterNew = '';
                        $querywoRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.WO_Amt","workmasters.Wo_Dt","workmasters.DTP_App_No","workmasters.DTP_App_Amt","workmasters.Work_Id")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                       ->Where('workmasters.TS_No', '<>','')
                       ->Where('workmasters.WO_No', '=','');
                        $querywoRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWoRegisterNew = $querywoRegisterNew->paginate(10);
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $querywoRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $querywoRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                               $querywoRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $querywoRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $querywoRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{


                                  $querywoRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $querywoRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $querywoRegisterNew->Where('workmasters.TS_No', '<>','')->Where('workmasters.WO_No', '=','');
                    $querywoRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWoRegisterNew =$querywoRegisterNew->paginate(10);
                    $initCount++;
                }
        }else{
                $projectWoRegisterNew =DB::table('estmasters')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.WO_Amt","workmasters.Wo_Dt","workmasters.DTP_App_No","workmasters.DTP_App_Amt","workmasters.Work_Id")->where('estmasters.Est_Id','=',0)
                ->Where('workmasters.TS_No', '<>','')
                ->Where('workmasters.WO_No', '=','')
                ->orderBy('estmasters.created_at', 'desc')
                ->paginate(10);
    }
    $dataWORegisterNew1 = DB::table('workmasters')
    ->join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
    ->select('workmasters.*', 'estmasters.*', 'subdivms.*')
    ->where('workmasters.DTP_App_No', '<>', '')
    ->whereNull('workmasters.WO_No') // Ensure WO_No is empty
    ->get();



   // dd($project);
   //dd($dataWORegisterNew);
   //dd($query);



    $dataMasterAgency= Agency::get();
    // A.A.Register New Model Result----------------------------------------------------------------
    return view('workOrderRegister/wolist',['data'=>$project,'dataWORegisterNew'=>$projectWoRegisterNew , 'dataWORegisterNew1' => $dataWORegisterNew1]);

    } catch (\Throwable $th) {
        throw $th;
    }
}


//Update AA Details

public function deleteAAEstimateAllDtls($id){
    try{

        $deletedWorkmaster = DB::table('workmasters')
            ->where('Work_Id', $id)
            ->delete();


        $SQLestimate = DB::table('estmasters')
            ->where('Work_Id', $id)
            ->update(['AA_No' => '', 'AA_Dt' => null]);


        $estId = DB::table('estmasters')
            ->where('Work_Id', $id)
            ->value('Est_Id');


        if ($estId) {
            $deletedEstrecaps = DB::table('estrecaps')
                ->where('Est_Id', $estId)
                ->delete();
        }


        if ($deletedWorkmaster && $SQLestimate && (!$estId || $deletedEstrecaps)) {
            return redirect('AARegisterList/h')->with('success', 'Record deleted successfully.');
        } else {
            return redirect('AARegisterList/h')->with('error', 'Error in deleting the record.');
        }

    } catch (\Throwable $th) {
        throw $th;
    }
}



//Update TS Details
public function deleteTSEstimateAllDtls(Request $request){
     try {
        //dd($request);
        $eid = $request->eid;
        $wid = $request->wid;
        //Estimate table data remove
        $SQLestimate = DB::table('estmasters')
        ->where('Est_Id', $eid)
        ->update(['TS_No' => '','TS_Dt'=>null,'AA_No'=>'','AA_Dt'=>null]);

        //Workmaster table data remove
        $SQLWorkmaster = DB::table('workmasters')
              ->where('TS_Est_Id', $eid)
              ->update(['TS_Est_Id' => '','TS_No' => '','TS_Dt'=>null,'TS_Amt' => 0.00,'TS_Authority' => '', 'TS_Remark' => '', 'TS_Form_Cd' => '','TS_PDF_Path'=>'','TS_Site_Photo1_Path'=>'','TS_Site_Photo2_Path'=>'','TS_Site_Photo3_Path'=>'']);

                if($SQLWorkmaster){
                    return redirect('TSRegisterList/h')->with('success','Record deleted successfully.');
                }else{
                    return redirect('TSRegisterList/h')->with('success','Error in record.');
                }
    } catch (\Throwable $th) {
        throw $th;
        return redirect('TSRegisterList/h')->with('success',$th);
    }
}

//Workorder delete functionality
public function deleteWOEstimateAllDtls(Request $request){
    try {
        $wid = $request->wid;
        //Workmaster table data remove
       $SQLWorkmaster = DB::table('workmasters')
             ->where('Work_Id', $wid)
             ->update(['WO_No' => '','Wo_Dt'=>null,'WO_Authority' => '','Above_Below'=>'','A_B_Pc'=>0.00,'WO_Amt' => 0.00,'Period'=>0.00,'Perd_Unit'=>'','DLP'=>0.00,'DLP_Unit'=>'','Stip_Comp_Dt' => null, 'DLP_Dt' => null, 'SD_Cash_Pc' => 0.00,'SD_Cash'=>0.00,'SD_Bill_Pc'=>0.00,'SD_Bill'=>0.00,'Spl_SD_Pc'=>0.00,'Spl_SD'=>0.00,'Spl_Conditions'=>'','WO_Form_Cd'=>'','WO_Remark'=>'','WO_PDF_Path'=>'','WO_Site_Photo1_Path'=>'','WO_Site_Photo2_Path'=>'','WO_Site_Photo3_Path'=>'']);
        if($SQLWorkmaster){
            return redirect('WORegisterList/h')->with('success','Record deleted successfully.');
        }else{
            return redirect('WORegisterList/h')->with('success','Error in record.');
        }

   } catch (\Throwable $th) {
       throw $th;
       return redirect('WORegisterList/h')->with('success',$th);
   }
}


public function deleteDTPEstimateAllDtls(Request $request){
    try{
        //WORK MASTER TABLE DATA REMOVE
        $wid = $request->wid;
        $SQLWorkmaster = DB::table('workmasters')
              ->where('Work_Id', $wid)
              ->update(['DTP_App_No' => '','DTP_App_Dt'=>null,'DTP_App_Amt' => 0.00,'DTP_App_Auth' => '', 'DTP_App_Rem' => '', 'Tnd_No' => '','Tnd_Pub_Dt'=>null,'Tnd_Pub_In'=>'','Period_Fr'=>null,'Period_To'=>null,'Tnd_Type'=>'','Tnd_Amt'=>0.00,'EMD'=>0.00,'Con_Class'=>'','Response'=>0.00,'Agency_Id'=>'']);

                if($SQLWorkmaster){
                    return redirect('DTPRegisterList/h')->with('success','Record deleted successfully.');
                }else{
                    return redirect('DTPRegisterList/h')->with('success','Error in record.');
                }
    } catch (\Throwable $th) {
        throw $th;
    }
}


// View Work Order Details
public function ViewWOEstimateForm(Request $request)
    {
        try{
        //Genrate unique ID Genration
        $uniquenumber = uniqid();
        // Logged User Session
        // login user session Data----------------------------
        $divid = auth()->user()->Div_id??0;
        $usercode = auth()->user()->usercode??'';
        $subdivid = auth()->user()->Sub_Div_id??0;
        // login user session Data----------------------------

        $FinanacialYear = DB::table('acyrms')
        ->selectRaw('`Ac_Yr`')
        ->get();

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();
        $rsDiv = json_decode($rsDivisionDtls,true);

        //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();
        $rsTalukas = DB::table('talms')
        ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
        ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
        ->where('divisions.div_id','=',$divid)
        ->get();


        //Scope Master data
        $scopeMasterList = Scopm::get();

        $SQLNewPKID = DB::table('estrecaps')
        ->selectRaw('Sr_No + 1 as Sr_No')
        ->orderBy('Sr_No', 'desc')
        ->where('Est_Id','=',$uniquenumber)
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
        $RecapSrno=$RSNewPKID[0]->Sr_No;
        }else{
        $RecapSrno=1;
        }

        //Get Estimate Details
        // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
        $rsestimate = DB::table('estmasters')
        ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
        ->where('Est_Id','=',$request->id)
        ->first();

        //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

        $FinanacialYear1 = DB::table('acyrms')
        ->selectRaw('Ac_Yr')
        ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
        ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
        ->first();

        //Get Estimate Scope
        $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

        //Get Estimate Recape
        $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

        //Get Estimate
        $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

        //Get Agency Master
        $dataMasterAgency= Agency::get();
        //dd($dataMasterAgency);

        return view('workOrderRegister/view',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster'),);

        } catch (\Throwable $th) {
            throw $th;
        }



    }


    public function ViewWOEstimateForm1(Request $request)
    {
        try{
        //Genrate unique ID Genration
        $uniquenumber = uniqid();
        // Logged User Session
        // login user session Data----------------------------
        $divid = auth()->user()->Div_id??0;
        $usercode = auth()->user()->usercode??'';
        $subdivid = auth()->user()->Sub_Div_id??0;
        // login user session Data----------------------------

        $FinanacialYear = DB::table('acyrms')
        ->selectRaw('`Ac_Yr`')
        ->get();

        // Get Division ID To Division Name
        $rsDivisionDtls = DB::table('divisions')
        ->selectRaw('`div_m`,`div`,`div_id`')
        ->where('div_id','=',$divid)->get();
        $rsDiv = json_decode($rsDivisionDtls,true);

        //Get Selected Divisions All Subdivisions
        $rsSubDivisionDtls = DB::table('subdivms')
        ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
        ->where('Div_Id','=',$divid)->get();
        $rsWorkType = DB::table('worktypes')
        ->selectRaw('`id`,`worktype`')
        ->get();
        $rsTalukas = DB::table('talms')
        ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
        ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
        ->where('divisions.div_id','=',$divid)
        ->get();


        //Scope Master data
        $scopeMasterList = Scopm::get();

        $SQLNewPKID = DB::table('estrecaps')
        ->selectRaw('Sr_No + 1 as Sr_No')
        ->orderBy('Sr_No', 'desc')
        ->where('Est_Id','=',$uniquenumber)
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
        $RecapSrno=$RSNewPKID[0]->Sr_No;
        }else{
        $RecapSrno=1;
        }

        //Get Estimate Details
        // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
        $rsestimate = DB::table('estmasters')
        ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
        ->where('Est_Id','=',$request->id)
        ->first();

        //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

        $FinanacialYear1 = DB::table('acyrms')
        ->selectRaw('Ac_Yr')
        ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
        ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
        ->first();

        //Get Estimate Scope
        $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

        //Get Estimate Recape
        $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

        //Get Estimate
        $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

        $progressReports = DB::table('progressreports')
        ->where('work_id', '=', $rsworkmaster->Work_Id)
        ->orderBy('pre_report_dt', 'asc')
        ->first();

        $billData = DB::table('rabills')
    ->where('work_id', '=', $rsworkmaster->Work_Id)
    ->orderBy('bill_date', 'asc')
    ->get();


        //Get Agency Master
        $dataMasterAgency= Agency::get();
        //dd($dataMasterAgency);

        return view('workOrderRegister/view1',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,  'progressReports' => $progressReports, 'prebudgety'=>$FinanacialYear1->Ac_Yr,  'rabills' => $billData, 'dataMasterAgency'=>$dataMasterAgency ],compact('rsworkmaster'),);

        } catch (\Throwable $th) {
            throw $th;
        }

    }

public function SearchWorkOrderEstimate(Request $request){
        //Login Session Details
        $uid = auth()->user()->id??0;
        $usercode = auth()->user()->usercode??'';
        $divid = auth()->user()->Div_id??0;
        $subdivid = auth()->user()->Sub_Div_id??0;
        //Get User Permission

        //Get Division Name
        //$divisionName = Division::select()

        $DSFoundhd = DB::table('userperms')
        ->select('F_H_CODE','Sub_Div_Id','Work_Id')
        ->where('User_Id', '=',$uid)
        ->where('Removed','=',1)
        ->get();

        $UseUserPermission = json_decode($DSFoundhd);
        $FinalExecuteQuery = '';
        $rsFilterResult = '';

        if($UseUserPermission){
            //Get All Estimates
            $queryDTPRegisterNew = DB::table('estmasters')
            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                    $initCount = 0;
                    foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                        $rsFound->F_H_CODE;
                        $rsFound->Sub_Div_Id;
                        $rsFound->Work_Id;
                        $foundcount = strlen($rsFound->F_H_CODE);
                    if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                        //echo "Step1"; exit;
                        $queryDTPRegisterNew = '';
                        $queryDTPRegisterNew = DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                        ->Where('workmasters.DTP_App_No', '<>','');
                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew = $queryDTPRegisterNew->get();
                        break;
                    }else{
                       // echo "Step2"; exit;
                        // If work id
                        if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                            //Calculate Count
                            if($initCount == 0){

                               $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                               $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }else{

                               $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                            }
                        }else{

                            if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                //Calculate Count
                                if($initCount == 0){

                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                  $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }else{


                                  $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                }

                            }
                            if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                //Calculate Count

                                $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                            }

                        }

                    }
                    $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                    if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                        $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                    }
                    if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                        $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                    }
                    if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                        $queryDTPRegisterNew->where([['workmasters.DTP_App_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                    }
                    if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                        $startDate = $request->txtsearchFromPreDate;
                        $endDate = $request->txtsearchToPreDate;
                        $queryDTPRegisterNew->whereDate('workmasters.DTP_App_Dt','<=', $startDate)
                        ->whereDate('workmasters.DTP_App_Dt','>=', $endDate);
                    }

                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectWORegisterNew =$queryDTPRegisterNew->get();
                    $initCount++;
                }
        }else{
                $projectWORegisterNew =DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                    ->Where('workmasters.DTP_App_No', '<>','')
                    ->orderBy('estmasters.created_at', 'desc')
                    ->get();

        }

        return response()->json(array('msg'=> $projectWORegisterNew), 200);
    }




    public function SearchAjaxDTPRegistersEstimate(Request $request){
    //Login Session Details
    $uid = auth()->user()->id;
    $usercode = auth()->user()->usercode;
    $divid = auth()->user()->Div_id;
    $subdivid = auth()->user()->Sub_Div_id;
    //Get User Permission

    //Get Division Name
    //$divisionName = Division::select()

    $DSFoundhd = DB::table('userperms')
    ->select('F_H_CODE','Sub_Div_Id','Work_Id')
    ->where('User_Id', '=',$uid)
    ->where('Removed','=',1)
    ->get();

    $UseUserPermission = json_decode($DSFoundhd);
    $FinalExecuteQuery = '';
    $rsFilterResult = '';
    // DTP Register New Model Result----------------------------------------------------------------
     if($UseUserPermission){
        //Get All Estimates
        $queryDTPRegisterNew = DB::table('estmasters')
        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No");

                $initCount = 0;
                foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                    $rsFound->F_H_CODE;
                    $rsFound->Sub_Div_Id;
                    $rsFound->Work_Id;
                    $foundcount = strlen($rsFound->F_H_CODE);

                if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){

                    $queryDTPRegisterNew = '';
                    $queryDTPRegisterNew = DB::table('estmasters')
                    ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                    ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                    ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                   ->Where('workmasters.TS_No', '<>','')
                   ->Where('workmasters.DTP_App_No1', '=','');
                    $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                    $projectDTPRegisterNew = $queryDTPRegisterNew->get();
                    break;
                }else{
                   dd("Step1");
                    // If work id
                    if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                        //Calculate Count
                        if($initCount == 0){

                           $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                           $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                        }else{

                           $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                        }
                    }else{
                        dd("Step2");
                        if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                            //Calculate Count
                            if($initCount == 0){

                              $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                              $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                            }else{


                              $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                            }

                        }

                        if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                            //Calculate Count

                            $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                        }

                    }

                }
                dd("Step3");
                $queryDTPRegisterNew->Where('workmasters.TS_No', '<>','')->Where('workmasters.DTP_App_No', '=','');
                if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                    $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                }
                if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                    $queryDTPRegisterNew->where("workmasters.TS_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                }
                if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                    $queryDTPRegisterNew->where([['workmasters.TS_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.TS_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                }
                if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                    $startDate = $request->txtsearchFromPreDate;
                    $endDate = $request->txtsearchToPreDate;
                    $queryDTPRegisterNew->whereDate('workmasters.TS_Dt','<=', $startDate)
                    ->whereDate('workmasters.TS_Dt','>=', $endDate);
                }

                $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                $projectDTPRegisterNew =$queryDTPRegisterNew->get();
                $initCount++;
            }
    }else{
           // dd("Step4");
            $projectDTPRegisterNew =DB::table('estmasters')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->leftJoin('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No")->where('estmasters.Est_Id','=',0)
                ->Where([['workmasters.TS_No', '<>',''],['workmasters.DTP_App_No', '=','']])
                ->orderBy('estmasters.created_at', 'desc')
                ->get();
            }
            return response()->json(array('msg'=> $projectDTPRegisterNew), 200);
}


//RA Bill Listing
public function createViewRaBills(Request $request)
{
    try {
        //Login Session Details
        $uid = auth()->user()->id??0;
        $usercode = auth()->user()->usercode??'';
        $divid = auth()->user()->Div_id??0;
        $subdivid = auth()->user()->Sub_Div_id??0;
        //Get User Permission

        //Get Division Name
       // $divisionName = Division::select()

        $DSFoundhd = DB::table('userperms')
        ->select('F_H_CODE','Sub_Div_Id','Work_Id')
        ->where('User_Id', '=',$uid)
        ->where('Removed','=',1)
        ->get();

        $UseUserPermission = json_decode($DSFoundhd);
        $FinalExecuteQuery = '';
        $rsFilterResult = '';

        if($UseUserPermission){
                //Get All Estimates
                $query = DB::table('workmasters')
                ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                        $initCount = 0;
                        foreach(json_decode($DSFoundhd) as $rsFound){
                            $rsFound->F_H_CODE;
                            $rsFound->Sub_Div_Id;
                            $rsFound->Work_Id;
                            $foundcount = strlen($rsFound->F_H_CODE);

                        //echo "Step0"; exit;
                        if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                            $query = DB::table('workmasters')
                            ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                            ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                            ->Where('workmasters.WO_No', '<>','');

                           // dd($request);
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                ->whereDate('workmasters.AA_Dt','>=', $endDate);
                            }

                            // if($request->flg == 'f'){ //Red
                            //     $query->Where('workmasters.DTP_App_No', '=','');
                            // }else if($request->flg == 't'){//Green
                            //     $query->Where('workmasters.DTP_App_No', '<>','');
                            // }

                            $query->orderBy('workmasters.created_at', 'desc');
                            $project = $query->paginate(10);
                            break;
                        }else{
                           // echo "Step2"; exit;
                            // If work id
                            if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                //Calculate Count
                                if($initCount == 0){

                                   $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                   $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }else{

                                    $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }
                            }else{

                                if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                    //Calculate Count
                                    if($initCount == 0){

                                      $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                      $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }else{

                                      $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }
                                }
                                if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                    //Calculate Count

                                    $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                }

                            }

                        }
                        $query->Where('workmasters.WO_No', '<>','');
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                            ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->where('workmasters.DTP_App_No','=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->where('workmasters.DTP_App_No','<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project =$query->paginate(10);
                        $initCount++;
                    }
            }else{
                        $project = DB::table('workmasters')
                        ->leftJoin('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                        ->Where('workmasters.WO_No', '<>','')
                        ->orderBy('workmasters.created_at', 'desc')
                        ->paginate(10);

            }

            // A.A.Register New Model Result----------------------------------------------------------------
            if($UseUserPermission){
                //Get All Estimates
                $queryDTPRegisterNew = DB::table('estmasters')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                        $initCount = 0;
                        foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                            $rsFound->F_H_CODE;
                            $rsFound->Sub_Div_Id;
                            $rsFound->Work_Id;
                            $foundcount = strlen($rsFound->F_H_CODE);
                        if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                            //echo "Step1"; exit;
                            $queryDTPRegisterNew = '';
                            $queryDTPRegisterNew = DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                            // ->Where('workmasters.WO_No', '=','');
                            ->Where('workmasters.DTP_App_No', '<>','');
                            $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                            $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                            break;
                        }else{
                           // echo "Step2"; exit;
                            // If work id
                            if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                //Calculate Count
                                if($initCount == 0){
                                //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                   $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                   $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                }else{
                                //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                   $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                }
                            }else{

                                if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                    //Calculate Count
                                    if($initCount == 0){
                                      //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                      $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                      $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }else{

                                    //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                      $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }

                                }
                                if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                    //Calculate Count
                                    // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                    $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                }

                            }

                        }
                        $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                        $initCount++;
                    }
            }else{
                    $projectWORegisterNew =DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                        // ->Where('workmasters.WO_No', '=','')
                        ->Where('workmasters.DTP_App_No', '<>','')
                        ->orderBy('estmasters.created_at', 'desc')
                        ->paginate(10);

            }

        //dd($projectWORegisterNew);
        $dataMasterAgency= Agency::get();
        // A.A.Register New Model Result----------------------------------------------------------------
        return view('rabills/list',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

        } catch (\Throwable $th) {
            throw $th;
        }
}

public function EditRABillS(Request $request)
{
    try{
            //Genrate unique ID Genration
            $uniquenumber = uniqid();
            // Logged User Session
            // login user session Data----------------------------
            $divid = auth()->user()->Div_id??0;
            $usercode = auth()->user()->usercode??'';
            $subdivid = auth()->user()->Sub_Div_id??0;
            // login user session Data----------------------------

            $FinanacialYear = DB::table('acyrms')
            ->selectRaw('`Ac_Yr`')
            ->get();

            // Get Division ID To Division Name
            $rsDivisionDtls = DB::table('divisions')
            ->selectRaw('`div_m`,`div`,`div_id`')
            ->where('div_id','=',$divid)->get();
            $rsDiv = json_decode($rsDivisionDtls,true);

            //Get Selected Divisions All Subdivisions
            $rsSubDivisionDtls = DB::table('subdivms')
            ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
            ->where('Div_Id','=',$divid)->get();
            $rsWorkType = DB::table('worktypes')
            ->selectRaw('`id`,`worktype`')
            ->get();
            $rsTalukas = DB::table('talms')
            ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
            ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
            ->where('divisions.div_id','=',$divid)
            ->get();



            //Scope Master data
            $scopeMasterList = Scopm::get();

            $SQLNewPKID = DB::table('estrecaps')
            ->selectRaw('Sr_No + 1 as Sr_No')
            ->orderBy('Sr_No', 'desc')
            ->where('Est_Id','=',$uniquenumber)
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
            $RecapSrno=$RSNewPKID[0]->Sr_No;
            }else{
            $RecapSrno=1;
            }

            //Get Estimate Details
            // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
            $rsestimate = DB::table('estmasters')
            ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
            ->where('Est_Id','=',$request->id)
            ->first();

            //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

            $FinanacialYear1 = DB::table('acyrms')
            ->selectRaw('Ac_Yr')
            ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
            ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
            ->first();

            //Get Estimate Scope
            $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

            //Get Estimate Recape
            $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

            //Get Estimate
            $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

            //Get Agency Master
            $dataMasterAgency= Agency::get();
            //dd($dataMasterAgency);

            return view('rabills/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster'),);

    } catch (\Throwable $th) {
        throw $th;
    }

}


//New RA Bill Genrate
public function EditRABillForm(Request $request)
{
    try{

    //Genrate unique ID Genration
    $uniquenumber = uniqid();
    // Logged User Session
    // login user session Data----------------------------
    $divid = auth()->user()->Div_id??0;
    $usercode = auth()->user()->usercode??'';
    $subdivid = auth()->user()->Sub_Div_id??0;

    // login user session Data----------------------------

    $FinanacialYear = DB::table('acyrms')
    ->selectRaw('`Ac_Yr`')
    ->get();

    // Get Division ID To Division Name
    $rsDivisionDtls = DB::table('divisions')
    ->selectRaw('`div_m`,`div`,`div_id`')
    ->where('div_id','=',$divid)->get();
    $rsDiv = json_decode($rsDivisionDtls,true);

    //Get Selected Divisions All Subdivisions
    $rsSubDivisionDtls = DB::table('subdivms')
    ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
    ->where('Div_Id','=',$divid)->get();
    $rsWorkType = DB::table('worktypes')
    ->selectRaw('`id`,`worktype`')
    ->get();
    $rsTalukas = DB::table('talms')
    ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
    ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
    ->where('divisions.div_id','=',$divid)
    ->get();


    //Scope Master data
    $scopeMasterList = Scopm::get();

    $SQLNewPKID = DB::table('estrecaps')
    ->selectRaw('Sr_No + 1 as Sr_No')
    ->orderBy('Sr_No', 'desc')
    ->where('Est_Id','=',$uniquenumber)
    ->limit(1)
    ->get();
    $RSNewPKID = json_decode($SQLNewPKID);
    if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
    $RecapSrno=$RSNewPKID[0]->Sr_No;
    }else{
    $RecapSrno=1;
    }

    //Get Estimate Details
    // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
    $rsestimate = DB::table('estmasters')
    ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
    ->where('Est_Id','=',$request->id)
    ->first();

    //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

    $FinanacialYear1 = DB::table('acyrms')
    ->selectRaw('Ac_Yr')
    ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
    ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
    ->first();

    //Get Estimate Scope
    $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

    //Get Estimate Recape
    $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

    //Get Estimate
    $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

    //Get Agency Master
    $dataMasterAgency= Agency::get();
    $rsWorkidRabillList=null;
    $rsWorkidLastBillGenrated=null;
    $rsWorkidLastBill=null;

    if($rsworkmaster->Work_Id){

        //RA Bill List - Work ID Wise RA Bill List
        $rsWorkidRabillList= Rabill::where('work_id','=',$rsworkmaster->Work_Id)->get();
        //GET RABill Last Record
        $rsWorkidLastBillGenrated= Rabill::where('work_id','=',$rsworkmaster->Work_Id)->orderBy('bill_id', 'desc')->first();
        $recordPresent = Rabill::where('work_id','=',$rsworkmaster->Work_Id)->count();
        if($rsWorkidLastBillGenrated){
            $maxBillno = $rsWorkidLastBillGenrated->bill_id+1;
        }else{
            $maxBillno = 1;
        }


        // Selected Work Final RA Bill Genrated Query
        $IsWorkComplete= Rabill::where('work_id','=',$rsworkmaster->Work_Id)->where('final_bill','=','on')->orderBy('bill_id', 'desc')->first();
        if($IsWorkComplete){
            $isfinalbill = $IsWorkComplete->final_bill;
        }else{
            $isfinalbill = 'Off';
        }
        $rsWorkidLastBill= Rabill::where('work_id','=',$rsworkmaster->Work_Id)->orderBy('bill_id', 'desc')->first();

     }

    return view('rabills/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster','rsWorkidRabillList','rsWorkidLastBillGenrated','rsWorkidLastBill','maxBillno','isfinalbill'));

    } catch (\Throwable $th) {
        throw $th;
    }
}

//RA Bill Listing
public function createViewProgressReports(Request $request)
{
    try {
        //Login Session Details
        $uid = auth()->user()->id??0;
        $usercode = auth()->user()->usercode??'';
        $divid = auth()->user()->Div_id??0;
        $subdivid = auth()->user()->Sub_Div_id??0;
        //Get User Permission

        //Get Division Name
       // $divisionName = Division::select()

        $DSFoundhd = DB::table('userperms')
        ->select('F_H_CODE','Sub_Div_Id','Work_Id')
        ->where('User_Id', '=',$uid)
        ->where('Removed','=',1)
        ->get();

        $UseUserPermission = json_decode($DSFoundhd);
        $FinalExecuteQuery = '';
        $rsFilterResult = '';

        if($UseUserPermission){
                //Get All Estimates
                $query = DB::table('workmasters')
                ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id");
                        $initCount = 0;
                        foreach(json_decode($DSFoundhd) as $rsFound){
                            $rsFound->F_H_CODE;
                            $rsFound->Sub_Div_Id;
                            $rsFound->Work_Id;
                            $foundcount = strlen($rsFound->F_H_CODE);

                        //echo "Step0"; exit;
                        if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                            $query = DB::table('workmasters')
                            ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")
                            ->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid)
                            ->Where('workmasters.WO_No', '<>','');

                           // dd($request);
                            if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                                $query->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                            }
                            if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                                $query->where("workmasters.AA_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                            }
                            if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                                $query->where([['workmasters.AA_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.AA_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                            }
                            if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                                $startDate = $request->txtsearchFromPreDate;
                                $endDate = $request->txtsearchToPreDate;
                                $query->whereDate('workmasters.AA_Dt','<=', $startDate)
                                ->whereDate('workmasters.AA_Dt','>=', $endDate);
                            }

                            // if($request->flg == 'f'){ //Red
                            //     $query->Where('workmasters.DTP_App_No', '=','');
                            // }else if($request->flg == 't'){//Green
                            //     $query->Where('workmasters.DTP_App_No', '<>','');
                            // }

                            $query->orderBy('workmasters.created_at', 'desc');
                            $project = $query->paginate(10);
                            break;
                        }else{
                           // echo "Step2"; exit;
                            // If work id
                            if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                //Calculate Count
                                if($initCount == 0){

                                   $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                   $query->orWhere('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }else{

                                    $query->where('workmasters.Work_Id','=',$rsFound->Work_Id);
                                }
                            }else{

                                if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                    //Calculate Count
                                    if($initCount == 0){

                                      $query->where(DB::raw('left(`workmasters`.`Est_Id`,3)'),'=',$divid);
                                      $query->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }else{

                                      $query->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }
                                }
                                if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                    //Calculate Count
                                    $query->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                }

                            }

                        }
                        $query->Where('workmasters.WO_No', '<>','');
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $query->where("workmasters.WO_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $query->where([['workmasters.WO_Amt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.WO_Amt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $query->whereDate('workmasters.Wo_Dt','<=', $startDate)
                            ->whereDate('workmasters.Wo_Dt','>=', $endDate);
                        }

                        // if($request->flg == 'f'){ //Red
                        //     $query->where('workmasters.DTP_App_No','=','');
                        // }else if($request->flg == 't'){//Green
                        //     $query->where('workmasters.DTP_App_No','<>','');
                        // }

                        $query->orderBy('workmasters.created_at', 'desc');
                        $project =$query->paginate(10);
                        $initCount++;
                    }
            }else{
                        $project = DB::table('workmasters')
                        ->Join('estmasters', 'estmasters.Est_Id', '=', 'workmasters.Est_Id')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->select("workmasters.Work_Id", "workmasters.Budget_Yr", "workmasters.Budget_I_No", "workmasters.Budget_P_No", "workmasters.F_H_Code", "workmasters.Est_Id", "workmasters.Sub_Div_Id", "subdivms.Sub_Div", "subdivms.Sub_Div_M", "workmasters.Tal_Id", "workmasters.Tal", "workmasters.Tal_M", "workmasters.Work_Nm", "workmasters.Work_Nm_M", "workmasters.Work_Type", "workmasters.AA_No", "workmasters.AA_Dt", "workmasters.AA_Amt", "workmasters.AA_Authority", "workmasters.AA_Remark", "workmasters.AA_PDF_Path","workmasters.TS_Est_Id", "workmasters.TS_No", "workmasters.TS_Dt", "workmasters.TS_Amt", "workmasters.TS_Authority", "workmasters.TS_Remark", "workmasters.TS_Form_Cd", "workmasters.TS_PDF_Path", "workmasters.TS_Site_Photo1_Path", "workmasters.TS_Site_Photo2_Path", "workmasters.TS_Site_Photo3_Path", "workmasters.DTP_App_No", "workmasters.DTP_App_Dt", "workmasters.DTP_App_Amt", "workmasters.DTP_App_Auth","workmasters.DTP_App_Rem", "workmasters.Tnd_No", "workmasters.Tnd_Pub_Dt", "workmasters.Tnd_Pub_In", "workmasters.Period_Fr", "workmasters.Period_To", "workmasters.Tnd_Type", "workmasters.Tnd_Amt", "workmasters.EMD", "workmasters.Con_Class", "workmasters.Response", "workmasters.Agency_Id", "workmasters.Agency_Nm", "workmasters.Agency_Nm_M", "workmasters.Tnd_Remark", "workmasters.WO_No", "workmasters.Wo_Dt", "workmasters.WO_Authority", "workmasters.Above_Below", "workmasters.A_B_Pc", "workmasters.WO_Amt", "workmasters.Period", "workmasters.Perd_Unit", "workmasters.DLP", "workmasters.DLP_Unit", "workmasters.Stip_Comp_Dt", "workmasters.DLP_Dt", "workmasters.SD_Cash_Pc", "workmasters.SD_Cash", "workmasters.SD_Bill_Pc", "workmasters.SD_Bill", "workmasters.Spl_SD_Pc", "workmasters.Spl_SD", "workmasters.Spl_Conditions", "workmasters.WO_Form_Cd", "workmasters.WO_Remark", "workmasters.WO_PDF_Path", "workmasters.WO_Site_Photo1_Path", "workmasters.WO_Site_Photo2_Path", "workmasters.WO_Site_Photo3_Path", "workmasters.Act_Comp_Dt", "workmasters.No_Of_Bills", "workmasters.Last_Bill_Dt", "workmasters.UptoDate_Paid", "workmasters.Oth_Exp", "workmasters.Tot_Exp", "workmasters.Fund_Available", "workmasters.Fund_Reqd", "workmasters.Last_Status", "workmasters.Last_Status_Dt", "workmasters.Last_Status_By", "workmasters.Last_Site_Photo1_Path", "workmasters.Last_Site_Photo2_Path", "workmasters.Last_Site_Photo3_Path", "workmasters.PB_Id", "workmasters.AB_Id", "workmasters.SO_Id")->where('workmasters.Est_Id','=',0) ->orWhereNull('workmasters.Est_Id')
                        ->Where('workmasters.WO_No', '<>','')
                        ->orderBy('workmasters.created_at', 'desc')
                        ->paginate(10);

            }

            // A.A.Register New Model Result----------------------------------------------------------------
            if($UseUserPermission){
                //Get All Estimates
                $queryDTPRegisterNew = DB::table('estmasters')
                ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt");

                        $initCount = 0;
                        foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
                            $rsFound->F_H_CODE;
                            $rsFound->Sub_Div_Id;
                            $rsFound->Work_Id;
                            $foundcount = strlen($rsFound->F_H_CODE);
                        if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
                            //echo "Step1"; exit;
                            $queryDTPRegisterNew = '';
                            $queryDTPRegisterNew = DB::table('estmasters')
                            ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                            ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                            ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid)
                            // ->Where('workmasters.WO_No', '=','');
                            ->Where('workmasters.DTP_App_No', '<>','');
                            $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                            $projectWORegisterNew = $queryDTPRegisterNew->paginate(10);
                            break;
                        }else{
                           // echo "Step2"; exit;
                            // If work id
                            if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
                                //Calculate Count
                                if($initCount == 0){
                                //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                   $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                   $queryDTPRegisterNew->orWhere('estmasters.Work_Id','=',$rsFound->Work_Id);
                                }else{
                                //    $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                   $queryDTPRegisterNew->where('estmasters.Work_Id','=',$rsFound->Work_Id);
                                }
                            }else{

                                if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
                                    //Calculate Count
                                    if($initCount == 0){
                                      //$queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                      $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`Est_Id`,3)'),'=',$divid);
                                      $queryDTPRegisterNew->where(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }else{

                                    //   $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                      $queryDTPRegisterNew->orWhere(DB::raw('left(`estmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount));
                                    }

                                }
                                if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
                                    //Calculate Count
                                    // $queryDTPRegisterNew->Where('workmasters.WO_No', '=','');

                                    $queryDTPRegisterNew->Where('estmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id);
                                }

                            }

                        }
                        $queryDTPRegisterNew->Where('workmasters.DTP_App_No', '<>','');
                        if($request->is_name_work && !empty($request->is_name_work) && isset($request->txtsearchnameofwork)){
                            $queryDTPRegisterNew->where("workmasters.Work_Nm", 'like', '%'.$request->txtsearchnameofwork.'%');
                        }
                        if($request->is_estimate_no && !empty($request->is_estimate_no) && isset($request->txtsearchEstimateno)){
                            $queryDTPRegisterNew->where("workmasters.DTP_App_No", 'like', '%'.$request->txtsearchEstimateno.'%');
                        }
                        if($request->is_estimate_amount && !empty($request->is_estimate_amount) && isset($request->txtsearchEstimateAmtFrom) && isset($request->txtsearchEstimateAmtTo)){
                            $queryDTPRegisterNew->where([['workmasters.DTP_App_Dt', '>=',trim($request->txtsearchEstimateAmtFrom)],['workmasters.DTP_App_Dt','<=',trim($request->txtsearchEstimateAmtTo)]]);
                        }
                        if($request->is_prepaire_date == 'on' && !empty($request->is_prepaire_date) && isset($request->txtsearchFromPreDate) && isset($request->txtsearchToPreDate)){
                            $startDate = $request->txtsearchFromPreDate;
                            $endDate = $request->txtsearchToPreDate;
                            $queryDTPRegisterNew->whereDate('workmasters.AA_Dt','<=', $startDate)
                            ->whereDate('workmasters.AA_Dt','>=', $endDate);
                        }

                        $queryDTPRegisterNew->orderBy('estmasters.created_at', 'desc');
                        $projectWORegisterNew =$queryDTPRegisterNew->paginate(10);
                        $initCount++;
                    }
            }else{
                    $projectWORegisterNew =DB::table('estmasters')
                        ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'estmasters.Sub_Div_Id')
                        ->Join('workmasters', 'workmasters.Est_Id', '=', 'estmasters.Est_Id')
                        ->select("estmasters.Est_Id","estmasters.Est_No","estmasters.Work_Nm","estmasters.Work_Nm","estmasters.Work_Nm_M","subdivms.Sub_Div","subdivms.Sub_Div_M","estmasters.Tot_Amt","estmasters.F_H_Code","estmasters.F_H_Code","estmasters.Work_Type","estmasters.TS_No","estmasters.Date_Prep","workmasters.TS_Amt","workmasters.WO_No","workmasters.Wo_Dt","workmasters.WO_Amt","workmasters.Work_Id","workmasters.DTP_App_No","workmasters.DTP_App_Dt","workmasters.DTP_App_Amt")->where('estmasters.Est_Id','=',0)
                        // ->Where('workmasters.WO_No', '=','')
                        ->Where('workmasters.DTP_App_No', '<>','')
                        ->orderBy('estmasters.created_at', 'desc')
                        ->paginate(10);

            }

        //dd($projectWORegisterNew);
        $dataMasterAgency= Agency::get();
        // A.A.Register New Model Result----------------------------------------------------------------
        return view('progressreports/list',['data'=>$project,'dataWORegisterNew'=>$projectWORegisterNew]);

        } catch (\Throwable $th) {
            throw $th;
        }
}


//New Progress Report Genrate
public function EditProgressReportForm(Request $request)
{
    try{
    //Genrate unique ID Genration
    $uniquenumber = uniqid();
    // Logged User Session
    // login user session Data----------------------------
    $divid = auth()->user()->Div_id??0;
    $usercode = auth()->user()->usercode??'';
    $subdivid = auth()->user()->Sub_Div_id??0;
    // login user session Data----------------------------

    $FinanacialYear = DB::table('acyrms')
    ->selectRaw('`Ac_Yr`')
    ->get();

    // Get Division ID To Division Name
    $rsDivisionDtls = DB::table('divisions')
    ->selectRaw('`div_m`,`div`,`div_id`')
    ->where('div_id','=',$divid)->get();
    $rsDiv = json_decode($rsDivisionDtls,true);

    //Get Selected Divisions All Subdivisions
    $rsSubDivisionDtls = DB::table('subdivms')
    ->selectRaw('`Sub_Div`,`Sub_Div_M`,`Sub_Div_Id`')
    ->where('Div_Id','=',$divid)->get();
    $rsWorkType = DB::table('worktypes')
    ->selectRaw('`id`,`worktype`')
    ->get();
    $rsTalukas = DB::table('talms')
    ->selectRaw('`talms`.`Tal_Id`,`talms`.`Tal_M`,`talms`.`Tal`')
    ->rightJoin('divisions', 'talms.Dist_Id', '=', 'divisions.dist_id')
    ->where('divisions.div_id','=',$divid)
    ->get();


    //Scope Master data
    $scopeMasterList = Scopm::get();

    $SQLNewPKID = DB::table('estrecaps')
    ->selectRaw('Sr_No + 1 as Sr_No')
    ->orderBy('Sr_No', 'desc')
    ->where('Est_Id','=',$uniquenumber)
    ->limit(1)
    ->get();
    $RSNewPKID = json_decode($SQLNewPKID);
    if(isset($RSNewPKID[0]->Sr_No) && !empty($RSNewPKID[0]->Sr_No)){
    $RecapSrno=$RSNewPKID[0]->Sr_No;
    }else{
    $RecapSrno=1;
    }

    //Get Estimate Details
    // $rsestimate = Estmaster::where('Est_Id','=',$request->id)->get();
    $rsestimate = DB::table('estmasters')
    ->select('Est_Id', 'Est_No', 'Sub_Div_Id', 'Sub_Div', 'Tal_Id', 'Tal', 'Work_Nm', 'Work_Nm_M', 'Work_Type', 'Tot_Amt', 'E_Prep_By', 'E_Chk_By', 'Date_Prep', 'Work_Id', 'AA_No', 'AA_Dt', 'TS_No', 'TS_Dt', 'Need', 'Remarks', 'Est_PDF_Path', 'Est_XLS_Path', 'created_at', 'updated_at', 'is_delete', 'F_H_Code', 'AA_TS',DB::raw("DATE_FORMAT(Date_Prep, '%Y') as datepYear"))
    ->where('Est_Id','=',$request->id)
    ->first();

    //$Date_Prep = Carbon::parse($rsestimate->Date_Prep)->toDateString();

    $FinanacialYear1 = DB::table('acyrms')
    ->selectRaw('Ac_Yr')
    ->whereDate('Yr_St','<=',$rsestimate->Date_Prep)
    ->whereDate('Yr_End','>=',$rsestimate->Date_Prep)
    ->first();

    //Get Estimate Scope
    $rsscope = Estscope::where('Est_Id','=',$request->id)->get();

    //Get Estimate Recape
    $rsrecape =Estrecap::where('Est_Id','=',$request->id)->get();

    //Get Estimate
    $rsworkmaster=Workmaster::where('Est_Id','=',$request->id)->first();

    //Get Agency Master
    $dataMasterAgency= Agency::get();

    //RA Bill List - Work ID Wise RA Bill List
    $rsWorkidProgressReportList= Progressreport::where('work_id','=',$rsworkmaster->Work_Id)->get();


    // Auto Increament Progress Report ID
    $SQLNewPKID = DB::table('progressreports')
    ->selectRaw('per_rep_id + 1 as per_rep_id')
    ->orderBy('per_rep_id', 'desc')
    ->where('work_id','=',$rsworkmaster->Work_Id)
    ->limit(1)
    ->get();

    $RSNewPKID = json_decode($SQLNewPKID);
    if(isset($RSNewPKID[0]->per_rep_id) && !empty($RSNewPKID[0]->per_rep_id)){
        $progressSrno=$RSNewPKID[0]->per_rep_id;
    }else{
        $progressSrno=1;
    }

    $rsReportedByUserList= DB::table('users')
    ->whereIn('usertypes',['EE','JE','DYE'])
    ->when($divid, function ($query) use ($divid) {
        return $query->where('Div_id','=',$divid);
    })
    ->when($subdivid, function ($query) use ($subdivid) {
        return $query->where('Sub_Div_id','=',$subdivid);
    })
    ->get();

    //Reported By Users
    $rsWorkidProgressReportList = DB::table('progressreports')
    ->select(
        'progressreports.work_id',
        'progressreports.per_rep_id',
        'progressreports.pr_rep_no',
        'progressreports.per_rep_by',
        'progressreports.pre_report_dt',
        'progressreports.photo1',
        'progressreports.photo2',
        'progressreports.photo3',
        'progressreports.document1',
        'progressreports.document2',
        'progressreports.document3',
        'progressreports.status',
        'users.name',
        'progressreports.is_final_report',
        'workmasters.is_work_complete' // Add the is_work_complete column from the workmasters table
    )
    ->leftJoin('users', 'users.id', '=', 'progressreports.per_rep_by')
    ->leftJoin('workmasters', 'workmasters.Work_Id', '=', 'progressreports.work_id') // Join with workmasters table
    ->where('progressreports.work_id', '=', $rsworkmaster->Work_Id)
    ->get();


     //Last Progress Report
     $rsWorkidLastProgressReportId= ProgressReport::where('work_id','=',$rsworkmaster->Work_Id)->orderby('per_rep_id','desc')->first();
     $LastProgressReportID = 0 ;
     if($rsWorkidLastProgressReportId){
        $LastProgressReportID = $rsWorkidLastProgressReportId->per_rep_id;
     }


     // Selected Progress Report Final
        $IsWorkComplete= ProgressReport::where('work_id','=',$rsworkmaster->Work_Id)->where('is_final_report','=','on')->orderBy('per_rep_id', 'desc')->first();
        if($IsWorkComplete){
            $isfinalreport = $IsWorkComplete->is_final_report;
        }else{
            $isfinalreport = 'Off';
        }


    return view('progressreports/add',['SinDivision'=>$rsDiv,'rssubdivisions'=>$rsSubDivisionDtls,'rswtype'=>$rsWorkType,'rstalukha'=>$rsTalukas,'uniqueno'=>$uniquenumber,'mscopelist'=>$scopeMasterList,'RecapSrno'=>$RecapSrno,'esthd'=>$rsestimate,'editrsscope'=>$rsscope,'editrsrecape'=>$rsrecape,'finyearlist'=>$FinanacialYear,'prebudgety'=>$FinanacialYear1->Ac_Yr,'dataMasterAgency'=>$dataMasterAgency],compact('rsworkmaster','rsWorkidProgressReportList','rsReportedByUserList','LastProgressReportID','isfinalreport','progressSrno'));

    } catch (\Throwable $th) {
        throw $th;
    }

}


public function gps2Num($coordPart){
    $parts = explode('/', $coordPart);
    if(count($parts) <= 0)
    return 0;
    if(count($parts) == 1)
    return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}


/**
 * get_image_location
 * Returns an array of latitude and longitude from the Image file
*/
public function ajaxgetTagImageChecker($imageRealPath){
    $image = $imageRealPath;
    $exif = exif_read_data($image, 0, true);
    if($exif && isset($exif['GPS'])){
        $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef'];
        $GPSLatitude    = $exif['GPS']['GPSLatitude'];
        $GPSLongitudeRef= $exif['GPS']['GPSLongitudeRef'];
        $GPSLongitude   = $exif['GPS']['GPSLongitude'];

        $lat_degrees = count($GPSLatitude) > 0 ? $this->gps2Num($GPSLatitude[0]) : 0;
        $lat_minutes = count($GPSLatitude) > 1 ? $this->gps2Num($GPSLatitude[1]) : 0;
        $lat_seconds = count($GPSLatitude) > 2 ? $this->gps2Num($GPSLatitude[2]) : 0;

        $lon_degrees = count($GPSLongitude) > 0 ? $this->gps2Num($GPSLongitude[0]) : 0;
        $lon_minutes = count($GPSLongitude) > 1 ? $this->gps2Num($GPSLongitude[1]) : 0;
        $lon_seconds = count($GPSLongitude) > 2 ? $this->gps2Num($GPSLongitude[2]) : 0;

        $lat_direction = ($GPSLatitudeRef == 'W' or $GPSLatitudeRef == 'S') ? -1 : 1;
        $lon_direction = ($GPSLongitudeRef == 'W' or $GPSLongitudeRef == 'S') ? -1 : 1;

        $latitude = $lat_direction * ($lat_degrees + ($lat_minutes / 60) + ($lat_seconds / (60*60)));
        $longitude = $lon_direction * ($lon_degrees + ($lon_minutes / 60) + ($lon_seconds / (60*60)));

        return array('latitude'=>$latitude, 'longitude'=>$longitude);
    }else{
        return false;
    }
}


// Progress Report Number Dulication Check
public function ajaxProgressReportNoCheck(Request $request){
    if($request->preportnumber){
        $search = $request->preportnumber;
        $workid = $request->Work_Id;
        $count = DB::table('progressreports')->where('pr_rep_no','=',$search)
        ->where('work_id','=',$workid)->count();

        return $count;
    }
}


    //Progress Report Image valid or not check
    public function ajaxProgressReportImageChecker(Request $request){
        try{
//dd($request->file('file'));
            if($request->file('file')){
               // dd(1);
                $file = $request->file('file');
                $SaveResult = $this->ajaxgetTagImageChecker($file->getRealPath());
                if(isset($SaveResult['latitude']) && isset($SaveResult['longitude'])){
                    echo  $SaveResult['latitude'].','.$SaveResult['longitude'];
                }else{
                    echo 0;
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function functionreport18columns(){
        return view('reports/18columns');
    }

    public function functionWork()
    {
        try {
            return view('reports/work');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getDropdownData()
{
    // Fetch Junior Engineers from the database
    $juniorEngineers = DB::table('jemasters')->get(); // Modify this based on your table name
    $poList = DB::table('jemasters')->get(); // Fetch PO list

    // Return data as JSON
    return response()->json([
        'juniorEngineers' => $juniorEngineers,
        'poList' => $poList,
    ]);
}

public function EditProgressReportForm1(Request $request)
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

                    if ($request->hasFile('video')) {
                        $videoFile = $request->file('video');
                        $this->fileNamevideo = time() . '_' . $videoFile->getClientOriginalName();
                        $videoFile->move(public_path('uploads/videos'), $this->fileNamevideo);
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
                    $objProgressreport->vid = $this->fileNamevideo ?? ''; // Save video file name

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


}

