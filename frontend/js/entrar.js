/* Login e cadastro. As mensagens vêm do backend (validação real); aqui só são exibidas. */
(function () {
  "use strict";
  const api = window.GameShelfAPI;
  const aviso = document.getElementById("mensagem");

  function mostrar(texto) {
    aviso.textContent = texto;
    aviso.classList.add("mensagem--erro");
    aviso.hidden = false;
  }
  function limpar(prefixo, campos) {
    aviso.hidden = true;
    campos.forEach(c => {
      document.getElementById(prefixo + "-" + c + "-erro").hidden = true;
      document.getElementById(prefixo + "-" + c).removeAttribute("aria-invalid");
    });
  }
  function mostrarErros(prefixo, erro) {
    let primeiro = null;
    Object.keys(erro.campos).forEach(campo => {
      const entrada = document.getElementById(prefixo + "-" + campo);
      const alvo = document.getElementById(prefixo + "-" + campo + "-erro");
      if (!entrada || !alvo) return;
      alvo.textContent = erro.campos[campo];
      alvo.hidden = false;
      entrada.setAttribute("aria-invalid", "true");
      primeiro = primeiro || entrada;
    });
    if (primeiro) primeiro.focus();
    else mostrar(erro.message);
  }
  function ligar(idForm, prefixo, campos, url) {
    document.getElementById(idForm).addEventListener("submit", async evento => {
      evento.preventDefault();
      limpar(prefixo, campos);
      const corpo = {};
      campos.forEach(c => { corpo[c] = document.getElementById(prefixo + "-" + c).value; });
      try {
        await api.post(url, corpo);
        window.location.href = "conta.html";
      } catch (erro) {
        mostrarErros(prefixo, erro);
      }
    });
  }
  ligar("form-login", "login", ["email", "senha"], "/api/auth/login");
  ligar("form-registro", "reg", ["nome", "email", "senha"], "/api/auth/registro");

  // Quem já está logado vai direto para a conta.
  api.get("/api/auth/me").then(() => { window.location.href = "conta.html"; }).catch(() => {});
})();
