<?php

namespace App\Services\System\Admin;

use App\Enums\Common\Status;
use App\Enums\DefaultTemplateSlug;
use App\Enums\SettingKey;
use App\Enums\StatusEnum;
use App\Enums\System\ChannelTypeEnum;
use App\Exceptions\ApplicationException;
use App\Http\Requests\AdminNewPasswordRequest;
use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Utility\SendMail;
use App\Managers\AdminManager;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use App\Models\Gateway;
use App\Models\Template;
use App\Services\System\TemplateService;
use App\Traits\Manageable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthService
{ 
     use Manageable;

     protected $sendMail;
     protected $adminManager;
     protected $templateService;

     /**
      * __construct
      *
      */
     public function __construct()
     {
          $this->sendMail          = new SendMail();
          $this->adminManager      = new AdminManager();
          $this->templateService   = new TemplateService();
     }

     /**
      * resetPassword
      *
      * @param AdminNewPasswordRequest $request
      * 
      * @return RedirectResponse
      */
     public function sendResetPasswordNotification(AdminNewPasswordRequest $request): RedirectResponse {

          $admin = $this->getSpecificLogByColumn(model: new Admin(), column: "email", value: $request->input("email")); 

          if(!$admin) throw new ApplicationException("Contact not found", Response::HTTP_NOT_FOUND);

          $passwordResetLog = $this->getSpecificLogByColumn(model: new AdminPasswordReset(), column: "email", value: $request->input("email")); 
          
          if($passwordResetLog) $passwordResetLog->delete();

          $now      = Carbon::now();
          $token    = randomNumber();

          AdminPasswordReset::create([
               'email'        => $request->input("email"),
               'token'        => $token,
               'created_at'   => $now,
          ]);
          
          $gateway = $this->getSpecificLogByColumn(
               model: new Gateway(), 
               column: "is_default",
               value: StatusEnum::TRUE->status(),
               attributes: [
                    "user_id" => null,
                    "channel" => ChannelTypeEnum::EMAIL->value,
               ]
          );
          if(!$gateway) throw new ApplicationException("Default gateway was not set, please contact support", Response::HTTP_NOT_FOUND);

          $template = $this->getSpecificLogByColumn(
               model: new Template(), 
               column: "slug",
               value: DefaultTemplateSlug::ADMIN_PASSWORD_RESET->value,
               attributes: [
                    "user_id" => null,
                    "channel" => ChannelTypeEnum::EMAIL,
                    "default" => true,
                    "status"  => Status::ACTIVE->value
               ]
          );
          if(!$template) throw new ApplicationException("Template Unavailable", Response::HTTP_NOT_FOUND);

          $mailCode = [
               'code' => $token, 
               'time' => $now,
          ];

          $status = $this->sendMail->MailNotification($gateway, $template, $admin, $mailCode);

          if(!$status) throw new ApplicationException("Could not send email please try again or contact support", Response::HTTP_BAD_REQUEST);

          session()->put('admin_password_reset_user_email', $request->email);
          $notify[] = ['success', translate('Check your email password reset code sent successfully')];
          return redirect(route('admin.password.verify.code'))->withNotify($notify);
     }

     /**
      * resetPassword
      *
      * @param AdminResetPasswordRequest $request
      * 
      * @return RedirectResponse
      */
     public function resetPassword(AdminResetPasswordRequest $request): RedirectResponse {

          $email = session()->get('admin_password_reset_user_email');

          $passwordResetLog = $this->getSpecificLogByColumn(
               model: new AdminPasswordReset(), 
               column: "email", 
               value: $email); 
          if(!$passwordResetLog) throw new ApplicationException("Invalid Token", Response::HTTP_NOT_FOUND);

          $admin = $this->getSpecificLogByColumn(model: new Admin(), column: "email", value: $request->input("email")); 
          if(!$admin) throw new ApplicationException("Contact not found", Response::HTTP_NOT_FOUND);
          $admin->password = Hash::make($request->password);
          $admin->save();

          if(session()->get('admin_password_reset_user_email')){
               session()->forget('admin_password_reset_user_email');
          }

          $gateway = $this->getSpecificLogByColumn(
               model: new Gateway(), 
               column: "is_default",
               value: StatusEnum::TRUE->status(),
               attributes: [
                    "user_id" => null,
                    "channel" => ChannelTypeEnum::EMAIL->value,
               ]
          );
          if(!$gateway) throw new ApplicationException("Default gateway was not set, please contact support", Response::HTTP_NOT_FOUND);

          $template = $this->getSpecificLogByColumn(
               model: new Template(), 
               column: "slug",
               value: DefaultTemplateSlug::PASSWORD_RESET_CONFIRM->value,
               attributes: [
                    "user_id" => null,
                    "channel" => ChannelTypeEnum::EMAIL,
                    "default" => true,
                    "status"  => Status::ACTIVE->value
               ]
          );
          if(!$template) throw new ApplicationException("Template Unavailable", Response::HTTP_NOT_FOUND);

          $mailCode = [
               'time' => Carbon::now(),
               'name' => site_settings(SettingKey::SITE_NAME->value, "Xsender")
          ];

          $status = $this->sendMail->MailNotification($gateway, $template, $admin, $mailCode);
          if(!$status) throw new ApplicationException("Could not send email please try again or contact support", Response::HTTP_BAD_REQUEST);

          $passwordResetLog->delete();
          $notify[] = ['success', 'Password changed successfully'];
          return redirect(route('admin.login'))->withNotify($notify);
     }
}