<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Division;
use App\Models\Subdivm;
use App\Models\Designation;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Mail\UserRegistered;
use Illuminate\Support\Facades\Mail;




class UserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function createview()
       {
            // login user session Data----------------------------
            $usercode = auth()->user()->usercode;
            $divid = auth()->user()->Div_id;
            $subdivid = auth()->user()->Sub_Div_id;
            // login user session Data----------------------------

            $rsDivisionList= Division::get()->where('div_id', '=', '141'); // Division Master List
            $rsSubDivisionList= Subdivm::get()->where('Div_Id', '=', '141'); // Sub Division Master List
            $rsDesignationList= Designation::get(); // Sub Division Master List
            return view('adduser',['rsDiv'=>$rsDivisionList,'rsSubDiv'=>$rsSubDivisionList,'rsDesignation'=>$rsDesignationList]);
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
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'Usernm' => 'required|regex:/^\S*$/u|string|max:15|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'mobileno'=> 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users',
            'password' => 'required|string|min:6',
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
//dd($request);
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
            'DefaultUnmPass'=> 1,


        ]);

        Mail::to($request->email)->send(new UserRegistered(
            $request->Usernm,
            $request->password,
            $request->email,    // ईमेल
            $request->mobileno  // मोबाइल नंबर
        ));


        //event(new Registered($user));
        return redirect('userslist')->with([
            'success' => 'Record saved successfully.',
            'Usernm' => $request->Usernm,
            'email' => $request->email,
            'password' => $request->password
        ]);

    }



}
