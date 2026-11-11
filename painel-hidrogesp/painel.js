'use strict'

// Os leads vêm de formulários públicos: tudo entra na tela por textContent, nunca como HTML.

const API = '../api/painel/'
const SITUACOES = { novo: 'Novo', em_contato: 'Em contato', cliente: 'Virou cliente', descartado: 'Descartado' }
const PAPEIS = { admin: 'Administrador', equipe: 'Equipe' }

const $ = (id) => document.getElementById(id)
const estado = { usuario: null, csrf: '', pagina: 1, aba: 'leads' }

// --- Comunicação --------------------------------------------------------------

async function api(caminho, corpo) {
  const opcoes = { method: corpo === undefined ? 'GET' : 'POST', credentials: 'same-origin', headers: {} }
  if (corpo !== undefined) {
    opcoes.headers['Content-Type'] = 'application/json'
    opcoes.headers['X-CSRF-Token'] = estado.csrf
    opcoes.body = JSON.stringify(corpo)
  }
  let resposta
  try {
    resposta = await fetch(API + caminho, opcoes)
  } catch {
    return { status: 0, dados: { ok: false, erro: 'Sem conexão com o servidor.' } }
  }
  const dados = await resposta.json().catch(() => ({ ok: false, erro: 'Resposta inválida do servidor.' }))
  if (resposta.status === 401 && caminho !== 'login.php') mostrarLogin()
  return { status: resposta.status, dados }
}

// --- Telas --------------------------------------------------------------------

function mostrar(tela) {
  $('carregando').hidden = true
  for (const id of ['telaLogin', 'telaSenha', 'telaPainel']) $(id).hidden = id !== tela
}

function mostrarMensagem(id, texto) {
  $(id).textContent = texto || ''
  $(id).hidden = !texto
}

function mostrarLogin() {
  estado.usuario = null
  estado.csrf = ''
  mostrar('telaLogin')
}

function entrar(usuario, csrf) {
  estado.usuario = usuario
  estado.csrf = csrf
  $('nomeUsuario').textContent = `${usuario.nome} · ${PAPEIS[usuario.papel] || usuario.papel}`
  $('abaUsuarios').hidden = usuario.papel !== 'admin'
  if (usuario.papel !== 'admin' && estado.aba === 'usuarios') estado.aba = 'leads'
  mostrar('telaPainel')
  abrirAba(estado.aba)
}

function abrirAba(aba) {
  estado.aba = aba
  for (const botao of document.querySelectorAll('.aba')) {
    if (botao.dataset.aba === aba) botao.setAttribute('aria-current', 'page')
    else botao.removeAttribute('aria-current')
  }
  $('secaoLeads').hidden = aba !== 'leads'
  $('secaoUsuarios').hidden = aba !== 'usuarios'
  mostrarMensagem('erroPainel')
  mostrarMensagem('okPainel')
  if (aba === 'usuarios') carregarUsuarios()
  else carregarLeads()
}

/** Botão que pede um segundo clique antes de executar uma ação importante. */
function confirmarAntes(botao, rotulo, acao) {
  botao.textContent = rotulo
  botao.addEventListener('click', () => {
    if (botao.dataset.confirmando) {
      delete botao.dataset.confirmando
      acao()
      return
    }
    botao.dataset.confirmando = '1'
    botao.textContent = 'Confirmar?'
    setTimeout(() => {
      delete botao.dataset.confirmando
      botao.textContent = rotulo
    }, 4000)
  })
  return botao
}

function elemento(tag, texto, classe) {
  const el = document.createElement(tag)
  if (texto !== undefined && texto !== null) el.textContent = texto
  if (classe) el.className = classe
  return el
}

function linha() {
  const tr = document.createElement('tr')
  const td = (conteudo) => {
    const c = document.createElement('td')
    if (conteudo instanceof Node) c.append(conteudo)
    else c.textContent = conteudo ?? ''
    tr.append(c)
    return c
  }
  return { tr, td }
}

