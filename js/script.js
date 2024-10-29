
setTimeout(function() {
// Ensure the variables are properly defined
var paymentMethodVar = typeof ampaCashVars !== 'undefined' ? ampaCashVars.paymentMethodVar : 'ampacash_veprap';
var isUserLoggedInVar = typeof ampaCashVars !== 'undefined' ? ampaCashVars.isUserLoggedInVar : '';
var merchantIdVar = typeof ampaCashVars !== 'undefined' ? ampaCashVars.merchantIdVar : '';

// Function to show/hide the order button
function toggleOrderButton(display) {
    console.log("place-order button visibility:", display);
    document.getElementById("place_order").style.display = display;
    document.getElementById("def").style.display = !display;
}

// Function to validate billing details
function validateBillingDetails() {
    console.log("validating...");
    var isValid = true;

    // Clear previous errors
    jQuery("#ampa-cash-errors").html("");

    // Check all required fields with class "validate-required" and without style="display: none;"
    jQuery(".validate-required:visible").each(function () {
        var fieldId = jQuery(this).find("input, select, textarea").attr("id");
        var fieldValue = jQuery(this).find("input, select, textarea").val();
        
        // Custom validation for ZIP code format
        if (fieldId === "billing_postcode") {
            if (!isValidZIP(fieldValue)) {
                isValid = false;
                var errorMessage = "<div class='wc-block-components-notice-banner is-error' role='alert'><div class='wc-block-components-notice-banner__content'>Billing ZIP Code is not a valid postcode / ZIP.</div></div>";
                jQuery("#ampa-cash-errors").append(errorMessage);
            }
        }

        if (!fieldValue) {
            console.log("invalid fields found...");
            isValid = false;

            // Display the error message
            var errorMessage = "<div class='wc-block-components-notice-banner is-error' role='alert'><div class='wc-block-components-notice-banner__content'><strong>" + fieldId + "</strong> is a required field.</div></div>";
            jQuery("#ampa-cash-errors").append(errorMessage);
        }
    });

    return isValid;
}

// Function to validate ZIP code format
function isValidZIP(zip) {
    var zipRegex = /^[a-zA-Z0-9]{5,6}$/;
    return zipRegex.test(zip);
}

// Function to check if the user is logged in
function isUserLoggedIn() {
    return isUserLoggedInVar;
}

jQuery(function ($) {
    paymentMethodVar = $("input[name='payment_method']:checked").val(); // Get the initially selected payment method
    
    // Check local storage for payment success status
    if (localStorage.getItem("paymentStatus") === "success") {
        document.getElementById("success_def").innerHTML = "Payment Received.";
        // toggleOrderButton("block");
    } else {
        // Hide the order button by default if the payment method is veprap
        if (localStorage.getItem("paymentStatus") !== "success" && paymentMethodVar === "ampacash_veprap") {
            toggleOrderButton("none");
        }
    }

    // Handle payment method change event
    $(document.body).on("change", "input[name='payment_method']", function () {
        paymentMethodVar = $(this).val(); // Update the variable when payment method changes
                
        console.log("paymentMethodVar: ", paymentMethodVar);
        console.log("paymentMethodVar === 'ampacash_veprap'?: ", paymentMethodVar === "ampacash_veprap");
        toggleOrderButton(paymentMethodVar === "ampacash_veprap" && $("#success_def").html().trim() === "" ? "none" : "block");
    });

    // Handle AmpaCash button hover event (onmouseover)
    $(document.body).on("mouseover", "#def a", function () {
        if (validateBillingDetails() && isUserLoggedIn()) {
            // Get the merchant ID
            var merchantId = merchantIdVar;
            $("#def").attr("merchantid", merchantId);
        } else {
            $("#def").removeAttr("merchantid");
            if (!isUserLoggedIn()) {
                var errorMessage = "<div class='wc-block-components-notice-banner is-error' role='alert'><div class='wc-block-components-notice-banner__content'>You must be logged in to use this payment method.</div></div>";
                jQuery("#ampa-cash-errors").append(errorMessage);
            }
        }
    });

    // Reset payment status on page load
    $(window).on('load', function() {
        localStorage.removeItem("paymentStatus");
        document.getElementById("success_def").innerHTML = "";
       
        if ($("input[name='payment_method']:checked").val() !== "ampacash_veprap") {
            toggleOrderButton("block");
        } else {
            toggleOrderButton("none");
        }
    });
});


console.log("Script loaded");
document.querySelector('#def').addEventListener("onsuccess", (event) => {
    console.log("event lister not initialized...");
    if (event.detail.data.data["success"]) {
        console.log(event.detail.data.data["message"]);
        if (document.getElementById("success_def").innerHTML !== "Payment Received.") {
            document.getElementById("success_def").innerHTML = "Payment Received.";
            // toggleOrderButton("block");
        }
        document.getElementById("ampa-cash-errors").style.display = "none";
        document.getElementById("error_def").innerHTML = "";

        // Set payment status to success in local storage
        localStorage.setItem("paymentStatus", "success");
        // Automatically submit the order form
        setTimeout(function() {
            jQuery('form.checkout').submit();
        }, 100);
        
    } else {
        console.log("error has been logged: ", event.detail.data.data["message"]);
        if (document.getElementById("success_def").innerHTML !== "Payment Received.") {
            document.getElementById("success_def").innerHTML = "";
            document.getElementById("ampa-cash-errors").style.display = "block";
            document.getElementById("error_def").innerHTML = event.detail.data.data["message"];
        }
    }
});
}, 1000);
