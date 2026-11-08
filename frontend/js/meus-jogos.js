/* Acervo pessoal. A lista em memória mantém o uso possível se salvar falhar. */
(function () {
  "use strict";
  // Compatibilidade com o acervo salvo pela versão anterior na mesma origem.
  const CHAVE = "pixelteca:meus-jogos";
  const CHAVE_REMOVIDOS = "gameshelf:removidos";
  const lista = document.getElementById("lista-jogos");
  const formulario = document.getElementById("form-meu-jogo");
  const validacao = window.GameShelfValidacao;
  if (!lista || !formulario || !validacao) return;
  const bloco = document.getElementById("adicionar");
  const retorno = document.getElementById("retorno-meu-jogo");
  const retornoTexto = document.getElementById("retorno-meu-jogo-texto");
  const estado = document.getElementById("estado-acervo");
  const restaurar = document.getElementById("restaurar-catalogo");
  const desfazer = document.getElementById("desfazer-remocao");
  // Guardamos os próprios nós para restaurar também as fichas e as fontes.
  const iniciais = new Map(Array.from(lista.querySelectorAll(".jogo"), no => [no.dataset.id, no]));
  let ultimaRemocao = null;
  const nomesPlataformas = { pc: "PC", playstation: "PlayStation", xbox: "Xbox", switch: "Switch" };
  const consoles = Array.from(document.getElementById("mj-console").options, opcao => opcao.value);
  const familiaConsole = { pc: "pc", ps2: "playstation", ps3: "playstation", ps4: "playstation", ps5: "playstation",
    xbox: "xbox", xbox360: "xbox", xboxone: "xbox", xboxseries: "xbox", switch: "switch", switch2: "switch" };
  const generos = Array.from(document.getElementById("mj-genero").options, opcao => opcao.value);
  let sequencia = 0;

  function avisar(mensagem) {
    estado.textContent = mensagem;
    estado.hidden = false;
  }
  function novoId() {
    sequencia += 1;
    return "meu-" + Date.now() + "-" + sequencia;
  }
  function numeroValido(valor, max, passo) {
    return valor === null || (typeof valor === "number" && Number.isFinite(valor) && valor >= 0 && valor <= max
      && Math.abs(valor / passo - Math.round(valor / passo)) < 0.000001);
  }
  function carregar() {
    try {
      const bruto = window.localStorage.getItem(CHAVE);
      if (!bruto) return [];
      const dados = JSON.parse(bruto);
      if (!Array.isArray(dados)) throw new Error("Lista inválida");
      const ids = new Set();
      const validos = dados.filter(j => j && typeof j.titulo === "string" && j.titulo.trim().length > 0
        && j.titulo.length <= 80 && generos.includes(j.genero) && Array.isArray(j.plataformas)
        && j.plataformas.every(p => Object.hasOwn(nomesPlataformas, p))
        && numeroValido(j.duracao, 500, 1) && numeroValido(j.metacritic, 100, 1)
        && numeroValido(j.comunidade, 10, 0.1) && typeof j.resenha === "string" && j.resenha.length <= 600
      ).map(j => {
        let id = typeof j.id === "string" && /^[a-zA-Z0-9-]{1,80}$/.test(j.id) ? j.id : novoId();
        if (iniciais.has(id)) id = novoId();
        while (ids.has(id)) id = novoId();
        ids.add(id);
        return { id, titulo: j.titulo.trim(), genero: j.genero, plataformas: [...new Set(j.plataformas)],
          console: consoles.includes(j.console) ? j.console : "",
          estudio: typeof j.estudio === "string" ? j.estudio.slice(0, 100) : "",
          ano: Number.isInteger(j.ano) && j.ano >= 1950 && j.ano <= 2100 ? j.ano : null,
          duracao: j.duracao, metacritic: j.metacritic, comunidade: j.comunidade, resenha: j.resenha };
      });
      if (validos.length !== dados.length) avisar("Alguns registros antigos estavam inválidos e não foram carregados. Os jogos válidos continuam disponíveis.");
      return validos;
    } catch (erro) {
      avisar("Não foi possível ler o acervo salvo. Você pode cadastrar jogos nesta página; se não for possível salvar, eles serão temporários.");
      return [];
    }
  }
  let itens = carregar();
  function carregarRemovidos() {
    try {
      const dados = JSON.parse(window.localStorage.getItem(CHAVE_REMOVIDOS) || "[]");
      if (!Array.isArray(dados)) throw new Error("Lista inválida");
      if (dados.some(id => typeof id !== "string" || !iniciais.has(id))) {
        avisar("A lista de remoções continha registros antigos ou inválidos. Apenas os títulos reconhecidos foram mantidos.");
      }
      return new Set(dados.filter(id => typeof id === "string" && iniciais.has(id)));
    } catch (erro) {
      avisar("Não foi possível ler as remoções salvas. O catálogo inicial foi carregado nesta página.");
      return new Set();
    }
  }
  const removidos = carregarRemovidos();
  function salvarRemovidos() {
    try {
      window.localStorage.setItem(CHAVE_REMOVIDOS, JSON.stringify(Array.from(removidos)));
      return true;
    } catch (erro) { return false; }
  }
  function atualizarAcoes() {
    restaurar.textContent = "Restaurar jogos iniciais (" + removidos.size + ")";
    restaurar.disabled = removidos.size === 0;
    desfazer.hidden = ultimaRemocao === null;
  }
  function avisarAlteracao(mensagem, persistiu) {
    avisar(mensagem + (persistiu ? " Alteração salva neste navegador." : " A alteração não pôde ser salva; o estado anterior pode voltar ao recarregar."));
    atualizarAcoes();
    atualizar();
    estado.setAttribute("tabindex", "-1");
    estado.focus();
  }
  function salvar() {
    try {
      window.localStorage.setItem(CHAVE, JSON.stringify(itens));
      return true;
    } catch (erro) {
      return false;
    }
  }
  function elemento(tag, classe, texto) {
    const no = document.createElement(tag);
    if (classe) no.className = classe;
    if (texto !== undefined) no.textContent = texto;
    return no;
  }
  function capa(titulo) {
    const iniciais = titulo.trim().split(/\s+/).slice(0, 2).map(p => Array.from(p)[0].toUpperCase()).join("");
    const seguro = iniciais.replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&apos;" }[c]));
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460 215">'
      + '<rect width="460" height="215" fill="#292440"/>'
      + '<circle cx="230" cy="107" r="80" fill="#f2b441"/>'
      + '<text x="230" y="130" font-family="sans-serif" font-size="65" text-anchor="middle" fill="#15131f">'
      + seguro + '</text></svg>';
    return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
  }
  function construirCartao(jogo) {
    const li = elemento("li", "jogo meu-jogo");
    Object.assign(li.dataset, { titulo: jogo.titulo, genero: jogo.genero, plataformas: jogo.plataformas.join(" "),
      ano: jogo.ano === null ? "" : String(jogo.ano), nota: jogo.metacritic === null ? "" : String(jogo.metacritic),
      horas: jogo.duracao === null ? "" : String(jogo.duracao), estudio: jogo.estudio, id: jogo.id, consoles: jogo.console || "" });
    li.id = jogo.id;
    const artigo = elemento("article", "cartao");
    const imagem = elemento("img", "cartao__capa");
    // Propriedades DOM: aspas e sinais no título não viram marcação HTML.
    imagem.src = capa(jogo.titulo);
    imagem.alt = "Capa com as iniciais de " + jogo.titulo;
    imagem.width = 460;
    imagem.height = 215;
    imagem.loading = "lazy";
    const corpo = elemento("div", "cartao__corpo");
    corpo.appendChild(elemento("h3", "cartao__titulo", jogo.titulo));
    corpo.appendChild(elemento("p", "cartao__meta", "Desenvolvedora: " + (jogo.estudio || "não informada")));
    if (jogo.ano !== null) corpo.appendChild(elemento("p", "cartao__meta", "Lançamento: " + jogo.ano));
    corpo.appendChild(elemento("p", "cartao__meta", "Seu jogo · " + (jogo.duracao === null ? "duração não informada" : jogo.duracao + " h")));
    if (jogo.comunidade !== null) corpo.appendChild(elemento("p", "cartao__meta", "Sua nota: " + jogo.comunidade.toLocaleString("pt-BR") + " / 10"));
    if (jogo.metacritic !== null) corpo.appendChild(elemento("p", "cartao__meta", "Referência informada por você: " + jogo.metacritic + " / 100"));
    if (jogo.resenha) corpo.appendChild(elemento("p", "cartao__resumo", jogo.resenha));
    const etiquetas = elemento("ul", "etiquetas");
    etiquetas.appendChild(elemento("li", "etiqueta etiqueta--genero", jogo.genero));
    etiquetas.appendChild(elemento("li", "etiqueta etiqueta--pessoal", "Seu acervo"));
    jogo.plataformas.forEach(p => etiquetas.appendChild(elemento("li", "etiqueta", nomesPlataformas[p])));
    if (jogo.console) {
      const opcao = Array.from(document.getElementById("mj-console").options).find(o => o.value === jogo.console);
      if (opcao) etiquetas.appendChild(elemento("li", "etiqueta", opcao.textContent));
    }
    corpo.appendChild(etiquetas);
    const remover = elemento("button", "botao botao--secundario botao--remover", "Remover da lista");
    remover.type = "button";
    remover.dataset.remover = jogo.id;
    remover.setAttribute("aria-label", "Remover " + jogo.titulo + " da lista");
    corpo.appendChild(remover);
    artigo.appendChild(imagem);
    artigo.appendChild(corpo);
    li.appendChild(artigo);
    return li;
  }
  function atualizar() {
    document.dispatchEvent(new CustomEvent("gameshelf:acervo-alterado"));
  }
  const regras = [
    { id: "mj-titulo", obrigatorio: true, maxTexto: 80, erro: "Informe um título de até 80 caracteres." },
    { id: "mj-estudio", maxTexto: 100, erro: "Use até 100 caracteres na desenvolvedora." },
    { id: "mj-ano", tipo: "numero", min: 1950, max: 2100, passo: 1, erro: "Informe um ano entre 1950 e 2100, ou deixe em branco." },
    { id: "mj-duracao", tipo: "numero", min: 0, max: 500, passo: 1, erro: "Informe de 0 a 500 horas, em número inteiro, ou deixe em branco." },
    { id: "mj-metacritic", tipo: "numero", min: 0, max: 100, passo: 1, erro: "Informe uma nota inteira de 0 a 100, ou deixe em branco." },
    { id: "mj-comunidade", tipo: "numero", min: 0, max: 10, passo: 0.1, erro: "Informe uma nota de 0 a 10, com até uma casa decimal, ou deixe em branco." },
    { id: "mj-resenha", maxTexto: 600, erro: "Use até 600 caracteres na resenha." }
  ];
  function valor(id) { return document.getElementById(id).value.trim(); }
  function numeroOpcional(id) { const texto = valor(id); return texto === "" ? null : Number(texto); }
  validacao.preparar(formulario);
  formulario.addEventListener("submit", evento => {
    evento.preventDefault();
    retorno.hidden = true;
    if (!validacao.validar(regras)) return;
    const jogo = { id: novoId(), titulo: valor("mj-titulo"), genero: valor("mj-genero"),
      estudio: valor("mj-estudio"), ano: numeroOpcional("mj-ano"), console: valor("mj-console"),
      plataformas: Array.from(formulario.querySelectorAll('input[name="mj-plataforma"]:checked'), c => c.value),
      duracao: numeroOpcional("mj-duracao"), metacritic: numeroOpcional("mj-metacritic"),
      comunidade: numeroOpcional("mj-comunidade"), resenha: valor("mj-resenha") };
    const familia = familiaConsole[jogo.console];
    if (familia && !jogo.plataformas.includes(familia)) jogo.plataformas.push(familia);
    itens.push(jogo);
    const persistiu = salvar();
    const cartao = construirCartao(jogo);
    lista.appendChild(cartao);
    formulario.reset();
    atualizar();
    let mensagem = jogo.titulo + (persistiu ? " foi adicionado e salvo neste navegador." : " foi adicionado apenas nesta página. Não foi possível salvar; ele pode desaparecer ao recarregar.");
    if (cartao.hidden) mensagem += " Limpe os filtros para encontrá-lo na lista.";
    retornoTexto.textContent = mensagem;
    retorno.hidden = false;
    retorno.setAttribute("tabindex", "-1");
    retorno.focus();
  });
  lista.addEventListener("click", evento => {
    const botao = evento.target.closest("[data-remover]");
    if (!botao) return;
    const id = botao.dataset.remover;
    const no = botao.closest(".jogo");
    if (!no) return;
    let persistiu;
    if (iniciais.has(id)) {
      removidos.add(id);
      ultimaRemocao = { tipo: "inicial", id, no };
      persistiu = salvarRemovidos();
    } else {
      const jogo = itens.find(item => item.id === id);
      if (!jogo) return;
      itens = itens.filter(item => item.id !== id);
      ultimaRemocao = { tipo: "pessoal", jogo, no };
      persistiu = salvar();
    }
    no.remove();
    avisarAlteracao(no.dataset.titulo + " foi removido do catálogo.", persistiu);
  });
  desfazer.addEventListener("click", () => {
    if (!ultimaRemocao) return;
    const ultima = ultimaRemocao;
    let persistiu;
    if (ultima.tipo === "inicial") {
      removidos.delete(ultima.id);
      persistiu = salvarRemovidos();
    } else {
      itens.push(ultima.jogo);
      persistiu = salvar();
    }
    lista.appendChild(ultima.no);
    ultimaRemocao = null;
    avisarAlteracao("Remoção de " + ultima.no.dataset.titulo + " desfeita. Os filtros atuais continuam ativos.", persistiu);
  });
  restaurar.addEventListener("click", () => {
    if (removidos.size === 0) return;
    removidos.forEach(id => lista.appendChild(iniciais.get(id)));
    removidos.clear();
    ultimaRemocao = null;
    avisarAlteracao("Jogos iniciais restaurados. Seus cadastros foram preservados. Limpe os filtros para ver todos.", salvarRemovidos());
  });
  iniciais.forEach((no, id) => {
    no.querySelector("[data-remover]").hidden = false;
    if (removidos.has(id)) no.remove();
  });
  itens.forEach(jogo => lista.appendChild(construirCartao(jogo)));
  document.getElementById("acoes-acervo").hidden = false;
  atualizarAcoes();
  bloco.hidden = false;
  atualizar();
  function abrirPelaAncora() {
    if (window.location.hash === "#adicionar") {
      bloco.open = true;
      document.getElementById("mj-titulo").focus();
    }
    const id = window.location.hash.slice(1);
    if (removidos.has(id)) {
      avisar("Este jogo foi removido da sua seleção. Use Restaurar jogos iniciais para recuperá-lo.");
    }
  }
  window.addEventListener("hashchange", abrirPelaAncora);
  abrirPelaAncora();
})();
