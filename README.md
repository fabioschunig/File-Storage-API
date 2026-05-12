# File Storage API

API para armazenamento de arquivos desenvolvida em PHP com Slim Framework. Arquivos são armazenados em diretório privado e acessíveis via API protegida por API Key.

## Requisitos

- PHP >= 8.0
- Composer
- Docker e Docker Compose (para desenvolvimento)
- MySQL/MariaDB

## Estrutura do Projeto

```
/public
  index.php       # Ponto de entrada da API
  .htaccess       # Reescrita de URLs
/src              # Código da aplicação (namespace App\)
  Config/         # Configurações (AppConfig, Database)
  Controller/     # Controllers (FileController)
  Middleware/     # Middlewares (ApiKeyMiddleware)
  Model/          # Modelos (FileRecord)
  Repository/     # Acesso a dados (FileRepository)
  Routes/         # Definição de rotas (api.php)
  Service/        # Lógica de negócio (FileStorageService, FileValidator)
/storage          # Diretório privado para arquivos
/database         # Scripts SQL
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

### Criar banco de dados

```bash
docker compose exec db mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS file_storage_api;"
docker compose exec db mysql -u root -p file_storage_api < database/schema.sql
```

### Parar ambiente

```bash
docker compose down
```

A API estará disponível em `http://localhost:8080/`

## Configuração

Copie `.env.default` para `.env` e ajuste as variáveis:

```bash
cp .env.default .env
```

```env
# Database (Docker)
DB_ROOT_PASSWORD=root
DB_HOST=db
DB_NAME=file_storage_api
DB_USER=file_storage_api
DB_PASSWORD=file_storage_api

# API Key (defina uma chave segura)
API_KEY=your-secure-api-key-change-this

# Storage
STORAGE_PATH=/var/www/html/storage

# Upload Limits
MAX_FILE_SIZE=52428800
```

## Funcionalidades

- **Autenticação via API Key**: Acesso protegido por chave de API
- **Validação de arquivos**: Whitelist de MIME types e extensões
- **Limite de tamanho**: Configurável via MAX_FILE_SIZE (padrão: 50MB)
- **Sanitização de filenames**: Prevenção de path traversal e header injection
- **Security headers**: CSP, X-Frame-Options, X-Content-Type-Options
- **Upload de arquivos**: Suporte a múltiplos tipos de arquivo
- **Download via ID**: Acesso direto ao arquivo pelo ID
- **Listagem paginada**: Visualização de todos os arquivos

## Endpoints API

### Upload de arquivo
```http
POST /api/files
Content-Type: multipart/form-data
X-API-Key: sua-api-key

Body:
- file: (arquivo)
- metadata: (opcional, descrição)
```

Resposta de erro de validação:
```json
{
  "success": false,
  "error": "File validation failed",
  "details": [
    "MIME type \"application/x-php\" is not allowed",
    "Extension \".php\" is not allowed"
  ]
}
```

### Download de arquivo
```http
GET /api/files/{id}
X-API-Key: sua-api-key
```

### Listar arquivos
```http
GET /api/files?limit=100&offset=0
X-API-Key: sua-api-key
```

## Tipos de Arquivos Permitidos

**Imagens**: jpg, jpeg, png, gif, webp, svg
**Documentos**: pdf, txt, csv, doc, docx, xls, xlsx
**Arquivos compactados**: zip, rar, 7z, tar, gz

## Segurança

- API Key obrigatória para todas as requisições
- Validação de MIME types e extensões (whelist)
- Sanitização de filenames (previne path traversal)
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options)
- Bloqueio de acesso a arquivos sensíveis (.env, composer.json)
- Limite de tamanho de arquivo configurável

## Integração ERP (Delphi/Indy)

Exemplo de upload com componentes Indy:

```delphi
// Configurar IdHTTP
IdHTTP1.Request.CustomHeaders.AddValue('X-API-Key', 'sua-api-key');

// Criar multipart form
FormData := TIdMultiPartFormDataStream.Create;
try
  FormData.AddFile('file', 'caminho\arquivo.pdf', 'application/pdf');
  FormData.AddFormField('metadata', 'Descrição do arquivo');
  
  Response := IdHTTP1.Post('http://api-url/api/files', FormData);
finally
  FormData.Free;
end;
```
