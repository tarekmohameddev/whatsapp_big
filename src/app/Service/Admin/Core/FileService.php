<?php

namespace App\Service\Admin\Core;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Enums\StatusEnum;
use App\Models\ContactGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\HeadingRowImport;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\File as FileFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileService
{
    
    /**
     * addContactFile
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $originalFileName
     * @param int $groupId
     * @param User|null $user
     * 
     * @return File
     */
    public function addContactFile(string $filePath, string $fileName, string $originalFileName, int $groupId, ?User $user = null): File
    {
        $fullPath   = $filePath . '/' . $fileName;
        
        $size       = FileFacade::exists($fullPath) 
                    ? FileFacade::size($fullPath) 
                        : 0;
        $mime       = FileFacade::mimeType($fullPath) 
                        ?: 'application/octet-stream';

        return File::create([
            'user_id'       => @$user?->id,
            'fileable_type' => ContactGroup::class,
            'fileable_id'   => $groupId,
            'path'          => $filePath,
            'name'          => $fileName,
            'mime_type'     => $mime,
            'size'          => $size,
            'meta_data'     => null,
        ]);
    }

    /**
     * unlinkFile
     *
     * @param string|null $filename
     * @param string|null $file_path
     * 
     * @return bool
     */
    public function unlinkFile(?string $filename, ?string $file_path = null): bool
    {
        if (!$filename) {
            return true; 
        }

        $config = config('setting.file_path');
        $filePath = $file_path ?? $config['profile']['user']['path'] ?? 'uploads/profile/';

        $fileDestination = rtrim($filePath, '/') . '/' . $filename;

        if (file_exists($fileDestination) && is_file($fileDestination)) {
            return unlink($fileDestination);
        }

        return true; 
    }
    
    
    
    
    
    
    
    
    ## Old functions



   /**
     * @param \Illuminate\Http\UploadedFile $file
     * 
     * @param string $key
     * 
     * @return string|null
     * 
     */
    public function uploadFile(UploadedFile $file, $key = null, $file_path = null, $file_size = null, $delete_file = true)
    {
        $config = config('setting.file_path');
        $filePath = $config[$key]['path'] ?? $file_path;
        $fileSize = $config[$key]['size'] ?? $file_size;
        if ($filePath) {
            if (!file_exists($filePath)) {
                mkdir($filePath, 0755, true);
            }

            $file_name = uniqid() . time() . '.' . $file->getClientOriginalExtension();
            $fileDestination = $filePath . '/' . $file_name;

            $oldFiles = glob($filePath . '/*');
            if ($oldFiles && $delete_file) {
                collect($oldFiles)->each(function ($oldFile) {
                    unlink($oldFile);
                });
            }

            // Check if the file is an SVG
            if ($file->getClientOriginalExtension() === 'svg') {
                $file->move($filePath, $file_name);
            } else {
                // Check if Intervention Image v3 is available
                if (class_exists(ImageManager::class)) {
                    // Intervention Image v3
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($file->getRealPath());

                    if ($fileSize) {
                        $size = explode('x', strtolower($fileSize));
                        $image->resize($size[0], $size[1], function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    if (site_settings("store_as_webp") == StatusEnum::TRUE->status() && in_array($file->getClientOriginalExtension(), ['jpeg', 'jpg', 'png', 'gif'])) {
                        $image->encode(new WebpEncoder(quality: 90));
                        $file_name = str_replace('.' . $file->getClientOriginalExtension(), '.webp', $file_name);
                        $fileDestination = $filePath . '/' . $file_name;
                    }

                    $image->save($fileDestination);
                } else {
                    // Intervention Image v2
                    $image = Image::make($file->getRealPath());

                    if ($fileSize) {
                        $size = explode('x', strtolower($fileSize));
                        $image->resize($size[0], $size[1], function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    if (site_settings("store_as_webp") == StatusEnum::TRUE->status() && in_array($file->getClientOriginalExtension(), ['jpeg', 'jpg', 'png', 'gif'])) {
                        $image->encode('webp', 90);
                        $file_name = str_replace('.' . $file->getClientOriginalExtension(), '.webp', $file_name);
                        $fileDestination = $filePath . '/' . $file_name;
                    }

                    $image->save($fileDestination);
                }
            }

            return $file_name;
        }
        return null;
    }



    /**
     * generateContactDemo
     *
     * @param mixed $type
     * @param array $condition_exlude
     * @param bool $allow_attribute
     * @param bool $single_channel_demo
     * 
     * @return BinaryFileResponse
     */
    public function generateContactDemo($type, $condition_exlude = [], $allow_attribute = false, $single_channel_demo = false):BinaryFileResponse {

        $contact_columns = Schema::getColumnListing('contacts');
        $first_name_key = array_search("first_name", $contact_columns);
        if ($first_name_key !== false) {

            unset($contact_columns[$first_name_key]);
            array_unshift($contact_columns, "first_name");
        }

        $columns_to_exclude = [
            'email_verification',
            'attributes', 
            'created_at', 
            'group_id', 
            'id', 
            'status', 
            'uid', 
            'updated_at', 
            'user_id'
        ];
        $columns_to_exclude = array_merge($columns_to_exclude, $condition_exlude);
        $contact_columns   = array_diff($contact_columns, $columns_to_exclude);
        if($allow_attribute) {

            $attributes         = json_decode(site_settings("contact_meta_data"));   
            $filteredAttributes = collect($attributes)->filter(function ($attribute) {

                return $attribute->status === true;
            })->toArray();
            $columns = array_keys($filteredAttributes);
            $attribute_type = collect($filteredAttributes)->map(function ($attribute) {
                return $attribute->type ?? null;
            })->toArray();

            $contact_columns = array_merge($contact_columns, $columns);
        }
			
        $data = $this->getData($contact_columns, [], $allow_attribute, $single_channel_demo); 
  
        if ($type == 'csv') {
            
            $filePath = $this->generateCsvFile([$data]);
        } elseif ($type == 'xlsx') {

            $filePath = $this->generateExcelFile($data);
        } else {

            throw new \InvalidArgumentException('Unsupported file type.');
        }
        return response()->download($filePath);
    }

    function prepareExportData($data, $data_config): array {

        $data_csv_rows = [];
        $headerRow = ['serial_number'];

        foreach ($data_config as $key => $config) {

            if ($config['type'] === 'object') {
                foreach ($config['format'] as $objectKey => $objectConfig) {

                    $headerRow[] = $objectKey;
                }
            } else {

                $headerRow[] = $key;
            }
        }
        $data_csv_rows[] = $headerRow;
        foreach ($data as $index => $contact) {

            $row = [];
            $row['serial_number'] = $index + 1;
            foreach ($data_config as $key => $config) {
                if ($config['type'] === 'object') {
                    foreach ($config['format'] as $objectKey => $objectConfig) {
                        $row[$objectKey] = $contact->meta_data->$objectKey->data ?? 'N/A';
                    }
                } elseif ($config['type'] === 'datetime') {

                    $row[$key] = isset($contact->$key) ? Carbon::parse($contact->$key)->toDayDateTimeString() : 'N/A';
                } else {
                    $row[$key] = $contact->$key ?? 'N/A';
                }
            }
            $data_csv_rows[] = $row;
        }

        return $data_csv_rows;
    }

    function generateCsvFile($data, $location = "contact_demo",  $file_name = "demo.csv") { 

		$file_name = $file_name;
		$file      = Excel::store(new \App\Exports\ContactDemoExport($data), $file_name, $location, \Maatwebsite\Excel\Excel::CSV);
		$files     = FileFacade::files(config("filesystems.disks.$location.root"));
		foreach ($files as $file) {

			if (pathinfo($file, PATHINFO_EXTENSION) == "csv" && basename($file) != $file_name) {

				FileFacade::delete($file);
			}
		}
		if($file) {

            return config("filesystems.disks.$location.root")."/".$file_name;
		}
	}

	public function generateExcelFile($data) {

		$data = [ $data ];
		$file_name = "demo.xlsx";
		$file = Excel::store(new \App\Exports\ContactDemoExport($data), $file_name, "contact_demo", \Maatwebsite\Excel\Excel::XLSX);
		$files = File::files(config("filesystems.disks.contact_demo.root"));
		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) == "xlsx" && basename($file) != $file_name) {

				FileFacade::delete($file);
			}
		}
		if($file) {
			return config("filesystems.disks.contact_demo.root")."/".$file_name;
		}
	}

	public function getData($contact_columns, $attribute_type, $allow_attribute, $single_channel_demo) {
		
		$data = [];
		foreach($contact_columns as $column) {
            
            if($column == "full_name" ) {
                
                $data[textFormat(["_"], $column)] = generateText("first_name")." ".generateText("last_name");
			}
			if($column == "first_name" ) {
                
                $data[$column] = generateText("first_name");
			}
			if($column == "last_name" ) {
                
                $data[$column] = generateText("last_name");
			}
			if($column == "email_contact" ) {
                
                $data[$single_channel_demo ? 'contact' : 'email_contact'] = generateText("email");
			}
			if($column == "sms_contact" ) {
                
                $data[$single_channel_demo ? 'contact' : 'sms_contact'] = (string)rand(10000000, 99999999);
			}
			if($column == "whatsapp_contact" ) {
                
                $data[$single_channel_demo ? 'contact' : 'whatsapp_contact'] = (string)rand(10000000, 99999999);
			}
		}

		if($allow_attribute) {

			foreach($attribute_type as $attribute_name => $type) {
				
				if ($type == \App\Models\GeneralSetting::DATE) {

					$data[textFormat(["_"], $attribute_name)] = (string)now()->addDays(rand(1, 30))->toDateTimeString();
				}
				
				if ($type == \App\Models\GeneralSetting::BOOLEAN) {

					$data[textFormat(["_"], $attribute_name)] = (rand(0, 1) == 1) ? 'Yes' : 'No';
				}
				
				if ($type == \App\Models\GeneralSetting::NUMBER) {

					$data[textFormat(["_"], $attribute_name)] = (string)rand(100000, 999999);
				}
				
				if ($type == \App\Models\GeneralSetting::TEXT) {

					$data[textFormat(["_"], $attribute_name)] = generateText("object");
				}
			}
		}
        
		return $data;
	}

    public function uploadContactFile($file) {

        $extension = $file->getClientOriginalExtension();
        $directory = "assets/file/contact/temporary";
        if (!FileFacade::isDirectory($directory)) {

            FileFacade::makeDirectory($directory, 0755, true, true);
        }
        $file_name = 'temp_' . time() . '.' . $extension;
        $file->move($directory, $file_name);
        $filePath = "assets/file/contact/temporary/{$file_name}";

        return [
            $file_name,
            $filePath
        ];
    }

    public function deleteContactFile($file_name): JsonResponse {
        
        $status   = false;
        $message  = translate("File Not Found");  
        $filePath = "assets/file/contact/temporary/{$file_name}";
        
        if(FileFacade::exists($filePath)) {

            FileFacade::delete($filePath);
            $status  = true;
            $message = null;
        } 

        return response()->json([

            'status'  => $status, 
            'message' => $message
        ]);
    }

    /**
     * parseContactFile
     *
     * @param mixed $filePath
     * 
     * @return JsonResponse
     */
    public function parseContactFile($filePath): JsonResponse {

        return response()->json([
            "data" => $this->parseData($filePath)
        ]);
    }

    /**
     * parseData
     *
     * @param mixed $filePath
     * 
     * @return array
     */
    private function parseData($filePath): array {

        $data = (new HeadingRowImport)->toArray($filePath);
        $headerRow = $data[0][0];
        $headers = array_combine(array_map([$this, 'getExcelColumnName'], range(1, count($headerRow))), $headerRow);
        
        return $headers;
    }

    /**
     * getExcelColumnName
     *
     * @param int $columnNumber
     * 
     * @return string
     */
    private function getExcelColumnName(int $columnNumber): string {

        $dividend = $columnNumber;
        $columnName = '';
       
        while ($dividend > 0) {
           
            $modulo = ($dividend - 1) % 26;
            $columnName = chr(65 + $modulo) . $columnName;
            $dividend = (int)(($dividend - $modulo) / 26);
        }
        return $columnName . '1';
    }

    public function readCsv($contacts, $custom_gateway = null) {

        $meta_data = [];
        $filePath  = $contacts->getPathname();
        $file      = fopen($filePath, 'r');
        $headers   = fgetcsv($file);
        $status    = 'success';
        $message   = translate("Successfully fetched contact from the file");
        $expected_headers = ['first_name', 'contact'];
    
        if ($headers !== $expected_headers) {
            $status  = 'error';
            $message = translate("Unsupported CSV file format");
            fclose($file);
            return [
                $status,
                $message,
                $meta_data,
            ];
        }
        while (($row = fgetcsv($file)) !== false) {
    
            $data    = array_combine($headers, $row);
            $contact = [];
            $valid   = true;
    
            foreach ($data as $key => $value) {

                $formatted_key = strtolower(textFormat([' '], $key, '_'));
                $contact[$formatted_key] = $value;
                $contact["custom_gateway_parameter"] = $custom_gateway;
            }
    
            if ($valid) {
                $meta_data[] = $contact;
            }
        }
    
        fclose($file);
        return [
            $status,
            $message,
            $meta_data,
        ];
    }
}