# 🧰 Projeto (Laravel 11) — GraphQL + Nginx (HTTPS) + Postgres

> Stack: **Laravel 11**, **Lighthouse GraphQL**, **JWT**, **PostgreSQL**, **Nginx** com **OpenSSL** (certificado autoassinado) e **GraphiQL** em `/graphiql`.

---

## ✅ Pré‑requisitos
- **Git**
- **Docker** e **Docker Compose** (recomendado) ou PHP ≥ 8.2, Composer ≥ 2.6

---

## 🚀 Clonar o projeto
```bash
# Substitua <seu-repo> pelo URL real do repositório
git clone <seu-repo>
cd <pasta-do-projeto>
```

---

## ⚙️ Configuração de ambiente
Crie o arquivo `.env` baseado no exemplo e ajuste as variáveis do Postgres e da aplicação.

```bash
cp .env.example .env
```

## 🔐 Chaves da aplicação e JWT
Gere a chave do Laravel e a **chave do JWT**:

```bash
# se estiver usando Docker, rode dentro do container app (ver seção Docker)
php artisan key:generate
php artisan jwt:secret
```

---


### 🔏 OpenSSL (certificado autoassinado)
Crie os diretórios e gere o certificado local:
```bash
mkdir -p docker/nginx/certs
openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout docker/nginx/certs/localhost.key \
  -out docker/nginx/certs/localhost.crt \
  -subj "/C=BR/ST=AM/L=Manaus/O=Dev/OU=Local/CN=localhost"
```
> Ao acessar via `https://localhost`, seu navegador pode alertar sobre certificado não confiável (autoassinado). Prossiga/autorize para ambiente local.

### ▶️ Subir os serviços
```bash
docker compose -f docker-composer.yml up -d --build
```

### ⏳ Migrações e seeds automáticos
Na **primeira execução**, o backend pode rodar `migrate --seed` automaticamente (conforme scripts do projeto). Aguarde concluir. Você pode acompanhar os logs:
```bash
docker compose logs -f app
```

Se preferir executar manualmente:
```bash
docker compose exec app php artisan migrate --seed
```

## ⭐ Cobertura rápida (destaque)
Para ver a **cobertura de testes** diretamente no terminal ou acessar container, rode:
```bash
php artisan test --coverage
```

---

## 💻 Execução sem Docker (opcional)
1) Suba seu Postgres local com as credenciais do `.env`.
2) Instale dependências e gere chaves:
```bash
composer install
php artisan key:generate
php artisan jwt:secret
```
3) Migrações e seeds:
```bash
php artisan migrate --seed
```
4) Servidor local (apenas HTTP):
```bash
php artisan serve --port=8000
```
> Para HTTPS local com Nginx, use a configuração de Nginx/OpenSSL acima apontando `root` para `public/`.

---

## 🎯 Acessar o GraphiQL
- **Local**: https://localhost/graphiql
- **Cloud (EC2)**: https://ec2-98-86-111-21.compute-1.amazonaws.com/graphiql

> Se o certificado da EC2 for válido, não haverá alerta. Caso esteja autoassinado, autorize no navegador.

### Endpoint GraphQL
```
POST /graphql
```

### Headers
Antes de autenticar:

Após login (JWT):
```json
{
  "Authorization": "Bearer <SEU_ACCESS_TOKEN>"
}
```

---

## 🔑 Fluxo rápido de autenticação (exemplo)
1) **Login** mutation → copie `access_token` do retorno
2) Cole o token nos **HTTP Headers** como `Authorization: Bearer ...`
3) Execute `me` / mutations protegidas normally

> O token expira conforme `expires_in`. Use a mutation `refresh` para obter um novo antes de expirar.

---

## 🧪 Verificação rápida
- `GET` em **/graphiql** deve abrir a IDE
- Mutation **login** deve retornar o `access_token`
- Query **me** deve responder com os dados do usuário autenticado
- Mutations de **Bank** (criarConta/sacar/depositar) só funcionam com **JWT** válido

