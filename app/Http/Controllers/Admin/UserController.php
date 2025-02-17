<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Division;
use App\Models\Subdivm;
use App\Models\Designation;
use App\Models\Userperm;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class UserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function createview()
       {
            $rsDivisionList= Division::get()->whereIn('div_id',[141,147]); // Division Master List
            $rsSubDivisionList= Subdivm::get()->whereIn('Div_Id',[141,147]); // Sub Division Master List
            $rsDesignationList= Designation::get(); // Sub Division Master List
            return view('users/add',['rsDiv'=>$rsDivisionList,'rsSubDiv'=>$rsSubDivisionList,'rsDesignation'=>$rsDesignationList]);
       }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeUsersData(Request $request)
    {  //echo $request; exit;
        $request->validate([
            'name' => 'required|string|max:255',
            'Usernm' => 'required|regex:/^\S*$/u|string|max:15|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'mobileno'=> 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'password' => 'required|string|confirmed|min:6',
        ]);

       // Check Division Or SubDivision ID
       if($request->Div_id){
        $concatDivORSubDivID = $request->Div_id;
      }
      if($request->Sub_Div_id){
        $concatDivORSubDivID = $request->Sub_Div_id;
      }


      $DivisionIDLength = strlen($concatDivORSubDivID);
      if((int)$DivisionIDLength === 1){
        $DivisionID = $concatDivORSubDivID."000";
      }else if((int)$DivisionIDLength === 2){
        $DivisionID = $concatDivORSubDivID."00";
      }else if((int)$DivisionIDLength === 3){
        $DivisionID = $concatDivORSubDivID."0";
      }else if((int)$DivisionIDLength === 4){
        $DivisionID = $concatDivORSubDivID;
      }


        //User code Genration Functionality
        $SQLNewPKID = DB::table('users')
        ->selectRaw(" MAX(CAST(right(IFNULL(`usercode`,'0'),4)AS UNSIGNED)) as usercode ")
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->usercode) && !empty($RSNewPKID[0]->usercode)){
            $PrimaryNumber=$RSNewPKID[0]->usercode + 1;
        }else{
            $PrimaryNumber='1';
        }
        $lenght = strlen($PrimaryNumber);  //Calculate Lenght
        if((int)$lenght === 1){ //Places Zero Functionality
            $placezero = '000';
        }else if((int)$lenght === 2){
            $placezero = '00';
        }else if((int)$lenght === 3){
            $placezero = '0';
        }else{
            $placezero = '';
        }

        $usercode = $DivisionID.$placezero.$PrimaryNumber;
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobileno'=>$request->mobileno,
            'password' => Hash::make($request->password),
            'Div_id'=>$request->Div_id,
            'Sub_Div_id'=>$request->Sub_Div_id,
            'Designation'=>$request->Designation,
            'usercode'=>$usercode,
            'usertypes'=>$request->usertypes,
            'Usernm'=>$request->Usernm,
        ]);

        //event(new Registered($user));
        // return redirect('RouteServiceProvider::HOME');

        if($request->usertypes == 'EE' || $request->usertypes == 'DA' || $request->usertypes == 'PA'){
            //get inserted userid
            $LastInsertedID = DB::table('users')
            ->selectRaw("id")
            ->where('usercode','=',$usercode)
            ->get();
            $rsuserid= json_decode($LastInsertedID);
            $rsuserid[0]->id;

            //Insert All Permission in userPermission Table
            // Auto Increament Userpermission Id
            $SQLNewPKID = DB::table('userperms')
            ->selectRaw('Unique_Id + 1 as Unique_Id')
            ->orderBy('Unique_Id', 'desc')
            ->limit(1)
            ->get();
            $RSNewPKID = json_decode($SQLNewPKID);
            if(isset($RSNewPKID[0]->Unique_Id) && !empty($RSNewPKID[0]->Unique_Id)){
            $PrimaryNumber=$RSNewPKID[0]->Unique_Id;
            }else{
            $PrimaryNumber=1;
            }
            //$Period_From = Input::get('Period_From');
            $objUserPermission = new Userperm();
            $objUserPermission->Unique_Id  = $PrimaryNumber;
            $objUserPermission->User_Id = $rsuserid[0]->id;
            $objUserPermission->F_H_CODE = 'all';
            $objUserPermission->Sub_Div_Id = 'all';
            $objUserPermission->Work_Id = 'all';
            $objUserPermission->save();
        }
        return redirect('admin/users/list')->with('success','Record save successfully.');
    }
}
