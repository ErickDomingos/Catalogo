/* Tela "Meu acervo": CRUD de jogos e listas pessoais via API. Todo texto entra no DOM com textContent. */
(function () {
  "use strict";
  const api = window.GameShelfAPI;
  const $ = id => document.getElementById(id);
  const ESTADOS = { nenhuma: "Sem lista", quero: "Quero jogar", jogando: "Jogando", joguei: "Já joguei", zerado: "Zerado" };
  const PLATAFORMAS = { pc: "PC", playstation: "PlayStation", xbox: "Xbox", switch: "Switch" };
  const CAMPOS = ["titulo", "estudio", "ano", "genero", "console", "duracao", "metacritic", "comunidade", "resenha"];
  const NUMERICOS = ["ano", "duracao", "metacritic", "comunidade"];
  let usuario = null;
  let pagina = 1;
  let totalPaginas = 1;
  let editando = null;

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

  /* ---------- formulário ---------- */
  function limparErros() {
    $("mensagem").hidden = true;
    document.querySelectorAll("#form-jogo .erro-campo").forEach(p => { p.hidden = true; });
    document.querySelectorAll("#form-jogo [aria-invalid]").forEach(c => c.removeAttribute("aria-invalid"));
  }
  function mostrarErrosCampos(campos) {
    let foco = null;
    Object.keys(campos).forEach(campo => {
      const alvo = $("jg-" + campo + "-erro");
      if (!alvo) return;
      alvo.textContent = campos[campo];
      alvo.hidden = false;
      const entrada = $("jg-" + campo) || $("jg-plat-pc");
      if (entrada) { entrada.setAttribute("aria-invalid", "true"); foco = foco || entrada; }
    });
    if (foco) foco.focus();
  }
  function lerFormulario() {
    const corpo = {};
    CAMPOS.forEach(campo => {
      const valor = $("jg-" + campo).value.trim();
      if (valor === "") return;
      corpo[campo] = NUMERICOS.includes(campo) ? Number(valor) : valor;
    });
    corpo.plataformas = Array.from(document.querySelectorAll('#form-jogo input[name="plataformas"]:checked'), c => c.value);
    return corpo;
  }
  function preencher(jogo) {
    CAMPOS.forEach(campo => {
      const valor = jogo[campo];
      $("jg-" + campo).value = valor === null || valor === undefined ? "" : String(valor);
    });
    document.querySelectorAll('#form-jogo input[name="plataformas"]').forEach(c => {
      c.checked = jogo.plataformas.includes(c.value);
    });
  }
  function sairDaEdicao() {
    editando = null;
    $("form-jogo").reset();
    $("t-form").textContent = usuario.perfil === "admin" ? "Cadastrar jogo oficial" : "Cadastrar jogo";
    $("salvar").textContent = "Salvar jogo";
    $("cancelar").hidden = true;
    limparErros();
  }
  $("cancelar").addEventListener("click", sairDaEdicao);
  $("form-jogo").addEventListener("submit", async evento => {
    evento.preventDefault();
    limparErros();
    try {
      const corpo = lerFormulario();
      if (editando === null) await api.post("/api/jogos", corpo);
      else await api.put("/api/jogos/" + editando, corpo);
      aviso(editando === null ? "Jogo cadastrado." : "Jogo atualizado.");
      sairDaEdicao();
      await Promise.all([carregarJogos(), carregarListas()]);
    } catch (erro) {
      if (erro.campos && Object.keys(erro.campos).length) { mostrarErrosCampos(erro.campos); aviso(erro.message, true); }
      else tratarErro(erro);
    }
  });

  /* ---------- listas pessoais ---------- */
  async function carregarListas() {
    const dados = await api.get("/api/listas");
    const ul = $("contagens");
    ul.replaceChildren();
    Object.keys(ESTADOS).filter(e => e !== "nenhuma").forEach(estado => {
      ul.appendChild(criar("li", "etiqueta", ESTADOS[estado] + ": " + dados.contagens[estado]));
    });
  }
  async function mudarLista(jogoId, estado) {
    try {
      if (estado === "nenhuma") await api.del("/api/listas/" + jogoId);
      else await api.put("/api/listas/" + jogoId, { estado: estado });
      await carregarListas();
    } catch (erro) { tratarErro(erro); }
  }

  /* ---------- jogos ---------- */
  function cartao(jogo) {
    const li = criar("li");
    const art = criar("article", "cartao");
    if (typeof jogo.capa === "string" && /^img\/capas\/[a-z0-9-]+\.jpg$/.test(jogo.capa)) {
      const img = criar("img", "cartao__capa");
      img.src = jogo.capa;
      img.alt = "Capa de " + jogo.titulo;
      img.width = 460; img.height = 215; img.loading = "lazy";
      art.appendChild(img);
    }
    const corpo = criar("div", "cartao__corpo");
    corpo.appendChild(criar("h3", "cartao__titulo", jogo.titulo));
    corpo.appendChild(criar("p", "cartao__meta", "Desenvolvedora: " + (jogo.estudio || "não informada")));
    if (jogo.ano !== null) corpo.appendChild(criar("p", "cartao__meta", "Lançamento: " + jogo.ano));
    if (jogo.duracao !== null) corpo.appendChild(criar("p", "cartao__meta", "Duração: " + jogo.duracao + " h"));
    if (jogo.metacritic !== null) corpo.appendChild(criar("p", "cartao__meta", "Crítica: " + jogo.metacritic + " / 100"));
    if (jogo.comunidade !== null) corpo.appendChild(criar("p", "cartao__meta", "Nota da comunidade: " + jogo.comunidade + " / 10"));
    if (jogo.resenha) corpo.appendChild(criar("p", "cartao__resumo", jogo.resenha));

    const etiquetas = criar("ul", "etiquetas");
    etiquetas.appendChild(criar("li", "etiqueta etiqueta--genero", jogo.genero));
    jogo.plataformas.forEach(p => etiquetas.appendChild(criar("li", "etiqueta", PLATAFORMAS[p] || p)));
    etiquetas.appendChild(criar("li", "etiqueta etiqueta--pessoal", jogo.oficial ? "Catálogo oficial" : "Seu jogo"));
    corpo.appendChild(etiquetas);

    const bloco = criar("div", "estado-jogo");
    const rotulo = criar("label", "campo__rotulo", "Na minha lista");
    const select = criar("select");
    select.id = "lista-" + jogo.id;
    rotulo.setAttribute("for", select.id);
    Object.keys(ESTADOS).forEach(valor => {
      const opcao = criar("option", "", ESTADOS[valor]);
      opcao.value = valor;
      select.appendChild(opcao);
    });
    select.value = jogo.minha_lista || "nenhuma";
    select.addEventListener("change", () => mudarLista(jogo.id, select.value));
    bloco.append(rotulo, select);
    corpo.appendChild(bloco);

    if (jogo.pode_editar) {
      const acoes = criar("div", "conta-acoes");
      const editar = criar("button", "botao botao--secundario", "Editar");
      editar.type = "button";
      editar.addEventListener("click", () => {
        editando = jogo.id;
        preencher(jogo);
        $("t-form").textContent = "Editar jogo";
        $("salvar").textContent = "Salvar alterações";
        $("cancelar").hidden = false;
        $("jg-titulo").focus();
        $("form-jogo").scrollIntoView({ behavior: "smooth", block: "start" });
      });
      const excluir = criar("button", "botao botao--remover", "Excluir");
      excluir.type = "button";
      excluir.addEventListener("click", async () => {
        if (!window.confirm("Excluir \"" + jogo.titulo + "\"? Esta ação não pode ser desfeita.")) return;
        try {
          await api.del("/api/jogos/" + jogo.id);
          aviso("Jogo excluído.");
          if (editando === jogo.id) sairDaEdicao();
          await Promise.all([carregarJogos(), carregarListas()]);
        } catch (erro) { tratarErro(erro); }
      });
      acoes.append(editar, excluir);
      corpo.appendChild(acoes);
    }
    art.appendChild(corpo);
    li.appendChild(art);
    return li;
  }

  async function carregarJogos() {
    const consulta = new URLSearchParams({ pagina: String(pagina), por_pagina: "12", escopo: $("f-escopo").value, ordem: $("f-ordem").value });
    const busca = $("f-busca").value.trim();
    if (busca) consulta.set("busca", busca);
    try {
      const dados = await api.get("/api/jogos?" + consulta.toString());
      totalPaginas = dados.paginacao.paginas;
      $("jogos").replaceChildren(...dados.dados.map(cartao));
      $("resumo").textContent = dados.paginacao.total + " jogo(s) encontrado(s).";
      $("pagina-atual").textContent = "Página " + dados.paginacao.pagina + " de " + totalPaginas;
      $("anterior").disabled = pagina <= 1;
      $("proxima").disabled = pagina >= totalPaginas;
    } catch (erro) { tratarErro(erro); }
  }
  $("form-filtros").addEventListener("submit", evento => { evento.preventDefault(); pagina = 1; carregarJogos(); });
  $("anterior").addEventListener("click", () => { if (pagina > 1) { pagina -= 1; carregarJogos(); } });
  $("proxima").addEventListener("click", () => { if (pagina < totalPaginas) { pagina += 1; carregarJogos(); } });

  $("sair").addEventListener("click", async () => {
    try { await api.post("/api/auth/logout"); } finally { window.location.href = "entrar.html"; }
  });

  async function iniciar() {
    try {
      usuario = (await api.get("/api/auth/me")).usuario;
    } catch (erro) {
      window.location.href = "entrar.html";
      return;
    }
    $("saudacao").textContent = "Olá, " + usuario.nome + " · perfil: " + (usuario.perfil === "admin" ? "administrador" : "usuário");
    $("barra").hidden = false;
    $("link-admin").hidden = usuario.perfil !== "admin";
    $("dica-form").textContent = usuario.perfil === "admin"
      ? "Como administrador, os jogos que você cadastra entram no catálogo oficial e ficam visíveis para todos."
      : "Jogos cadastrados aqui são só seus: ninguém mais os vê.";
    sairDaEdicao();
    try {
      const generos = (await api.get("/api/generos")).generos;
      $("jg-genero").replaceChildren(...generos.map(g => { const o = criar("option", "", g); o.value = g; return o; }));
      await Promise.all([carregarJogos(), carregarListas()]);
    } catch (erro) { tratarErro(erro); }
  }
  iniciar();
})();
