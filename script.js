// Aguarda o documento HTML ser completamente carregado
document.addEventListener('DOMContentLoaded', function() {
    
    // Seleciona os campos do formulário
    const form = document.getElementById('cadastroForm');
    const cpfInput = document.getElementById('cpf');
    const telefoneInput = document.getElementById('telefone');
    const feedbackDiv = document.getElementById('feedback');

    // MÁSCARAS DE INPUT
    // Máscara para CPF (000.000.000-00)
    cpfInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não for dígito
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    });

    // Máscara para Telefone ((00) 90000-0000)
    telefoneInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        e.target.value = value;
    });


    // VALIDAÇÃO NO ENVIO DO FORMULÁRIO
    form.addEventListener('submit', function(event) {
        // Impede o envio padrão do formulário para validarmos primeiro
        event.preventDefault(); 
        
        // --- NOVO --- Pega o valor do campo nome
        const nome = document.getElementById('nome').value;
        const email = document.getElementById('email').value;
        const senha = document.getElementById('senha').value;
        const cpf = cpfInput.value;
        const telefone = telefoneInput.value;
        
        // Limpa mensagens de feedback anteriores
        feedbackDiv.innerHTML = '';
        feedbackDiv.className = '';

        // Validações
        // --- NOVO --- Validação do campo nome
        if (nome.trim().length < 3) {
            exibirFeedback('O nome deve ter pelo menos 3 caracteres.', 'erro');
            return;
        }

        if (senha.length < 8) {
            exibirFeedback('A senha deve ter pelo menos 8 caracteres.', 'erro');
            return; // Para a execução
        }
        
        if (!validarCPF(cpf)) {
            exibirFeedback('CPF inválido. Por favor, verifique.', 'erro');
            return;
        }

        // Se tudo estiver correto, envia o formulário
        exibirFeedback('Cadastro enviado com sucesso!', 'sucesso');
        setTimeout(() => {
            form.submit();
        }, 1500); // Envia após 1.5 segundos para o usuário ver a mensagem

    });

    // Função para exibir mensagens ao usuário
    function exibirFeedback(mensagem, tipo) {
        feedbackDiv.textContent = mensagem;
        feedbackDiv.className = tipo;
    }

    // Função completa para validar CPF (algoritmo oficial)
    function validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g,''); // Remove caracteres não numéricos
        if(cpf == '') return false; 
        if (cpf.length != 11 || 
            cpf == "00000000000" || 
            cpf == "11111111111" || 
            cpf == "22222222222" || 
            cpf == "33333333333" || 
            cpf == "44444444444" || 
            cpf == "55555555555" || 
            cpf == "66666666666" || 
            cpf == "77777777777" || 
            cpf == "88888888888" || 
            cpf == "99999999999")
                return false;       

        // Valida 1o digito 
        let add = 0;    
        for (let i=0; i < 9; i ++)       
            add += parseInt(cpf.charAt(i)) * (10 - i);  
            let rev = 11 - (add % 11);  
            if (rev == 10 || rev == 11)     
                rev = 0;    
            if (rev != parseInt(cpf.charAt(9)))     
                return false;       
        
        // Valida 2o digito 
        add = 0;    
        for (let i = 0; i < 10; i ++)       
            add += parseInt(cpf.charAt(i)) * (11 - i);  
        rev = 11 - (add % 11);  
        if (rev == 10 || rev == 11) 
            rev = 0;    
        if (rev != parseInt(cpf.charAt(10)))
            return false;       
            
        return true;   
    }
});