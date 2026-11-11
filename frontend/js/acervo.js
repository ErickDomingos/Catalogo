/*
 * Acervo do catálogo: jogos pessoais e listas ("Quero jogar", "Jogando"...) vêm da API.
 * É o MESMO banco usado pela página Minha conta, então o que for feito em uma página aparece na outra.
 * Visitante (sem login) continua vendo e filtrando os 128 jogos; cadastrar e organizar listas exige conta.
 * Todo texto vindo do servidor entra no DOM com textContent (sem innerHTML).
 */
(function () {
  "use strict";
  const api = window.GameShelfAPI;
  const validacao = window.GameShelfValidacao;
  const lista = document.getElementById("lista-jogos");
  const formulario = document.getElementById("form-meu-jogo");
  if (!api || !validacao || !lista || !formulario) return;

  const bloco = document.getElementById("adicionar");
  const retorno = document.getElementById("retorno-meu-jogo");
  const retornoTexto = document.getElementById("retorno-meu-jogo-texto");
  const estado = document.getElementById("estado-acervo");
  const painel = document.getElementById("minhas-listas");
  const filtro = document.getElementById("minha-lista");
  const retornoListas = document.getElementById("estado-listas");

  const NOMES = { nenhuma: "Sem lista", quero: "Quero jogar", jogando: "Jogando", joguei: "Já joguei", zerado: "Zerado" };
  const PLATAFORMAS = { pc: "PC", playstation: "PlayStation", xbox: "Xbox", switch: "Switch" };
  const CAPA_VALIDA = /^img\/capas\/[a-z0-9-]+\.jpg$/;

  // Cartões do HTML do Trabalho 1: id "base-<slug>"; o slug é o mesmo da coluna jogos.slug no banco.
  const estaticos = new Map(Array.from(lista.querySelectorAll('.jogo[id^="base-"]'), no => [no.dataset.id.slice(5), no]));

  /* ---------- utilidades ---------- */
  function elemento(tag, classe, texto) {
    const no = document.createElement(tag);
    if (classe) no.className = classe;
    if (texto !== undefined) no.textContent = texto;
    return no;
  }
  function avisar(texto) {
    estado.textContent = texto;
    estado.hidden = false;
  }
  function avisarListas(texto) {
    retornoListas.textContent = texto;
    retornoListas.hidden = false;
  }
  function falar(texto) {
    retornoTexto.textContent = texto;
    retorno.hidden = false;
    retorno.setAttribute("tabindex", "-1");
    retorno.focus();
  }
  function mensagemDeErro(erro) {
    if (erro && erro.status === 401) return "Sua sessão expirou. Entre de novo na página Minha conta e tente outra vez.";
    return (erro && erro.message) || "Não foi possível concluir a operação.";
  }
  function atualizar() {
    document.dispatchEvent(new CustomEvent("gameshelf:acervo-alterado"));
  }
  async function buscarTodos(escopo) {
    const todos = [];
    let pagina = 1;
    let paginas = 1;
    do {
      const consulta = new URLSearchParams({ escopo: escopo, pagina: String(pagina), por_pagina: "50", ordem: "titulo" });
      const dados = await api.get("/api/jogos?" + consulta.toString());
      todos.push(...dados.dados);
      paginas = dados.paginacao.paginas;
      pagina += 1;
    } while (pagina <= paginas);
    return todos;
  }

  /* ---------- listas pessoais ---------- */
  function sincronizar() {
    const contagens = { todas: 0, nenhuma: 0, quero: 0, jogando: 0, joguei: 0, zerado: 0 };
    lista.querySelectorAll(".jogo").forEach(no => {
      const atual = no.dataset.lista || "nenhuma";
      contagens.todas += 1;
      contagens[atual] = (contagens[atual] || 0) + 1;
      const campo = no.querySelector("[data-estado-jogo]");
      if (campo) campo.value = atual;
    });
    painel.querySelectorAll("[data-lista]").forEach(botao => {
      const valor = botao.dataset.lista;
      botao.querySelector("strong").textContent = String(contagens[valor] || 0);
      botao.setAttribute("aria-pressed", String(filtro.value === valor));
    });
  }
  function anexarSeletor(no, jogo) {
    const bloco = elemento("div", "estado-jogo");
    const rotulo = elemento("label", "campo__rotulo", "Na minha lista");
    const campo = elemento("select");
    campo.id = "lista-" + no.dataset.id;
    campo.dataset.estadoJogo = String(jogo.id);
    campo.setAttribute("aria-label", "Lista de " + jogo.titulo);
    rotulo.setAttribute("for", campo.id);
    Object.keys(NOMES).forEach(valor => {
      const opcao = elemento("option", "", NOMES[valor]);
      opcao.value = valor;
      campo.appendChild(opcao);
    });
    bloco.append(rotulo, campo);
    const corpo = no.querySelector(".cartao__corpo");
    corpo.insertBefore(bloco, corpo.querySelector("[data-remover]"));
  }
  lista.addEventListener("change", async evento => {
    const campo = evento.target.closest("[data-estado-jogo]");
    if (!campo) return;
    const no = campo.closest(".jogo");
    const anterior = no.dataset.lista || "nenhuma";
    const novo = campo.value;
    if (!Object.hasOwn(NOMES, novo)) { campo.value = anterior; return; }
    try {
      if (novo === "nenhuma") await api.del("/api/listas/" + no.dataset.jogoId);
      else await api.put("/api/listas/" + no.dataset.jogoId, { estado: novo });
    } catch (erro) {
      campo.value = anterior; // o servidor não aceitou: a tela volta ao que está salvo
      avisarListas(mensagemDeErro(erro));
      return;
    }
    no.dataset.lista = novo;
    sincronizar();
    document.dispatchEvent(new CustomEvent("gameshelf:lista-alterada"));
    avisarListas(no.dataset.titulo + ": " + NOMES[novo] + ". Marcação salva na sua conta."
      + (no.hidden ? " O jogo saiu do filtro atual. Escolha outra lista para encontrá-lo." : ""));
    if (no.hidden) retornoListas.focus();
  });
  painel.addEventListener("click", evento => {
    const botao = evento.target.closest("[data-lista]");
    if (!botao) return;
    filtro.value = botao.dataset.lista;
    sincronizar();
    document.dispatchEvent(new CustomEvent("gameshelf:lista-alterada"));
  });
  const filtros = document.getElementById("form-filtros");
  filtros.addEventListener("change", sincronizar);
  filtros.addEventListener("reset", () => window.setTimeout(sincronizar, 0));

  /* ---------- cartões de jogos que vêm do banco (pessoais e oficiais fora do HTML) ---------- */
  function capaGerada(titulo) {
    const iniciais = titulo.trim().split(/\s+/).slice(0, 2).map(p => Array.from(p)[0].toUpperCase()).join("");
    const seguro = iniciais.replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&apos;" }[c]));
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460 215">'
      + '<rect width="460" height="215" fill="#292440"/>'
      + '<circle cx="230" cy="107" r="80" fill="#f2b441"/>'
      + '<text x="230" y="130" font-family="sans-serif" font-size="65" text-anchor="middle" fill="#15131f">'
      + seguro + "</text></svg>";
    return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
  }
  function construirCartao(jogo) {
    const idDom = (jogo.oficial ? "oficial-" : "meu-") + jogo.id;
    const li = elemento("li", jogo.oficial ? "jogo" : "jogo meu-jogo");
    Object.assign(li.dataset, {
      titulo: jogo.titulo, genero: jogo.genero, plataformas: jogo.plataformas.join(" "),
      ano: jogo.ano === null ? "" : String(jogo.ano), nota: jogo.metacritic === null ? "" : String(jogo.metacritic),
      horas: jogo.duracao === null ? "" : String(jogo.duracao), estudio: jogo.estudio || "", id: idDom,
      consoles: jogo.console || "", jogoId: String(jogo.id), lista: jogo.minha_lista || "nenhuma"
    });
    li.id = idDom;
    const artigo = elemento("article", "cartao");
    const imagem = elemento("img", "cartao__capa");
    imagem.src = typeof jogo.capa === "string" && CAPA_VALIDA.test(jogo.capa) ? jogo.capa : capaGerada(jogo.titulo);
    imagem.alt = "Capa de " + jogo.titulo;
    imagem.width = 460;
    imagem.height = 215;
    imagem.loading = "lazy";
    const corpo = elemento("div", "cartao__corpo");
    corpo.appendChild(elemento("h3", "cartao__titulo", jogo.titulo));
    corpo.appendChild(elemento("p", "cartao__meta", "Desenvolvedora: " + (jogo.estudio || "não informada")));
    if (jogo.ano !== null) corpo.appendChild(elemento("p", "cartao__meta", "Lançamento: " + jogo.ano));
    const horas = jogo.duracao === null ? "não informada" : jogo.duracao + " h";
    corpo.appendChild(elemento("p", "cartao__meta", (jogo.oficial ? "Duração: " : "Seu jogo · duração ") + horas));
    if (jogo.comunidade !== null) corpo.appendChild(elemento("p", "cartao__meta", (jogo.oficial ? "Nota da comunidade: " : "Sua nota: ") + jogo.comunidade.toLocaleString("pt-BR") + " / 10"));
    if (jogo.metacritic !== null) corpo.appendChild(elemento("p", "cartao__meta", (jogo.oficial ? "Nota da crítica: " : "Referência informada por você: ") + jogo.metacritic + " / 100"));
    if (jogo.resenha) corpo.appendChild(elemento("p", "cartao__resumo", jogo.resenha));
    const etiquetas = elemento("ul", "etiquetas");
    etiquetas.appendChild(elemento("li", "etiqueta etiqueta--genero", jogo.genero));
    etiquetas.appendChild(elemento("li", "etiqueta etiqueta--pessoal", jogo.oficial ? "Catálogo oficial" : "Seu acervo"));
    jogo.plataformas.forEach(p => etiquetas.appendChild(elemento("li", "etiqueta", PLATAFORMAS[p] || p)));
    if (jogo.console) {
      const opcao = Array.from(document.getElementById("mj-console").options).find(o => o.value === jogo.console);
      if (opcao) etiquetas.appendChild(elemento("li", "etiqueta", opcao.textContent));
    }
    corpo.appendChild(etiquetas);
    if (jogo.pode_editar) {
      const remover = elemento("button", "botao botao--secundario botao--remover", jogo.oficial ? "Excluir do catálogo" : "Excluir jogo");
      remover.type = "button";
      remover.dataset.remover = idDom;
      remover.setAttribute("aria-label", "Excluir " + jogo.titulo);
      corpo.appendChild(remover);
    }
    artigo.append(imagem, corpo);
    li.appendChild(artigo);
    anexarSeletor(li, jogo);
    return li;
  }

  lista.addEventListener("click", async evento => {
    const botao = evento.target.closest("[data-remover]");
    if (!botao) return;
    const no = botao.closest(".jogo");
    if (!no || !no.dataset.jogoId) return;
    if (!window.confirm("Excluir \"" + no.dataset.titulo + "\"? Esta ação não pode ser desfeita.")) return;
    try {
      await api.del("/api/jogos/" + no.dataset.jogoId);
    } catch (erro) {
      avisar(mensagemDeErro(erro));
      return;
    }
    no.remove();
    sincronizar();
    atualizar();
    avisar(no.dataset.titulo + " foi excluído do seu acervo.");
    estado.setAttribute("tabindex", "-1");
    estado.focus();
  });

  /* ---------- cadastro de jogo ---------- */
  const regras = [
    { id: "mj-titulo", obrigatorio: true, maxTexto: 80, erro: "Informe um título de até 80 caracteres." },
    { id: "mj-estudio", maxTexto: 100, erro: "Use até 100 caracteres na desenvolvedora." },
    { id: "mj-ano", tipo: "numero", min: 1950, max: 2100, passo: 1, erro: "Informe um ano entre 1950 e 2100, ou deixe em branco." },
    { id: "mj-duracao", tipo: "numero", min: 0, max: 500, passo: 1, erro: "Informe de 0 a 500 horas, em número inteiro, ou deixe em branco." },
    { id: "mj-metacritic", tipo: "numero", min: 0, max: 100, passo: 1, erro: "Informe uma nota inteira de 0 a 100, ou deixe em branco." },
    { id: "mj-comunidade", tipo: "numero", min: 0, max: 10, passo: 0.1, erro: "Informe uma nota de 0 a 10, com até uma casa decimal, ou deixe em branco." },
    { id: "mj-resenha", maxTexto: 600, erro: "Use até 600 caracteres na resenha." }
  ];
  const CAMPOS_TEXTO = { titulo: "mj-titulo", estudio: "mj-estudio", genero: "mj-genero", console: "mj-console", resenha: "mj-resenha" };
  const CAMPOS_NUMERO = { ano: "mj-ano", duracao: "mj-duracao", metacritic: "mj-metacritic", comunidade: "mj-comunidade" };

  function lerFormulario() {
    const corpo = {};
    Object.keys(CAMPOS_TEXTO).forEach(campo => {
      const texto = document.getElementById(CAMPOS_TEXTO[campo]).value.trim();
      if (texto !== "") corpo[campo] = texto;
    });
    Object.keys(CAMPOS_NUMERO).forEach(campo => {
      const texto = document.getElementById(CAMPOS_NUMERO[campo]).value.trim();
      if (texto !== "") corpo[campo] = Number(texto);
    });
    corpo.plataformas = Array.from(formulario.querySelectorAll('input[name="mj-plataforma"]:checked'), c => c.value);
    return corpo;
  }
  function marcarCampo(id, texto) {
    const campo = document.getElementById(id);
    if (!campo) return null;
    let erro = document.getElementById(id + "-erro");
    if (!erro) {
      erro = elemento("p", "erro-campo");
      erro.id = id + "-erro";
      campo.parentElement.appendChild(erro);
      campo.setAttribute("aria-describedby", ((campo.getAttribute("aria-describedby") || "") + " " + erro.id).trim());
    }
    erro.textContent = texto;
    erro.hidden = false;
    campo.setAttribute("aria-invalid", "true");
    return campo;
  }
  validacao.preparar(formulario);
  formulario.addEventListener("submit", async evento => {
    evento.preventDefault();
    retorno.hidden = true;
    if (!validacao.validar(regras)) return;
    const corpo = lerFormulario();
    if (corpo.plataformas.length === 0 && !corpo.console) {
      falar("Selecione ao menos uma plataforma ou informe o console da edição.");
      return;
    }
    let jogo;
    try {
      jogo = (await api.post("/api/jogos", corpo)).jogo;
    } catch (erro) {
      // Erros por campo vêm do backend (a validação de verdade); aqui só são mostrados.
      const campos = (erro && erro.campos) || {};
      let foco = null;
      Object.keys(campos).forEach(campo => { foco = foco || marcarCampo("mj-" + campo, campos[campo]); });
      if (foco) foco.focus();
      falar(mensagemDeErro(erro));
      return;
    }
    const cartao = construirCartao(jogo);
    lista.appendChild(cartao);
    formulario.reset();
    sincronizar();
    atualizar();
    falar(jogo.titulo + " foi adicionado e salvo na sua conta. Ele também aparece em Minha conta."
      + (cartao.hidden ? " Limpe os filtros para encontrá-lo na lista." : ""));
  });

  /* ---------- inicialização ---------- */
  function abrirPelaAncora() {
    if (window.location.hash === "#adicionar" && !bloco.hidden) {
      bloco.open = true;
      document.getElementById("mj-titulo").focus();
    }
  }
  function modoVisitante() {
    filtro.closest(".campo").hidden = true; // não há listas para filtrar sem conta
    estado.replaceChildren(
      document.createTextNode("Você está navegando como visitante. "),
      Object.assign(elemento("a", "", "Entre ou crie uma conta"), { href: "entrar.html" }),
      document.createTextNode(" para cadastrar seus jogos e organizar suas listas; tudo fica salvo na sua conta.")
    );
    estado.hidden = false;
  }
  function removerExcluidos(oficiais) {
    // O banco é a fonte da verdade: ficha fixa do HTML sem registro no banco (excluída pelo admin) sai da página.
    const slugs = new Set(oficiais.map(jogo => jogo.slug));
    estaticos.forEach((no, slug) => {
      if (!slugs.has(slug)) { no.remove(); estaticos.delete(slug); }
    });
  }
  function modoLogado(oficiais, meus) {
    oficiais.forEach(jogo => {
      const no = estaticos.get(jogo.slug);
      if (!no) { lista.appendChild(construirCartao(jogo)); return; } // oficial criado por admin, fora do HTML do T1
      no.dataset.jogoId = String(jogo.id);
      no.dataset.lista = jogo.minha_lista || "nenhuma";
      anexarSeletor(no, jogo);
    });
    meus.forEach(jogo => lista.appendChild(construirCartao(jogo)));
    bloco.hidden = false;
    painel.hidden = false;
    sincronizar();
    atualizar();
    abrirPelaAncora();
  }
  async function iniciar() {
    // 1) Catálogo oficial (público): vale para visitante e para quem está logado.
    let oficiais;
    try {
      oficiais = await buscarTodos("oficiais");
    } catch (erro) {
      avisar("Não foi possível falar com o servidor. O catálogo continua disponível para consulta, mas cadastro e listas estão indisponíveis.");
      return;
    }
    if (oficiais.length === 0) {
      avisar("O banco está sem jogos do catálogo. Rode: php backend/database/seed.php");
      return;
    }
    removerExcluidos(oficiais);
    const total = document.getElementById("total-hero");
    if (total) total.textContent = String(oficiais.length);
    atualizar();
    // 2) Sessão: visitante só consulta; logado ganha cadastro e listas.
    try {
      await api.get("/api/auth/me");
    } catch (erro) {
      if (erro && erro.status === 401) modoVisitante();
      else avisar(mensagemDeErro(erro));
      return;
    }
    try {
      modoLogado(oficiais, await buscarTodos("meus"));
    } catch (erro) {
      avisar(mensagemDeErro(erro));
    }
  }
  window.addEventListener("hashchange", abrirPelaAncora);
  iniciar();
})();
