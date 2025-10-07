{*
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2025
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}

<div class="bm-info--small bm-info--dev spacing-bottom">
    <img width="22" class="bm-info--small__icon img-fluid" src="{$src_img|escape:'html':'UTF-8'}/info.svg" alt="Info" />

    <p>{l s='Test your Autopay module configuration to ensure proper connection with payment gateway and transaction processing. This will help identify any issues with your setup.' mod='bluepayment'}</p>
</div>

<div class="bm-test-connection panel">
    <div class="panel-heading">
        <i class="icon-plug"></i> {l s='Connection tests' mod='bluepayment'}
    </div>
    
    <div class="panel-body">
        <div class="bm-test-buttons">
            <button type="button" id="bm-test-connection" class="btn btn-primary">
                <i class="icon-exchange mr-2"></i> {l s='Check connection' mod='bluepayment'}
            </button>
            
            <button type="button" id="bm-test-transaction" class="btn btn-default">
                <i class="icon-credit-card mr-2"></i> {l s='Check transaction correctness' mod='bluepayment'}
            </button>
        </div>
        
        <div id="bm-test-progress" class="progress" style="display: none;">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        
        <div id="bm-test-results" class="bm-test-results" style="display: none;">
            <div class="alert alert-info">
                <i class="icon-spinner icon-spin"></i> {l s='Running tests...' mod='bluepayment'}
            </div>
        </div>
        
        <div id="bm-test-logs" class="bm-test-logs panel" style="display: none;">
            <div class="panel-heading">
                <i class="icon-file-text"></i> {l s='Test Logs' mod='bluepayment'}
            </div>
            <div class="panel-body">
                <div id="bm-logs-info" class="bm-logs-info"></div>
                <div class="bm-logs-actions">
                    <button type="button" id="bm-download-logs" class="btn btn-default" style="display: none;">
                        <i class="icon-download mr-2"></i> {l s='Download Logs' mod='bluepayment'}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var bluepayment_ajax_url = "{$link->getAdminLink('AdminTestConnection')|addslashes nofilter}";
    var bluepayment_translations = {
        running_tests: "{l s='Running tests...' mod='bluepayment' js=1}",
        checking_connection: "{l s='Checking connection...' mod='bluepayment' js=1}",
        checking_transaction: "{l s='Checking transaction correctness...' mod='bluepayment' js=1}",
        ajax_error: "{l s='AJAX request failed' mod='bluepayment' js=1}",
        no_test_steps: "{l s='No test steps defined for this test type' mod='bluepayment' js=1}",
        test_results: "{l s='Test Results' mod='bluepayment' js=1}",
        test_step: "{l s='Test Step' mod='bluepayment' js=1}",
        status: "{l s='Status' mod='bluepayment' js=1}",
        message: "{l s='Message' mod='bluepayment' js=1}",
        log_file: "{l s='Log file' mod='bluepayment' js=1}",
        log_size: "{l s='Size' mod='bluepayment' js=1}",
        test_completed_success: "{l s='Test completed successfully!' mod='bluepayment' js=1}",
        test_completed_error: "{l s='Test completed with errors!' mod='bluepayment' js=1}",
        test_completed_warning: "{l s='Test completed with warnings!' mod='bluepayment' js=1}",
        all_tests_passed: "{l s='All tests have passed. Your module is configured correctly.' mod='bluepayment' js=1}",
        some_tests_failed: "{l s='Some tests have failed. Please check the details above and fix the issues.' mod='bluepayment' js=1}",
        some_tests_warnings: "{l s='Some tests have warnings. Your module will work, but you might want to check the details above.' mod='bluepayment' js=1}",
        logs_available: "{l s='Detailed test logs are available for download below.' mod='bluepayment' js=1}",
        last_modified: "{l s='Last modified' mod='bluepayment' js=1}"
    };
</script>

<script type="text/javascript" src="{$module_dir|escape:'html':'UTF-8'}views/js/admin-test.js"></script>

<style type="text/css">
    .bm-test-connection {
        margin-top: 20px;
    }
    
    .bm-test-buttons {
        margin-bottom: 20px;
    }
    
    .bm-test-buttons button {
        margin-right: 10px;
    }
    
    .bm-test-results,
    .bm-test-logs {
        margin-top: 20px;
    }
    
    .bm-logs-info {
        margin-bottom: 15px;
    }
    
    .bm-logs-actions {
        margin-top: 10px;
    }
    
    .mr-2 {
        margin-right: 5px;
    }
    
    .icon-spin {
        animation: spin 2s infinite linear;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
