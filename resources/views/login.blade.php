<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-login">
    <div class="login-dark acomodo">
        <form id="loginForm" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="center">
                <img src="assets/img/LP.png" alt="Descripción de la imagen" width="100" height="100" class="invertida rounded-circle">
            </div>
            <br>
            @if(session('error'))
            <div class="alert alert-danger" role="alert" id="error-message">
                {{ session('error') }}
            </div>
            @endif

            @if($errors->has('inactive'))
            <div class="alert alert-danger" role="alert" id="inactive-message">
                {{ $errors->first('inactive') }}
            </div>
            @endif

            <div class="form-group position-relative">
                <input name="username" class="form-control uper placeholder-login" placeholder="Usuario" value="{{ old('username') }}">
                <i class="fas fa-user position-absolute start-0 top-50 translate-middle-y ms-2 icons-color"></i>
            </div>
            @error('username')
            <span class="text-danger">{{ $message }}</span>
            @enderror

            <br>

            <div class="form-group position-relative">
                <input type="password" name="password" class="form-control placeholder-login" id="password" placeholder="CONTRASEÑA" required>
                <i class="fas fa-lock position-absolute start-0 top-50 translate-middle-y ms-2 icons-color"></i>
                <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-2 pointer icons-color" id="togglePassword" style="margin-top: 2px;"></i>
            </div>

            <br>

            <div class="center">
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </div>
            
        </form>

        <!-- Modal de Cambio de Contraseña -->
        <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content no-border">
                    <form id="changePasswordForm">
                        <h5 class="center title">Se requiere cambio de contraseña</h5>
                        <br>
                        @csrf
                        <input type="hidden" id="modalUsername">
                    
                        <!-- Mensaje para la contraseña -->
                        <div class="form-text text-muted" id="passwordRequirements">
                            La contraseña debe cumplir con los siguientes requisitos:
                            <ul>
                                <li>Al menos una mayúscula</li>
                                <li>Un carácter especial</li>
                                <li>Un número</li>
                                <li>Al menos 8 caracteres</li>
                            </ul>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="newPassword" class="form-control" placeholder="Contraseña" required>
                                <i class="fas fa-eye toggle-password" id="toggleNewPassword" style="margin-left: 200px; margin-top: 2px;"></i>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirmPassword" class="form-control" id="confirmPassword" placeholder="Confirmar Contraseña" required>
                                <i class="fas fa-eye toggle-password" id="toggleConfirmPassword" style="margin-left: 200px; margin-top: 2px;"></i>
                            </div>
                        </div>

                        <div id="passwordError" class="text-danger mt-2"></div> <!-- Aquí se mostrará el mensaje de error -->
                        <div class="center">
                            <button type="submit" class="btn btn-primary mt-3">Cambiar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Fin Modal de Cambio de Contraseña -->

        <!-- Pasar rutas a JavaScript -->
        <script>
            window.routes = {
                login: "{{ route('login') }}",
                changePassword: "{{ route('changePassword') }}",
                index: "{{ route('index') }}"
            };
        </script>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
        <script src="{{ asset('js/login.js') }}"></script>
    </div>
</body>
</html>
