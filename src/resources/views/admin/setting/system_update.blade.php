@extends('admin.layouts.app')
@section("panel")

@push('style-push')
<link rel="stylesheet" href="{{ asset('assets/theme/update/css/update-system.css') }}">
@endpush

<main class="main-body">

    <div class="container-fluid px-0">
        <!-- Warning Card -->
        <div class="i-card-md mt-3">
            <div class="card--header">
                <h4 class="card-title">
                    {{trans('default.system_update_title')}}
                </h4>
            </div>
            <div class="card-body">
                <div class="us-warning-card">
                    <div class="us-warning-header">
                        <i class="ri-shield-line us-icon-primary"></i>
                        <h3 class="us-title">{{translate("Be Aware !!! Before Update")}}</h3>
                    </div>
                    <ul class="us-warning-list">
                        <li class="us-warning-item">
                            <div class="us-warning-icon">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                            <div class="us-warning-text">{{translate("You must take backup from your server (files & database)")}}</div>
                        </li>
                        <li class="us-warning-item">
                            <div class="us-warning-icon">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                            <div class="us-warning-text">{{translate("Make Sure You have stable internet connection")}}</div>
                        </li>
                        <li class="us-warning-item">
                            <div class="us-warning-icon">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                            <div class="us-warning-text">{{translate("Do not close the tab while the process is running")}}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Update System Card -->
        <div class="i-card-md mt-3">
            <div class="card--header">
                <h4 class="card-title">
                    {{translate("System Update")}}
                </h4>
            </div>
            <div class="card-body">
                <div class="us-container">
                    <div class="us-tabs">
                        <div class="us-tab active" data-tab="manual-update">{{translate("Manual Update")}}</div>
                        <div class="us-tab" data-tab="click-update">{{translate("Click & Update")}}</div>
                    </div>

                    <div class="us-tab-content active" id="manual-update">
                        <div class="us-version-info">
                            <div class="us-version-label">{{translate("Current Version")}}</div>
                            <div class="us-version-value">
                                <div class="us-version-number">{{translate('V')}}{{site_settings("app_version",1.1)}}</div>
                                <div class="us-version-dot"></div>
                                <div class="us-version-date">{{get_date_time(site_settings("system_installed_at",\Carbon\Carbon::now()))}}</div>
                            </div>
                        </div>

                        <form action="{{route('admin.system.update')}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <label for="updateFile" class="us-upload-area">
                                <i class="ri-upload-cloud-line us-upload-icon"></i>
                                <div class="us-upload-text">{{translate("Upload Zip File")}}</div>
                                <div class="us-upload-subtext">{{translate("Click to browse files")}}</div>
                                <input type="file" id="updateFile" name="updateFile" accept=".zip" hidden>
                            </label>

                            <div class="d-flex justify-content-end text-end">
                                <button type="submit" class="us-btn-update">
                                    <i class="ri-download-cloud-line"></i>
                                    {{translate("Update Now")}}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="us-tab-content" id="click-update">
                        <div class="us-update-status-container">
                            <div class="us-update-status" id="updateStatus">
                                <i class="ri-refresh-line us-spinner"></i>
                                <span>{{translate("Checking for updates...")}}</span>
                            </div>
                        </div>

                        <div class="us-update-list" id="updateAvailableList">
                        </div>
                    </div>
                </div>

                <div class="us-modal" id="changelogModal">
                    <div class="us-modal-content">
                        <div class="us-modal-header">
                            <h3 class="us-modal-title">{{translate("Change Log")}}</h3>
                        </div>
                        <div class="us-modal-body" id="changelogContent">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('script-push')
<script>
    const currentAppVersion = "{{ site_settings('app_version', 1.1) }}";
</script>

