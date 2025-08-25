<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Enums\System\ChannelTypeEnum;
use App\Http\Utility\SendMail;
use App\Models\Blog;
use App\Models\FrontendSection;
use App\Models\Gateway;
use App\Traits\Manageable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Validator;

class WebController extends Controller
{
    use Manageable;

    protected $sendMail;

    /**
     * __construct
     *
     */
    public function __construct()
    {
        $this->sendMail = new SendMail();
    }


    public function index(): View
    {
        $title = translate("Home");
        return view('frontend.home', compact('title'));
    }
    
    public function pricing(): View
    {
        $title = translate("Pricing");
        return view('frontend.pages.pricing', compact('title'));
    }

    public function about(): View
    {
        $title = translate("About");
        return view('frontend.pages.about', compact('title'));
    }

    public function contact(): View
    {
        $title = translate("Contact");
        return view('frontend.pages.contact', compact('title'));
    }

    public function getInTouch(Request $request) {

        $status = 'error';
        $message = 'This feature is unavailable at the moment, please try again later';
    
        try {
            $validatedData = $request->validate([
                'email_from_name'  => 'required',
                'email_to_address' => 'required|email:rfc,dns',
                'message'          => 'required',
            ]);
            
            $data = $request->toArray();
            $gateway = $this->getSpecificLogByColumn(
                model: new Gateway(), 
                column: "is_default",
                value: StatusEnum::TRUE->status(),
                attributes: [
                    "user_id" => null,
                    "channel" => ChannelTypeEnum::EMAIL->value,
                ]
            );

            if($gateway) {

                $this->sendMail->send(
                    $gateway, 
                    Arr::get($data, "email_to_address"), 
                    Arr::get($data, "subject"), 
                    Arr::get($data, "message"));
                    
                    $message = translate("Contacted admin successfully");
            }
    
        } catch(\Illuminate\Validation\ValidationException $e) {

            $errors = $e->validator->errors()->all();
            $message = implode(' ', $errors); 
            $notify[] = [$status, $message];
            return back()->withNotify($notify);
        } catch(\Exception $e) {
        }
    
        $notify[] = [$status, $message];
        return back()->withNotify($notify);
    }
    
    public function service($type = null) 
    {
        try {
            $title = ucfirst($type). translate(" Service");
            if(in_array($type, array_flip(['sms, whatsapp, email']))) {
                
                $notify[] = ['error', translate("Currently we only offer sms,email and whatsapp services")];
                return back()->withNotify($notify);
            } else {
                return view('frontend.pages.service', compact('title', 'type'));
            }
        } catch (\Exception $e) {
            
        }
        
    }

    public function pages($key , $id): View
    {
        $title = translate("Pages");
        $data = FrontendSection::where('id',$id)->first();
        $description = getArrayValue($data->section_value, 'details');
        return view('frontend.pages.policy', compact('title',"description",'key'));
    }

    public function blog($uid = null) {

        $title = translate("Our Blog");
        if($uid) {

            $blogs = Blog::where('status', StatusEnum::TRUE->status())->get()->take(2);
            $blog  = Blog::where('uid', $uid)->first();
            return view('frontend.blog.details', compact('title',"blog", "blogs"));
        } else {

            $blogs = Blog::search(['title'])
                            ->filter(['status'])
                            ->latest()
                            ->date()
                            ->paginate(paginateNumber(site_settings("paginate_number")))->onEachSide(1)
                            ->appends(request()->all());
            return view('frontend.blog.list', compact('title',"blogs"));
        }
    }
    public function blogSearch(Request $request) {
        
        
        $search = $request->input('search');
        $limit = site_settings("paginate_number");
    
        $blogs = Blog::where('status', '1')
                     ->when($search, function ($query) use ($search) {
                         return $query->where('title', 'like', '%' . $search . '%');
                     })
                     ->latest()
                     ->limit($limit)
                     ->get();
       
        return view('frontend.blog.partials.recent_blogs', compact('blogs'));
    }
}
