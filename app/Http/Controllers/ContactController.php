<?php

namespace App\Http\Controllers;
use App\Models\Member;

use Illuminate\Http\Request;

class ContactController extends Controller
{

    public function insert(){
        //$urlData = getURLList();
        return view('contact');
    }

    function addData(Request $request)
    {

        $data = $request->input();
        try{
            $student = new Member;
            $student->uname = $data['uname'];
            $student->email = $data['email'];
            $student->mobileno = $data['mobileno'];
            $student->subject = $data['subject'];
            $student->disc = $data['disc'];
            $student->save();
            return redirect('contact')->with('success',"Enquiry Submitted");
        }catch(Exception $e){
            return redirect('contact')->with('failed',"operation failed");
        }

    }


}
