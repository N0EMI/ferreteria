/**
 * LÓGICA DE INICIO DE SESIÓN
 * Se conecta con api/login.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const errorMsg = document.getElementById('error-msg');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Capturar valores
            const correo = document.getElementById('correo').value.trim();
            const password = document.getElementById('password').value.trim();

            // Ocultar mensaje de error previo
            errorMsg.style.display = 'none';

            // 2. Validar campos vacíos (frontend)
            if (correo === "" || password === "") {
                errorMsg.innerText = "Por favor, complete todos los campos.";
                errorMsg.style.display = 'block';
                return;
            }

            try {
                // 3. Enviar datos al puente PHP
                const response = await fetch('./api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ correo, password })
                });

                // Verificar si la respuesta es correcta
                if (!response.ok) {
                    throw new Error("Error en el servidor local");
                }

                const result = await response.json();

                // 4. Manejar la respuesta del servidor
                if (result.success) {
                    // Guardar datos en sessionStorage (se borra al cerrar el navegador)
                    sessionStorage.setItem('usuario_id', result.usuario.id);
                    sessionStorage.setItem('usuario_nombre', result.usuario.nombre);
                    sessionStorage.setItem('usuario_rol', result.usuario.rol);
                    
                    // Redirigir al Dashboard
                    window.location.href = 'dashboard.html';
                } else {
                    // Mostrar error si las credenciales fallan
                    errorMsg.innerText = result.message || "Credenciales incorrectas.";
                    errorMsg.style.display = 'block';
                }

            } catch (error) {
                console.error('Error de conexión:', error);
                errorMsg.innerText = "No se pudo conectar con el servidor. Verifique XAMPP.";
                errorMsg.style.display = 'block';
            }
        });
    }
});