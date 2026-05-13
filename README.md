# File Storage API

API RESTful para armazenamento seguro de arquivos desenvolvida em PHP 8.0+ com Slim Framework 4. A API fornece endpoints para upload, download e listagem de arquivos com autenticação via API Key e validação robusta.

## Status do Projeto

✅ **Em desenvolvimento ativo**

### Componentes Implementados

- ✅ Estrutura base com Slim Framework 4
- ✅ Autenticação via API Key (middleware)
- ✅ Upload de arquivos com validação
- ✅ Download de arquivos por ID/nome
- ✅ Persistência em MySQL/MariaDB
- ✅ Docker Compose para desenvolvimento
- ✅ Validação de MIME types e extensões
- ✅ Sanitização e proteção contra path traversal
- ✅ Injeção de dependências com PHP-DI
- ✅ Error handling centralizado

## Requisitos

- **PHP** >= 8.0
- **Composer** >= 2.0
- **Docker** e **Docker Compose** (recomendado para desenvolvimento)
- **MySQL 8.0** ou **MariaDB 10.4+**

## Dependências Principais

```json
{
  "slim/slim": "4.*",
  "slim/psr7": "^1.8",
  "vlucas/phpdotenv": "^5.6",
  "php-di/php-di": "^7.1"
}
```

## Arquitetura

### Estrutura de Diretórios

```
/public
  index.php                 # Ponto de entrada da aplicação
  .htaccess                 # Reescrita de URLs Apache

/src                        # Código-fonte (PSR-4: namespace App\)
  Config/
    AppConfig.php           # Configurações da aplicação
    Database.php            # Configuração de banco de dados
  Controller/
    FileController.php      # Controller para operações de arquivo
  Middleware/
    ApiKeyMiddleware.php    # Autenticação via API Key
  Model/
    FileRecord.php          # Modelo de arquivo
  Repository/
    FileRepository.php      # Acesso a dados (Data Access)
  Routes/
    api.php                 # Definição de rotas
  Service/
    FileStorageService.php  # Lógica de armazenamento
    FileValidator.php       # Validação de arquivos

/database
  schema.sql                # Schema do banco de dados

/storage                    # Diretório privado para arquivos (não versionado)

/vendor                     # Dependências Composer (não versionado)

docker-compose.yml          # Orquestração de containers
Dockerfile                  # Imagem Docker PHP
composer.json               # Definição de dependências
.env.default                # Variáveis de ambiente padrão
```

## Quick Start com Docker

### 1. Iniciar Containers

```bash
docker compose up -d
```

Aguarde os serviços iniciarem (tipicamente 10-15 segundos).

### 2. Instalar Dependências

```bash
docker compose exec web composer install
```

### 3. Configurar Banco de Dados

```bash
# Criar banco de dados
docker compose exec db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS file_storage_api;"

# Executar schema
docker compose exec db mysql -u root -proot file_storage_api < database/schema.sql
```

### 4. Configurar Variáveis de Ambiente

```bash
cp .env.default .env
```

Edite `.env` se necessário (os padrões funcionam com Docker).

### 5. Testar API

```bash
# Verificar se a API está rodando
curl http://localhost:8080/

# Deve retornar:
# {"name":"File Storage API","version":"1.0.0","authentication":"API Key required only for upload and list operations"}
```

### Parar Ambiente

```bash
docker compose down
```

## Configuração

### Variáveis de Ambiente (.env)

```env
# Banco de Dados
DB_HOST=db                          # Host do banco (db = container Docker)
DB_NAME=file_storage_api            # Nome do banco
DB_USER=file_storage_api            # Usuário
DB_PASSWORD=file_storage_api        # Senha do usuário
DB_ROOT_PASSWORD=root               # Senha do root

# API
API_KEY=sua-chave-segura-aqui       # Chave de autenticação (mude em produção!)
APP_DEBUG=false                     # Debug mode (true apenas em desenvolvimento)

# Armazenamento
STORAGE_PATH=/var/www/html/storage  # Caminho para armazenar arquivos

# Limites
MAX_FILE_SIZE=52428800              # Tamanho máximo (padrão: 50MB)
```

## Funcionalidades

### Segurança

- 🔐 **Autenticação API Key**: Endpoints protegidos requerem autenticação
- 🛡️ **Validação de Arquivo**: Whitelist de MIME types e extensões
- 🚫 **Path Traversal Protection**: Prevenção de acesso a diretórios
- 📝 **Sanitização de Nomes**: Normalização e sanitização de filenames
- 🔒 **Security Headers**: CSP, X-Frame-Options, X-Content-Type-Options

### Funcionalidades de API

- 📤 **Upload de Arquivos**: Suporte a múltiplos tipos
- 📥 **Download por ID**: Acesso direto via identificador único
- 📋 **Listagem Paginada**: Visualização de todos os arquivos
- 🗂️ **Metadados**: Armazenamento de nome original, MIME type, tamanho, data
- ♻️ **Rename Seguro**: Sanitização automática de nomes com ID único

## Endpoints API

### 1. Informações da API (Público)

```http
GET /
```

**Resposta de sucesso (200)**:
```json
{
  "name": "File Storage API",
  "version": "1.0.0",
  "authentication": "API Key required only for upload and list operations"
}
```

---

### 2. Upload de Arquivo (Protegido)

