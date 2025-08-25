@push("style-include")
<link rel="stylesheet" href="{{asset('assets/theme/global/css/prism.css')}}">
@endpush
@extends('admin.layouts.app')
@section('panel')
<main class="main-body">
    <div class="container-fluid px-0 main-content">
      <div class="page-header">
        <div class="page-header-left">
          <h2>{{ $title }}</h2>
          <p>{{ translate("API Key Generation and Documentations to use the functionality.") }}</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="form-element border-bottom-0 py-0">
            <div class="row gy-3">
              <div class="col-xxl-2 col-xl-3">
                <h5 class="form-element-title">{{ translate("Before you get started") }}</h5>
              </div>
              <div class="col-xxl-10 col-xl-9">
                <div class="row">
                  <div class="col-xl-10">
                    <div class="bg-light rounded-2 p-3 fs-15 text-muted border">
                      <p> {{ translate("A brief overview of the API and its purpose") }} <br />
                        <span class="text-dark fw-semibold">{{ translatE("Endpoints: ") }}</span>{{ translate("A list of all the endpoints available in the API, including their URLs and the HTTP methods they support.") }}<br />
                        <span class="text-dark fw-semibold">{{ translate("Request and Response: ") }}</span> {{ translate("The expected request format and the format of the response, including examples of how to use the API and the data that it returns.") }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card mt-4">
        <div class="card-body" id="api-accordion">
          <div class="form-element pt-0">
            <div class="row gy-3">
              <div class="col-xxl-2 col-xl-3">
                <h5 class="form-element-title">{{ translate("Generate Api") }}</h5>
              </div>
              <div class="col-xxl-10 col-xl-9">
                  <div class="row gy-3 gx-3">
                    <div class="col-xxl-8 col-xl-7 col-lg-9 col-sm-8">
                      <div class="form-inner">
                        <label for="api_key" class="form-label"> {{ translate("Generate API Key") }} </label>
                        <div class="input-group">
                          <input type="text" class="form-control" placeholder="{{ translate("API KEY") }}" id="api_key" name="api_key" aria-describedby="recipient-addon" value="{{ $api_key }}"/>
                          <span class="fs-14 bg--success-light input-group-text text-success" id="copy_api_key" role="button">{{ translate("Copy") }}</span>
                        </div>
                        <p class="form-element-note"> {{ translate("Please do not share the API Key") }} </p>
                      </div>
                    </div>
                    <div class="col-xxl-2 col-xl-3 col-lg-3 col-sm-4">
                      <button class="i-btn btn--primary btn--md w-100 mt-sm-4 generate-api-key" id="keygen" type="button">
                        <i class="ri-add-fill fs-18"></i> {{ translate("Generate") }} </button>
                    </div>
                  </div>
              </div>
            </div>
          </div>
          
          <div class="form-element">
            <div class="row gy-3">
              <div class="col-xxl-2 col-xl-3">
                <h5 class="form-element-title">{{ translate("Email") }}</h5>
              </div>
              <div class="col-xxl-10 col-xl-9">
                <div class="row">
                  <div class="col-xl-10">
                    <div class="accordion-wrapper api-accordion">
                      <div class="accordion">
                        <div>
                          <span class="form-label">{{ translate("Send via POST Method") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="emailOne">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#emailPost" aria-expanded="false" aria-controls="emailPost"> {{route('incoming.email.send')}} </button>
                            </h2>
                            <div id="emailPost" class="accordion-collapse collapse" aria-labelledby="emailOne" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
									<code class="language-php">
$curl = curl_init();
$postdata = array(
  "contact" = array(
    array(
        "subject" => "This is a API test",
        "email" => "test@mail.com",
        "message" => "This is a API \ntest",
        "gateway_identifier" : "*****************"
    ),
    array(
        "subject" => "This is a API test",
        "email" => "test@mail.com",
        "message" => "This is a API \ntest",
        "schedule_at" => "2024-07-10 12:25:00",
    ),
    array(
        "subject" => "This is a API test",
        "email" => "test@mail.com",
        "message" => "This is a API \ntest",
        "sender_name" => "Postman",
        "schedule_at" => "2024-07-10 12:25:00",
    ),
    array(
        "subject" => "This is a API test",
        "email" => "test@mail.com",
        "message" => "This is a API \ntest",
        "reply_to_email" => "postman@api.com",
    ),
    array(
        "subject" => "This is a API test",
        "email" => "test@mail.com",
        "message" => "This is a API \ntest",
        "sender_name" => "Postman",
        "reply_to_email" => "postman@api.com",
    ),
);
);

curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://xsender.igensolutionsltd.com/api/email/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($postdata),
        CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
        'Content-Type: application/json'
    ),
));
$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Email Dispatch Request Created Successfully",
  "data": [
      {
          "id": 57,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test identifier",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 58,
          "created_at": "2025-04-21 16:05:10",
          "status": "schedule",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 59,
          "created_at": "2025-04-21 16:05:10",
          "status": "schedule",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 60,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 61,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      }
  ]
}
                                        </code>
									</pre>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("Send via GET method") }}</span>
                          <div class="accordion-item">
                              <h2 class="accordion-header" id="emailQuery">
                                <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#emailQueryGet" aria-expanded="false" aria-controls="emailQueryGet"> {{ route('incoming.email.send.query') . '?contacts={contacts}&message={message}&subject={subject}' }} </button>
                              </h2>
                              <div id="emailQueryGet" class="accordion-collapse collapse" aria-labelledby="emailQuery" data-bs-parent="#api-accordion">
                                  <div class="accordion-body">
                                      <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint using query parameters, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                      <pre>
                                          <code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => '{{route('incoming.email.send.query')}}?contacts=a@a.com,b@b.com&message=test%20body&subject=test%20subject',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
  'Api-key: ###########################,
),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Email Dispatch Request Created Successfully",
  "data": [
      {
          "id": 57,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test identifier",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 58,
          "created_at": "2025-04-21 16:05:10",
          "status": "schedule",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 59,
          "created_at": "2025-04-21 16:05:10",
          "status": "schedule",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 60,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      },
      {
          "id": 61,
          "created_at": "2025-04-21 16:05:10",
          "status": "pending",
          "message": {
              "subject": "This is a API test",
              "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "email_contact": "igenteams@gmail.com",
              "meta_data": null
          }
      }
  ]
}
                                          </code>
                                      </pre>
                                  </div>
                              </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("GET Status") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="emailTwo">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#emailGet" aria-expanded="false" aria-controls="emailGet"> {{url('api/get/email/{id}')}} </button>
                            </h2>
                            <div id="emailGet" class="accordion-collapse collapse" aria-labelledby="emailTwo" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
									<code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => '{{url('api/get/email/{id}')}}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
    ),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Successfully Fetched Email From Logs",
  "data": {
      "id": 56,
      "created_at": "2025-04-21 16:04:38",
      "status": "fail",
      "message": {
          "subject": "This is a API test",
          "main_body": "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><p>This is a API \ntest</p></body></html>\n"
      },
      "contact": {
          "first_name": null,
          "last_name": null,
          "email_contact": "igenteams@gmail.com",
          "meta_data": null
      }
  }
}
                                        </code>
									</pre>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="form-element">
            <div class="row gy-3">
              <div class="col-xxl-2 col-xl-3">
                <h5 class="form-element-title">{{ translate("SMS") }}</h5>
              </div>
              <div class="col-xxl-10 col-xl-9">
                <div class="row">
                  <div class="col-xl-10">
                    <p class="form-element-note mt-0 mb-3"> <a target="_blank" class="text-primary" href="{{ route("admin.system.setting", ["type" => "member"]) }}">{{ translate("Click here") }}</a> {{ translate(" then navigate to the 'Gateway Management' tab to update default sms method for API") }}</p>
                    <div class="accordion-wrapper api-accordion">
                      <div class="accordion">
                        <div>
                          <span class="form-label">{{ translate("Send via POST method") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="smsOne">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#smsPost" aria-expanded="false" aria-controls="smsPost"> {{route('incoming.sms.send')}} </button>
                            </h2>
                            <div id="smsPost" class="accordion-collapse collapse" aria-labelledby="smsOne" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
									<code class="language-php">
$curl = curl_init();
$postdata = array(
  "contact" = array(
    array(
        "number" => 123456789,
        "body" => "This is a test from \nxsender",
        "sms_type" => "plain",
        "gateway_identifier" : "*****************"
    ),
    array(
        "number" => 123456789,
        "body" => "This is a test from \nxsender",
        "sms_type" => "plain",
        "schedule_at" => "2024-07-10 14:39:00",
    ),
  )
);

curl_setopt_array($curl, array(
    CURLOPT_URL => '{{route('incoming.sms.send')}}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>json_encode($postdata),
    CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Sms Dispatch Request Created Successfully",
  "data": [
      {
          "id": 1,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API Test with identifier \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 2,
          "created_at": "2025-04-21 14:55:39",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 3,
          "created_at": "2025-04-21 14:55:39",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 4,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 5,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      }
  ]
}
                                        </code>
									</pre>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("Send via GET method") }}</span>
                          <div class="accordion-item">
                              <h2 class="accordion-header" id="smsQuery">
                                  <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#smsQueryGet" aria-expanded="false" aria-controls="smsQueryGet"> {{ route('incoming.sms.send.query') . '?contacts={contacts}&message={message}' }} </button>
                              </h2>
                              <div id="smsQueryGet" class="accordion-collapse collapse" aria-labelledby="smsQuery" data-bs-parent="#api-accordion">
                                  <div class="accordion-body">
                                      <p class="fs-13"> {{ translate("This PHP method uses cURL to send SMS data to an API endpoint using query parameters, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with SMS request status and logs.") }} </p>
                                      <pre>
                                          <code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => '{{route('incoming.sms.send.query')}}?contacts=123456789,987654321&message=test%20body&sms_type=plain',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
  'Api-key: ###########################,
),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Sms Dispatch Request Created Successfully",
  "data": [
      {
          "id": 1,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API Test with identifier \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 2,
          "created_at": "2025-04-21 14:55:39",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 3,
          "created_at": "2025-04-21 14:55:39",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 4,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 5,
          "created_at": "2025-04-21 14:55:39",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "sms_contact": "8912",
              "meta_data": null
          }
      }
  ]
}
                                          </code>
                                      </pre>
                                  </div>
                              </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("GET Status") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="smsTwo">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#smsGet" aria-expanded="false" aria-controls="smsGet">{{url('api/get/sms/{id}')}}</button>
                            </h2>
                            <div id="smsGet" class="accordion-collapse collapse" aria-labelledby="smsTwo" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
									<code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => '{{url('api/get/sms/{id}')}}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
    ),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Successfully Fetched Sms From Logs",
  "data": {
      "id": 4,
      "created_at": "2025-04-21 15:37:15",
      "status": "fail",
      "message": {
          "message": "test"
      },
      "contact": {
          "first_name": null,
          "last_name": null,
          "email_contact": "65478",
          "meta_data": null
      }
  }
}
                                        </code>
									</pre>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="form-element border-bottom-0 pb-0">
            <div class="row gy-3">
              <div class="col-xxl-2 col-xl-3">
                <h5 class="form-element-title">{{ translate("Whatsapp") }}</h5>
              </div>
              <div class="col-xxl-10 col-xl-9">
                <div class="row gy-3">
                  <div class="col-xl-10">
                    <div class="accordion-wrapper api-accordion">
                      <div class="accordion">
                        <div>
                          <span class="form-label">{{ translate("Send via POST method") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="whatsappOne">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#whatsappPost" aria-expanded="false" aria-controls="whatsappPost"> {{route('incoming.whatsapp.send')}} </button>
                            </h2>
                            <div id="whatsappPost" class="accordion-collapse collapse" aria-labelledby="whatsappOne" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13"> {{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
																<code class="language-php">
$curl = curl_init();
$postdata = array(
  "contact" = array(
      array(
          "number" => 123456789,
          "message" => "some *text*",
          "schedule_at" => "2024-07-10 15:30:00",
          "gateway_identifier": "**********"
      ),
      array(
          "number" => 123456789,
          "message" => "some *text*",
          "media" => "image",
          "url" => "https://some-site-example.jpg",
      ),
      array(
          "number" => 123456789,
          "message" => "some *text*",
          "media" => "audio",
          "url" => "https://some-site-example.mp3",
      ),
      array(
          "number" => 123456789,
          "message" => "some *text*",
          "media" => "video",
          "url" => "https://some-site-example.mp4",
      ),
      array(
          "number" => 123456789,
          "message" => "some *text*",
          "media" => "document",
          "url" => "https://some-site-example.doc",
          "schedule_at" => "2024-07-10 15:30:00",
      ),
  );
);

curl_setopt_array($curl, array(
    CURLOPT_URL => '{{route('incoming.whatsapp.send')}}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>json_encode($postdata),
    CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "WhatsApp Dispatch Request Created Successfully",
  "data": [
      {
          "id": 1,
          "created_at": "2025-04-21 16:28:15",
          "status": "pending",
          "message": {
              "message": "This is a API Test with identifier \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 2,
          "created_at": "2025-04-21 16:28:15",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 3,
          "created_at": "2025-04-21 16:28:16",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 4,
          "created_at": "2025-04-21 16:28:16",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 5,
          "created_at": "2025-04-21 16:28:16",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      }
  ]
}
                                    </code>
                                </pre>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("Post via GET method") }}</span>
                          <div class="accordion-item">
                              <h2 class="accordion-header" id="whatsappQuery">

                                  <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#whatsappQueryGet" aria-expanded="false" aria-controls="whatsappQueryGet"> {{ route('incoming.whatsapp.send.query') . '?contacts={contacts}&message={message}' }} </button>
                              </h2>
                              <div id="whatsappQueryGet" class="accordion-collapse collapse" aria-labelledby="whatsappQuery" data-bs-parent="#api-accordion">
                                  <div class="accordion-body">
                                      <p class="fs-13"> {{ translate("This PHP method uses cURL to send WhatsApp data to an API endpoint using query parameters, receiving a") }} <code>{{ translate("JSON response") }}</code> {{ translate("with WhatsApp request status and logs.") }} </p>
                                      <pre>
                                          <code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => '{{route('incoming.whatsapp.send.query')}}?contacts=123456789,987654321&message=test%20body',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
  'Api-key: ###########################,
),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "WhatsApp Dispatch Request Created Successfully",
  "data": [
      {
          "id": 1,
          "created_at": "2025-04-21 16:28:15",
          "status": "pending",
          "message": {
              "message": "This is a API Test with identifier \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 2,
          "created_at": "2025-04-21 16:28:15",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 3,
          "created_at": "2025-04-21 16:28:16",
          "status": "schedule",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 4,
          "created_at": "2025-04-21 16:28:16",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      },
      {
          "id": 5,
          "created_at": "2025-04-21 16:28:16",
          "status": "pending",
          "message": {
              "message": "This is a API \ntest"
          },
          "contact": {
              "first_name": null,
              "last_name": null,
              "whatsapp_contact": "8912",
              "meta_data": null
          }
      }
  ]
}
                                          </code>
                                      </pre>
                                  </div>
                              </div>
                          </div>
                        </div>
                        <div class="mt-4">
                          <span class="form-label">{{ translate("GET Status") }}</span>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="whatsappTwo">
                              <button class="accordion-button collapsed text-break" type="button" data-bs-toggle="collapse" data-bs-target="#whatsappGet" aria-expanded="false" aria-controls="whatsappGet"> {{url('api/get/whatsapp/{id}')}}</button>
                            </h2>
                            <div id="whatsappGet" class="accordion-collapse collapse" aria-labelledby="whatsappTwo" data-bs-parent="#api-accordion">
                              <div class="accordion-body">
                                <p class="fs-13">{{ translate("This PHP method uses cURL to send email data to an API endpoint, receiving a") }}<code>{{ translate(" JSON response ") }}</code> {{ translate("with email request status and logs.") }} </p>
                                <pre>
									<code class="language-php">
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => '{{url('api/get/whatsapp/{id}')}}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Api-key: ###########################,
    ),
));

