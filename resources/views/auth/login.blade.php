<!doctype html>
<html lang="en">


<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--favicon-->
    <link rel="icon" href="{{assets('backend/images/favicon-32x32.png')}}" type="image/png" />
    <link href="{{assets('backend/plugins/simplebar/css/simplebar.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/perfect-scrollbar/css/perfect-scrollbar.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/metismenu/css/metisMenu.min.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/datatable/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/bootstrap-extended.css')}}" rel="stylesheet">
    <link href="{{assets('backend/fonts.googleapis.com/css276c7.css')}}?family=Roboto:wght@400;500&amp;display=swap" rel="stylesheet">
    <link href="{{assets('backend/css/app.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/custom.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/icons.css')}}" rel="stylesheet">

    <title>AGRO IOT</title>
</head>

@php
    $app_name = configs(['app_name']);
    $app_logo = configs(['app_logo']);
@endphp

<body class="bg-theme bg-theme2">
<!--wrapper-->
<div class="wrapper">
    <div class="section-authentication-signin d-flex align-items-center justify-content-center my-5 my-lg-0">
        <div class="container">
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3">
                <div class="col mx-auto">
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="p-4">
                                <div class="mb-3 text-center">
                                    <img src="{{$app_logo}}" alt="App Logo" height="60">
                                </div>
                                <div class="text-center mb-4">
                                    <h5 class="">Welcome Back</h5>
                                    <p class="mb-0">Please log in to your account</p>
                                </div>
                                <div class="form-body">
                                    <form class="row g-3" method="post" action="{{route('login.submit')}}">
                                        {{csrf_field()}}
                                        <div class="col-12">
                                            <label for="inputEmailAddress" class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" id="inputEmailAddress" >
                                        </div>
                                        <div class="col-12">
                                            <label for="inputChoosePassword" class="form-label">Password</label>
                                            <div class="input-group" id="show_hide_password">
                                                <input type="password" name="password" class="form-control border-end-0" id="password" placeholder="Enter Password">
                                                <a onclick="togglePassword()" id="toggleBtn" class="input-group-text bg-transparent pointer">
                                                    <i class="bx bxs-show"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" name="remember_me" type="checkbox" id="flexSwitchCheckChecked">
                                                <label class="form-check-label pointer" for="flexSwitchCheckChecked">Remember Me</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">	<a href="auth-basic-forgot-password.html">Forgot Password ?</a>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-light">Sign in</button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-center ">
                                                <p class="mb-0">Registered dealers, please <a href="auth-basic-signup.html">Sign In</a> in here?
                                                </p>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end row-->
        </div>
    </div>
</div>

<script src="{{assets('backend/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{assets('backend/js/jquery.min.js')}}"></script>
<script src="{{assets('backend/plugins/simplebar/js/simplebar.min.js')}}"></script>
<script src="{{assets('backend/plugins/metismenu/js/metisMenu.min.js')}}"></script>
<script src="{{assets('backend/plugins/perfect-scrollbar/js/perfect-scrollbar.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>
<script src="{{assets('backend/js/app.js')}}"></script>
<script>
    function togglePassword() {
        const passwordField = document.getElementById("password");
        const toggleBtn = document.getElementById("toggleBtn");

        if (passwordField.type === "password") {
            passwordField.type = "text";
            toggleBtn.innerHTML = "<i class='bx bxs-hide'></i>";
        } else {
            passwordField.type = "password";
            toggleBtn.innerHTML = "<i class='bx bxs-show'></i>";
        }
    }
</script>
</body>

</html>
