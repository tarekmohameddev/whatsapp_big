@push("style-include")
<link rel="stylesheet" href="{{ asset('assets/theme/global/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/theme/global/css/prism.css') }}">
@endpush

@extends('admin.layouts.app')
@section("panel")
<main class="main-body">
    <div class="container-fluid px-0 main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h2 class="page-title">{{ $title ?? translate('Automation Settings') }}</h2>
                <div class="breadcrumb-wrapper">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.dashboard') }}">{{ translate("Dashboard") }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $title ?? translate('Automation Settings') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card info-card mb-4">
           
            <div class="card-header">
                <div class="card-header-left">
                    <h4 class="card-title">{{ translate("Before You Start") }}</h4>
                </div>
                <div class="card-header-right">
                    <button type="button" class="i-btn btn--primary btn--md" data-bs-toggle="modal" data-bs-target="#queueConnectionModal">
                        {{ translate("Configure Queue Connection") }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="info-alert">
                    <div class="info-content">
                        <p class="info-title">{{ translate("Choose a queue processing method based on your hosting:") }}</p>
                        <ul class="info-list">
                            <li>
                                <span class="info-badge">{{ translate("cURL Setup") }}</span>
                                <span>{{ translate("Use HTTP requests for shared hosting.") }}</span>
                            </li>
                            <li>
                                <span class="info-badge">{{ translate("Command Setup") }}</span>
                                <span>{{ translate("Run Artisan commands directly for efficiency.") }}</span>
                            </li>
                            <li>
                                <span class="info-badge">{{ translate("Supervisor Setup") }}</span>
                                <span>{{ translate("Manage queues on a VPS for high throughput.") }}</span>
                            </li>
                        </ul>
                        <div class="info-note">
                            <i class="ri-information-line"></i>
                            <span>{{ Arr::get($queue_info, 'no_auth_warning', translate('Secure routes in production.')) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cron Setup -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="card-header-left">
                    <h4 class="card-title">
                        <i class="ri-links-line"></i>
                        {{ translate("Cron Setup") }}
                    </h4>
                </div>
            </div>
            <div class="card-body">
                <p class="tab-description">{{ translate("Use cron jobs to automate queue processing and system tasks.") }}</p>
                <p>{{ translate("Last cron ran at: ") }}{{ site_settings("last_cron_run", "Never") }}</p>
                
                <div class="modern-accordion" id="cronAccordion">
                    
                    <!-- System Cron -->
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <div class="accordion-label">{{ translate("System Tasks Cron") }}</div>
                            <div class="accordion-trigger" data-bs-toggle="collapse" data-bs-target="#systemCronCollapse">
                                <div class="code-block">
                                    <code>{{ Arr::get($curl, 'cron_run_url', '#') }}</code>
                                    <button type="button" class="copy-btn" data-clipboard-text="{{ Arr::get($curl, 'cron_run_url', '#') }}">
                                        <i class="ri-file-copy-line"></i>
                                    </button>
                                </div>
                                <i class="accordion-icon ri-arrow-down-s-line"></i>
                            </div>
                        </div>
                        <div id="systemCronCollapse" class="accordion-collapse collapse" data-bs-parent="#cronAccordion">
                            <div class="accordion-body">
                                <div class="code-description">
                                    <p>{{ translate("Automates system tasks, including:") }}</p>
                                    <ul>
                                        <li>{{ translate("Scheduling SMS, WhatsApp, and email dispatches.") }}</li>
                                        <li>{{ translate("Processing active, ongoing, and completed campaigns.") }}</li>
                                        <li>{{ translate("Checking subscription plan expirations.") }}</li>
                                    </ul>
                                    <p class="mt-2">{{ translate("Cron Example (every minute):") }}</p>
                                    <div class="code-example">
                                        <pre><code class="language-bash">* * * * * curl -s {{ Arr::get($curl, 'cron_run_url', '#') }}</code></pre>
                                        <button type="button" class="copy-btn" data-clipboard-text="* * * * * curl -s {{ Arr::get($curl, 'cron_run_url', '#') }}">
                                            <i class="ri-file-copy-line"></i>
                                        </button>
                                    </div>
                                    <p class="mt-2">{{ translate("Last run: ") }}{{ site_settings("last_cron_run", "Never") }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Queue Tabs -->
        <ul class="nav modern-tabs" id="automationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="curl-tab" data-bs-toggle="tab" data-bs-target="#curl" type="button" role="tab" aria-controls="curl" aria-selected="true">
                    <i class="ri-links-line"></i>
                    <span>{{ translate("cURL Setup") }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="command-tab" data-bs-toggle="tab" data-bs-target="#command" type="button" role="tab" aria-controls="command" aria-selected="false">
                    <i class="ri-terminal-line"></i>
                    <span>{{ translate("Command Setup") }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="supervisor-tab" data-bs-toggle="tab" data-bs-target="#supervisor" type="button" role="tab" aria-controls="supervisor" aria-selected="false">
                    <i class="ri-robot-2-line"></i>
                    <span>{{ translate("Supervisor Setup") }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content mt-4">
            <!-- cURL & Cron Tab -->
            <div class="tab-pane fade show active" id="curl" role="tabpanel" aria-labelledby="curl-tab">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <h4 class="card-title">
                                <i class="ri-links-line"></i>
                                {{ translate("cURL Setup") }}
                            </h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="tab-description">{{ translate("Use cURL to trigger queue processing via HTTP. Ideal for shared hosting (e.g., cPanel).") }}</p>
                        
                        <div class="modern-accordion" id="curlAccordion">
                            <!-- All Queues -->
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <div class="accordion-label">{{ translate("All Queues") }}</div>
                                    <div class="accordion-trigger" data-bs-toggle="collapse" data-bs-target="#curlAllCollapse">
                                        <div class="code-block">
                                            <code>{{ Arr::get($curl, 'all_queues_url', '#') }}</code>
                                            <button type="button" class="copy-btn" data-clipboard-text="{{ Arr::get($curl, 'all_queues_url', '#') }}">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                        <i class="accordion-icon ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                                <div id="curlAllCollapse" class="accordion-collapse collapse" data-bs-parent="#curlAccordion">
                                    <div class="accordion-body">
                                        <div class="code-description">
                                            <p>{{ translate("Processes all queues in order:") }} <code>{{ implode(', ', Arr::get($queue_info, 'priority_order', [])) }}</code></p>
                                            <p class="mt-2">{{ translate("Cron Example (every minute):") }}</p>
                                            <div class="code-example">
                                                <pre><code class="language-bash">* * * * * curl -s {{ Arr::get($curl, 'all_queues_url', '#') }}</code></pre>
                                                <button type="button" class="copy-btn" data-clipboard-text="* * * * * curl -s {{ Arr::get($curl, 'all_queues_url', '#') }}">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Queue-specific endpoints -->
                            @foreach (Arr::get($curl, 'queues', []) as $queue => $url)
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <div class="accordion-label">{{ translate(ucfirst(str_replace('-', ' ', $queue))) }}</div>
                                    <div class="accordion-trigger" data-bs-toggle="collapse" data-bs-target="#curl{{ Str::studly($queue) }}Collapse">
                                        <div class="code-block">
                                            <code>{{ $url }}</code>
                                            <button type="button" class="copy-btn" data-clipboard-text="{{ $url }}">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                        <i class="accordion-icon ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                                <div id="curl{{ Str::studly($queue) }}Collapse" class="accordion-collapse collapse" data-bs-parent="#curlAccordion">
                                    <div class="accordion-body">
                                        <div class="code-description">
                                            <p>{{ translate("Processes one job from the $queue queue. Requires worker cron.") }}</p>
                                            <p class="mt-2">{{ translate("Cron Example (every minute):") }}</p>
                                            <div class="code-example">
                                                <pre><code class="language-bash">* * * * * curl -s {{ $url }}</code></pre>
                                                <button type="button" class="copy-btn" data-clipboard-text="* * * * * curl -s {{ $url }}">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            <!-- Worker Trigger -->
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <div class="accordion-label">{{ translate("Worker Trigger") }}</div>
                                    <div class="accordion-trigger" data-bs-toggle="collapse" data-bs-target="#curlWorkerCollapse">
                                        <div class="code-block">
                                            <code>{{ translate("Worker Cron") }}</code>
                                            <button type="button" class="copy-btn" data-clipboard-text="{{ Arr::get($curl, 'worker_trigger_command', '#') }}">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                        <i class="accordion-icon ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                                <div id="curlWorkerCollapse" class="accordion-collapse collapse" data-bs-parent="#curlAccordion">
                                    <div class="accordion-body">
                                        <div class="code-description">
                                            <p>{{ translate("Required for queue-specific cURL routes to process jobs.") }}</p>
                                            <div class="code-example">
                                                <pre><code class="language-bash">* * * * * {{ Arr::get($curl, 'worker_trigger_command', '#') }}</code></pre>
                                                <button type="button" class="copy-btn" data-clipboard-text="* * * * * {{ Arr::get($curl, 'worker_trigger_command', '#') }}">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Command & Cron Tab -->
            <div class="tab-pane fade" id="command" role="tabpanel" aria-labelledby="command-tab">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <h4 class="card-title">
                                <i class="ri-terminal-line"></i>
                                {{ translate("Command Setup") }}
                            </h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="tab-description">{{ translate("Run Artisan commands directly via cron. More efficient for shared hosting.") }}</p>
                        
                        <div class="modern-accordion" id="cmdAccordion">
                            @foreach (Arr::get($command, 'commands', []) as $queue => $cmd)
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <div class="accordion-label">{{ translate(ucfirst(str_replace('-', ' ', $queue))) }}</div>
                                    <div class="accordion-trigger" data-bs-toggle="collapse" data-bs-target="#cmd{{ Str::studly($queue) }}Collapse">
                                        <div class="code-block">
                                            <code>{{ $cmd }}</code>
                                            <button type="button" class="copy-btn" data-clipboard-text="{{ $cmd }}">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                        <i class="accordion-icon ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                                <div id="cmd{{ Str::studly($queue) }}Collapse" class="accordion-collapse collapse" data-bs-parent="#cmdAccordion">
                                    <div class="accordion-body">
                                        <div class="code-description">
                                            <p>{{ translate("Processes one job from the $queue queue.") }}</p>
                                            <p class="mt-2">{{ translate("Cron Example (every minute):") }}</p>
                                            <div class="code-example">
                                                <pre><code class="language-bash">* * * * * {{ $cmd }}</code></pre>
                                                <button type="button" class="copy-btn" data-clipboard-text="* * * * * {{ $cmd }}">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supervisor Tab -->
            <div class="tab-pane fade" id="supervisor" role="tabpanel" aria-labelledby="supervisor-tab">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <h4 class="card-title">
                                <i class="ri-robot-2-line"></i>
                                {{ translate("Supervisor Setup") }}
                            </h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="tab-description">{{ translate("Use Supervisor on a VPS for continuous queue processing. Requires SSH access.") }}</p>
                        
                        <div class="step-guide">
                            <h5 class="step-guide-title">{{ translate("Step-by-Step Guide") }}</h5>
                            <ol class="step-list">
                                <li class="step-item">
                                    <div class="step-header">
                                        <div class="step-number">1</div>
                                        <h6 class="step-title">{{ translate("Install Supervisor") }}</h6>
                                    </div>
                                    <div class="step-content">
                                        <div class="code-example">
                                            <pre><code class="language-bash">sudo apt-get update
sudo apt-get install supervisor</code></pre>
                                            <button type="button" class="copy-btn" data-clipboard-text="sudo apt-get update
sudo apt-get install supervisor">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                                
                                <li class="step-item">
                                    <div class="step-header">
                                        <div class="step-number">2</div>
                                        <h6 class="step-title">{{ translate("Create a configuration file for each queue") }}</h6>
                                    </div>
                                    <div class="step-content">
                                        <p>{{ translate("Create a configuration file for each queue, for example:") }}</p>
                                        <ul>
                                            @foreach (array_keys(Arr::get($command, 'commands', [])) as $queue)
                                                <li>/etc/supervisor/conf.d/xsender-{{ $queue }}.conf</li>
                                            @endforeach
                                        </ul>
                                        <p class="mt-2">{{ translate("Example for regular-sms:") }}</p>
                                        <div class="code-example">
                                            <pre><code class="language-bash">[program:xsender-regular-sms]
command=php {{ Arr::get($supervisor, 'artisan_path', '#') }} queue:work:regular-sms
directory={{ Arr::get($supervisor, 'root_dir', '#') }}
autostart=true
autorestart=true
user={{ Arr::get($supervisor, 'user', 'www-data') }}
numprocs=1
redirect_stderr=true
stdout_logfile={{ Arr::get($supervisor, 'root_dir', '#') }}/storage/logs/supervisor-regular-sms.log
stopwaitsecs=3600</code></pre>
                                            <button type="button" class="copy-btn" data-clipboard-text="[program:xsender-regular-sms]
command=php {{ Arr::get($supervisor, 'artisan_path', '#') }} queue:work:regular-sms
directory={{ Arr::get($supervisor, 'root_dir', '#') }}
autostart=true
autorestart=true
user={{ Arr::get($supervisor, 'user', 'www-data') }}
numprocs=1
redirect_stderr=true
stdout_logfile={{ Arr::get($supervisor, 'root_dir', '#') }}/storage/logs/supervisor-regular-sms.log
stopwaitsecs=3600">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                        <p class="mt-2">{{ translate("Repeat for other queues (regular-email, campaign-sms, etc.) and worker-trigger.") }}</p>
                                    </div>
                                </li>
                                
                                <li class="step-item">
                                    <div class="step-header">
                                        <div class="step-number">3</div>
                                        <h6 class="step-title">{{ translate("Update Supervisor") }}</h6>
                                    </div>
                                    <div class="step-content">
                                        <div class="code-example">
                                            <pre><code class="language-bash">sudo supervisorctl reread
sudo supervisorctl update</code></pre>
                                            <button type="button" class="copy-btn" data-clipboard-text="sudo supervisorctl reread
sudo supervisorctl update">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                                
                                <li class="step-item">
                                    <div class="step-header">
                                        <div class="step-number">4</div>
                                        <h6 class="step-title">{{ translate("Start all programs") }}</h6>
                                    </div>
                                    <div class="step-content">
                                        <div class="code-example">
                                            <pre><code class="language-bash">sudo supervisorctl start all</code></pre>
                                            <button type="button" class="copy-btn" data-clipboard-text="sudo supervisorctl start all">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                                
                                <li class="step-item">
                                    <div class="step-header">
                                        <div class="step-number">5</div>
                                        <h6 class="step-title">{{ translate("Check status") }}</h6>
                                    </div>
                                    <div class="step-content">
                                        <div class="code-example">
                                            <pre><code class="language-bash">sudo supervisorctl status</code></pre>
                                            <button type="button" class="copy-btn" data-clipboard-text="sudo supervisorctl status">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            </ol>
                            
                            <div class="info-note mt-4">
                                <i class="ri-information-line"></i>
                                <div class="info-note-warning">
                                    <p class="info-note-title">{{ translate("Ensure user and group permissions match:") }}</p>
                                    <code>{{ Arr::get($supervisor, 'user', 'www-data') }}:{{ Arr::get($supervisor, 'group', 'www-data') }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="queueConnectionModal" tabindex="-1" aria-labelledby="queueConnectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form class="settingsForm" data-route="{{ route('admin.system.setting.store') }}">
                @csrf
                <input type="hidden" name="channel" value="queue">
                <div class="modal-header">
                    <h5 class="modal-title" id="queueConnectionModalLabel">{{ translate("Queue Connection Settings") }}</h5>
                    <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                        <i class="ri-close-large-line"></i>
                    </button>
                </div>
                <div class="modal-body modal-lg-custom-height">
                    <div class="row g-4">
                        <div class="col-lg-12">
                            <div class="form-inner">
                                <div class="card info-card mb-4">
                                    <div class="card-body p-4">
                                        <div class="info-alert">
                                            <div class="info-content">
                                                <ul class="info-list">
                                                    <li>
                                                        <span class="info-badge">{{ translate("Database") }}</span>
                                                        <span>{{ translate("Database for shared hosting") }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="info-badge">{{ translate("Sync") }}</span>
                                                        <span>{{ translate("Sync for testing only.") }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="info-badge">{{ translate("Redis/SQS") }}</span>
                                                        <span>{{ translate("Redis/SQS for VPS high volume") }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="info-badge">{{ translate("Beanstalkd") }}</span>
                                                        <span>{{ translate("Beanstalkd for advanced setups") }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="queue_driver" class="form-label">{{ translate("Queue Driver") }}<span class="text-danger">*</span></label>
                                <select id="queue_driver" name="site_settings[queue_connection_config][driver]" class="form-control select2-search" required>
                                    <option value="sync" {{ site_settings('queue_connection_config.driver', 'database') === 'sync' ? 'selected' : '' }}>{{ translate("Sync") }}</option>
                                    <option value="database" {{ site_settings('queue_connection_config.driver', 'database') === 'database' ? 'selected' : '' }}>{{ translate("Database") }}</option>
                                    <option value="beanstalkd" {{ site_settings('queue_connection_config.driver', 'database') === 'beanstalkd' ? 'selected' : '' }}>{{ translate("Beanstalkd") }}</option>
                                    <option value="sqs" {{ site_settings('queue_connection_config.driver', 'database') === 'sqs' ? 'selected' : '' }}>{{ translate("SQS") }}</option>
                                    <option value="redis" {{ site_settings('queue_connection_config.driver', 'database') === 'redis' ? 'selected' : '' }}>{{ translate("Redis") }}</option>
                                </select>
                                
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="row g-4" id="queue-driver-fields"></div>
                        </div>
                        <div class="col-lg-12">
                            <div class="info-note">
                                <i class="ri-information-line"></i>
                                <span>{{ translate("Shared hosting: Use Database (check other drivers with your host). VPS: Use Redis/SQS for 10M+ dispatches/day; Beanstalkd for advanced setups.") }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal">{{ translate("Close") }}</button>
                    <button type="submit" class="i-btn btn--primary btn--md">{{ translate("Save") }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push("script-include")
<script src="{{ asset('assets/theme/global/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/theme/global/js/prism.js') }}"></script>
@endpush
@push("script-push")
<script>
    "use strict";

    function initCopyButtons() {
        document.querySelectorAll('.copy-btn').forEach(function(button) {
            button.addEventListener('click', function(event) {
                // Prevent accordion toggle
                event.stopPropagation();

                // Get text to copy
                var text = button.dataset.clipboardText || '';

                // Copy function
                function copyTextToClipboard(text) {
                    // Try modern clipboard API
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(text);
                    } else {
                        // Fallback: Create hidden textarea
                        return new Promise(function(resolve, reject) {
                            var textarea = document.createElement('textarea');
                            textarea.value = text;
                            textarea.style.position = 'fixed';
                            textarea.style.opacity = '0';
                            document.body.appendChild(textarea);
                            textarea.select();
                            try {
                                document.execCommand('copy');
                                resolve();
                            } catch (error) {
                                reject(error);
                            } finally {
                                document.body.removeChild(textarea);
                            }
                        });
                    }
                }

                // Execute copy
                copyTextToClipboard(text)
                    .then(function() {
                        // Success
                        notify("success", "{{ translate('Text copied!') }}");
                    })
                    .catch(function(error) {
                        // Error
                        console.error('Unable to copy text: ', error);
                        notify("error", "{{ translate('Failed to copy text!') }}");
                    });
            });
        });
    }
    
    $(document).ready(function() {
       
        initCopyButtons();
        
        // Initialize select2 if needed
        if ($('.select2-search').length) {
            select2_search($('.select2-search').data('placeholder'), "#queueConnectionModal");
        }
        
        // Initialize Prism.js for syntax highlighting
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
    });
</script>

<script>
    // Pass connections to JavaScript
    window.connections = @json($connections ?? []);

    $(document).ready(function() {
    // Handle driver change
    $('#queue_driver').on('change', function() {
        const driver = $(this).val();
        const $fieldsContainer = $('#queue-driver-fields');
        $fieldsContainer.empty(); // Clear existing fields

        // Get driver configuration
        const driverConfig = window.connections[driver] || {};

        if (Object.keys(driverConfig).length === 0) {
            $fieldsContainer.append(
                '<div class="col-lg-12"><div class="info-note"><i class="ri-information-line"></i><span>' +
                "{{ translate('No additional configuration required for this driver.') }}" +
                '</span></div></div>'
            );
            return;
        }

        // Generate fields dynamically
        Object.entries(driverConfig).forEach(([key, config]) => {
            
            const isPassword = key === 'password' || key === 'secret';
            const required = config.required ? '<span class="text-danger">*</span>' : '';
            const value = config?.value || ''; // Use empty string if value is undefined
            const inputType = isPassword ? 'password' : 'text';

            const fieldHtml = `
                <div class="col-lg-6">
                    <div class="form-inner">
                        <label for="${driver}_${key}" class="form-label">${config.label}${required}</label>
                        <input
                            type="${inputType}"
                            id="${driver}_${key}"
                            name="site_settings[queue_connection_config][connection][${key}]"
                            class="form-control"
                            placeholder="${config.placeholder}"
                            value="${window.connections['driver'] == driver ? value.replace(/"/g, '&quot;') : ""}" // Escape quotes for HTML
                            aria-label="${config.label}"
                            ${config.required ? 'required' : ''}
                        >
                    </div>
                </div>
            `;
            $fieldsContainer.append(fieldHtml);
        });
    });

    // Initialize fields on modal open
    $('#queueConnectionModal').on('show.bs.modal', function() {
        // Ensure the driver is set to the value from connections
        const currentDriver = window.connections.driver || 'database';
        $('#queue_driver').val(currentDriver).trigger('change');
    });
});
</script>
@endpush