$response = curl_exec($curl);
curl_close($curl);

//response will return data in this format
{
  "success": true,
  "message": "Successfully Fetched WhatsApp From Logs",
  "data": {
      "id": 1,
      "created_at": "2025-04-21 16:28:15",
      "status": "pending",
      "message": {
          "message": "This is a API Test with identifier \ntest"
      },
      "contact": {
          "first_name": null,
          "last_name": null,
          "email_contact": "8912",
          "meta_data": null
      }
  }
}
                                        </code>
                                </pre>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
@endsection
@push("script-include")
    <script src="{{asset('assets/theme/global/js/prism.js')}}"></script>
@endpush
@push('script-push')
<script>
    "use strict"

    $(document).ready(function() {

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        $('.generate-api-key').on('click', function() {
            var apiKey = generateUUID();
            $('#api_key').val(apiKey);

            $.ajax({
                type : "GET",
                url  : "{{route('admin.communication.api')}}",
                data : {_token : "{{ csrf_token() }}", api_key : apiKey},
                success:function(response) {

                    notify(response.status, response.message)
                }
            });
        });

        $('#copy_api_key').on('click', function() {

            myFunction();
        });
        function myFunction() {

            var copyText = document.getElementById("api_key");
            copyText.select();
            copyText.setSelectionRange(0, 99999)
            document.execCommand("copy");
            notify('success', 'Copied the API Key : ' + copyText.value);
        }
    });
</script>
@endpush
