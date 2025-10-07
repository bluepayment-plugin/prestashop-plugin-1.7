/**
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
 */
$(document).ready(function() {
    let currentStepIndex = 0;
    let testSteps = [];
    let totalSteps = 0;
    let currentTestType = '';
    let overallStatus = 'success';
    
    const $testResults = $('#bm-test-results');
    const $testProgress = $('#bm-test-progress');
    const $progressBar = $testProgress.find('.progress-bar');
    const $logsSection = $('#bm-test-logs');
    const $logsInfo = $('#bm-logs-info');
    const $downloadLogsBtn = $('#bm-download-logs');
    
    $('#bm-test-connection').on('click', function() {
        resetTest();
        currentTestType = 'connection';
        startTests(currentTestType);
    });
    
    $('#bm-test-transaction').on('click', function() {
        resetTest();
        currentTestType = 'transaction';
        startTests(currentTestType);
    });
    
    $downloadLogsBtn.on('click', function(e) {
        e.preventDefault();
        if (currentTestType) {
            const downloadUrl = bluepayment_ajax_url + '&action=downloadLogs&test_type=' + currentTestType + '&ajax=1';
            window.location.href = downloadUrl;
        }
    });
    
    function resetTest() {
        currentStepIndex = 0;
        overallStatus = 'success';
        $testProgress.show();
        $progressBar.css('width', '0%').attr('aria-valuenow', 0);
        $testResults.show().empty().html('<div id="running-tests-alert" class="alert alert-info"><i class="icon-spinner icon-spin"></i> ' + 
            bluepayment_translations.running_tests + '</div>');
        $logsSection.hide();
        $logsInfo.empty();
        $downloadLogsBtn.removeAttr('data-url').hide();
    }
    
    function startTests(testType) {
        $.ajax({
            url: bluepayment_ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'getTestSteps',
                test_type: testType
            },
            success: function(response) {
                if (response.success && response.steps && response.steps.length > 0) {
                    testSteps = response.steps;
                    totalSteps = testSteps.length;
                    runTestStep(testSteps[currentStepIndex], testType);
                } else {
                    $testResults.html('<div class="alert alert-danger"><i class="icon-times"></i> ' + 
                        bluepayment_translations.no_test_steps + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $testResults.html('<div class="alert alert-danger"><i class="icon-times"></i> ' + 
                    bluepayment_translations.ajax_error + ': ' + error + '</div>');
            }
        });
    }
    
    function updateProgressBar(response) {
        const progressPercent = Math.round((currentStepIndex / totalSteps) * 100);
        $progressBar.css('width', progressPercent + '%').attr('aria-valuenow', progressPercent);
        
        if (response.status === 'error') {
            $progressBar.removeClass('progress-bar-success').addClass('progress-bar-danger');
        } else {
            $progressBar.removeClass('progress-bar-danger');
        }
    }
    
    function runTestStep(step, testType) {
        $.ajax({
            url: bluepayment_ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'execute',
                test_step: step,
                test_type: testType
            },
            success: function(response) {
                updateTestResults(response);
                

                if (response.status === 'error') {
                    overallStatus = 'error';
                } else if (response.status === 'warning' && overallStatus !== 'error') {
                    overallStatus = 'warning';
                }
                
                if (response.status !== 'error' && currentStepIndex < totalSteps - 1) {
                    currentStepIndex++;
                    updateProgressBar(response);
                    runTestStep(testSteps[currentStepIndex], testType);
                } else {

                    showTestSummary({status: overallStatus, message: response.message});
                }
            },
            error: function(xhr, status, error) {
                $testResults.html('<div class="alert alert-danger"><i class="icon-times"></i> ' + 
                    bluepayment_translations.ajax_error + ': ' + error + '</div>');
                $progressBar.css('width', '100%').addClass('progress-bar-danger');
            }
        });
    }
    
    function updateTestResults(response) {
        let alertClass = 'alert-info';
        let icon = 'icon-info';
        
        if (response.status === 'success') {
            alertClass = 'alert-success';
            icon = 'icon-check';
        } else if (response.status === 'error') {
            alertClass = 'alert-danger';
            icon = 'icon-times';
        } else if (response.status === 'warning') {
            alertClass = 'alert-warning';
            icon = 'icon-warning';
        }
        
        // Dodajemy nowy wynik zamiast nadpisywać
        $testResults.append('<div class="alert ' + alertClass + '"><i class="' + icon + '"></i> ' + 
            response.message + '</div>');
    }
    
    function showTestSummary(finalResponse) {
        $progressBar.css('width', '100%');
        

        $('#running-tests-alert').remove();
        
        if (finalResponse.status === 'success') {
            $progressBar.removeClass('progress-bar-danger progress-bar-warning').addClass('progress-bar-success');

            $testResults.append('<div class="alert alert-success summary-alert"><strong><i class="icon-check-circle"></i> ' + 
                bluepayment_translations.test_completed_success + '</strong><br/>' +
                bluepayment_translations.all_tests_passed + '</div>');
        } else if (finalResponse.status === 'warning') {
            $progressBar.removeClass('progress-bar-danger progress-bar-success').addClass('progress-bar-warning');

            $testResults.append('<div class="alert alert-warning summary-alert"><strong><i class="icon-exclamation-triangle"></i> ' + 
                bluepayment_translations.test_completed_warning + '</strong><br/>' +
                bluepayment_translations.some_tests_warnings + '</div>');
        } else {
            $progressBar.removeClass('progress-bar-success progress-bar-warning').addClass('progress-bar-danger');

            $testResults.append('<div class="alert alert-danger summary-alert"><strong><i class="icon-times-circle"></i> ' + 
                bluepayment_translations.test_completed_error + '</strong><br/>' +
                bluepayment_translations.some_tests_failed + '</div>');
        }
        
        showLogsSection(currentTestType);
    }
    
    function showLogsSection(testType) {
        $logsSection.show();
        $downloadLogsBtn.show();
        $logsInfo.html('<p class="text-info"><i class="icon-info-circle"></i> ' + 
            bluepayment_translations.logs_available + '</p>');
    }
});
