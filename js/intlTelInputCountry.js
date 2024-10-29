$(document).ready(function () {
  //console.log(123); 
var telInput = $("#phone"),
  errorMsg = $("#error-msg"),
  validMsg = $("#valid-msg");

// initialise plugin
  $("#phone").intlTelInput({

  allowExtensions: true,
  formatOnDisplay: false,
  autoFormat: true,
  autoHideDialCode: true,
  autoPlaceholder: true,
  defaultCountry: "auto",
  ipinfoToken: "yolo",

  nationalMode: false,
  numberType: "MOBILE",
  //onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
    preferredCountries: ['us', 'in','gb'],
  preventInvalidNumbers: true,
  separateDialCode: true,
  initialCountry: "us",
  geoIpLookup: function (callback) {
    $.get("http://ipinfo.io", function () { }, "jsonp").always(function (resp) {
      var countryCode = (resp && resp.country) ? resp.country : "";
      callback(countryCode);
    });
  },
  // utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.9/js/utils.js"
});

var reset = function () {
  telInput.removeClass("error");
  errorMsg.addClass("hide");
  validMsg.addClass("hide");
  };
  $("#phone").intlTelInput("setCountry", $("#countrySelectedIso").val());
// on blur: validate
  telInput.blur(function () {
    //console.log(telInput);
    //console.log($("#phone").intlTelInput("getNumber"));
    $("#countrySelectedCode").val($("#phone").intlTelInput('getSelectedCountryData').dialCode);
    $("#countrySelectedIso").val($("#phone").intlTelInput('getSelectedCountryData').iso2);
    //console.log($("#countrySelectedCode").val());
    //console.log($("#countrySelectedIso").val());
  reset();
  if ($.trim(telInput.val())) {
    if (telInput.intlTelInput("isValidNumber")) {
      validMsg.removeClass("hide");
    } else {
      telInput.addClass("error");
      errorMsg.removeClass("hide");
    }
  }
});

// on keyup / change flag: reset
telInput.on("keyup change", reset);

});
