function HowLong(text, min, max){
    min = min || 1;
    max = max || 10000;
 
    if (text.length < min || text.length > max) {
        return false;
    }
    return true;
}

function ValidateURL(form) {
    var str = form.ipn_url_new.value;

    var errors = [];

    var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
      '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name and extension
      '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
      '(\\:\\d+)?'+ // port
      '(\\/[-a-z\\d%_.~+&:]*)*'+ // path
      '(\\?[;&a-z\\d%_.,~+&:=-]*)?'+ // query string
      '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
    var result = pattern.test(str);
    if (!result) {
        errors.push("URL is invalid!");
    } 

    if (errors.length > 0) {
        ReportErrors(errors);
        return false;
    }
    return true;
}

function ValidateLogin(form){
    var email = form.email.value;
    var password = form.password.value;

    var errors = [];

   
    var regex_number =  /[0-9]/;
    var regex_letter = /[a-z]/;
  
    //very liberal email validation
    if (!HowLong(email)) {
        errors.push("Email field empty!");
    } else if (email.indexOf('@') === -1 || email.indexOf('.') === -1 || (/\s/.test(email)) || email.length < 5 || email.length > 320) {
        errors.push("Email is invalid!");
    }

    if (!HowLong(password)) {
        errors.push("Password field empty!");
    } else if (/\s/.test(password)) {
         errors.push("Password is invalid!");
    } else if (password == email) {
        errors.push("Password is invalid!");
    } else if(!regex_number.test(password) || !regex_letter.test(password) || password.length < 8) {
        errors.push("Password is invalid!");
    }

    if (errors.length > 0) {
        ReportErrors(errors);
        return false;
    }
    return true;
}


function ValidateRegister(form){
    var password = form.password.value;
    var password_retyped = form.password_retyped.value;
    var email = form.email.value;
    var captcha = form.captcha_code.value;

   

    var errors = [];

    var regex_number =  /[0-9]/;
    var regex_letter = /[a-z]/;

    if (!HowLong(password)) {
        errors.push("Password field empty!");
    } else if (/\s/.test(password)) {
         errors.push("Password may not contain whitespace!");
    } else if (password !== password_retyped) {
        errors.push("Passwords not equal!");
    } else if (password == email) {
        errors.push("Password may not be same as email!");
    } 

    if(!regex_number.test(password) || !regex_letter.test(password) || password.length < 8) {
        errors.push("Password must be at least 8 characters and must contain at least one lowercase letter (a-z), and one number (0-9)!");
    }

    //very liberal email validation
    if (!HowLong(email)) {
        errors.push("Email field empty!");
    } else if (email.indexOf('@') === -1 || email.indexOf('.') === -1 || (/\s/.test(email)) || email.length < 5 || email.length > 320) {
        errors.push("Email is invalid!");
    }
    
    if (!HowLong(captcha)) {
        errors.push("Captcha field empty!");
    }

    if (!document.getElementById('terms_and_conditions_checkbox').checked) {
        errors.push("Please agree to the Terms And Conditions!");
    }

    
    if (errors.length > 0) {
        ReportErrors(errors);
        return false;
    }
 
    return true;
}

function ReportErrors(errors){
    var msg = "Validation errors!\n";
    var numError;
    for (var i = 0; i<errors.length; i++) {
        numError = i + 1;
        msg += "<br>" + numError + ". " + errors[i];
    }
    
    var error = document.getElementsByClassName("error")[0];
    
    error.innerHTML=msg;
    error.style.display = 'block';

}
