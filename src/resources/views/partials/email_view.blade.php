<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{asset('assets/theme/global/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/theme/global/css/all.min.css')}}">
</head>
<body>
    
    {!!$log?->message?->main_body 
        ? replaceContactVariables($log->contact, $log->message->main_body) 
        : translate("N/A")!!}  
    <span class="text-danger" >{{$log->response_message ? ":".$log->response_message :"" }}</span>
    <script src="{{asset('assets/theme/global/js/jquery-3.6.0.min.js')}}"></script>
    <script src="{{asset('assets/theme/global/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('assets/theme/global/js/all.min.js')}}"></script>
</body>
</html>