```http
POST /api/files
Content-Type: multipart/form-data
X-API-Key: sua-api-key

Body:
- file: <arquivo>
- description: (opcional, descrição do arquivo)
```

**Resposta de sucesso (200)**:
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file": {
    "id": 1,
    "original_name": "documento.pdf",
    "stored_name": "documento_abc123def456.pdf",
    "mime_type": "application/pdf",
    "size": 102400,
    "created_at": "2026-05-12 14:30:00"
  }
}
```

**Resposta de erro - Validação (400)**:
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

**Resposta de erro - API Key inválida (401)**:
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Invalid or missing API Key"
}
```

---

### 3. Download de Arquivo (Público)

```http
GET /api/files/{stored_name}
```

**Resposta de sucesso (200)**:
- Retorna o arquivo binário com headers apropriados
- Content-Type definido baseado no MIME type armazenado
- Content-Disposition com nome original do arquivo

**Resposta de erro (404)**:
```json
{
  "success": false,
  "error": "File not found"
}
```

---

### 4. Listar Arquivos (Protegido)

```http
GET /api/files?limit=50&offset=0
X-API-Key: sua-api-key
```

**Parâmetros de query**:
- `limit`: Número de resultados por página (padrão: 50, máximo: 100)
- `offset`: Número de registros a pular para paginação (padrão: 0)

**Resposta de sucesso (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "original_name": "documento.pdf",
      "stored_name": "documento_abc123def456.pdf",
      "mime_type": "application/pdf",
      "size": 102400,
      "created_at": "2026-05-12 14:30:00"
    }
  ],
  "pagination": {
    "limit": 50,
    "offset": 0,
    "total": 1
  }
}
```

---

## Tipos de Arquivos Permitidos

### Categorias

| Categoria | Extensões |
|-----------|-----------|
| **Imagens** | jpg, jpeg, png, gif, webp, svg |
| **Documentos** | pdf, txt, csv, doc, docx, xls, xlsx |
| **Arquivos compactados** | zip, rar, 7z, tar, gz |

### Restrições de Segurança

Arquivos com as seguintes extensões são **bloqueados por segurança**:
- Executáveis: exe, bat, cmd, com, msi, scr
- Scripts: php, asp, aspx, jsp, py, sh, bash, rb
- Arquivos de configuração: env, conf, config
- Metadados: json, xml (com restrições)

---

## Padrões de Segurança

### Validação

✅ Validação dupla de tipos (extensão + MIME type)
✅ Limite de tamanho configurável (padrão: 50MB)
✅ Sanitização de nomes de arquivo
✅ Prevenção de path traversal
✅ Proteção contra header injection

### Autenticação

✅ API Key obrigatória em endpoints protegidos
✅ Header customizado: `X-API-Key`
✅ Mude a chave padrão em produção

### Headers de Segurança

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'none'; script-src 'none'
```

---

## Testes da API

### cURL

**Upload**:
```bash
curl -X POST http://localhost:8080/api/files \
  -H "X-API-Key: sua-api-key" \
  -F "file=@/caminho/documento.pdf"
```

**Download**:
```bash
curl -O http://localhost:8080/api/files/documento_abc123def456.pdf
```

**Listar**:
```bash
curl -H "X-API-Key: sua-api-key" \
  http://localhost:8080/api/files?limit=10
```

### Postman/Insomnia

1. Importe a URL base: `http://localhost:8080`
2. Defina header `X-API-Key` em cada requisição protegida
3. Use `multipart/form-data` para upload

---

## Integração com ERP (Delphi/Indy)

Exemplo de upload com componentes Indy:

```delphi
// Configurar IdHTTP
IdHTTP1.Request.CustomHeaders.AddValue('X-API-Key', 'sua-api-key');

// Criar multipart form
FormData := TIdMultiPartFormDataStream.Create;
try
  FormData.AddFile('file', 'caminho\documento.pdf', 'application/pdf');
  FormData.AddFormField('description', 'Documento importante');
  
  Response := IdHTTP1.Post('http://localhost:8080/api/files', FormData);
  ShowMessage(Response);
finally
  FormData.Free;
end;
```

---

## Troubleshooting

### Erro: "Connection refused"
- Verifique se Docker está rodando: `docker ps`
- Verifique a porta: `docker logs file-storage-api-web-1`

### Erro: "Database connection failed"
- Aguarde inicialização do MySQL (10-15 segundos)
- Verifique credenciais em `.env`
- Verifique schema: `docker compose exec db mysql -u root -proot file_storage_api -e "SHOW TABLES;"`

### Erro: "API Key invalid"
- Verifique o header `X-API-Key`
- Confirme que a chave em `.env` está correta
- Headers em cURL: `-H "X-API-Key: valor"`

### Erro: "File validation failed"
- Verifique se o tipo é permitido
- Verifique tamanho do arquivo vs `MAX_FILE_SIZE`
- Verifique se a extensão é permitida

---

## Desenvolvimento

### Instalar dependências (sem Docker)

```bash
composer install
```

### Executar servidor embutido (sem Docker)

```bash
# Criar banco de dados localmente
php -r "require 'vendor/autoload.php'; \
  \$db = new mysqli('localhost', 'root', '', 'file_storage_api'); \
  if (\$db->connect_error) die('Erro: ' . \$db->connect_error);"

# Executar servidor
php -S localhost:8080 -t public/
```
