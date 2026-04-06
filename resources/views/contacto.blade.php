<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Hawkins Suite - Gracias por reservar con nosotros</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-white">
    <div class="container text-center">
        <div class="row align-items-center justify-content-center">
            <div class="col-12 mt-5">
                <div class="logo">
                    <img src="https://apartamentosalgeciras.com/wp-content/uploads/2022/09/Logo-Hawkins-Suites.svg" alt="" class="img-fluid mb-3 w-75 m-auto">
                </div>
            </div>
            <div class="col-12 mt-3">
                <h2>Para cualquier consulta rellene el siguiente formulario.</h2>
                <p>Nuestro tiempo de respuesta es inferior a 30 minutos</p>
            </div>
            <div class="col-12 mt-4">
                <form action="" method="POST" class="row g-3 needs-validation" novalidate enctype="multipart/form-data">
                    @csrf
                    <div class="form-floating">
                        <input type="text" required class="form-control" id="nombre" placeholder="Nombre">
                        <label for="nombre">Nombre completo</label>
                        <div class="valid-feedback">
                            Correcto!
                        </div>
                        <div class="invalid-feedback">
                            El nombre es obligatorio.
                        </div>
                    </div>
                    <div class="form-floating mt-3">
                        <input type="text" required class="form-control" id="telefono" placeholder="Teléfono">
                        <label for="telefono">Numero de teléfono</label>
                        <div class="valid-feedback">
                            Correcto!
                        </div>
                        <div class="invalid-feedback">
                            El Telefono es obligatorio.
                        </div>
                    </div>
                    <div class="form-floating mt-3">
                        <input type="email" required class="form-control" id="email" placeholder="Correo Electronico">
                        <label for="email">Correo Electronico</label>
                        <div class="valid-feedback">
                            Correcto!
                        </div>
                        <div class="invalid-feedback">
                            El correo electronico es obligatorio.
                        </div>
                    </div>
                    <div class="form-floating mt-3">
                        <textarea class="form-control" required placeholder="Escriba su mensaje aqui..." name="mensaje" id="mensaje" style="height: 100px"></textarea>
                        <label for="mensaje">Mensaje</label>
                        <div class="valid-feedback">
                            Correcto!
                        </div>
                        <div class="invalid-feedback">
                            El mensaje es obligatorio.
                        </div>
                    </div>
                    <div class="form-floating mt-4">
                        <button class="btn btn-terminar w-100">Enviar</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
<script>
    (function () {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
        form.addEventListener('submit', function (event) {
            console.log(form.checkValidity())
            if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
            }

            form.classList.add('was-validated')
        }, false)
        })
    })()
</script>
</body>
</html>