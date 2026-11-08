/* Busca e filtros sobre o HTML existente: os jogos continuam legíveis sem JS. */
(function () {
  "use strict";
  const formulario = document.getElementById("form-filtros");
  const lista = document.getElementById("lista-jogos");
  if (!formulario || !lista) return;
  const busca = document.getElementById("busca");
  const genero = document.getElementById("genero");
  const ordem = document.getElementById("ordem");
  const consoleFiltro = document.getElementById("console");
  const minhaLista = document.getElementById("minha-lista");
  const contador = document.getElementById("contador");
  const vazio = document.getElementById("vazio");
  let jogos = Array.from(lista.querySelectorAll(".jogo"));

  function normalizar(texto) {
    return texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }
  function numero(valor) {
    return valor === "" || valor == null ? null : Number(valor);
  }
  function atendeDuracao(horas, faixa) {
    if (faixa === "todas") return true;
    if (horas === null || !Number.isFinite(horas)) return false;
    if (faixa === "curta") return horas < 10;
    if (faixa === "media") return horas >= 10 && horas <= 30;
    return horas > 30;
  }
  function aplicar() {
    const termo = normalizar(busca.value.trim());
    const plataformas = Array.from(formulario.querySelectorAll('input[name="plataforma"]:checked'), campo => campo.value);
    const faixa = formulario.querySelector('input[name="duracao"]:checked').value;
    let visiveis = 0;
    jogos.forEach(jogo => {
      const dados = jogo.dataset;
      const nome = normalizar(dados.titulo + " " + (dados.estudio || "") + " " + (dados.alias || ""));
      const mesmasPlataformas = plataformas.length === 0 || plataformas.some(p => dados.plataformas.split(" ").includes(p));
      const mostrar = nome.includes(termo) && (genero.value === "todos" || genero.value === dados.genero)
        && mesmasPlataformas && atendeDuracao(numero(dados.horas), faixa)
        && (consoleFiltro.value === "todos" || (dados.consoles || "").split(" ").includes(consoleFiltro.value))
        && (minhaLista.value === "todas" || (dados.lista || "nenhuma") === minhaLista.value);
      jogo.hidden = !mostrar;
      if (mostrar) visiveis += 1;
    });
    const chave = { ano: "ano", nota: "nota", duracao: "horas" }[ordem.value];
    const foco = document.activeElement;
    jogos.slice().sort((a, b) => {
      if (chave) {
        const va = numero(a.dataset[chave]);
        const vb = numero(b.dataset[chave]);
        // Valores não informados vão para o fim, independentemente da ordem.
        if (va === null && vb !== null) return 1;
        if (vb === null && va !== null) return -1;
        if (va !== null && vb !== null && va !== vb) return chave === "horas" ? va - vb : vb - va;
      }
      return a.dataset.titulo.localeCompare(b.dataset.titulo, "pt-BR");
    }).forEach(jogo => lista.appendChild(jogo));
    // Mover cartões durante a ordenação não deve perder o foco do seletor.
    if (foco && foco.closest(".jogo") && !foco.closest(".jogo").hidden && document.activeElement !== foco) foco.focus();
    contador.textContent = "Mostrando " + visiveis + " de " + jogos.length + " jogos.";
    vazio.hidden = visiveis !== 0;
  }
  formulario.addEventListener("input", aplicar);
  formulario.addEventListener("change", aplicar);
  formulario.addEventListener("reset", () => window.setTimeout(aplicar, 0));
  formulario.addEventListener("submit", evento => { evento.preventDefault(); aplicar(); });
  // O cadastro avisa por evento para não depender da ordem de inicialização.
  document.addEventListener("gameshelf:acervo-alterado", () => {
    jogos = Array.from(lista.querySelectorAll(".jogo"));
    aplicar();
  });
  document.addEventListener("gameshelf:lista-alterada", aplicar);
  formulario.hidden = false;
  aplicar();
})();
