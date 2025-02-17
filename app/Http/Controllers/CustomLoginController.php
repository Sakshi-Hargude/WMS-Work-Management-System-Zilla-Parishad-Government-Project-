<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Http\Request;
use App\Mail\LoginNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;




class CustomLoginController extends Controller
{
    //
    //* Handle an authentication attempt.
    public function authenticate(Request $request)
    {


               // Check if the portal is EMB
            if ($request->isportal == "EMB") {
                // Decrypt the username and password
                $username = $request->input('Usernm');
                $password = $request->input('password'); // This is the plain text password


                     // Store the flag in the session
                    session(['isportal' => $request->isportal]);

                // Find the user by username
                $user = User::where('Usernm', $username)->first();

                // Check if the user exists
                if ($user && $password === $user->password) { // Verify the plain text password against the stored hash
                    // Password is correct, proceed with authentication
                    Auth::login($user);
                    //dd($password, $user->password);
                    // Redirect to intended page after successful OTP verification
                return redirect(RouteServiceProvider::HOME);

                } else {
                    // Handle failed authentication (e.g., return an error message)
                    return back()->withErrors([
                        'Usernm' => 'The provided credentials do not match our records.',
                    ])->onlyInput('Usernm');
                }
            }


        // Validate the login form data
        $request->validate([
            'Usernm' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Attempt to authenticate the user
        $credentials = $request->only('Usernm', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Check if DefaultUnmPass is set to 1
    if ($user->DefaultUnmPass == 1) {
        //dd('ok3');
        // Redirect to update page with current username and password
        return redirect()->route('updateUsernamePassword', ['id' => $user->id]);

    }
    //dd('ok4');

            // Generate OTP (you can use any OTP generation logic)
            $otp = random_int(100000, 999999); // Example: 6-digit random OTP

            // Save OTP and credentials in session
            session(['otp' => $otp, 'otp_user' => $user->id, 'credentials' => $credentials]);


            // Send OTP via email
            Mail::to($user->email)->queue(new OtpMail($otp));

            // Redirect to OTP form with username and password
            return redirect()->route('logIn')->with([
                'otp_required' => true,
                'Usernm' => $request->input('Usernm'),
                'password' => $request->input('password')
            ]);
        }

        // If authentication fails, redirect back with an error message
        return redirect()->route('authenticate')->withErrors(['Usernm' => 'The provided credentials do not match our records.']);
    }


//Verify otp
public function verifyOtp(Request $request)
{
    // Validate the OTP form data
    $request->validate([
        'otp' => ['required', 'string'],
    ]);

    // Retrieve OTP and user information from the session
    $otp = $request->otp;
    $sessionOtp = session('otp');
    $otpUserId = session('otp_user');
    $credentials = session('credentials');
//dd($credentials);


    if ($otp == $sessionOtp) {
        // Clear OTP from session
        session()->forget(['otp', 'otp_user', 'Usernm', 'password']);

        // Log in the user
        Auth::loginUsingId($otpUserId);

        // Set a session variable to indicate OTP verification
        session(['otp_verified' => true]);

        // Send login notification email
        $user = Auth::user();
        Mail::to($user->email)->queue(new LoginNotification($user));




        // Redirect to intended page after successful OTP verification
        return redirect(RouteServiceProvider::HOME);
    }

    // If OTP verification fails, redirect back with an error message
    return redirect()->route('logIn')->withErrors(['otp' => 'Invalid OTP.'])
        ->with([
            'otp_required' => true,
            'Usernm' => $credentials['Usernm'],
            'password' => $credentials['password']
        ]);
}


public function loginWithToken(Request $request) {
    try {
        // Decrypt the username and password
        $username = decrypt($request->input('Usernm'));
        $password = decrypt($request->input('password'));
        $isportal = $request->input('isportal');

        // Check the portal flag to ensure the request is legit
        if ($isportal !== 'EMB') {
            return redirect()->route('login')->withErrors(['Invalid portal flag.']);
        }

        // Find the user by username
        $user = User::where('Usernm', $username)->first();

        // If the user exists, validate the password
        if ($user && Hash::check($password, $user->password)) {
            // Log the user in without asking for credentials again
            Auth::login($user);

            // Redirect to the intended page after successful login
             return redirect(RouteServiceProvider::HOME);
        }

        // If the credentials are invalid, redirect back to the login form
        return redirect()->route('login')->withErrors(['Invalid credentials.']);
    } catch (Exception $e) {
        // Handle decryption errors or other exceptions
        return redirect()->route('login')->withErrors(['Failed to decrypt credentials.']);
    }
}
 // Show the form for updating username and password
 public function showUpdateForm($id)
 {
     // Find the user by ID
     $user = User::findOrFail($id);
     session()->put('otp_sent', false);
     // Pass the user information to the view
     return view('update_username_password', [
         'Usernm' => $user->Usernm,
         'password' => '', // You may choose to keep this empty for security reasons
         'userId' => $user->id,
         'id' => $id // Pass the id here
     ]);
 }

// public function updateUserCredentials(Request $request, $id)
// {
//     // Validate the input
//     $request->validate([
//         'Usernm' => 'required|string|max:255',
//         'password' => 'required|string|min:6',


//         'otp' => 'required|string', // Validate OTP

//     ]);


//      // OTP सत्यापित करा
//      if ($request->otp != session('otp')) {
//         return back()->withErrors(['otp' => 'Invalid OTP']);
//     }

//     // Find the user
//     $user = User::findOrFail($id);

//     // Update username and password
//     $user->Usernm = $request->Usernm;
//     $user->password = Hash::make($request->password); // Hash the password
//     $user->DefaultUnmPass = 0; // Set to 0 if password is updated
//     $user->save();


//     Auth::logout();

//     return redirect()->route('login')->with('success', 'Credentials updated successfully.');
// }

public function updateUserCredentials(Request $request, $id)
{
    //dd($request->all());
     $request->validate([
        'otp' => 'required|digits:6', // Validate the OTP
       'Usernm' => 'required|string|max:255',
         'password' => 'required|string|min:8'
    ]);

 //dd('ok');

    // Verify the OTP
    if ($request->otp == session('otp')) {
        //$credentials = session('credentials');
        $user = User::find($id);

        //Update credentials in the database
         $user->Usernm = $request->Usernm;
        $user->password = Hash::make($request->password);
        $user->DefaultUnmPass = 0; // Reset flag
        $user->save();

        // Clear OTP and credentials from the session
        $request->session()->forget(['otp', 'otp_sent','Usernm','password']);

        Auth::logout();
        return redirect()->route('login')->with('status', 'Credentials updated successfully. Please log in.');
    } else {
        return back()->withErrors(['otp' => 'The OTP entered is incorrect.']);
    }

}




public function allrecords()
{
    $users = User::all(); // सर्व वापरकर्त्यांचे डेटा डेटाबेसमधून आणा
    return view('userslist', compact('users')); // व्ह्यूमध्ये डेटा पाठवा
}



public function sendOtp(Request $request)
{
    // Validate incoming request
    $request->validate([
        'Usernm' => 'required|string|max:255',
        'password' => 'required|string|min:8', // Ensure you confirm password
    ]);

    // Find the user by ID
    $user = User::find($request->id);

    // If user not found, handle the error
    if (!$user) {
        return redirect()->back()->withErrors(['User not found']);
    }

    // Check if entered credentials match existing default credentials
    if ($request->Usernm === $user->Usernm || Hash::check($request->password, $user->password)) {
        session()->flash('error', 'Default username and password cannot be reused. Please choose different credentials.');

        return view('update_username_password', ['user' => $user, 'id' => $request->id]);
    }

    // Generate and store OTP
    $otp = rand(100000, 999999);
    $request->session()->put('otp', $otp);

    try {
        // Send OTP to the user's email
        Mail::to($user->email)->send(new OtpMail($otp));
        Log::info('OTP sent to user: ' . $user->email);

        // Store input values in session to show them back in the form
        $request->session()->put('Usernm', $request->Usernm);
        $request->session()->put('password', $request->password);
        $request->session()->put('otp_sent', true);

        return view('update_username_password', ['user' => $user, 'id' => $request->id]);
    } catch (\Exception $e) {
        Log::error('Error sending OTP: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Failed to send OTP']);
    }
}






}
