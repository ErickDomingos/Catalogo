/*
 * Página inicial: o resumo "N títulos · M gêneros" vem do banco (catálogo oficial), então acompanha
 * as inclusões e exclusões feitas pelo administrador. Se a API não responder, fica o texto do HTML.
 */
(function () {
  "use strict";
  const alvo = document.getElementById("totais-catalogo");
  if (!alvo) return;

  async function buscarOficiais() {
    const todos = [];
    let pagina = 1;
    let paginas = 1;
    do {
      const resposta = await fetch("/api/jogos?escopo=oficiais&por_pagina=50&pagina=" + pagina, {
        credentials: "same-origin",
        headers: { Accept: "application/json" }
      });
      if (!resposta.ok) throw new Error("HTTP " + resposta.status);
      const dados = await resposta.json();
      todos.push(...dados.dados);
      paginas = dados.paginacao.paginas;
      pagina += 1;
    } while (pagina <= paginas);
    return todos;
  }

  buscarOficiais().then(jogos => {
    if (jogos.length === 0) return;
    const generos = new Set(jogos.map(jogo => jogo.genero)).size;
    alvo.textContent = jogos.length + (jogos.length === 1 ? " título · " : " títulos · ")
      + generos + (generos === 1 ? " gênero" : " gêneros") + " · sua seleção, no seu ritmo";
  }).catch(() => { /* mantém o texto do HTML */ });
})();
