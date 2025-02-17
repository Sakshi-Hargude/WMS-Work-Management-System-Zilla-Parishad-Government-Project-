<?php

namespace App\Http\Controllers;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function insert(){
        return view('category.list');
    }

    function saveData(Request $request)
    {

        $data = $request->input();
        try{
            $ev = new Event;
            $ev->eventtitle  = $data['eventtitle'];


            $ev->save();
            return redirect('category')->with('status',"Insert successfully");
        }catch(Exception $e){
            return redirect('category')->with('failed',"operation failed");
        }

    }

}
