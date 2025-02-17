<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Fundhdm;
use App\Models\Subdivm;
Use App\Models\User;
use App\Models\Userperm;
use App\Models\Workmaster;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class UserpermController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function createview()
       {
            $rsAllUserList = User::get();
            $rsFundedList = Fundhdm::get();
            $rsSubDevisionList = Subdivm::get();
            $rsWorkMaster = Workmaster::get();

            return view('permission/add',['rsUser'=>$rsAllUserList,'rsFund'=>$rsFundedList,'rsSubDiv'=>$rsSubDevisionList,'rsWorkMaster'=>$rsWorkMaster]);
       }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function InsertTB(Request $request)
    {
        $data = $request->input();
        $request->validate([
            'User_Id' => 'required|string',
            'F_H_CODE' => 'required|string',
            'Sub_Div_Id'=> 'required|string',
            'Work_Id' => 'required|string',
        ]);

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
        $objUserPermission = new Userperm();
        $objUserPermission->Unique_Id  = $PrimaryNumber;
        $objUserPermission->User_Id = $data['User_Id'];
        $objUserPermission->F_H_CODE = $data['F_H_CODE'];
        $objUserPermission->Sub_Div_Id = $data['Sub_Div_Id'];
        $objUserPermission->Work_Id = $data['Work_Id'];
        $objUserPermission->save();
        return redirect('admin/permission/add')->with('status',"Permission Grant Successfully");
    }
}
