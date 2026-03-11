# Sistema de Vendas (Laravel 12 + Docker)

Sistema simples para registrar vendas com os campos:

- Mes da venda (`YYYY-MM`)
- Nome da pessoa
- Valor do consumo
- Data e hora da venda (automatico)

## Stack

- PHP 8.4 (FPM)
- Laravel 12
- MySQL 8.4
- Nginx
- Docker Compose

## Estrutura Docker

- `app`: container PHP-FPM com Laravel
- `nginx`: servidor web na porta `8081`
- `db`: MySQL na porta `33060`

## Como executar

1. Subir os containers:

```bash
docker compose up -d --build
```

2. Instalar dependencias PHP dentro do container:

```bash
docker compose exec app composer install
```

3. Gerar chave da aplicacao (se necessario):

```bash
docker compose exec app php artisan key:generate
```

4. Rodar migrations:

```bash
docker compose exec app php artisan migrate
```

5. Acessar no navegador:

`http://localhost:8081`

## Banco de dados

As variaveis ja estao configuradas no `.env` para uso com o container MySQL:

- `DB_HOST=db`
- `DB_PORT=3306`
- `DB_DATABASE=vendas`
- `DB_USERNAME=vendas_user`
- `DB_PASSWORD=vendas_pass`

## Rotas principais

- `GET /` lista e formulario de vendas
- `POST /vendas` registra uma venda
