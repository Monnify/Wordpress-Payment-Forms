(function ($) {
    "use strict";
    $(document).ready(
        function ($) {

            if ( $("#pf-vamount").length ) {
                  var amountField = $("#pf-vamount");
                  calculateTotal();
            } else {
                var amountField = $("#pf-amount");
            }
            var max = 10;
            amountField.keydown(
                function (e) {
                    format_validate(max, e);
                }
            );

            amountField.keyup(
                function (e) {
                    checkMinimumVal();
                }
            );

            $.fn.digits = function () {
                return this.each(
                    function () {
                        $(this).text(
                            $(this)
                            .text()
                            .replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")
                        );
                    }
                );
            };

            $(".pf-number").keydown(
                function (event) {
                    if (event.keyCode == 46
                        || event.keyCode == 8
                        || event.keyCode == 9
                        || event.keyCode == 27
                        || event.keyCode == 13
                        || (event.keyCode == 65 && event.ctrlKey === true)
                        || (event.keyCode >= 35 && event.keyCode <= 39)
                    ) {
                        return;
                    } else {
                        if (event.shiftKey
                            || ((event.keyCode < 48 || event.keyCode > 57)
                            && (event.keyCode < 96 || event.keyCode > 105))
                        ) {
                            event.preventDefault();
                        }
                    }
                }
            );

            if ($("#pf-quantity").length) {
				$( "#pf-quantity" ).on( 'change', function(event){
					checkMinimumVal();
				} );
                calculateTotal();
            };

            $("#pf-quantity, #pf-vamount, #pf-amount").on(
                "change", function () {
                    calculateTotal();
                }
            );

            $(".monnify-form").on(
                "submit", function (e) {
                    var requiredFieldIsInvalid = false;
                    e.preventDefault();

                    $("#pf-agreementicon").removeClass("rerror");

                    $(this)
                    .find("input, select, textarea")
                    .each(
                        function () {
                            $(this).removeClass("rerror");
                        }
                    );
                    var email = $(this)
                    .find("#pf-email")
                    .val();

                    var amount;
                    if ($("#pf-vamount").length) {
                        amount = $("#pf-vamount").val();
                        calculateTotal();
                    } else {
                        amount = $(this)
                        .find("#pf-amount")
                        .val();
                    }

                    if (Number(amount) > 0) {
                    } else {
                        $(this)
                        .find("#pf-amount,#pf-vamount")
                        .addClass("rerror");
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }
                    if (!validateEmail(email)) {
                        $(this)
                        .find("#pf-email")
                        .addClass("rerror");
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }

                    if (checkMinimumVal() == false) {
                        $(this)
                        .find("#pf-amount")
                        .addClass("rerror");
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }

                    $(this)
                    .find("input, select, text, textarea")
                    .filter("[required]")
                    .filter(
                        function () {
                            return this.value === "";
                        }
                    )
                    .each(
                        function () {
                            $(this).addClass("rerror");
                            requiredFieldIsInvalid = true;
                        }
                    );

                    if ($("#pf-agreement").length && !$("#pf-agreement").is(":checked")) {
                        $("#pf-agreementicon").addClass("rerror");
                        requiredFieldIsInvalid = true;
                    }

                    if (requiredFieldIsInvalid) {
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }

                    var self = $(this);
                    var $form = $(this);

                    $.blockUI({ message: "Please wait..." });

                    var formdata = new FormData(this);

                    $.ajax(
                        {
                            url: $form.attr("action"),
                            type: "POST",
                            data: formdata,
                            mimeTypes: "multipart/form-data",
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "JSON",
                            success: function (data) {
                                $.unblockUI();

                                if ( data.result != "success" ) {
                                    alert(data.message);
                                    return;
                                }

                                data.custom_fields.push(
                                    {
                                        "display_name": "Plugin",
                                        "variable_name": "plugin",
                                        "type": "text",
                                        "value": "mff-monnify"
                                    }
                                );

                                var quantity = data.quantity;

                                $("#pf-nonce").val(data.invoiceNonce);

                                var flatMetadata = {};
                                data.custom_fields.forEach(function (field) {
                                    flatMetadata[field.variable_name] = field.value;
                                });

                                var sdkConfig = {
                                    apiKey: mffSettings.apiKey,
                                    contractCode: mffSettings.contractCode,
                                    amount: Number(data.total),
                                    currency: data.currency,
                                    reference: data.code,
                                    customerEmail: data.email,
                                    customerFullName: data.name,
                                    paymentDescription: data.paymentDescription,
                                    metadata: flatMetadata,
                                    onComplete: function () {
                                        $.blockUI({ message: "Please wait..." });
                                        $.post(
                                            $form.attr("action"),
                                            {
                                                action: "mff_monnify_confirm_action",
                                                code: data.code,
                                                quantity: quantity,
                                                nonce: data.confirmNonce
                                            },
                                            function (newdata) {
                                                newdata = JSON.parse(newdata);
                                                if (newdata.result == "success2") {
                                                    window.location.href = newdata.link;
                                                }
                                                if (newdata.result == "success") {
                                                    $(".monnify-form")[0].reset();
                                                    $("html,body").animate(
                                                        { scrollTop: $(".monnify-form").offset().top - 110 },
                                                        500
                                                    );

                                                    self.before('<div class="alert-success">' + newdata.message + '</div>');

                                                    $.unblockUI();
                                                } else {
                                                    self.before('<div class="alert-danger">' + newdata.message + '</div>');
                                                    $.unblockUI();
                                                }
                                            }
                                        );
                                    },
                                    onClose: function () {
                                        // Unblock regardless of why the widget closed (cancelled, failed,
                                        // or dismissed) - the confirm-payment ajax calls above already
                                        // handle their own unblockUI on completion, so this is a safe no-op
                                        // in that case.
                                        $.unblockUI();
                                    }
                                };

                                if (data.incomeSplitConfig) {
                                    sdkConfig.incomeSplitConfig = data.incomeSplitConfig;
                                }

                                MonnifySDK.initialize(sdkConfig);
                            },
                            error: function (xhr, status, error) {
                                $.unblockUI();
                                console.log("An error occurred");
                                console.log("XHR: ", xhr);
                                console.log("Status: ", status);
                                console.log("Error: ", error);
                                alert("Something went wrong submitting the form. Please try again.");
                            }
                        }
                    );
                }
            );

            $(".retry-form").on(
                "submit", function (e) {
                    var self = $(this);
                    var $form = $(this);
                    e.preventDefault();

                    $.blockUI({ message: "Please wait..." });

                    var formdata = new FormData(this);

                    $.ajax(
                        {
                            url: $form.attr("action"),
                            type: "POST",
                            data: formdata,
                            mimeTypes: "multipart/form-data",
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "JSON",
                            success: function (data) {
                                $.unblockUI();

                                if (data.result != "success") {
                                    alert(data.message);
                                    return;
                                }

                                data.custom_fields.push(
                                    {
                                        "display_name": "Plugin",
                                        "variable_name": "plugin",
                                        "type": "text",
                                        "value": "mff-monnify"
                                    }
                                );

                                var quantity = data.quantity;

                                $("#pf-nonce").val(data.retryNonce);

                                var flatMetadata = {};
                                data.custom_fields.forEach(function (field) {
                                    flatMetadata[field.variable_name] = field.value;
                                });

                                var sdkConfig = {
                                    apiKey: mffSettings.apiKey,
                                    contractCode: mffSettings.contractCode,
                                    amount: Number(data.total),
                                    currency: data.currency,
                                    reference: data.code,
                                    customerEmail: data.email,
                                    customerFullName: data.name,
                                    paymentDescription: data.paymentDescription,
                                    metadata: flatMetadata,
                                    onComplete: function () {
                                        $.blockUI({ message: "Please wait..." });
                                        $.post(
                                            $form.attr("action"),
                                            {
                                                action: "mff_monnify_confirm_action",
                                                code: data.code,
                                                quantity: quantity,
                                                retry: true,
                                                nonce: data.confirmNonce
                                            },
                                            function (newdata) {
                                                newdata = JSON.parse(newdata);
                                                if (newdata.result == "success2") {
                                                    window.location.href = newdata.link;
                                                }
                                                if (newdata.result == "success") {
                                                    var currentUrl = window.location.href;
                                                    var url = new URL(currentUrl);
                                                    window.location.href = url.origin + url.pathname;
                                                } else {
                                                    self.before('<div class="alert-danger">' + newdata.message + '</div>');
                                                    $.unblockUI();
                                                }
                                            }
                                        );
                                    },
                                    onClose: function () {
                                        // Unblock regardless of why the widget closed (cancelled, failed,
                                        // or dismissed) - the confirm-payment ajax calls above already
                                        // handle their own unblockUI on completion, so this is a safe no-op
                                        // in that case.
                                        $.unblockUI();
                                    }
                                };

                                if (data.incomeSplitConfig) {
                                    sdkConfig.incomeSplitConfig = data.incomeSplitConfig;
                                }

                                MonnifySDK.initialize(sdkConfig);
                            },
                            error: function (xhr, status, error) {
                                $.unblockUI();
                                console.log("An error occurred");
                                console.log("XHR: ", xhr);
                                console.log("Status: ", status);
                                console.log("Error: ", error);
                                alert("Something went wrong submitting the form. Please try again.");
                            }
                        }
                    );
                }
            );

			function checkMinimumVal() {
				if ( $("#pf-amount").length ) {
					var min_amount = Number($("#pf-amount").attr('min'));
					var amt = Number($("#pf-amount").val());
					var quantity = 1;

					if ( $("#pf-quantity").length ) {
						quantity = $("#pf-quantity").val();
					}

					amt = amt * quantity;

					if (min_amount > 0 && amt < min_amount) {
						$("#pf-min-val-warn").text( "Amount cannot be less than the minimum amount");
						return false;
					} else {
						$("#pf-min-val-warn").text("");
						$("#pf-amount").removeClass("rerror");
					}
				}
			}

			function format_validate(max, e) {
				var value = amountField.text();
				if (e.which != 8 && value.length > max) {
					e.preventDefault();
				}
				// Allow: backspace, delete, tab, escape, enter and .
				if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1
					// Allow: Ctrl+A
					|| (e.keyCode == 65 && e.ctrlKey === true)
					// Allow: Ctrl+C
					|| (e.keyCode == 67 && e.ctrlKey === true)
					// Allow: Ctrl+X
					|| (e.keyCode == 88 && e.ctrlKey === true)
					// Allow: home, end, left, right
					|| (e.keyCode >= 35 && e.keyCode <= 39)
				) {
					// let it happen, don't do anything
					return;
				}
				// Ensure that it is a number and stop the keypress
				if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57))
					&& (e.keyCode < 96 || e.keyCode > 105)
				) {
					e.preventDefault();
				}
			}

			function calculateTotal() {
				var unit;

				if ($("#pf-vamount").length) {
					unit = $("#pf-vamount").val();
					var name = $("#pf-vamount option:selected").attr("data-name");
					$("#pf-vname").val(name);
				} else {
					unit = $("#pf-amount").val();
				}
				var quant = $("#pf-quantity").val();

				var newvalue = unit * quant;

				if (quant == "" || quant == null) {
					quant = 1;
				} else {
					$("#pf-total").val(newvalue);
				}

			}

			function validateEmail(email) {
				var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				return re.test(email);
			}

        }
    );
})(jQuery);
