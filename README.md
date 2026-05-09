# File-Storage-API

API para armazenamento de arquivos desenvolvida em PHP sem frameworks. Arquivos são armazenados em diretório privado e acessíveis apenas via links únicos (share by link).

## Requisitos

- PHP >= 8.0
- Composer
- Docker e Docker Compose (para desenvolvimento)
- MySQL/MariaDB

## Estrutura do Projeto

```
/public
  index.php       # Ponto de entrada da API
/src              # Código da aplicação (namespace App\)
/storage          # Diretório privado para arquivos (não acessível via web)
/vendor           # Dependências (gerado pelo Composer)
```

## Desenvolvimento com Docker

### Iniciar ambiente

```bash
docker compose up -d
```

### Instalar dependências

```bash
docker compose exec web composer install
```

### Parar ambiente

```bash
docker compose down
```

A API estará disponível em `http://localhost:8080/`

## Configuração

Crie um arquivo `.env` na raiz do projeto:

```env
# Database (Docker)
DB_ROOT_PASSWORD=root
DB_HOST=db
DB_NAME=file_storage_api
DB_USER=file_storage_api
DB_PASSWORD=file_storage_api

# JWT
JWT_SECRET=sua_chave_secreta_aqui

# Storage
STORAGE_PATH=/var/www/html/storage
```

## Funcionalidades

- **Autenticação JWT**: Login com usuário/senha para obter token
- **Upload de PDFs**: Máximo 9MB, apenas arquivos PDF
- **Share by link**: Cada arquivo recebe um link único para acesso
- **Expiração**: Arquivos são removidos automaticamente após 30 dias
- **Rate limiting**: Proteção contra abuso (16 requisições/minuto)

## Endpoints

- `POST /login` - Autenticação (retorna JWT)
- `POST /upload` - Upload de arquivo (requer JWT)
- `GET /file/{token}` - Acesso ao arquivo via token único
