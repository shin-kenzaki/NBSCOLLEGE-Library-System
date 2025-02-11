<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <meta name="description" content="" />
    <meta name="author" content="" />

    <title>SB Admin 2 - Register</title>

    <!-- Custom fonts for this template-->
    <link
      href="vendor/fontawesome-free/css/all.min.css"
      rel="stylesheet"
      type="text/css"
    />
    <link
      href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
      rel="stylesheet"
    />

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet" />

    <style>
   
        .bg-login-image {
        background: url('../guest/inc/img/bg-login.JPG') center center no-repeat;
        background-size: cover;
        }


      /* Style for the select dropdown with larger size */
      .select-dropdown {
        display: block;
        width: 100%;
        height: 3.5em; /* Increased height for bigger dropdown */
        padding: 0.5rem 1rem; /* Adjusted padding for better spacing */
        font-size: 0.9rem; /* Larger font size */
        font-weight: 400;
        line-height: 1.5;
        color: #6e707e;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #d1d3e2;
        border-radius: 10rem; /* Rounded corners */
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      }

      /* Optional: Apply hover effect */
      .select-dropdown:hover {
        border-color: #5e72e4; /* Highlight border color on hover */
      }

      /* Optional: Focus state */
      .select-dropdown:focus {
        border-color: #5e72e4; /* Border color when focused */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        outline: none;
      }
    </style>
  </head>

  <body class="bg-gradient-primary">
    <div class="container">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
          <!-- Nested Row within Card Body -->
          <div class="row">
            <div class="col-lg-5 d-none d-lg-block bg-login-image"></div>
            <div class="col-lg-7">
              <div class="p-5">
                <div class="text-center">
                  <h1 class="h4 text-gray-900 mb-4">Guest Registration!</h1>
                </div>

                <form
                  class="user"
                  action=""
                  method="POST"
                  enctype="multipart/form-data"
                >
                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        id="school_id"
                        name="school_id"
                        placeholder="ID"
                        value=""
                        required
                      />
                    </div>
                    <div class="col-sm-6">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="firstname"
                        placeholder="First Name"
                        value=""
                        required
                      />
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="middle_init"
                        placeholder="Middle Initial"
                        value=""
                        maxlength="1"
                        pattern="[A-Za-z]"
                        title="Only one letter is allowed"
                      />
                    </div>

                    <div class="col-sm-6">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="lastname"
                        placeholder="Last Name"
                        value=""
                        required
                      />
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        id="contact_no"
                        name="contact_no"
                        placeholder="Contact No."
                        value=""
                        required
                      />
                    </div>
                    <div class="col-sm-6">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="address"
                        placeholder="Address"
                        value=""
                        required
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <input
                      type="text"
                      class="form-control form-control-user"
                      name="email"
                      placeholder="School Email"
                      value=""
                      required
                    />
                  </div>

                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="password"
                        class="form-control form-control-user"
                        name="password"
                        placeholder="Password"
                        required
                      />
                    </div>
                    <div class="col-sm-6">
                      <select
                        name="usertype"
                        class="form-control user select-dropdown"
                        required
                      >
                        <option value="">Select Role</option>
                        <option value="Student">Guest</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="id_type"
                        placeholder="ID Type"
                        value=""
                        required
                      />
                    </div>
                    <div class="col-sm-6">
                      <input
                        type="file"
                        class="form"
                        name="id_image"
                        accept=".png, .jpg, .jpeg"
                        required
                      />
                    </div>
                  </div>

                  <button
                    type="submit"
                    name="submit"
                    class="btn btn-primary btn-user btn-block"
                  >
                    Register Account
                  </button>

                  <hr />
                </form>

                <div class="text-center">
                  <a class="small" href="forgot-password.php"
                    >Forgot Password?</a
                  >
                </div>
                <div class="text-center">
                  <a class="small" href="index.php"
                    >Already have an account? Login!</a
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../inc/js/sb-admin-2.min.js"></script>
  </body>
</html>
