<?php

namespace App\Http\Controllers\FrontEnd;

use App\Events\MessageStored;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Http\Helpers\BasicMailer;
use App\Http\Helpers\SellerPermissionHelper;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\MessageRequest;
use App\Http\Requests\SupportTicket\ConversationRequest;
use App\Http\Requests\SupportTicket\TicketRequest;
use App\Http\Requests\User\ForgetPasswordRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\SignupRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\ClientService\Service;
use App\Models\ClientService\ServiceOrder;
use App\Models\ClientService\ServiceOrderMessage;
use App\Models\Follower;
use App\Models\Seller;
use App\Models\SupportTicket;
use App\Models\TicketConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Mews\Purifier\Facades\Purifier;

class UserController extends Controller
{
  public function login()
  {
    $misc = new MiscellaneousController();

    $language = $misc->getLanguage();

    $queryResult['seoInfo'] = $language->seoInfo()->select('meta_keyword_customer_login', 'meta_description_customer_login')->first();

    $queryResult['pageHeading'] = $misc->getPageHeading($language);

    $queryResult['breadcrumb'] = $misc->getBreadcrumb();

    $queryResult['bs'] = Basic::query()->select('google_recaptcha_status', 'facebook_login_status', 'google_login_status')->first();

    return view('frontend.login', $queryResult);
  }

  public function redirectToFacebook()
  {
    return Socialite::driver('facebook')->redirect();
  }
  public function redirectToLinkedIn()
  {
    //return response()->json(['hi'=>'mssg']);

     return Socialite::driver('linkedin-openid')->scopes(['openid', 'profile', 'email', 'w_member_social'])->redirect();
  }
  public function handleFacebookCallback()
  {
    return $this->authenticationViaProvider('facebook');
  }

  public function redirectToGoogle()
  {
    return Socialite::driver('google')->redirect();
  }

  public function handleGoogleCallback()
  {
    return $this->authenticationViaProvider('google');
  }

  public function handleLinkedInCallback()
{
 // return response()->json(['hi'=>'mssg']);
     return $this->authenticationViaProvider('linkedin-openid');
}
  public function authenticationViaProvider($driver)
  {
    // get the url from session which will be redirect after login
   

    $responseData = Socialite::driver($driver)->user();

    $userInfo = $responseData->user;
    $redirectURL = 'http://localhost:3000';
    return redirect()->to($redirectURL . '?' . http_build_query($userInfo));
  
    $isUser = User::query()->where('email_address', '=', $userInfo['email'])->first();

    if (!empty($isUser)) {
      // log in
      if ($isUser->status == 1) {
        Auth::login($isUser);

        return redirect($redirectURL);
      } else {
        Session::flash('error', 'Sorry, your account has been deactivated.');

        return redirect()->route('user.login');
      }
    } else {
      // get user avatar and save it
      $avatar = $responseData->getAvatar();
      $fileContents = file_get_contents($avatar);

      $avatarName = $responseData->getId() . '.jpg';
      $path = public_path('assets/img/users/');

      file_put_contents($path . $avatarName, $fileContents);

      // sign up
      $user = new User();

      if ($driver == 'facebook') {
        $user->first_name = $userInfo['name'];
      } else {
        $user->first_name = $userInfo['given_name'];
        $user->last_name = $userInfo['family_name'];
      }

      $user->image = $avatarName;
      $user->email_address = $userInfo['email'];
      $user->email_verified_at = date('Y-m-d H:i:s');
      $user->status = 1;
      $user->provider = ($driver == 'facebook') ? 'facebook' : 'google';
      $user->provider_id = $userInfo['id'];
      $user->save();

      Auth::login($user);

      return redirect($redirectURL);
    }
  }

  public function loginSubmit(LoginRequest $request)
  {
    // get the url from session which will be redirect after login
    if ($request->session()->has('redirectTo')) {
      $redirectURL = $request->session()->get('redirectTo');
    } else {
      $redirectURL = route('user.dashboard');
    }

    // get the email-address and password which has provided by the user
    $credentials = $request->only('username', 'password');

    // login attempt
    if (Auth::guard('web')->attempt($credentials)) {
      $authUser = Auth::guard('web')->user();

      // first, check whether the user's email address verified or not
      if (is_null($authUser->email_verified_at)) {
        $request->session()->flash('error', 'Please, verify your email address.');

        // logout auth user as condition not satisfied
        Auth::guard('web')->logout();

        return redirect()->back();
      }

      // second, check whether the user's account is active or not
      if ($authUser->status == 0) {
        $request->session()->flash('error', 'Sorry, your account has been deactivated.');

        // logout auth user as condition not satisfied
        Auth::guard('web')->logout();

        return redirect()->back();
      }

      // before, redirect to next url forget the session value
      if ($request->session()->has('redirectTo')) {
        $request->session()->forget('redirectTo');
      }


      // otherwise, redirect auth user to next url
      return redirect($redirectURL);
    } else {
      $request->session()->flash('error', 'Incorrect username or password!');

      return redirect()->back();
    }
  }

  public function forgetPassword()
  {
    $misc = new MiscellaneousController();

    $language = $misc->getLanguage();

    $queryResult['seoInfo'] = $language->seoInfo()->select('meta_keyword_customer_forget_password', 'meta_description_customer_forget_password')->first();

    $queryResult['pageHeading'] = $misc->getPageHeading($language);

    $queryResult['breadcrumb'] = $misc->getBreadcrumb();

    return view('frontend.forget-password', $queryResult);
  }

  public function forgetPasswordMail(ForgetPasswordRequest $request)
  {
    $user = User::query()->where('email_address', '=', $request->email_address)->first();

    // store user email in session to use it later
    $request->session()->put('userEmail', $user->email_address);

    // get the mail template information from db
    $mailTemplate = MailTemplate::query()->where('mail_type', '=', 'reset_password')->first();
    $mailData['subject'] = $mailTemplate->mail_subject;
    $mailBody = $mailTemplate->mail_body;

    // get the website title info from db
    $websiteTitle = Basic::query()->pluck('website_title')->first();

    $name = $user->first_name . ' ' . $user->last_name;

    $link = '<a href=' . url("user/reset-password") . '>Click Here</a>';

    $mailBody = str_replace('{customer_name}', $name, $mailBody);
    $mailBody = str_replace('{password_reset_link}', $link, $mailBody);
    $mailBody = str_replace('{website_title}', $websiteTitle, $mailBody);

    $mailData['body'] = $mailBody;

    $mailData['recipient'] = $user->email_address;

    $mailData['sessionMessage'] = 'A mail has been sent to your email address.';

    BasicMailer::sendMail($mailData);

    return redirect()->back();
  }

  public function resetPassword()
  {
    $misc = new MiscellaneousController();

    $breadcrumb = $misc->getBreadcrumb();

    return view('frontend.reset-password', compact('breadcrumb'));
  }

  public function resetPasswordSubmit(ResetPasswordRequest $request)
  {
    if ($request->session()->has('userEmail')) {
      // get the user email from session
      $emailAddress = $request->session()->get('userEmail');

      $user = User::query()->where('email_address', '=', $emailAddress)->first();

      $user->update([
        'password' => Hash::make($request->new_password)
      ]);

      $request->session()->flash('success', 'Password updated successfully.');
    } else {
      $request->session()->flash('error', 'Something went wrong!');
    }

    return redirect()->route('user.login');
  }

}