const dataCurta = (valor) =>
  valor ? new Date(valor.replace(' ', 'T')).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—'

// --- Leads ---------------------------------------------------------------------

const naLixeira = () => $('filtroLixeira').value === '1'

function filtros() {
  const p = new URLSearchParams()
  for (const [id, nome] of [['filtroLixeira', 'lixeira'], ['filtroTipo', 'tipo'], ['filtroSituacao', 'situacao'], ['filtroBusca', 'busca']]) {
    const valor = $(id).value.trim()
    if (valor) p.set(nome, valor)
  }
  return p
}

let pedidoLeads = 0

async function carregarLeads() {
  const p = filtros()
  $('exportar').href = `${API}exportar.php?${p}`
  p.set('pagina', estado.pagina)

  // Só a resposta do pedido mais recente vale: uma busca lenta não pode sobrescrever uma mais nova.
  const pedido = ++pedidoLeads
  const { status, dados } = await api(`leads.php?${p}`)
  if (pedido !== pedidoLeads || status === 401) return
  if (!dados.ok) return mostrarMensagem('erroPainel', dados.erro)

  estado.pagina = dados.pagina
  $('totLeads').textContent = dados.resumo.total
  $('totPj').textContent = dados.resumo.pj
  $('totPf').textContent = dados.resumo.pf
  $('totNovos').textContent = dados.resumo.novos
  $('totLixeira').textContent = dados.resumo.lixeira

  const corpo = $('linhasLeads')
  corpo.replaceChildren()
  for (const lead of dados.lista) corpo.append(linhaLead(lead))
  $('vazioLeads').textContent = naLixeira() ? 'A lixeira está vazia.' : 'Nenhum lead encontrado.'
  $('vazioLeads').hidden = dados.lista.length > 0
  $('infoPagina').textContent = `Página ${dados.pagina} de ${dados.paginas} · ${dados.total} ${naLixeira() ? 'na lixeira' : 'leads'}`
  $('anterior').disabled = dados.pagina <= 1
  $('proxima').disabled = dados.pagina >= dados.paginas
}

function linhaLead(lead) {
  const { tr, td } = linha()
  td(dataCurta(lead.criado_em))
  td(elemento('span', lead.tipo, lead.tipo === 'PF' ? 'selo pf' : 'selo'))
  td(lead.nome)
  const empresa = td(lead.empresa || '')
  if (lead.cnpj) empresa.append(elemento('small', lead.cnpj))
  td([lead.cidade, lead.uf].filter(Boolean).join(' / '))
  td(lead.telefone)
  td(lead.email)
  td(lead.campanha || lead.assunto || '')

  const seletor = document.createElement('select')
  seletor.setAttribute('aria-label', `Situação de ${lead.nome}`)
  for (const [valor, rotulo] of Object.entries(SITUACOES)) seletor.add(new Option(rotulo, valor))
  seletor.value = lead.situacao
  seletor.disabled = Boolean(lead.excluido_em)
  seletor.addEventListener('change', async () => {
    const { dados } = await api('leads.php', { acao: 'situacao', id: Number(lead.id), situacao: seletor.value })
    if (!dados.ok) {
      seletor.value = lead.situacao
      return mostrarMensagem('erroPainel', dados.erro)
    }
    lead.situacao = seletor.value
    mostrarMensagem('erroPainel')
    carregarLeads()
  })
  td(seletor)

  const acoes = elemento('div', null, 'acoes')
  const nome = lead.nome || lead.empresa || lead.telefone || 'lead sem nome'
  if (lead.excluido_em) {
    acoes.append(confirmarAntes(elemento('button', null, 'leve'), 'Restaurar', () =>
      acaoLead({ acao: 'restaurar', id: Number(lead.id) }, `Lead de ${nome} restaurado.`),
    ))
    // Apagar de vez só existe na lixeira e só para administradores (o servidor confere também).
    if (estado.usuario.papel === 'admin') {
      acoes.append(confirmarAntes(elemento('button', null, 'perigo'), 'Apagar de vez', () =>
        acaoLead({ acao: 'apagar', id: Number(lead.id) }, `Lead de ${nome} apagado definitivamente.`),
      ))
    }
    acoes.append(elemento('small', `Excluído em ${dataCurta(lead.excluido_em)}`))
  } else {
    acoes.append(confirmarAntes(elemento('button', null, 'perigo'), 'Excluir', () =>
      acaoLead({ acao: 'excluir', id: Number(lead.id) }, `Lead de ${nome} enviado para a lixeira.`),
    ))
  }
  td(acoes)
  return tr
}

