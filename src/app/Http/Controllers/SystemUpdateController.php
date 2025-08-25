<?php

namespace App\Http\Controllers;

use App\Traits\InstallerManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use ZipArchive;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SystemUpdateController extends Controller
{
    use InstallerManager;

    public function __construct(){

    }

    public function init() :View {

        Session::put("menu_active", true);
        return view('admin.setting.system_update',[
            "title" => translate("Update System")
        ]);
    }


    /**
     * Summary of checkUpdate
     * @return array{data: array, message: string, success: bool|array{data: mixed, message: string, success: bool}|array{message: mixed, success: bool}|array{message: string, success: bool}}
     */
    public function checkUpdate(): array
    {
        $params = [
            'domain'            => url('/'),
            'software_id'       => config('installer.software_id'),
            'version'           => config('installer.version'),
            'purchase_key'      => env('PURCHASE_KEY'),
            'envato_username'   => env('ENVATO_USERNAME')
        ];

        try {
            $url = 'https://verifylicense.online/api/licence-verification/get-update-versions';
            $response = Http::post($url, $params);
            $data = $response->json();
            

            if (!isset($data['success'], $data['code'], $data['message'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid API response structure',
                ];
            }

            if ($data['success'] === true) {
                if (!empty($data['data'])) {
                    return [
                        'success' => true,
                        'message' => 'Update available',
                        'data' => $data['data'],
                    ];
                } else {
                    return [
                        'success' => true,
                        'message' => 'No updates available',
                        'data' => [],
                    ];
                }
            }

            $errorMessage = $data['message'] ?? 'Unknown error';
            if (isset($data['data']['errors'])) {
                $errors = $data['data']['errors'];
                $errorMessage .= ': ' . json_encode($errors);
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Exception $e) {
            
            return [
                'success' => false,
                'message' => 'Failed to connect to API: ' . $e->getMessage(),
            ];
        }
    }


    public function installUpdate(Request $request)
    {
        $params = [
            'domain'            => url('/'),
            'software_id'       => config('installer.software_id'),
            'version'           => $request->input('version'),
            'purchase_key'      => env('PURCHASE_KEY'),
            'envato_username'   => env('ENVATO_USERNAME')
        ];

        $status = false;

        try{

            $url        = 'https://verifylicense.online/api/licence-verification/download-version';
            $response   = Http::post($url, $params);
            $data       = $response->json();

            if($response->successful()){
                $basePath = base_path('/storage/app/public/temp_update/');
                if(!file_exists($basePath))   mkdir($basePath, 0777);


                $filename = 'default_update.zip';
                if ($response->hasHeader('Content-Disposition')) {
                    $disposition = $response->header('Content-Disposition');
                    if (preg_match('/filename="(.+?)"/', $disposition, $matches)) {
                        $filename = $matches[1];
                    }
                }

                $filePath = $basePath . '/' . $filename;

                file_put_contents($filePath, $response->body());

                $zip = new ZipArchive;
                $res = $zip->open($filePath);

                if (!$res){
                    $this->deleteDirectory($basePath);

                    $updateResponse = [
                        'success' => false,
                        'message' => translate('Error! Could not open File')
                    ];

                    return $updateResponse;

                }


                $zip->extractTo($basePath);

                $zip->close();

                $configFilePath = $basePath.'config.json';
                $configJson = json_decode(file_get_contents($configFilePath), true);

                if (empty($configJson) || empty($configJson['version'])) {
                    $this->deleteDirectory($basePath );

                    $updateResponse = [
                        'success' => false,
                        'message' => translate('Error! No Configuration file found')
                    ];

                    return $updateResponse;
                }


                $newVersion      = (double) $configJson['version'];
                $currentVersion  = (double) @site_settings("app_version")?? 1.1;



                $src = $basePath;
                $dst = dirname(base_path());


                if($newVersion  > $currentVersion){

                    $message = translate('Your system updated successfully');
                    $status  = true;



                    if($this->copyDirectory($src, $dst)){

                        $this->_runMigrations($configJson);
                        $this->_runSeeder($configJson);
                        DB::table('settings')->upsert([
                            ['key' => 'app_version', 'value' => $newVersion],
                            ['key' => 'system_installed_at', 'value' =>  Carbon::now()],
                        ], ['key'], ['value']);


                    }
                }


            }

            $errorMessage = $data['message'] ?? 'Unknown error';
            if (isset($data['data']['errors'])) {
                $errors = $data['data']['errors'];
                $errorMessage .= ': ' . json_encode($errors);
            }

            if(isset($data['data']['error'])){
                $errorMessage =  $data['data']['error'];
            }

        }catch(\Exception $e){

        }

        $updateResponse = [
            'success' => $status,
            'message' => $message ?? $errorMessage
        ];


        optimize_clear();
        $this->deleteDirectory($basePath );


        return $updateResponse;


    }



    /**
     * update the system
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request) :RedirectResponse {

        ini_set('memory_limit', '-1');
        ini_set('max_input_time', '300');
        ini_set('max_execution_time', '300');
        ini_set('upload_max_filesize', '1G');
        ini_set('post_max_size', '1G');

        $request->validate([
            'updateFile' => ['required', 'mimes:zip'],
        ],[
            'updateFile.required' => translate('File field is required')
        ]);
        $response = [];
        $response[] = response_status(translate('Your system is currently running the latest version.'),'error');

        try {
            if ($request->hasFile('updateFile')) {

                $zipFile = $request->file('updateFile');
                $basePath = base_path('/storage/app/public/temp_update/');
                if(!file_exists($basePath))   mkdir($basePath, 0777);

                $zipFile->move($basePath, $zipFile->getClientOriginalName());

                $zip = new ZipArchive;
                $res = $zip->open($basePath . $zipFile->getClientOriginalName());

                if (!$res){
                    $this->deleteDirectory($basePath);
                    return back()->with("error",translate('Error! Could not open File'));
                }


                $zip->extractTo($basePath);
                $zip->close();
                // Read configuration file
                $configFilePath = $basePath.'config.json';
                $configJson = json_decode(file_get_contents($configFilePath), true);

                if (empty($configJson) || empty($configJson['version'])) {
                    $this->deleteDirectory($basePath );
                    return back()->with("error",translate('Error! No Configuration file found'));
                }


                $newVersion      = (double) $configJson['version'];
                $currentVersion  = (double) @site_settings("app_version")?? 1.1;


                $src = storage_path('app/public/temp_update');
                $dst = dirname(base_path());

                if($newVersion  > $currentVersion){
                    $response = [];
                    $response[] = response_status(translate('Your system updated successfully'));

                    if($this->copyDirectory($src, $dst)){

                        $this->_runMigrations($configJson);
                        $this->_runSeeder($configJson);
                        DB::table('settings')->upsert([
                            ['key' => 'app_version', 'value' => $newVersion],
                            ['key' => 'system_installed_at', 'value' =>  Carbon::now()],
                        ], ['key'], ['value']);

                        $this->deleteDirectory($basePath );

                    }
                }
            }
        } catch (\Exception $ex) {
            
            $response = [];
            $response[] = response_status(strip_tags($ex->getMessage()),'error');
            
        }
        
        optimize_clear();
        $this->deleteDirectory($basePath);
        return redirect()->back()->withNotify($response);
    }


    private function _runMigrations(array $json) :void{

        $migrations = Arr::get($json , 'migrations' ,default: []);
        if(count($migrations) > 0){
            $migrationFiles = $this->_getFormattedFiles($migrations);
            foreach ($migrationFiles as $migration) {
                Artisan::call('migrate',
                    array(
                        '--path' => $migration,
                        '--force' => true));
            }
        }
    }

    private function _runSeeder(array $json) :void{

        $seeders = Arr::get($json , 'seeder' ,[]);

        if(count($seeders) > 0){
            $seederFiles = $this->_getFormattedFiles($seeders);
            foreach ($seederFiles as $seeder) {
                Artisan::call('db:seed',
                    array(
                        '--class' => $seeder,
                        '--force' => true));
            }
        }
    }

    private function _getFormattedFiles (array $files) :array{

        $currentVersion  = (double) site_settings(key : "app_version",default :1.1);
        $formattedFiles = [];
        foreach($files as $version => $file){
           if(version_compare($version, (string)$currentVersion, '>')){
              $formattedFiles [] =  $file;
           }
        }

        return array_unique(Arr::collapse($formattedFiles));

    }



    /**
     * Copy directory
     *
     * @param string $src
     * @param string $dst
     * @return boolean
     */
    public function copyDirectory(string $src, string $dst) :bool {

        try {
            $dir = opendir($src);
            @mkdir($dst);
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        } catch (\Exception $e) {
           return false;
        }

        return true;
    }



    /**
     * delete directory
     *
     * @param string $dirname
     * @return boolean
     */
    public function deleteDirectory(string $dirname) :bool {

        try{
            if (!is_dir($dirname)){
                return false;
            }
            $dir_handle = opendir($dirname);

            if (!$dir_handle)
                return false;
            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    if (!is_dir($dirname . "/" . $file))
                        unlink($dirname . "/" . $file);
                    else
                        $this->deleteDirectory($dirname . '/' . $file);
                }
            }
            closedir($dir_handle);
            rmdir($dirname);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }


    public function removeDirectory($basePath) {

        if (File::exists($basePath)) {
            File::deleteDirectory($basePath);
        }
    }

}
