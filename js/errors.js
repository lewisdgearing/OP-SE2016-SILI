var errors = {};
//Registration Errors
errors["R002"] = {"field":"registerEmail", "errorMessage":"Please enter your email address"};
errors["R003"] = {"field":"registerEmail", "errorMessage":"Please enter a valid email address"};
errors["R004"] = {"field":"registerConfirmEmail", "errorMessage":"Please enter confirm email address"};
errors["R005"] = {"field":"registerConfirmEmail", "errorMessage":"Confirm email doesnt match Email Address"};
errors["R006"] = {"field":"registerEmail", "errorMessage":"This email has already been used to register"};
errors["R008"] = {"field":"registerFirstName", "errorMessage":"Please enter your First Name"};
errors["R009"] = {"field":"registerLastName", "errorMessage":"Please Enter your Last Name"};
errors["R010"] = {"field":"registerPassword", "errorMessage":"Your password does not meet the complexity requirements"};
errors["R011"] = {"field":"registerConfirmPassword", "errorMessage":"Confirm password does not match password"};
errors["R012"] = {"field":"registerConfirmPassword", "errorMessage":"Please confirm your password"};
errors["R013"] = {"field":"registerPassword", "errorMessage":"Please enter your password"};
errors["R014"] = {"field":"registerUserName", "errorMessage":"Please enter a UserName"};
errors["R015"] = {"field":"registerUserName", "errorMessage":"Your UserName does not meet the requirements"};
errors["R016"] = {"field":"registerUserName", "errorMessage":"The UserName has already been used, please choose another"};
//Login errors
errors["L002"] = {"field":"loginEmail", "errorMessage":"Please enter your email address"};
errors["L003"] = {"field":"loginEmail", "errorMessage":"Please enter a valid email address"};
errors["L004"] = {"field":"loginPassword", "errorMessage":"Please enter your password"};
errors["L005"] = {"field":"loginPassword", "errorMessage":"Incorrect password entered"};
errors["L006"] = {"field":"loginEmail", "errorMessage":"Please enter a valid email address"};