---

## 🛠️ Comandos úteis
```bash
# acessar o container app
docker compose exec app bash

# rodar testes
php artisan test

# limpar caches
php artisan optimize:clear

# rodar apenas migrações
php artisan migrate
```

---

## 📚 Exemplos GraphQL (colapsáveis)
Para não alongar o README, os exemplos ficam escondidos em blocos expansíveis. Você pode copiar e colar cada grupo no **GraphiQL** e selecionar a *Operation Name*.

<details>
<summary><strong>🔐 Autenticação (Register, Login, Me)</strong></summary>

```graphql
mutation Register($inputRegis: RegisterInput!) {
  register(input: $inputRegis) {
    access_token
    token_type
    expires_in
    user { id name email }
  }
}

mutation Login($input: LoginInput!) {
  login(input: $input) {
    access_token
    token_type
    expires_in
    user { id name email }
  }
}

query Me {
  me {
    id
    name
    email
    created_at
    updated_at
  }
}
```

**Variables (GraphiQL → Query Variables)**
```json
{
  "inputRegis": {
    "name": "darlan",
    "email": "teste@teste.com",
    "password": "123456",
    "confirm_password": "123456"
  },
  "input": {
    "email": "teste@teste.com",
    "password": "123456"
  }
}
```
</details>

<details>
<summary><strong>👤 Usuários (consultas)</strong></summary>

```graphql
query GetUserByIdOrId($id: ID) {
  user(id: $id) {
    id
    name
    email
    created_at
  }
}

query GetUserByIdOrEmail($email: String) {
  user(email: $email) {
    id
    name
    email
    created_at
  }
}

query ListUsers($name: String) {
  users(name: $name) {
    data {
      id
      name
      email
    }
    paginatorInfo {
      currentPage
      lastPage
      total
    }
  }
}
```

**Variables — exemplos**
```json
// GetUserByIdOrId
{ "id": "1" }
```
```json
// GetUserByIdOrEmail
{ "email": "teste@teste.com" }
```
```json
// ListUsers (LIKE)
{ "name": "%da%" }
```
</details>

<details>
<summary><strong>🏦 Bank (criarConta, saldo, depositar, sacar) — requer JWT</strong></summary>

```graphql
mutation CriarConta($conta: Int!, $saldoInicial: Float, $user_id: ID) {
  criarConta(conta: $conta, saldoInicial: $saldoInicial, user_id: $user_id) {
    conta
    saldo
  }
}

query Saldo($conta: Int!) {
  saldo(conta: $conta)
}

mutation Depositar($conta: Int!, $valor: Float!) {
  depositar(conta: $conta, valor: $valor) {
    conta
    saldo
  }
}

mutation Sacar($conta: Int!, $valor: Float!) {
  sacar(conta: $conta, valor: $valor) {
    conta
    saldo
  }
}
```

**Variables (exemplo)**
```json
{
  "conta": 1001,
  "saldoInicial": 50.0,
  "user_id": "1",
  "valor": 90
}
```

> Lembre-se de colocar o header **Authorization: Bearer &lt;TOKEN&gt;** nas operações protegidas.
</details>

<details>
<summary><strong>📦 Arquivos de exemplo (opcional)</strong></summary>
Crie uma pasta `docs/graphql/` com os arquivos:

- `auth.graphql` → Register, Login, Me + `auth.variables.json`
- `users.graphql` → GetUserByIdOrId, GetUserByIdOrEmail, ListUsers + `users.variables.json`
- `bank.graphql` → CriarConta, Saldo, Depositar, Sacar + `bank.variables.json`

Assim você pode arrastar o conteúdo para o GraphiQL conforme necessário, sem poluir o README.
</details>

---

## 📜 Licença
Defina aqui a licença do projeto (MIT, proprietária, etc.).

