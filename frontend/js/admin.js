/* Administração de usuários (a API também exige perfil admin: esta tela é só conveniência). */
(function () {
  "use strict";
  const api = window.GameShelfAPI;
  const $ = id => document.getElementById(id);
  let eu = null;
  let pagina = 1;
  let totalPaginas = 1;

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
    tr.appendChild(criar("td", "", u.nome));
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
    const excluir = criar("button", "botao botao--remover", "Excluir");
    excluir.type = "button";
    excluir.disabled = u.id === eu.id;
    excluir.addEventListener("click", async () => {
      if (!window.confirm("Excluir a conta de " + u.nome + " e todos os jogos cadastrados por ela?")) return;
      try {
        await api.del("/api/usuarios/" + u.id);
        aviso("Usuário excluído.");
        await carregar();
      } catch (erro) { tratarErro(erro); }
    });
    tdAcoes.appendChild(excluir);
    tr.appendChild(tdAcoes);
    return tr;
  }

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
