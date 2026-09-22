/* Demonstração local: não armazena dados nem envia e-mails. */
(function () {
  "use strict";
  const formulario = document.getElementById("form-sugestao");
  const validacao = window.GameShelfValidacao;
  if (!formulario || !validacao) return;
  const retorno = document.getElementById("retorno");
  const texto = document.getElementById("retorno-texto");
  const regras = [
    { id: "jogo", obrigatorio: true, maxTexto: 80, erro: "Informe um título de até 80 caracteres." },
    { id: "email", obrigatorio: true, tipo: "email", maxTexto: 254, erro: "Informe um e-mail válido, como teste@example.com." },
    { id: "motivo", obrigatorio: true, maxTexto: 600, erro: "Escreva uma justificativa de até 600 caracteres." }
  ];
  validacao.preparar(formulario);
  formulario.addEventListener("submit", evento => {
    evento.preventDefault();
    retorno.hidden = true;
    if (!validacao.validar(regras)) return;
    const titulo = document.getElementById("jogo").value.trim();
    texto.textContent = "Simulação concluída para “" + titulo + "”. Nenhum dado foi enviado ou armazenado, e você não receberá e-mail.";
    formulario.reset();
    retorno.hidden = false;
    retorno.setAttribute("tabindex", "-1");
    retorno.focus();
  });
  formulario.hidden = false;
})();
