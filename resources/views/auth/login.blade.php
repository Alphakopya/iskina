<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Iskina Pares')</title>

    @vite(['resources/scss/login.scss'])
</head>
<body>
    <main>
        <div class="login-container">
            <div class="login-image">
                <img src="{{ asset('images/IskinaParesLogo.jpg') }}" alt="Logo"/>
            </div>
            <div class="login-content">
                <form id="login-form">
                    <h2>Login</h2>
                    <p>Please fill in the fields below.</p>
                    <div class="form-group">
                        <p id="error-message" style="display: none;"></p>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" name="email" id="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <button type="submit" class="btn">Submit</button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {
            function checkInput() {
                $(".form-control").each(function () {
                    if ($(this).val().trim() !== "") {
                        $(this).addClass("has-text");
                    } else {
                        $(this).removeClass("has-text");
                    }
                });
            }

            // Check on page load
            checkInput();

            // Check when user types
            $(".form-control").on("input", function () {
                checkInput();
            });

            $("#login-form").submit(function (event) {
                event.preventDefault();

                axios.post("/login", {
                    email: $("#email").val(),
                    password: $("#password").val()
                })
                .then(response => {
                    if (response.data.status === "success") {
                        window.location.href = response.data.redirect;
                    } else {
                        $("#error-message").text("Login failed.").show();
                    }
                })
                .catch(error => {
                    console.error(error.response?.data || error.message);
                    $("#error-message").text("Invalid Credentials").show();
                });
            });
        });

    </script>
</body>
</html>
