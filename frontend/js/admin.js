/* Administração de usuários (a API também exige perfil admin: esta tela é só conveniência). */
(function () {
  "use strict";
  const api = window.GameShelfAPI;
  const $ = id => document.getElementById(id);
  let eu = null;
  let pagina = 1;
  let totalPaginas = 1;
  let donoAtual = null;
  let paginaJogos = 1;
  let totalPaginasJogos = 1;
  const PLATAFORMAS = { pc: "PC", playstation: "PlayStation", xbox: "Xbox", switch: "Switch" };

  function criar(tag, classe, texto) {
    const no = document.createElement(tag);
    if (classe) no.className = classe;
    if (texto !== undefined) no.textContent = texto;
    return no;
  }
  function aviso(texto, erro) {
    const caixa = $("mensagem");
    caixa.textContent = texto;
    caixa.classList.toggle("mensagem--erro", Boolean(erro));
    caixa.hidden = false;
  }
  function tratarErro(erro) {
    if (erro.status === 401) { window.location.href = "entrar.html"; return; }
    aviso(erro.message, true);
  }

  function linha(u) {
    const tr = criar("tr");
    tr.appendChild(criar("td", "", u.nome + (u.protegido ? " (administrador padrão)" : "")));
    tr.appendChild(criar("td", "", u.email));

    const tdPerfil = criar("td");
    const select = criar("select");
    select.id = "perfil-" + u.id;
    select.setAttribute("aria-label", "Perfil de " + u.nome);
    [["usuario", "Usuário"], ["admin", "Administrador"]].forEach(([valor, nome]) => {
      const o = criar("option", "", nome);
      o.value = valor;
      select.appendChild(o);
    });
    select.value = u.perfil;
    select.disabled = Boolean(u.protegido);
    select.addEventListener("change", async () => {
      try {
        await api.patch("/api/usuarios/" + u.id, { perfil: select.value });
        aviso("Perfil de " + u.nome + " atualizado.");
      } catch (erro) {
        select.value = u.perfil;
        tratarErro(erro);
      }
    });
    tdPerfil.appendChild(select);
    tr.appendChild(tdPerfil);

    const tdAcoes = criar("td");
    const jogos = criar("button", "botao botao--secundario", "Jogos (" + (u.total_jogos || 0) + ")");
    jogos.type = "button";
    jogos.setAttribute("aria-label", "Ver os jogos de " + u.nome);
    jogos.addEventListener("click", () => abrirJogos(u));
    tdAcoes.appendChild(jogos);
    const excluir = criar("button", "botao botao--remover", "Excluir");
    excluir.type = "button";
    excluir.disabled = u.id === eu.id || Boolean(u.protegido);
    if (u.protegido) excluir.title = "O administrador padrão não pode ser excluído.";
    excluir.addEventListener("click", async () => {
      if (!window.confirm("Excluir a conta de " + u.nome + " e todos os jogos cadastrados por ela?")) return;
      try {
        await api.del("/api/usuarios/" + u.id);
        aviso("Usuário excluído.");
        if (donoAtual && donoAtual.id === u.id) fecharJogos();
        await carregar();
      } catch (erro) { tratarErro(erro); }
    });
    tdAcoes.appendChild(excluir);
    tr.appendChild(tdAcoes);
    return tr;
  }

  function linhaJogo(j) {
    const tr = criar("tr");
    tr.appendChild(criar("td", "", j.titulo));
    tr.appendChild(criar("td", "", j.genero));
    tr.appendChild(criar("td", "", j.ano === null ? "—" : String(j.ano)));
    tr.appendChild(criar("td", "", j.plataformas.map(p => PLATAFORMAS[p] || p).join(", ") || "—"));
    const td = criar("td");
    const excluir = criar("button", "botao botao--remover", "Excluir");
    excluir.type = "button";
    excluir.setAttribute("aria-label", "Excluir " + j.titulo);
    excluir.addEventListener("click", async () => {
      if (!window.confirm("Excluir \"" + j.titulo + "\" de " + donoAtual.nome + "? Esta ação não pode ser desfeita.")) return;
      try {
        await api.del("/api/jogos/" + j.id);
        aviso("\"" + j.titulo + "\" foi excluído.");
        await carregarJogos();
        await carregar();
      } catch (erro) { tratarErro(erro); }
    });
    td.appendChild(excluir);
    tr.appendChild(td);
    return tr;
  }

  async function carregarJogos() {
    try {
      const dados = await api.get("/api/usuarios/" + donoAtual.id + "/jogos?pagina=" + paginaJogos + "&por_pagina=10");
      totalPaginasJogos = dados.paginacao.paginas;
      if (paginaJogos > totalPaginasJogos) { paginaJogos = totalPaginasJogos; return carregarJogos(); }
      $("jogos-usuario").replaceChildren(...dados.dados.map(linhaJogo));
      $("jogos-vazio").hidden = dados.paginacao.total !== 0;
      $("pagina-jogos").textContent = "Página " + dados.paginacao.pagina + " de " + totalPaginasJogos;
      $("anterior-jogos").disabled = paginaJogos <= 1;
      $("proxima-jogos").disabled = paginaJogos >= totalPaginasJogos;
    } catch (erro) { tratarErro(erro); }
  }
  function abrirJogos(u) {
    donoAtual = u;
    paginaJogos = 1;
    $("jogos-de").textContent = u.nome + " (" + u.email + ")";
    $("painel-jogos").hidden = false;
    carregarJogos().then(() => $("t-jogos").focus());
  }
  function fecharJogos() {
    donoAtual = null;
    $("painel-jogos").hidden = true;
  }
  $("anterior-jogos").addEventListener("click", () => { if (paginaJogos > 1) { paginaJogos -= 1; carregarJogos(); } });
  $("proxima-jogos").addEventListener("click", () => { if (paginaJogos < totalPaginasJogos) { paginaJogos += 1; carregarJogos(); } });
  $("fechar-jogos").addEventListener("click", fecharJogos);

  async function carregar() {
    try {
      const dados = await api.get("/api/usuarios?pagina=" + pagina + "&por_pagina=20");
      totalPaginas = dados.paginacao.paginas;
      $("usuarios").replaceChildren(...dados.dados.map(linha));
      $("pagina-atual").textContent = "Página " + dados.paginacao.pagina + " de " + totalPaginas;
      $("anterior").disabled = pagina <= 1;
      $("proxima").disabled = pagina >= totalPaginas;
    } catch (erro) { tratarErro(erro); }
  }
  $("anterior").addEventListener("click", () => { if (pagina > 1) { pagina -= 1; carregar(); } });
  $("proxima").addEventListener("click", () => { if (pagina < totalPaginas) { pagina += 1; carregar(); } });

  (async function iniciar() {
    try {
      eu = (await api.get("/api/auth/me")).usuario;
    } catch (erro) {
      window.location.href = "entrar.html";
      return;
    }
    if (eu.perfil !== "admin") {
      aviso("Esta área é exclusiva para administradores.", true);
      document.querySelector("section.painel").hidden = true;
      return;
    }
    carregar();
  })();
})();
