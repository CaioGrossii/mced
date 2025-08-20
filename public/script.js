document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const messageElement = document.getElementById('message');

    loginForm.addEventListener('submit', async (event) => {
        // 1. Impede o comportamento padrão do formulário (que é recarregar a página)
        event.preventDefault();

        // 2. Pega os dados dos campos de input
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Limpa mensagens anteriores
        messageElement.textContent = '';
        messageElement.className = 'message';

        try {
            // 3. Envia os dados para o servidor (back-end) usando a API Fetch
            const response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password }), // Converte os dados para o formato JSON
            });

            // 4. Converte a resposta do servidor para JSON
            const data = await response.json();

            // 5. Mostra uma mensagem de sucesso ou erro para o usuário
            if (data.success) {
                messageElement.textContent = 'Login bem-sucedido! Redirecionando...';
                messageElement.classList.add('success');
                // Em um app real, você redirecionaria o usuário:
                // window.location.href = '/dashboard';
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