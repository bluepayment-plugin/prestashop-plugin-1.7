(function ($) {
    'use strict';
    var apc = {
        defaults: {
            formInvalid: false,
            productAddedToCart: false,
        },

        autopay: false,
        cartData: false,


        initialize: function () {

            this.initAutopay();
            return this;
        },

        isCatalogProduct: function() {
            return true;
        },

        whenAvailable: function (name, callback) {
            var interval = 10;
            window.setTimeout(function () {
                if (window[name]) {
                    callback(window[name]);
                } else {
                    this.whenAvailable(name, callback);
                }
            }.bind(this), interval);
        },

        initAutopay: function () {
            var self = this,
                autopay,
                button,
                quantity,
                container = $('.autopay-container');

            this.whenAvailable('autopay', function () {

                autopay = new window.autopay.checkout({
                    merchantId: container.attr('data-apc_merchantid'),
                    theme: 'dark',
                    language: 'pl'
                });
                button = autopay.createButton({
                    theme: container.attr('data-apc_button_theme')?container.attr('data-apc_button_theme'):'dark',
                    fullWidth: container.attr('data-apc_button_fullwidth')==1?true:false,
                    rounded: container.attr('data-apc_button_rounded')==1?true:false,
                });

                self.autopay = autopay;
                if($('.product-information .add-to-cart').attr('disabled')){
                    $('.autopay-container').addClass('lock');
                }
                else{
                    $('.autopay-container').removeClass('lock');
                }

                self.onRemoveFromCartListener();

                autopay.onBeforeCheckout = () => {
                    self.log('onBeforeCheckout executed');

                    return new Promise((resolve, reject) => {
                        if (self.isCatalogProduct()) {
                            self.log('Product already added to cart');
                            // If already added - just set data and resolve promise.
                            // $('[data-button-action="add-to-cart"]').click();
                            $('.autopay-container').removeAttr('data-grand_total');
                            quantity = $('.autopay-container').closest('form').find('[name="qty"]').val();
                            self.addToCart(quantity);
                            var int = setInterval(function(){
                                if($('body').find('.autopay-container').attr('data-grand_total')){
                                    clearInterval(int);
                                    self.setAutopayData(resolve, reject);
                                }
                            }, 10)
                            //
                        } else {
                            self.log('Not catalog product');

                            self.setAutopayData(resolve, reject);
                        }
                    });
                };

                container.append(button);
            });
        },
        setAutopayData: function (resolve, reject) {

            var data = {
                id: $('.autopay-container').data('cart_id'),
                amount: parseFloat($('.autopay-container').attr('data-grand_total')),
                currency: $('.autopay-container').attr('data-currency_iso'),
                label: 'APC payment',
                productList: [],
            };

            this.log('SetTransactionData', data);
            this.autopay.setTransactionData(data);

            resolve();
        },
        clearCart: function (reject) {
            const self = this;

            self.log('Clear cart started');
        },
        onRemoveFromCartListener: function () {
            const self = this;
        },
        addToCart: function(quantity){
            var action = $('.autopay-container').attr('data-action'),
                cartId = $('.autopay-container').attr('data-cart_id'),
                customerId = $('.autopay-container').attr('data-uid'),
                productId = $('.autopay-container').attr('data-id_product'),
                attributeId = $('.autopay-container').attr('data-id_product_attribute'),
                self = this,
                currency_iso = $('.autopay-container').attr('data-currency_iso'),
                currency = $('.autopay-container').attr('data-currency');
            if(!attributeId){
                attributeId = 0;
            }
            if(!productId){
                productId = 0;
            }
            $.ajax(action, {
                method: 'POST',
                type: 'POST',
                data: {
                    customerId: customerId,
                    currency: currency,
                    currency_iso: currency_iso,
                    cartId: cartId,
                    productId: productId,
                    attributeId: attributeId,
                    quantity: quantity,
                },
                beforeSend: function(){
                    $('.autopay-container').removeAttr('data-grand_total');
                    $('.autopay-container').removeAttr('data-currency_iso');
                    $('.autopay-container').removeAttr('data-secure_key');
                },
                success: function (response) {
                    console.log(response + 'aaaa');
                    console.log('cdcdcd');
                    response = JSON.parse(response);
                    if(response['status'] === 'valid') {
                        $('.autopay-container').attr('data-grand_total', response['grand_total']);
                        $('.autopay-container').attr('data-currency_iso', currency_iso);
                        $('.autopay-container').attr('data-secure_key', response['secure_key']);
                    }
                    else{
                        $('.autopay-modal-container').css('display', 'flex')
                                                    .hide()
                                                    .fadeIn(300)
                                                    .find('.content')
                                                    .html('<p>' + response['message'] + '</p>');
                    }
                }
            });
        },

        log: function (message, object = null) {
            message = '[AutoPay]' + this.formatConsoleDate(new Date()) + message;

            console.log(message, object);
        },

        formatConsoleDate: (date) => {
            var hour = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            var milliseconds = date.getMilliseconds();

            return '[' +
                ((hour < 10) ? '0' + hour: hour) +
                ':' +
                ((minutes < 10) ? '0' + minutes: minutes) +
                ':' +
                ((seconds < 10) ? '0' + seconds: seconds) +
                '.' +
                ('00' + milliseconds).slice(-3) +
                '] ';
        }
    };

    $(document).ready(function() {
        apc.initialize();
        prestashop.on('updatedProduct',function() {
            $('.autopay-container').html('');
            apc.initialize();
        });
        prestashop.on('updateCart',function() {
            $('.autopay-cart, .autopay-container').html('');
            apc.initialize();
        });
        $('body').on('click', '.autopay-modal-container .close', function(e){
            e.preventDefault();
            $('.autopay-modal-container').fadeOut(300);
        });
        if($('.cart-summary').length > 0){
            $('.autopay-container').insertAfter('.cart-summary .checkout');
        }
    });
})(jQuery);