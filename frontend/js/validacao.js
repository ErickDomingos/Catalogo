/* Regras compartilhadas: mensagem junto ao campo, aria-invalid e foco no erro. */
(function () {
  "use strict";
  function limparCampo(campo) {
    campo.removeAttribute("aria-invalid");
    const erro = document.getElementById(campo.id + "-erro");
    if (erro) erro.hidden = true;
  }
  function marcar(campo, mensagem) {
    const id = campo.id + "-erro";
    let erro = document.getElementById(id);
    if (!erro) {
      erro = document.createElement("p");
      erro.id = id;
      erro.className = "erro-campo";
      campo.parentElement.appendChild(erro);
      campo.setAttribute("aria-describedby", ((campo.getAttribute("aria-describedby") || "") + " " + id).trim());
    }
    erro.textContent = mensagem;
    erro.hidden = false;
    campo.setAttribute("aria-invalid", "true");
  }
  function mensagem(campo, regra) {
    const valor = campo.value.trim();
    if (regra.tipo === "numero" && campo.validity.badInput) return regra.erro;
    if (!valor) return regra.obrigatorio ? regra.erro : "";
    if (regra.maxTexto && valor.length > regra.maxTexto) return regra.erro;
    if (regra.tipo === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) return regra.erro;
    if (regra.tipo === "numero") {
      const n = Number(valor);
      const passos = n / regra.passo;
      if (!Number.isFinite(n) || n < regra.min || n > regra.max || Math.abs(passos - Math.round(passos)) > 0.000001) return regra.erro;
    }
    return "";
  }
  function validar(regras) {
    let primeiroErro = null;
    regras.forEach(regra => {
      const campo = document.getElementById(regra.id);
      limparCampo(campo);
      const erro = mensagem(campo, regra);
      if (erro) {
        marcar(campo, erro);
        if (!primeiroErro) primeiroErro = campo;
      }
    });
    if (primeiroErro) primeiroErro.focus();
    return !primeiroErro;
  }
  function preparar(formulario) {
    formulario.addEventListener("input", evento => limparCampo(evento.target));
    formulario.addEventListener("reset", () => {
      formulario.querySelectorAll('[aria-invalid="true"]').forEach(limparCampo);
    });
  }
  window.GameShelfValidacao = { validar, preparar };
})();
