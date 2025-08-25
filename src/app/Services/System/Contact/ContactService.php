<?php

namespace App\Services\System\Contact;

use Carbon\Carbon;
use SplFileObject;
use App\Models\User;
use App\Models\File;
use App\Models\Contact;
use App\Enums\StatusEnum;
use App\Enums\SettingKey;
use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\Common\Status;
use App\Models\ContactGroup;
use Illuminate\Http\Response;
use App\Models\ContactImport;
use App\Jobs\ImportContactJob;
use App\Traits\CollectionTrait;
use App\Managers\ContactManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use App\Services\Core\MailService;
use Illuminate\Support\Facades\DB;
use App\Enums\ContactAttributeEnum;
use Illuminate\Http\RedirectResponse;
use App\Enums\System\ChannelTypeEnum;
use Illuminate\Support\LazyCollection;
use App\Service\Admin\Core\FileService;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ApplicationException;
use Illuminate\Database\Eloquent\Builder;
use App\Service\Admin\Core\SettingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContactService
{ 
    use CollectionTrait; 

    protected FileService $fileService;
    protected MailService $mailService;
    protected ContactManager $contactManager;
    protected SettingService $settingService;

    public function __construct()
    {
        $this->settingService = new SettingService;
        $this->fileService    = new FileService;
        $this->contactManager = new ContactManager;
        $this->mailService    = new MailService;
    }

    ## ------------------ ##
    ## Contact Attribute ##
    ## ----------------- ##

    /**
     * getContactAttributes
     *
     * @param User|null $user
     * 
     * @return View
     */
    public function getContactAttributes(?User $user = null): View {

        $title = translate("Manage Contact Attributes");
        $contactAttributes  = $user 
                                ? $user->contact_meta_data 
                                : site_settings(SettingKey::CONTACT_META_DATA->value, []);
                         
        $collection         = $this->collect(json_decode($contactAttributes, true));

        $contactAttributes  = $this->paginate(
            $this->filterByKey(
                $this->searchCollection($collection),
                last(explode('.', request()->route()->getName()))
            ),
            paginateNumber(site_settings(SettingKey::PAGINATE_NUMBER->value))
        );
        
        return view($user ? 'user.contact.settings.index' : 'admin.contact.settings.index', compact('title', 'contactAttributes'));
    }

    /**
     * saveContactAttributes
     *
     * @param array $data
     * @param User|null $user
     * 
     * @return array
     */
    public function saveContactAttributes(array $data, ?User $user = null): array {
        
        $new_attribute_name = strtolower(str_replace(' ', '_', $data["attribute_name"]));
        
        $new_data[SettingKey::CONTACT_META_DATA->value] = [
            $new_attribute_name => [
                "type"   => (int) Arr::get($data, "attribute_type"),
                "status" => Status::ACTIVE->value,
            ]
        ];
        
        $old_data = $user 
            ? json_decode($user->contact_meta_data, true) 
            : json_decode(site_settings(SettingKey::CONTACT_META_DATA->value, []), true);
    
        if (isset($data["old_attribute_name"])) {
            $old_attribute_name = strtolower(str_replace(' ', '_', $data["old_attribute_name"]));
            
            if (isset($old_data[$old_attribute_name])) {
                $old_data[$new_attribute_name] = array_merge($old_data[$old_attribute_name], $new_data['contact_meta_data'][$new_attribute_name]);
                
                if ($old_attribute_name !== $new_attribute_name) {
                    unset($old_data[$old_attribute_name]);
                }
            }
        } else {
            
            $old_data[$new_attribute_name] = $new_data['contact_meta_data'][$new_attribute_name];
        }
    
        $final_data['contact_meta_data'] = $old_data;
        return $final_data;
    }   

    /**
     * deleteContactAttributes
     *
     * @param array $data
     * @param User|null $user
     * 
     * @return array
     */
    public function deleteContactAttributes(array $data, ?User $user = null): array {
        
        $attribute_name = strtolower(str_replace(' ', '_', $data["attribute_name"]));
        $old_data       = $user ? json_decode($user->contact_meta_data, true) : json_decode(site_settings('contact_meta_data'), true);
        unset($old_data[$attribute_name]);
        $final_data['contact_meta_data'] = $old_data;
        return $final_data;
    }

    /**
     * contactAttributeStatusUpdate
     *
     * @param Request $request
     * @param User|null $user
     * 
     * @return JsonResponse
     */
    public function contactAttributeStatusUpdate(Request $request, ?User $user = null): JsonResponse|ApplicationException {

        $contactAttributes  = [];

        if(!$user) $contactAttributes   = site_settings(SettingKey::CONTACT_META_DATA->value, []);
        if($user) $contactAttributes    = $user->contact_meta_data;
        
        $contactAttributes  = json_decode($contactAttributes, true);
        $attributeName      = $request->input('name');
        $attribute          = Arr::get($contactAttributes, $attributeName);
        
        if (is_null($attribute)) throw new ApplicationException("Attribute not found", Response::HTTP_NOT_FOUND);
        
        $updatedStatus = Arr::get($attribute, "status") == StatusEnum::TRUE->status() 
                            ||  Arr::get($attribute, "status") == Status::ACTIVE->value
                                ? Status::INACTIVE->value
                                : Status::ACTIVE->value;
        Arr::set($attribute, "status", $updatedStatus);
        Arr::set($contactAttributes, $attributeName, $attribute);
        
        if($user) {

            $user->contact_meta_data = $contactAttributes;
            $user->save();
        } else {

            $this->settingService->updateSettings([
                SettingKey::CONTACT_META_DATA->value => $contactAttributes
            ]);
        }

        return response()->json([
            'reload'    => true,
            'status'    => true,
            'message'   => translate('Contact Attribute status updated successfully'),
        ]);
    }

    ## ------------- ##
    ## Contact Group ##
    ## ------------- ##

    /**
     * getContactGroups
     *
     * @param string|null $uid
     * @param User|null $user
     * 
     * @return View
     */
    public function getContactGroups(string|null $uid = null, ?User $user = null): View {
        
        $title          = translate("Manage Contact Groups");
        $contactGroups  = ContactGroup::date()
                                        ->search(['name'])
                                        ->filter(['status'])
                                        ->latest()
                                        ->when($uid, fn(Builder $q): Builder =>
                                            $q->where("uid", $uid))
                                        ->when($user, fn(Builder $q): Builder =>
                                            $q->where("user_id", $user->id),
                                                fn(Builder $q): Builder =>
                                                    $q->whereNull("user_id"))
                                        ->paginate(paginateNumber(site_settings(SettingKey::PAGINATE_NUMBER->value, 10)))
                                        ->onEachSide(1)
                                        ->appends(request()->all());
        
        return view($user 
                            ? "user.contact.groups.index" 
                            : 'admin.contact.groups.index', 
            compact('title', 'contactGroups'));
    }

    /**
     * saveContactGroups
     *
     * @param array $data
     * @param string|null $uid
     * @param User|null $user
     * 
     * @return RedirectResponse
     */
    public function saveContactGroups(array $data, ?string $uid = null, ?User $user = null): RedirectResponse {

        $contactGroup = null;

        if($uid) {

            $contactGroup = ContactGroup::when($user, 
                                            fn(Builder $q): Builder =>
                                                $q->where("user_id", $user->id), 
                                                    fn(Builder $q): Builder =>
                                                        $q->admin())
                                            ->when($uid, fn(Builder $q): Builder =>
                                                $q->where("uid", $uid))
                                            ->first();
            if(!$contactGroup) throw new ApplicationException("Contact group not found", Response::HTTP_NOT_FOUND);
        }
        
        $contactGroup = $contactGroup 
                            ? $contactGroup
                            : new ContactGroup();
        $contactGroup->user_id  = @$user ? $user->id : null;
        $contactGroup->name     = Arr::get($data, "name");
        $contactGroup->save();
        $notify[] = ['success', translate("Contact group updated successfully")];
        return back()->withNotify($notify);
    }

    /**
     * deleteContactGroup
     *
     * @param array $data
     * @param string|null $uid
     * @param User|null $user
     * 
     * @return RedirectResponse
     */
    public function deleteContactGroup(array $data, ?string $uid = null, ?User $user = null): RedirectResponse
    {
        $contactGroup = ContactGroup::when($user, 
                fn(Builder $q): Builder => $q->where("user_id", $user->id), 
                fn(Builder $q): Builder => $q->whereNull("user_id") 
            )->where("uid", $uid)
            ->first();
    
        if (!$contactGroup) {
            throw new ApplicationException("Contact group not found", Response::HTTP_NOT_FOUND);
        }
    
        try {
            DB::table('contacts')
                ->where('group_id', $contactGroup->id)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('dispatch_logs')
                        ->whereColumn('dispatch_logs.contact_id', 'contacts.id');
                })
                ->delete();
    
            $contactGroup->delete();
    
            $notify[] = ['success', translate("Contact group deleted successfully")];
            return back()->withNotify($notify);
        } catch (\Exception $e) {
            throw new ApplicationException("Failed to delete contact group", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    ## ------- ##
    ## Contact ##
    ## ------- ##

    /**
     * getContacts
     *
     * @param int|string|null $groupId
     * @param User|null $user
     * 
     * @return View
     */
    public function getContacts(int|string|null $groupId, ?User $user = null): View {

        $title              = translate("Contact List");
        $contactMetaData    = $user 
                                ? $user->contact_meta_data
                                : site_settings(SettingKey::CONTACT_META_DATA->value, []);
        $contactMetaData    = json_decode($contactMetaData, true);
        $filtered_meta_data = $this->filterMetaData($contactMetaData, Status::ACTIVE->value);
        $contacts           = $this->fetchContacts(groupId: $groupId, user:$user); 
        $groups             = $this->pluckContactGroup("name", "id", $user);
        $csv_data           = $this->getCsvExportData($groupId, $user);
        
        return view($user ? "user.contact.index" :'admin.contact.index', 
        compact('title', 'contacts', 'filtered_meta_data', 'groups', 'groupId', 'csv_data'));
    }

     /**
     * filterMetaData
     *
     * @param array|null $contact_meta_data
     * @param string $status
     * 
     * @return array
     */
    private function filterMetaData(array|null $contact_meta_data, string $status): array {
        
        return collect($contact_meta_data)
                ->filter(function ($meta_data) use($status) {
                        $meta_data = (object) $meta_data;
                        
                        return @$meta_data?->status == $status;
                    })->toArray();
    }

    /**
     * fetchContacts
     *
     * @param bool $export
     * @param int|string|null|null $groupId
     * @param User|null $user
     * 
     * @return Collection
     */
    private function fetchContacts(bool $export = false, int|string|null $groupId = null, ?User $user = null): Collection|LengthAwarePaginator {

        return Contact::when($user, fn(Builder $q): Builder => 
                    $q->where('user_id', $user->id), 
                        fn(Builder $q) : Builder =>
                            $q->admin())
                            ->when($groupId, fn(Builder $q): Builder => 
                            $q->where('group_id', $groupId))
                ->search([
                    'first_name|last_name',
                    'first_name',
                    'last_name',
                    'whatsapp_contact',
                    'email_contact',
                    'sms_contact'
                ])
                ->filter(['status', 'email_verification'])
                ->latest()
                ->date()
                ->with(['group'])
                ->when(
                    $export,
                    fn(Builder $q): Collection => $q->get(),
                    fn(Builder $q): LengthAwarePaginator => 
                        $q->paginate(paginateNumber(site_settings('paginate_number')))
                            ->onEachSide(1)
                            ->appends(request()->all())
                );
    }

    /**
     * createContact
     *
     * @param int|string|null|null $groupId
     * @param User|null $user
     * 
     * @return View
     */
    public function createContact(int|string|null $groupId = null, ?User $user = null): View {
        
        $title              = translate("Add Contacts");
        $contactMetaData    = $user 
                                ? $user->contact_meta_data
                                : site_settings(SettingKey::CONTACT_META_DATA->value, []);
                                
        $contactMetaData    = json_decode($contactMetaData, true);
        
        $filtered_meta_data = $this->filterMetaData($contactMetaData, Status::ACTIVE->value);
        $groups = $this->pluckContactGroup("name", "id", $user);

        return view($user ? "user.contact.create" : 'admin.contact.create', 
            compact(
                'title', 
                'filtered_meta_data', 
                'groups', 
                'groupId'));
    }

    /**
     * exportContacts
     *
     * @param Request $request
     * @param int|string|null $groupId
     * 
     * @return BinaryFileResponse
     */
    public function exportContacts(Request $request, int|string|null $groupId): BinaryFileResponse { 

        $file_name      = 'contacts_export_' . Carbon::now()->format('Y_m_d_His') . '.csv';
        $data_config    = json_decode($request->input('data_config'), true);
        $contacts       = $this->fetchContacts(true, $groupId);
        $data           = $this->fileService->prepareExportData($contacts, $data_config);
        $csv_file_path  = $this->fileService->generateCsvFile($data, "contact_exports", $file_name);

        $headers = [
            'X-Status'   => 'true',
            'X-Message'  => translate("Successfully generated contact CSV file"),
            'X-Filename' => $file_name 
        ];
        return response()->download($csv_file_path, $file_name, $headers);
    }

    /**
     * Save a contact (single or bulk).
     *
     * @param array $data
     * @param User|null $user
     * @return RedirectResponse
     */
    public function contactSave(array $data, ?User $user = null): RedirectResponse
    {
        $this->contactManager->validateContactGroup($data, $user);
        $isBulk = Arr::get($data, "single_contact") == "false";
        if ($isBulk) return $this->saveBulkContact($data, $user);
        return $this->saveSingleContact($data, $user);
    }

    /**
     * saveSingleContact
     *
     * @param array $data
     * @param User|null $user
     * 
     * @return RedirectResponse
     */
    public function saveSingleContact(array $data, ?User $user): RedirectResponse {
        
        $email          = Arr::get($data, "email_contact");  
        if($email && site_settings(SettingKey::EMAIL_CONTACT_VERIFICATION->value) == StatusEnum::TRUE->status()) {
            
            $result = $this->mailService->verifyEmail($email);
            $isValid = Arr::get($result, "valid");
            $data['email_verification'] = $isValid ? "verified" : "unverified";
        }
        $data["user_id"]   = @$user ? $user->id : null;
        $data["meta_data"] = $this->contactMetaData($data);
        unset($data["single_contact"]);
        
        $this->contactManager->updateOrCreate($data);
        $this->contactManager->updateGroupMetaData($data);

        $notify[] = ["success", translate('Single contact saved successfully')];
        return back()->withNotify($notify);
    }

    /**
     * contactMetaData
     *
     * @param array $data
     * 
     * @return array
     */
    public function contactMetaData(array $data): array|null {
         
        $metaData = Arr::get($data, "meta_data");
        if (!$metaData) return null; 
    
        $refinedAttribute = collect($metaData)
                                ->mapWithKeys(function ($value, $key) {

                                    if(!$value) return [];

                                    $keyParts = explode("::", $key);
                                    return [
                                        $keyParts[0] => [
                                            "value" => $value,
                                            "type"  => Arr::get($keyParts, 1) 
                                        ]
                                    ];
                                })->toArray();
    
        return $refinedAttribute;
    }

    /**
     * saveBulkContact
     *
     * @param array $data
     * @param User|null $user
     * 
     * @return RedirectResponse
     */
    public function saveBulkContact(array $data, ?User $user = null): RedirectResponse
    {
        $groupId = Arr::get($data, 'group_id');
        $this->contactManager->checkExistingImport($groupId);
        $fileDetails = $this->prepareFileDetails($data);
        $totalContacts = $this->countCsvContacts(Arr::get($fileDetails,'fullPath'), Arr::get($fileDetails,'newRow'));

        $contactImport = $this->createFileAndImport(
            Arr::get($fileDetails,'filePath'),
            Arr::get($fileDetails,'fileName'),
            Arr::get($fileDetails,'originalFileName'),
            $groupId,
            $user,
            $totalContacts,
            $data
        );

        $this->dispatchImportJob($contactImport->id);

        $notify[] = ['success', translate('Your contact upload request is being processed')];
        return back()->withNotify($notify);
    }

    /**
     * Prepare file details for processing.
     *
     * @param array $data
     * @return array
     */
    protected function prepareFileDetails(array $data): array
    {
        $filePath           = filePath()['contact']['path'];
        $fileName           = Arr::get($data, 'file__name', '');
        $originalFileName   = Arr::get($data, 'file', '');
        $newRow             = Arr::get($data, 'new_row') === 'true';
        $fullPath           = $filePath . '/' . $fileName;

        return [
            'filePath'          => $filePath,
            'fileName'          => $fileName,
            'originalFileName'  => $originalFileName,
            'newRow'            => $newRow,
            'fullPath'          => $fullPath,
        ];
    }

    /**
     * Count the number of contacts in a CSV file efficiently.
     *
     * @param string $filePath
     * @param bool $includeHeaderAsRow
     * @return int
     */
    protected function countCsvContacts(string $filePath, bool $includeHeaderAsRow): int
    {
        if (!file_exists($filePath)) return 0;
        
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);

        $lineCount = 0;
        while (!$file->eof()) {
            $file->fgetcsv();
            $lineCount++;
        }

        return $includeHeaderAsRow ? $lineCount : max(0, $lineCount - 1);
    }

    /**
     * Create File and ContactImport records within a transaction.
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $originalFileName
     * @param string $groupId
     * @param User|null $user
     * @param int $totalContacts
     * @param array $data
     * @return ContactImport
     * @throws \Throwable
     */
    protected function createFileAndImport(
        string $filePath,
        string $fileName,
        string $originalFileName,
        string $groupId,
        ?User $user,
        int $totalContacts,
        array $data
    ): ContactImport {
        return DB::transaction(function () use (
            $filePath,
            $fileName,
            $originalFileName,
            $groupId,
            $user,
            $totalContacts,
            $data
        ) {
            $file = $this->createFileRecord(
                $filePath,
                $fileName,
                $originalFileName,
                $groupId,
                $user
            );

            return $this->contactManager->createContactImport(
                $file->id,
                $groupId,
                $totalContacts,
                $data
            );
        });
    }

    /**
     * Create a File record for the uploaded CSV.
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $originalFileName
     * @param string $groupId
     * @param User|null $user
     * @return File
     */
    protected function createFileRecord(
        string $filePath,
        string $fileName,
        string $originalFileName,
        string $groupId,
        ?User $user
    ): File {
        
        return $this->fileService->addContactFile(
            filePath: $filePath,
            fileName: $fileName,
            originalFileName: $originalFileName,
            groupId: $groupId,
            user: $user
        );
    }


    /**
     * Dispatch the ImportContactJob to process the contacts.
     *
     * @param int $contactImportId
     * @return void
     */
    protected function dispatchImportJob(int $contactImportId): void
    {
        $queue = config('queue.pipes.regular.contacts', 'import-contacts');
        ImportContactJob::dispatch($contactImportId)->onQueue($queue);
    }

    /**
     * deleteContact
     *
     * @param string $uid
     * @param User|null $user
     * 
     * @return RedirectResponse
     */
    public function deleteContact(string $uid, ?User $user = null): RedirectResponse {

        $contact = Contact::when($user, fn(Builder $q): Builder =>
                                    $q->where("user_id", $user->id), 
                                        fn(Builder $q):Builder =>
                                            $q->admin())
                                ->where("uid", $uid)
                                ->with(['dispatchLog'])
                                ->first();
        if(!$contact) throw new ApplicationException("Contact not found", Response::HTTP_NOT_FOUND);
        if($contact->dispatchLog) throw new ApplicationException("Contact can not be deleted it has dispatch record logs", Response::HTTP_NOT_FOUND);
        $contact->delete();

        $notify[] = ['success', translate("Contact deleted successfully")];
        return back()->withNotify($notify);
    }

    /**
     * getCsvExportData
     *
     * @param int|string|null $groupId
     * 
     * @return array
     */
    public function getCsvExportData(int|string|null $groupId = null, ?User $user = null): array {

        $route = $user 
                    ? route('user.contact.export', ['group_id' => $groupId]) 
                    : route('admin.contact.export', ['group_id' => $groupId]); 
        return [
            "url"    => $route,
            "method" => "POST",
            "parameters" => [
                "first_name" => [
                    "type" => "string"
                ],
                "last_name" => [
                    "type" => "string"
                ],
                "whatsapp_contact" => [
                    "type" => "string"
                ],
                "email_contact" => [
                    "type" => "string"
                ],
                "sms_contact" => [
                    "type" => "string"
                ],
                "meta_data" => [
                    "type" => "object",
                    "format" => [
                        "date_of_birth" => [
                            "data" => "value"
                        ]
                    ]
                ],
                "created_at" => [
                    "type" => "datetime"
                ]
            ]
        ];
    }

    /**
     * pluckContactGroup
     *
     * @param string $key
     * @param string $value
     * @param User|null $user
     * 
     * @return array
     */
    public function pluckContactGroup(string $keyColumn, string $valueColumn, ?User $user = null): array {

        return  ContactGroup::when($user, 
                            fn(Builder $q): Builder =>
                                $q->where("user_id", $user->id),
                                    fn(Builder $q): Builder => 
                                        $q->whereNull("user_id"))
                                
                                ->pluck($valueColumn, $keyColumn)
                                ->toArray();
    }

    ## Update SIngle COntact Verfication

    /**
     * singleContactEmailVerification
     *
     * @param Request $request
     * @param User|null $user
     * 
     * @return JsonResponse
     */
    public function singleContactEmailVerification(Request $request, ?User $user = null): JsonResponse {

        $contact = Contact::when($user, 
                        fn(Builder $q): Builder => 
                            $q->where("user_id", $user->id), 
                                fn(Builder $q): Builder => 
                                    $q->admin())
                            ->where("uid", $request->input('uid'))
                            ->first();
        if (!$contact) throw new ApplicationException("Invalid Contact", Response::HTTP_NOT_FOUND);
        $contact->email_verification = $request->input('email_verification') == 'true' ? 'verified' : 'unverified';
        $contact->update();

        return response()->json([
            'status'  => true,
            'message' => translate("Contact Email Verification Status has been updated"),
            'reload'  => true
        ]);
    }

    /**
     * contactUploadFile
     *
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function contactUploadFile(Request $request): JsonResponse {

        list($fileName, $filePath) = $this->fileService->uploadContactFile($request->file('file'));
        return response()->json([

            "status" => true, 
            "file_name" => $fileName, 
            "file_path" => $filePath
        ]);
    }

    /**
     * getChannelSpecificGroup
     *
     * @param ChannelTypeEnum $channel
     * @param User|null $user
     * 
     * @return Collection
     */
    public function getChannelSpecificGroup(ChannelTypeEnum $channel, ?User $user = null): Collection {

        return ContactGroup::active()
                                ->when($user, fn(Builder $q): Builder =>
                                    $q->where("user_id", $user->id), 
                                        fn(Builder $q): Builder =>
                                            $q->whereNull("user_id"))

                                ->whereHas('contacts', fn(Builder $q): Builder =>
                                        $q->whereNotNull([$channel->value."_contact"]))
                                ->get();
    }

    /**
     * Process a chunk of CSV rows and save them as contacts.
     *
     * @param array $chunk
     * @param array $header
     * @param array $columnMapping
     * @param ContactImport $contactImport
     * @param bool $newRow
     * @return void
     */
    public function processContactChunk(
        array $chunk,
        array $header,
        array $columnMapping,
        ContactImport $contactImport,
        bool $newRow
    ): void {
        
        $groupId = $contactImport->group_id;
        $userId = $contactImport->file->user_id;
        $user = User::find($userId);
        $contactAttributes = $user
            ? $user->contact_meta_data
            : site_settings(SettingKey::CONTACT_META_DATA->value, []);
        if (is_string($contactAttributes)) {
            $contactAttributes = json_decode($contactAttributes, true);
        }
    
        $transformedColumns = $this->transformColumnsForChunk($columnMapping);
        $transformedChunk = $this->transformChunkData($chunk, $header, $transformedColumns, $newRow);
    
        $contactsToCreate = collect($transformedChunk)->map(function ($row) use ($groupId, $userId, $contactAttributes) {
            $contactData = [
                "uid"   => str_unique(),
                'group_id' => $groupId,
                'user_id' => $userId,
                'status' => Status::ACTIVE->value,
            ];
    
            $metaData = collect($row)->mapWithKeys(function ($value, $columnKey) use ($contactAttributes) {
                $columnKey = strtolower(str_replace(' ', '_', $columnKey));
                if (in_array($columnKey, ['first_name', 'last_name', 'email_contact', 'sms_contact', 'whatsapp_contact'])) {
                    return [$columnKey => $value ? strtolower($value) : null];
                }
    
                if (Arr::has($contactAttributes, $columnKey)) {
                    return [
                        $columnKey => [
                            'value' => $value ? strtolower($value) : null,
                            'type' => Arr::get($contactAttributes, $columnKey . '.type', ContactAttributeEnum::TEXT->value),
                        ]
                    ];
                }
    
                return [];
            })->filter()->toArray();
    
            collect(['first_name', 'last_name', 'email_contact', 'sms_contact', 'whatsapp_contact'])
                ->each(function ($key) use (&$contactData, &$metaData) {
                    if (Arr::has($metaData, $key)) {
                        $contactData[$key] = Arr::get($metaData, $key);
                        Arr::forget($metaData, $key);
                    }
                });
    
            $contactData['meta_data'] = !empty($metaData) ? json_encode($metaData) : null;
    
            return $contactData;
        })->toArray();
    
        if (site_settings('filter_duplicate_contact') == StatusEnum::TRUE->status()) {
            
            $contactsToCreate = collect($contactsToCreate)
                ->unique(function ($contact) {
                    return implode('|', [
                        $contact['first_name'] ?? '',
                        $contact['last_name'] ?? '',
                        $contact['email_contact'] ?? '',
                        $contact['sms_contact'] ?? '',
                        $contact['whatsapp_contact'] ?? '',
                    ]);
                })
                ->filter(function ($contact) use ($groupId, $userId) {
                    $firstName  = Arr::get($contact, "first_name");
                    $lastName   = Arr::get($contact, "last_name");
                    $email      = Arr::get($contact, "email_contact");
                    $sms        = Arr::get($contact, "sms_contact");
                    $whatsapp   = Arr::get($contact, "whatsapp_contact");
    
                    if (!$firstName 
                            && !$lastName 
                            && !$email 
                            && !$sms 
                            && !$whatsapp) return true;
                    
    
                    return !Contact::where('group_id', $groupId)
                                        ->where('user_id', $userId)
                                        ->where('first_name', $firstName)
                                        ->where('last_name', $lastName)
                                        ->where('email_contact', $email)
                                        ->where('sms_contact', $sms)
                                        ->where('whatsapp_contact', $whatsapp)
                                        ->exists();
                })
                ->values()
                ->toArray();
        }
    
        if (!empty($contactsToCreate)) {
            Contact::insert($contactsToCreate);
            $contactImport->increment('processed_contacts', count($contactsToCreate));
        }
    }

    /**
     * Transform column mapping for the chunk.
     *
     * @param array $columnMapping
     * @return array
     */
    protected function transformColumnsForChunk(array $columnMapping): array
    {
        $transformedColumns = [];

        foreach ($columnMapping as $csvColumn => $mapping) {
            $field  = Arr::get($mapping, "field");
            $type   = Arr::get($mapping,'type');
            $transformedColumns[$csvColumn] = [
                $field => [
                    'status' => true,
                    'type' => $type,
                ],
            ];
        }

        return $transformedColumns;
    }

    /**
     * Transform the chunk data based on the header and mapping.
     *
     * @param array $chunk
     * @param array $header
     * @param array $transformedColumns
     * @return array
     */
    public function transformChunkData(
        array $chunk,
        array $header,
        array $transformedColumns,
    ): array {
        $columnMapping = collect($transformedColumns)->mapWithKeys(function ($updatedColumnData, $originalColumn) {
            $newColumnName = array_key_first($updatedColumnData);
            return [$originalColumn => $newColumnName];
        })->toArray();

        return collect($chunk)
            ->map(function ($row) use ($header, $columnMapping) {
                return collect($header)->mapWithKeys(function ($headerColumn, $index) use ($row, $columnMapping) {
                    $headerColumn = trim(strtolower($headerColumn));
                    $mappedColumn = Arr::get($columnMapping, $headerColumn, $headerColumn);
                    return [$mappedColumn => Arr::get($row, $index)];
                })->toArray();
            })->toArray();
    }

    /**
      * handleContacts
      *
      * @param ChannelTypeEnum $type
      * @param mixed $contactsInput
      * @param User|null $user
      * 
      * @return \Illuminate\Support\Collection
      */
    public function handleContacts(ChannelTypeEnum $type, $contactsInput, ?User $user = null): \Illuminate\Support\Collection
    {
        $groups = collect();
        
        if (is_string($contactsInput)) {
            
            $group = $this->createSingleContactGroup($type, $contactsInput, $user);
            $groups->push($group);
        } elseif ($contactsInput instanceof UploadedFile) {
            
            $group = $this->createGroupFromCsv($type, $contactsInput, $user);
            $groups->push($group);
        } else {
            
            $groups = $this->getGroupsByIds($contactsInput, $user);
        }
        return $groups;
    }

    /**
     * createSingleContactGroup
     *
     * @param ChannelTypeEnum $type
     * @param string $contact
     * @param User|null $user
     * 
     * @return ContactGroup
     */
    public function createSingleContactGroup(ChannelTypeEnum $type, string $contact, ?User $user = null): ContactGroup
    {
        $emailVerification = "unverified";

        if ($type == ChannelTypeEnum::EMAIL 
            && (
                site_settings('email_contact_verification') == StatusEnum::TRUE->status() 
                    || site_settings('email_contact_verification') == Status::ACTIVE->value
            )) { 
            $data = $this->mailService->verifyEmail($contact);
            if(!Arr::get($data, "valid")) throw new ApplicationException('Invalid email address. Reason: '. Arr::get($data, "reason"));
            $emailVerification = "verified";
        }
        $groupData = [
            'name' => SettingKey::SINGLE_CONTACT_GROUP_NAME->value,
            'user_id' => $user?->id,
            'status' => 'active',
        ];
        
        $group = $this->contactManager->createGroup($groupData, $user);
        $contactField = "{$type->value}_contact";
        
        $contactData = [
            [
                'uid'     => str_unique(),
                'user_id'  => @$user?->id,
                'group_id' => $group->id,
                'first_name' => null,
                $contactField => $contact,
                'status' => Status::ACTIVE->value,
                'email_verification' => $emailVerification,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        $this->contactManager->insertContacts($contactData, $user);

        return $group;
    }

    /**
     * createGroupFromCsv
     *
     * @param ChannelTypeEnum $type
     * @param UploadedFile $file
     * @param User|null $user
     * 
     * @return ContactGroup
     */
    public function createGroupFromCsv(ChannelTypeEnum $type, UploadedFile $file, ?User $user = null): ContactGroup
    {
        $emailVerification = "unverified";
        $groupData = [
            'name' => "CSV Import - " . now()->format('Y-m-d H:i:s'),
            'user_id' => $user?->id,
            'status' => 'active',
        ];
        $group = $this->contactManager->createGroup($groupData);
        $contactField = "{$type->value}_contact";
        
        $path = Storage::putFile('csv_uploads', $file);
        File::create([
            'fileable_id' => $group->id,
            'fileable_type' => ContactGroup::class,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'user_id' => $user?->id,
        ]);

        $contacts = [];
        LazyCollection::make(function () use ($file, $contactField, $type, $emailVerification) {
                            $handle = fopen($file->getRealPath(), 'r');
                            fgetcsv($handle);
                            while (($row = fgetcsv($handle)) !== false) {
                                yield $row;
                            }
                            fclose($handle);
                        })->chunk(1000)->each(function ($chunk) use ($group, &$contacts, $contactField, $type, $emailVerification) {
                            $chunk->each(function ($row) use ($group, &$contacts, $contactField, $type, $emailVerification) {
                                
                                $contact = Arr::get($row, 1);
                                if ($type == ChannelTypeEnum::EMAIL 
                                    && (
                                        site_settings('email_contact_verification') == StatusEnum::TRUE->status() 
                                            || site_settings('email_contact_verification') == Status::ACTIVE->value
                                    )) {

                                        $data = $this->mailService->verifyEmail($contact);
                                        if(Arr::get($data, "valid")) $emailVerification = "verified";
                                }
                                    
                                $contacts[] = [
                                    'uid'     => str_unique(),
                                    'group_id' => $group->id,
                                    'first_name' => Arr::get($row, 0),
                                    $contactField => $contact,
                                    'status' => Status::ACTIVE->value,
                                    'email_verification' => $emailVerification,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            });
                        });
        $this->contactManager->insertContacts($contacts);

        return $group;
    }

    /**
     * getGroupsByIds
     *
     * @param array $groupIds
     * @param User|null $user
     * 
     * @return Collection
     */
    public function getGroupsByIds(array $groupIds, ?User $user = null): Collection
    {
        return $this->contactManager->getGroupsByIds($groupIds, $user);
    }

    /**
     * Create a contact group from API-provided contacts.
     *
     * @param ChannelTypeEnum $type
     * @param array $contacts
     * @param User|null $user
     * @return ContactGroup
     */
    public function createGroupFromApiContacts(ChannelTypeEnum $type, array $contacts, ?User $user = null): ContactGroup
    {
        $emailVerification = "unverified";
        $groupData = [
            'name'      => "API Import - " . now()->format('Y-m-d H:i:s'),
            'user_id'   => $user?->id,
            'status'    => 'active',
        ];
        $group = $this->contactManager->createGroup($groupData, $user);
        $contactField = "{$type->value}_contact";

        $contactData = collect($contacts)->map(function ($entry) use ($type, $user, $group, $contactField, &$emailVerification) {
            
            $contact = $entry[$type->value];

            if ($type == ChannelTypeEnum::EMAIL 
                && (
                    site_settings('email_contact_verification') == StatusEnum::TRUE->status() 
                    || site_settings('email_contact_verification') == Status::ACTIVE->value
                )) {
                $data = $this->mailService->verifyEmail($contact);
                if (!Arr::get($data, "valid")) {
                    throw new ApplicationException(
                        'Invalid email address. Reason: ' . Arr::get($data, "reason")
                    );
                }
                $emailVerification = "verified";
            }

            return [
                'uid' => str_unique(),
                'user_id' => $user?->id,
                'group_id' => $group->id,
                'first_name' => null,
                $contactField => $contact,
                'status' => Status::ACTIVE->value,
                'email_verification' => $emailVerification,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        $this->contactManager->insertContacts($contactData, $user);

        return $group;
    }
}