<script>
    "use strict";
    $(document).ready(function() {
        // Tab switching logic
        $('.us-tab').on('click', function() {
            const tabId = $(this).data('tab');
            $('.us-tab').removeClass('active');
            $('.us-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tabId).addClass('active');
            
            if (tabId === 'click-update') {
                $('#updateStatus').show();
            }
        });

        const $updateStatus = $('#updateStatus');
        const $updateList = $('#updateAvailableList');
        const $changelogModal = $('#changelogModal');
        const $changelogContent = $('#changelogContent');

        // Initially hide the update status
        $updateStatus.hide();

        // Check for updates on document load
        $updateStatus.show();
        checkupdate();

        // File upload styling
        $('#updateFile').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $('.us-upload-text').text(fileName);
                $('.us-upload-subtext').text('{{translate("File selected")}}');
                $('.us-upload-area').addClass('us-file-selected');
            } else {
                $('.us-upload-text').text('{{translate("Upload Zip File")}}');
                $('.us-upload-subtext').text('{{translate("Click to browse files")}}');
                $('.us-upload-area').removeClass('us-file-selected');
            }
        });

        // Modal close logic
        $('#closeModal, .us-modal').on('click', function(e) {
            if (e.target === this) {
                $changelogModal.removeClass('active');
            }
        });

        function compareVersions(v1, v2) {
            const v1Parts = v1.split('.').map(Number);
            const v2Parts = v2.split('.').map(Number);
            for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
                const part1 = v1Parts[i] || 0;
                const part2 = v2Parts[i] || 0;
                if (part1 < part2) return -1;
                if (part1 > part2) return 1;
            }
            return 0;
        }

        function checkupdate() {
            $updateStatus.attr('class', 'us-update-status us-checking');
            $updateStatus.html('<i class="ri-refresh-line us-spinner"></i><span>{{translate("Checking for updates...")}}</span>');
            $updateList.empty();
            $updateList.removeClass('us-show');

            $.ajax({
                url: '{{ route("admin.system.check.update") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        
                        const currentVersion = currentAppVersion;
                        response.data.sort((a, b) => compareVersions(a.version, b.version));
                        let nextVersion = null;
                        for (const update of response.data) {
                            if (compareVersions(update.version, currentVersion) > 0) {
                                nextVersion = update.version;
                                break;
                            }
                        }

                        $updateStatus.attr('class', 'us-update-status us-available');
                        $updateStatus.html('<i class="ri-information-line"></i><span>' + response.data.length + ' {{translate("updates available!")}}</span>');
                        $updateList.addClass('us-show');
                        
                        $.each(response.data, function(index, update) {
                            const isNextVersion = update.version === nextVersion;
                            const disabledAttr = isNextVersion ? '' : 'disabled';
                            const disabledClass = isNextVersion ? '' : 'us-disabled';
                            
                            const updateItem = $('<div>', {
                                class: 'us-update-item'
                            });
                            
                            updateItem.html(`
                                <div class="us-update-item-header">
                                    <div class="us-update-item-version">
                                        <span class="us-update-version-number">V${update.version}</span>
                                        <span class="us-update-version-dot"></span>
                                        <span class="us-update-version-date">${update.release_date}</span>
                                    </div>
                                    <div class="us-update-item-description">
                                        ${update.description || '{{translate("This update includes bug fixes and performance improvements.")}}'}
                                    </div>
                                </div>
                                <div class="us-update-item-actions">
                                    <button class="us-btn-download ${disabledClass}" data-version="${update.version}" ${disabledAttr}>
                                        <i class="ri-download-cloud-line"></i>
                                        {{translate("Download & Install")}}
                                    </button>
                                    <button class="us-btn-changelog" data-changelog="${encodeURIComponent(update.changelog)}">
                                        <i class="ri-file-list-line"></i>
                                        {{translate("View Changelog")}}
                                    </button>
                                </div>
                            `);
                            
                            $updateList.append(updateItem);
                        });
                        
                        $('.us-btn-download:not(.us-disabled)').on('click', function() {
                            const version = $(this).data('version');
                            downloadAndInstallUpdate(version);
                            $(this).addClass('us-disabled').prop('disabled', true);
                        });
                        
                        $('.us-btn-changelog').on('click', function() {
                            const changelog = decodeURIComponent($(this).data('changelog'));
                            $changelogContent.html(formatChangelog(changelog));
                            $changelogModal.addClass('active');
                        });
                    } else {
                        $updateStatus.attr('class', 'us-update-status us-none');
                        $updateStatus.html('<i class="ri-check-line"></i><span>{{translate("Your software is up to date.")}}</span>');
                    }
                },
                error: function(xhr, status, error) {
                    $updateStatus.attr('class', 'us-update-status us-error');
                    $updateStatus.html('<i class="ri-error-warning-line"></i><span>{{translate("Error checking for updates. Please try again later.")}}</span>');
                    notify("error", 'Update check failed:', error);
                },
                complete: function() {
                    $updateStatus.show();
                }
            });
        }

        function formatChangelog(changelog) {
            // If changelog is already HTML, return it
            if (changelog.includes('<')) {
                return changelog;
            }
            
            // Format plain text changelog
            const lines = changelog.split('\n');
            let html = '<div class="us-changelog-item">';
            html += '<div class="us-changelog-version">{{translate("Changelog")}}</div>';
            html += '<ul class="us-changelog-list">';
            
            for (const line of lines) {
                if (line.trim()) {
                    html += `
                        <li class="us-changelog-list-item">
                            <span class="us-changelog-bullet"></span>
                            <span class="us-changelog-text">${line}</span>
                        </li>
                    `;
                }
            }
            
            html += '</ul></div>';
            return html;
        }

        function downloadAndInstallUpdate(version) {
            $updateStatus.attr('class', 'us-update-status us-checking');
            $updateStatus.html('<i class="ri-refresh-line us-spinner"></i><span>{{translate("Downloading and installing update")}} ' + version + '...</span>');
            
            $.ajax({
                url: '{{ route("admin.system.install.update") }}',
                type: 'POST',
                data: {
                    version: version,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success == true) {
                        $updateStatus.attr('class', 'us-update-status us-success');
                        $updateStatus.html('<i class="ri-check-double-line"></i><span>{{translate("Update installed successfully! Refreshing page...")}}</span>');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $updateStatus.attr('class', 'us-update-status us-error');
                        $updateStatus.html('<i class="ri-error-warning-line"></i><span>{{translate("Update installation failed:")}} ' + response.message + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    $updateStatus.attr('class', 'us-update-status us-error');
                    $updateStatus.html('<i class="ri-error-warning-line"></i><span>{{translate("Error installing update. Please try again later.")}}</span>');
                    
                    notify("error", 'Update installation failed:', error);
                }
            });
        }
    });
</script>
@endpush

@endsection