$(document).ready(function() {
    $('input:radio[name=bluepayment-gateway-gateway-id]').change(function() {
        $.get('/module/bluepayment/gateway', {'gateway_id': this.value}, function(){
        },'json')
    });
});