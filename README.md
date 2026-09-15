
## Descrição

Projeto de gerenciamento de eventos da Fastbar.

## Instalação


1. **Clone o repositório**
```
git@github.com:fastbarofc/fastbar-api.git
```
2. **Acesse o diretório do projeto**
```
cd fastbar-api
```
3. **Copie o arquivo .env.example para .env**
```
cp .env.example .env
```
4. **Instale as dependências do projeto utilizando Docker**
```
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs
```
5. **Inicie o servidor**
```
sail up -d
```
6. **Instale as dependências do Composer**
```
sail composer install
```
7. **Rode as migrações do banco de dados**
```
sail artisan migrate:fresh --seed
```
8. **Crie um client para que o frontend possa se comunicar com a API**
```
sail artisan passport:client --personal
```

Você receberá um retorno parecido com esse:
```
Client ID: 9a4bca9c-d821-4fbf-91da-8dc592b7ac46
Client secret: pwQ9U7nAtVt5d2RrL8hSJxjJCaO7G76ZCDhxMjBg
```

Salve o Client ID e o Client secret em um arquivo .env na raiz do projeto.

Você deverá enviar esses campos na requisição de login do usuário para que o Laravel Passport possa gerar um token de acesso.