document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const messageElement = document.getElementById('message');

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        messageElement.textContent = '';
        messageElement.className = 'message';

        try {
            // ---- ÚNICA ALTERAÇÃO AQUI ----
            // Apontamos para o nosso novo arquivo de API em PHP
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password }),
            });

            const data = await response.json();
            
            // O resto do código continua igual, pois ele espera uma resposta JSON.
            if (data.success) {
                messageElement.textContent = 'Login bem-sucedido! Redirecionando...';
                messageElement.classList.add('success');
            } else {
                messageElement.textContent = data.message || 'E-mail ou senha inválidos.';
                messageElement.classList.add('error');
            }

        } catch (error) {
            console.error('Erro na requisição:', error);
            messageElement.textContent = 'Ocorreu um erro. Tente novamente.';
            messageElement.classList.add('error');
        }
    });
});