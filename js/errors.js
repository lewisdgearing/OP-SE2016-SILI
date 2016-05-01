var errors = {};
//UserAPI Errors
errors["U001"] = {"field":"Email", "errorMessage":"Please enter your email address"};
errors["U002"] = {"field":"Email", "errorMessage":"Please enter a valid email address"};
errors["U003"] = {"field":"Password", "errorMessage":"Please enter your password"};
errors["U004"] = {"field":"Password", "errorMessage":"Incorrect password entered"};
errors["U005"] = {"field":"Email", "errorMessage":"Email has not been used to register"};
errors["U007"] = {"field":"ConfirmEmail", "errorMessage":"Please enter confirm email address"};
errors["U008"] = {"field":"ConfirmEmail", "errorMessage":"Confirm email doesnt match Email Address"};
errors["U009"] = {"field":"Email", "errorMessage":"This email has already been used to register"};
errors["U010"] = {"field":"FirstName", "errorMessage":"Please enter your First Name"};
errors["U011"] = {"field":"LastName", "errorMessage":"Please Enter your Last Name"};
errors["U012"] = {"field":"Password", "errorMessage":"Your password does not meet the complexity requirements"};
errors["U013"] = {"field":"ConfirmPassword", "errorMessage":"Confirm password does not match password"};
errors["U014"] = {"field":"ConfirmPassword", "errorMessage":"Please confirm your password"};
errors["U015"] = {"field":"UserName", "errorMessage":"Please enter a UserName"};
errors["U016"] = {"field":"UserName", "errorMessage":"Your UserName does not meet the requirements"};
errors["U017"] = {"field":"UserName", "errorMessage":"The UserName has already been used, please choose another"};