async function acaoLead(corpo, mensagemOk) {
  mostrarMensagem('erroPainel')
  mostrarMensagem('okPainel')
  const { dados } = await api('leads.php', corpo)
  if (!dados.ok) return mostrarMensagem('erroPainel', dados.erro)
  mostrarMensagem('okPainel', mensagemOk)
  carregarLeads()
}

// --- Usuários ------------------------------------------------------------------

async function carregarUsuarios() {
  const { status, dados } = await api('usuarios.php')
  if (status === 401) return
  if (!dados.ok) return mostrarMensagem('erroPainel', dados.erro)
  const corpo = $('linhasUsuarios')
  corpo.replaceChildren()
  for (const u of dados.usuarios) corpo.append(linhaUsuario(u))
}

function linhaUsuario(u) {
  const { tr, td } = linha()
  const eu = u.id === estado.usuario.id

  td(eu ? `${u.nome} (você)` : u.nome)
  td(u.email)

  if (eu) {
    td(PAPEIS[u.papel])
  } else {
    const papel = document.createElement('select')
    papel.setAttribute('aria-label', `Papel de ${u.nome}`)
    for (const [valor, rotulo] of Object.entries(PAPEIS)) papel.add(new Option(rotulo, valor))
    papel.value = u.papel
    papel.addEventListener('change', () => acaoUsuario({ acao: 'papel', id: u.id, papel: papel.value }, `Papel de ${u.nome} alterado.`))
    td(papel)
  }

  td(elemento('span', u.ativo ? 'Ativo' : 'Desativado', u.ativo ? 'selo' : 'selo inativo'))
  td(dataCurta(u.ultimo_login))

  const acoes = elemento('div', null, 'acoes')
  if (!eu) {
    const definir = elemento('button', 'Definir senha', 'leve')
    definir.type = 'button'
    definir.addEventListener('click', () => abrirDefinirSenha(acoes, u))
    acoes.append(definir)
    acoes.append(confirmarAntes(elemento('button', null, u.ativo ? 'perigo' : 'leve'), u.ativo ? 'Desativar' : 'Reativar', () =>
      acaoUsuario({ acao: 'ativo', id: u.id, ativo: !u.ativo }, u.ativo ? `${u.nome} foi desativado.` : `${u.nome} foi reativado.`),
    ))
  }
  td(acoes)
  return tr
}

/** Troca os botões da linha por um campo para o administrador digitar a nova senha. */
function abrirDefinirSenha(acoes, u) {
  const campo = document.createElement('input')
  campo.type = 'password'
  campo.autocomplete = 'new-password'
  campo.placeholder = 'Nova senha (12+ caracteres)'
  campo.setAttribute('aria-label', `Nova senha de ${u.nome}`)
  const salvar = elemento('button', 'Salvar')
  salvar.type = 'button'
  const cancelar = elemento('button', 'Cancelar', 'leve')
  cancelar.type = 'button'

  salvar.addEventListener('click', () => acaoUsuario({ acao: 'senha', id: u.id, senha: campo.value }, `Senha de ${u.nome} redefinida. As sessões abertas dele foram encerradas.`))
  cancelar.addEventListener('click', carregarUsuarios)
  campo.addEventListener('keydown', (e) => { if (e.key === 'Enter') salvar.click() })

  acoes.replaceChildren(campo, salvar, cancelar)
  campo.focus()
}

