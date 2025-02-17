<?php
namespace App\Http\Controllers;
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
    **/

    public function ajaxRequestcreateview(Request $request)
        {
            if($request->fundeda != ''){
                $request->fundeda;
                $rsFundedList =  DB::table('fundhdms')
                ->select(DB::raw("CONCAT(F_H_CODE,' ',Fund_Hd_M) AS Fund_Hd_M"))
                ->where('F_H_CODE', 'like', '%' . $request->fundeda . '%')
                ->orWhere('Fund_Hd_M', 'like', '%' . $request->fundeda . '%')
                ->get();
                return response()->json(array('msg'=> $rsFundedList), 200);
            } else{
                return response()->json(array('msg'=> null), 200);
            }
        }


    // Get Selected User Permission
    public function ajaxRequestUserPermission(Request $request)
        {
            if($request->puid){ // Selected User ID
             $rsUserPermissionsList =  DB::table('userperms')
                ->select('userperms.User_Id','userperms.Unique_Id','userperms.F_H_CODE','userperms.Sub_Div_Id','userperms.Work_Id','subdivms.Sub_Div_M')
                ->leftJoin('subdivms', 'userperms.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->where('userperms.User_Id', '=', $request->puid)
                ->where('userperms.Removed', '=', 1)
                ->get();
                return response()->json(array('msg'=> $rsUserPermissionsList), 200);
            }else{
                return response()->json(array('msg'=> null), 200);
            }

        }

        // Remove User Permission

    public function ajaxRemoveUserPermission(Request $request)
    {
        if($request->puid){ // Selected User ID
            $rsRemoveUserPermission =  DB::table('userperms')
            ->where('Unique_Id', '=', $request->puid)
            ->update(['Removed' => 0]);

            if($request->puserid){ // Selected User ID
                $rsUserPermissionsList =  DB::table('userperms')
                ->select('userperms.User_Id','userperms.Unique_Id','userperms.F_H_CODE','userperms.Sub_Div_Id','userperms.Work_Id','subdivms.Sub_Div_M')
                ->leftJoin('subdivms', 'userperms.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->where('userperms.User_Id', '=', $request->puserid)
                ->where('userperms.Removed', '=', 1)
                ->get();
                   return response()->json(array('msg'=> $rsUserPermissionsList), 200);
               }else{
                   return response()->json(array('msg'=> null), 200);
               }

        }

    }

    public function createview()
       {
            // login user session Data----------------------------
            $usercode = auth()->user()->usercode;
            $divid = auth()->user()->Div_id;
            $subdivid = auth()->user()->Sub_Div_id;
            // login user session Data----------------------------

            $rsAllUserList = User::get()->whereNotIn('usertypes', ['EE','DA']);
            $rsFundedList = Fundhdm::get();
            $rsSubDevisionList = Subdivm::get()
            ->where('Div_Id','=',$divid);
            $rsWorkMaster = Workmaster::get()
            ->where('Sub_Div_Id','=',$subdivid);
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
            // 'F_H_CODE' => 'required|string',
            // 'Sub_Div_Id'=> 'required|string',
            // 'Work_Id' => 'required|string',
        ]);
        for ($i = 0; $i < count($request->F_H_CODE); $i++){

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
                $objUserPermission->User_Id = $data['User_Id'];
                $objUserPermission->F_H_CODE = $request->F_H_CODE[$i];
                $objUserPermission->Sub_Div_Id = $request->Sub_Div_Id[$i];
                $objUserPermission->Work_Id = $request->Work_Id[$i];
                $objUserPermission->save();
        }

        return redirect('addpermission')->with('status',"Permission Grant Successfully");
    }
}
