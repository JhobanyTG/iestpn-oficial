@extends('layout.app')

@section('title', 'Registro | IESTPN')

@section('content')

<head>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

    <div class="contenido_login">
        <div class="container">
            <div class="row">
                <div class="col">
                    <img class="logo" src="images/logo/logo.png">
                </div>
                <h2 class="fw-bold instituto">INSTITUTO DE EDUCACION SUPERIOR TECNOLOGICO PUBLICO DE NUÑOA</h2>
                <!-- Register-->
                <form action="" method="POST">
                    @csrf
                    <input type="text" placeholder="Nombre" id="name" name="name" class="form-control" required>
                    <input type="text" placeholder="Correo" id="email" name="email" class="form-control" required>
                    <input type="password" placeholder="Contraseña" id="password" name="password" class="form-control" required>
                    @error('password')
                    <p>* Las contraseñas no coinciden.</p>
                    @enderror
                    <input type="password" placeholder="Confirmar Contraseña" id="password_confirmation" name="password_confirmation" class="form-control" required>
                    <label for="role">Rol:</label>
                    <select id="role" name="role">
                        <option value="admin">Admin</option>
                        <option value="administrador" selected>Administrador</option>
                    </select>
                    <button type="submit" class="boton_registrar"> Registrar </button>
                </form>
                <a href="{{ route('usuarios.index') }}"  class="boton_cancelar"><button type="submit" class="boton_salir">Cancelar</button></a>
            </div>
        </div>
    </div>
</body>


@endsection
