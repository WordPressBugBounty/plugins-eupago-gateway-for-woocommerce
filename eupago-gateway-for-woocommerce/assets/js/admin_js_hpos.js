jQuery(document).ready(function ($) {
    //Get Param from url
    $.urlParam = function (name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        return results[1] || 0;
    }

    // Refund request
    jQuery(document).ready(function($) {
        $('.eupago-refund-request').on('click', function () {
            var refund_name = $('input[name="refund_name"].eupago-field').val();
            var refund_iban = $('input[name="refund_iban"].eupago-field').val();
            var refund_bic = $('input[name="refund_bic"].eupago-field').val();
            var refund_amount = $('input[name="refund_amount"].eupago-field').val();
            var refund_reason = $('input[name="refund_reason"].eupago-field').val();
            var refund_order = $.urlParam('id');
            var site_url = $('.eupago-site-url').text();
            $('.eupago-refund-response').empty();
    
            $.ajax({
                type: 'post',
                url: MYajax.ajax_url,
                data: {
                    action: 'refund',
                    refund_order: refund_order,
                    refund_name: refund_name,
                    refund_iban: refund_iban,
                    refund_bic: refund_bic,
                    refund_amount: refund_amount,
                    refund_reason: refund_reason
                },
                success: function (response) {
                    $('.eupago-refund-response').empty().append(response);
                },
                error: function (xhr, status, error) {
                    // Handle the error response
                    alert('An error occurred while processing the refund. Please try again.');
                },
                complete: function () {
                    // Re-enable the button after the AJAX call is complete
                    $('.eupago-refund-request').prop('disabled', false);
                }
            });
    
            // Disable the button to prevent multiple clicks
            $(this).prop('disabled', true);
        });
    });
    

    // Eupago settings
    $('input[name="sms_enable"]').on('change', function () {
        if (this.checked) {
            $('.eupago-sms-notifications').addClass('active');
        } else {
            $('.eupago-sms-notifications').removeClass('active');
        }
    });

    // Check if the button exists on the page
    if ($('.button.generate-ref').length) {
        // Bind a click event handler to the button
        $('.button.generate-ref').on('click', function (e) {
            e.preventDefault(); // Prevent the default button click behavior
            var $button = $(this);
            var href = $button.attr('href');

            // Disable the button
            $button.addClass('disabled').attr('disabled', true).text('A Gerar Referencia...');

            // Perform the AJAX request to generate the reference
            $.ajax({
                url: href,
                method: 'GET',
                success: function (response) {
                    // Enable the button and do any necessary actions with the response
                    $(document).ajaxComplete(function(event, xhr, settings) {
                        location.reload();
                    });
                },
                error: function (xhr, status, error) {
                    // Handle the error response
                    console.log(xhr.responseText);
                    // Enable the button and display an error message
                    $button.removeClass('disabled').attr('disabled', false).text('Generate Reference');
                    alert('An error occurred while generating the reference. Please try again.');
                }
            });
        });
    }
});