async function acaoUsuario(corpo, mensagemOk) {
  mostrarMensagem('erroPainel')
  mostrarMensagem('okPainel')
  const { dados } = await api('usuarios.php', corpo)
  if (!dados.ok) return mostrarMensagem('erroPainel', dados.erro)
  mostrarMensagem('okPainel', mensagemOk)
  carregarUsuarios()
}

// --- Eventos -------------------------------------------------------------------

$('formLogin').addEventListener('submit', async (evento) => {
  evento.preventDefault()
  const form = evento.target
  mostrarMensagem('erroLogin')
  const { dados } = await api('login.php', { email: form.email.value, senha: form.senha.value })
  if (!dados.ok) return mostrarMensagem('erroLogin', dados.erro)
  form.reset()
  entrar(dados.usuario, dados.csrf)
})

$('abrirSenha').addEventListener('click', () => {
  $('formSenha').reset()
  mostrarMensagem('erroSenha')
  mostrar('telaSenha')
})
$('cancelarSenha').addEventListener('click', () => entrar(estado.usuario, estado.csrf))

$('formSenha').addEventListener('submit', async (evento) => {
  evento.preventDefault()
  const form = evento.target
  if (form.nova.value !== form.repetir.value) return mostrarMensagem('erroSenha', 'A nova senha e a repetição não são iguais.')
  const { dados } = await api('senha.php', { atual: form.atual.value, nova: form.nova.value })
  if (!dados.ok) return mostrarMensagem('erroSenha', dados.erro)
  form.reset()
  entrar(dados.usuario, dados.csrf)
  mostrarMensagem('okPainel', 'Sua senha foi alterada.')
})

$('sair').addEventListener('click', async () => {
  await api('logout.php', {})
  $('linhasLeads').replaceChildren()
  $('linhasUsuarios').replaceChildren()
  mostrarLogin()
})

for (const botao of document.querySelectorAll('.aba')) botao.addEventListener('click', () => abrirAba(botao.dataset.aba))

for (const id of ['filtroLixeira', 'filtroTipo', 'filtroSituacao']) {
  $(id).addEventListener('change', () => {
    estado.pagina = 1
    carregarLeads()
  })
}
let esperaBusca
$('filtroBusca').addEventListener('input', () => {
  clearTimeout(esperaBusca)
  esperaBusca = setTimeout(() => {
    estado.pagina = 1
    carregarLeads()
  }, 350)
})
$('anterior').addEventListener('click', () => { estado.pagina--; carregarLeads() })
$('proxima').addEventListener('click', () => { estado.pagina++; carregarLeads() })

$('mostrarSenha').addEventListener('change', (e) => {
  $('formUsuario').senha.type = e.target.checked ? 'text' : 'password'
})

$('formUsuario').addEventListener('submit', async (evento) => {
  evento.preventDefault()
  const form = evento.target
  mostrarMensagem('erroPainel')
  mostrarMensagem('okPainel')
  const corpo = { acao: 'criar', nome: form.nome.value, email: form.email.value, papel: form.papel.value, senha: form.senha.value }
  const { dados } = await api('usuarios.php', corpo)
  if (!dados.ok) return mostrarMensagem('erroPainel', dados.erro)
  mostrarMensagem('okPainel', `Usuário ${corpo.nome} criado.`)
  form.reset()
  form.senha.type = 'password'
  carregarUsuarios()
})

for (const [valor, rotulo] of Object.entries(SITUACOES)) $('filtroSituacao').add(new Option(rotulo, valor))

// Início: descobre se já existe uma sessão aberta.
;(async () => {
  const { status, dados } = await api('sessao.php')
  if (dados.ok) entrar(dados.usuario, dados.csrf)
  else if (status !== 401) mostrarLogin()
})()
