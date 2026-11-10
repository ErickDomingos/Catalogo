/* Cliente da API do GameShelf: cookie de sessão + token CSRF renovado automaticamente. */
(function () {
  "use strict";
  let csrf = null;

  async function token() {
    if (!csrf) {
      const resposta = await fetch("/api/csrf", { credentials: "same-origin" });
      csrf = (await resposta.json()).csrf_token;
    }
    return csrf;
  }

  async function chamar(metodo, url, corpo) {
    const opcoes = { method: metodo, credentials: "same-origin", headers: { Accept: "application/json" } };
    if (corpo !== undefined) {
      opcoes.headers["Content-Type"] = "application/json";
      opcoes.body = JSON.stringify(corpo);
    }
    if (metodo !== "GET") opcoes.headers["X-CSRF-Token"] = await token();

    let resposta = await fetch(url, opcoes);
    if (resposta.status === 403 && metodo !== "GET") {
      const falha = await resposta.clone().json().catch(() => null);
      if (falha && falha.erro && falha.erro.codigo === "csrf_invalido") {
        csrf = null; // token expirou ou foi renovado: tenta uma única vez com um novo
        opcoes.headers["X-CSRF-Token"] = await token();
        resposta = await fetch(url, opcoes);
      }
    }
    if (metodo === "POST" && url.indexOf("/api/auth/") === 0) csrf = null; // login/logout renovam o token

    if (resposta.status === 204) return null;
    const dados = await resposta.json().catch(() => null);
    if (!resposta.ok) {
      const e = (dados && dados.erro) || {};
      const erro = new Error(e.mensagem || "Não foi possível concluir a operação.");
      erro.status = resposta.status;
      erro.codigo = e.codigo || "erro";
      erro.campos = e.campos || {};
      throw erro;
    }
    return dados;
  }

  window.GameShelfAPI = {
    get: url => chamar("GET", url),
    post: (url, corpo) => chamar("POST", url, corpo),
    put: (url, corpo) => chamar("PUT", url, corpo),
    patch: (url, corpo) => chamar("PATCH", url, corpo),
    del: url => chamar("DELETE", url),
  };
})();
