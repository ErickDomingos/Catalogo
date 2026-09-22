/* Um estado por jogo, separado dos cadastros e das remoções. */
(function () {
  "use strict";
  const CHAVE = "gameshelf:listas:v1";
  const lista = document.getElementById("lista-jogos");
  const painel = document.getElementById("minhas-listas");
  const filtro = document.getElementById("minha-lista");
  const retorno = document.getElementById("estado-listas");
  if (!lista || !painel || !filtro || !retorno) return;
  const nomes = { nenhuma: "Sem lista", quero: "Quero jogar", jogando: "Jogando", joguei: "Já joguei", zerado: "Zerado" };
  let estados = new Map();
  function avisar(texto) { retorno.textContent = texto; retorno.hidden = false; }
  try {
    const dados = JSON.parse(window.localStorage.getItem(CHAVE) || "[]");
    if (!Array.isArray(dados) || dados.length > 10000) throw new Error("Formato inválido");
    const validos = dados.filter(item => Array.isArray(item) && item.length === 2
      && typeof item[0] === "string" && /^[a-zA-Z0-9-]{1,100}$/.test(item[0])
      && Object.hasOwn(nomes, item[1]) && item[1] !== "nenhuma");
    estados = new Map(validos);
    if (validos.length !== dados.length) avisar("Algumas marcações inválidas foram ignoradas. As marcações válidas foram mantidas.");
  } catch (erro) {
    avisar("Não foi possível ler suas listas salvas. Você pode organizar os jogos nesta página; o salvamento pode não estar disponível.");
  }
  function salvar() {
    try { window.localStorage.setItem(CHAVE, JSON.stringify(Array.from(estados))); return true; }
    catch (erro) { return false; }
  }
  function sincronizar() {
    const contagens = { todas: 0, nenhuma: 0, quero: 0, jogando: 0, joguei: 0, zerado: 0 };
    lista.querySelectorAll(".jogo").forEach(jogo => {
      const id = jogo.dataset.id;
      const estado = estados.get(id) || "nenhuma";
      jogo.dataset.lista = estado;
      contagens.todas += 1; contagens[estado] += 1;
      let campo = jogo.querySelector("[data-estado-jogo]");
      if (!campo) {
        const bloco = document.createElement("div"); bloco.className = "estado-jogo";
        const rotulo = document.createElement("label"); rotulo.className = "campo__rotulo";
        rotulo.setAttribute("for", "lista-" + id); rotulo.textContent = "Na minha lista";
        campo = document.createElement("select"); campo.id = "lista-" + id;
        campo.dataset.estadoJogo = id;
        campo.setAttribute("aria-label", "Lista de " + jogo.dataset.titulo);
        Object.entries(nomes).forEach(([valor, nome]) => {
          const opcao = document.createElement("option"); opcao.value = valor; opcao.textContent = nome;
          campo.appendChild(opcao);
        });
        bloco.appendChild(rotulo); bloco.appendChild(campo);
        const corpo = jogo.querySelector(".cartao__corpo");
        corpo.insertBefore(bloco, corpo.querySelector("[data-remover]"));
      }
      campo.value = estado;
    });
    painel.querySelectorAll("[data-lista]").forEach(botao => {
      const valor = botao.dataset.lista;
      botao.querySelector("strong").textContent = String(contagens[valor]);
      botao.setAttribute("aria-pressed", String(filtro.value === valor));
    });
  }
  lista.addEventListener("change", evento => {
    const campo = evento.target.closest("[data-estado-jogo]");
    if (!campo || !Object.hasOwn(nomes, campo.value)) return;
    const jogo = campo.closest(".jogo");
    const id = jogo.dataset.id;
    if (campo.value === "nenhuma") estados.delete(id); else estados.set(id, campo.value);
    const persistiu = salvar();
    sincronizar();
    document.dispatchEvent(new CustomEvent("gameshelf:lista-alterada"));
    avisar(jogo.dataset.titulo + ": " + nomes[campo.value] + ". "
      + (persistiu ? "Marcação salva neste navegador." : "Não foi possível salvar; a marcação vale apenas nesta página.")
      + (jogo.hidden ? " O jogo saiu do filtro atual. Escolha outra lista para encontrá-lo." : ""));
    if (jogo.hidden) retorno.focus();
  });
  painel.addEventListener("click", evento => {
    const botao = evento.target.closest("[data-lista]");
    if (!botao) return;
    filtro.value = botao.dataset.lista;
    sincronizar();
    document.dispatchEvent(new CustomEvent("gameshelf:lista-alterada"));
  });
  document.getElementById("form-filtros").addEventListener("change", sincronizar);
  document.getElementById("form-filtros").addEventListener("reset", () => window.setTimeout(sincronizar, 0));
  document.addEventListener("gameshelf:acervo-alterado", sincronizar);
  painel.hidden = false;
  sincronizar();
})();
